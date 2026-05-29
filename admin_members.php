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

// DELETE MEMBER
if (isset($_GET['delete'])) {
    $username = mysqli_real_escape_string($conn, $_GET['delete']);
    
    if ($username != 'admin') {
        mysqli_query($conn, "DELETE FROM users WHERE username = '$username'");
        mysqli_query($conn, "DELETE FROM matrix_boards WHERE leader_username = '$username'");
        mysqli_query($conn, "UPDATE matrix_boards SET slot1 = NULL WHERE slot1 = '$username'");
        mysqli_query($conn, "UPDATE matrix_boards SET slot2 = NULL WHERE slot2 = '$username'");
        mysqli_query($conn, "DELETE FROM cycles WHERE username = '$username'");
        $message = "Member '$username' deleted!";
    } else {
        $error = "Cannot delete admin!";
    }
}

// GET ALL MEMBERS
$members = mysqli_query($conn, "SELECT * FROM users ORDER BY reg_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Members</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            border-radius: 28px;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
        }
        h2 { font-size: 1rem; }
        .back-link {
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            color: #2563eb;
            font-size: 0.7rem;
        }
        .message {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.75rem;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.75rem;
        }
        .member-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .member-name {
            font-weight: 700;
            font-size: 0.9rem;
        }
        .member-email {
            font-size: 0.7rem;
            color: #64748b;
            margin: 4px 0;
        }
        .member-sponsor {
            font-size: 0.65rem;
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
        }
        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.65rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
        }
        hr { margin: 16px 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>👥 Manage Members</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>
    
    <?php if ($message): ?>
        <div class="message">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php while ($row = mysqli_fetch_assoc($members)): ?>
        <div class="member-card">
            <div class="member-name">
                <?php echo htmlspecialchars($row['username']); ?>
                <?php if ($row['username'] == 'admin'): ?>
                    <span style="background:#fef3c7; padding:2px 6px; border-radius:12px; font-size:0.6rem;">Admin</span>
                <?php endif; ?>
            </div>
            <div class="member-email">📧 <?php echo htmlspecialchars($row['email']); ?></div>
            <div class="member-sponsor">Sponsor: <?php echo htmlspecialchars($row['sponsor'] ?: 'None'); ?></div>
            <?php if ($row['username'] != 'admin'): ?>
                <a href="?delete=<?php echo urlencode($row['username']); ?>" class="delete-btn" onclick="return confirm('Delete <?php echo addslashes($row['username']); ?>?')">🗑️ Delete</a>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
	
<!-- Bottom Navigation Buttons -->
<div class="bottom-nav" style="display: flex; gap: 8px; margin-top: 20px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
    <a href="admin_members.php" class="nav-btn" style="flex:1; text-align:center; background:#2563eb; color:white; padding:8px; border-radius:12px; text-decoration:none; font-size:0.7rem; font-weight:600;">👥 Members</a>
    <a href="admin_payouts.php" class="nav-btn" style="flex:1; text-align:center; background:#f8fafc; color:#64748b; padding:8px; border-radius:12px; text-decoration:none; font-size:0.7rem; font-weight:600;">💰 Payouts</a>
    <a href="generate_codes.php" class="nav-btn" style="flex:1; text-align:center; background:#f8fafc; color:#64748b; padding:8px; border-radius:12px; text-decoration:none; font-size:0.7rem; font-weight:600;">🔑 Codes</a>
</div>
	
</div>
</body>
</html>