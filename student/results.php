<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'My Results';
$id = (int)$_SESSION['student_id'];

$session  = $_GET['session']  ?? '';
$semester = $_GET['semester'] ?? '';

$where = "WHERE r.student_id = $id";
if ($session)  $where .= " AND r.session = '".mysqli_real_escape_string($conn, $session)."'";
if ($semester) $where .= " AND r.semester = '".mysqli_real_escape_string($conn, $semester)."'";

$results = $conn->query("
    SELECT r.*, c.course_code, c.course_title, c.credit_units
    FROM results r
    JOIN courses c ON r.course_id = c.id
    $where
    ORDER BY r.session DESC, r.semester ASC
");
$rows = [];
while ($row = $results->fetch_assoc()) $rows[] = $row;
$gpa = computeGPA($rows);

// All rows unfiltered for full GPA
$allResults = $conn->query("
    SELECT r.*, c.course_code, c.course_title, c.credit_units
    FROM results r JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = $id
");
$allRows = [];
while ($row = $allResults->fetch_assoc()) $allRows[] = $row;
$overallGpa = computeGPA($allRows);

// Distinct sessions and semesters for dropdowns
$sessions  = $conn->query("SELECT DISTINCT session FROM results WHERE student_id=$id ORDER BY session DESC");
$semesters = $conn->query("SELECT DISTINCT semester FROM results WHERE student_id=$id ORDER BY semester ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Results — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .filter-bar {
    display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;
  }
  .filter-bar select {
    padding: 9px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    font-size: 14px; color: #374151; background: #fff; outline: none;
    cursor: pointer; transition: border-color 0.2s;
  }
  .filter-bar select:focus { border-color: #7B1C3E; }
  .filter-bar button {
    padding: 9px 20px; background: #7B1C3E; color: #fff;
    border: none; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer;
  }
  .filter-bar button:hover { background: #550f28; }
  .clear-link {
    padding: 9px 16px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    color: #6b7280; text-decoration: none; font-size: 14px;
    display: flex; align-items: center; gap: 6px;
  }
  .clear-link:hover { border-color: #dc2626; color: #dc2626; }
  .gpa-cards {
    display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
  }
  .gpa-card {
    flex: 1; min-width: 180px; padding: 18px 22px; border-radius: 10px;
    border: 1.5px solid #e5e7eb; background: #fff;
  }
  .gpa-card .label {
    font-size: 12px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.5px; color: #6b7280; margin-bottom: 6px;
  }
  .gpa-card .value {
    font-size: 28px; font-weight: 700; color: #7B1C3E;
  }
  .gpa-card .sub {
    font-size: 12px; color: #6b7280; margin-top: 2px;
  }
  .active-filters {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;
  }
  .active-filter-tag {
    background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px;
    padding: 4px 12px; font-size: 13px; color: #374151; font-weight: 600;
  }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1>My Academic Results</h1>
  </div>

  <!-- GPA Cards -->
  <div class="gpa-cards">
    <div class="gpa-card">
      <div class="label">Overall GPA</div>
      <div class="value"><?= $overallGpa ?></div>
      <div class="sub">All sessions combined</div>
    </div>
    <?php if ($session || $semester): ?>
    <div class="gpa-card">
      <div class="label">Filtered GPA</div>
      <div class="value"><?= $gpa ?></div>
      <div class="sub">
        <?= $session ? htmlspecialchars($session) : 'All sessions' ?>
        <?= $semester ? ' — '.htmlspecialchars($semester).' Semester' : '' ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Filter Bar -->
  <form method="GET" class="filter-bar">
    <select name="session">
      <option value="">All Sessions</option>
      <?php while($s = $sessions->fetch_assoc()): ?>
      <option value="<?= $s['session'] ?>" <?= $session === $s['session'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($s['session']) ?>
      </option>
      <?php endwhile; ?>
    </select>
    <select name="semester">
      <option value="">All Semesters</option>
      <?php while($s = $semesters->fetch_assoc()): ?>
      <option value="<?= $s['semester'] ?>" <?= $semester === $s['semester'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($s['semester']) ?>
      </option>
      <?php endwhile; ?>
    </select>
    <button type="submit"><i class="fas fa-filter"></i> Filter</button>
    <?php if ($session || $semester): ?>
    <a href="results.php" class="clear-link"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
  </form>

  <!-- Active filter tags -->
  <?php if ($session || $semester): ?>
  <div class="active-filters">
    <?php if ($session): ?>
    <span class="active-filter-tag"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($session) ?></span>
    <?php endif; ?>
    <?php if ($semester): ?>
    <span class="active-filter-tag"><i class="fas fa-layer-group"></i> <?= htmlspecialchars($semester) ?> Semester</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="card">
    <div class="card-body" style="padding:0;">
      <?php if (empty($rows)): ?>
      <div style="padding:40px;text-align:center;color:#6b7280;">
        <i class="fas fa-search" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.3;"></i>
        <strong>No results found</strong><br>
        <span style="font-size:14px;">Try a different session or semester.</span>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Course Code</th><th>Course Title</th><th>Units</th>
            <th>CA</th><th>Exam</th><th>Total</th><th>Grade</th>
            <th>Session</th><th>Semester</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['course_code']) ?></td>
            <td><?= htmlspecialchars($r['course_title']) ?></td>
            <td><?= $r['credit_units'] ?></td>
            <td><?= $r['ca_score'] ?></td>
            <td><?= $r['exam_score'] ?></td>
            <td><?= $r['total_score'] ?></td>
            <td>
              <span class="badge badge-<?= $r['grade'] === 'F' ? 'danger' : 'success' ?>">
                <?= $r['grade'] ?>
              </span>
            </td>
            <td><?= htmlspecialchars($r['session']) ?></td>
            <td><?= htmlspecialchars($r['semester']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>