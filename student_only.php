<?php
session_start();
include("../db_connection.php");

// Block anyone who is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Auto logout after 30 minutes idle
$timeout = 1800;
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $timeout) {
    session_destroy();
    header("Location: ../index.php?reason=timeout");
    exit();
}
$_SESSION['last_active'] = time();

$student_id   = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
?>