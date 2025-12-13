<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'role' => 'admin',
        'logged_in' => true
    ];
}
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once 'Database.php';

// TODO: Get the PDO database connection
$db = Database::getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$requestBody = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    global $queryParams;
    $search = $queryParams['search'] ?? null;
    $sort = $queryParams['sort'] ?? null;
    $order = $queryParams['order'] ?? 'asc';

    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    $whereClause = '';
    $params = [];
    if ($search) {
        $whereClause = 'WHERE name LIKE :search OR email LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    $orderClause = '';
    $allowedSorts = ['name', 'email', 'id', 'created_at'];
    $allowedOrders = ['asc', 'desc'];
    if ($sort && in_array($sort, $allowedSorts) && in_array($order, $allowedOrders)) {
        $orderClause = "ORDER BY $sort $order";
    }

    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    $sql = "SELECT id, name, email, created_at FROM users $whereClause $orderClause";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if using search
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $responseStudents = array_map(function($student) {
        $student['student_id'] = $student['email'];
        unset($student['email']);
        return $student;
    }, $students);
    
    // TODO: Return JSON response with success status and data
    sendResponse(['success' => true, 'data' => $responseStudents]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $sql = "SELECT id, name, email, created_at FROM users WHERE email = :unique_id";
    $stmt = $db->prepare($sql);

    // TODO: Bind the student_id parameter
    $stmt->bindValue(':unique_id', $uniqueId);

    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    if ($student) {
        $student['student_id'] = $student['email'];
        unset($student['email']);
        sendResponse(['success' => true, 'data' => $student]);
    } else {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data['student_id'], $data['name'], $data['password'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields (student_id, name, password)'], 400);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $studentId = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $password = $data['password']; // Don't sanitize password, but validate 
    $email = $studentId;

    if (!validateEmail($email)) {
        sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
        return;
    }
    
    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $sql = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':student_id', $studentId);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student ID or email already exists'], 409);
        return;
    }
    
    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password', $hashedPassword);
    
    // TODO: Execute the query
    if ($stmt->execute()) {
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
    sendResponse(['success' => true, 'message' => 'Student created successfully'], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create student'], 500);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!isset($data['student_id'])) {
        sendResponse(['success' => false, 'message' => 'Student ID is required'], 400);
        return;
    }

    $studentId = sanitizeInput($data['student_id']);
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $sql = "SELECT id, email FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':email', $oldEmail);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $updates = [];
    $params = [':id' => $student['id']];
    if (isset($data['name'])) {
        $updates[] = 'name = :name';
        $params[':name'] = sanitizeInput($data['name']);
    }
    if (isset($data['email'])) {
        $email = sanitizeInput($data['email']);
        if (!validateEmail($email)) {
            sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
            return;
        }
    
    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
    $sqlCheck = "SELECT id FROM students WHERE email = :email AND id != :id";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindValue(':email', $email);
        $stmtCheck->bindValue(':id', $student['id']);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            sendResponse(['success' => false, 'message' => 'Email already exists'], 409);
            return;
        }
        $updates[] = 'email = :email';
        $params[':email'] = $email;
    }

    if (empty($updates)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
        return;
    }
    
    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the query
    if ($stmt->execute()) {

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    sendResponse(['success' => true, 'message' => 'Student updated successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update student'], 500);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!$studentId) {
        sendResponse(['success' => false, 'message' => 'Student ID is required'], 400);
        return;
    }

    $studentId = sanitizeInput($studentId);
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $sql = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':email', $uniqueId);
    $stmt->execute();
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        return;
    }
    
    // TODO: Prepare DELETE query
    $sql = "DELETE FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the student_id parameter
    $stmt->bindValue(':email', $uniqueId);  
      
    // TODO: Execute the query
    if ($stmt->execute()) {

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    sendResponse(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete student'], 500);
    }
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data['student_id'], $data['current_password'], $data['new_password'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields (student_id, current_password, new_password)'], 400);
        return;
    }

    $uniqueId = sanitizeInput($data['student_id']);
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];
    
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($newPassword) < 8) {
        sendResponse(['success' => false, 'message' => 'New password must be at least 8 characters'], 400);
        return;
    }
    
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $sql = "SELECT password FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':email', $uniqueId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        return;
    }
    
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!password_verify($currentPassword, $student['password'])) {
        sendResponse(['success' => false, 'message' => 'Current password is incorrect'], 401);
        return;
    }
    
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // TODO: Update password in database
    // Prepare UPDATE query
    $sql = "UPDATE users SET password = :password WHERE email = :email";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters and execute
    $stmt->bindValue(':password', $hashedNewPassword);
    $stmt->bindValue(':email', $uniqueId);
    
    if ($stmt->execute()) {

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    sendResponse(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to change password'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        students (with optional search/sort)
        $studentId = $queryParams['student_id'] ?? null;
        if ($studentId) {
            getStudentById($db, $studentId);
        } else {
            getStudents($db);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        $action = $queryParams['action'] ?? null;
        if ($action === 'change_password') {
            changePassword($db, $requestBody);
        } else {
            createStudent($db, $requestBody);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $requestBody);
        
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = $queryParams['student_id'] ?? ($requestBody['student_id'] ?? null);
        deleteStudent($db, $studentId);
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log('Database error: ' . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    error_log('General error: ' . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error'], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
    return htmlspecialchars(strip_tags(trim($data)));
}

?>
