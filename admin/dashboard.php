<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Admin Dashboard';

$totalStudents  = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$totalResults   = $conn->query("SELECT COUNT(*) c FROM results")->fetch_assoc()['c'];
$totalHODs      = $conn->query("SELECT COUNT(*) c FROM hods WHERE status='active'")->fetch_assoc()['c'];
$totalStaff     = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='active'")->fetch_assoc()['c'];
$countPaidFees  = $conn->query("SELECT COUNT(*) c FROM fees WHERE status='paid'")->fetch_assoc()['c'];
$countUnpaid    = $conn->query("SELECT COUNT(*) c FROM fees WHERE status IN ('unpaid','partial')")->fetch_assoc()['c'];
$suspendedCount = $conn->query("SELECT COUNT(*) c FROM students WHERE status='suspended'")->fetch_assoc()['c'];
$recentStudents = $conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?></p>
  </div>

  <!-- Row 1 Stats -->
  <div class="stats-grid" style="margin-bottom:16px;">
    <div class="stat-card" onclick="window.location='students.php'" style="cursor:pointer;">
      <div class="stat-icon blue"><i class="fas fa-users"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalStudents ?></div>
        <div class="stat-label">Total Students</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='fees.php?filter=paid'" style="cursor:pointer;">
      <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $countPaidFees ?></div>
        <div class="stat-label">Fees Collected</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='fees.php?filter=unpaid'" style="cursor:pointer;">
      <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $countUnpaid ?></div>
        <div class="stat-label">Outstanding Fees</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='results.php'" style="cursor:pointer;">
      <div class="stat-icon yellow"><i class="fas fa-chart-bar"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalResults ?></div>
        <div class="stat-label">Results Entered</div>
      </div>
    </div>
  </div>

  <!-- Row 2 Stats — Bursar removed, now only 3 cards -->
  <div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" onclick="window.location='hod.php'" style="cursor:pointer;">
      <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalHODs ?></div>
        <div class="stat-label">Active HODs</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='staff.php'" style="cursor:pointer;">
      <div class="stat-icon yellow"><i class="fas fa-user-tie"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalStaff ?></div>
        <div class="stat-label">Staff Members</div>
      </div>
    </div>
    <div class="stat-card" onclick="window.location='suspended_students.php'" style="cursor:pointer;">
      <div class="stat-icon red"><i class="fas fa-user-clock"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $suspendedCount ?></div>
        <div class="stat-label">Suspended Students</div>
      </div>
    </div>
  </div>

  <!-- Recent Students -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-users"></i> Recently Registered Students</h3>
      <a href="students.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <table class="data-table">
        <thead>
          <tr><th>Name</th><th>Reg Number</th><th>Department</th><th>Level</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php while($s = $recentStudents->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= $s['reg_number'] ?></td>
            <td><?= $s['department'] ?></td>
            <td><?= $s['level'] ?></td>
            <td><span class="badge badge-<?= $s['status']==='active'?'success':'danger' ?>"><?= ucfirst($s['status']) ?></span></td>
            <td><a href="view_student.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>