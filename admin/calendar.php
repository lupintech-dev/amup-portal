<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Academic Calendar';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_title = sanitize($conn, $_POST['event_title'] ?? '');
    $event_date  = sanitize($conn, $_POST['event_date'] ?? '');
    $event_type  = sanitize($conn, $_POST['event_type'] ?? 'other');
    $session     = sanitize($conn, $_POST['session'] ?? '');
    $semester    = sanitize($conn, $_POST['semester'] ?? 'First');
    $description = sanitize($conn, $_POST['description'] ?? '');

    $stmt = $conn->prepare("INSERT INTO academic_calendar (session, semester, event_title, event_date, event_type, description) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssssss', $session, $semester, $event_title, $event_date, $event_type, $description);
    $stmt->execute() ? $success = 'Event added!' : $error = 'Failed to add event.';
}

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM academic_calendar WHERE id=".(int)$_GET['delete']);
    redirect('calendar.php');
}

$events = $conn->query("SELECT * FROM academic_calendar ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Academic Calendar — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>Academic Calendar</h1></div>

  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3>Add Calendar Event</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Event Title</label>
          <input type="text" name="event_title" class="form-control" placeholder="e.g. First Semester Resumption" required>
        </div>
        <div class="form-group">
          <label>Event Date</label>
          <input type="date" name="event_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Event Type</label>
          <select name="event_type" class="form-control">
            <option value="resumption">Resumption</option>
            <option value="suspension">Suspension</option>
            <option value="exam">Exam</option>
            <option value="registration">Registration</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Session</label>
          <input type="text" name="session" class="form-control" placeholder="2024/2025" required>
        </div>
        <div class="form-group">
          <label>Semester</label>
          <select name="semester" class="form-control">
            <option value="First">First</option>
            <option value="Second">Second</option>
          </select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Event</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>All Events</h3></div>
    <div class="card-body">
      <table class="data-table">
        <thead>
          <tr><th>Event</th><th>Date</th><th>Type</th><th>Session</th><th>Semester</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php while($e = $events->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($e['event_title']) ?></strong><br><small><?= htmlspecialchars($e['description']) ?></small></td>
            <td><?= date('d M Y', strtotime($e['event_date'])) ?></td>
            <td><span class="badge badge-info"><?= ucfirst($e['event_type']) ?></span></td>
            <td><?= $e['session'] ?></td>
            <td><?= $e['semester'] ?></td>
            <td><a href="calendar.php?delete=<?= $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>