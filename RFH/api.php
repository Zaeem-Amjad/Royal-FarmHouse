<?php
/* =====================================================
   CONFIG - Database Credentials
   ===================================================== */
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'royal_farming_house',
    'charset' => 'utf8mb4'
];

/* =====================================================
   Headers for CORS and JSON responses
   ===================================================== */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* =====================================================
   Database Connection
   ===================================================== */
function getDBConnection($config) {
    try {
        $conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $conn->set_charset($config['charset']);
        return $conn;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }
}

/* =====================================================
   Validate Email
   ===================================================== */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/* =====================================================
   Get Request Data
   ===================================================== */
function getRequestData() {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ? $data : [];
    }

    return $_POST;
}

/* =====================================================
   Send JSON Response
   ===================================================== */
function sendResponse($success, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit();
}

/* =====================================================
   Handle Booking
   ===================================================== */
function handleBooking($conn, $data) {
    // Validate required fields
    if (empty($data['visitor_name']) || empty($data['email']) || 
        empty($data['date']) || empty($data['time_slot']) || 
        empty($data['participants'])) {
        sendResponse(false, 'All fields are required', [], 400);
    }

    // Validate email
    if (!validateEmail($data['email'])) {
        sendResponse(false, 'Invalid email address', [], 400);
    }

    // Validate participants
    $participants = intval($data['participants']);
    if ($participants <= 0) {
        sendResponse(false, 'Number of participants must be greater than 0', [], 400);
    }

    $visitor_name = trim($data['visitor_name']);
    $email = trim($data['email']);
    $date = $data['date'];
    $time_slot = $data['time_slot'];

    // Check for conflicts (same date and time slot)
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE date = ? AND time_slot = ?");
    $stmt->bind_param("ss", $date, $time_slot);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        sendResponse(false, 'This time slot is already booked. Please choose another time.', [], 400);
    }
    $stmt->close();

    // Insert booking
    $stmt = $conn->prepare(
        "INSERT INTO bookings (visitor_name, email, date, time_slot, participants) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssi", $visitor_name, $email, $date, $time_slot, $participants);

    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        $stmt->close();
        sendResponse(true, 'Tour booked successfully!', ['booking_id' => $booking_id]);
    } else {
        $stmt->close();
        sendResponse(false, 'Failed to book tour', [], 500);
    }
}

/* =====================================================
   Handle Contact
   ===================================================== */
function handleContact($conn, $data) {
    // Validate required fields
    if (empty($data['name']) || empty($data['email']) || 
        empty($data['subject']) || empty($data['message'])) {
        sendResponse(false, 'All fields are required', [], 400);
    }

    // Validate email
    if (!validateEmail($data['email'])) {
        sendResponse(false, 'Invalid email address', [], 400);
    }

    $name = trim($data['name']);
    $email = trim($data['email']);
    $subject = trim($data['subject']);
    $message = trim($data['message']);

    // Insert contact message
    $stmt = $conn->prepare(
        "INSERT INTO contacts (name, email, subject, message) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    if ($stmt->execute()) {
        $contact_id = $conn->insert_id;
        $stmt->close();
        sendResponse(true, 'Message sent successfully! We will get back to you soon.', ['contact_id' => $contact_id]);
    } else {
        $stmt->close();
        sendResponse(false, 'Failed to send message', [], 500);
    }
}

/* =====================================================
   Main Request Handler
   ===================================================== */

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Only POST requests are allowed', [], 405);
}

// Get request data
$data = getRequestData();

// Get action
$action = isset($data['action']) ? $data['action'] : '';

if (empty($action)) {
    sendResponse(false, 'Action parameter is required', [], 400);
}

// Connect to database
$conn = getDBConnection($db_config);

// Route to appropriate handler
switch ($action) {
    case 'book':
        handleBooking($conn, $data);
        break;
    
    case 'contact':
        handleContact($conn, $data);
        break;
    
    default:
        sendResponse(false, 'Invalid action', [], 400);
}

// Close connection
$conn->close();
?>