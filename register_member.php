<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$sponsor = $_SESSION['username'];
$error = '';
$success = '';

// Helper: cycle board if complete
function cycleIfComplete($conn, $board_id, $leader_username) {
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE id = $board_id"));
    $filled = 0;
    foreach(['slot1','slot2','slot3','slot4','slot5','slot6'] as $s) if(!empty($board[$s])) $filled++;
    if($filled == 6) {
        mysqli_query($conn, "UPDATE matrix_boards SET status = 'COMPLETED' WHERE id = $board_id");
        mysqli_query($conn, "INSERT INTO cycles (username, reward_amount) VALUES ('$leader_username', 5500)");
        mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('$leader_username', 'ACTIVE')");
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_user = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $reg_code = mysqli_real_escape_string($conn, strtoupper(trim($_POST['code'])));
    
    // 1. I-validate muna ang code
    $code_check = mysqli_query($conn, "SELECT * FROM codes WHERE code = '$reg_code' AND is_used = 0");
    $code_row = mysqli_fetch_assoc($code_check);
    
    if (!$code_row) {
        $error = "Invalid or already used registration code!";
    } else {
        // 2. Check kung existing na ang username
        $check_user = mysqli_query($conn, "SELECT id FROM users WHERE username='$new_user'");
        if (mysqli_num_rows($check_user) > 0) {
            $error = "Username already exists!";
        } else {
            // 3. Kunin ang product price (optional, pwedeng gamitin sa future reports)
            $product_id = $code_row['product_id'];
            $product_query = mysqli_query($conn, "SELECT price FROM products WHERE id = $product_id");
            $product = mysqli_fetch_assoc($product_query);
            
            // 4. I-mark ang code bilang nagamit na
            mysqli_query($conn, "UPDATE codes SET is_used = 1, used_by = '$new_user', used_at = NOW() WHERE code = '$reg_code'");
            
            // 5. I-create ang bagong user
            mysqli_query($conn, "INSERT INTO users (username, password, sponsor) VALUES ('$new_user', '$password', '$sponsor')");
            mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('$new_user', 'ACTIVE')");
            
            // 6. Ilagay sa matrix ng sponsor (may spillover logic)
            // (Dito ilalagay ang existing placement code mo - gamitin mo ang iyong kasalukuyang logic)
            // Para maging maayos, isama mo rito ang buong placement logic mula sa iyong kasalukuyang register_member.php
            
            $success = "Member $new_user registered successfully with code: $reg_code!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Member</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ===== BEAUTIFUL STYLES (same as before) ===== */
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --gray-200: #e2e8f0;
            --gray-700: #334155;
            --white: #ffffff;
        }
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
        .card {
            max-width: 400px;
            width: 100%;
            background: var(--white);
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 20px;
            color: white;
        }
        .card-header h2 { font-size: 1.3rem; font-weight: 700; }
        .card-body { padding: 24px; }
        .sponsor-badge {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 16px;
            padding: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
            color: #92400e;
        }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.2s;
            font-family: monospace;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.8rem;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.8rem;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            font-size: 0.75rem;
            text-decoration: none;
        }
        hr { margin: 16px 0; border-color: var(--gray-200); }
        .code-hint {
            background: #f1f5f9;
            padding: 8px;
            border-radius: 8px;
            font-size: 0.7rem;
            text-align: center;
            margin-top: 4px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h2>➕ Register New Member</h2>
        </div>
        <div class="card-body">
            <div class="sponsor-badge">
                👑 Sponsor: <strong><?php echo htmlspecialchars($sponsor); ?></strong>
            </div>

            <?php if ($error): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
                <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
            <?php else: ?>
                <form method="post">
                    <div class="form-group">
                        <label>Registration Code</label>
                        <input type="text" name="code" placeholder="Enter your purchase code (e.g., EASY2X2-ABC123)" required autofocus>
                        <div class="code-hint">💡 Need a code? Contact admin to purchase membership.</div>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Choose username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Choose password" required>
                    </div>
                    <button type="submit">Register Member</button>
                </form>
                <hr>
                <a href="dashboard.php" class="back-link">← Cancel</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>