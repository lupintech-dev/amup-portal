<?php
require_once '../includes/config.php';
if (!isset($_SESSION['bursar_id'])) {
    header("Location: login.php"); exit();
}

$bursar_name = $_SESSION['bursar_name'];
$success = $error = '';

// Load students for dropdown
$students = $conn->query("SELECT id, full_name, reg_number, department FROM students WHERE status='active' ORDER BY full_name");

// Load fee templates for dropdown
$templates = $conn->query("SELECT * FROM fee_templates WHERE is_active=1 ORDER BY fee_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id  = (int)($_POST['student_id'] ?? 0);
    $fee_type    = sanitize($conn, $_POST['fee_type'] ?? '');
    $amount      = (float)($_POST['amount'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $session     = sanitize($conn, $_POST['session'] ?? '');
    $semester    = sanitize($conn, $_POST['semester'] ?? '');
    $due_date    = sanitize($conn, $_POST['due_date'] ?? '');
    $receipt_no  = sanitize($conn, $_POST['receipt_no'] ?? '');
    $remark      = sanitize($conn, $_POST['remark'] ?? '');

    // Determine status
    if ($amount_paid <= 0) {
        $status = 'unpaid';
    } elseif ($amount_paid >= $amount) {
        $status = 'paid';
    } else {
        $status = 'partial';
    }

    $paid_date = ($status === 'paid') ? date('Y-m-d') : null;

    if (!$student_id || !$fee_type || !$amount || !$session || !$semester) {
        $error = 'Please fill in all required fields.';
    } elseif ($amount_paid > $amount) {
        $error = 'Amount paid cannot exceed total fee amount.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO fees (student_id, fee_type, amount, amount_paid, session, semester, due_date, paid_date, status, receipt_no, remark, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('isddsssssss', $student_id, $fee_type, $amount, $amount_paid, $session, $semester, $due_date, $paid_date, $status, $receipt_no, $remark);

        if ($stmt->execute()) {
            $success = 'Payment record added successfully.';
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Record Payment — AMUP Bursar</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --maroon:#3d0a1a;--maroon2:#7B1C3E;--gold:#D4A825;
  --bg:#f5f0f2;--white:#fff;--text:#1a1a2e;--muted:#6b7280;
  --sidebar:220px;
}
body{font-family:'Source Sans 3',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

/* Sidebar */
.sidebar{width:var(--sidebar);background:var(--maroon);position:fixed;top:0;left:0;height:100vh;display:flex;flex-direction:column;z-index:100;}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:12px;}
.sidebar-logo img{width:42px;}
.sidebar-logo-text{color:#fff;}
.sidebar-logo-text h3{font-family:'Playfair Display',serif;font-size:1rem;line-height:1.2;}
.sidebar-logo-text p{font-size:11px;opacity:.6;}
nav{flex:1;padding:20px 0;overflow-y:auto;}
.nav-section{font-size:10px;font-weight:700;color:rgba(255,255,255,0.35);letter-spacing:1.5px;padding:16px 20px 6px;text-transform:uppercase;}
.nav-link{display:flex;align-items:center;gap:12px;padding:10px 20px;color:rgba(255,255,255,0.75);font-size:13.5px;text-decoration:none;transition:.2s;}
.nav-link:hover,.nav-link.active{background:rgba(255,255,255,0.1);color:#fff;}
.nav-link.active{border-left:3px solid var(--gold);}
.nav-link i{width:18px;text-align:center;font-size:13px;}
.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1);}
.sidebar-user{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.85);font-size:13px;}
.sidebar-avatar{width:34px;height:34px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--maroon);font-size:14px;flex-shrink:0;}

/* Main */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--white);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ede8ea;position:sticky;top:0;z-index:50;}
.topbar-title h2{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--maroon);}
.topbar-title p{font-size:12px;color:var(--muted);}
.badge-user{display:flex;align-items:center;gap:8px;background:var(--bg);padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;color:var(--maroon);}
.badge-user .av{width:30px;height:30px;border-radius:50%;background:var(--maroon);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;}
.content{padding:28px 32px;flex:1;}

/* Card */
.card{background:var(--white);border-radius:16px;border:1px solid rgba(61,10,26,0.06);box-shadow:0 2px 12px rgba(61,10,26,0.05);overflow:hidden;}
.card-header{padding:20px 28px;border-bottom:1px solid #f0eaec;display:flex;align-items:center;gap:12px;}
.card-header h3{font-family:'Playfair Display',serif;font-size:1.15rem;color:var(--maroon);}
.card-body{padding:28px;}

/* Form */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
.form-group label{font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;}
.form-group input,
.form-group select,
.form-group textarea{padding:11px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;outline:none;color:var(--text);transition:.2s;}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{border-color:var(--maroon2);}
.form-group textarea{resize:vertical;min-height:80px;}
.form-group .hint{font-size:11px;color:var(--muted);}

/* Amount preview */
.amount-preview{background:#f5f0f2;border-radius:10px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-top:4px;}
.amount-preview span{font-size:13px;color:var(--muted);}
.amount-preview strong{font-size:1.1rem;color:var(--maroon);font-weight:700;}

/* Buttons */
.btn{padding:11px 22px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,var(--maroon),var(--maroon2));color:#fff;}
.btn-secondary{background:#f3f4f6;color:var(--text);}
.form-footer{display:flex;justify-content:flex-end;gap:12px;margin-top:28px;padding-top:20px;border-top:1px solid #f0eaec;}

/* Alert */
.alert{padding:14px 20px;border-radius:10px;font-size:14px;font-weight:600;margin-bottom:24px;display:flex;align-items:center;gap:10px;}
.alert-success{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;}
.alert-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}

/* Status badge preview */
.status-preview{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-top:8px;}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="../assets/img/logo.png" alt="AMUP">
    <div class="sidebar-logo-text">
      <h3>AMUP Bursar</h3>
      <p>Fee Management</p>
    </div>
  </div>
  <nav>
    <div class="nav-section">Dashboard</div>
    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Overview</a>
    <div class="nav-section">Fees</div>
    <a href="students.php" class="nav-link"><i class="fas fa-users"></i> All Students</a>
    <a href="payments.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Fee Records</a>
    <a href="record_payment.php" class="nav-link active"><i class="fas fa-plus-circle"></i> Record Payment</a>
    <div class="nav-section">Reports</div>
    <a href="receipts.php" class="nav-link"><i class="fas fa-file-invoice"></i> Receipts</a>
    <a href="summary.php" class="nav-link"><i class="fas fa-chart-bar"></i> Summary Report</a>
    <div class="nav-section">Account</div>
    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
      <div>
        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($bursar_name) ?></div>
        <div style="font-size:11px;opacity:.6;">Bursar</div>
      </div>
    </div>
  </div>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Record Payment</h2>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="badge-user">
      <div class="av"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
      <?= htmlspecialchars($bursar_name) ?>
    </div>
  </div>

  <div class="content">

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-plus-circle" style="color:var(--gold);font-size:18px;"></i>
        <h3>New Fee Payment Record</h3>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="form-grid">

            <!-- Student -->
            <div class="form-group full">
              <label>Student <span style="color:#dc2626;">*</span></label>
              <select name="student_id" id="student_id" required onchange="loadStudentFees(this.value)">
                <option value="">— Select Student —</option>
                <?php while($s = $students->fetch_assoc()): ?>
                  <option value="<?= $s['id'] ?>"
                    data-reg="<?= $s['reg_number'] ?>"
                    data-dept="<?= htmlspecialchars($s['department']) ?>"
                    <?= (isset($_POST['student_id']) && $_POST['student_id']==$s['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['full_name']) ?> — <?= $s['reg_number'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <div id="student-info" style="font-size:12px;color:var(--muted);margin-top:4px;"></div>
            </div>

            <!-- Fee Type -->
            <div class="form-group">
              <label>Fee Type <span style="color:#dc2626;">*</span></label>
              <select name="fee_type" id="fee_type" required onchange="setAmount(this)">
                <option value="">— Select Fee Type —</option>
                <?php
                // Reset pointer
                $templates->data_seek(0);
                while($t = $templates->fetch_assoc()):
                ?>
                  <option value="<?= htmlspecialchars($t['fee_name']) ?>"
                    data-amount="<?= $t['amount'] ?>"
                    <?= (isset($_POST['fee_type']) && $_POST['fee_type']==$t['fee_name']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['fee_name']) ?> — ₦<?= number_format($t['amount'],2) ?>
                  </option>
                <?php endwhile; ?>
                <option value="Other">Other (manual)</option>
              </select>
            </div>

            <!-- Total Amount -->
            <div class="form-group">
              <label>Total Amount (₦) <span style="color:#dc2626;">*</span></label>
              <input type="number" name="amount" id="amount" step="0.01" min="0" placeholder="0.00" required
                value="<?= isset($_POST['amount']) ? $_POST['amount'] : '' ?>">
            </div>

            <!-- Amount Paid -->
            <div class="form-group">
              <label>Amount Paid Now (₦) <span style="color:#dc2626;">*</span></label>
              <input type="number" name="amount_paid" id="amount_paid" step="0.01" min="0" placeholder="0.00" required
                value="<?= isset($_POST['amount_paid']) ? $_POST['amount_paid'] : '' ?>"
                oninput="updatePreview()">
              <div id="status-preview"></div>
            </div>

            <!-- Outstanding preview -->
            <div class="form-group">
              <label>Outstanding (auto-calculated)</label>
              <div class="amount-preview">
                <span>Remaining balance:</span>
                <strong id="outstanding-display">₦0.00</strong>
              </div>
            </div>

            <!-- Session -->
            <div class="form-group">
              <label>Session <span style="color:#dc2626;">*</span></label>
              <select name="session" required>
                <option value="">— Select Session —</option>
                <?php
                $yr = date('Y');
                for($y = $yr; $y >= $yr-5; $y--):
                  $val = $y.'/'.(($y)+1);
                ?>
                  <option value="<?= $val ?>" <?= (isset($_POST['session']) && $_POST['session']==$val)?'selected':'' ?>><?= $val ?></option>
                <?php endfor; ?>
              </select>
            </div>

            <!-- Semester -->
            <div class="form-group">
              <label>Semester <span style="color:#dc2626;">*</span></label>
              <select name="semester" required>
                <option value="">— Select Semester —</option>
                <option value="First"    <?= (isset($_POST['semester']) && $_POST['semester']=='First')   ?'selected':'' ?>>First</option>
                <option value="Second"   <?= (isset($_POST['semester']) && $_POST['semester']=='Second')  ?'selected':'' ?>>Second</option>
                <option value="Full Year"<?= (isset($_POST['semester']) && $_POST['semester']=='Full Year')?'selected':'' ?>>Full Year</option>
              </select>
            </div>

            <!-- Due Date -->
            <div class="form-group">
              <label>Due Date</label>
              <input type="date" name="due_date" value="<?= isset($_POST['due_date']) ? $_POST['due_date'] : '' ?>">
            </div>

            <!-- Receipt No -->
            <div class="form-group">
              <label>Receipt Number</label>
              <input type="text" name="receipt_no" placeholder="e.g. RCP-2024-001"
                value="<?= isset($_POST['receipt_no']) ? htmlspecialchars($_POST['receipt_no']) : '' ?>">
            </div>

            <!-- Remark -->
            <div class="form-group full">
              <label>Remark</label>
              <textarea name="remark" placeholder="Any additional notes..."><?= isset($_POST['remark']) ? htmlspecialchars($_POST['remark']) : '' ?></textarea>
            </div>

          </div>

          <div class="form-footer">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Payment Record</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
function setAmount(sel) {
  const opt = sel.options[sel.selectedIndex];
  const amt = opt.getAttribute('data-amount');
  if (amt) {
    document.getElementById('amount').value = parseFloat(amt).toFixed(2);
    updatePreview();
  } else {
    document.getElementById('amount').value = '';
    updatePreview();
  }
}

function updatePreview() {
  const total   = parseFloat(document.getElementById('amount').value) || 0;
  const paid    = parseFloat(document.getElementById('amount_paid').value) || 0;
  const outstanding = total - paid;

  document.getElementById('outstanding-display').textContent =
    '₦' + Math.max(0, outstanding).toLocaleString('en-NG', {minimumFractionDigits:2});

  const preview = document.getElementById('status-preview');
  if (paid <= 0) {
    preview.innerHTML = '<span class="status-preview" style="background:#fee2e2;color:#dc2626;">Will be: Unpaid</span>';
  } else if (paid >= total && total > 0) {
    preview.innerHTML = '<span class="status-preview" style="background:#dcfce7;color:#16a34a;">Will be: Paid</span>';
  } else {
    preview.innerHTML = '<span class="status-preview" style="background:#fef9c3;color:#b45309;">Will be: Partial</span>';
  }
}

function loadStudentFees(id) {
  const sel  = document.getElementById('student_id');
  const opt  = sel.options[sel.selectedIndex];
  const info = document.getElementById('student-info');
  if (id) {
    info.textContent = opt.getAttribute('data-dept') + ' — ' + opt.getAttribute('data-reg');
  } else {
    info.textContent = '';
  }
}

// Also trigger on amount change
document.getElementById('amount').addEventListener('input', updatePreview);
</script>
</body>
</html>