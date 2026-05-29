<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_config.php';

$current_user = $_SESSION['username'];

// Fetch all payouts for this user with actual status
$query = "SELECT * FROM cycles WHERE username = '$current_user' ORDER BY completed_date DESC";
$result = mysqli_query($conn, $query);

$my_payouts = [];
$total_direct = 0;
$total_pairing = 0;
$total_pending = 0;
$total_paid = 0;
$total_earnings = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $amount = (float)$row['reward_amount'];
    $date = $row['completed_date'];
    $status = $row['status'] ?? 'PENDING';
    
    if ($amount == 250) {
        $type = 'Direct Commission';
        $total_direct += $amount;
    } elseif ($amount == 500) {
        $type = 'Pairing Bonus';
        $total_pairing += $amount;
    } else {
        $type = 'Bonus';
    }
    
    if ($status == 'PAID') {
        $total_paid += $amount;
    } else {
        $total_pending += $amount;
    }
    
    $total_earnings += $amount;

    $my_payouts[] = [
        'amount' => $amount,
        'date' => $date,
        'type' => $type,
        'status' => $status
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Payout History | Easy 2x2</title>
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
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
        .back-link {
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            color: #2563eb;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        /* Stats Grid - DIRECT, PAIRING, TOTAL sa iisang row */
        .stats-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 100px;
            background: #f8fafc;
            border-radius: 16px;
            padding: 12px;
            text-align: center;
        }
        .stat-label {
            font-size: 0.6rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-value {
            font-size: 1.1rem;
            font-weight: 800;
        }
        .stat-card.direct .stat-value { color: #059669; }
        .stat-card.pairing .stat-value { color: #d97706; }
        .stat-card.total .stat-value { color: #1e40af; }
        
        /* Payout List */
        .payout-list {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .payout-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .payout-item:last-child { border-bottom: none; }
        
        .payout-type {
            font-weight: 700;
            font-size: 0.85rem;
        }
        .payout-type.direct { color: #059669; }
        .payout-type.pairing { color: #d97706; }
        
        .payout-date {
            font-size: 0.65rem;
            color: #94a3b8;
            margin-top: 2px;
        }
        
        .payout-right {
            text-align: right;
        }
        .payout-amount {
            font-weight: 800;
            font-size: 0.9rem;
        }
        
        /* Status Badge - consistent positioning */
        .status-badge {
            font-size: 0.55rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .info-note {
            margin-top: 16px;
            text-align: center;
            font-size: 0.6rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header-nav">
            <h2>💰 History ng Kita</h2>
            <a href="dashboard.php" class="back-link">← Back</a>
        </div>
        
        <!-- Stats: DIRECT | PAIRING | TOTAL -->
        <div class="stats-grid">
            <div class="stat-card direct">
                <div class="stat-label">DIRECT (₱250)</div>
                <div class="stat-value">₱<?php echo number_format($total_direct); ?></div>
            </div>
            <div class="stat-card pairing">
                <div class="stat-label">PAIRING (₱500)</div>
                <div class="stat-value">₱<?php echo number_format($total_pairing); ?></div>
            </div>
            <div class="stat-card total">
                <div class="stat-label">TOTAL</div>
                <div class="stat-value">₱<?php echo number_format($total_earnings); ?></div>
            </div>
        </div>
        
        <!-- Payout List -->
        <div class="payout-list">
            <?php if (count($my_payouts) > 0): ?>
                <?php foreach ($my_payouts as $p): ?>
                    <div class="payout-item">
                        <div>
                            <div class="payout-type <?php echo $p['type'] == 'Direct Commission' ? 'direct' : 'pairing'; ?>">
                                <?php echo $p['type']; ?>
                            </div>
                            <div class="payout-date"><?php echo date('M d, Y h:i A', strtotime($p['date'])); ?></div>
                        </div>
                        <div class="payout-right">
                            <div class="payout-amount">₱<?php echo number_format($p['amount']); ?></div>
                            <div class="status-badge <?php echo $p['status'] == 'PAID' ? 'status-paid' : 'status-pending'; ?>">
                                <?php echo $p['status']; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    No payouts yet.<br>
                    💡 Direct Commission: ₱250 kada direct recruit<br>
                    💡 Pairing Bonus: ₱500 kada pair (LEFT + RIGHT)
                </div>
            <?php endif; ?>
        </div>
        
        <div class="info-note">
            💰 ₱250 bawat direct recruit | 💰 ₱500 bawat pair (L+R)
        </div>
		
        <!-- ===== IDAGDAG ITO ===== -->
        <?php if ($total_pending > 0): ?>
            <div style="margin-top: 16px;">
                <a href="request_payout.php" style="display: block; text-align: center; background: #2563eb; color: white; padding: 12px; border-radius: 14px; text-decoration: none; font-weight: 700;">
                    💸 Request Payout (₱<?php echo number_format($total_pending); ?> pending)
                </a>
            </div>
        <?php endif; ?>
        <!-- ===== HANGGANG DITO ===== -->
		
    </div>
</div>
</body>
</html>