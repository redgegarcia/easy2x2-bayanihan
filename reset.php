<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$current_user = $_SESSION['username'];
if ($current_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm == 'RESET') {
        // Delete all members except admin
        mysqli_query($conn, "DELETE FROM users WHERE username != 'admin'");
        
        // Delete all boards except admin's
        mysqli_query($conn, "DELETE FROM matrix_boards WHERE leader_username != 'admin'");
        
        // Clear admin's slots
        mysqli_query($conn, "UPDATE matrix_boards SET slot1 = NULL, slot2 = NULL WHERE leader_username = 'admin'");
        
        // Ensure admin has an active board
        $check = mysqli_query($conn, "SELECT id FROM matrix_boards WHERE leader_username = 'admin'");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('admin', 'ACTIVE')");
        }
        
        // Clear all tables
        mysqli_query($conn, "TRUNCATE TABLE cycles");
        mysqli_query($conn, "TRUNCATE TABLE codes");
        mysqli_query($conn, "TRUNCATE TABLE otps");
        
        $message = "System reset successful! Only admin remains.";
    } else {
        $error = "Confirmation code incorrect. Type 'RESET' to confirm.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Reset System - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 32px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .warning-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }
        h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #b91c1c;
        }
        .warning-text {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .warning-text p {
            font-size: 0.8rem;
            color: #7f1d1d;
            margin-bottom: 8px;
        }
        .warning-text ul {
            margin-left: 20px;
            font-size: 0.7rem;
            color: #991b1b;
        }
        .warning-text li {
            margin: 4px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        input {
            width: 100%;
            padding: 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.9rem;
            text-align: center;
            letter-spacing: 2px;
            font-family: monospace;
        }
        input:focus {
            outline: none;
            border-color: #ef4444;
        }
        button {
            width: 100%;
            background: #ef4444;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            margin-top: 8px;
        }
        button:active { transform: scale(0.98); }
        .message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            text-align: center;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #64748b;
            font-size: 0.7rem;
            text-decoration: none;
        }
        hr {
            margin: 16px 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="warning-icon">⚠️</div>
        <h2>Reset System</h2>
    </div>

    <?php if ($message): ?>
        <div class="message">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="warning-text">
        <p><strong>⚠️ WARNING: This action cannot be undone!</strong></p>
        <ul>
            <li>❌ All members (except admin) will be deleted</li>
            <li>❌ All payout history will be cleared</li>
            <li>❌ All registration codes will be deleted</li>
            <li>❌ All OTP records will be cleared</li>
            <li>✅ Only <strong>admin</strong> account will remain</li>
        </ul>
    </div>

    <?php if (!$message): ?>
        <form method="post">
            <div class="form-group">
                <label>Type <strong>"RESET"</strong> to confirm</label>
                <input type="text" name="confirm" placeholder="RESET" required autocomplete="off">
            </div>
            <button type="submit" onclick="return confirm('⚠️ FINAL WARNING: This will DELETE ALL DATA. Are you absolutely sure?')">
                🗑️ Reset Everything
            </button>
        </form>
        <hr>
        <a href="dashboard.php" class="back-link">← Cancel and Back to Dashboard</a>
    <?php else: ?>
        <a href="dashboard.php" class="back-link" style="background: #2563eb; color: white; padding: 12px; border-radius: 14px; margin-top: 8px;">← Go to Dashboard</a>
    <?php endif; ?>
</div>
</body>
</html>