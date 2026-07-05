<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Fee Management';

$search = sanitize($conn, $_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (s.full_name LIKE '%$search%' OR s.reg_number LIKE '%$search%')";
}
if ($filter === 'paid') {
    $where .= " AND f.status = 'paid'";
} elseif ($filter === 'unpaid') {
    $where .= " AND f.status IN ('unpaid', 'partial')";
}

$fees = $conn->query("SELECT f.*, s.full_name, s.reg_number FROM fees f JOIN students s ON f.student_id=s.id $where ORDER BY f.created_at DESC");

$totalPaidCount   = $conn->query("SELECT COUNT(*) c FROM fees f JOIN students s ON f.student_id=s.id WHERE f.status='paid'")->fetch_assoc()['c'];
$totalUnpaidCount = $conn->query("SELECT COUNT(*) c FROM fees f JOIN students s ON f.student_id=s.id WHERE f.status IN ('unpaid','partial')")->fetch_assoc()['c'];
$totalAllCount    = $conn->query("SELECT COUNT(*) c FROM fees f JOIN students s ON f.student_id=s.id")->fetch_assoc()['c'];

// Total amounts for summary banner
$totalPaidAmount   = $conn->query("SELECT SUM(amount_paid) c FROM fees WHERE status='paid'")->fetch_assoc()['c'] ?? 0;
$totalUnpaidAmount = $conn->query("SELECT SUM(amount - amount_paid) c FROM fees WHERE status IN ('unpaid','partial')")->fetch_assoc()['c'] ?? 0;
$totalAllAmount    = $conn->query("SELECT SUM(amount) c FROM fees")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Management — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .fee-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }
  .fee-tab {
    padding: 9px 20px;
    border-radius: 8px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    font-size: 14px;
    font-weight: 600;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.2s;
  }
  .fee-tab:hover { border-color: #7B1C3E; color: #7B1C3E; }
  .fee-tab.active-all    { background: #7B1C3E; color: #fff; border-color: #7B1C3E; }
  .fee-tab.active-paid   { background: #16a34a; color: #fff; border-color: #16a34a; }
  .fee-tab.active-unpaid { background: #dc2626; color: #fff; border-color: #dc2626; }
  .fee-tab .count {
    display: inline-block;
    background: rgba(255,255,255,0.25);
    border-radius: 20px;
    padding: 1px 8px;
    font-size: 12px;
    margin-left: 6px;
  }
  .fee-tab:not(.active-all):not(.active-paid):not(.active-unpaid) .count {
    background: #f3f4f6;
    color: #374151;
  }
  .search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
  }
  .search-wrap { position: relative; flex: 1; }
  .search-wrap i {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%); color: #9ca3af;
  }
  .search-bar input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
  }
  .search-bar input:focus { border-color: #7B1C3E; }
  .search-bar button {
    padding: 10px 20px;
    background: #7B1C3E;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
  }
  .search-bar button:hover { background: #550f28; }
  .clear-link {
    padding: 10px 16px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    color: #6b7280;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .clear-link:hover { border-color: #dc2626; color: #dc2626; }
  .amount-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 15px;
    font-weight: 600;
  }
  .amount-banner.green { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #15803d; }
  .amount-banner.red   { background: #fef2f2; border: 1.5px solid #fecaca; color: #dc2626; }
  .amount-banner.gray  { background: #f9fafb; border: 1.5px solid #e5e7eb; color: #374151; }
  .amount-banner i { font-size: 22px; }
  .amount-banner .big { font-size: 22px; font-weight: 700; margin-left: 4px; }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>Fee Management</h1></div>

  <!-- Search Bar -->
  <form method="GET" class="search-bar">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <div class="search-wrap">
      <i class="fas fa-search"></i>
      <input type="text" name="search" placeholder="Search by student name or reg number..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit"><i class="fas fa-search"></i> Search</button>
    <?php if ($search): ?>
    <a href="fees.php?filter=<?= $filter ?>" class="clear-link"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
  </form>

  <!-- Tabs -->
  <div class="fee-tabs">
    <a href="fees.php?filter=all<?= $search ? '&search='.urlencode($search) : '' ?>"
       class="fee-tab <?= $filter === 'all' ? 'active-all' : '' ?>">
      <i class="fas fa-list"></i> All Records
      <span class="count"><?= $totalAllCount ?></span>
    </a>
    <a href="fees.php?filter=paid<?= $search ? '&search='.urlencode($search) : '' ?>"
       class="fee-tab <?= $filter === 'paid' ? 'active-paid' : '' ?>">
      <i class="fas fa-check-circle"></i> Paid
      <span class="count"><?= $totalPaidCount ?></span>
    </a>
    <a href="fees.php?filter=unpaid<?= $search ? '&search='.urlencode($search) : '' ?>"
       class="fee-tab <?= $filter === 'unpaid' ? 'active-unpaid' : '' ?>">
      <i class="fas fa-times-circle"></i> Unpaid / Partial
      <span class="count"><?= $totalUnpaidCount ?></span>
    </a>
  </div>

  <!-- Amount Summary Banner -->
  <?php if ($filter === 'paid'): ?>
  <div class="amount-banner green">
    <i class="fas fa-check-circle"></i>
    Total Amount Collected:
    <span class="big"><?= formatMoney($totalPaidAmount) ?></span>
  </div>
  <?php elseif ($filter === 'unpaid'): ?>
  <div class="amount-banner red">
    <i class="fas fa-exclamation-circle"></i>
    Total Outstanding Amount:
    <span class="big"><?= formatMoney($totalUnpaidAmount) ?></span>
  </div>
  <?php else: ?>
  <div class="amount-banner gray">
    <i class="fas fa-list"></i>
    Total Fee Value (All Records):
    <span class="big"><?= formatMoney($totalAllAmount) ?></span>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="card">
    <div class="card-header">
      <h3>
        <?php if ($filter === 'paid'): ?>
          <i class="fas fa-check-circle" style="color:#16a34a;"></i> Paid Fee Records
        <?php elseif ($filter === 'unpaid'): ?>
          <i class="fas fa-times-circle" style="color:#dc2626;"></i> Unpaid / Partial Records
        <?php else: ?>
          <i class="fas fa-list"></i> All Fee Records
        <?php endif; ?>
        <?php if ($search): ?>
          &nbsp;<small style="font-weight:400;color:#6b7280;">— results for "<?= htmlspecialchars($search) ?>"</small>
        <?php endif; ?>
      </h3>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($fees->num_rows === 0): ?>
      <div style="padding:40px;text-align:center;color:#6b7280;">
        <i class="fas fa-search" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.3;"></i>
        <strong>No records found</strong><br>
        <span style="font-size:14px;">Try a different search or filter.</span>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Fee Type</th>
            <th>Amount</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Due Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while($f = $fees->fetch_assoc()): ?>
          <tr>
            <td>
              <?= htmlspecialchars($f['full_name']) ?><br>
              <small style="color:#6b7280;"><?= $f['reg_number'] ?></small>
            </td>
            <td><?= htmlspecialchars($f['fee_type']) ?></td>
            <td><?= formatMoney($f['amount']) ?></td>
            <td><?= formatMoney($f['amount_paid']) ?></td>
            <td><?= formatMoney($f['amount'] - $f['amount_paid']) ?></td>
            <td>
              <span class="badge badge-<?= $f['status']==='paid' ? 'success' : ($f['status']==='partial' ? 'warning' : 'danger') ?>">
                <?= ucfirst($f['status']) ?>
              </span>
            </td>
            <td><?= $f['due_date'] ?></td>
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