<?php
session_start();

$_SESSION = [];
session_destroy();

if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, "/");
}
if (isset($_COOKIE['user_role'])) {
    setcookie('user_role', '', time() - 3600, "/");
}

header("Location: login.php");
exit();
