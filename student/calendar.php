<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'Academic Calendar';
$events = $conn->query("SELECT * FROM academic_calendar ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Academic Calendar — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>Academic Calendar</h1></div>
  <div class="card">
    <div class="card-body">
      <table class="data-table">
        <thead>
          <tr><th>Event</th><th>Date</th><th>Session</th><th>Semester</th><th>Type</th></tr>
        </thead>
        <tbody>
          <?php while($e = $events->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($e['event_title']) ?></strong><br><small style="color:#6b7280;"><?= htmlspecialchars($e['description']) ?></small></td>
            <td><?= date('d M Y', strtotime($e['event_date'])) ?></td>
            <td><?= $e['session'] ?></td>
            <td><?= $e['semester'] ?></td>
            <td><span class="badge badge-info"><?= ucfirst($e['event_type']) ?></span></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>