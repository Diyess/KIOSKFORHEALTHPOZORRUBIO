<?php
session_start();

$_SESSION = [];

session_destroy();

header("Location: admin/admin_login.php");
exit();
?>
