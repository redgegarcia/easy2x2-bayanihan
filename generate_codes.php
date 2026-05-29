<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

// Admin only - kung hindi admin, redirect
$current_user = $_SESSION['username'];
if ($current_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity = (int)$_POST['quantity'];
    $prefix = mysqli_real_escape_string($conn, $_POST['prefix']);
    
    if ($quantity < 1 || $quantity > 100) {
        $error = "Quantity must be between 1 and 100.";
    } else {
        $generated = 0;
        for ($i = 1; $i <= $quantity; $i++) {
            // Generate unique code
            $random = strtoupper(bin2hex(random_bytes(4)));
            $code = $prefix . '-' . $random;
            
            // Check if code already exists
            $check = mysqli_query($conn, "SELECT id FROM codes WHERE code = '$code'");
            if (mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "INSERT INTO codes (code, product_id, is_used) VALUES ('$code', 1, 0)");
                $generated++;
            }
        }
        $message = "Successfully generated $generated registration codes!";
    }
}

// Kunin ang listahan ng existing codes
$codes_result = mysqli_query($conn, "SELECT * FROM codes ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Codes - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        h2 { font-size: 1.1rem; font-weight: 700; }
        .back-link {
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            color: #2563eb;
            font-size: 0.7rem;
        }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.7rem; font-weight: 700; color: #334155; margin-bottom: 4px; text-transform: uppercase; }
        input, select {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.9rem;
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
        }
        .message { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 0.8rem; }
        .error { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 0.8rem; }
        .codes-list {
            margin-top: 24px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }
        .codes-list h3 { font-size: 0.8rem; margin-bottom: 12px; }
        .code-item {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 10px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            font-family: monospace;
            font-size: 0.7rem;
        }
        .code-used { color: #ef4444; text-decoration: line-through; opacity: 0.6; }
        .code-unused { color: #10b981; }
        .badge {
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .badge-used { background: #fee2e2; color: #b91c1c; }
        .badge-unused { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>🔑 Generate Registration Codes</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="message">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Code Prefix</label>
            <input type="text" name="prefix" placeholder="e.g., EASY2X2" value="EASY2X2" required>
        </div>
        <div class="form-group">
            <label>Quantity (1-100)</label>
            <input type="number" name="quantity" min="1" max="100" value="5" required>
        </div>
        <button type="submit">Generate Codes</button>
    </form>

    <div class="codes-list">
        <h3>📋 Recent Codes (last 50)</h3>
        <?php if (mysqli_num_rows($codes_result) > 0): ?>
            <?php while ($code = mysqli_fetch_assoc($codes_result)): ?>
                <div class="code-item">
                    <span class="<?php echo $code['is_used'] ? 'code-used' : 'code-unused'; ?>">
                        <?php echo htmlspecialchars($code['code']); ?>
                    </span>
                    <span class="badge <?php echo $code['is_used'] ? 'badge-used' : 'badge-unused'; ?>">
                        <?php echo $code['is_used'] ? 'USED by ' . htmlspecialchars($code['used_by']) : 'AVAILABLE'; ?>
                    </span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="font-size:0.7rem; color:#94a3b8;">No codes yet. Generate your first batch above.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
