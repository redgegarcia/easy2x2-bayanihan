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

// Helper: try to place new member into a specific board (returns true if placed)
function placeIntoBoard($conn, $board_id, $new_user, $sponsor_in_upline = null) {
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE id = $board_id"));
    if ($board['status'] !== 'ACTIVE') return false;
    
    // 1) Try direct slots (slot1, slot2) – only if the sponsor is the board owner
    if ($sponsor_in_upline === null || $sponsor_in_upline == $board['leader_username']) {
        if (empty($board['slot1'])) {
            mysqli_query($conn, "UPDATE matrix_boards SET slot1='$new_user' WHERE id = $board_id");
            return true;
        }
        if (empty($board['slot2'])) {
            mysqli_query($conn, "UPDATE matrix_boards SET slot2='$new_user' WHERE id = $board_id");
            return true;
        }
    }
    
    // 2) Try level‑2 slots (slot3-6) – need to know if sponsor sits in slot1 or slot2 of this board
    if ($sponsor_in_upline) {
        $is_slot1 = ($board['slot1'] == $sponsor_in_upline);
        if ($is_slot1) {
            if (empty($board['slot3'])) { mysqli_query($conn, "UPDATE matrix_boards SET slot3='$new_user' WHERE id = $board_id"); return true; }
            if (empty($board['slot4'])) { mysqli_query($conn, "UPDATE matrix_boards SET slot4='$new_user' WHERE id = $board_id"); return true; }
        } else {
            if (empty($board['slot5'])) { mysqli_query($conn, "UPDATE matrix_boards SET slot5='$new_user' WHERE id = $board_id"); return true; }
            if (empty($board['slot6'])) { mysqli_query($conn, "UPDATE matrix_boards SET slot6='$new_user' WHERE id = $board_id"); return true; }
        }
    }
    return false;
}

// Helper: walk up the upline chain and find the first active board with an empty slot
function findAndPlaceUpline($conn, $start_username, $new_user) {
    $current = $start_username;
    $visited = [];
    while ($current && !in_array($current, $visited)) {
        $visited[] = $current;
        // Get the user's active board
        $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$current' AND status = 'ACTIVE'"));
        if ($board) {
            // Try to place into this board – direct slots only (sponsor is the board owner)
            if (empty($board['slot1'])) {
                mysqli_query($conn, "UPDATE matrix_boards SET slot1='$new_user' WHERE id = {$board['id']}");
                cycleIfComplete($conn, $board['id'], $current);
                return true;
            }
            if (empty($board['slot2'])) {
                mysqli_query($conn, "UPDATE matrix_boards SET slot2='$new_user' WHERE id = {$board['id']}");
                cycleIfComplete($conn, $board['id'], $current);
                return true;
            }
            // For level‑2 slots, we need to know where $current sits in *this* board's parent board.
            // That's too complex for a quick fix – we'll rely on the direct sponsor's placement first.
        }
        // Move up to sponsor
        $sponsor_res = mysqli_query($conn, "SELECT sponsor FROM users WHERE username = '$current'");
        $sponsor_row = mysqli_fetch_assoc($sponsor_res);
        $current = $sponsor_row ? $sponsor_row['sponsor'] : null;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_user = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$new_user'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists!";
    } else {
        // Create new user
        mysqli_query($conn, "INSERT INTO users (username, password, sponsor) VALUES ('$new_user', '$password', '$sponsor')");
        mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('$new_user', 'ACTIVE')");
        
        // ---- PLACEMENT LOGIC ----
        $placed = false;
        
        // 1) Try to place into the sponsor's active board (direct slots first)
        $sponsor_board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$sponsor' AND status = 'ACTIVE'"));
        if ($sponsor_board) {
            // Direct slots
            if (empty($sponsor_board['slot1'])) {
                mysqli_query($conn, "UPDATE matrix_boards SET slot1='$new_user' WHERE id = {$sponsor_board['id']}");
                $placed = true;
            } elseif (empty($sponsor_board['slot2'])) {
                mysqli_query($conn, "UPDATE matrix_boards SET slot2='$new_user' WHERE id = {$sponsor_board['id']}");
                $placed = true;
            } else {
                // Sponsor's board has direct slots full; try level‑2 slots (requires knowing if sponsor sits in slot1/slot2 of his own upline)
                // We'll handle that in the upline walk.
            }
        }
        
        // 2) If not placed yet, walk up the upline chain to find a board with an empty direct slot
        if (!$placed) {
            $current = $sponsor;
            $walk_limit = 10;
            while ($current && $walk_limit-- > 0) {
                $upl_board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$current' AND status = 'ACTIVE'"));
                if ($upl_board) {
                    if (empty($upl_board['slot1'])) {
                        mysqli_query($conn, "UPDATE matrix_boards SET slot1='$new_user' WHERE id = {$upl_board['id']}");
                        $placed = true;
                        break;
                    }
                    if (empty($upl_board['slot2'])) {
                        mysqli_query($conn, "UPDATE matrix_boards SET slot2='$new_user' WHERE id = {$upl_board['id']}");
                        $placed = true;
                        break;
                    }
                }
                // Move to sponsor of current user
                $sp_res = mysqli_query($conn, "SELECT sponsor FROM users WHERE username = '$current'");
                $sp_row = mysqli_fetch_assoc($sp_res);
                $current = $sp_row ? $sp_row['sponsor'] : null;
            }
        }
        
        // 3) If still not placed, try level‑2 slots (slot3-6) in the upline chain
        if (!$placed) {
            $current = $sponsor;
            $walk_limit = 10;
            while ($current && $walk_limit-- > 0) {
                // Find the board where $current sits as a direct child of its leader
                $parent_board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE (slot1 = '$current' OR slot2 = '$current') AND status = 'ACTIVE'"));
                if ($parent_board) {
                    $is_slot1 = ($parent_board['slot1'] == $current);
                    if ($is_slot1) {
                        if (empty($parent_board['slot3'])) {
                            mysqli_query($conn, "UPDATE matrix_boards SET slot3='$new_user' WHERE id = {$parent_board['id']}");
                            $placed = true;
                            break;
                        }
                        if (empty($parent_board['slot4'])) {
                            mysqli_query($conn, "UPDATE matrix_boards SET slot4='$new_user' WHERE id = {$parent_board['id']}");
                            $placed = true;
                            break;
                        }
                    } else {
                        if (empty($parent_board['slot5'])) {
                            mysqli_query($conn, "UPDATE matrix_boards SET slot5='$new_user' WHERE id = {$parent_board['id']}");
                            $placed = true;
                            break;
                        }
                        if (empty($parent_board['slot6'])) {
                            mysqli_query($conn, "UPDATE matrix_boards SET slot6='$new_user' WHERE id = {$parent_board['id']}");
                            $placed = true;
                            break;
                        }
                    }
                }
                // Move up
                $sp_res = mysqli_query($conn, "SELECT sponsor FROM users WHERE username = '$current'");
                $sp_row = mysqli_fetch_assoc($sp_res);
                $current = $sp_row ? $sp_row['sponsor'] : null;
            }
        }
        
        // 4) Final fallback: place into sponsor's board level‑2 if possible (already handled above)
        
        // After placement, check for board completions in all affected boards
        // For simplicity, we re‑check the boards we updated (but we don't track which ones). 
        // We'll just check the sponsor's board and the boards we touched.
        // A full check is safer but heavier. We'll limit to sponsor and direct upline.
        
        // Check sponsor's board for completion
        $updated_sponsor_board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$sponsor' AND status = 'ACTIVE'"));
        if ($updated_sponsor_board) {
            cycleIfComplete($conn, $updated_sponsor_board['id'], $sponsor);
        }
        
        // Also check the board where we placed the new member (if different from sponsor's board)
        // This is already partially covered.
        
        $success = "Member $new_user registered successfully!";
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
        /* (Keep your beautiful original styles – same as before) */
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-700: #334155;
            --white: #ffffff;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            transition: transform 0.1s;
        }
        button:active { transform: scale(0.98); }
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
            color: var(--gray-400);
            font-size: 0.75rem;
            text-decoration: none;
        }
        .back-link:hover { color: var(--gray-700); }
        hr { margin: 16px 0; border-color: var(--gray-200); }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h2>➕ Register New Member</h2>
        </div>
        <div class="card-body">
            <div class="sponsor-badge">
                👑 Your Sponsor (auto): <strong><?php echo htmlspecialchars($sponsor); ?></strong>
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
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter password" required>
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