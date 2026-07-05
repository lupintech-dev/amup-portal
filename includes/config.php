<?php
session_start();
require_once __DIR__ . '/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;background:#fee2e2;color:#dc2626;border-radius:8px;margin:20px;">
    <strong>Database Connection Failed:</strong> ' . $conn->connect_error . '<br><br>
    Please ensure your database credentials are correct.
    </div>');
}
$conn->set_charset('utf8mb4');

// ── Helpers ──────────────────────────────────────
function sanitize($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}

function redirect($url) {
    header("Location: $url"); exit;
}

function isStudentLoggedIn() {
    return isset($_SESSION['student_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireStudent() {
    if (!isStudentLoggedIn()) redirect('../index.php');
}

function requireAdmin() {
    if (!isAdminLoggedIn()) redirect('../admin/login.php');
}

function gradeFromScore($total) {
    if ($total >= 70) return ['A', 4.0];
    if ($total >= 60) return ['B', 3.5];
    if ($total >= 50) return ['C', 2.5];
    if ($total >= 45) return ['D', 1.5];
    if ($total >= 40) return ['E', 1.0];
    return ['F', 0.0];
}

function computeGPA($results) {
    $totalQP = 0; $totalUnits = 0;
    foreach ($results as $r) {
        $totalQP    += $r['grade_point'] * $r['credit_units'];
        $totalUnits += $r['credit_units'];
    }
    return $totalUnits > 0 ? round($totalQP / $totalUnits, 2) : 0.00;
}

function getUnreadCount($conn, $studentId) {
    $res = $conn->query("SELECT COUNT(*) c FROM notifications WHERE (student_id=$studentId OR student_id IS NULL) AND is_read=0");
    return $res->fetch_assoc()['c'] ?? 0;
}

function formatMoney($n) {
    return '₦' . number_format($n, 2);
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)   return 'Just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return date('d M Y', strtotime($datetime));
}