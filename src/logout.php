<?php
// src/logout.php - Session termination
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
