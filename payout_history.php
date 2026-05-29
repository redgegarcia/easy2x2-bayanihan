<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_config.php';

$current_user = $_SESSION['username'];

// Fetch all cycles (payouts) for this user from the database
$query = "SELECT * FROM cycles WHERE username = '$current_user' ORDER BY completed_date DESC";
$result = mysqli_query($conn, $query);

$my_payouts = [];
$total_earnings = 0;
$total_pending = 0;  // we'll treat all cycles as 'PAID' automatically, but we keep structure
$total_paid = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $amount = (float)$row['reward_amount'];
    $date = $row['completed_date'];
    $type = 'Matrix Completion';  // or 'Cycle Reward'
    $status = 'PAID';             // since reward is earned automatically, can be considered paid

    $my_payouts[] = [
        'amount' => $amount,
        'date' => $date,
        'type' => $type,
        'status' => $status
    ];

    $total_earnings += $amount;
    if ($status === 'PAID') {
        $total_paid += $amount;
    } else {
        $total_pending += $amount;
    }
}

// No need for CSV file anymore
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout History | Easy 2x2</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 15px; color: #1e293b; }
        .container { max-width: 500px; margin: 0 auto; }
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat-card { background: #fff; padding: 12px 8px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .value { font-size: 1rem; font-weight: bold; }
        .payout-list { background: #fff; border-radius: 12px; padding: 8px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .payout-item { display: flex; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
        .status-badge { font-size: 0.7rem; font-weight: bold; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
        .pending { background-color: #fef3c7; color: #d97706; }
        .paid { background-color: #d1fae5; color: #059669; }
        .empty-message { text-align: center; padding: 30px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-nav">
        <h2>💰 History ng Kita</h2>
        <a href="dashboard.php" style="text-decoration:none; color:#007bff; font-weight:bold;">← Back</a>
    </div>
    <div class="stats-grid">
        <div class="stat-card"><div class="label">Total</div><div class="value">₱<?php echo number_format($total_earnings); ?></div></div>
        <div class="stat-card"><div class="label">Pending</div><div class="value" style="color:#d97706;">₱<?php echo number_format($total_pending); ?></div></div>
        <div class="stat-card"><div class="label">Paid</div><div class="value" style="color:#059669;">₱<?php echo number_format($total_paid); ?></div></div>
    </div>
    <div class="payout-list">
        <?php if (count($my_payouts) > 0): ?>
            <?php foreach ($my_payouts as $p): ?>
                <div class="payout-item">
                    <div>
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($p['type']); ?></div>
                        <div style="font-size:0.75rem; color:#94a3b8;"><?php echo date('M d, Y', strtotime($p['date'])); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:bold;">₱<?php echo number_format($p['amount']); ?></div>
                        <span class="status-badge <?php echo strtolower($p['status']); ?>"><?php echo $p['status']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-message">No payout records yet. Complete a 2x2 matrix to earn ₱5,500!</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
