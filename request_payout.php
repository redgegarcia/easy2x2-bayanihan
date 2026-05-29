<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$current_user = $_SESSION['username'];
$message = '';
$error = '';

// Check if user has pending payouts
$pending_query = "SELECT SUM(reward_amount) as total FROM cycles WHERE username = '$current_user' AND (status = 'PENDING' OR status IS NULL)";
$pending_result = mysqli_query($conn, $pending_query);
$pending_row = mysqli_fetch_assoc($pending_result);
$total_pending = $pending_row['total'] ?? 0;

// Check if user already has a pending request
$request_check = mysqli_query($conn, "SELECT id FROM payout_requests WHERE username = '$current_user' AND status = 'PENDING'");
$has_pending_request = mysqli_num_rows($request_check) > 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($total_pending < 100) {
        $error = "Minimum payout request is ₱100. You have ₱" . number_format($total_pending);
    } elseif ($has_pending_request) {
        $error = "You already have a pending payout request. Please wait for it to be processed.";
    } else {
        mysqli_query($conn, "INSERT INTO payout_requests (username, amount, status, request_date) VALUES ('$current_user', $total_pending, 'PENDING', NOW())");
        $message = "✅ Payout request submitted! Amount: ₱" . number_format($total_pending) . ". Admin will process your request within 3-5 business days.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Payout</title>
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
        h2 { font-size: 1.3rem; font-weight: 700; }
        .amount-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .amount-label {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
        }
        .amount-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e40af;
        }
        .message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.75rem;
            text-align: center;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.75rem;
            text-align: center;
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
        }
        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #64748b;
            font-size: 0.7rem;
            text-decoration: none;
        }
        .info-note {
            font-size: 0.6rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 16px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>💸 Request Payout</h2>
    </div>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="amount-card">
        <div class="amount-label">Your Pending Balance</div>
        <div class="amount-value">₱<?php echo number_format($total_pending); ?></div>
    </div>

    <?php if ($total_pending >= 100 && !$has_pending_request): ?>
        <form method="post">
            <button type="submit">✅ Submit Payout Request</button>
        </form>
    <?php elseif ($has_pending_request): ?>
        <button disabled>⏳ Request Already Pending</button>
    <?php elseif ($total_pending < 100 && $total_pending > 0): ?>
        <button disabled>⚠️ Minimum ₱100 required</button>
    <?php endif; ?>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    <div class="info-note">
        💡 Payout requests are processed within 3-5 business days.<br>
        You will be notified once your request is approved.
    </div>
</div>
</body>
</html>