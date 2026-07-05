<?php
require_once '../includes/config.php';

// Destroy only bursar session data
unset($_SESSION['bursar_id']);
unset($_SESSION['bursar_name']);

session_destroy();

header("Location: login.php"); exit();