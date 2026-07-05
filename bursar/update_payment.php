<?php
require_once '../includes/config.php';
if (!isset($_SESSION['bursar_id'])) {
    header("Location: login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php"); exit();
}

$fee_id      = (int)($_POST['fee_id'] ?? 0);
$amount_paid = (float)($_POST['amount_paid'] ?? 0);
$status      = sanitize($conn, $_POST['status'] ?? '');
$receipt_no  = sanitize($conn, $_POST['receipt_no'] ?? '');
$remark      = sanitize($conn, $_POST['remark'] ?? '');

if (!$fee_id) {
    header("Location: dashboard.php?error=invalid"); exit();
}

// Get the fee record to validate amount_paid doesn't exceed total amount
$fee = $conn->query("SELECT * FROM fees WHERE id=$fee_id")->fetch_assoc();
if (!$fee) {
    header("Location: dashboard.php?error=notfound"); exit();
}

if ($amount_paid > $fee['amount']) {
    header("Location: dashboard.php?error=overpaid"); exit();
}

// Auto-set paid_date if status is paid and not already set
$paid_date_sql = "";
if ($status === 'paid' && empty($fee['paid_date'])) {
    $paid_date_sql = ", paid_date = CURDATE()";
} elseif ($status !== 'paid') {
    $paid_date_sql = ", paid_date = NULL";
}

$stmt = $conn->prepare("
    UPDATE fees
    SET amount_paid = ?,
        status      = ?,
        receipt_no  = ?,
        remark      = ?
        $paid_date_sql
    WHERE id = ?
");
$stmt->bind_param('dsssi', $amount_paid, $status, $receipt_no, $remark, $fee_id);

if ($stmt->execute()) {
    header("Location: dashboard.php?success=updated"); exit();
} else {
    header("Location: dashboard.php?error=dbfail"); exit();
}