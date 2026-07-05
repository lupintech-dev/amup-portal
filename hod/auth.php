<?php
// hod/auth.php — include at the TOP of every HOD page
// Usage:  require_once 'auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['hod_id'])) {
    header('Location: login.php');
    exit;
}

// Convenience variables available after including this file:
$hodId         = $_SESSION['hod_id'];
$hodName       = $_SESSION['hod_name'];
$hodEmail      = $_SESSION['hod_email'];
$hodDepartment = $_SESSION['hod_department'];
