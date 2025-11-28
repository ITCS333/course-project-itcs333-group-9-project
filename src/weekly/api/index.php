<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
header('Content-Type: application/json');
// Allow cross-origin requests (CORS) if needed
header('Access-Control-Allow-Origin: *');
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// Allow specific headers (Content-Type, Authorization)
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once '../config/Database.php';


// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
$requestBody = file_get_contents('php://input');
// Decode JSON data using json_decode()
$data = json_decode($requestBody, true);


// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';


// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'start_date';
    $sortOrder = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';
    
    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $query = "SELECT id, title, start_date, description, links, created_at FROM weeks";
    
    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    if ($searchTerm) {
        $query .= " WHERE title LIKE :search_title OR description LIKE :search_desc";
    }
    
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sortField, $allowedSortFields)) {
        $sortField = 'start_date';
    }
    
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    if ($sortOrder !== 'asc' && $sortOrder !== 'desc') {
        $sortOrder = 'asc';
    }
    
    // TODO: Add ORDER BY clause to the query
    $query .= " ORDER BY {$sortField} {$sortOrder}";
    
    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($query);
    
    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    if ($searchTerm) {
        $likeTerm = "%" . $searchTerm . "%";
        $stmt->bindValue(':search_title', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search_desc', $likeTerm, PDO::PARAM_STR);
    }
    // echo "GET request received for weeks resource";
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true);
    }

    
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(['success' => true, 'data' => $weeks]);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError("week_id parameter is required", 400);
    }
    
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $query = "SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?";
    $stmt = $db->prepare($query);
    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(1, $weekId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if ($week) {
        $week['links'] = json_decode($week['links'], true);
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendError("Week not found", 404);
    }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    if (empty($data['week_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['description'])) {
        sendError("Missing required fields: week_id, title, start_date, description", 400);
    }
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $weekId = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    
    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
    $startDate = $data['start_date'];
    if (!validateDate($startDate)) {
        sendError("Invalid start_date format. Expected YYYY-MM-DD", 400);
    }
    
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkQuery = "SELECT id FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    $links = [];
    if (isset($data['links']) && is_array($data['links'])) {
        $links = $data['links'];
    }
    $linksJson = json_encode($links);


    
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $insertQuery = "INSERT INTO weeks (id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    
    // TODO: Bind parameters
    $insertStmt->bindParam(1, $weekId);
    $insertStmt->bindParam(2, $title);
    $insertStmt->bindParam(3, $startDate);
    $insertStmt->bindParam(4, $data['description']);
    $insertStmt->bindParam(5, $linksJson);
    
    // TODO: Execute the query
    $insertStmt->execute();
    
    // echo "POST request received for weeks resource";
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if ($insertStmt->rowCount() > 0) {
        $newWeek = [
            'week_id' => $weekId,
            'title' => $title,
            'start_date' => $startDate,
            'description' => $data['description'],
            'links' => $links
        ];
        sendResponse(['success' => true, 'data' => $newWeek], 201);
    } else {
        sendError("Failed to create new week", 500);
    }

}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($data['week_id'])) {
        sendError("week_id is required for update", 400);
    }
    $weekId = $data['week_id'];
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $checkQuery = "SELECT id FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(1, $weekId);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        sendError("Week not found", 404);
    }

    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $values = [];

    
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
    if (isset($data['title'])) {
        $setClauses[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }
    if (isset($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendError("Invalid start_date format. Expected YYYY-MM-DD", 400);
        }
        $setClauses[] = "start_date = ?";
        $values[] = $data['start_date'];
    }
    if (isset($data['description'])) {
        $setClauses[] = "description = ?";
        $values[] = $data['description'];
    }
    if (isset($data['links']) && is_array($data['links'])) {
        $linksJson = json_encode($data['links']);
        $setClauses[] = "links = ?";
        $values[] = $linksJson;
    }

    
    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendError("No fields provided to update", 400);
    }

    
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";

    
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $setClauseStr = implode(", ", $setClauses);
    $updateQuery = "UPDATE weeks SET {$setClauseStr} WHERE id = ?";
    
    
    // TODO: Prepare the query
    $updateStmt = $db->prepare($updateQuery);
    
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    foreach ($values as $index => $value) {
        $updateStmt->bindValue($index + 1, $value);
    }
    $updateStmt->bindValue(count($values) + 1, $weekId);
    
    // TODO: Execute the query
    $updateStmt->execute();

    
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    if ($updateStmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Week updated successfully']);
    } else {
        sendError("Failed to update week or no changes made", 500);
    }
    
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError("week_id parameter is required", 400);
    }

    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkQuery = "SELECT id FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(1, $weekId);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        sendError("Week not found", 404);
    }

    
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    $deleteCommentsQuery = "DELETE FROM comments_week WHERE week_id = ?";
    $deleteCommentsStmt = $db->prepare($deleteCommentsQuery);
    $deleteCommentsStmt->bindParam(1, $weekId);
    
    // TODO: Execute comment deletion query
    $deleteCommentsStmt->execute();
    
    
    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $deleteWeekQuery = "DELETE FROM weeks WHERE id = ?";
    $deleteWeekStmt = $db->prepare($deleteWeekQuery);

    
    // TODO: Bind the week_id parameter
    $deleteWeekStmt->bindParam(1, $weekId);

    
    // TODO: Execute the query
    $deleteWeekStmt->execute();

    
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if ($deleteWeekStmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Week and associated comments deleted successfully']);
    } else {
        sendError("Failed to delete week", 500);
    }

}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError("week_id parameter is required", 400);
    }

    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $query = "SELECT id, week_id, author, text, created_at FROM comments_week WHERE week_id = ? ORDER BY created_at ASC";
    $stmt = $db->prepare($query);

    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(1, $weekId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    sendResponse(['success' => true, 'data' => $comments]);

}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendError("Missing required fields: week_id, author, text", 400);
    }
    $weekId = $data['week_id'];
    $author = $data['author'];
    $text = $data['text'];

    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = sanitizeInput($weekId);
    $author = sanitizeInput($author);
    $text = sanitizeInput($text);

    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if (empty($text)) {
        sendError("Comment text cannot be empty", 400);
    }

    
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $checkQuery = "SELECT id FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(1, $weekId);
    $checkStmt->execute();
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    if ($checkStmt->rowCount() === 0) {
        sendError("Week not found for the provided week_id", 404);
    }
    $insertQuery = "INSERT INTO comments_week (week_id, author, text) VALUES (?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);

    
    // TODO: Bind parameters
    $insertStmt->bindParam(1, $weekId);
    $insertStmt->bindParam(2, $author);
    $insertStmt->bindParam(3, $text);

    
    // TODO: Execute the query
    $insertStmt->execute();

    
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
    if ($insertStmt->rowCount() > 0) {
        $newCommentId = $db->lastInsertId();
        $newComment = [
            'id' => $newCommentId,
            'week_id' => $weekId,
            'author' => $author,
            'text' => $text
        ];
        sendResponse(['success' => true, 'data' => $newComment], 201);
    } else {
        sendError("Failed to create new comment", 500);
    }

}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (empty($commentId)) {
        sendError("Comment id parameter is required", 400);
    }

    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkQuery = "SELECT id FROM comments_week WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(1, $commentId);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        sendError("Comment not found", 404);
    }

    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $deleteQuery = "DELETE FROM comments_week WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);

    
    // TODO: Bind the id parameter
    $deleteStmt->bindParam(1, $commentId);

    
    // TODO: Execute the query
    $deleteStmt->execute();

    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($deleteStmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully']);
    } else {
        sendError("Failed to delete comment", 500);
    }

}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';

    
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            if (isset($_GET['week_id'])) {
                // echo "GET request received for weeks resource with week_id";
                getWeekById($db, $_GET['week_id']);
            } else {
                
                getAllWeeks($db);
                
            }

            
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
           
            createWeek($db, $data);
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db, $data);

            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : (isset($data['week_id']) ? $data['week_id'] : null);
            deleteWeek($db, $weekId);

            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError("Method not allowed for weeks resource", 405);

        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            if (isset($_GET['week_id'])) {
                getCommentsByWeek($db, $_GET['week_id']);
            } else {
                sendError("week_id parameter is required to get comments", 400);
            }

            
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $data);

            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $commentId = isset($_GET['id']) ? $_GET['id'] : (isset($data['id']) ? $data['id'] : null);
            deleteComment($db, $commentId);

            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError("Method not allowed for comments resource", 405);

        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);

    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    sendError("Database error occurred", 500);

    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
    sendError("An unexpected error occurred", 500);

}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);

    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data);

    
    // TODO: Exit to prevent further execution
    exit();

}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $errorResponse = [
        'success' => false,
        'error' => $message
    ];

    
    // TODO: Call sendResponse() with the error array and status code
    sendResponse($errorResponse, $statusCode);

}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;

}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);

    
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);

    
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // TODO: Return sanitized data
    return $data;

}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields);
}

?>
