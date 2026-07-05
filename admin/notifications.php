<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Notifications';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = sanitize($conn, $_POST['title'] ?? '');
    $message    = sanitize($conn, $_POST['message'] ?? '');
    $type       = sanitize($conn, $_POST['type'] ?? 'info');
    $target     = $_POST['target'] ?? 'all';
    $student_id = $target === 'all' ? 'NULL' : (int)$target;

    if (!$title || !$message) {
        $error = 'Title and message are required.';
    } else {
        $conn->query("INSERT INTO notifications (student_id, title, message, type) VALUES ($student_id, '$title', '$message', '$type')");
        $success = 'Notification sent successfully!';
    }
}

$students = $conn->query("SELECT id, full_name, reg_number FROM students ORDER BY full_name");
$notifs   = $conn->query("SELECT n.*, s.full_name FROM notifications n LEFT JOIN students s ON n.student_id = s.id ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>Notifications</h1></div>

  <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3>Send New Notification</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title" class="form-control" placeholder="Notification title" required>
        </div>
        <div class="form-group">
          <label>Message</label>
          <textarea name="message" class="form-control" rows="3" placeholder="Notification message" required></textarea>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="type" class="form-control">
            <option value="info">Info</option>
            <option value="success">Success</option>
            <option value="warning">Warning</option>
            <option value="danger">Danger</option>
          </select>
        </div>
        <div class="form-group">
          <label>Send To</label>
          <select name="target" class="form-control">
            <option value="all">All Students</option>
            <?php while($s = $students->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $s['reg_number'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Notification</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Sent Notifications</h3></div>
    <div class="card-body">
      <table class="data-table">
        <thead>
          <tr><th>Title</th><th>Message</th><th>Type</th><th>Recipient</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php while($n = $notifs->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($n['title']) ?></td>
            <td><?= htmlspecialchars($n['message']) ?></td>
            <td><span class="badge badge-info"><?= $n['type'] ?></span></td>
            <td><?= $n['full_name'] ?? 'All Students' ?></td>
            <td><?= timeAgo($n['created_at']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>