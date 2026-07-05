<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'My Suspensions';
$id = (int)$_SESSION['student_id'];

$suspensions = $conn->query("SELECT * FROM student_suspensions WHERE student_id=$id ORDER BY suspended_at DESC");
$totalSuspensions = $conn->query("SELECT COUNT(*) c FROM student_suspensions WHERE student_id=$id")->fetch_assoc()['c'];
$activeSuspension = $conn->query("SELECT * FROM student_suspensions WHERE student_id=$id AND status='active' LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Suspensions — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .suspension-card {
    border-radius: 10px; padding: 20px 24px; margin-bottom: 14px;
    border: 1.5px solid #e5e7eb; background: #fff;
    display: flex; gap: 18px; align-items: flex-start;
  }
  .suspension-card.active-susp {
    border-color: #fecaca; background: #fef2f2;
  }
  .suspension-card.lifted-susp {
    border-color: #bbf7d0; background: #f0fdf4;
  }
  .susp-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
  }
  .active-susp .susp-icon  { background: #fee2e2; color: #dc2626; }
  .lifted-susp .susp-icon  { background: #dcfce7; color: #16a34a; }
  .susp-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
  .active-susp  .susp-title { color: #dc2626; }
  .lifted-susp  .susp-title { color: #15803d; }
  .susp-reason  { font-size: 14px; color: #374151; margin-bottom: 8px; }
  .susp-meta    { font-size: 12px; color: #6b7280; display: flex; gap: 16px; flex-wrap: wrap; }
  .susp-meta span { display: flex; align-items: center; gap: 5px; }
  .summary-banner {
    padding: 16px 22px; border-radius: 10px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 14px;
    font-size: 15px; font-weight: 600;
  }
  .summary-banner.red   { background: #fef2f2; border: 1.5px solid #fecaca; color: #dc2626; }
  .summary-banner.green { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #15803d; }
  .summary-banner.gray  { background: #f9fafb; border: 1.5px solid #e5e7eb; color: #374151; }
  .summary-banner i { font-size: 22px; }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1>My Suspension History</h1>
    <p><?= $totalSuspensions ?> suspension record<?= $totalSuspensions != 1 ? 's' : '' ?> found</p>
  </div>

  <!-- Current Status Banner -->
  <?php if ($activeSuspension): ?>
  <div class="summary-banner red">
    <i class="fas fa-ban"></i>
    <div>
      Your account is currently <strong>Suspended</strong>.
      <div style="font-size:13px;font-weight:400;margin-top:2px;">
        Reason: <?= htmlspecialchars($activeSuspension['reason']) ?> — Contact admin to resolve.
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="summary-banner green">
    <i class="fas fa-check-circle"></i>
    Your account is currently <strong>Active</strong> — no active suspension.
  </div>
  <?php endif; ?>

  <?php if ($totalSuspensions === 0): ?>
  <div class="summary-banner gray">
    <i class="fas fa-shield-alt"></i>
    You have no suspension history. Keep it up!
  </div>
  <?php else: ?>
    <?php while($s = $suspensions->fetch_assoc()): ?>
    <div class="suspension-card <?= $s['status'] === 'active' ? 'active-susp' : 'lifted-susp' ?>">
      <div class="susp-icon">
        <i class="fas fa-<?= $s['status'] === 'active' ? 'ban' : 'check-circle' ?>"></i>
      </div>
      <div style="flex:1;">
        <div class="susp-title">
          <?= $s['status'] === 'active' ? 'Active Suspension' : 'Suspension Lifted' ?>
        </div>
        <div class="susp-reason"><?= htmlspecialchars($s['reason']) ?></div>
        <div class="susp-meta">
          <span><i class="fas fa-calendar-times"></i> Suspended: <?= date('d M Y, h:i A', strtotime($s['suspended_at'])) ?></span>
          <?php if ($s['lifted_at']): ?>
          <span><i class="fas fa-calendar-check"></i> Lifted: <?= date('d M Y, h:i A', strtotime($s['lifted_at'])) ?></span>
          <?php endif; ?>
          <?php if ($s['lifted_by']): ?>
          <span><i class="fas fa-user-shield"></i> By: <?= htmlspecialchars($s['lifted_by']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <span class="badge badge-<?= $s['status'] === 'active' ? 'danger' : 'success' ?>">
          <?= ucfirst($s['status']) ?>
        </span>
      </div>
    </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>
</body>
</html>