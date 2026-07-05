<?php
// Fetch sidebar badge stats
$_sb_collected  = $conn->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fees")->fetch_assoc()['t'];
$_sb_unpaid     = $conn->query("SELECT COUNT(*) AS t FROM fees WHERE status='unpaid'")->fetch_assoc()['t'];
$_sb_page       = $_sb_page ?? '';
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="../assets/img/logo.png" alt="AMUP">
    <div class="sidebar-logo-text">
      <h3>AMUP Bursar</h3>
      <p>Fee Management</p>
    </div>
  </div>
  <nav>
    <div class="nav-section">Dashboard</div>
    <a href="dashboard.php" class="nav-link <?= $_sb_page==='dashboard' ? 'active' : '' ?>">
      <i class="fas fa-chart-pie"></i> Overview
    </a>

    <div class="nav-section">Fees</div>
    <a href="students.php" class="nav-link <?= $_sb_page==='students' ? 'active' : '' ?>">
      <i class="fas fa-users"></i> All Students
    </a>
    <a href="payments.php" class="nav-link <?= $_sb_page==='payments' ? 'active' : '' ?>">
      <i class="fas fa-money-bill-wave"></i> Fee Records
    </a>
    <a href="record_payment.php" class="nav-link <?= $_sb_page==='record_payment' ? 'active' : '' ?>">
      <i class="fas fa-plus-circle"></i> Record Payment
    </a>
    <a href="dashboard.php?status=paid&card=collected" class="nav-link">
      <i class="fas fa-check-circle"></i> Total Collected
      <span class="nav-badge green">₦<?= number_format($_sb_collected/1000,0) ?>k</span>
    </a>
    <a href="dashboard.php?status=unpaid&card=unpaid" class="nav-link">
      <i class="fas fa-clock"></i> Unpaid Records
      <span class="nav-badge yellow"><?= $_sb_unpaid ?></span>
    </a>

    <div class="nav-section">Reports</div>
    <a href="receipts.php" class="nav-link <?= $_sb_page==='receipts' ? 'active' : '' ?>">
      <i class="fas fa-file-invoice"></i> Receipts
    </a>
    <a href="summary.php" class="nav-link <?= $_sb_page==='summary' ? 'active' : '' ?>">
      <i class="fas fa-chart-bar"></i> Summary Report
    </a>

    <div class="nav-section">Account</div>
    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
      <div>
        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($bursar_name) ?></div>
        <div style="font-size:11px;opacity:.6;">Bursar</div>
      </div>
    </div>
  </div>
</aside>