<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$current_user = $_SESSION['username'];

$board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$current_user' AND status = 'ACTIVE'"));
if (!$board) {
    mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('$current_user', 'ACTIVE')");
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$current_user' AND status = 'ACTIVE'"));
}

$l1_1 = $board['slot1'] ?? '';
$l1_2 = $board['slot2'] ?? '';

function countLeg($conn, $username) {
    $count = 0;
    $result = mysqli_query($conn, "SELECT slot1, slot2 FROM matrix_boards WHERE leader_username = '$username'");
    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['slot1'])) {
            $count++;
            $count += countLeg($conn, $row['slot1']);
        }
        if (!empty($row['slot2'])) {
            $count++;
            $count += countLeg($conn, $row['slot2']);
        }
    }
    return $count;
}

$left_count = !empty($l1_1) ? 1 + countLeg($conn, $l1_1) : 0;
$right_count = !empty($l1_2) ? 1 + countLeg($conn, $l1_2) : 0;
$pairs = min($left_count, $right_count);
$bonus = $pairs * 500;

$filled_count = (!empty($l1_1) ? 1 : 0) + (!empty($l1_2) ? 1 : 0);
$is_l1_complete = (!empty($l1_1) && !empty($l1_2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Easy 2x2 Bayanihan • Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --success: #10b981;
            --success-dark: #059669;
            --gold: #f59e0b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-700: #334155;
            --gray-900: #0f172a;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 50%, #bfdbfe 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            -webkit-font-smoothing: antialiased;
        }

        .app-container {
            width: 100%;
            max-width: 430px;
            min-height: 100vh;
            background: var(--white);
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow-x: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.2);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-icon {
            width: 44px; height: 44px; background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px); border-radius: 14px; display: flex;
            align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 1.3rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: white; letter-spacing: -0.3px; }
        .logo-text span { font-size: 0.65rem; color: rgba(255, 255, 255, 0.8); font-weight: 500; text-transform: uppercase; }

        .logout-btn {
            background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px; border-radius: 10px; color: white; font-size: 0.8rem;
            font-weight: 500; text-decoration: none; backdrop-filter: blur(10px);
        }

        .main-content { padding: 20px 12px 32px; }

        .user-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid var(--gray-200); border-radius: 16px; padding: 14px;
            margin-bottom: 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow);
        }
        .user-avatar { width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), #3b82f6); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .user-info { flex: 1; }
        .user-name { font-weight: 700; font-size: 1rem; color: var(--gray-900); }
        .user-role { font-size: 0.7rem; color: var(--gray-500); font-weight: 600; }
        .status-badge { background: #d1fae5; color: #065f46; font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; }

        /* Minimalist progress text - walang box */
        .progress-text {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 500;
            color: #059669;
            margin-bottom: 16px;
            padding: 0 8px;
        }

        /* MATRIX BOXES - Mas malaki, mas prominent */
        .pyramid-container { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 16px; 
            width: 100%; 
            padding: 8px 0 16px;
        }
        .pyramid-row { display: flex; justify-content: center; width: 100%; gap: 16px; }
        .slot-top { width: 140px; height: 95px; }
        .slot-l1 { width: 130px; height: 90px; }
        
        .matrix-slot {
            background: var(--white); border: 2px solid var(--gray-200); border-radius: 16px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            box-shadow: var(--shadow-md); padding: 8px; text-align: center;
        }

        .matrix-slot.filled-owner { background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); border-color: #1e293b; color: white; }
        .matrix-slot.filled-l1 { background: linear-gradient(145deg, #e0e7ff 0%, #c7d2fe 100%); border-color: #6366f1; }
        .matrix-slot.empty { background: #f8fafc; border: 2px dashed var(--gray-400); color: var(--gray-400); }

        .slot-title { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 4px; letter-spacing: 0.5px; }
        .filled-owner .slot-title { color: #94a3b8; }
        .filled-l1 .slot-title { color: #4338ca; }

        .slot-name { font-size: 0.9rem; font-weight: 700; color: var(--gray-900); width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 0 4px; }
        .matrix-icon { font-size: 28px; line-height: 1; margin-bottom: 6px; display: inline-block; }
        .filled-owner .slot-name { color: white; }
        .filled-l1 .slot-name { color: #1e1b4b; }
        .empty .slot-name { color: var(--gray-400); font-weight: 500; font-size: 0.7rem; }

        /* COUNTERS - Mas maliit, simple, walang dominanteng box */
        .counters-row {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin: 8px 0 12px;
            padding: 6px 12px;
        }
        .counter-item {
            text-align: center;
        }
        .counter-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .counter-number {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1e293b;
        }
        .counter-item.left .counter-number { color: #1e40af; }
        .counter-item.right .counter-number { color: #065f46; }

        .pairs-badge {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: #92400e;
            background: #fef3c7;
            display: inline-block;
            width: auto;
            margin: 0 auto 12px;
            padding: 4px 16px;
            border-radius: 30px;
        }

        .payout-card { background: linear-gradient(135deg, #fef3c7, #fde68a); border: 2px solid #fbbf24; border-radius: 16px; padding: 16px; margin-top: 8px; text-align: center; box-shadow: var(--shadow-md); }
        .payout-card.completed { background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-color: #34d399; }
        .payout-amount { font-size: 2rem; font-weight: 800; color: #92400e; line-height: 1; margin-bottom: 4px; }
        .completed .payout-amount { color: #065f46; }
        .payout-label { font-size: 0.7rem; color: #92400e; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .payout-status { font-size: 0.65rem; color: #92400e; margin-top: 4px; }

        .action-area { margin-top: 16px; }
        .encode-link-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white; text-decoration: none; font-size: 0.9rem; font-weight: 700;
            padding: 14px; border-radius: 14px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease; border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .encode-link-btn:active { transform: scale(0.98); opacity: 0.9; }

        .app-footer { text-align: center; padding: 15px; font-size: 0.65rem; color: var(--gray-400); background: var(--gray-50); border-top: 1px solid var(--gray-100); margin-top: 24px; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="header">
            <div class="header-top">
                <div class="logo-area">
                    <div class="logo-icon">B</div>
                    <div class="logo-text">
                        <h1>Easy 2x2</h1>
                        <span>Bayanihan Program</span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="user-card">
                <div class="user-avatar">👤</div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($current_user); ?></div>
                    <div class="user-role">Bayanihan Account</div>
                </div>
                <div class="status-badge">● Active</div>
            </div>

            <!-- Minimalist text - walang box, walang progress bar -->
            <div class="progress-text">
                <?php 
                if ($filled_count == 0) {
                    echo "🎯 Lagyan ng tao ang iyong dalawang diamond slots para kumita.";
                } elseif ($filled_count < 2 && !$is_l1_complete) {
                    echo "🚀 Magandang simula! Punan ang Slot 1 at Slot 2 para mag-qualify sa bonus.";
                } else {
                    echo "🔥 Galing! Kumpleto na ang iyong dalawang slots. Tulungan ang iyong downline para dumami ang kaliwa at kanan!";
                }
                ?>
            </div>

            <!-- MATRIX (mas malaki ang boxes) -->
            <div class="pyramid-container">
                <div class="pyramid-row">
                    <div class="matrix-slot slot-top filled-owner">
                        <div class="matrix-icon">👑</div>
                        <div class="slot-title">Leader</div>
                        <div class="slot-name"><?php echo htmlspecialchars($current_user); ?></div>
                    </div>
                </div>

                <div class="pyramid-row">
                    <div class="matrix-slot slot-l1 <?php echo !empty($l1_1) ? 'filled-l1' : 'empty'; ?>">
                        <div class="matrix-icon">💎</div>
                        <div class="slot-title">Slot 1</div>
                        <div class="slot-name"><?php echo !empty($l1_1) ? htmlspecialchars($l1_1) : 'Open Slot'; ?></div>
                    </div>
                    <div class="matrix-slot slot-l1 <?php echo !empty($l1_2) ? 'filled-l1' : 'empty'; ?>">
                        <div class="matrix-icon">💎</div>
                        <div class="slot-title">Slot 2</div>
                        <div class="slot-name"><?php echo !empty($l1_2) ? htmlspecialchars($l1_2) : 'Open Slot'; ?></div>
                    </div>
                </div>
            </div>

            <!-- COUNTERS (maliit, simple, hindi naka-box) -->
            <div class="counters-row">
                <div class="counter-item left">
                    <div class="counter-label">LEFT COUNT</div>
                    <div class="counter-number"><?php echo $left_count; ?></div>
                </div>
                <div class="counter-item right">
                    <div class="counter-label">RIGHT COUNT</div>
                    <div class="counter-number"><?php echo $right_count; ?></div>
                </div>
            </div>

            <!-- Pairs indicator -->
            <div style="text-align: center;">
                <div class="pairs-badge">
                    🎯 <?php echo $pairs; ?> pair(s) = ₱<?php echo number_format($bonus); ?>
                </div>
            </div>

            <!-- Payout Card (yellow) -->
            <div class="payout-card <?php echo ($pairs > 0) ? 'completed' : ''; ?>">
                <div class="payout-label">Pairing Bonus</div>
                <div class="payout-amount">₱<?php echo number_format($bonus); ?></div>
                <div class="payout-status">₱500 bawat pair (LEFT + RIGHT)</div>
            </div>

            <div class="action-area">
                <a href="register_member.php" class="encode-link-btn">
                    <span>➕ Register New Member</span>
                </a>
                
                <div style="display: flex; gap: 10px; margin-top: 12px;">
                    <a href="history.php" style="flex: 1; text-align: center; color: #64748b; font-size: 0.75rem; text-decoration: none; font-weight: 600; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
                        📜 Cycle
                    </a>
                    <a href="payout_history.php" style="flex: 1; text-align: center; color: #64748b; font-size: 0.75rem; text-decoration: none; font-weight: 600; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
                        💰 Payout
                    </a>
					<a href="downline.php" style="flex: 1; text-align: center; color: #64748b; font-size: 0.75rem; text-decoration: none; font-weight: 600; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
						🌳 Downline
					</a>
                </div>
            </div>
        </div>

        <div class="app-footer">
            © 2026 Easy 2x2 Bayanihan Program
        </div>
    </div>
</body>
</html>
