<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Suspended Students';
 
$success = ''; $error = '';
 
// Handle reinstate action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reinstate_id'])) {
    $id = (int)$_POST['reinstate_id'];
    $stmt = $conn->prepare("UPDATE students SET status='active' WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute() ? $success = 'Student reinstated successfully.' : $error = 'Failed to reinstate student.';
}
 
$suspendedStudents = $conn->query("SELECT * FROM students WHERE status='suspended' ORDER BY full_name ASC");
$totalSuspended = $conn->query("SELECT COUNT(*) c FROM students WHERE status='suspended'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Suspended Students — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>
 
<div class="main-content">
  <div class="page-header">
    <h1>Suspended Students</h1>
    <p><?= $totalSuspended ?> student<?= $totalSuspended != 1 ? 's' : '' ?> currently suspended</p>
  </div>
 
  <?php if ($success): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>
 
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-user-clock"></i> Suspended Students</h3>
      <a href="students.php" class="btn btn-sm btn-outline">
        <i class="fas fa-users"></i> All Students
      </a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($totalSuspended === 0): ?>
      <div style="padding:40px;text-align:center;color:#6b7280;">
        <i class="fas fa-check-circle" style="font-size:48px;color:#16a34a;margin-bottom:12px;display:block;"></i>
        <strong>No suspended students</strong><br>
        <span style="font-size:14px;">All students are currently active.</span>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Reg Number</th>
            <th>Department</th>
            <th>Level</th>
            <th>Email</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($s = $suspendedStudents->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= htmlspecialchars($s['reg_number']) ?></td>
            <td><?= htmlspecialchars($s['department']) ?></td>
            <td><?= htmlspecialchars($s['level']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td style="display:flex;gap:8px;align-items:center;">
              <a href="view_student.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline">
                <i class="fas fa-eye"></i>
              </a>
              <form method="POST" onsubmit="return confirm('Reinstate <?= htmlspecialchars($s['full_name']) ?>?');" style="margin:0;">
                <input type="hidden" name="reinstate_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-sm btn-primary" style="background:#16a34a;border:none;cursor:pointer;">
                  <i class="fas fa-user-check"></i> Reinstate
                </button>
              </form>
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