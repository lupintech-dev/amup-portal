<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'Fee Payments';
$id = (int)$_SESSION['student_id'];

$filter   = $_GET['filter']   ?? 'all';
$session  = $_GET['session']  ?? '';
$semester = $_GET['semester'] ?? '';

$where = "WHERE student_id=$id";
if ($filter === 'paid')   $where .= " AND status = 'paid'";
if ($filter === 'unpaid') $where .= " AND status IN ('unpaid','partial')";
if ($session)  $where .= " AND session = '".mysqli_real_escape_string($conn, $session)."'";
if ($semester) $where .= " AND semester = '".mysqli_real_escape_string($conn, $semester)."'";

$fees = $conn->query("SELECT * FROM fees $where ORDER BY created_at DESC");

// Amounts for banner (respect filters)
$amountQuery = "SELECT SUM(amount_paid) paid, SUM(amount) total, SUM(amount - amount_paid) outstanding FROM fees $where";
$amounts = $conn->query($amountQuery)->fetch_assoc();

$countPaid   = $conn->query("SELECT COUNT(*) c FROM fees WHERE student_id=$id AND status='paid'")->fetch_assoc()['c'];
$countUnpaid = $conn->query("SELECT COUNT(*) c FROM fees WHERE student_id=$id AND status IN ('unpaid','partial')")->fetch_assoc()['c'];
$countAll    = $conn->query("SELECT COUNT(*) c FROM fees WHERE student_id=$id")->fetch_assoc()['c'];

// Get distinct sessions and semesters for dropdowns
$sessions  = $conn->query("SELECT DISTINCT session FROM fees WHERE student_id=$id ORDER BY session DESC");
$semesters = $conn->query("SELECT DISTINCT semester FROM fees WHERE student_id=$id ORDER BY semester ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Payments — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .fee-tabs {
    display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;
  }
  .fee-tab {
    padding: 9px 20px; border-radius: 8px; border: 1.5px solid #e5e7eb;
    background: #fff; font-size: 14px; font-weight: 600; color: #6b7280;
    text-decoration: none; transition: all 0.2s;
  }
  .fee-tab:hover { border-color: #7B1C3E; color: #7B1C3E; }
  .fee-tab.active-all    { background: #7B1C3E; color: #fff; border-color: #7B1C3E; }
  .fee-tab.active-paid   { background: #16a34a; color: #fff; border-color: #16a34a; }
  .fee-tab.active-unpaid { background: #dc2626; color: #fff; border-color: #dc2626; }
  .fee-tab .count {
    display: inline-block; background: rgba(255,255,255,0.25);
    border-radius: 20px; padding: 1px 8px; font-size: 12px; margin-left: 6px;
  }
  .fee-tab:not(.active-all):not(.active-paid):not(.active-unpaid) .count {
    background: #f3f4f6; color: #374151;
  }
  .filter-bar {
    display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;
  }
  .filter-bar select {
    padding: 9px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    font-size: 14px; color: #374151; background: #fff; outline: none;
    cursor: pointer; transition: border-color 0.2s;
  }
  .filter-bar select:focus { border-color: #7B1C3E; }
  .filter-bar button {
    padding: 9px 20px; background: #7B1C3E; color: #fff;
    border: none; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer;
  }
  .filter-bar button:hover { background: #550f28; }
  .clear-link {
    padding: 9px 16px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    color: #6b7280; text-decoration: none; font-size: 14px;
    display: flex; align-items: center; gap: 6px;
  }
  .clear-link:hover { border-color: #dc2626; color: #dc2626; }
  .amount-banner {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 22px; border-radius: 10px; margin-bottom: 20px;
    font-size: 15px; font-weight: 600;
  }
  .amount-banner.green { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #15803d; }
  .amount-banner.red   { background: #fef2f2; border: 1.5px solid #fecaca; color: #dc2626; }
  .amount-banner.gray  { background: #f9fafb; border: 1.5px solid #e5e7eb; color: #374151; }
  .amount-banner i { font-size: 22px; }
  .amount-banner .big { font-size: 22px; font-weight: 700; margin-left: 4px; }
  .active-filters {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;
  }
  .active-filter-tag {
    background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px;
    padding: 4px 12px; font-size: 13px; color: #374151; font-weight: 600;
  }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>Fee Payments</h1></div>

  <!-- Tabs -->
  <div class="fee-tabs">
    <a href="fees.php?filter=all&session=<?= urlencode($session) ?>&semester=<?= urlencode($semester) ?>"
       class="fee-tab <?= $filter === 'all' ? 'active-all' : '' ?>">
      <i class="fas fa-list"></i> All <span class="count"><?= $countAll ?></span>
    </a>
    <a href="fees.php?filter=paid&session=<?= urlencode($session) ?>&semester=<?= urlencode($semester) ?>"
       class="fee-tab <?= $filter === 'paid' ? 'active-paid' : '' ?>">
      <i class="fas fa-check-circle"></i> Paid <span class="count"><?= $countPaid ?></span>
    </a>
    <a href="fees.php?filter=unpaid&session=<?= urlencode($session) ?>&semester=<?= urlencode($semester) ?>"
       class="fee-tab <?= $filter === 'unpaid' ? 'active-unpaid' : '' ?>">
      <i class="fas fa-times-circle"></i> Unpaid / Partial <span class="count"><?= $countUnpaid ?></span>
    </a>
  </div>

  <!-- Session & Semester Filter -->
  <form method="GET" class="filter-bar">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <select name="session">
      <option value="">All Sessions</option>
      <?php while($s = $sessions->fetch_assoc()): ?>
      <option value="<?= $s['session'] ?>" <?= $session === $s['session'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($s['session']) ?>
      </option>
      <?php endwhile; ?>
    </select>
    <select name="semester">
      <option value="">All Semesters</option>
      <?php while($s = $semesters->fetch_assoc()): ?>
      <option value="<?= $s['semester'] ?>" <?= $semester === $s['semester'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($s['semester']) ?>
      </option>
      <?php endwhile; ?>
    </select>
    <button type="submit"><i class="fas fa-filter"></i> Filter</button>
    <?php if ($session || $semester): ?>
    <a href="fees.php?filter=<?= $filter ?>" class="clear-link"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
  </form>

  <!-- Active filter tags -->
  <?php if ($session || $semester): ?>
  <div class="active-filters">
    <?php if ($session): ?>
    <span class="active-filter-tag"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($session) ?></span>
    <?php endif; ?>
    <?php if ($semester): ?>
    <span class="active-filter-tag"><i class="fas fa-layer-group"></i> <?= htmlspecialchars($semester) ?> Semester</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Amount Banner -->
  <?php if ($filter === 'paid'): ?>
  <div class="amount-banner green">
    <i class="fas fa-check-circle"></i> Total Amount Paid:
    <span class="big"><?= formatMoney($amounts['paid'] ?? 0) ?></span>
  </div>
  <?php elseif ($filter === 'unpaid'): ?>
  <div class="amount-banner red">
    <i class="fas fa-exclamation-circle"></i> Total Outstanding Balance:
    <span class="big"><?= formatMoney($amounts['outstanding'] ?? 0) ?></span>
  </div>
  <?php else: ?>
  <div class="amount-banner gray">
    <i class="fas fa-list"></i> Total Fees Charged:
    <span class="big"><?= formatMoney($amounts['total'] ?? 0) ?></span>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="card">
    <div class="card-body" style="padding:0;">
      <?php if ($fees->num_rows === 0): ?>
      <div style="padding:40px;text-align:center;color:#6b7280;">
        <i class="fas fa-search" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.3;"></i>
        <strong>No records found</strong><br>
        <span style="font-size:14px;">Try a different filter or session.</span>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Fee Type</th><th>Amount</th><th>Paid</th>
            <th>Balance</th><th>Session</th><th>Semester</th><th>Due Date</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($f = $fees->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($f['fee_type']) ?></td>
            <td><?= formatMoney($f['amount']) ?></td>
            <td><?= formatMoney($f['amount_paid']) ?></td>
            <td><?= formatMoney($f['amount'] - $f['amount_paid']) ?></td>
            <td><?= htmlspecialchars($f['session']) ?></td>
            <td><?= htmlspecialchars($f['semester']) ?></td>
            <td><?= $f['due_date'] ?></td>
            <td>
              <span class="badge badge-<?= $f['status']==='paid' ? 'success' : ($f['status']==='partial' ? 'warning' : 'danger') ?>">
                <?= ucfirst($f['status']) ?>
              </span>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>