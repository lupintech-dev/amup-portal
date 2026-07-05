<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Courses';

$success = ''; $error = '';

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $course_code  = strtoupper(sanitize($conn, $_POST['course_code'] ?? ''));
    $course_title = sanitize($conn, $_POST['course_title'] ?? '');
    $credit_units = (int)$_POST['credit_units'];
    $department   = sanitize($conn, $_POST['department'] ?? '');
    $level        = sanitize($conn, $_POST['level'] ?? '100L');
    $semester     = sanitize($conn, $_POST['semester'] ?? 'First');

    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_title, credit_units, department, level, semester) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssisss', $course_code, $course_title, $credit_units, $department, $level, $semester);
    $stmt->execute() ? $success = 'Course added successfully!' : $error = 'Failed. Course code may already exist.';
}

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_dept') {
    $dname    = sanitize($conn, $_POST['dept_name'] ?? '');
    $dfaculty = sanitize($conn, $_POST['dept_faculty'] ?? '');
    $ddegree  = sanitize($conn, $_POST['dept_degree'] ?? 'B.Sc');
    $ddur     = (int)($_POST['dept_duration'] ?? 4);
    $stmt = $conn->prepare("INSERT INTO departments (name, faculty, degree, duration_years) VALUES (?,?,?,?)");
    $stmt->bind_param('sssi', $dname, $dfaculty, $ddegree, $ddur);
    $stmt->execute() ? $success = 'Department added!' : $error = 'Failed. Department may already exist.';
}

// Handle Edit Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_dept') {
    $did   = (int)$_POST['dept_id'];
    $ddeg  = sanitize($conn, $_POST['dept_degree'] ?? 'B.Sc');
    $ddur  = (int)($_POST['dept_duration'] ?? 4);
    $dfac  = sanitize($conn, $_POST['dept_faculty'] ?? '');
    $conn->query("UPDATE departments SET degree='$ddeg', duration_years=$ddur, faculty='$dfac' WHERE id=$did");
    $success = 'Department updated!';
}

// Handle deletes
if (isset($_GET['delete_course'])) {
    $conn->query("DELETE FROM courses WHERE id=".(int)$_GET['delete_course']);
    redirect('courses.php');
}
if (isset($_GET['delete_dept'])) {
    $conn->query("DELETE FROM departments WHERE id=".(int)$_GET['delete_dept']);
    redirect('courses.php');
}

$courses = $conn->query("SELECT * FROM courses ORDER BY department, level, course_code");
$depts   = $conn->query("SELECT * FROM departments ORDER BY faculty, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courses — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.tab-btn { padding:10px 18px;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;background:transparent;color:#6b7280;transition:all 0.2s; }
.tab-btn.active { background:#3d0a1a;color:white; }
.tab-btn:hover:not(.active) { background:#f3f4f6; }
.dur-badge { display:inline-block;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700; }
.dur-4 { background:#dbeafe;color:#1d4ed8; }
.dur-5 { background:#fef9c3;color:#92400e; }
.dur-6 { background:#fce7f3;color:#be185d; }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="page-header"><h1>Courses & Departments</h1><p>Manage university courses and department durations</p></div>

  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>

  <!-- Page Tabs -->
  <div style="display:flex;gap:4px;margin-bottom:24px;background:white;padding:6px;
              border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <button class="tab-btn active" onclick="showTab('courses',this)">
      <i class="fas fa-book"></i> Courses
    </button>
    <button class="tab-btn" onclick="showTab('departments',this)">
      <i class="fas fa-building"></i> Departments & Durations
    </button>
  </div>

  <!-- ══════════════════════════════════════════════ -->
  <!-- TAB: COURSES -->
  <!-- ══════════════════════════════════════════════ -->
  <div id="tab-courses">

    <!-- Add Course Form -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3><i class="fas fa-plus"></i> Add New Course</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_course">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Course Code</label>
              <input type="text" name="course_code" class="form-control"
                     placeholder="e.g. CSC 101" required>
            </div>
            <div class="form-group">
              <label>Credit Units</label>
              <input type="number" name="credit_units" class="form-control"
                     min="1" max="6" value="3" required>
            </div>
          </div>
          <div class="form-group">
            <label>Course Title</label>
            <input type="text" name="course_title" class="form-control"
                   placeholder="e.g. Introduction to Computer Science" required>
          </div>
          <div class="form-group">
            <label>Department</label>
            <select name="department" class="form-control" required>
              <option value="">— Select Department —</option>
              <?php
              $dlist = $conn->query("SELECT name FROM departments ORDER BY name");
              while($d = $dlist->fetch_assoc()):
              ?>
              <option value="<?= htmlspecialchars($d['name']) ?>">
                <?= htmlspecialchars($d['name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Level</label>
              <select name="level" class="form-control">
                <option value="100L">100L</option>
                <option value="200L">200L</option>
                <option value="300L">300L</option>
                <option value="400L">400L</option>
                <option value="500L">500L</option>
                <option value="600L">600L</option>
              </select>
            </div>
            <div class="form-group">
              <label>Semester</label>
              <select name="semester" class="form-control">
                <option value="First">First Semester</option>
                <option value="Second">Second Semester</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Course
          </button>
        </form>
      </div>
    </div>

    <!-- Search -->
    <div style="margin-bottom:16px;">
      <input type="text" id="searchBox" class="form-control"
             placeholder="🔍 Search by name, code or department..."
             onkeyup="filterTable()" style="max-width:420px;">
    </div>

    <!-- Courses Table -->
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><i class="fas fa-book"></i> All Courses</h3>
        <span style="font-size:13px;color:#6b7280;"><?= $courses->num_rows ?> course(s)</span>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table" id="coursesTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Title</th>
              <th>Units</th>
              <th>Department</th>
              <th>Level</th>
              <th>Semester</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; while($c = $courses->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong style="color:#3d0a1a;"><?= htmlspecialchars($c['course_code']) ?></strong></td>
              <td><?= htmlspecialchars($c['course_title']) ?></td>
              <td style="text-align:center;">
                <span class="badge badge-info"><?= $c['credit_units'] ?> units</span>
              </td>
              <td><?= htmlspecialchars($c['department']) ?></td>
              <td><?= $c['level'] ?></td>
              <td><?= $c['semester'] ?></td>
              <td>
                <a href="courses.php?delete_course=<?= $c['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete <?= htmlspecialchars($c['course_code']) ?>?')">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- end tab-courses -->

  <!-- ══════════════════════════════════════════════ -->
  <!-- TAB: DEPARTMENTS -->
  <!-- ══════════════════════════════════════════════ -->
  <div id="tab-departments" style="display:none;">

    <!-- Add Department Form -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3><i class="fas fa-plus"></i> Add New Department</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_dept">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Department Name</label>
              <input type="text" name="dept_name" class="form-control"
                     placeholder="e.g. Computer Science" required>
            </div>
            <div class="form-group">
              <label>Faculty</label>
              <input type="text" name="dept_faculty" class="form-control"
                     placeholder="e.g. Faculty of Basic Sciences"
                     list="faculty-list">
              <datalist id="faculty-list">
                <option value="Faculty of Social and Management Sciences">
                <option value="Faculty of Health Science">
                <option value="Faculty of Law">
                <option value="Faculty of Basic Sciences">
                <option value="College of Medicine">
              </datalist>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Degree Awarded</label>
              <select name="dept_degree" class="form-control">
                <option value="B.Sc">B.Sc</option>
                <option value="LLB">LLB</option>
                <option value="MBBS">MBBS</option>
                <option value="B.Eng">B.Eng</option>
                <option value="B.A">B.A</option>
                <option value="B.Tech">B.Tech</option>
              </select>
            </div>
            <div class="form-group">
              <label>Programme Duration (Years)</label>
              <select name="dept_duration" class="form-control">
                <option value="4">4 Years</option>
                <option value="5">5 Years</option>
                <option value="6">6 Years</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Department
          </button>
        </form>
      </div>
    </div>

    <!-- Departments Table -->
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><i class="fas fa-building"></i> All Departments</h3>
        <span style="font-size:13px;color:#6b7280;"><?= $depts->num_rows ?> department(s)</span>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Department</th>
              <th>Faculty</th>
              <th>Degree</th>
              <th>Duration</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; while($d = $depts->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
              <td style="font-size:13px;color:#6b7280;"><?= htmlspecialchars($d['faculty']) ?></td>
              <td><span class="badge badge-info"><?= $d['degree'] ?></span></td>
              <td>
                <span class="dur-badge dur-<?= $d['duration_years'] ?>">
                  <?= $d['duration_years'] ?> yrs
                </span>
              </td>
              <td style="display:flex;gap:6px;">
                <button onclick="openEditDept(<?= $d['id'] ?>,'<?= addslashes($d['faculty']) ?>','<?= $d['degree'] ?>',<?= $d['duration_years'] ?>)"
                        class="btn btn-sm btn-outline">
                  <i class="fas fa-edit"></i>
                </button>
                <a href="courses.php?delete_dept=<?= $d['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete <?= htmlspecialchars($d['name']) ?>?')">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- end tab-departments -->

</div><!-- end main-content -->

<!-- Edit Department Modal -->
<div id="editDeptModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);
     z-index:9999;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:440px;margin:20px;">
    <h3 style="margin-bottom:20px;color:#3d0a1a;"><i class="fas fa-edit"></i> Edit Department</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit_dept">
      <input type="hidden" name="dept_id" id="editDeptId">
      <div class="form-group">
        <label>Faculty</label>
        <input type="text" name="dept_faculty" id="editDeptFaculty" class="form-control"
               list="faculty-list2">
        <datalist id="faculty-list2">
          <option value="Faculty of Social and Management Sciences">
          <option value="Faculty of Health Science">
          <option value="Faculty of Law">
          <option value="Faculty of Basic Sciences">
          <option value="College of Medicine">
        </datalist>
      </div>
      <div class="form-group">
        <label>Degree Awarded</label>
        <select name="dept_degree" id="editDeptDegree" class="form-control">
          <option value="B.Sc">B.Sc</option>
          <option value="LLB">LLB</option>
          <option value="MBBS">MBBS</option>
          <option value="B.Eng">B.Eng</option>
          <option value="B.A">B.A</option>
          <option value="B.Tech">B.Tech</option>
        </select>
      </div>
      <div class="form-group">
        <label>Programme Duration (Years)</label>
        <select name="dept_duration" id="editDeptDur" class="form-control">
          <option value="4">4 Years</option>
          <option value="5">5 Years</option>
          <option value="6">6 Years</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;margin-top:16px;">
        <button type="submit" class="btn btn-primary" style="flex:1;">
          <i class="fas fa-save"></i> Save Changes
        </button>
        <button type="button" class="btn btn-outline" onclick="closeEditDept()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showTab(name, btn) {
  document.getElementById('tab-courses').style.display     = name==='courses'     ? 'block' : 'none';
  document.getElementById('tab-departments').style.display = name==='departments' ? 'block' : 'none';
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function filterTable() {
  const input = document.getElementById('searchBox').value.toLowerCase();
  document.querySelectorAll('#coursesTable tbody tr').forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
  });
}

function openEditDept(id, faculty, degree, duration) {
  document.getElementById('editDeptId').value       = id;
  document.getElementById('editDeptFaculty').value  = faculty;
  document.getElementById('editDeptDegree').value   = degree;
  document.getElementById('editDeptDur').value      = duration;
  document.getElementById('editDeptModal').style.display = 'flex';
}
function closeEditDept() {
  document.getElementById('editDeptModal').style.display = 'none';
}
</script>

</body>
</html>