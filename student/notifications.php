<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'Notifications';
$id = $_SESSION['student_id'];

// Mark all as read
$conn->query("UPDATE notifications SET is_read=1 WHERE student_id=$id OR student_id IS NULL");
$notifs = $conn->query("SELECT * FROM notifications WHERE student_id=$id OR student_id IS NULL ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>Notifications</h1></div>
  <?php while($n = $notifs->fetch_assoc()): ?>
  <div class="card" style="margin-bottom:12px;border-left:4px solid var(--<?= $n['type'] === 'success' ? 'success' : ($n['type'] === 'warning' ? 'warning' : ($n['type'] === 'danger' ? 'danger' : 'info')) ?>);">
    <div class="card-body">
      <h4><?= htmlspecialchars($n['title']) ?></h4>
      <p style="color:#6b7280;margin:6px 0;"><?= htmlspecialchars($n['message']) ?></p>
      <small style="color:#9ca3af;"><?= timeAgo($n['created_at']) ?></small>
    </div>
  </div>
  <?php endwhile; ?>
</div>
</body>
</html>