<?php
require_once '../includes/config.php';
require_once 'auth.php';
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$message     = '';
$messageType = '';

// ── Handle PDF Upload ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['result_file'])) {
    $file = $_FILES['result_file'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message     = 'Upload failed. Please try again.';
        $messageType = 'error';
    } elseif ($ext !== 'pdf') {
        $message     = 'Only PDF files are supported.';
        $messageType = 'error';
    } else {
        try {
            $parser   = new Parser();
            $pdf      = $parser->parseFile($file['tmp_name']);
            $text     = $pdf->getText();
            $lines    = array_filter(array_map('trim', explode("\n", $text)));
            $semester = trim($_POST['semester'] ?? '');
            $session  = trim($_POST['session']  ?? '');
            $inserted = 0;
            $skipped  = 0;

            foreach ($lines as $line) {
                if (!preg_match('/^([A-Z]+\/\d{4}\/\d{3,})/i', $line)) {
                    $skipped++;
                    continue;
                }
                $cols = preg_split('/\s{2,}|\t/', trim($line));
                if (count($cols) < 9) { $skipped++; continue; }

                [$reg_number, $course_code, $ca_score, $exam_score, $attendance, $total, $grade, $gp, $cgp] = $cols;

                $s = $conn->prepare("SELECT id FROM students WHERE reg_number = ? AND department = ?");
                $s->bind_param('ss', $reg_number, $hodDepartment);
                $s->execute();
                $student = $s->get_result()->fetch_assoc();
                if (!$student) { $skipped++; continue; }

                $ins = $conn->prepare("
                    INSERT INTO results
                        (student_id, course_code, ca_score, exam_score, attendance, total, grade, gp, cgp, semester, session, uploaded_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        ca_score = VALUES(ca_score), exam_score = VALUES(exam_score),
                        attendance = VALUES(attendance), total = VALUES(total),
                        grade = VALUES(grade), gp = VALUES(gp), cgp = VALUES(cgp)
                ");
                $ins->bind_param(
                    'isddddsddssi',
                    $student['id'], $course_code,
                    $ca_score, $exam_score, $attendance, $total,
                    $grade, $gp, $cgp,
                    $semester, $session, $hodId
                );
                $ins->execute();
                $inserted++;
            }

            $message     = "Upload complete — $inserted record(s) saved, $skipped line(s) skipped.";
            $messageType = 'success';

        } catch (\Exception $e) {
            $message     = 'Could not read PDF: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ── Fetch all results ──────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT r.*, s.full_name, s.reg_number, s.level,
           c.course_code, c.course_title
    FROM results r
    JOIN students s ON s.id = r.student_id
    JOIN courses  c ON c.id = r.course_id
    WHERE s.department = ?
    ORDER BY s.full_name, r.created_at DESC
");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$allResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Group by student ───────────────────────────────────────────────
$byStudent = [];
foreach ($allResults as $r) {
    $byStudent[$r['reg_number']]['info'] = [
        'name'  => $r['full_name'],
        'reg'   => $r['reg_number'],
        'level' => $r['level'],
    ];
    $byStudent[$r['reg_number']]['courses'][] = $r;
}

// ── Group by course ────────────────────────────────────────────────
$byCourse = [];
foreach ($allResults as $r) {
    $code = $r['course_code'] ?? 'Unknown';
    $byCourse[$r['course_id'] ?? 'Unknown'][] = $r;
}

// ── Group by semester ──────────────────────────────────────────────
$bySem = [];
foreach ($allResults as $r) {
    $key = ($r['semester'] ?? 'Unknown') . ' — ' . ($r['session'] ?? 'Unknown');
    $bySem[$key][] = $r;
}

$activeTab = $_GET['tab'] ?? 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Results — <?= htmlspecialchars($hodDepartment) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --maroon:    #6b0f2b;
    --maroon-d:  #4a0a1e;
    --gold:      #c9a84c;
    --gold-l:    #e8c96d;
    --bg:        #f5f0f2;
    --white:     #ffffff;
    --sidebar-w: 240px;
    --radius:    10px;
    --text:      #2d1a22;
    --muted:     #888;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg); color: var(--text);
    display: flex; min-height: 100vh;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w); background: var(--maroon-d);
    display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; bottom: 0;
    z-index: 100; overflow-y: auto;
  }
  .sidebar-brand {
    padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,.1);
    display: flex; align-items: center; gap: 12px;
  }
  .sidebar-brand img { width: 42px; border-radius: 6px; }
  .sidebar-brand-text .name { color: var(--white); font-weight: 600; font-size: .9rem; }
  .sidebar-brand-text .sub  { color: rgba(255,255,255,.45); font-size: .75rem; }
  .sidebar-section {
    padding: 16px 20px 6px; font-size: .68rem; letter-spacing: .1em;
    text-transform: uppercase; color: rgba(255,255,255,.35); font-weight: 600;
  }
  .sidebar-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 20px; color: rgba(255,255,255,.65);
    text-decoration: none; font-size: .88rem;
    transition: background .15s, color .15s;
  }
  .sidebar-link:hover, .sidebar-link.active {
    background: rgba(201,168,76,.15); color: var(--gold-l);
  }
  .sidebar-link .ico {
    width: 20px; display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
  }
  .sidebar-bottom {
    margin-top: auto; padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,.08);
  }
  .hod-info { color: rgba(255,255,255,.75); font-size: .83rem; }
  .hod-info .dept { color: var(--gold); font-size: .75rem; margin-top: 2px; }
  .logout-btn {
    display: flex; align-items: center; gap: 8px; margin-top: 12px;
    color: rgba(255,255,255,.5); text-decoration: none;
    font-size: .83rem; transition: color .2s;
  }
  .logout-btn:hover { color: #ff9999; }

  /* ── Main ── */
  .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }
  .topbar {
    background: var(--white); border-bottom: 1px solid #eedde3;
    padding: 16px 32px; display: flex; align-items: center; justify-content: space-between;
  }
  .topbar-title h1 { font-size: 1.35rem; font-family: 'Playfair Display', serif; color: var(--maroon-d); }
  .topbar-title p  { color: var(--muted); font-size: .82rem; margin-top: 2px; }
  .dept-pill {
    background: var(--maroon); color: var(--white);
    padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 600;
  }
  .content { padding: 28px 32px; flex: 1; }

  /* ── Alert ── */
  .alert {
    padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;
    font-size: .88rem; font-weight: 500;
  }
  .alert-success { background: #e6f9ee; color: #1a7a40; border: 1px solid #b3e6c8; }
  .alert-error   { background: #fde8e8; color: #c0392b; border: 1px solid #f5b8b8; }

  /* ── Upload Card ── */
  .upload-card {
    background: var(--white); border-radius: var(--radius);
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    padding: 28px 32px; margin-bottom: 24px;
  }
  .upload-card h2 { font-size: 1rem; font-weight: 600; color: var(--maroon-d); margin-bottom: 6px; }
  .upload-card p  { font-size: .83rem; color: var(--muted); margin-bottom: 20px; }
  .upload-row { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-start; }

  .drop-zone {
    flex: 1; min-width: 260px;
    border: 2px dashed #d0b0bb; border-radius: var(--radius);
    padding: 36px 20px; text-align: center;
    background: #fdf7f9; transition: border-color .2s, background .2s;
    cursor: pointer; position: relative;
  }
  .drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--maroon); background: #f9eff2;
  }
  .drop-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
  }
  .drop-zone .dz-icon { font-size: 2.2rem; margin-bottom: 8px; color: var(--maroon); }
  .drop-zone .dz-text { font-size: .88rem; color: var(--maroon-d); font-weight: 600; }
  .drop-zone .dz-sub  { font-size: .76rem; color: var(--muted); margin-top: 4px; }
  .drop-zone .dz-file-name {
    margin-top: 10px; font-size: .82rem;
    color: var(--maroon); font-weight: 600; display: none;
  }

  .upload-meta { display: flex; flex-direction: column; gap: 12px; min-width: 200px; }
  .upload-meta label { font-size: .8rem; font-weight: 600; color: var(--text); margin-bottom: 4px; display: block; }
  .upload-meta input, .upload-meta select {
    padding: 9px 12px; border: 1.5px solid #e0d0d5; border-radius: 8px;
    font-family: 'DM Sans', sans-serif; font-size: .85rem; outline: none;
    transition: border-color .2s; width: 100%;
  }
  .upload-meta input:focus, .upload-meta select:focus { border-color: var(--maroon); }

  .hint-box {
    background: #f0f7ff; border: 1px solid #c0d8f0; border-radius: 8px;
    padding: 12px 16px; margin-top: 16px; font-size: .8rem; color: #1a4a7a; line-height: 1.6;
  }
  .hint-box strong { display: block; margin-bottom: 4px; }

  .btn-upload {
    margin-top: 16px; padding: 10px 28px; background: var(--maroon); color: var(--white);
    border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif;
    font-size: .88rem; font-weight: 600; cursor: pointer; transition: background .2s;
  }
  .btn-upload:hover { background: var(--maroon-d); }

  /* ── Tabs ── */
  .tabs-card {
    background: var(--white); border-radius: var(--radius);
    box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden;
  }
  .tabs-nav {
    display: flex; border-bottom: 2px solid #f0e8eb; padding: 0 24px; gap: 4px;
  }
  .tab-btn {
    padding: 14px 20px; background: none; border: none;
    font-family: 'DM Sans', sans-serif; font-size: .88rem;
    font-weight: 600; color: var(--muted); cursor: pointer;
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    transition: color .2s, border-color .2s;
  }
  .tab-btn.active { color: var(--maroon); border-bottom-color: var(--maroon); }
  .tab-btn:hover  { color: var(--maroon-d); }
  .tab-content { display: none; padding: 24px; }
  .tab-content.active { display: block; }

  .tab-search { margin-bottom: 20px; }
  .tab-search input {
    padding: 9px 14px; border: 1.5px solid #e0d0d5; border-radius: 8px;
    font-family: 'DM Sans', sans-serif; font-size: .85rem; outline: none;
    width: 300px; transition: border-color .2s;
  }
  .tab-search input:focus { border-color: var(--maroon); }

  /* ── Student Accordion ── */
  .student-block {
    border: 1px solid #f0e0e5; border-radius: var(--radius);
    margin-bottom: 14px; overflow: hidden;
  }
  .student-block-header {
    background: #fdf0f3; padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer; user-select: none;
  }
  .student-block-header:hover { background: #f9e5ea; }
  .student-block-header .s-name { font-weight: 700; color: var(--maroon-d); font-size: .95rem; }
  .student-block-header .s-meta { font-size: .78rem; color: var(--muted); margin-top: 2px; }
  .student-block-header .toggle { font-size: .85rem; color: var(--maroon); font-weight: 700; }
  .student-block-body { display: none; }
  .student-block-body.open { display: block; }
  .semester-label {
    padding: 8px 14px; background: #f5edf0;
    font-size: .73rem; font-weight: 700; color: var(--maroon);
    text-transform: uppercase; letter-spacing: .06em;
  }

  /* ── Tables ── */
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: var(--maroon-d); }
  thead th {
    padding: 10px 14px; text-align: left; font-size: .75rem;
    font-weight: 600; color: rgba(255,255,255,.85);
    letter-spacing: .04em; text-transform: uppercase;
  }
  tbody tr { border-bottom: 1px solid #f5edf0; transition: background .15s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #fdf7f9; }
  tbody td { padding: 11px 14px; font-size: .855rem; }

  .grade-pill {
    display: inline-block; padding: 3px 10px; border-radius: 12px;
    font-size: .75rem; font-weight: 700;
  }
  .grade-A { background: #e6f9ee; color: #1a7a40; }
  .grade-B { background: #e6f5ff; color: #1a4a8a; }
  .grade-C { background: #fff8e1; color: #856404; }
  .grade-D { background: #fff3cd; color: #a05c00; }
  .grade-F { background: #fde8e8; color: #c0392b; }

  .empty-state { text-align: center; padding: 50px 20px; color: var(--muted); }
  .empty-state p { font-size: .9rem; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="../assets/img/logo.png" alt="AMUP"
         onerror="this.style.background='#8b1a3a';this.style.minWidth='42px';this.style.minHeight='42px'">
    <div class="sidebar-brand-text">
      <div class="name">AMUP HOD Portal</div>
      <div class="sub">Management System</div>
    </div>
  </div>

  <div class="sidebar-section">Dashboard</div>
  <a href="dashboard.php" class="sidebar-link">
    <span class="ico">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    </span>
    Overview
  </a>

  <div class="sidebar-section">Students</div>
  <a href="dashboard.php" class="sidebar-link">
    <span class="ico">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </span>
    All Students
  </a>
  <a href="dashboard.php?status=graduated" class="sidebar-link">
    <span class="ico">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    </span>
    Graduated Students
  </a>

  <div class="sidebar-section">Academics</div>
  <a href="results.php" class="sidebar-link active">
    <span class="ico">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    </span>
    Results
  </a>

  <div class="sidebar-bottom">
    <div class="hod-info">
      <div><?= htmlspecialchars($hodName) ?></div>
      <div class="dept">HOD — <?= htmlspecialchars($hodDepartment) ?></div>
    </div>
    <a href="logout.php" class="logout-btn">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h1>Results Management</h1>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="dept-pill"><?= htmlspecialchars($hodDepartment) ?></div>
  </div>

  <div class="content">

    <?php if ($message): ?>
      <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Upload Card -->
    <div class="upload-card">
      <h2>Bulk Upload Results via PDF</h2>
      <p>Upload a PDF result sheet saved from Microsoft Word. Each data row must start with the student's Reg Number.</p>

      <form method="POST" enctype="multipart/form-data">
        <div class="upload-row">
          <div class="drop-zone" id="dropZone">
            <input type="file" name="result_file" id="fileInput" accept=".pdf">
            <div class="dz-icon">
              <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <div class="dz-text">Drag & drop PDF here</div>
            <div class="dz-sub">or click to browse</div>
            <div class="dz-file-name" id="fileName"></div>
          </div>

          <div class="upload-meta">
            <div>
              <label>Semester</label>
              <select name="semester" required>
                <option value="">— Select —</option>
                <option value="First">First Semester</option>
                <option value="Second">Second Semester</option>
              </select>
            </div>
            <div>
              <label>Academic Session</label>
              <input type="text" name="session" placeholder="e.g. 2024/2025" required>
            </div>
          </div>
        </div>

        <div class="hint-box">
          <strong>Expected PDF column order:</strong>
          Reg No &nbsp;·&nbsp; Course Code &nbsp;·&nbsp; CA Score &nbsp;·&nbsp; Exam Score &nbsp;·&nbsp; Attendance &nbsp;·&nbsp; Total &nbsp;·&nbsp; Grade &nbsp;·&nbsp; GP &nbsp;·&nbsp; CGP
          <br>Rows not starting with a Reg Number are skipped automatically.
        </div>

        <button type="submit" class="btn-upload">Upload Results</button>
      </form>
    </div>

    <!-- Results Tabs -->
    <div class="tabs-card">
      <div class="tabs-nav">
        <button class="tab-btn <?= $activeTab === 'student'  ? 'active' : '' ?>" onclick="switchTab('student',  this)">By Student</button>
        <button class="tab-btn <?= $activeTab === 'semester' ? 'active' : '' ?>" onclick="switchTab('semester', this)">By Semester</button>
        <button class="tab-btn <?= $activeTab === 'course'   ? 'active' : '' ?>" onclick="switchTab('course',   this)">By Course</button>
      </div>

      <!-- TAB 1: By Student -->
      <div class="tab-content <?= $activeTab === 'student' ? 'active' : '' ?>" id="tab-student">
        <div class="tab-search">
          <input type="text" id="searchStudent" placeholder="Search student name or reg number…" oninput="filterStudents()">
        </div>
        <?php if ($byStudent): ?>
          <?php foreach ($byStudent as $reg => $data): ?>
            <?php
              $semGroups = [];
              foreach ($data['courses'] as $c) {
                  $semGroups[$c['semester'] . ' — ' . $c['session']][] = $c;
              }
              $cgp = end($data['courses'])['cgp'] ?? '—';
            ?>
            <div class="student-block" data-name="<?= strtolower($data['info']['name']) ?>" data-reg="<?= strtolower($reg) ?>">
              <div class="student-block-header" onclick="toggleBlock(this)">
                <div>
                  <div class="s-name"><?= htmlspecialchars($data['info']['name']) ?></div>
                  <div class="s-meta"><?= htmlspecialchars($reg) ?> &nbsp;·&nbsp; <?= htmlspecialchars($data['info']['level']) ?> &nbsp;·&nbsp; CGP: <?= $cgp ?></div>
                </div>
                <span class="toggle">▼</span>
              </div>
              <div class="student-block-body">
                <?php foreach ($semGroups as $semLabel => $courses): ?>
                  <div class="semester-label"><?= htmlspecialchars($semLabel) ?></div>
                  <table>
                    <thead>
                      <tr>
                        <th>Course Code</th><th>CA</th><th>Exam</th>
                        <th>Attendance</th><th>Total</th><th>Grade</th><th>GP</th><th>CGP</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($courses as $c): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                        <td><?= $c['ca_score'] ?></td>
                        <td><?= $c['exam_score'] ?></td>
                        <td><?= $c['attendance'] ?></td>
                        <td><?= $c['total_score'] ?></td>
                        <td><span class="grade-pill grade-<?= strtoupper($c['grade'][0]) ?>"><?= htmlspecialchars($c['grade']) ?></span></td>
                        <td><?= $c['grade_point'] ?></td>
                        <td><?= $c['cgp'] ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state"><p>No results uploaded yet.</p></div>
        <?php endif; ?>
      </div>

      <!-- TAB 2: By Semester -->
      <div class="tab-content <?= $activeTab === 'semester' ? 'active' : '' ?>" id="tab-semester">
        <?php if ($bySem): ?>
          <?php foreach ($bySem as $semLabel => $rows): ?>
            <div class="semester-label" style="margin-bottom:12px;border-radius:6px"><?= htmlspecialchars($semLabel) ?></div>
            <table style="margin-bottom:28px">
              <thead>
                <tr>
                  <th>#</th><th>Reg No</th><th>Name</th><th>Level</th>
                  <th>Course</th><th>CA</th><th>Exam</th><th>Attend</th>
                  <th>Total</th><th>Grade</th><th>GP</th><th>CGP</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($r['reg_number']) ?></td>
                  <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                  <td><?= htmlspecialchars($r['level']) ?></td>
                  <td><?= htmlspecialchars($r['course_code']) ?></td>
                  <td><?= $r['ca_score'] ?></td>
                  <td><?= $r['exam_score'] ?></td>
                  <td><?= $r['attendance'] ?></td>
                  <td><?= $r['total_score'] ?></td>
                  <td><span class="grade-pill grade-<?= strtoupper($r['grade'][0]) ?>"><?= htmlspecialchars($r['grade']) ?></span></td>
                  <td><?= $r['grade_point'] ?></td>
                  <td><?= $r['cgp'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state"><p>No results uploaded yet.</p></div>
        <?php endif; ?>
      </div>

      <!-- TAB 3: By Course -->
      <div class="tab-content <?= $activeTab === 'course' ? 'active' : '' ?>" id="tab-course">
        <div class="tab-search">
          <input type="text" id="searchCourse" placeholder="Search course code…" oninput="filterCourses()">
        </div>
        <?php if ($byCourse): ?>
          <?php foreach ($byCourse as $code => $rows): ?>
            <div class="student-block course-block" data-code="<?= strtolower($code) ?>">
              <div class="student-block-header" onclick="toggleBlock(this)">
                <div>
                  <div class="s-name"><?= htmlspecialchars($code) ?></div>
                  <div class="s-meta"><?= count($rows) ?> student(s)</div>
                </div>
                <span class="toggle">▼</span>
              </div>
              <div class="student-block-body">
                <table>
                  <thead>
                    <tr>
                      <th>#</th><th>Reg No</th><th>Name</th><th>Level</th>
                      <th>CA</th><th>Exam</th><th>Attend</th><th>Total</th>
                      <th>Grade</th><th>GP</th><th>CGP</th><th>Semester</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><?= htmlspecialchars($r['reg_number']) ?></td>
                      <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                      <td><?= htmlspecialchars($r['level']) ?></td>
                      <td><?= $r['ca_score'] ?></td>
                      <td><?= $r['exam_score'] ?></td>
                      <td><?= $r['attendance'] ?></td>
                      <td><?= $r['total'] ?></td>
                      <td><span class="grade-pill grade-<?= strtoupper($r['grade'][0]) ?>"><?= htmlspecialchars($r['grade']) ?></span></td>
                      <td><?= $r['gp'] ?></td>
                      <td><?= $r['cgp'] ?></td>
                      <td><?= htmlspecialchars($r['semester']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state"><p>No results uploaded yet.</p></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<script>
  function switchTab(name, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
  }

  function toggleBlock(header) {
    const body   = header.nextElementSibling;
    const toggle = header.querySelector('.toggle');
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    toggle.textContent = isOpen ? '▼' : '▲';
  }

  function filterStudents() {
    const q = document.getElementById('searchStudent').value.toLowerCase();
    document.querySelectorAll('.student-block[data-name]').forEach(block => {
      block.style.display = (block.dataset.name.includes(q) || block.dataset.reg.includes(q)) ? '' : 'none';
    });
  }

  function filterCourses() {
    const q = document.getElementById('searchCourse').value.toLowerCase();
    document.querySelectorAll('.course-block').forEach(block => {
      block.style.display = block.dataset.code.includes(q) ? '' : 'none';
    });
  }

  const dropZone  = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  const fileName  = document.getElementById('fileName');

  fileInput.addEventListener('change', () => {
    if (fileInput.files.length) {
      fileName.textContent = fileInput.files[0].name;
      fileName.style.display = 'block';
    }
  });
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    fileInput.files = e.dataTransfer.files;
    if (fileInput.files.length) {
      fileName.textContent = fileInput.files[0].name;
      fileName.style.display = 'block';
    }
  });
</script>

</body>
</html>