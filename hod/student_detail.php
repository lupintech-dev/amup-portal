<?php
// hod/student_detail.php
require_once '../includes/config.php';
require_once 'auth.php';

$studentId = intval($_GET['id'] ?? 0);

// Fetch student — MUST be in HOD's department
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND department = ?");
$stmt->bind_param('is', $studentId, $hodDepartment);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { header('Location: dashboard.php'); exit; }

$saved   = $_GET['saved'] ?? '';
$errors  = [];

// ── Handle result amendment ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Edit existing result
    if ($_POST['action'] === 'edit_result') {
        $rid        = intval($_POST['result_id']);
        $ca         = floatval($_POST['ca_score']);
        $exam       = floatval($_POST['exam_score']);
        $grade      = trim($_POST['grade']);
        $gp         = floatval($_POST['grade_point']);
        $stmt2 = $conn->prepare("UPDATE results SET ca_score=?, exam_score=?, grade=?, grade_point=? WHERE id=? AND student_id=?");
        $stmt2->bind_param('ddsiii', $ca, $exam, $grade, $gp, $rid, $studentId);
        $stmt2->execute();
        header("Location: student_detail.php?id=$studentId&saved=result");
        exit;
    }

    // Add new result
    if ($_POST['action'] === 'add_result') {
        $courseId = intval($_POST['course_id']);
        $session  = trim($_POST['session']);
        $semester = trim($_POST['semester']);
        $ca       = floatval($_POST['ca_score']);
        $exam     = floatval($_POST['exam_score']);
        $grade    = trim($_POST['grade']);
        $gp       = floatval($_POST['grade_point']);
        $stmt2 = $conn->prepare("INSERT INTO results (student_id,course_id,session,semester,ca_score,exam_score,grade,grade_point) VALUES (?,?,?,?,?,?,?,?)");
        $stmt2->bind_param('iissddsд', $studentId, $courseId, $session, $semester, $ca, $exam, $grade, $gp);
        $stmt2->bind_param('iissddsd', $studentId, $courseId, $session, $semester, $ca, $exam, $grade, $gp);
        $stmt2->execute();
        header("Location: student_detail.php?id=$studentId&saved=result");
        exit;
    }

    // Update exam eligibility
    if ($_POST['action'] === 'set_eligibility') {
        $eligible = intval($_POST['eligible']); // 1 or 0
        $stmt2 = $conn->prepare("UPDATE students SET exam_eligible=? WHERE id=?");
        $stmt2->bind_param('ii', $eligible, $studentId);
        $stmt2->execute();
        header("Location: student_detail.php?id=$studentId&saved=eligibility");
        exit;
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT r.*, c.course_title, c.course_code, c.credit_units
    FROM results r
    JOIN courses c ON c.id = r.course_id
    WHERE r.student_id = ?
    ORDER BY r.semester DESC, c.course_code
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// All courses in this department for adding results
$stmt = $conn->prepare("SELECT * FROM courses WHERE department = ? ORDER BY level, course_code");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$allCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fee payments (view only)
$stmt = $conn->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalOwed = array_sum(array_column($payments, 'amount'));
$totalPaid = array_sum(array_column($payments, 'amount_paid'));
$balance   = $totalOwed - $totalPaid;

// Check if exam_eligible column exists, default to 1 if not
$examEligible = $student['exam_eligible'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($student['full_name']) ?> — HOD Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{--maroon:#6b0f2b;--maroon-d:#4a0a1e;--gold:#c9a84c;--bg:#f5f0f2;--white:#fff;--muted:#888;--radius:10px;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'DM Sans',sans-serif;background:var(--bg);color:#2d1a22;min-height:100vh;padding:28px 32px;}

  .back{display:inline-flex;align-items:center;gap:6px;color:var(--maroon);text-decoration:none;font-size:.875rem;font-weight:600;margin-bottom:20px;}
  .back:hover{text-decoration:underline;}

  .alert-success{background:#e6f9ee;border:1px solid #a3e4b5;color:#1a7a40;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.88rem;}

  .profile-card{background:var(--white);border-radius:var(--radius);padding:28px;display:flex;gap:24px;align-items:flex-start;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
  .avatar{width:72px;height:72px;border-radius:50%;background:var(--maroon);color:var(--white);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;flex-shrink:0;}
  .profile-info h1{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--maroon-d);}
  .profile-info .reg{color:var(--muted);font-size:.85rem;margin-top:2px;}
  .profile-meta{display:flex;gap:20px;flex-wrap:wrap;margin-top:14px;}
  .meta-item{font-size:.82rem;}
  .meta-item strong{color:var(--maroon-d);display:block;font-size:.95rem;}

  .badges-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}
  .badge{display:inline-block;padding:4px 12px;border-radius:12px;font-size:.78rem;font-weight:600;}
  .badge-active{background:#e6f9ee;color:#1a7a40;}
  .badge-suspended{background:#fff3cd;color:#856404;}
  .badge-graduated{background:#e0f0ff;color:#1a5a9a;}
  .badge-inactive{background:#fde8e8;color:#c0392b;}
  .badge-eligible{background:#e6f9ee;color:#1a7a40;}
  .badge-ineligible{background:#fde8e8;color:#c0392b;}
  .badge-paid{background:#e6f9ee;color:#1a7a40;}
  .badge-partial{background:#fff3cd;color:#856404;}
  .badge-unpaid{background:#fde8e8;color:#c0392b;}

  .fee-summary{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;}
  .fee-box{background:var(--white);border-radius:var(--radius);padding:18px 24px;flex:1;min-width:150px;box-shadow:0 1px 4px rgba(0,0,0,.06);border-top:3px solid var(--maroon);}
  .fee-box.success{border-top-color:#27ae60;}
  .fee-box.danger{border-top-color:#e74c3c;}
  .fee-box .fb-val{font-size:1.2rem;font-weight:700;color:var(--maroon-d);}
  .fee-box .fb-lbl{font-size:.75rem;color:var(--muted);margin-top:3px;}

  .section{background:var(--white);border-radius:var(--radius);box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:20px;overflow:hidden;}
  .section-header{padding:16px 22px;border-bottom:1px solid #f0e8eb;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
  .section-title{font-size:.95rem;font-weight:600;color:var(--maroon-d);}

  table{width:100%;border-collapse:collapse;}
  thead tr{background:var(--maroon-d);}
  thead th{padding:10px 14px;text-align:left;font-size:.73rem;font-weight:600;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.04em;}
  tbody tr{border-bottom:1px solid #f5edf0;}
  tbody tr:last-child{border-bottom:none;}
  tbody tr:hover{background:#fdf7f9;}
  tbody td{padding:10px 14px;font-size:.85rem;}

  .btn{padding:6px 14px;border-radius:7px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;font-family:'DM Sans',sans-serif;transition:background .2s;}
  .btn-maroon{background:var(--maroon);color:#fff;} .btn-maroon:hover{background:var(--maroon-d);}
  .btn-green{background:#27ae60;color:#fff;} .btn-green:hover{background:#1e8449;}
  .btn-red{background:#e74c3c;color:#fff;} .btn-red:hover{background:#c0392b;}
  .btn-sm{padding:4px 10px;font-size:.75rem;}

  .empty{padding:30px;text-align:center;color:var(--muted);font-size:.875rem;}

  /* Modal */
  .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;}
  .modal-overlay.open{display:flex;}
  .modal{background:#fff;border-radius:12px;padding:28px;width:100%;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,.2);}
  .modal h3{font-family:'Playfair Display',serif;color:var(--maroon-d);margin-bottom:18px;font-size:1.1rem;}
  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
  .form-group{margin-bottom:12px;}
  .form-group label{display:block;font-size:.78rem;font-weight:600;color:#555;margin-bottom:4px;text-transform:uppercase;}
  .form-group input,.form-group select{width:100%;padding:9px 12px;border:1.5px solid #ddd;border-radius:8px;font-size:.88rem;font-family:'DM Sans',sans-serif;outline:none;}
  .form-group input:focus,.form-group select:focus{border-color:var(--maroon);}
  .modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:16px;}
  .btn-cancel{background:#f0e8eb;color:var(--maroon);} .btn-cancel:hover{background:#e0d0d5;}

  .eligibility-box{padding:18px 22px;}
  .elig-info{display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
  .elig-status{font-size:1rem;font-weight:600;}
</style>
</head>
<body>

<a href="dashboard.php" class="back">← Back to Dashboard</a>

<?php if ($saved): ?>
<div class="alert-success">✅ <?= $saved === 'result' ? 'Result updated successfully.' : 'Exam eligibility updated successfully.' ?></div>
<?php endif; ?>

<!-- Profile -->
<div class="profile-card">
  <div class="avatar"><?= strtoupper(substr($student['full_name'], 0, 1)) ?></div>
  <div class="profile-info">
    <h1><?= htmlspecialchars($student['full_name']) ?></h1>
    <div class="reg"><?= htmlspecialchars($student['reg_number']) ?></div>
    <div class="profile-meta">
      <div class="meta-item"><strong><?= htmlspecialchars($student['department']) ?></strong>Department</div>
      <div class="meta-item"><strong><?= htmlspecialchars($student['level']) ?></strong>Level</div>
      <div class="meta-item"><strong><?= htmlspecialchars($student['email']) ?></strong>Email</div>
      <?php if (!empty($student['phone'])): ?>
      <div class="meta-item"><strong><?= htmlspecialchars($student['phone']) ?></strong>Phone</div>
      <?php endif; ?>
    </div>
    <div class="badges-row">
      <span class="badge badge-<?= $student['status'] ?>"><?= ucfirst($student['status']) ?></span>
      <?php if ($student['status'] === 'suspended'): ?>
        <span class="badge" style="background:#fff3cd;color:#856404;">
          Suspended <?= !empty($student['suspension_date']) ? date('d M Y', strtotime($student['suspension_date'])) : '' ?>
        </span>
        <?php if (!empty($student['resumption_date'])): ?>
        <span class="badge" style="background:#e0f0ff;color:#1a5a9a;">
          Resumes <?= date('d M Y', strtotime($student['resumption_date'])) ?>
        </span>
        <?php endif; ?>
      <?php endif; ?>
      <span class="badge <?= $examEligible ? 'badge-eligible' : 'badge-ineligible' ?>">
        <?= $examEligible ? '✅ Eligible for Exams' : '❌ Not Eligible for Exams' ?>
      </span>
    </div>
  </div>
</div>

<!-- Exam Eligibility Control -->
<div class="section">
  <div class="section-header">
    <div class="section-title">🎓 Exam Eligibility</div>
  </div>
  <div class="eligibility-box">
    <div class="elig-info">
      <div class="elig-status">
        Current Status:
        <span class="badge <?= $examEligible ? 'badge-eligible' : 'badge-ineligible' ?>">
          <?= $examEligible ? 'Eligible' : 'Not Eligible' ?>
        </span>
      </div>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="set_eligibility">
        <?php if ($examEligible): ?>
          <input type="hidden" name="eligible" value="0">
          <button type="submit" class="btn btn-red" onclick="return confirm('Mark this student as NOT eligible for exams?')">
            ❌ Mark as Not Eligible
          </button>
        <?php else: ?>
          <input type="hidden" name="eligible" value="1">
          <button type="submit" class="btn btn-green" onclick="return confirm('Mark this student as eligible for exams?')">
            ✅ Mark as Eligible
          </button>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<!-- Fee Summary (view only) -->
<div class="fee-summary">
  <div class="fee-box"><div class="fb-val">₦<?= number_format($totalOwed, 2) ?></div><div class="fb-lbl">Total Billed</div></div>
  <div class="fee-box success"><div class="fb-val">₦<?= number_format($totalPaid, 2) ?></div><div class="fb-lbl">Total Paid</div></div>
  <div class="fee-box danger"><div class="fb-val">₦<?= number_format($balance, 2) ?></div><div class="fb-lbl">Outstanding Balance</div></div>
</div>

<!-- Fee Payments (view only) -->
<div class="section">
  <div class="section-header">
    <div class="section-title">💰 Fee Status (View Only)</div>
  </div>
  <?php if ($payments): ?>
  <table>
    <thead><tr><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due Date</th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['fee_type']) ?></td>
        <td>₦<?= number_format($p['amount'], 2) ?></td>
        <td>₦<?= number_format($p['amount_paid'], 2) ?></td>
        <td>₦<?= number_format($p['amount'] - $p['amount_paid'], 2) ?></td>
        <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
        <td><?= !empty($p['due_date']) ? date('d M Y', strtotime($p['due_date'])) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty">No payment records yet.</div>
  <?php endif; ?>
</div>

<!-- Academic Results -->
<div class="section">
  <div class="section-header">
    <div class="section-title">📊 Academic Results</div>
    <button class="btn btn-maroon btn-sm" onclick="openModal('addModal')">+ Add Result</button>
  </div>
  <?php if ($results): ?>
  <table>
    <thead>
      <tr><th>Code</th><th>Course</th><th>Semester</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>GP</th><th>Edit</th></tr>
    </thead>
    <tbody>
      <?php foreach ($results as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['course_code']) ?></td>
        <td><?= htmlspecialchars($r['course_title']) ?></td>
        <td><?= htmlspecialchars($r['semester']) ?></td>
        <td><?= $r['ca_score'] ?></td>
        <td><?= $r['exam_score'] ?></td>
        <td><?= $r['total_score'] ?></td>
        <td><strong><?= $r['grade'] ?? '—' ?></strong></td>
        <td><?= $r['grade_point'] ?? '—' ?></td>
        <td>
          <button class="btn btn-sm" style="background:#f0e8eb;color:var(--maroon);"
            onclick="openEdit(<?= $r['id'] ?>, <?= $r['ca_score'] ?>, <?= $r['exam_score'] ?>, '<?= $r['grade'] ?>', <?= $r['grade_point'] ?>)">
            ✏️ Edit
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty">No results recorded yet. Click "Add Result" to add one.</div>
  <?php endif; ?>
</div>

<!-- Edit Result Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h3>✏️ Edit Result</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit_result">
      <input type="hidden" name="result_id" id="edit_result_id">
      <div class="form-row">
        <div class="form-group">
          <label>CA Score (max 40)</label>
          <input type="number" name="ca_score" id="edit_ca" step="0.01" min="0" max="40" required>
        </div>
        <div class="form-group">
          <label>Exam Score (max 60)</label>
          <input type="number" name="exam_score" id="edit_exam" step="0.01" min="0" max="60" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Grade</label>
          <select name="grade" id="edit_grade">
            <option>A</option><option>B</option><option>C</option><option>D</option><option>E</option><option>F</option>
          </select>
        </div>
        <div class="form-group">
          <label>Grade Point</label>
          <select name="grade_point" id="edit_gp">
            <option value="5.0">5.0</option><option value="4.0">4.0</option>
            <option value="3.0">3.0</option><option value="2.0">2.0</option>
            <option value="1.0">1.0</option><option value="0.0">0.0</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-maroon">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Result Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <h3>➕ Add New Result</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_result">
      <div class="form-group">
        <label>Course</label>
        <select name="course_id" required>
          <option value="">Select Course</option>
          <?php foreach ($allCourses as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'].' — '.$c['course_title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Session</label>
          <input type="text" name="session" placeholder="e.g. 2024/2025" value="2024/2025" required>
        </div>
        <div class="form-group">
          <label>Semester</label>
          <select name="semester">
            <option value="First">First</option>
            <option value="Second">Second</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>CA Score</label>
          <input type="number" name="ca_score" step="0.01" min="0" max="40" required>
        </div>
        <div class="form-group">
          <label>Exam Score</label>
          <input type="number" name="exam_score" step="0.01" min="0" max="60" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Grade</label>
          <select name="grade">
            <option>A</option><option>B</option><option>C</option><option>D</option><option>E</option><option>F</option>
          </select>
        </div>
        <div class="form-group">
          <label>Grade Point</label>
          <select name="grade_point">
            <option value="5.0">5.0</option><option value="4.0">4.0</option>
            <option value="3.0">3.0</option><option value="2.0">2.0</option>
            <option value="1.0">1.0</option><option value="0.0">0.0</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-maroon">Add Result</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openEdit(id, ca, exam, grade, gp) {
  document.getElementById('edit_result_id').value = id;
  document.getElementById('edit_ca').value = ca;
  document.getElementById('edit_exam').value = exam;
  document.getElementById('edit_grade').value = grade;
  document.getElementById('edit_gp').value = gp;
  openModal('editModal');
}
// Close modal on backdrop click
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});
</script>
</body>
</html>