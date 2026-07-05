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

$feeStmt = $conn->prepare("SELECT SUM(amount) total, SUM(amount_paid) paid FROM fees WHERE student_id=?");
$feeStmt->bind_param('i', $id);
$feeStmt->execute();
$fees = $feeStmt->get_result()->fetch_assoc();

$rcStmt = $conn->prepare("SELECT COUNT(*) c FROM results WHERE student_id=?");
$rcStmt->bind_param('i', $id);
$rcStmt->execute();
$resultsCount = $rcStmt->get_result()->fetch_assoc()['c'];

$unread = getUnreadCount($conn, $id);

$isSuspended = $student['status'] === 'suspended';
$outstanding = ($fees['total'] ?? 0) - ($fees['paid'] ?? 0);
$examEligible = !$isSuspended && $outstanding <= 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  /* ── LAYOUT ── */
  *, *::before, *::after { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; overflow-x: hidden; width: 100%; background: #f3f4f6; }

  /* Desktop: sidebar always visible */
  @media (min-width: 769px) {
    .sidebar {
      position: fixed !important;
      left: 0 !important;
      top: 0;
      width: 260px;
      height: 100vh;
      z-index: 999;
      overflow-y: auto;
    }
    .main-content {
      margin-left: 260px !important;
      padding: 80px 24px 24px 24px !important;
    }
    .sidebar-toggle { display: none !important; }
    .sidebar-overlay { display: none !important; }
  }

  /* Mobile: sidebar hidden by default, shown when open */
  @media (max-width: 768px) {
    .sidebar {
      position: fixed !important;
      left: -280px !important;
      top: 0;
      width: 260px;
      height: 100vh;
      z-index: 999;
      overflow-y: auto;
      transition: left 0.3s ease;
    }
    .sidebar.open {
      left: 0 !important;
    }
    .main-content {
      margin-left: 0 !important;
      padding: 80px 16px 24px 16px !important;
    }
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 998;
    }
    .sidebar-overlay.active { display: block; }
  }

  /* ── PAGE HEADER ── */
  .page-header h1 { font-size: clamp(20px, 5vw, 30px); margin: 0 0 6px 0; }
  .page-header p  { font-size: 14px; margin: 0 0 20px 0; color: #6b7280; }

  /* ── STATUS BANNER ── */
  .status-banner {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 16px; border-radius: 10px;
    margin-bottom: 16px; font-size: 14px; font-weight: 600;
  }
  .status-banner.suspended {
    background: #fef2f2; border: 1.5px solid #fecaca; color: #dc2626;
  }
  .status-banner p { margin: 4px 0 0 0; font-size: 13px; font-weight: 400; opacity: 0.85; }

  /* ── EXAM CARDS ── */
  .exam-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px; margin-bottom: 16px;
  }
  @media (max-width: 480px) { .exam-cards { grid-template-columns: 1fr; } }

  .exam-card {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; border-radius: 10px;
    border: 1.5px solid #e5e7eb; background: #fff;
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
  .exam-card.eligible   .value { color: #15803d; font-size: 14px; font-weight: 700; }
  .exam-card.ineligible .value { color: #dc2626; font-size: 14px; font-weight: 700; }
  .exam-card .reason { font-size: 11px; color: #6b7280; margin-top: 2px; }

  /* ── STATS GRID ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px; margin-bottom: 16px;
  }
  @media (min-width: 900px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
  @media (max-width: 360px)  { .stats-grid { grid-template-columns: 1fr; } }

  .stat-card {
    display: flex; align-items: center; gap: 12px;
    padding: 16px; border-radius: 10px;
    background: #fff; border: 1px solid #e5e7eb;
    cursor: pointer; transition: box-shadow 0.2s;
  }
  .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
  .stat-number { font-size: clamp(14px, 3.5vw, 20px); font-weight: 700; }
  .stat-label  { font-size: 11px; color: #6b7280; }

  /* Icon colours */
  .stat-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0; }
  .stat-icon.blue   { background:#eff6ff; color:#3b82f6; }
  .stat-icon.green  { background:#f0fdf4; color:#16a34a; }
  .stat-icon.red    { background:#fef2f2; color:#dc2626; }
  .stat-icon.yellow { background:#fefce8; color:#ca8a04; }

  /* ── INFO CARD ── */
  .card { width:100%; border-radius:10px; background:#fff; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:16px; }
  .card-header { padding:14px 16px; border-bottom:1px solid #e5e7eb; }
  .card-header h3 { margin:0; font-size:15px; }
  .info-table { width:100%; border-collapse:collapse; font-size:13px; }
  .info-table tr { border-bottom:1px solid #f3f4f6; }
  .info-table tr:last-child { border-bottom:none; }
  .info-table td { padding:10px 16px; vertical-align:top; word-break:break-word; }
  .info-table td:first-child { width:40%; color:#6b7280; }
  .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
  .badge-success { background:#dcfce7; color:#16a34a; }
  .badge-danger  { background:#fee2e2; color:#dc2626; }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>Welcome, <?= htmlspecialchars($student['full_name'] ?? 'Student') ?> 👋</h1>
    <p><?= htmlspecialchars($student['department'] ?? '') ?> | <?= htmlspecialchars($student['level'] ?? '') ?> | <?= htmlspecialchars($student['reg_number'] ?? '') ?></p>
  </div>

  <?php if ($isSuspended): ?>
  <div class="status-banner suspended">
    <i class="fas fa-ban" style="font-size:20px;margin-top:2px;flex-shrink:0;"></i>
    <div>
      Your account is currently <strong>Suspended</strong>.
      <p>You cannot access exam-related services. Please contact the admin for assistance.</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="exam-cards">
    <div class="exam-card <?= $isSuspended ? 'ineligible' : 'eligible' ?>">
      <div class="icon"><i class="fas fa-<?= $isSuspended ? 'ban' : 'user-check' ?>"></i></div>
      <div>
        <div class="label">Account Status</div>
        <div class="value"><?= $isSuspended ? 'Suspended' : 'Active' ?></div>
        <div class="reason"><?= $isSuspended ? 'Contact admin to resolve' : 'Your account is in good standing' ?></div>
      </div>
    </div>
    <div class="exam-card <?= $examEligible ? 'eligible' : 'ineligible' ?>">
      <div class="icon"><i class="fas fa-<?= $examEligible ? 'graduation-cap' : 'times-circle' ?>"></i></div>
      <div>
        <div class="label">Exam Eligibility</div>
        <div class="value"><?= $examEligible ? 'Eligible' : 'Not Eligible' ?></div>
        <div class="reason">
          <?php if ($isSuspended): ?>Account is suspended
          <?php elseif ($outstanding > 0): ?>Outstanding: <?= formatMoney($outstanding) ?>
          <?php else: ?>All fees cleared — you're good to go
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card" onclick="window.location='results.php'">
      <div class="stat-icon blue"><i class="fas fa-chart-bar"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $resultsCount ?></div>
        <div class="stat-label">Courses Recorded</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='fees.php?filter=paid'">
      <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= formatMoney($fees['paid'] ?? 0) ?></div>
        <div class="stat-label">Fees Paid</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='fees.php?filter=unpaid'">
      <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= formatMoney(($fees['total'] ?? 0) - ($fees['paid'] ?? 0)) ?></div>
        <div class="stat-label">Outstanding Fees</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='notifications.php'">
      <div class="stat-icon yellow"><i class="fas fa-bell"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $unread ?></div>
        <div class="stat-label">Unread Notifications</div>
      </div>
    </div>
  </div>

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
  const overlay = document.getElementById('sidebarOverlay');
  const sidebar = document.getElementById('sidebar');

  // Toggle sidebar from topbar hamburger
  function toggleSidebar() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
  }

  // Close sidebar when overlay clicked
  overlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
  });

  // Close sidebar when nav link clicked on mobile
  document.querySelectorAll('.sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
      }
    });
  });
</script>
</body>
</html>