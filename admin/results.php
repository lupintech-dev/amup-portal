<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Results Manager';

$success = ''; $error = '';

// Single result save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_result') {
    $student_id = (int)$_POST['student_id'];
    $course_id  = (int)$_POST['course_id'];
    $session    = sanitize($conn, $_POST['session'] ?? '');
    $semester   = sanitize($conn, $_POST['semester'] ?? 'First');
    $ca_score   = (float)$_POST['ca_score'];
    $exam_score = (float)$_POST['exam_score'];
    $total      = $ca_score + $exam_score;
    [$grade, $gp] = gradeFromScore($total);

    $check = $conn->prepare("SELECT id FROM results WHERE student_id=? AND course_id=? AND session=? AND semester=?");
    $check->bind_param('iiss', $student_id, $course_id, $session, $semester);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE results SET ca_score=?, exam_score=?, grade=?, grade_point=? WHERE student_id=? AND course_id=? AND session=? AND semester=?");
        $stmt->bind_param('ddssiiss', $ca_score, $exam_score, $grade, $gp, $student_id, $course_id, $session, $semester);
    } else {
        $stmt = $conn->prepare("INSERT INTO results (student_id, course_id, session, semester, ca_score, exam_score, grade, grade_point) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iissddsd', $student_id, $course_id, $session, $semester, $ca_score, $exam_score, $grade, $gp);
    }
    $stmt->execute() ? $success = "Result saved! Grade: $grade (GP: $gp)" : $error = 'Failed to save result.';
}

// Bulk department upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_upload') {
    $department  = sanitize($conn, $_POST['bulk_department'] ?? '');
    $course_id   = (int)$_POST['bulk_course_id'];
    $session     = sanitize($conn, $_POST['bulk_session'] ?? '');
    $semester    = sanitize($conn, $_POST['bulk_semester'] ?? 'First');
    $bulk_data   = $_POST['bulk_data'] ?? '';
    $lines       = array_filter(array_map('trim', explode("\n", $bulk_data)));
    $saved = 0; $skipped = 0;

    foreach ($lines as $line) {
        // Format: REG_NUMBER, CA_SCORE, EXAM_SCORE
        $parts = array_map('trim', explode(',', $line));
        if (count($parts) < 3) { $skipped++; continue; }
        [$reg, $ca, $exam] = $parts;
        $reg  = strtoupper($conn->real_escape_string($reg));
        $ca   = (float)$ca;
        $exam = (float)$exam;
        if ($ca > 30 || $exam > 70) { $skipped++; continue; }
        $total = $ca + $exam;
        [$grade, $gp] = gradeFromScore($total);

        $stu = $conn->query("SELECT id FROM students WHERE reg_number='$reg'")->fetch_assoc();
        if (!$stu) { $skipped++; continue; }
        $sid = $stu['id'];

        $check = $conn->query("SELECT id FROM results WHERE student_id=$sid AND course_id=$course_id AND session='$session' AND semester='$semester'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE results SET ca_score=$ca, exam_score=$exam, grade='$grade', grade_point=$gp WHERE student_id=$sid AND course_id=$course_id AND session='$session' AND semester='$semester'");
        } else {
            $conn->query("INSERT INTO results (student_id, course_id, session, semester, ca_score, exam_score, grade, grade_point) VALUES ($sid, $course_id, '$session', '$semester', $ca, $exam, '$grade', $gp)");
        }
        $saved++;
    }
    $success = "Bulk upload complete! $saved saved, $skipped skipped.";
}

// Search students
$search  = sanitize($conn, $_GET['search'] ?? '');
$dept    = sanitize($conn, $_GET['dept'] ?? '');
$stuWhere = "WHERE 1=1";
if ($search) $stuWhere .= " AND (full_name LIKE '%$search%' OR reg_number LIKE '%$search%')";
if ($dept)   $stuWhere .= " AND department='$dept'";

$students    = $conn->query("SELECT * FROM students $stuWhere ORDER BY full_name");
$allStudents = $conn->query("SELECT id, full_name, reg_number FROM students ORDER BY full_name");
$courses     = $conn->query("SELECT * FROM courses ORDER BY course_code");
$departments = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");

// Results list
$resSearch = sanitize($conn, $_GET['res_search'] ?? '');
$resWhere  = "WHERE 1=1";
if ($resSearch) $resWhere .= " AND (s.full_name LIKE '%$resSearch%' OR s.reg_number LIKE '%$resSearch%' OR c.course_code LIKE '%$resSearch%')";
$results = $conn->query("SELECT r.*, s.full_name, s.reg_number, s.department, c.course_code, c.course_title FROM results r JOIN students s ON r.student_id=s.id JOIN courses c ON r.course_id=c.id $resWhere ORDER BY r.created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Results Manager — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="page-header"><h1>Results Manager</h1></div>

  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;margin-bottom:20px;background:white;padding:6px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <button class="tab-btn active" onclick="showTab('single',this)">📝 Single Entry</button>
    <button class="tab-btn" onclick="showTab('bulk',this)">📋 Bulk Upload by Department</button>
    <button class="tab-btn" onclick="showTab('view',this)">🔍 Search & View Results</button>
  </div>

  <!-- TAB: Single Entry -->
  <div id="tab-single" class="tab-panel">
    <div class="card">
      <div class="card-header"><h3>Enter / Update Single Result</h3></div>
      <div class="card-body">

        <!-- Student Search -->
        <div style="background:#f9fafb;border-radius:10px;padding:16px;margin-bottom:20px;">
          <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
            <input type="hidden" name="tab" value="single">
            <div style="flex:1;">
              <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">Search Student</label>
              <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                       class="form-control" style="padding-left:36px;"
                       placeholder="Name or Reg Number...">
              </div>
            </div>
            <div style="min-width:180px;">
              <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">Filter by Department</label>
              <select name="dept" class="form-control">
                <option value="">All Departments</option>
                <?php while($d = $departments->fetch_assoc()): ?>
                <option value="<?= $d['department'] ?>" <?= $dept===$d['department']?'selected':'' ?>><?= $d['department'] ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <a href="results.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
          </form>
        </div>

        <?php if ($search || $dept): ?>
        <!-- Show matching students to pick -->
        <div style="margin-bottom:20px;">
          <p style="font-size:13px;color:#6b7280;margin-bottom:10px;">
            <?= $students->num_rows ?> student(s) found — click a student to fill the form:
          </p>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php while($s = $students->fetch_assoc()): ?>
            <button type="button" class="btn btn-outline"
                    onclick="selectStudent(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name']) ?>', '<?= $s['reg_number'] ?>')">
              <?= htmlspecialchars($s['full_name']) ?> — <?= $s['reg_number'] ?>
            </button>
            <?php endwhile; ?>
          </div>
        </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="action" value="save_result">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Student <span style="color:red;">*</span></label>
              <select name="student_id" id="studentSelect" class="form-control" required>
                <option value="">Select Student</option>
                <?php
                $allStudents->data_seek(0);
                while($s = $allStudents->fetch_assoc()):
                ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> — <?= $s['reg_number'] ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Course <span style="color:red;">*</span></label>
              <select name="course_id" class="form-control" required>
                <option value="">Select Course</option>
                <?php
                $courses->data_seek(0);
                while($c = $courses->fetch_assoc()):
                ?>
                <option value="<?= $c['id'] ?>"><?= $c['course_code'] ?> — <?= htmlspecialchars($c['course_title']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Session</label>
              <input type="text" name="session" class="form-control" value="2024/2025" required>
            </div>
            <div class="form-group">
              <label>Semester</label>
              <select name="semester" class="form-control">
                <option value="First">First Semester</option>
                <option value="Second">Second Semester</option>
              </select>
            </div>
            <div class="form-group">
              <label>CA Score (max 30)</label>
              <input type="number" name="ca_score" class="form-control" min="0" max="30" step="0.5" required>
            </div>
            <div class="form-group">
              <label>Exam Score (max 70)</label>
              <input type="number" name="exam_score" class="form-control" min="0" max="70" step="0.5" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Result</button>
        </form>
      </div>
    </div>
  </div>

  <!-- TAB: Bulk Upload -->
  <div id="tab-bulk" class="tab-panel" style="display:none;">
    <div class="card">
      <div class="card-header"><h3>📋 Bulk Upload Results by Department</h3></div>
      <div class="card-body">
        <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:16px;margin-bottom:20px;">
          <strong style="color:#92400e;">📌 How to use Bulk Upload:</strong>
          <ol style="margin:10px 0 0 20px;color:#78350f;font-size:13px;line-height:1.8;">
            <li>Select the department, course, session and semester below</li>
            <li>In the text box, enter one student per line in this format:</li>
            <li style="list-style:none;background:#fff;padding:8px 12px;border-radius:6px;font-family:monospace;margin:4px 0;">
              AMUP/2024/001, 25, 55<br>
              AMUP/2024/002, 28, 62<br>
              AMUP/2024/003, 20, 48
            </li>
            <li>Format is: <strong>REG_NUMBER, CA_SCORE, EXAM_SCORE</strong></li>
            <li>CA max = 30, Exam max = 70</li>
            <li>Grade is auto-calculated</li>
          </ol>
        </div>

        <form method="POST">
          <input type="hidden" name="action" value="bulk_upload">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Department</label>
              <input type="text" name="bulk_department" class="form-control"
                     list="dept-list2" placeholder="Select department" required>
              <datalist id="dept-list2">
                <?php
                $conn->query("SELECT DISTINCT department FROM students ORDER BY department")->data_seek(0);
                $depts2 = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
                while($d = $depts2->fetch_assoc()):
                ?>
                <option value="<?= $d['department'] ?>">
                <?php endwhile; ?>
              </datalist>
            </div>
            <div class="form-group">
              <label>Course</label>
              <select name="bulk_course_id" class="form-control" required>
                <option value="">Select Course</option>
                <?php
                $courses->data_seek(0);
                while($c = $courses->fetch_assoc()):
                ?>
                <option value="<?= $c['id'] ?>"><?= $c['course_code'] ?> — <?= htmlspecialchars($c['course_title']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Session</label>
              <input type="text" name="bulk_session" class="form-control" value="2024/2025" required>
            </div>
            <div class="form-group">
              <label>Semester</label>
              <select name="bulk_semester" class="form-control">
                <option value="First">First Semester</option>
                <option value="Second">Second Semester</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Paste Result Data (REG_NUMBER, CA, EXAM — one per line)</label>
            <textarea name="bulk_data" class="form-control" rows="12"
                      placeholder="AMUP/2024/001, 25, 55&#10;AMUP/2024/002, 28, 62&#10;AMUP/2024/003, 20, 48&#10;..." 
                      style="font-family:monospace;font-size:13px;" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;">
            <i class="fas fa-upload"></i> Upload All Results
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- TAB: View Results -->
  <div id="tab-view" class="tab-panel" style="display:none;">
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body" style="padding:16px;">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
          <input type="hidden" name="tab" value="view">
          <div style="flex:1;">
            <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">Search Results</label>
            <div style="position:relative;">
              <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
              <input type="text" name="res_search" value="<?= htmlspecialchars($resSearch) ?>"
                     class="form-control" style="padding-left:36px;"
                     placeholder="Student name, reg number or course code...">
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
          <a href="results.php?tab=view" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Results</h3></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr><th>Student</th><th>Dept</th><th>Course</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Session</th></tr>
          </thead>
          <tbody>
            <?php while($r = $results->fetch_assoc()): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                <small style="color:#9ca3af;"><?= $r['reg_number'] ?></small>
              </td>
              <td style="font-size:12px;"><?= $r['department'] ?></td>
              <td><?= $r['course_code'] ?><br><small><?= htmlspecialchars($r['course_title']) ?></small></td>
              <td><?= $r['ca_score'] ?></td>
              <td><?= $r['exam_score'] ?></td>
              <td><strong><?= $r['total_score'] ?></strong></td>
              <td><span class="badge badge-<?= $r['grade']==='F'?'danger':'success' ?>"><?= $r['grade'] ?></span></td>
              <td><?= $r['session'] ?><br><small><?= $r['semester'] ?></small></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
.tab-btn {
  padding: 10px 18px; border: none; border-radius: 8px;
  cursor: pointer; font-size: 13px; font-weight: 600;
  background: transparent; color: #6b7280; transition: all 0.2s;
}
.tab-btn.active { background: #3d0a1a; color: white; }
.tab-btn:hover:not(.active) { background: #f3f4f6; }
</style>

<script>
function showTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).style.display = 'block';
  btn.classList.add('active');
}

function selectStudent(id, name, reg) {
  const sel = document.getElementById('studentSelect');
  for (let opt of sel.options) {
    if (opt.value == id) { sel.value = id; break; }
  }
}

// Auto-open tab from URL
const params = new URLSearchParams(window.location.search);
const tab = params.get('tab');
if (tab) {
  const btns = document.querySelectorAll('.tab-btn');
  const panels = ['single','bulk','view'];
  const idx = panels.indexOf(tab);
  if (idx >= 0) showTab(tab, btns[idx]);
}
</script>

</body>
</html>