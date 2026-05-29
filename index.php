<?php
session_start();

// Kung naka-login na, diretso sa dashboard
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Kung hindi pa naka-login, pumunta sa login page
header("Location: login.php");
exit();
?>