<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function require_role($allowedRoles)
{
    if (!in_array($_SESSION['role'], (array) $allowedRoles, true)) {
        header('Location: login.php?error=access_denied');
        exit;
    }
}
?>
