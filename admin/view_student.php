<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('students.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_photo') {
        $student = $conn->query("SELECT * FROM students WHERE id=$id")->fetch_assoc();
        $photoError = '';
        if (empty($_FILES['photo']['name'])) {
            $photoError = 'Please select a photo to upload.';
        } else {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed))
                $photoError = 'Photo must be JPG, PNG or WEBP.';
            elseif ($_FILES['photo']['size'] > 2*1024*1024)
                $photoError = 'Photo must be under 2MB.';
            else {
                $old = $student['photo'] ?? '';
                if ($old && file_exists("../assets/uploads/students/$old"))
                    unlink("../assets/uploads/students/$old");
                $reg      = strtolower(str_replace('/', '-', $student['reg_number']));
                $filename = $reg . '_' . time() . '.' . $ext;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/uploads/students/$filename"))
                    $photoError = 'Failed to upload photo. Check folder permissions.';
                else
                    $conn->query("UPDATE students SET photo='$filename' WHERE id=$id");
            }
        }
        $_SESSION['photo_msg']  = $photoError ?: 'Photo updated successfully!';
        $_SESSION['photo_type'] = $photoError ? 'danger' : 'success';
        redirect("view_student.php?id=$id");
    }

    if ($action === 'update_status') {
        $status = sanitize($conn, $_POST['status']);

        if ($status === 'graduated') {
            $grad_session = sanitize($conn, $_POST['graduation_session'] ?? '');
            $now = date('Y-m-d H:i:s');
            $conn->query("UPDATE students SET status='graduated', graduation_session='$grad_session', graduated_at='$now' WHERE id=$id");
        } else {
            $reason          = sanitize($conn, $_POST['suspension_reason'] ?? '');
            $suspension_date = sanitize($conn, $_POST['suspension_date'] ?? '');
            $resumption_date = sanitize($conn, $_POST['resumption_date'] ?? '');
            $conn->query("UPDATE students SET status='$status', suspension_reason='$reason', suspension_date='$suspension_date', resumption_date='$resumption_date' WHERE id=$id");
        }

        $statusMsg = [
            'suspended' => ['Account Suspended', "Your account has been suspended. Reason: ".sanitize($conn, $_POST['suspension_reason'] ?? ''), 'danger'],
            'active'    => ['Account Activated',  'Your account has been reactivated. Welcome back!', 'success'],
            'graduated' => ['Congratulations! 🎓', 'You have been marked as graduated! Congratulations on completing your programme.', 'success'],
        ];
        if (isset($statusMsg[$status])) {
            [$title, $msg, $type] = $statusMsg[$status];
            $conn->query("INSERT INTO notifications (student_id, title, message, type) VALUES ($id, '$title', '$msg', '$type')");
        }
    }

    if ($action === 'send_notification') {
        $title   = sanitize($conn, $_POST['notif_title']);
        $message = sanitize($conn, $_POST['notif_message']);
        $type    = sanitize($conn, $_POST['notif_type']);
        $conn->query("INSERT INTO notifications (student_id, title, message, type) VALUES ($id, '$title', '$message', '$type')");
    }

    if ($action === 'update_fee') {
        $fee_id      = (int)$_POST['fee_id'];
        $amount_paid = (float)$_POST['amount_paid'];
        $remark      = sanitize($conn, $_POST['remark'] ?? '');
        $fee         = $conn->query("SELECT amount FROM fees WHERE id=$fee_id AND student_id=$id")->fetch_assoc();
        if ($fee) {
            $status        = $amount_paid >= $fee['amount'] ? 'paid' : ($amount_paid > 0 ? 'partial' : 'unpaid');
            $paid_date_sql = $amount_paid > 0 ? "'".date('Y-m-d')."'" : 'NULL';
            $conn->query("UPDATE fees SET amount_paid=$amount_paid, status='$status', paid_date=$paid_date_sql, remark='$remark' WHERE id=$fee_id AND student_id=$id");
            if ($status === 'paid')
                $conn->query("INSERT INTO notifications (student_id, title, message, type) VALUES ($id, '✅ Fee Payment Confirmed', 'Your fee payment has been confirmed and updated by the admin. Your account is now cleared.', 'success')");
        }
    }

    redirect("view_student.php?id=$id&saved=1");
}

// ── Get data ──────────────────────────────────────────────────────
$student = $conn->query("SELECT * FROM students WHERE id=$id")->fetch_assoc();
if (!$student) redirect('students.php');
$pageTitle  = 'Student Profile';
$results    = $conn->query("SELECT r.*, c.course_code, c.course_title, c.credit_units FROM results r JOIN courses c ON r.course_id=c.id WHERE r.student_id=$id ORDER BY r.session, r.semester");
$resultRows = [];
while ($r = $results->fetch_assoc()) $resultRows[] = $r;
$gpa        = computeGPA($resultRows);
$fees       = $conn->query("SELECT * FROM fees WHERE student_id=$id ORDER BY created_at DESC");
$feeRows    = [];
while ($f = $fees->fetch_assoc()) $feeRows[] = $f;
$notifs     = $conn->query("SELECT * FROM notifications WHERE student_id=$id ORDER BY created_at DESC LIMIT 10");
$totalOwed  = array_sum(array_column($feeRows, 'amount'));
$totalPaid  = array_sum(array_column($feeRows, 'amount_paid'));
$balance    = $totalOwed - $totalPaid;
$feeYears   = array_unique(array_column($feeRows, 'session'));
rsort($feeYears);

// Get department info for duration-based graduation sessions
$deptInfo     = $conn->query("SELECT * FROM departments WHERE name='".$conn->real_escape_string($student['department'])."' LIMIT 1")->fetch_assoc();
$deptDuration = $deptInfo['duration_years'] ?? 4;
$deptDegree   = $deptInfo['degree'] ?? 'B.Sc';

// Build graduation sessions based on dept duration
$autoSessions = [];
for ($y = 2021; $y <= (int)date('Y'); $y++) {
    $autoSessions[] = $y . '-' . ($y + $deptDuration);
}

$photoMsg  = $_SESSION['photo_msg']  ?? '';
$photoType = $_SESSION['photo_type'] ?? '';
unset($_SESSION['photo_msg'], $_SESSION['photo_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Profile — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.tab-btn {
  padding:10px 16px; border:none; border-radius:8px; cursor:pointer;
  font-size:13px; font-weight:600; background:transparent;
  color:#6b7280; transition:all 0.2s;
}
.tab-btn.active { background:#3d0a1a; color:white; }
.tab-btn:hover:not(.active) { background:#f3f4f6; }

.fee-subtab-btn {
  padding:7px 18px; border:none; border-radius:6px; cursor:pointer;
  font-size:13px; font-weight:600; background:transparent;
  color:#6b7280; transition:all 0.2s;
}
.fee-subtab-btn.active { background:#7B1C3E; color:white; }
.fee-subtab-btn:hover:not(.active) { background:#f3f4f6; }

.photo-upload-area {
  display:flex; align-items:center; gap:24px; padding:20px;
  background:#f9fafb; border-radius:12px; border:1.5px solid #e5e7eb;
  margin-bottom:20px;
}
.photo-circle {
  width:90px; height:90px; border-radius:50%; flex-shrink:0;
  object-fit:cover; border:3px solid #7B1C3E;
}
.photo-initials-circle {
  width:90px; height:90px; border-radius:50%; flex-shrink:0;
  background:rgba(255,255,255,0.2); border:3px solid rgba(255,255,255,0.5);
  display:flex; align-items:center; justify-content:center;
  font-size:2.5rem; font-weight:700; color:white;
}
.photo-upload-btn {
  display:inline-block; padding:8px 18px; background:#7B1C3E; color:#fff;
  border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;
  position:relative; overflow:hidden; border:none;
}
.photo-upload-btn input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.photo-upload-btn:hover { background:#550f28; }

.id-card {
  width:323px; border-radius:10px; overflow:hidden; border:1.5px solid #bbb;
  background:#fff; font-family:'Source Sans 3',Arial,sans-serif;
  display:flex; flex-direction:column;
  box-shadow:0 4px 20px rgba(0,0,0,0.15);
  -webkit-print-color-adjust:exact; print-color-adjust:exact;
}
.id-card-header {
  background:#1565C0; color:#fff; padding:8px 12px;
  display:flex; align-items:center; gap:10px;
  -webkit-print-color-adjust:exact; print-color-adjust:exact;
}
.id-card-footer-bar {
  background:#1565C0; color:#fff; text-align:center;
  font-size:10.5px; font-weight:700; padding:6px;
  letter-spacing:1.5px; text-transform:uppercase;
  -webkit-print-color-adjust:exact; print-color-adjust:exact;
}

@media print {
  body > * { display:none !important; }
  #print-cards-only { display:flex !important; }
  #print-cards-only {
    position:fixed; top:0; left:0; width:100vw;
    flex-direction:column; align-items:center;
    justify-content:center; gap:8mm; padding:5mm; background:#fff;
  }
  .id-card { width:85.6mm; box-shadow:none; border-radius:3mm; page-break-inside:avoid; }
  * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; color-adjust:exact !important; }
}
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">

  <?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> Changes saved successfully!</div>
  <?php endif; ?>
  <?php if ($photoMsg): ?>
  <div class="alert alert-<?= $photoType ?>">
    <i class="fas fa-<?= $photoType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($photoMsg) ?>
  </div>
  <?php endif; ?>

  <div style="margin-bottom:16px;">
    <a href="students.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Students</a>
    <?php if ($student['status'] === 'graduated'): ?>
    <a href="graduated_students.php" class="btn btn-outline" style="margin-left:8px;">
      <i class="fas fa-graduation-cap"></i> View in Graduated List
    </a>
    <?php endif; ?>
  </div>

  <!-- ── Profile Header ── -->
  <div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,#3d0a1a,#7B1C3E);color:white;">
    <div class="card-body" style="display:flex;align-items:center;gap:24px;padding:28px;">
      <div style="position:relative;flex-shrink:0;">
        <?php if (!empty($student['photo'])): ?>
          <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($student['photo']) ?>"
               alt="Profile Photo" id="headerPreview" class="photo-circle"
               style="border:3px solid rgba(255,255,255,0.6);">
        <?php else: ?>
          <div class="photo-initials-circle" id="headerInitials">
            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
          </div>
          <img id="headerPreview" class="photo-circle"
               style="display:none;border:3px solid rgba(255,255,255,0.6);" src="" alt="">
        <?php endif; ?>
        <label for="headerPhotoInput" title="Update student photo"
               style="position:absolute;bottom:0;right:0;width:28px;height:28px;
                      background:#fff;border-radius:50%;display:flex;align-items:center;
                      justify-content:center;cursor:pointer;border:2px solid #7B1C3E;">
          <i class="fas fa-camera" style="font-size:12px;color:#7B1C3E;"></i>
        </label>
      </div>
      <div style="flex:1;">
        <h2 style="font-size:1.6rem;font-weight:700;margin-bottom:4px;color:white;">
          <?= htmlspecialchars($student['full_name']) ?>
        </h2>
        <p style="color:rgba(255,255,255,0.7);font-size:14px;margin-bottom:8px;">
          <?= $student['reg_number'] ?> &bull; <?= $student['department'] ?> &bull; <?= $student['level'] ?>
        </p>
        <span style="background:rgba(255,255,255,0.2);padding:4px 14px;border-radius:20px;font-size:13px;">
          <?= ucfirst($student['status']) ?>
        </span>
        <?php if ($student['status']==='suspended' && $student['suspension_reason']): ?>
        <span style="margin-left:8px;background:rgba(220,38,38,0.3);padding:4px 14px;border-radius:20px;font-size:13px;">
          ⚠️ <?= htmlspecialchars($student['suspension_reason']) ?>
        </span>
        <?php endif; ?>
        <?php if ($student['status']==='graduated' && !empty($student['graduation_session'])): ?>
        <span style="margin-left:8px;background:rgba(22,163,74,0.35);padding:4px 14px;border-radius:20px;font-size:13px;">
          🎓 Class of <?= htmlspecialchars($student['graduation_session']) ?>
        </span>
        <?php endif; ?>
      </div>
      <div style="text-align:right;">
        <div style="font-size:2.5rem;font-weight:700;color:#F0D060;"><?= $gpa ?></div>
        <div style="font-size:12px;color:rgba(255,255,255,0.6);">Current GPA</div>
        <div style="margin-top:8px;font-size:14px;color:<?= $balance>0?'#fca5a5':'#86efac' ?>;">
          <?= $balance>0 ? '⚠️ Owes '.formatMoney($balance) : '✅ Fees Cleared' ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden photo upload form -->
  <form id="photoUploadForm" method="POST" enctype="multipart/form-data" style="display:none;">
    <input type="hidden" name="action" value="update_photo">
    <input type="file" name="photo" id="headerPhotoInput" accept="image/*">
  </form>

  <!-- ── Tabs ── -->
  <div style="display:flex;gap:4px;margin-bottom:20px;background:white;padding:6px;
              border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);flex-wrap:wrap;">
    <button class="tab-btn active" onclick="showTab('info',this)">👤 Profile</button>
    <button class="tab-btn" onclick="showTab('fees',this)">💰 Fees</button>
    <button class="tab-btn" onclick="showTab('results',this)">📊 Results</button>
    <button class="tab-btn" onclick="showTab('alerts',this)">🔔 Alerts</button>
    <button class="tab-btn" onclick="showTab('status',this)">⚙️ Status</button>
    <button class="tab-btn" onclick="showTab('idcard',this)">🪪 ID Card</button>
  </div>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- TAB: Profile -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <div id="tab-info" class="tab-panel">
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-user"></i> Personal Information</h3></div>
      <div class="card-body">
        <div class="photo-upload-area">
          <?php if (!empty($student['photo'])): ?>
            <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($student['photo']) ?>"
                 alt="Profile Photo" id="tabPreview" class="photo-circle">
          <?php else: ?>
            <div class="photo-initials-circle" id="tabInitials"
                 style="background:#7B1C3E;color:#fff;font-size:2rem;">
              <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
            </div>
            <img id="tabPreview" class="photo-circle" style="display:none;" src="" alt="">
          <?php endif; ?>
          <div>
            <div style="font-weight:700;font-size:15px;margin-bottom:4px;"><?= htmlspecialchars($student['full_name']) ?></div>
            <div style="color:#6b7280;font-size:13px;margin-bottom:12px;"><?= $student['reg_number'] ?></div>
            <label class="photo-upload-btn">
              <i class="fas fa-camera"></i> Update Photo
              <input type="file" accept="image/*" onchange="submitAdminPhoto(this)">
            </label>
            <div style="font-size:12px;color:#9ca3af;margin-top:6px;">JPG, PNG or WEBP — max 2MB</div>
          </div>
        </div>
        <table class="info-table">
          <tr><td>Full Name</td><td><?= htmlspecialchars($student['full_name']) ?></td></tr>
          <tr><td>Reg Number</td><td><?= $student['reg_number'] ?></td></tr>
          <tr><td>Email</td><td><?= $student['email'] ?></td></tr>
          <tr><td>Phone</td><td><?= $student['phone'] ?: '—' ?></td></tr>
          <tr><td>Department</td><td><?= $student['department'] ?></td></tr>
          <tr><td>Level</td><td><?= $student['level'] ?></td></tr>
          <tr><td>Gender</td><td><?= $student['gender'] ?></td></tr>
          <tr><td>Session</td><td><?= $student['session'] ?></td></tr>
          <tr><td>Status</td><td>
            <span class="badge badge-<?= $student['status']==='active'?'success':($student['status']==='graduated'?'info':'danger') ?>">
              <?= ucfirst($student['status']) ?>
            </span>
          </td></tr>
          <?php if ($student['status'] === 'graduated'): ?>
          <tr>
            <td>Graduation Session</td>
            <td><strong style="color:#3d0a1a;">🎓 <?= htmlspecialchars($student['graduation_session'] ?? '—') ?></strong></td>
          </tr>
          <tr>
            <td>Graduated On</td>
            <td><?= !empty($student['graduated_at']) ? date('d M Y', strtotime($student['graduated_at'])) : '—' ?></td>
          </tr>
          <?php endif; ?>
          <tr><td>Registered</td><td><?= date('d M Y', strtotime($student['created_at'])) ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- TAB: Fees -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <div id="tab-fees" class="tab-panel" style="display:none;">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-info">
          <div class="stat-number"><?= formatMoney($totalOwed) ?></div>
          <div class="stat-label">Total Billed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
          <div class="stat-number"><?= formatMoney($totalPaid) ?></div>
          <div class="stat-label">Total Paid</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon <?= $balance>0?'red':'green' ?>">
          <i class="fas fa-<?= $balance>0?'exclamation-circle':'check' ?>"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?= formatMoney($balance) ?></div>
          <div class="stat-label">Outstanding Balance</div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap;">
      <div>
        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:3px;">Academic Year</label>
        <select id="feeFilterYear" onchange="filterFees()"
                style="padding:7px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
          <option value="">All Years</option>
          <?php foreach($feeYears as $yr): ?>
          <option value="<?= $yr ?>"><?= $yr ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:3px;">Semester</label>
        <select id="feeFilterSem" onchange="filterFees()"
                style="padding:7px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
          <option value="">All Semesters</option>
          <option value="First">First</option>
          <option value="Second">Second</option>
          <option value="Full Year">Full Year</option>
        </select>
      </div>
      <button onclick="clearFeeFilter()" class="btn btn-outline" style="font-size:13px;padding:7px 14px;">
        <i class="fas fa-times"></i> Clear
      </button>
    </div>

    <div style="display:flex;gap:4px;margin-bottom:16px;background:#f9fafb;
                padding:5px;border-radius:10px;width:fit-content;">
      <button class="fee-subtab-btn active" onclick="showFeeSubtab('unpaid',this)">
        <i class="fas fa-exclamation-circle"></i> Unpaid / Partial
      </button>
      <button class="fee-subtab-btn" onclick="showFeeSubtab('paid',this)">
        <i class="fas fa-check-circle"></i> Paid
      </button>
    </div>

    <div id="fee-subtab-unpaid">
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-exclamation-circle" style="color:#dc2626;"></i> Unpaid &amp; Partial Fees</h3>
        </div>
        <div class="card-body" style="padding:0;">
          <table class="data-table" id="tbl-unpaid">
            <thead>
              <tr><th>Fee Type</th><th>Session</th><th>Semester</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due Date</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach($feeRows as $f): if($f['status']==='paid') continue; ?>
              <tr data-session="<?= $f['session'] ?>" data-semester="<?= $f['semester'] ?>">
                <td><strong><?= htmlspecialchars($f['fee_type']) ?></strong></td>
                <td><?= $f['session'] ?></td>
                <td><?= $f['semester'] ?></td>
                <td><?= formatMoney($f['amount']) ?></td>
                <td><?= formatMoney($f['amount_paid']) ?></td>
                <td style="color:#dc2626;font-weight:600;"><?= formatMoney($f['amount']-$f['amount_paid']) ?></td>
                <td><span class="badge badge-<?= $f['status']==='partial'?'warning':'danger' ?>"><?= ucfirst($f['status']) ?></span></td>
                <td><?= $f['due_date'] ?></td>
                <td>
                  <button onclick="openFeeEdit(<?= $f['id'] ?>,'<?= htmlspecialchars($f['fee_type']) ?>',<?= $f['amount'] ?>,<?= $f['amount_paid'] ?>)"
                          class="btn btn-sm btn-outline">
                    <i class="fas fa-edit"></i> Amend
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="fee-subtab-paid" style="display:none;">
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-check-circle" style="color:#16a34a;"></i> Paid Fees</h3>
        </div>
        <div class="card-body" style="padding:0;">
          <table class="data-table" id="tbl-paid">
            <thead>
              <tr><th>Fee Type</th><th>Session</th><th>Semester</th><th>Amount</th><th>Paid</th><th>Paid Date</th><th>Remark</th></tr>
            </thead>
            <tbody>
              <?php foreach($feeRows as $f): if($f['status']!=='paid') continue; ?>
              <tr data-session="<?= $f['session'] ?>" data-semester="<?= $f['semester'] ?>">
                <td><strong><?= htmlspecialchars($f['fee_type']) ?></strong></td>
                <td><?= $f['session'] ?></td>
                <td><?= $f['semester'] ?></td>
                <td><?= formatMoney($f['amount']) ?></td>
                <td style="color:#16a34a;font-weight:600;"><?= formatMoney($f['amount_paid']) ?></td>
                <td><?= $f['paid_date'] ?? '—' ?></td>
                <td><?= htmlspecialchars($f['remark'] ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div><!-- end tab-fees -->

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- TAB: Results -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <div id="tab-results" class="tab-panel" style="display:none;">
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;">
        <h3><i class="fas fa-chart-bar"></i> Academic Results</h3>
        <span>GPA: <strong style="color:#3d0a1a;font-size:1.2rem;"><?= $gpa ?></strong></span>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if(empty($resultRows)): ?>
          <div style="padding:32px;text-align:center;color:#9ca3af;">No results recorded yet.</div>
        <?php else: ?>
        <table class="data-table">
          <thead>
            <tr><th>Code</th><th>Course</th><th>Units</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Session</th></tr>
          </thead>
          <tbody>
            <?php foreach($resultRows as $r): ?>
            <tr>
              <td><?= $r['course_code'] ?></td>
              <td><?= htmlspecialchars($r['course_title']) ?></td>
              <td><?= $r['credit_units'] ?></td>
              <td><?= $r['ca_score'] ?></td>
              <td><?= $r['exam_score'] ?></td>
              <td><strong><?= $r['total_score'] ?></strong></td>
              <td><span class="badge badge-<?= $r['grade']==='F'?'danger':'success' ?>"><?= $r['grade'] ?></span></td>
              <td><?= $r['session'] ?> <?= $r['semester'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- TAB: Alerts -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <div id="tab-alerts" class="tab-panel" style="display:none;">
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3><i class="fas fa-paper-plane"></i> Send Alert to Student</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="send_notification">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Notification Title</label>
              <input type="text" name="notif_title" id="notifTitle" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Type</label>
              <select name="notif_type" class="form-control">
                <option value="warning">⚠️ Warning</option>
                <option value="danger">🚫 Danger</option>
                <option value="success">✅ Success</option>
                <option value="info">ℹ️ Info</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Message</label>
            <textarea name="notif_message" id="notifMessage" class="form-control" rows="3" required></textarea>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <button type="button" class="btn btn-outline" onclick="quickMsg('fee')">💰 Fee Warning</button>
            <button type="button" class="btn btn-outline" onclick="quickMsg('exam')">📝 Exam Not Approved</button>
            <button type="button" class="btn btn-outline" onclick="quickMsg('missed')">🕐 Exam Missed</button>
            <button type="button" class="btn btn-outline" onclick="quickMsg('suspend')">🚫 Suspension Notice</button>
            <button type="button" class="btn btn-outline" onclick="quickMsg('mass')">⛪ Missed Mass</button>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Send Alert
          </button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-bell"></i> Notification History</h3></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Title</th><th>Message</th><th>Type</th><th>Sent</th></tr></thead>
          <tbody>
            <?php while($n=$notifs->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($n['title']) ?></strong></td>
              <td><?= htmlspecialchars($n['message']) ?></td>
              <td><span class="badge badge-<?= $n['type']==='success'?'success':($n['type']==='danger'?'danger':($n['type']==='warning'?'warning':'info')) ?>"><?= $n['type'] ?></span></td>
              <td><?= timeAgo($n['created_at']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- TAB: Status -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <div id="tab-status" class="tab-panel" style="display:none;">
    <div class="card" style="max-width:520px;">
      <div class="card-header"><h3><i class="fas fa-cog"></i> Manage Student Status</h3></div>
      <div class="card-body">

        <!-- Department duration info box -->
        <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;
                    padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:14px;">
          <div style="background:#3d0a1a;color:#fff;border-radius:8px;padding:10px 14px;font-size:1.4rem;">🏫</div>
          <div>
            <div style="font-weight:700;font-size:14px;color:#111;">
              <?= htmlspecialchars($student['department']) ?>
            </div>
            <div style="font-size:13px;color:#6b7280;margin-top:4px;">
              <span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;">
                <?= $deptDegree ?>
              </span>
              &nbsp;
              <span style="background:<?= $deptDuration==6?'#fce7f3':($deptDuration==5?'#fef9c3':'#dcfce7') ?>;
                           color:<?= $deptDuration==6?'#be185d':($deptDuration==5?'#92400e':'#166534') ?>;
                           padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;">
                <?= $deptDuration ?>-year programme
              </span>
              <?php if (!$deptInfo): ?>
              <span style="color:#dc2626;font-size:12px;margin-left:6px;">
                ⚠️ Dept not in departments table — go to Courses to add it
              </span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <form method="POST">
          <input type="hidden" name="action" value="update_status">
          <div class="form-group">
            <label>Student Status</label>
            <select name="status" class="form-control" id="statusSelect" onchange="toggleStatusFields()">
              <option value="active"    <?= $student['status']==='active'   ?'selected':'' ?>>✅ Active</option>
              <option value="suspended" <?= $student['status']==='suspended'?'selected':'' ?>>🚫 Suspended</option>
              <option value="graduated" <?= $student['status']==='graduated'?'selected':'' ?>>🎓 Graduated</option>
            </select>
          </div>

          <!-- Suspension fields -->
          <div id="suspensionFields" style="display:<?= $student['status']==='suspended'?'block':'none' ?>;">
            <div class="form-group">
              <label>Suspension Reason</label>
              <select name="suspension_reason" class="form-control">
                <option value="Outstanding fees"      <?= ($student['suspension_reason']??'')==='Outstanding fees'     ?'selected':'' ?>>💰 Outstanding Fees</option>
                <option value="Exam misconduct"       <?= ($student['suspension_reason']??'')==='Exam misconduct'      ?'selected':'' ?>>📝 Exam Misconduct</option>
                <option value="Not approved for exam" <?= ($student['suspension_reason']??'')==='Not approved for exam'?'selected':'' ?>>❌ Not Approved for Exam</option>
                <option value="Disciplinary action"   <?= ($student['suspension_reason']??'')==='Disciplinary action'  ?'selected':'' ?>>⚠️ Disciplinary Action</option>
                <option value="Exam missed"           <?= ($student['suspension_reason']??'')==='Exam missed'          ?'selected':'' ?>>🕐 Exam Missed</option>
                <option value="Missed Mass/Chapel"    <?= ($student['suspension_reason']??'')==='Missed Mass/Chapel'   ?'selected':'' ?>>⛪ Missed Mass/Chapel</option>
                <option value="Other"                 <?= ($student['suspension_reason']??'')==='Other'                ?'selected':'' ?>>📋 Other</option>
              </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="form-group">
                <label>Suspension Date</label>
                <input type="date" name="suspension_date" class="form-control"
                       value="<?= $student['suspension_date'] ?? '' ?>">
              </div>
              <div class="form-group">
                <label>Resumption Date</label>
                <input type="date" name="resumption_date" class="form-control"
                       value="<?= $student['resumption_date'] ?? '' ?>">
              </div>
            </div>
          </div>

          <!-- Graduation fields -->
          <div id="graduationFields" style="display:<?= $student['status']==='graduated'?'block':'none' ?>;
               background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;
               padding:16px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
              <i class="fas fa-graduation-cap" style="color:#16a34a;font-size:18px;"></i>
              <strong style="color:#166534;">Graduation Details</strong>
            </div>
            <div class="form-group">
              <label>Graduation Session
                <span style="font-size:11px;color:#6b7280;font-weight:400;">
                  (based on <?= $deptDuration ?>-year programme)
                </span>
              </label>
              <select name="graduation_session" class="form-control" id="gradSessionSelect">
                <option value="">— Select Entry Year —</option>
                <?php foreach($autoSessions as $gs): ?>
                <option value="<?= $gs ?>"
                  <?= ($student['graduation_session'] ?? '') === $gs ? 'selected' : '' ?>>
                  <?= $gs ?> &nbsp;(<?= $deptDuration ?> yrs)
                </option>
                <?php endforeach; ?>
              </select>
              <div style="font-size:12px;color:#6b7280;margin-top:6px;">
                Entry year → graduation year calculated from the
                <strong><?= $deptDuration ?>-year</strong> duration set for
                <strong><?= htmlspecialchars($student['department']) ?></strong>.
                To change duration go to <a href="courses.php" style="color:#3d0a1a;">Courses → Departments</a>.
              </div>
            </div>

            <!-- Live preview -->
            <div id="gradPreview" style="display:none;margin-top:12px;
                 background:#dcfce7;border-radius:8px;padding:10px 14px;font-size:13px;color:#166534;">
              🎓 <strong id="gradPreviewText"></strong>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;">
            <i class="fas fa-save"></i> Update Status
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- TAB: ID Card -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <div id="tab-idcard" class="tab-panel" style="display:none;">
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><i class="fas fa-id-card"></i> Student ID Card</h3>
        <button onclick="printIDCard()" class="btn btn-primary">
          <i class="fas fa-print"></i> Print as PDF
        </button>
      </div>
      <div class="card-body">
        <div style="display:flex;gap:32px;flex-wrap:wrap;align-items:flex-start;">

          <!-- ── Card previews ── -->
          <div style="flex-shrink:0;">
            <p style="font-size:12px;color:#9ca3af;margin-bottom:6px;text-align:center;font-weight:600;">FRONT</p>

            <!-- FRONT CARD -->
            <div class="id-card" id="card-front">

              <!-- Blue header -->
              <div class="id-card-header" id="card-header-bar">
                <img src="<?= $base ?>assets/img/logoi.png"
                     onerror="this.style.display='none'"
                     style="width:50px;height:50px;object-fit:contain;flex-shrink:0;" alt="Logo">
                <div>
                  <div style="font-size:14px;font-weight:800;text-transform:uppercase;
                              letter-spacing:0.5px;line-height:1.2;" id="card-uni-name">
                    AVE MARIA UNIVERSITY
                  </div>
                  <div style="font-size:9px;opacity:0.9;margin-top:2px;" id="card-uni-address">
                    Along Jikwoyi-Karshi Road, Piyanko, FCT Abuja
                  </div>
                </div>
              </div>

              <!-- Red label -->
              <div style="background:#fff;color:#C62828;text-align:center;
                          font-size:10px;font-weight:800;padding:3px 0;
                          text-transform:uppercase;letter-spacing:1.5px;
                          border-bottom:1px solid #e5e7eb;
                          -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                Students Identity Card
              </div>

              <!-- Body -->
              <div style="display:flex;background:#fff;position:relative;min-height:100px;">

                <!-- Watermark -->
                <div style="position:absolute;inset:0;display:flex;align-items:center;
                            justify-content:center;pointer-events:none;z-index:0;">
                  <img src="<?= $base ?>assets/img/logoi.png"
                       onerror="this.style.display='none'"
                       style="width:90px;height:90px;object-fit:contain;opacity:0.07;filter:grayscale(1);" alt="">
                </div>

                <!-- Photo -->
                <div style="padding:10px 8px 10px 12px;flex-shrink:0;z-index:1;">
                  <?php if (!empty($student['photo'])): ?>
                    <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($student['photo']) ?>"
                         id="card-photo-img"
                         style="width:75px;height:88px;object-fit:cover;
                                border:2px solid #1565C0;border-radius:3px;display:block;" alt="Photo">
                  <?php else: ?>
                    <div id="card-photo-img"
                         style="width:75px;height:88px;background:#e5e7eb;
                                border:2px solid #1565C0;border-radius:3px;
                                display:flex;align-items:center;justify-content:center;
                                font-size:2rem;color:#9ca3af;">
                      <i class="fas fa-user"></i>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Info + QR -->
                <div style="padding:8px 10px 8px 4px;flex:1;z-index:1;
                            display:flex;flex-direction:column;justify-content:center;gap:5px;">
                  <div style="font-size:12px;font-weight:800;color:#1a1a1a;line-height:1.3;" id="card-name">
                    <?= htmlspecialchars($student['full_name']) ?>
                  </div>
                  <div style="font-size:11px;font-weight:700;color:#222;" id="card-dept">
                    <?= strtoupper($student['department']) ?>
                  </div>
                  <div style="font-size:10px;color:#444;" id="card-reg">
                    <?= $student['reg_number'] ?>
                  </div>
                  <!-- QR code — saved locally as qr_amup.png -->
                  <img id="card-qr"
                       src="<?= $base ?>assets/img/qr_amup.png"
                       style="width:46px;height:46px;border:2px solid #1565C0;
                              border-radius:3px;margin-top:4px;" alt="QR">
                </div>
              </div>

              <!-- Blue footer -->
              <div class="id-card-footer-bar" id="card-footer-bar">
                <span id="card-class">CLASS OF <?= $student['session'] ?></span>
              </div>

            </div><!-- end card-front -->

            <!-- BACK CARD -->
            <p style="font-size:12px;color:#9ca3af;margin:18px 0 6px;text-align:center;font-weight:600;">BACK</p>

            <div class="id-card" id="card-back">
              <!-- White body -->
              <div style="background:#fff;padding:14px 16px 10px;flex:1;position:relative;
                          -webkit-print-color-adjust:exact;print-color-adjust:exact;">

                <!-- Watermark on back -->
                <div style="position:absolute;inset:0;display:flex;align-items:center;
                            justify-content:center;pointer-events:none;">
                  <img src="<?= $base ?>assets/img/logoi.png"
                       onerror="this.style.display='none'"
                       style="width:100px;height:100px;object-fit:contain;opacity:0.07;filter:grayscale(1);" alt="">
                </div>

                <p style="font-size:8.5px;line-height:1.65;color:#111;margin:0 0 10px;position:relative;z-index:1;" id="card-back-text">
                  This I.D Card is valid through out the duration of your course.
                  You must carry it at all times whilst in the University Premises
                  as you may be asked to produce it for identification purpose.
                  You must present this card when borrowing from the Library
                  and when sitting for examination.<br><br>
                  This card remains the property of <strong>AVE MARIA UNIVERSITY.</strong>
                  Any unapproved use could constitute a breach of the Law<br><br>
                  If found, please return to the office of the Registrar
                </p>

                <div style="position:relative;z-index:1;margin-top:4px;">
                  <span style="font-size:11px;font-weight:800;font-style:italic;color:#111;">
                    Registrar's Sign. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  </span>
                </div>
              </div>

              <!-- Black footer -->
              <div style="background:#1a1a1a;color:#fff;text-align:center;
                          font-size:9px;font-weight:600;padding:6px;
                          letter-spacing:0.5px;
                          -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                Property of AMUP. If found pls return to the Registrar
              </div>
            </div><!-- end card-back -->

            <p style="font-size:11px;color:#9ca3af;margin-top:10px;text-align:center;">
              CR-80 card size: 85.6 × 54mm
            </p>
          </div><!-- end previews -->

          <!-- ── Edit form ── -->
          <div style="flex:1;min-width:260px;">
            <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
              Edit any field — the card updates live. Then click <strong>Print as PDF</strong>.
            </p>

            <?php
            $fields = [
              ['University Name',    'edit-uni-name',    'AVE MARIA UNIVERSITY',                             'card-uni-name'],
              ['Address',            'edit-uni-addr',    'Along Jikwoyi-Karshi Road, Piyanko, FCT Abuja',   'card-uni-address'],
              ['Student Full Name',  'edit-name',        htmlspecialchars($student['full_name']),            'card-name'],
              ['Department',         'edit-dept',        strtoupper($student['department']),                 'card-dept'],
              ['Registration Number','edit-reg',         $student['reg_number'],                             'card-reg'],
              ['Class / Session',    'edit-class',       'CLASS OF '.$student['session'],                   'card-class'],
            ];
            foreach($fields as [$label,$inputId,$val,$targetId]):
            ?>
            <div style="margin-bottom:12px;">
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                <?= $label ?>
              </label>
              <input type="text" id="<?= $inputId ?>" value="<?= $val ?>"
                     style="width:100%;padding:7px 10px;border:1px solid #d1d5db;
                            border-radius:8px;font-size:13px;box-sizing:border-box;"
                     oninput="document.getElementById('<?= $targetId ?>').innerText=this.value">
            </div>
            <?php endforeach; ?>

            <div style="margin-bottom:12px;">
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                Header / Footer Colour
              </label>
              <input type="color" value="#1565C0"
                     style="width:60px;height:36px;border:none;cursor:pointer;border-radius:6px;"
                     oninput="setCardColor(this.value)">
            </div>

            <div style="padding:12px 14px;background:#fffbeb;border:1px solid #fcd34d;
                        border-radius:8px;font-size:12px;color:#92400e;line-height:1.6;">
              <i class="fas fa-info-circle"></i>
              In the print dialog:<br>
              1. Set <strong>Destination → Save as PDF</strong><br>
              2. Turn off <strong>Headers and footers</strong><br>
              3. Turn on <strong>Background graphics</strong><br>
              4. Click Print — then send PDF to card printer at CR-80 size.
            </div>
          </div>

        </div>
      </div>
    </div>
  </div><!-- end tab-idcard -->

</div><!-- end main-content -->

<!-- ── Fee Edit Modal ── -->
<div id="feeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);
     z-index:9999;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:440px;margin:20px;">
    <h3 style="margin-bottom:20px;color:#3d0a1a;"><i class="fas fa-edit"></i> Amend Fee Payment</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update_fee">
      <input type="hidden" name="fee_id" id="modalFeeId">
      <div class="form-group">
        <label>Fee Type</label>
        <input type="text" id="modalFeeName" class="form-control" disabled>
      </div>
      <div class="form-group">
        <label>Total Amount</label>
        <input type="text" id="modalFeeTotal" class="form-control" disabled>
      </div>
      <div class="form-group">
        <label>Amount Paid (₦)</label>
        <input type="number" name="amount_paid" id="modalAmountPaid"
               class="form-control" step="0.01" min="0" required>
      </div>
      <div class="form-group">
        <label>Remark (optional)</label>
        <input type="text" name="remark" class="form-control"
               placeholder="e.g. Paid via bank transfer">
      </div>
      <div style="display:flex;gap:8px;margin-top:16px;">
        <button type="submit" class="btn btn-primary" style="flex:1;">
          <i class="fas fa-save"></i> Save Payment
        </button>
        <button type="button" class="btn btn-outline" onclick="closeFeeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).style.display = 'block';
  btn.classList.add('active');
}

function showFeeSubtab(name, btn) {
  document.getElementById('fee-subtab-unpaid').style.display = name==='unpaid' ? 'block' : 'none';
  document.getElementById('fee-subtab-paid').style.display   = name==='paid'   ? 'block' : 'none';
  document.querySelectorAll('.fee-subtab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function filterFees() {
  const year = document.getElementById('feeFilterYear').value;
  const sem  = document.getElementById('feeFilterSem').value;
  ['tbl-unpaid','tbl-paid'].forEach(tblId => {
    document.querySelectorAll('#' + tblId + ' tbody tr').forEach(row => {
      const matchYear = !year || row.dataset.session  === year;
      const matchSem  = !sem  || row.dataset.semester === sem;
      row.style.display = (matchYear && matchSem) ? '' : 'none';
    });
  });
}
function clearFeeFilter() {
  document.getElementById('feeFilterYear').value = '';
  document.getElementById('feeFilterSem').value  = '';
  filterFees();
}

function toggleStatusFields() {
  const val = document.getElementById('statusSelect').value;
  document.getElementById('suspensionFields').style.display = val === 'suspended' ? 'block' : 'none';
  document.getElementById('graduationFields').style.display = val === 'graduated'  ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
  const sel = document.getElementById('gradSessionSelect');
  if (!sel) return;
  function updatePreview() {
    const preview     = document.getElementById('gradPreview');
    const previewText = document.getElementById('gradPreviewText');
    if (sel.value) {
      previewText.textContent = 'Class of ' + sel.value + ' — will appear on student profile';
      preview.style.display = 'block';
    } else {
      preview.style.display = 'none';
    }
  }
  sel.addEventListener('change', updatePreview);
  updatePreview();
});

function openFeeEdit(feeId, feeName, amount, paid) {
  document.getElementById('modalFeeId').value      = feeId;
  document.getElementById('modalFeeName').value    = feeName;
  document.getElementById('modalFeeTotal').value   = '₦' + amount.toLocaleString();
  document.getElementById('modalAmountPaid').value = paid;
  document.getElementById('feeModal').style.display = 'flex';
}
function closeFeeModal() {
  document.getElementById('feeModal').style.display = 'none';
}

function submitAdminPhoto(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const reader = new FileReader();
  reader.onload = e => {
    const hp = document.getElementById('headerPreview');
    const hi = document.getElementById('headerInitials');
    if (hp) { hp.src = e.target.result; hp.style.display = 'block'; }
    if (hi) hi.style.display = 'none';
    const tp = document.getElementById('tabPreview');
    const ti = document.getElementById('tabInitials');
    if (tp) { tp.src = e.target.result; tp.style.display = 'block'; }
    if (ti) ti.style.display = 'none';
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('headerPhotoInput').files = dt.files;
    document.getElementById('photoUploadForm').submit();
  };
  reader.readAsDataURL(file);
}
document.getElementById('headerPhotoInput').addEventListener('change', function() {
  if (this.files && this.files[0]) document.getElementById('photoUploadForm').submit();
});

// ── ID Card colour ────────────────────────────────────────────────
function setCardColor(color) {
  document.querySelectorAll('.id-card-header, .id-card-footer-bar').forEach(el => {
    el.style.background = color;
  });
  document.querySelectorAll('#card-front .id-card-header').forEach(el => {
    el.style.borderColor = color;
  });
  document.getElementById('card-qr').style.borderColor = color;
  document.querySelectorAll('#card-front img[id="card-photo-img"]').forEach(el => {
    el.style.borderColor = color;
  });
}

// ── Print ─────────────────────────────────────────────────────────
function printIDCard() {
  // Remove any old print wrapper
  const old = document.getElementById('print-cards-only');
  if (old) old.remove();

  // Clone both cards into a hidden print-only container
  const front = document.getElementById('card-front').cloneNode(true);
  const back  = document.getElementById('card-back').cloneNode(true);

  const wrap = document.createElement('div');
  wrap.id = 'print-cards-only';
  wrap.style.cssText = 'display:none;';
  wrap.appendChild(front);
  wrap.appendChild(back);
  document.body.appendChild(wrap);

  window.print();
}

const templates = {
  fee:     ['⚠️ Outstanding Fee Warning',     'Dear student, you have outstanding fees on your account. Please visit the bursary to clear your fees immediately to avoid suspension.'],
  exam:    ['❌ Not Approved for Examination', 'Dear student, you have NOT been approved to sit for the upcoming examination due to outstanding obligations. Please report to the admin office immediately.'],
  missed:  ['🕐 Examination Missed',           'Dear student, our records indicate that you missed a scheduled examination. Please report to the examination office with a valid reason within 48 hours.'],
  suspend: ['🚫 Suspension Notice',            'Dear student, your account has been suspended. Please visit the admin office for further information and to resolve outstanding issues.'],
  mass:    ['⛪ Missed Mass/Chapel',            'Dear student, our records indicate that you missed the mandatory Mass/Chapel service. Attendance is compulsory. Repeated absence may attract disciplinary action.'],
};
function quickMsg(type) {
  document.getElementById('notifTitle').value   = templates[type][0];
  document.getElementById('notifMessage').value = templates[type][1];
}
</script>

</body>
</html>