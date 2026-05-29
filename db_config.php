<?php
$db_host = 'localhost';
$db_name = 'kayodpin_easy_2x2';
$db_user = 'kayodpin_admin';
$db_pass = 'edgie######';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>