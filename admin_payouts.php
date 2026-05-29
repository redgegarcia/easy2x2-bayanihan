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

// Mark payout as PAID (from cycles)
if (isset($_POST['mark_paid'])) {
    $cycle_id = (int)$_POST['cycle_id'];
    mysqli_query($conn, "UPDATE cycles SET status = 'PAID', paid_date = NOW() WHERE id = $cycle_id");
    $message = "Payout marked as PAID!";
}

// Mark all as PAID (from cycles)
if (isset($_POST['mark_all_paid'])) {
    mysqli_query($conn, "UPDATE cycles SET status = 'PAID', paid_date = NOW() WHERE status = 'PENDING'");
    $message = "All pending payouts marked as PAID!";
}

// Approve payout request
if (isset($_POST['approve_request'])) {
    $request_id = (int)$_POST['request_id'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $amount = (float)$_POST['amount'];
    
    mysqli_query($conn, "UPDATE payout_requests SET status = 'PAID', processed_date = NOW() WHERE id = $request_id");
    mysqli_query($conn, "UPDATE cycles SET status = 'PAID', paid_date = NOW() WHERE username = '$username' AND (status = 'PENDING' OR status IS NULL)");
    
    $message = "Payout request approved for $username! Amount: ₱" . number_format($amount);
}

// Reject payout request
if (isset($_POST['reject_request'])) {
    $request_id = (int)$_POST['request_id'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    mysqli_query($conn, "UPDATE payout_requests SET status = 'REJECTED', processed_date = NOW(), notes = 'Rejected by admin' WHERE id = $request_id");
    
    $message = "Payout request rejected for $username.";
}

// Get pending payouts (cycles)
$pending_result = mysqli_query($conn, "SELECT c.*, u.email, u.username FROM cycles c LEFT JOIN users u ON c.username = u.username WHERE c.status = 'PENDING' ORDER BY c.completed_date ASC");

// Get paid payouts (cycles)
$paid_result = mysqli_query($conn, "SELECT c.*, u.email, u.username FROM cycles c LEFT JOIN users u ON c.username = u.username WHERE c.status = 'PAID' ORDER BY c.paid_date DESC LIMIT 20");

// Get payout requests
$requests_result = mysqli_query($conn, "SELECT * FROM payout_requests WHERE status = 'PENDING' ORDER BY request_date ASC");

// Calculate totals
$total_pending = 0;
$pending_count = 0;
while ($row = mysqli_fetch_assoc($pending_result)) {
    $total_pending += $row['reward_amount'];
    $pending_count++;
}
mysqli_data_seek($pending_result, 0);

$total_paid = 0;
$paid_count = 0;
while ($row = mysqli_fetch_assoc($paid_result)) {
    $total_paid += $row['reward_amount'];
    $paid_count++;
}
mysqli_data_seek($paid_result, 0);

$total_requests = mysqli_num_rows($requests_result);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin Payouts</title>
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
            margin: 0 auto;
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
            flex-wrap: nowrap;
            gap: 8px;
        }
        h2 { 
            font-size: 1rem; 
            font-weight: 700; 
            white-space: nowrap;
        }
        .back-link {
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            color: #2563eb;
            font-size: 0.7rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .message {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.75rem;
            text-align: center;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.75rem;
            text-align: center;
        }
        .summary {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .summary-card {
            flex: 1;
            background: #f8fafc;
            border-radius: 16px;
            padding: 12px;
            text-align: center;
        }
        .summary-label {
            font-size: 0.55rem;
            color: #64748b;
            text-transform: uppercase;
        }
        .summary-number {
            font-size: 1.2rem;
            font-weight: 800;
        }
        .requests .summary-number { color: #8b5cf6; }
        .pending .summary-number { color: #d97706; }
        .paid .summary-number { color: #059669; }
        
        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mark-all-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.6rem;
            cursor: pointer;
        }
        
        .request-item {
            background: #f3e8ff;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #8b5cf6;
        }
        .request-info { margin-bottom: 8px; }
        .request-username { font-weight: 700; font-size: 0.85rem; }
        .request-amount { font-weight: 800; font-size: 0.9rem; color: #8b5cf6; }
        .request-date { font-size: 0.55rem; color: #64748b; }
        .request-buttons {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .approve-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.6rem;
            cursor: pointer;
            flex: 1;
        }
        .reject-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.6rem;
            cursor: pointer;
            flex: 1;
        }
        
        .payout-item {
            background: #f8fafc;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .payout-info { margin-bottom: 8px; }
        .payout-username { font-weight: 700; font-size: 0.85rem; }
        .payout-email { font-size: 0.6rem; color: #64748b; }
        .payout-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }
        .payout-amount { font-weight: 800; font-size: 0.9rem; }
        .payout-type {
            font-size: 0.55rem;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .type-direct { background: #d1fae5; color: #065f46; }
        .type-pairing { background: #fef3c7; color: #d97706; }
        .payout-date { font-size: 0.55rem; color: #64748b; }
        .mark-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            cursor: pointer;
            width: 100%;
        }
        .empty-message { text-align: center; padding: 20px; color: #94a3b8; font-size: 0.7rem; }
        hr { margin: 20px 0; }
        .paid-item {
            background: #f8fafc;
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .paid-name { font-weight: 600; font-size: 0.75rem; }
        .paid-email { font-size: 0.55rem; color: #64748b; }
        .paid-amount { font-weight: 700; font-size: 0.75rem; }
        .paid-date { font-size: 0.55rem; color: #64748b; }
        
        /* Bottom nav buttons */
        .bottom-nav {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }
        .nav-btn {
            flex: 1;
            text-align: center;
            background: #f8fafc;
            padding: 8px;
            border-radius: 12px;
            text-decoration: none;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>💰 Payout Manager</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="message">✅ <?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-card requests">
            <div class="summary-label">Requests</div>
            <div class="summary-number"><?php echo $total_requests; ?></div>
            <div style="font-size:0.55rem;">pending</div>
        </div>
        <div class="summary-card pending">
            <div class="summary-label">Pending</div>
            <div class="summary-number">₱<?php echo number_format($total_pending); ?></div>
            <div style="font-size:0.55rem;">(<?php echo $pending_count; ?> items)</div>
        </div>
        <div class="summary-card paid">
            <div class="summary-label">Total Paid</div>
            <div class="summary-number">₱<?php echo number_format($total_paid); ?></div>
            <div style="font-size:0.55rem;">(<?php echo $paid_count; ?> items)</div>
        </div>
    </div>

    <!-- PAYOUT REQUESTS SECTION -->
    <?php if ($total_requests > 0): ?>
        <div class="section-title">
            <span>📋 Payout Requests</span>
        </div>
        <?php while ($req = mysqli_fetch_assoc($requests_result)): ?>
            <div class="request-item">
                <div class="request-info">
                    <div class="request-username">👤 <?php echo htmlspecialchars($req['username']); ?></div>
                    <div class="request-amount">₱<?php echo number_format($req['amount']); ?></div>
                    <div class="request-date">Requested: <?php echo date('M d, Y h:i A', strtotime($req['request_date'])); ?></div>
                </div>
                <div class="request-buttons">
                    <form method="post" style="flex:1;">
                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($req['username']); ?>">
                        <input type="hidden" name="amount" value="<?php echo $req['amount']; ?>">
                        <button type="submit" name="approve_request" class="approve-btn" onclick="return confirm('Approve payout request for <?php echo htmlspecialchars($req['username']); ?>?')">✓ Approve</button>
                    </form>
                    <form method="post" style="flex:1;">
                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($req['username']); ?>">
                        <button type="submit" name="reject_request" class="reject-btn" onclick="return confirm('Reject payout request for <?php echo htmlspecialchars($req['username']); ?>?')">✗ Reject</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
        <hr>
    <?php endif; ?>

    <!-- PENDING PAYOUTS -->
    <div class="section-title">
        <span>⏳ Pending Commissions</span>
        <?php if ($pending_count > 0): ?>
            <form method="post" style="display:inline;">
                <button type="submit" name="mark_all_paid" class="mark-all-btn" onclick="return confirm('Mark ALL pending commissions as PAID?')">✓ Mark All</button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php if ($pending_count > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($pending_result)): ?>
            <div class="payout-item">
                <div class="payout-info">
                    <div class="payout-username"><?php echo htmlspecialchars($row['username']); ?></div>
                    <div class="payout-email"><?php echo htmlspecialchars($row['email']); ?></div>
                </div>
                <div class="payout-details">
                    <div class="payout-amount">₱<?php echo number_format($row['reward_amount']); ?></div>
                    <span class="payout-type <?php echo $row['reward_amount'] == 250 ? 'type-direct' : 'type-pairing'; ?>">
                        <?php echo $row['reward_amount'] == 250 ? 'Direct' : 'Pairing'; ?>
                    </span>
                    <div class="payout-date"><?php echo date('M d, Y', strtotime($row['completed_date'])); ?></div>
                </div>
                <form method="post">
                    <input type="hidden" name="cycle_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="mark_paid" class="mark-btn">✓ Mark Paid</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-message">✅ No pending commissions</div>
    <?php endif; ?>

    <hr>

    <!-- RECENT PAID -->
    <div class="section-title">✅ Recent Paid</div>
    <?php if ($paid_count > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($paid_result)): ?>
            <div class="paid-item">
                <div>
                    <div class="paid-name"><?php echo htmlspecialchars($row['username']); ?></div>
                    <div class="paid-email"><?php echo htmlspecialchars($row['email']); ?></div>
                </div>
                <div>
                    <div class="paid-amount">₱<?php echo number_format($row['reward_amount']); ?></div>
                    <div class="paid-date"><?php echo date('M d, Y', strtotime($row['paid_date'])); ?></div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-message">No paid payouts yet</div>
    <?php endif; ?>

    <!-- Bottom Navigation Buttons -->
    <div class="bottom-nav">
        <a href="admin_members.php" class="nav-btn">👥 Members</a>
        <a href="admin_payouts.php" class="nav-btn" style="background:#2563eb; color:white;">💰 Payouts</a>
        <a href="generate_codes.php" class="nav-btn">🔑 Codes</a>
    </div>
</div>
</body>
</html>