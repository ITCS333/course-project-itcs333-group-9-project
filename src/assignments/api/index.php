<?php
// Assuming 'Database.php' contains a static method for PDO connection
// For this example to be runnable, Database.php must be defined.
// class Database { public static function getConnection() { /* ... */ } }
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
$_SESSION['user'] = [
    'role' => 'student',
    'logged_in' => true
];

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json');

// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// NOTE: Ensure 'Database.php' is available and functional
if (!file_exists('Database.php')) {
    sendResponse(['success' => false, 'message' => 'Database connection file is missing.'], 500);
}
// TODO: Include the database connection class
require_once 'Database.php';


// TODO: Create database connection
$db = Database::getConnection();

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$requestBody = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters
$queryParams = $_GET;


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * * Query Parameters:
 * - search: Optional search term to filter by title or description
 * - sort: Optional field to sort by (title, due_date, created_at)
 * - order: Optional sort order (asc or desc, default: asc)
 * * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    global $queryParams;
    $search = $queryParams['search'] ?? null;
    $sort = $queryParams['sort'] ?? null;
    $order = $queryParams['order'] ?? 'asc';

    // TODO: Start building the SQL query
    $whereClause = '';
    $params = [];

    // TODO: Check if 'search' query parameter exists in $_GET
    if ($search) {
        $whereClause = 'WHERE title LIKE :search OR description LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    // TODO: Check if 'sort' and 'order' query parameters exist
    $orderClause = '';
    $allowedSorts = ['title', 'due_date', 'created_at'];
    // IMPORTANT: Sanitize $sort and $order to prevent SQL Injection in the ORDER BY clause
    if ($sort && in_array($sort, $allowedSorts) && in_array(strtolower($order), ['asc', 'desc'])) {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $orderClause = "ORDER BY $sort $order";
    }

    // TODO: Prepare the SQL statement using $db->prepare()
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments $whereClause $orderClause";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if search is used
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // TODO: Execute the prepared statement
    $stmt->execute();

    // TODO: Fetch all results as associative array
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($assignments as &$assignment) {
        $assignment['files'] = json_decode($assignment['files'], true) ?? [];
        // Ensure id is an integer for client-side consistency
        $assignment['id'] = (int) $assignment['id'];
    }

    // TODO: Return JSON response
    sendResponse(['success' => true, 'data' => $assignments]);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * * Query Parameters:
 * - id: The assignment ID (required)
 * * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and is a valid integer
    if (!$assignmentId || !filter_var($assignmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        sendResponse(['success' => false, 'message' => 'Valid Assignment ID is required'], 400);
        return;
    }

    // TODO: Prepare SQL query to select assignment by id
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);

    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if assignment was found
    if (!$assignment) {
        sendResponse(['success' => false, 'message' => 'Assignment not found'], 404);
        return;
    }

    // TODO: Decode the 'files' field from JSON to array and convert ID to integer
    $assignment['files'] = json_decode($assignment['files'], true) ?? [];
    $assignment['id'] = (int) $assignment['id'];

    // TODO: Return success response with assignment data
    sendResponse(['success' => true, 'data' => $assignment]);
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * * Required JSON Body:
 * - title: Assignment title (required)
 * - description: Assignment description (required)
 * - due_date: Due date in YYYY-MM-DD format (required)
 * - files: Array of file URLs/paths (optional)
 * * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if (!isset($data['title'], $data['description'], $data['due_date'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields: title, description, or due_date'], 400);
        return;
    }

    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = sanitizeInput($data['due_date']);

    // TODO: Validate due_date format
    if (!validateDate($dueDate)) {
        sendResponse(['success' => false, 'message' => 'Invalid due date format. Use YYYY-MM-DD'], 400);
        return;
    }

    // The SQL table uses AUTO_INCREMENT for ID, so we remove the custom ID generation.
    // $id = 'asg_' . time() . rand(1000, 9999); // REMOVED

    // TODO: Handle the 'files' field
    $files = isset($data['files']) && is_array($data['files']) ? json_encode($data['files']) : json_encode([]);

    // TODO: Prepare INSERT query (removed 'id' column)
    $sql = "INSERT INTO assignments (title, description, due_date, files) VALUES (:title, :description, :due_date, :files)";
    $stmt = $db->prepare($sql);

    // TODO: Bind all parameters
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':due_date', $dueDate);
    $stmt->bindValue(':files', $files);

    // TODO: Execute the statement
    if ($stmt->execute()) {

        // TODO: Get the auto-incremented ID
        $id = $db->lastInsertId();

        // TODO: Check if insert was successful
        sendResponse([
            'success' => true, 
            'message' => 'Assignment created', 
            'data' => [
                'id' => (int)$id, 
                'title' => $title, 
                'description' => $description, 
                'due_date' => $dueDate, 
                'files' => json_decode($files, true)
            ]
        ], 201);
    } else {

        // TODO: If insert failed, return 500 error
        sendResponse(['success' => false, 'message' => 'Failed to create assignment'], 500);
    }
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * * Required JSON Body:
 * - id: Assignment ID (required, to identify which assignment to update)
 * - title: Updated title (optional)
 * - description: Updated description (optional)
 * - due_date: Updated due date (optional)
 * - files: Updated files array (optional)
 * * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided and is a valid integer
    if (!isset($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        sendResponse(['success' => false, 'message' => 'Valid Assignment ID is required for update'], 400);
        return;
    }

    // TODO: Store assignment ID in variable
    $id = (int)$data['id'];

    // TODO: Check if assignment exists
    $sqlCheck = "SELECT id FROM assignments WHERE id = :id";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    if (!$stmtCheck->fetch()) {
        sendResponse(['success' => false, 'message' => 'Assignment not found'], 404);
        return;
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    $updates = [];
    $params = [':id' => $id];
    if (isset($data['title'])) {
        $updates[] = 'title = :title';
        $params[':title'] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $updates[] = 'description = :description';
        $params[':description'] = sanitizeInput($data['description']);
    }
    if (isset($data['due_date'])) {
        $dueDate = sanitizeInput($data['due_date']);
        if (!validateDate($dueDate)) {
            sendResponse(['success' => false, 'message' => 'Invalid due date format'], 400);
            return;
        }
        $updates[] = 'due_date = :due_date';
        $params[':due_date'] = $dueDate;
    }

    // TODO: Check which fields are provided and add to SET clause
    if (isset($data['files']) && is_array($data['files'])) {
        $updates[] = 'files = :files';
        $params[':files'] = json_encode($data['files']);
    }

    // TODO: If no fields to update (besides updated_at), return 400 error
    if (empty($updates)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
        return;
    }

    // TODO: Complete the UPDATE query
    $updates[] = 'updated_at = NOW()';
    $sql = "UPDATE assignments SET " . implode(', ', $updates) . " WHERE id = :id";


    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);

    // TODO: Bind all parameters dynamically
    foreach ($params as $key => $value) {
        // Use PARAM_INT for ID, otherwise use default
        $type = ($key === ':id') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }

    // TODO: Execute the statement
    if ($stmt->execute()) {

        // Check if a row was actually affected
        if ($stmt->rowCount() > 0) {
            // TODO: Check if update was successful
            sendResponse(['success' => true, 'message' => 'Assignment updated']);
        } else {
            sendResponse(['success' => true, 'message' => 'Assignment found, but no changes were made']);
        }
    } else {

        // TODO: If no rows affected, return appropriate message
        sendResponse(['success' => false, 'message' => 'Failed to update assignment'], 500);
    }
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * * Query Parameters:
 * - id: Assignment ID (required)
 * * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and is a valid integer
    if (!$assignmentId || !filter_var($assignmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        sendResponse(['success' => false, 'message' => 'Valid Assignment ID is required'], 400);
        return;
    }

    // Ensure the ID is an integer
    $assignmentId = (int)$assignmentId;

    // TODO: Check if assignment exists
    $sqlCheck = "SELECT id FROM assignments WHERE id = :id";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmtCheck->execute();
    if (!$stmtCheck->fetch()) {
        sendResponse(['success' => false, 'message' => 'Assignment not found'], 404);
        return;
    }

    // **IMPORTANT CORRECTION**: The SQL uses ON DELETE CASCADE for the foreign key, 
    // so we only need to delete the assignment itself. The RDBMS will handle comments.
    // The existing code to delete comments first is redundant but harmless; I'll remove it for efficiency.
    /*
    // TODO: Delete associated comments first (due to foreign key constraint) - REDUNDANT
    $sqlDeleteComments = "DELETE FROM comments_assignment WHERE assignment_id = :assignment_id";
    $stmtDeleteComments = $db->prepare($sqlDeleteComments);
    $stmtDeleteComments->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmtDeleteComments->execute();
    */

    // TODO: Prepare DELETE query for assignment
    $sql = "DELETE FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);

    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    if ($stmt->execute() && $stmt->rowCount() > 0) {

        // TODO: Check if delete was successful
        sendResponse(['success' => true, 'message' => 'Assignment deleted']);
    } else {

        // TODO: If delete failed or no rows affected (should be caught by the check above), return 500 error
        sendResponse(['success' => false, 'message' => 'Failed to delete assignment'], 500);
    }
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * * Query Parameters:
 * - assignment_id: The assignment ID (required)
 * * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and is a valid integer
    if (!$assignmentId || !filter_var($assignmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        sendResponse(['success' => false, 'message' => 'Valid Assignment ID is required'], 400);
        return;
    }

    // Ensure the ID is an integer
    $assignmentId = (int)$assignmentId;

    // **CORRECTION**: Use the correct table name 'comments_assignment'
    // TODO: Prepare SQL query to select all comments for the assignment
    $sql = "SELECT id, assignment_id, author, text, created_at FROM comments_assignment WHERE assignment_id = :assignment_id ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);

    // TODO: Bind the :assignment_id parameter
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch all results as associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert IDs to integers
    foreach ($comments as &$comment) {
        $comment['id'] = (int) $comment['id'];
        $comment['assignment_id'] = (int) $comment['assignment_id'];
    }

    // TODO: Return success response with comments data
    sendResponse(['success' => true, 'data' => $comments]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * * Required JSON Body:
 * - assignment_id: Assignment ID (required)
 * - author: Comment author name (required)
 * - text: Comment content (required)
 * * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if (!isset($data['assignment_id'], $data['author'], $data['text'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields: assignment_id, author, or text'], 400);
        return;
    }

    // TODO: Sanitize input data
    $assignmentId = $data['assignment_id']; // Will be validated as INT later
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    // TODO: Validate that assignment_id is a valid integer
    if (!filter_var($assignmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        sendResponse(['success' => false, 'message' => 'Invalid Assignment ID format'], 400);
        return;
    }

    $assignmentId = (int)$assignmentId;

    // TODO: Validate that text is not empty after trimming
    if (empty($text)) {
        sendResponse(['success' => false, 'message' => 'Comment text cannot be empty'], 400);
        return;
    }

    // TODO: Verify that the assignment exists
    $sqlCheck = "SELECT id FROM assignments WHERE id = :assignment_id";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmtCheck->execute();
    if (!$stmtCheck->fetch()) {
        sendResponse(['success' => false, 'message' => 'Assignment not found'], 404);
        return;
    }

    // **CORRECTION**: Use the correct table name 'comments_assignment'
    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments_assignment (assignment_id, author, text) VALUES (:assignment_id, :author, :text)";
    $stmt = $db->prepare($sql);

    // TODO: Bind all parameters
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);

    // TODO: Execute the statement
    if ($stmt->execute()) {

        // TODO: Get the ID of the inserted comment
        $commentId = $db->lastInsertId();

        // TODO: Return success response with created comment data
        sendResponse([
            'success' => true, 
            'message' => 'Comment created', 
            'data' => [
                'id' => (int)$commentId, 
                'assignment_id' => $assignmentId, 
                'author' => $author, 
                'text' => $text
            ]
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create comment'], 500);
    }
}

/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * * Query Parameters:
 * - id: Comment ID (required)
 * * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and is a valid integer
    if (!$commentId || !filter_var($commentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        sendResponse(['success' => false, 'message' => 'Valid Comment ID is required'], 400);
        return;
    }

    // Ensure the ID is an integer
    $commentId = (int)$commentId;

    // **CORRECTION**: Use the correct table name 'comments_assignment'
    // TODO: Check if comment exists
    $sqlCheck = "SELECT id FROM comments_assignment WHERE id = :id";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->bindValue(':id', $commentId, PDO::PARAM_INT);
    $stmtCheck->execute();
    if (!$stmtCheck->fetch()) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
        return;
    }

    // **CORRECTION**: Use the correct table name 'comments_assignment'
    // TODO: Prepare DELETE query
    $sql = "DELETE FROM comments_assignment WHERE id = :id";
    $stmt = $db->prepare($sql);

    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    if ($stmt->execute() && $stmt->rowCount() > 0) {

        // TODO: Check if delete was successful
        sendResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {

        // TODO: If delete failed, return 500 error
        sendResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    $resource = $queryParams['resource'] ?? null; // <-- Corrected: This line was missing!

    // Removed the errant sendResponse call here

    // TODO: Route based on HTTP method and resource type

    if ($method === 'GET') {
        // TODO: Handle GET requests

        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            $id = $queryParams['id'] ?? null;
            if ($id) {
                getAssignmentById($db, $id);
            } else {
                getAllAssignments($db);
            }

        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            $assignmentId = $queryParams['assignment_id'] ?? null;
            if ($assignmentId) {
                getCommentsByAssignment($db, $assignmentId);
            } else {
                sendResponse(['success' => false, 'message' => 'Assignment ID required for comments'], 400);
            }

        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['success' => false, 'message' => 'Invalid resource'], 400);
        }

    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)

        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            // Ensure student role check is done here if needed. Assuming only certain roles can create assignments.
            // if ($_SESSION['user']['role'] !== 'instructor') { sendResponse(['success' => false, 'message' => 'Unauthorized'], 403); return; }
            createAssignment($db, $requestBody);

        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db, $requestBody);

        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['success' => false, 'message' => 'Invalid resource'], 400);
        }

    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            // if ($_SESSION['user']['role'] !== 'instructor') { sendResponse(['success' => false, 'message' => 'Unauthorized'], 403); return; }
            updateAssignment($db, $requestBody);

        } else {
            // TODO: PUT not supported for other resources
            sendResponse(['success' => false, 'message' => 'PUT not supported for this resource'], 405);
        }

    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests

        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter (prefer query for DELETE)
            // if ($_SESSION['user']['role'] !== 'instructor') { sendResponse(['success' => false, 'message' => 'Unauthorized'], 403); return; }
            $id = $queryParams['id'] ?? null;
            deleteAssignment($db, $id);

        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            $id = $queryParams['id'] ?? null;
            deleteComment($db, $id);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['success' => false, 'message' => 'Invalid resource'], 400);
        }

    } else {
        // TODO: Method not supported
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }

 } catch (PDOException $e) {
    // TODO: Handle database errors
    error_log('Database error: ' . $e->getMessage());
    // In production, do not expose $e->getMessage() to the client
    sendResponse(['success' => false, 'message' => 'Database operation failed'], 500);

 } catch (Exception $e) {
    // TODO: Handle general errors
    error_log('General error: ' . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error'], 500);
 }


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['error' => 'Invalid response data'];
    }

    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to sanitize string input
 * * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // Convert to string in case non-string input is passed (e.g., numbers)
    if (!is_string($data)) {
        $data = (string) $data;
    }

    // TODO: Trim whitespace from beginning and end
    $data = trim($data);

    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);

    // TODO: Convert special characters to HTML entities
    // ENT_QUOTES covers both single and double quotes
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);

    // The strict check $d->format('Y-m-d') === $date ensures that '2020-02-30' is rejected
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    return in_array($value, $allowedValues);

}
?>