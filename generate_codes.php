<?php
session_start();
require_once 'db_config.php';

date_default_timezone_set('Asia/Manila');

$current_user = $_SESSION['username'];
if ($current_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';
$reset_error = '';

// RESET ALL CODES (with PIN verification)
if (isset($_POST['reset_codes'])) {
    $pin = $_POST['pin'];
    $secret_pin = '1234';
    
    if ($pin == $secret_pin) {
        mysqli_query($conn, "TRUNCATE TABLE codes");
        $message = "All codes have been deleted!";
    } else {
        $reset_error = "Invalid PIN! Codes were NOT deleted.";
    }
}

// GENERATE NEW CODES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $quantity = (int)$_POST['quantity'];
    $prefix = mysqli_real_escape_string($conn, $_POST['prefix']);
    
    if ($quantity < 1 || $quantity > 100) {
        $error = "Quantity must be between 1 and 100.";
    } else {
        $generated = 0;
        for ($i = 1; $i <= $quantity; $i++) {
            $random = strtoupper(bin2hex(random_bytes(4)));
            $code = $prefix . '-' . $random;
            
            $check = mysqli_query($conn, "SELECT id FROM codes WHERE code = '$code'");
            if (mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "INSERT INTO codes (code, product_id, is_used) VALUES ('$code', 1, 0)");
                $generated++;
            }
        }
        $message = "Successfully generated $generated registration codes!";
    }
}

// SEARCH USER
$search_result = '';
$search_username = '';
if (isset($_GET['search'])) {
    $search_username = mysqli_real_escape_string($conn, $_GET['username']);
    $search_result = mysqli_query($conn, "SELECT c.*, u.email FROM codes c LEFT JOIN users u ON c.used_by = u.username WHERE c.used_by = '$search_username' ORDER BY c.used_at DESC");
}

// Get statistics
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM codes");
$total_row = mysqli_fetch_assoc($total_query);
$total_codes = $total_row['total'];

$used_query = mysqli_query($conn, "SELECT COUNT(*) as used FROM codes WHERE is_used = 1");
$used_row = mysqli_fetch_assoc($used_query);
$used_codes = $used_row['used'];

$available_codes = $total_codes - $used_codes;

// Get used codes
$used_list = mysqli_query($conn, "SELECT c.*, u.email FROM codes c LEFT JOIN users u ON c.used_by = u.username WHERE c.is_used = 1 ORDER BY c.used_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Codes - Admin</title>
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
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        h2 { font-size: 1rem; font-weight: 700; }
        .back-link {
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            color: #2563eb;
            font-size: 0.7rem;
        }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.7rem; font-weight: 600; color: #334155; margin-bottom: 4px; }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.85rem;
        }
        button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
        }
        .message { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 12px; margin-bottom: 16px; font-size: 0.75rem; text-align: center; }
        .error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 12px; margin-bottom: 16px; font-size: 0.75rem; text-align: center; }
        
        /* Stats */
        .stats {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background: #f8fafc;
            border-radius: 14px;
            padding: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 1.3rem;
            font-weight: 800;
        }
        .stat-label {
            font-size: 0.55rem;
            color: #64748b;
            text-transform: uppercase;
        }
        .stat-card.total .stat-number { color: #1e40af; }
        .stat-card.used .stat-number { color: #b91c1c; }
        .stat-card.available .stat-number { color: #065f46; }

        /* Search */
        .search-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .search-form {
            display: flex;
            gap: 8px;
        }
        .search-form input {
            flex: 1;
            padding: 10px;
        }
        .search-form button {
            width: auto;
            padding: 0 16px;
        }
        .search-result {
            margin-top: 12px;
            padding: 10px;
            background: white;
            border-radius: 12px;
            font-size: 0.7rem;
        }

        /* Codes */
        .codes-section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .code-row {
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .code-text {
            font-family: monospace;
            font-size: 0.7rem;
            font-weight: 500;
            color: #1e293b;
        }
        .copy-btn {
            background: #e2e8f0;
            border: none;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.6rem;
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
        }
        .used-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .used-code {
            font-family: monospace;
            font-size: 0.7rem;
            color: #b91c1c;
            text-decoration: line-through;
        }
        .used-date {
            font-size: 0.55rem;
            color: #64748b;
        }
        .used-user {
            text-align: right;
        }
        .used-name {
            font-size: 0.7rem;
            font-weight: 600;
            color: #1e293b;
        }
        .used-email {
            font-size: 0.55rem;
            color: #64748b;
        }
        .empty-message {
            text-align: center;
            padding: 16px;
            color: #94a3b8;
            font-size: 0.7rem;
        }
        hr {
            margin: 16px 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }

        /* Reset */
        .reset-section {
            background: #fef2f2;
            border-radius: 16px;
            padding: 15px;
            margin-top: 20px;
        }
        .reset-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #b91c1c;
            margin-bottom: 10px;
        }
        .reset-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .pin-input {
            width: 80px;
            text-align: center;
            letter-spacing: 3px;
        }
        .reset-btn {
            background: #ef4444;
            width: auto;
            padding: 8px 16px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>🔑 Registration Codes</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="message">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $total_codes; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card used">
            <div class="stat-number"><?php echo $used_codes; ?></div>
            <div class="stat-label">Used</div>
        </div>
        <div class="stat-card available">
            <div class="stat-number"><?php echo $available_codes; ?></div>
            <div class="stat-label">Available</div>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="search-section">
        <form method="get" class="search-form">
            <input type="text" name="username" placeholder="Search user..." value="<?php echo htmlspecialchars($search_username); ?>">
            <button type="submit" name="search">🔍</button>
        </form>
        <?php if (isset($_GET['search']) && $search_result && mysqli_num_rows($search_result) > 0): ?>
            <div class="search-result">
                <?php while ($code = mysqli_fetch_assoc($search_result)): ?>
                    <div style="padding: 4px 0;">📝 <?php echo htmlspecialchars($code['code']); ?> <span style="color:#64748b;">(<?php echo date('M d, Y', strtotime($code['used_at'])); ?>)</span></div>
                <?php endwhile; ?>
            </div>
        <?php elseif (isset($_GET['search'])): ?>
            <div class="search-result">No codes found for this user.</div>
        <?php endif; ?>
    </div>

    <!-- AVAILABLE CODES -->
    <div class="codes-section">
        <div class="section-title">📋 Available Codes</div>
        <?php
        $available_list = mysqli_query($conn, "SELECT * FROM codes WHERE is_used = 0 ORDER BY created_at DESC LIMIT 10");
        if (mysqli_num_rows($available_list) > 0): ?>
            <?php while ($code = mysqli_fetch_assoc($available_list)): ?>
                <div class="code-row">
                    <span class="code-text"><?php echo htmlspecialchars($code['code']); ?></span>
                    <button class="copy-btn" data-code="<?php echo htmlspecialchars($code['code']); ?>">Copy</button>
                </div>
            <?php endwhile; ?>
            <?php if ($available_codes > 10): ?>
                <div class="empty-message" style="padding: 8px;">+ <?php echo ($available_codes - 10); ?> more</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-message">No available codes. Generate below.</div>
        <?php endif; ?>
    </div>

    <!-- USED CODES -->
    <?php if (mysqli_num_rows($used_list) > 0): ?>
    <div class="codes-section">
        <div class="section-title">📋 Recently Used</div>
        <?php while ($code = mysqli_fetch_assoc($used_list)): ?>
            <div class="used-item">
                <div>
                    <div class="used-code"><?php echo htmlspecialchars($code['code']); ?></div>
                    <div class="used-date"><?php echo date('M d, Y h:i A', strtotime($code['used_at'])); ?></div>
                </div>
                <div class="used-user">
                    <div class="used-name"><?php echo htmlspecialchars($code['used_by']); ?></div>
                    <div class="used-email"><?php echo htmlspecialchars($code['email'] ?? ''); ?></div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- GENERATE -->
    <hr>
    <form method="post">
        <div class="form-group">
            <label>Generate New Codes</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" name="prefix" placeholder="Prefix" value="EASY2X2" style="width: 40%;">
                <input type="number" name="quantity" min="1" max="100" value="5" style="width: 30%;">
                <button type="submit" name="generate" style="width: 30%;">Generate</button>
            </div>
        </div>
    </form>

    <!-- RESET -->
    <div class="reset-section">
        <div class="reset-title">⚠️ Danger Zone</div>
        <?php if ($reset_error): ?>
            <div class="error" style="margin-bottom: 10px;"><?php echo $reset_error; ?></div>
        <?php endif; ?>
        <form method="post" class="reset-form">
            <input type="password" name="pin" maxlength="4" class="pin-input" placeholder="PIN" required>
            <button type="submit" name="reset_codes" class="reset-btn" onclick="return confirm('Delete ALL codes? This cannot be undone!')">Delete All</button>
        </form>
    </div>
	
<!-- Bottom Navigation Buttons -->
<div class="bottom-nav" style="display: flex; gap: 8px; margin-top: 20px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
    <a href="admin_members.php" class="nav-btn" style="flex:1; text-align:center; background:#f8fafc; color:#64748b; padding:8px; border-radius:12px; text-decoration:none; font-size:0.7rem; font-weight:600;">👥 Members</a>
    <a href="admin_payouts.php" class="nav-btn" style="flex:1; text-align:center; background:#f8fafc; color:#64748b; padding:8px; border-radius:12px; text-decoration:none; font-size:0.7rem; font-weight:600;">💰 Payouts</a>
    <a href="generate_codes.php" class="nav-btn" style="flex:1; text-align:center; background:#2563eb; color:white; padding:8px; border-radius:12px; text-decoration:none; font-size:0.7rem; font-weight:600;">🔑 Codes</a>
</div>

</div>

<script>
// Simple copy function - no alert, no toast, just copy
document.querySelectorAll('.copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var code = this.getAttribute('data-code');
        navigator.clipboard.writeText(code);
        // Visual feedback only - change text then revert
        var originalText = this.innerText;
        this.innerText = '✓';
        setTimeout(function() {
            btn.innerText = originalText;
        }, 800);
    });
});
</script>
</body>
</html>