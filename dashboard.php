<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$current_user = $_SESSION['username'];

// Get all boards (newest first)
$all_boards_query = "SELECT * FROM matrix_boards WHERE leader_username = '$current_user' ORDER BY created_at DESC";
$all_boards_result = mysqli_query($conn, $all_boards_query);
$all_boards = [];
while ($row = mysqli_fetch_assoc($all_boards_result)) {
    $all_boards[] = $row;
}

if (empty($all_boards)) {
    mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('$current_user', 'ACTIVE')");
    $all_boards_result = mysqli_query($conn, $all_boards_query);
    while ($row = mysqli_fetch_assoc($all_boards_result)) {
        $all_boards[] = $row;
    }
}

$board_index = isset($_GET['board_index']) ? (int)$_GET['board_index'] : 0;
if ($board_index < 0) $board_index = 0;
if ($board_index >= count($all_boards)) $board_index = count($all_boards) - 1;

$current_board = $all_boards[$board_index];
$board_status = $current_board['status'];

$l1_1 = $current_board['slot1'] ?? '';
$l1_2 = $current_board['slot2'] ?? '';
$l2_1 = $current_board['slot3'] ?? '';
$l2_2 = $current_board['slot4'] ?? '';
$l2_3 = $current_board['slot5'] ?? '';
$l2_4 = $current_board['slot6'] ?? '';

$filled_count = 0;
foreach ([$l1_1, $l1_2, $l2_1, $l2_2, $l2_3, $l2_4] as $s) {
    if (!empty($s)) $filled_count++;
}

$is_l1_complete = (!empty($l1_1) && !empty($l1_2));
$directs_count = (!empty($l1_1) ? 1 : 0) + (!empty($l1_2) ? 1 : 0);
$reward_amount = ($filled_count == 6) ? 5500 : ($directs_count * 250);

if ($board_status == 'COMPLETED') {
    $payout_query = "SELECT reward_amount FROM cycles WHERE username = '$current_user' ORDER BY completed_date DESC LIMIT 1";
    $payout_res = mysqli_query($conn, $payout_query);
    if ($payout_row = mysqli_fetch_assoc($payout_res)) {
        $reward_amount = $payout_row['reward_amount'];
    }
}

$total_boards = count($all_boards);
$is_active_board = ($board_status == 'ACTIVE');

$can_go_older = ($board_index < $total_boards - 1);
$can_go_newer = ($board_index > 0);

// Minimalist counter: e.g., "2/2" for newest board, "1/2" for oldest
$counter_display = ($total_boards - $board_index) . '/' . $total_boards;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Easy 2x2 Bayanihan • Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ===== ALL ORIGINAL STYLES (unchanged) ===== */
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
        .progress-section { background: var(--white); border: 2px solid var(--gray-200); border-radius: 16px; padding: 14px; margin-bottom: 20px; box-shadow: var(--shadow); }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .progress-title { font-size: 0.8rem; font-weight: 600; color: var(--gray-700); }
        .progress-count { font-size: 0.8rem; font-weight: 700; color: var(--primary); background: #eff6ff; padding: 2px 10px; border-radius: 20px; }
        .progress-bar-bg { background: var(--gray-100); height: 8px; border-radius: 10px; overflow: hidden; }
        .progress-bar-fill { background: linear-gradient(90deg, #3b82f6, #2563eb); height: 100%; transition: width 0.5s ease; }
        .progress-message { font-size: 0.75rem; color: #059669; margin-top: 8px; font-weight: 600; text-align: center; }
        .pyramid-container { display: flex; flex-direction: column; align-items: center; gap: 14px; width: 100%; padding: 5px 0; }
        .pyramid-row { display: flex; justify-content: space-around; width: 100%; }
        .slot-top { width: 125px; height: 85px; }
        .slot-l1 { width: 115px; height: 82px; margin: 0 4px; }
        .slot-l2 { flex: 1; min-width: 0; max-width: 88px; height: 78px; margin: 0 2px; }
        .gap-separator { margin-right: 14px; } 
        .matrix-slot {
            background: var(--white); border: 2px solid var(--gray-200); border-radius: 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            box-shadow: var(--shadow); padding: 4px; text-align: center; position: relative;
        }
        .matrix-slot.filled-owner { background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); border-color: #1e293b; color: white; }
        .matrix-slot.filled-l1 { background: linear-gradient(145deg, #e0e7ff 0%, #c7d2fe 100%); border-color: #6366f1; }
        .matrix-slot.filled-l2 { background: linear-gradient(145deg, #d1fae5 0%, #a7f3d0 100%); border-color: #10b981; }
        .matrix-slot.empty { background: #f8fafc; border: 2px dashed var(--gray-400); color: var(--gray-400); }
        .slot-title { font-size: 0.55rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 2px; }
        .filled-owner .slot-title { color: #94a3b8; }
        .filled-l1 .slot-title { color: #4338ca; }
        .filled-l2 .slot-title { color: #065f46; }
        .slot-name { font-size: 0.85rem; font-weight: 700; color: var(--gray-900); width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 0 2px; }
        .slot-name2 { font-size: 0.75rem; font-weight: 700; color: var(--gray-900); width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 0 2px; }
        .filled-owner .slot-name { color: white; }
        .filled-l1 .slot-name { color: #1e1b4b; }
        .filled-l2 .slot-name { color: #022c22; }
        .empty .slot-name { color: var(--gray-400); font-weight: 500; font-size: 0.65rem; }
        .matrix-icon { font-size: 24px; line-height: 1; margin-bottom: 4px; display: inline-block; }
        .payout-card { background: linear-gradient(135deg, #fef3c7, #fde68a); border: 2px solid #fbbf24; border-radius: 16px; padding: 16px; margin-top: 20px; text-align: center; box-shadow: var(--shadow-md); }
        .payout-card.completed { background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-color: #34d399; }
        .payout-amount { font-size: 2rem; font-weight: 800; color: #92400e; line-height: 1; margin-bottom: 4px; }
        .completed .payout-amount { color: #065f46; }
        .payout-label { font-size: 0.75rem; color: #92400e; font-weight: 700; text-transform: uppercase; }
        .completed .payout-label { color: #065f46; }
        .action-area { margin-top: 16px; }
        .encode-link-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white; text-decoration: none; font-size: 0.9rem; font-weight: 700;
            padding: 14px; border-radius: 14px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease; border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .encode-link-btn:active { transform: scale(0.98); opacity: 0.9; }
        .encode-link-btn.disabled {
            background: #94a3b8;
            pointer-events: none;
            opacity: 0.6;
        }
        .board-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0 15px;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 40px;
            gap: 8px;
        }
        .nav-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 30px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .nav-btn.disabled {
            color: #cbd5e1;
            pointer-events: none;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }
        .board-counter {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--gray-900);
            background: white;
            padding: 6px 12px;
            border-radius: 30px;
            white-space: nowrap;
            text-align: center;
            flex-shrink: 1;
            min-width: 60px;
        }
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

            <!-- Simplified board navigation: only fraction in the middle -->
            <div class="board-nav">
                <?php if ($can_go_older): ?>
                    <a href="?board_index=<?php echo $board_index + 1; ?>" class="nav-btn">← Older</a>
                <?php else: ?>
                    <span class="nav-btn disabled">← Older</span>
                <?php endif; ?>
                
                <span class="board-counter"><?php echo $counter_display; ?></span>
                
                <?php if ($can_go_newer): ?>
                    <a href="?board_index=<?php echo $board_index - 1; ?>" class="nav-btn">Newer →</a>
                <?php else: ?>
                    <span class="nav-btn disabled">Newer →</span>
                <?php endif; ?>
            </div>

            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-title">Board Progress</span>
                    <span class="progress-count"><?php echo $filled_count; ?>/6 Filled</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?php echo ($filled_count / 6) * 100; ?>%"></div>
                </div>
                <div class="progress-message">
                    <?php 
                    if ($board_status == 'COMPLETED') {
                        echo "🎉 Board Completed! ₱5,500 reward credited.";
                    } elseif ($filled_count == 0) {
                        echo "🎯 Simulan na ang hataw! Ipasok ang iyong unang 2 directs sa Slot 1 at Slot 2!";
                    } 
                    elseif ($filled_count < 6 && !$is_l1_complete) {
                        echo "🚀 Magandang simula! Punuin ang Slot 1 & 2 para maging qualified sa ₱5,000 payout mo!";
                    } 
                    elseif ($filled_count < 6 && $is_l1_complete) {
                        echo "🔥 Galing! Kumpleto na ang Slot 1 at Slot 2 mo. Tulungan naman natin silang mag-duplicate para mapuno ang mga natitirang slots!";
                    } 
                    else {
                        echo "🎉 Board Cleared! Congratulations, handa na ang iyong payout!";
                    }
                    ?>
                </div>
            </div>

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
                <div class="pyramid-row">
                    <div class="matrix-slot slot-l2 <?php echo !empty($l2_1) ? 'filled-l2' : 'empty'; ?>">
                        <div class="slot-title">Slot 3</div>
                        <div class="slot-name2"><?php echo !empty($l2_1) ? htmlspecialchars($l2_1) : '---'; ?></div>
                    </div>
                    <div class="matrix-slot slot-l2 gap-separator <?php echo !empty($l2_2) ? 'filled-l2' : 'empty'; ?>">
                        <div class="slot-title">Slot 4</div>
                        <div class="slot-name2"><?php echo !empty($l2_2) ? htmlspecialchars($l2_2) : '---'; ?></div>
                    </div>
                    <div class="matrix-slot slot-l2 <?php echo !empty($l2_3) ? 'filled-l2' : 'empty'; ?>">
                        <div class="slot-title">Slot 5</div>
                        <div class="slot-name2"><?php echo !empty($l2_3) ? htmlspecialchars($l2_3) : '---'; ?></div>
                    </div>
                    <div class="matrix-slot slot-l2 <?php echo !empty($l2_4) ? 'filled-l2' : 'empty'; ?>">
                        <div class="slot-title">Slot 6</div>
                        <div class="slot-name2"><?php echo !empty($l2_4) ? htmlspecialchars($l2_4) : '---'; ?></div>
                    </div>
                </div>
            </div>

            <div class="payout-card <?php echo ($filled_count == 6 || $board_status == 'COMPLETED') ? 'completed' : ''; ?>">
                <div class="payout-label"><?php echo ($filled_count == 6 || $board_status == 'COMPLETED') ? 'Bayanihan Incentive Ready!' : 'Estimated Reward'; ?></div>
                <div class="payout-amount">₱<?php echo number_format($reward_amount); ?></div>
                <div class="payout-status">
                    <?php 
                    if ($board_status == 'COMPLETED') {
                        echo '✅ Completed and Paid.';
                    } elseif ($filled_count == 6) {
                        echo '✅ Matrix Completed! Cycle Initiated.';
                    } else {
                        echo '🔒 Locked • Waiting for ' . (6 - $filled_count) . ' slot(s)';
                    }
                    ?>
                </div>
            </div>

            <div class="action-area">
                <?php if ($is_active_board && $filled_count < 6): ?>
                    <a href="register_member.php" class="encode-link-btn">
                        <span>➕ Register New Member</span>
                    </a>
                <?php elseif ($is_active_board && $filled_count == 6): ?>
                    <div class="encode-link-btn disabled">
                        ⏳ New board will auto‑create
                    </div>
                <?php else: ?>
                    <div class="encode-link-btn disabled">
                        🔒 Completed board – switch to newer board
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 10px; margin-top: 12px;">
                    <a href="history.php" style="flex: 1; text-align: center; color: #64748b; font-size: 0.75rem; text-decoration: none; font-weight: 600; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
                        📜 Cycle History
                    </a>
                    <a href="payout_history.php" style="flex: 1; text-align: center; color: #64748b; font-size: 0.75rem; text-decoration: none; font-weight: 600; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
                        💰 Payout History
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