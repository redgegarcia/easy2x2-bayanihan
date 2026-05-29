<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
require_once 'db_config.php';
$user = $_SESSION['username'];
$cycles = mysqli_query($conn, "SELECT * FROM cycles WHERE username='$user' ORDER BY completed_date DESC");
?>
<!DOCTYPE html>
<html>
<head><title>My Cycles</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>body{font-family:Inter;background:#f0f9ff;padding:20px;} .container{max-width:450px;margin:auto;background:white;border-radius:24px;padding:20px;} </style>
</head>
<body>
<div class="container">
    <h2>📋 My Completed Cycles</h2>
    <?php if(mysqli_num_rows($cycles) == 0): ?>
        <p>No completed cycles yet.</p>
    <?php else: ?>
        <ul>
        <?php while($c = mysqli_fetch_assoc($cycles)): ?>
            <li><?php echo $c['completed_date']; ?> – ₱<?php echo number_format($c['reward_amount']); ?></li>
        <?php endwhile; ?>
        </ul>
    <?php endif; ?>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</div>
</body>
</html>