<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once __DIR__ . '/../config/Database.php';

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TODO: Get the request body for POST and PUT requests
$rawInput = file_get_contents('php://input');
$requestData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $requestData = $decoded;
    }
}

// TODO: Parse query parameters
$action      = isset($_GET['action']) ? $_GET['action'] : null;
$id          = isset($_GET['id']) ? $_GET['id'] : null;
$resource_id = isset($_GET['resource_id']) ? $_GET['resource_id'] : null;
$comment_id  = isset($_GET['comment_id']) ? $_GET['comment_id'] : null;


// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Function: Get all resources
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of resource objects
 */
function getAllResources($db) {
    // TODO: Initialize the base SQL query
    $sql = "SELECT id, title, description, link, created_at FROM resources";

    // Gather optional query params
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort   = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'created_at';
    $order  = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'desc';

    // TODO: Check if search parameter exists
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
    }

    // TODO: Check if sort parameter exists and validate it
    $allowedSort = ['title', 'created_at'];
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'created_at';
    }

    // TODO: Check if order parameter exists and validate it
    $order = ($order === 'asc') ? 'ASC' : 'DESC';

    // TODO: Add ORDER BY clause to query
    $sql .= " ORDER BY {$sort} {$order}";

    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($sql);

    // TODO: If search parameter was used, bind the search parameter
    if (!empty($search)) {
        $like = '%' . $search . '%';
        $stmt->bindValue(':search', $like, PDO::PARAM_STR);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    sendResponse([
        'success' => true,
        'data'    => $resources
    ], 200);
}


/**
 * Function: Get a single resource by ID
 * Method: GET
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - data: Resource object or error message
 */
function getResourceById($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource ID.'
        ], 400);
    }

    // TODO: Prepare SQL query to select resource by id
    $sql = "SELECT id, title, description, link, created_at FROM resources WHERE id = ?";
    $stmt = $db->prepare($sql);

    // TODO: Bind the resource_id parameter
    $stmt->bindValue(1, (int)$resourceId, PDO::PARAM_INT);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch the result as an associative array
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if resource exists
    if ($resource) {
        sendResponse([
            'success' => true,
            'data'    => $resource
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }
}


/**
 * Function: Create a new resource
 * Method: POST
 * 
 * Required JSON Body:
 *   - title: Resource title (required)
 *   - description: Resource description (optional)
 *   - link: URL to the resource (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created resource (on success)
 */
function createResource($db, $data) {
    // TODO: Validate required fields
    $validation = validateRequiredFields($data, ['title', 'link']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $validation['missing']
        ], 400);
    }

    // TODO: Sanitize input data
    $title       = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link        = sanitizeInput($data['link']);

    // Validate URL
    if (!validateUrl($link)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid URL format for link.'
        ], 400);
    }

    // TODO: Set default value for description if not provided
    if ($description === null) {
        $description = '';
    }

    // TODO: Prepare INSERT query
    $sql = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters
    $stmt->bindValue(1, $title, PDO::PARAM_STR);
    $stmt->bindValue(2, $description, PDO::PARAM_STR);
    $stmt->bindValue(3, $link, PDO::PARAM_STR);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if insert was successful
    if ($success) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully.',
            'id'      => (int)$newId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create resource.'
        ], 500);
    }
}


/**
 * Function: Update an existing resource
 * Method: PUT
 * 
 * Required JSON Body:
 *   - id: The resource's database ID (required)
 *   - title: Updated resource title (optional)
 *   - description: Updated description (optional)
 *   - link: Updated URL (optional)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function updateResource($db, $data) {
    // TODO: Validate that resource ID is provided
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource ID.'
        ], 400);
    }

    $resourceId = (int)$data['id'];

    // TODO: Check if resource exists
    $checkSql = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }
    if (isset($data['link'])) {
        // TODO: If link is being updated, validate URL format
        $link = sanitizeInput($data['link']);
        if (!validateUrl($link)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid URL format for link.'
            ], 400);
        }
        $fields[] = "link = ?";
        $values[] = $link;
    }

    // TODO: If no fields to update, return error response with 400 status
    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided to update.'
        ], 400);
    }

    // TODO: Build the complete UPDATE SQL query
    $sql = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?";

    // TODO: Prepare the query
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters dynamically
    foreach ($values as $index => $val) {
        $stmt->bindValue($index + 1, $val, PDO::PARAM_STR);
    }
    // Bind ID at the end
    $stmt->bindValue(count($values) + 1, $resourceId, PDO::PARAM_INT);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if update was successful
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Resource updated successfully.'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update resource.'
        ], 500);
    }
}


/**
 * Function: Delete a resource
 * Method: DELETE
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 * 
 * Note: This should also delete all associated comments
 */
function deleteResource($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource ID.'
        ], 400);
    }

    $resourceId = (int)$resourceId;

    // TODO: Check if resource exists
    $checkSql = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    // TODO: Begin a transaction (for data integrity)
    $db->beginTransaction();

    try {
        // TODO: First, delete all associated comments
        $delCommentsSql = "DELETE FROM comments WHERE resource_id = ?";
        $delCommentsStmt = $db->prepare($delCommentsSql);
        $delCommentsStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
        $delCommentsStmt->execute();

        // TODO: Then, delete the resource
        $delResourceSql = "DELETE FROM resources WHERE id = ?";
        $delResourceStmt = $db->prepare($delResourceSql);
        $delResourceStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
        $delResourceStmt->execute();

        // TODO: Commit the transaction
        $db->commit();

        // TODO: Return success response with 200 status
        sendResponse([
            'success' => true,
            'message' => 'Resource and associated comments deleted successfully.'
        ], 200);

    } catch (Exception $e) {
        // TODO: Rollback the transaction on error
        $db->rollBack();

        // TODO: Return error response with 500 status
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete resource.'
        ], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific resource
 * Method: GET with action=comments
 * 
 * Query Parameters:
 *   - resource_id: The resource's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of comment objects
 */
function getCommentsByResourceId($db, $resourceId) {
    // TODO: Validate that resource_id is provided and is numeric
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource ID.'
        ], 400);
    }

    $resourceId = (int)$resourceId;

    // TODO: Prepare SQL query to select comments for the resource
    $sql = "SELECT id, resource_id, author, text, created_at 
            FROM comments 
            WHERE resource_id = ? 
            ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);

    // TODO: Bind the resource_id parameter
    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    sendResponse([
        'success' => true,
        'data'    => $comments
    ], 200);
}


/**
 * Function: Create a new comment
 * Method: POST with action=comment
 * 
 * Required JSON Body:
 *   - resource_id: The resource's database ID (required)
 *   - author: Name of the comment author (required)
 *   - text: Comment text content (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created comment (on success)
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    $validation = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $validation['missing']
        ], 400);
    }

    // TODO: Validate that resource_id is numeric
    if (!is_numeric($data['resource_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource ID.'
        ], 400);
    }

    $resourceId = (int)$data['resource_id'];

    // TODO: Check if the resource exists
    $checkSql = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    // TODO: Sanitize input data
    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);

    // TODO: Prepare INSERT query
    $sql = "INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters
    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $stmt->bindValue(2, $author, PDO::PARAM_STR);
    $stmt->bindValue(3, $text, PDO::PARAM_STR);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if insert was successful
    if ($success) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully.',
            'id'      => (int)$newId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create comment.'
        ], 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE with action=delete_comment
 * 
 * Query Parameters or JSON Body:
 *   - comment_id: The comment's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that comment_id is provided and is numeric
    if (empty($commentId) || !is_numeric($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing comment ID.'
        ], 400);
    }

    $commentId = (int)$commentId;

    // TODO: Check if comment exists
    $checkSql = "SELECT id FROM comments WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(1, $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found.'
        ], 404);
    }

    // TODO: Prepare DELETE query
    $sql = "DELETE FROM comments WHERE id = ?";
    $stmt = $db->prepare($sql);

    // TODO: Bind the comment_id parameter
    $stmt->bindValue(1, $commentId, PDO::PARAM_INT);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if delete was successful
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully.'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment.'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method and action parameter
    
    if ($method === 'GET') {
        // TODO: Check the action parameter to determine which function to call

        if ($action === 'comments') {
            // If action is 'comments', get comments for a resource
            getCommentsByResourceId($db, $resource_id);
        } elseif (!empty($id)) {
            // If id parameter exists, get single resource
            getResourceById($db, $id);
        } else {
            // Otherwise, get all resources
            getAllResources($db);
        }

    } elseif ($method === 'POST') {
        // TODO: Check the action parameter to determine which function to call
        if ($action === 'comment') {
            // If action is 'comment', create a new comment
            createComment($db, $requestData);
        } else {
            // Otherwise, create a new resource
            createResource($db, $requestData);
        }

    } elseif ($method === 'PUT') {
        // TODO: Update a resource
        updateResource($db, $requestData);

    } elseif ($method === 'DELETE') {
        // TODO: Check the action parameter to determine which function to call
        if ($action === 'delete_comment') {
            // If action is 'delete_comment', delete a comment
            $cid = $comment_id ?? ($requestData['comment_id'] ?? null);
            deleteComment($db, $cid);
        } else {
            // Otherwise, delete a resource
            $rid = $id ?? ($requestData['id'] ?? null);
            deleteResource($db, $rid);
        }

    } else {
        // TODO: Return error for unsupported methods
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed.'
        ], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    error_log('PDOException: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred.'
    ], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    error_log('Exception: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An unexpected error occurred.'
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param array $data - Data to send (should include 'success' key)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code using http_response_code()
    http_response_code($statusCode);

    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    // TODO: Echo JSON encoded data
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate URL format
 * 
 * @param string $url - URL to validate
 * @return bool - True if valid, false otherwise
 */
function validateUrl($url) {
    // TODO: Use filter_var with FILTER_VALIDATE_URL
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace using trim()
    $data = trim($data);
    
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate required fields
 * 
 * @param array $data - Data array to validate
 * @param array $requiredFields - Array of required field names
 * @return array - Array with 'valid' (bool) and 'missing' (array of missing fields)
 */
function validateRequiredFields($data, $requiredFields) {
    // TODO: Initialize empty array for missing fields
    $missing = [];

    // TODO: Loop through required fields
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $missing[] = $field;
        }
    }

    // TODO: Return result array
    return [
        'valid'   => count($missing) === 0,
        'missing' => $missing
    ];
}

?>
