<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'Student Dashboard';

$id = (int)$_SESSION['student_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    session_destroy();
    redirect('../index.php');
}

$_SESSION['student_name'] = $student['full_name'];
$_SESSION['student_reg']  = $student['reg_number'];

// Fee summary
$feeStmt = $conn->prepare("SELECT SUM(amount) total, SUM(amount_paid) paid FROM fees WHERE student_id=?");
$feeStmt->bind_param('i', $id);
$feeStmt->execute();
$fees = $feeStmt->get_result()->fetch_assoc();

// Results count
$rcStmt = $conn->prepare("SELECT COUNT(*) c FROM results WHERE student_id=?");
$rcStmt->bind_param('i', $id);
$rcStmt->execute();
$resultsCount = $rcStmt->get_result()->fetch_assoc()['c'];

// Unread notifications
$unread = getUnreadCount($conn, $id);

// Suspension & exam eligibility logic
$isSuspended = $student['status'] === 'suspended';
$outstanding = ($fees['total'] ?? 0) - ($fees['paid'] ?? 0);
$examEligible = !$isSuspended && $outstanding <= 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Dashboard — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  /* ===== GLOBAL MOBILE RESET ===== */
  *, *::before, *::after {
    box-sizing: border-box;
  }
  html, body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    width: 100%;
  }

  /* ===== SIDEBAR OVERLAY (mobile) ===== */
  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
  }
  .sidebar-overlay.active {
    display: block;
  }

  /* ===== SIDEBAR ===== */
  .sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 260px;
    height: 100vh;
    z-index: 999;
    overflow-y: auto;
    transition: left 0.3s ease;
  }
  .sidebar.open {
    left: 0;
  }

  /* ===== TOPBAR ===== */
  .topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 997;
    width: 100%;
  }

  /* ===== MAIN CONTENT ===== */
  .main-content {
    width: 100%;
    max-width: 100%;
    padding: 80px 16px 24px 16px;
    box-sizing: border-box;
    overflow-x: hidden;
    margin-left: 0 !important;
  }

  /* ===== PAGE HEADER ===== */
  .page-header h1 {
    font-size: clamp(22px, 6vw, 32px);
    margin: 0 0 6px 0;
    word-break: break-word;
  }
  .page-header p {
    font-size: 14px;
    margin: 0 0 20px 0;
    word-break: break-word;
  }

  /* ===== STATUS BANNER ===== */
  .status-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 600;
    width: 100%;
  }
  .status-banner.suspended {
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
  }
  .status-banner.suspended i { font-size: 20px; margin-top: 2px; flex-shrink: 0; }
  .status-banner p {
    margin: 4px 0 0 0;
    font-size: 13px;
    font-weight: 400;
    opacity: 0.85;
  }

  /* ===== EXAM CARDS ===== */
  .exam-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
    width: 100%;
  }
  @media (max-width: 480px) {
    .exam-cards { grid-template-columns: 1fr; }
  }
  .exam-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    width: 100%;
    min-width: 0;
  }
  .exam-card.eligible   { border-color: #bbf7d0; background: #f0fdf4; }
  .exam-card.ineligible { border-color: #fecaca; background: #fef2f2; }
  .exam-card .icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
  }
  .exam-card.eligible   .icon { background: #dcfce7; color: #16a34a; }
  .exam-card.ineligible .icon { background: #fee2e2; color: #dc2626; }
  .exam-card .label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.5px; color: #6b7280; margin-bottom: 3px;
  }
  .exam-card .value {
    font-size: 14px; font-weight: 700;
  }
  .exam-card.eligible   .value { color: #15803d; }
  .exam-card.ineligible .value { color: #dc2626; }
  .exam-card .reason {
    font-size: 11px; color: #6b7280; margin-top: 2px; word-break: break-word;
  }

  /* ===== STATS GRID ===== */
  .stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
    width: 100%;
  }
  @media (max-width: 360px) {
    .stats-grid { grid-template-columns: 1fr; }
  }
  .stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e5e7eb;
    width: 100%;
    min-width: 0;
  }
  .stat-number {
    font-size: clamp(14px, 4vw, 20px);
    font-weight: 700;
    word-break: break-all;
  }
  .stat-label {
    font-size: 11px;
    color: #6b7280;
  }

  /* ===== INFO TABLE ===== */
  .card {
    width: 100%;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 16px;
  }
  .card-header {
    padding: 14px 16px;
    border-bottom: 1px solid #e5e7eb;
  }
  .card-header h3 { margin: 0; font-size: 15px; }
  .card-body { padding: 0; }
  .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }
  .info-table tr { border-bottom: 1px solid #f3f4f6; }
  .info-table tr:last-child { border-bottom: none; }
  .info-table td {
    padding: 10px 16px;
    vertical-align: top;
    word-break: break-word;
  }
  .info-table td:first-child {
    width: 40%;
    color: #6b7280;
  }
</style>
</head>
<body>

<!-- Sidebar overlay for mobile tap-to-close -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>Welcome, <?= htmlspecialchars($student['full_name'] ?? 'Student') ?> 👋</h1>
    <p><?= $student['department'] ?? '' ?> | <?= $student['level'] ?? '' ?> | <?= $student['reg_number'] ?? '' ?></p>
  </div>

  <!-- Suspension Alert -->
  <?php if ($isSuspended): ?>
  <div class="status-banner suspended">
    <i class="fas fa-ban"></i>
    <div>
      Your account is currently <strong>Suspended</strong>.
      <p>You cannot access exam-related services. Please contact the admin for assistance.</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Exam Eligibility & Account Status -->
  <div class="exam-cards">
    <div class="exam-card <?= $isSuspended ? 'ineligible' : 'eligible' ?>">
      <div class="icon">
        <i class="fas fa-<?= $isSuspended ? 'ban' : 'user-check' ?>"></i>
      </div>
      <div>
        <div class="label">Account Status</div>
        <div class="value"><?= $isSuspended ? 'Suspended' : 'Active' ?></div>
        <div class="reason"><?= $isSuspended ? 'Contact admin to resolve' : 'Your account is in good standing' ?></div>
      </div>
    </div>
    <div class="exam-card <?= $examEligible ? 'eligible' : 'ineligible' ?>">
      <div class="icon">
        <i class="fas fa-<?= $examEligible ? 'graduation-cap' : 'times-circle' ?>"></i>
      </div>
      <div>
        <div class="label">Exam Eligibility</div>
        <div class="value"><?= $examEligible ? 'Eligible' : 'Not Eligible' ?></div>
        <div class="reason">
          <?php if ($isSuspended): ?>
            Account is suspended
          <?php elseif ($outstanding > 0): ?>
            Outstanding balance of <?= formatMoney($outstanding) ?>
          <?php else: ?>
            All fees cleared — you're good to go
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card" onclick="window.location='results.php'" style="cursor:pointer;">
      <div class="stat-icon blue"><i class="fas fa-chart-bar"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $resultsCount ?></div>
        <div class="stat-label">Courses Recorded</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='fees.php?filter=paid'" style="cursor:pointer;">
      <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= formatMoney($fees['paid'] ?? 0) ?></div>
        <div class="stat-label">Fees Paid</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='fees.php?filter=unpaid'" style="cursor:pointer;">
      <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= formatMoney(($fees['total'] ?? 0) - ($fees['paid'] ?? 0)) ?></div>
        <div class="stat-label">Outstanding Fees</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='notifications.php'" style="cursor:pointer;">
      <div class="stat-icon yellow"><i class="fas fa-bell"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $unread ?></div>
        <div class="stat-label">Unread Notifications</div>
      </div>
    </div>
  </div>

  <!-- Student Info -->
  <div class="card">
    <div class="card-header"><h3>Student Information</h3></div>
    <div class="card-body">
      <table class="info-table">
        <tr><td><strong>Full Name</strong></td><td><?= htmlspecialchars($student['full_name'] ?? '') ?></td></tr>
        <tr><td><strong>Reg Number</strong></td><td><?= htmlspecialchars($student['reg_number'] ?? '') ?></td></tr>
        <tr><td><strong>Email</strong></td><td><?= htmlspecialchars($student['email'] ?? '') ?></td></tr>
        <tr><td><strong>Department</strong></td><td><?= htmlspecialchars($student['department'] ?? '') ?></td></tr>
        <tr><td><strong>Level</strong></td><td><?= htmlspecialchars($student['level'] ?? '') ?></td></tr>
        <tr>
          <td><strong>Status</strong></td>
          <td>
            <span class="badge badge-<?= ($student['status'] ?? '') === 'active' ? 'success' : 'danger' ?>">
              <?= ucfirst($student['status'] ?? 'unknown') ?>
            </span>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>

<script>
  // Sidebar toggle logic
  const overlay = document.getElementById('sidebarOverlay');
  const sidebar = document.querySelector('.sidebar');

  // Find your hamburger button — adjust selector if needed
  const hamburger = document.querySelector('.hamburger, .menu-toggle, [data-toggle="sidebar"], .navbar-toggler');

  if (hamburger && sidebar) {
    hamburger.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
    });
  }

  // Close sidebar when overlay is tapped
  overlay.addEventListener('click', function () {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
  });

  // Close sidebar when a menu link is tapped on mobile
  document.querySelectorAll('.sidebar a').forEach(function(link) {
    link.addEventListener('click', function () {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  });
</script>

</body>
</html>