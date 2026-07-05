<?php
require_once '../includes/config.php';
if (!isset($_SESSION['bursar_id'])) {
    header("Location: login.php"); exit();
}

$bursar_name = $_SESSION['bursar_name'];

$search     = sanitize($conn, $_GET['search'] ?? '');
$s_session  = sanitize($conn, $_GET['session'] ?? '');
$s_status   = sanitize($conn, $_GET['status'] ?? '');
$s_student  = (int)($_GET['student_id'] ?? 0);

$where = "WHERE 1=1";
if ($search)    $where .= " AND (s.full_name LIKE '%$search%' OR s.reg_number LIKE '%$search%')";
if ($s_session) $where .= " AND f.session = '$s_session'";
if ($s_status)  $where .= " AND f.status = '$s_status'";
if ($s_student) $where .= " AND f.student_id = $s_student";

$fees = $conn->query("
    SELECT f.*, s.full_name, s.reg_number, s.department, s.level
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
    ORDER BY f.created_at DESC
");

$sessions = $conn->query("SELECT DISTINCT session FROM fees ORDER BY session DESC");

// totals for filtered result
$totals = $conn->query("
    SELECT
        COALESCE(SUM(f.amount),0) AS billed,
        COALESCE(SUM(f.amount_paid),0) AS collected,
        COALESCE(SUM(f.amount - f.amount_paid),0) AS outstanding
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Fee Records — AMUP Bursar</title>
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
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--white);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ede8ea;position:sticky;top:0;z-index:50;}
.topbar-title h2{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--maroon);}
.topbar-title p{font-size:12px;color:var(--muted);}
.badge-user{display:flex;align-items:center;gap:8px;background:var(--bg);padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;color:var(--maroon);}
.badge-user .av{width:30px;height:30px;border-radius:50%;background:var(--maroon);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;}
.content{padding:28px 32px;flex:1;}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:var(--white);border-radius:14px;padding:20px 24px;display:flex;align-items:center;gap:16px;border:1px solid rgba(61,10,26,0.06);}
.stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.stat-icon.green{background:#dcfce7;color:#16a34a;}
.stat-icon.red{background:#fee2e2;color:#dc2626;}
.stat-icon.blue{background:#dbeafe;color:#2563eb;}
.stat-label{font-size:12px;color:var(--muted);margin-bottom:3px;}
.stat-value{font-size:1.3rem;font-weight:700;color:var(--maroon);}
.filter-bar{background:var(--white);border-radius:14px;padding:18px 24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px;border:1px solid rgba(61,10,26,0.06);}
.filter-bar input,.filter-bar select{padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-family:inherit;outline:none;color:var(--text);}
.filter-bar input{flex:1;min-width:180px;}
.btn{padding:9px 18px;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,var(--maroon),var(--maroon2));color:#fff;}
.btn-secondary{background:#f3f4f6;color:var(--text);}
.btn-sm{padding:6px 12px;font-size:12px;}
.table-wrap{background:var(--white);border-radius:16px;border:1px solid rgba(61,10,26,0.06);overflow:hidden;box-shadow:0 2px 12px rgba(61,10,26,0.05);}
.table-header{padding:18px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0eaec;}
.table-header h3{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--maroon);}
table{width:100%;border-collapse:collapse;}
th{background:#faf6f7;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid #f0eaec;}
td{padding:13px 16px;font-size:13px;border-bottom:1px solid #faf6f7;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fdf9fa;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-paid{background:#dcfce7;color:#16a34a;}
.badge-unpaid{background:#fee2e2;color:#dc2626;}
.badge-partial{background:#fef9c3;color:#b45309;}
.action-btns{display:flex;gap:6px;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:20px;padding:36px;width:100%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.2);}
.modal h3{font-family:'Playfair Display',serif;color:var(--maroon);font-size:1.3rem;margin-bottom:20px;}
.form-group{margin-bottom:16px;}
.form-group label{font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;outline:none;}
.form-group textarea{resize:vertical;min-height:80px;}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:24px;}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5f0f2;font-size:13px;}
.info-row:last-child{border:none;}
.info-label{color:var(--muted);}
.info-value{font-weight:600;}
a{text-decoration:none;}
</style>
</head>
<body>

<?php $_sb_page = 'payments'; require 'sidebar.php'; ?>
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
    <a href="payments.php" class="nav-link active"><i class="fas fa-money-bill-wave"></i> Fee Records</a>
    <a href="record_payment.php" class="nav-link"><i class="fas fa-plus-circle"></i> Record Payment</a>
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

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Fee Records</h2>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="badge-user">
      <div class="av"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
      <?= htmlspecialchars($bursar_name) ?>
    </div>
  </div>

  <div class="content">

    <!-- Mini stats for current filter -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-file-invoice"></i></div>
        <div>
          <div class="stat-label">Total Billed</div>
          <div class="stat-value">₦<?= number_format($totals['billed'],2) ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div>
          <div class="stat-label">Collected</div>
          <div class="stat-value">₦<?= number_format($totals['collected'],2) ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
        <div>
          <div class="stat-label">Outstanding</div>
          <div class="stat-value">₦<?= number_format($totals['outstanding'],2) ?></div>
        </div>
      </div>
    </div>

    <form method="GET" class="filter-bar">
      <input type="text" name="search" placeholder="Search student name or reg number..." value="<?= htmlspecialchars($search) ?>">
      <select name="session">
        <option value="">All Sessions</option>
        <?php while($row = $sessions->fetch_assoc()): ?>
          <option value="<?= $row['session'] ?>" <?= $s_session==$row['session']?'selected':'' ?>><?= $row['session'] ?></option>
        <?php endwhile; ?>
      </select>
      <select name="status">
        <option value="">All Status</option>
        <option value="paid"     <?= $s_status=='paid'   ?'selected':'' ?>>Paid</option>
        <option value="unpaid"   <?= $s_status=='unpaid' ?'selected':'' ?>>Unpaid</option>
        <option value="partial"  <?= $s_status=='partial'?'selected':'' ?>>Partial</option>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
      <a href="payments.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
      <div class="table-header">
        <h3>Fee Records <?= $s_student ? '— Filtered by Student' : '' ?></h3>
        <span style="font-size:13px;color:var(--muted);"><?= $fees->num_rows ?> record(s)</span>
      </div>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Reg Number</th>
            <th>Department</th>
            <th>Fee Type</th>
            <th>Amount</th>
            <th>Amount Paid</th>
            <th>Outstanding</th>
            <th>Session</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        if ($fees->num_rows === 0):
        ?>
          <tr><td colspan="11" style="text-align:center;color:var(--muted);padding:40px;">No fee records found.</td></tr>
        <?php else: while($f = $fees->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($f['full_name']) ?></strong></td>
            <td style="font-family:monospace;font-size:12px;"><?= $f['reg_number'] ?></td>
            <td><?= $f['department'] ?></td>
            <td><?= $f['fee_type'] ?></td>
            <td>₦<?= number_format($f['amount'],2) ?></td>
            <td style="color:#16a34a;font-weight:600;">₦<?= number_format($f['amount_paid'],2) ?></td>
            <td style="color:#dc2626;font-weight:600;">₦<?= number_format($f['amount']-$f['amount_paid'],2) ?></td>
            <td><?= $f['session'] ?> / <?= $f['semester'] ?></td>
            <td><span class="badge badge-<?= $f['status'] ?>"><?= ucfirst($f['status']) ?></span></td>
            <td>
              <div class="action-btns">
                <button class="btn btn-primary btn-sm" onclick="openView(<?= htmlspecialchars(json_encode($f)) ?>)">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm" style="background:#fef9c3;color:#b45309;" onclick="openEdit(<?= htmlspecialchars(json_encode($f)) ?>)">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
  <div class="modal">
    <h3><i class="fas fa-file-invoice" style="color:var(--gold);margin-right:8px;"></i> Fee Details</h3>
    <div id="viewBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h3><i class="fas fa-edit" style="color:var(--gold);margin-right:8px;"></i> Update Payment</h3>
    <form method="POST" action="update_payment.php">
      <input type="hidden" name="fee_id" id="edit_fee_id">
      <div class="form-group">
        <label>Amount Paid (₦)</label>
        <input type="number" step="0.01" name="amount_paid" id="edit_amount_paid" required>
      </div>
      <div class="form-group">
        <label>Payment Status</label>
        <select name="status" id="edit_status">
          <option value="unpaid">Unpaid</option>
          <option value="partial">Partial</option>
          <option value="paid">Paid</option>
        </select>
      </div>
      <div class="form-group">
        <label>Receipt No (optional)</label>
        <input type="text" name="receipt_no" id="edit_receipt_no" placeholder="e.g. RCP-2024-001">
      </div>
      <div class="form-group">
        <label>Remark (optional)</label>
        <textarea name="remark" id="edit_remark"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openView(f) {
  const fmt = n => '₦' + parseFloat(n).toLocaleString('en-NG', {minimumFractionDigits:2});
  document.getElementById('viewBody').innerHTML = `
    <div class="info-row"><span class="info-label">Student</span><span class="info-value">${f.full_name}</span></div>
    <div class="info-row"><span class="info-label">Reg Number</span><span class="info-value">${f.reg_number}</span></div>
    <div class="info-row"><span class="info-label">Fee Type</span><span class="info-value">${f.fee_type}</span></div>
    <div class="info-row"><span class="info-label">Total Amount</span><span class="info-value">${fmt(f.amount)}</span></div>
    <div class="info-row"><span class="info-label">Amount Paid</span><span class="info-value" style="color:#16a34a;">${fmt(f.amount_paid)}</span></div>
    <div class="info-row"><span class="info-label">Outstanding</span><span class="info-value" style="color:#dc2626;">${fmt(f.amount - f.amount_paid)}</span></div>
    <div class="info-row"><span class="info-label">Session</span><span class="info-value">${f.session} / ${f.semester}</span></div>
    <div class="info-row"><span class="info-label">Due Date</span><span class="info-value">${f.due_date ?? 'N/A'}</span></div>
    <div class="info-row"><span class="info-label">Paid Date</span><span class="info-value">${f.paid_date ?? 'N/A'}</span></div>
    <div class="info-row"><span class="info-label">Receipt No</span><span class="info-value">${f.receipt_no ?? 'N/A'}</span></div>
    <div class="info-row"><span class="info-label">Status</span><span class="info-value">${f.status}</span></div>
  `;
  document.getElementById('viewModal').classList.add('open');
}

function openEdit(f) {
  document.getElementById('edit_fee_id').value      = f.id;
  document.getElementById('edit_amount_paid').value = f.amount_paid;
  document.getElementById('edit_status').value      = f.status;
  document.getElementById('edit_receipt_no').value  = f.receipt_no ?? '';
  document.getElementById('edit_remark').value      = f.remark ?? '';
  document.getElementById('editModal').classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if(e.target===el) el.classList.remove('open'); });
});
</script>
</body>
</html>