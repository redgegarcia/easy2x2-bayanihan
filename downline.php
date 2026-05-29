<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$current_user = $_SESSION['username'];

// Bilangin ang kabuuang miyembro sa isang leg (kasama ang sarili)
function countTotalInLeg($conn, $username) {
    if (empty($username)) return 0;
    
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slot1, slot2 FROM matrix_boards WHERE leader_username = '$username'"));
    $left = $board['slot1'] ?? '';
    $right = $board['slot2'] ?? '';
    
    $count = 1; // kasama ang sarili
    $count += countTotalInLeg($conn, $left);
    $count += countTotalInLeg($conn, $right);
    
    return $count;
}

// Kunin ang lahat ng nodes na may level at counts
$all_nodes = [];

function collectNodeCounts($conn, $username, $level = 1, &$nodes) {
    if (empty($username)) return;
    
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slot1, slot2 FROM matrix_boards WHERE leader_username = '$username'"));
    $left = $board['slot1'] ?? '';
    $right = $board['slot2'] ?? '';
    
    $left_count = countTotalInLeg($conn, $left);
    $right_count = countTotalInLeg($conn, $right);
    
    $nodes[] = [
        'level' => $level,
        'leader' => $username,
        'left' => $left_count,
        'right' => $right_count
    ];
    
    // I-collect ang LEFT at RIGHT chain (para sa susunod na levels)
    if (!empty($left)) {
        collectNodeCounts($conn, $left, $level + 1, $nodes);
    }
    if (!empty($right)) {
        collectNodeCounts($conn, $right, $level + 1, $nodes);
    }
}

// Bilangin ang kabuuang left at right mula sa root
function countRootLegs($conn, $username, &$left_ref, &$right_ref) {
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slot1, slot2 FROM matrix_boards WHERE leader_username = '$username'"));
    $left = $board['slot1'] ?? '';
    $right = $board['slot2'] ?? '';
    
    $left_ref = countTotalInLeg($conn, $left);
    $right_ref = countTotalInLeg($conn, $right);
}

$nodes = [];
collectNodeCounts($conn, $current_user, 1, $nodes);

// Pag-uri-uriin ayon sa level
usort($nodes, function($a, $b) {
    if ($a['level'] != $b['level']) {
        return $a['level'] - $b['level'];
    }
    return strcmp($a['leader'], $b['leader']);
});

$root_left = 0;
$root_right = 0;
countRootLegs($conn, $current_user, $root_left, $root_right);

ob_start();
foreach ($nodes as $node) {
    echo "Level {$node['level']}\n";
    echo "LEADER: {$node['leader']}\n";
    echo "L: {$node['left']} | R: {$node['right']}\n";
    echo "--------------------------------\n";
}
$tree_output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Downline</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Courier New', monospace;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 450px;
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
        h2 { font-size: 1rem; font-weight: 700; color: #1e293b; }
        .back-link {
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            color: #2563eb;
            font-weight: 500;
            font-size: 0.7rem;
            font-family: 'Inter', sans-serif;
        }
        .stats {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 16px;
        }
        .stat {
            flex: 1;
            text-align: center;
        }
        .stat-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-number {
            font-size: 1.3rem;
            font-weight: 800;
        }
        .stat.left .stat-number { color: #1e40af; }
        .stat.right .stat-number { color: #065f46; }
        pre {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            line-height: 1.6;
            background: #fefce8;
            padding: 16px;
            border-radius: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
            color: #334155;
        }
        hr {
            margin: 16px 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }
        .footer-note {
            font-size: 0.6rem;
            text-align: center;
            color: #94a3b8;
            font-family: 'Inter', sans-serif;
        }
        .note {
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.65rem;
            color: #475569;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📋 Downline Tree</h2>
            <a href="dashboard.php" class="back-link">← Dashboard</a>
        </div>
        
        <div class="stats">
            <div class="stat left">
                <div class="stat-label">LEFT TOTAL</div>
                <div class="stat-number"><?php echo $root_left; ?></div>
            </div>
            <div class="stat right">
                <div class="stat-label">RIGHT TOTAL</div>
                <div class="stat-number"><?php echo $root_right; ?></div>
            </div>
        </div>
        
        <div class="note">
            💡 Ang bilang ay kabuuang miyembro sa ilalim ng bawat LEADER (kasama ang kanilang mga downline)
        </div>
        
        <pre><?php echo $tree_output; ?></pre>
        
        <hr>
        <div class="footer-note">
            💰 ₱500 bawat pair (LEFT + RIGHT)
        </div>
    </div>
</body>
</html>
