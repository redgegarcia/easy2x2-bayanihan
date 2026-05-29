<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';

$sponsor = $_SESSION['username'];
$error = '';
$success = '';

// Helper: Bilangin ang kabuuang miyembro sa isang leg
function countTotalInLeg($conn, $username) {
    if (empty($username)) return 0;
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slot1, slot2 FROM matrix_boards WHERE leader_username = '$username'"));
    $left = $board['slot1'] ?? '';
    $right = $board['slot2'] ?? '';
    $count = 1;
    $count += countTotalInLeg($conn, $left);
    $count += countTotalInLeg($conn, $right);
    return $count;
}

// Helper: Mag-record ng pairing bonus kung may bagong pair
function recordPairingBonus($conn, $username) {
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slot1, slot2 FROM matrix_boards WHERE leader_username = '$username'"));
    $left = $board['slot1'] ?? '';
    $right = $board['slot2'] ?? '';
    
    if (empty($left) || empty($right)) return 0;
    
    $left_count = countTotalInLeg($conn, $left);
    $right_count = countTotalInLeg($conn, $right);
    $current_pairs = min($left_count, $right_count);
    
    // Kunin ang existing pairs mula sa cycles table
    $existing_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM cycles WHERE username = '$username'");
    $existing_row = mysqli_fetch_assoc($existing_query);
    $existing_pairs = (int)$existing_row['total'];
    
    $new_pairs = $current_pairs - $existing_pairs;
    
    for ($i = 0; $i < $new_pairs; $i++) {
        mysqli_query($conn, "INSERT INTO cycles (username, reward_amount) VALUES ('$username', 500)");
    }
    
    return $new_pairs;
}

// Function: Hanapin ang pinakamalalim na bakanteng slot sa napiling side
function findDeepestVacant($conn, $root_user, $target_side, $depth = 0) {
    if ($depth > 50) return null;
    
    $board = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM matrix_boards WHERE leader_username = '$root_user' AND status = 'ACTIVE'"));
    if (!$board) return null;
    
    $slot_name = ($target_side == 'left') ? 'slot1' : 'slot2';
    
    if (empty($board[$slot_name])) {
        return ['user' => $root_user, 'slot' => $slot_name];
    }
    
    $downline = $board[$slot_name];
    return findDeepestVacant($conn, $downline, $target_side, $depth + 1);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_user = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    
    $check_user = mysqli_query($conn, "SELECT id FROM users WHERE username='$new_user'");
    if (mysqli_num_rows($check_user) > 0) {
        $error = "Username already exists!";
    } else {
        // Create new user and his board
        mysqli_query($conn, "INSERT INTO users (username, password, sponsor) VALUES ('$new_user', '$password', '$sponsor')");
        mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('$new_user', 'ACTIVE')");
        
        // Hanapin kung saan ilalagay (pinakamalalim na bakante)
        $placement = findDeepestVacant($conn, $sponsor, $position);
        
        if ($placement) {
            mysqli_query($conn, "UPDATE matrix_boards SET {$placement['slot']} = '$new_user' WHERE leader_username = '{$placement['user']}'");
            
            // *** IMPORTANTE: Mag-record ng pairing bonus para sa direct sponsor ***
            $direct_placement_user = $placement['user'];
            recordPairingBonus($conn, $direct_placement_user);
            
            // Kung ang sponsor ay hindi pareho ng direct_placement_user, i-record din para sa kanya
            if ($sponsor != $direct_placement_user) {
                recordPairingBonus($conn, $sponsor);
            }
            
            $success = "Member $new_user registered successfully under {$placement['user']} ({$position} side)!";
        } else {
            $error = "Cannot find vacant slot. Please try another side.";
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
            background: white;
            border-radius: 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 24px;
            color: white;
            text-align: center;
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
        label { display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select {
            width: 100%;
            padding: 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
        }
        .side-options {
            display: flex;
            gap: 12px;
        }
        .side-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .side-option.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .side-option input {
            display: none;
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            margin-top: 8px;
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
        hr { margin: 16px 0; border-color: #e2e8f0; }
    </style>
    <script>
        function selectSide(side) {
            document.getElementById('position').value = side;
            document.querySelectorAll('.side-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector(`.side-option.${side}`).classList.add('selected');
        }
    </script>
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
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Choose username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Choose password" required>
                    </div>
                    <div class="form-group">
                        <label>Placement Side</label>
                        <input type="hidden" name="position" id="position" value="left">
                        <div class="side-options">
                            <div class="side-option left selected" onclick="selectSide('left')">
                                <div style="font-size:1.5rem;">⬅️</div>
                                <div style="font-weight:700;">LEFT</div>
                                <div style="font-size:0.65rem; color:#64748b;">Slot 1</div>
                            </div>
                            <div class="side-option right" onclick="selectSide('right')">
                                <div style="font-size:1.5rem;">➡️</div>
                                <div style="font-weight:700;">RIGHT</div>
                                <div style="font-size:0.65rem; color:#64748b;">Slot 2</div>
                            </div>
                        </div>
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
