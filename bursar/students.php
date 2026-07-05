<?php
require_once '../includes/config.php';
if (!isset($_SESSION['bursar_id'])) {
    header("Location: login.php"); exit();
}

$bursar_name = $_SESSION['bursar_name'];

$search = sanitize($conn, $_GET['search'] ?? '');
$s_dept = sanitize($conn, $_GET['department'] ?? '');

$where = "WHERE 1=1";
if ($search) $where .= " AND (s.full_name LIKE '%$search%' OR s.reg_number LIKE '%$search%' OR s.email LIKE '%$search%')";
if ($s_dept) $where .= " AND s.department = '$s_dept'";

$students = $conn->query("
    SELECT s.*,
        COALESCE(SUM(f.amount),0)      AS total_billed,
        COALESCE(SUM(f.amount_paid),0) AS total_paid,
        COALESCE(SUM(f.amount - f.amount_paid),0) AS total_outstanding
    FROM students s
    LEFT JOIN fees f ON f.student_id = s.id
    $where
    GROUP BY s.id
    ORDER BY s.full_name
");

$depts = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Students — AMUP Bursar</title>
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
.filter-bar{background:var(--white);border-radius:14px;padding:18px 24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px;border:1px solid rgba(61,10,26,0.06);}
.filter-bar input,.filter-bar select{padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-family:inherit;outline:none;color:var(--text);}
.filter-bar input{flex:1;min-width:200px;}
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
.badge-active{background:#dcfce7;color:#16a34a;}
.badge-inactive{background:#fee2e2;color:#dc2626;}
.badge-cleared{background:#dcfce7;color:#16a34a;}
.badge-owing{background:#fee2e2;color:#dc2626;}
.avatar{width:34px;height:34px;border-radius:50%;background:var(--maroon2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;}
.student-cell{display:flex;align-items:center;gap:10px;}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:20px;padding:36px;width:100%;max-width:540px;box-shadow:0 20px 60px rgba(0,0,0,0.2);max-height:90vh;overflow-y:auto;}
.modal h3{font-family:'Playfair Display',serif;color:var(--maroon);font-size:1.3rem;margin-bottom:20px;}
.info-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f5f0f2;font-size:13px;}
.info-row:last-child{border:none;}
.info-label{color:var(--muted);}
.info-value{font-weight:600;text-align:right;}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;}
.section-divider{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);padding:12px 0 4px;margin-top:8px;}
a{text-decoration:none;}
</style>
</head>
<body>

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
    <a href="students.php" class="nav-link active"><i class="fas fa-users"></i> All Students</a>
    <a href="payments.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Fee Records</a>
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
      <h2>All Students</h2>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="badge-user">
      <div class="av"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
      <?= htmlspecialchars($bursar_name) ?>
    </div>
  </div>

  <div class="content">

    <form method="GET" class="filter-bar">
      <input type="text" name="search" placeholder="Search by name, reg number or email..." value="<?= htmlspecialchars($search) ?>">
      <select name="department">
        <option value="">All Departments</option>
        <?php while($d = $depts->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($d['department']) ?>" <?= $s_dept==$d['department']?'selected':'' ?>><?= $d['department'] ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
      <a href="students.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
      <div class="table-header">
        <h3>Students — Fee Overview</h3>
        <span style="font-size:13px;color:var(--muted);"><?= $students->num_rows ?> student(s)</span>
      </div>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Reg Number</th>
            <th>Department</th>
            <th>Level</th>
            <th>Total Billed</th>
            <th>Amount Paid</th>
            <th>Outstanding</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        if ($students->num_rows === 0):
        ?>
          <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:40px;">No students found.</td></tr>
        <?php else: while($s = $students->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td>
              <div class="student-cell">
                <div class="avatar"><?= strtoupper(substr($s['full_name'],0,1)) ?></div>
                <div>
                  <div style="font-weight:600;"><?= htmlspecialchars($s['full_name']) ?></div>
                  <div style="font-size:11px;color:var(--muted);"><?= $s['email'] ?></div>
                </div>
              </div>
            </td>
            <td style="font-family:monospace;font-size:12px;"><?= $s['reg_number'] ?></td>
            <td><?= htmlspecialchars($s['department']) ?></td>
            <td><?= $s['level'] ?></td>
            <td>₦<?= number_format($s['total_billed'],2) ?></td>
            <td style="color:#16a34a;font-weight:600;">₦<?= number_format($s['total_paid'],2) ?></td>
            <td style="color:#dc2626;font-weight:600;">₦<?= number_format($s['total_outstanding'],2) ?></td>
            <td>
              <?php if($s['total_outstanding'] <= 0 && $s['total_billed'] > 0): ?>
                <span class="badge badge-cleared">Cleared</span>
              <?php elseif($s['total_outstanding'] > 0): ?>
                <span class="badge badge-owing">Owing</span>
              <?php else: ?>
                <span class="badge" style="background:#f3f4f6;color:var(--muted);">No Fees</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-primary btn-sm" onclick="viewStudent(<?= htmlspecialchars(json_encode($s)) ?>)">
                <i class="fas fa-eye"></i> View
              </button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="studentModal">
  <div class="modal">
    <h3><i class="fas fa-user-graduate" style="color:var(--gold);margin-right:8px;"></i> Student Details</h3>
    <div id="modalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Close</button>
      <a id="viewFeesLink" href="#" class="btn btn-primary"><i class="fas fa-money-bill-wave"></i> View Fee Records</a>
    </div>
  </div>
</div>

<script>
function viewStudent(s) {
  const fmt = n => '₦' + parseFloat(n).toLocaleString('en-NG', {minimumFractionDigits:2});
  document.getElementById('modalBody').innerHTML = `
    <div class="section-divider">Personal Information</div>
    <div class="info-row"><span class="info-label">Full Name</span><span class="info-value">${s.full_name}</span></div>
    <div class="info-row"><span class="info-label">Reg Number</span><span class="info-value">${s.reg_number}</span></div>
    <div class="info-row"><span class="info-label">Email</span><span class="info-value">${s.email}</span></div>
    <div class="info-row"><span class="info-label">Phone</span><span class="info-value">${s.phone || 'N/A'}</span></div>
    <div class="info-row"><span class="info-label">Gender</span><span class="info-value">${s.gender || 'N/A'}</span></div>
    <div class="section-divider">Academic Information</div>
    <div class="info-row"><span class="info-label">Department</span><span class="info-value">${s.department}</span></div>
    <div class="info-row"><span class="info-label">Level</span><span class="info-value">${s.level}</span></div>
    <div class="info-row"><span class="info-label">Session</span><span class="info-value">${s.session || 'N/A'}</span></div>
    <div class="section-divider">Fee Summary</div>
    <div class="info-row"><span class="info-label">Total Billed</span><span class="info-value">${fmt(s.total_billed)}</span></div>
    <div class="info-row"><span class="info-label">Amount Paid</span><span class="info-value" style="color:#16a34a;">${fmt(s.total_paid)}</span></div>
    <div class="info-row"><span class="info-label">Outstanding</span><span class="info-value" style="color:#dc2626;">${fmt(s.total_outstanding)}</span></div>
  `;
  document.getElementById('viewFeesLink').href = `payments.php?student_id=${s.id}`;
  document.getElementById('studentModal').classList.add('open');
}

function closeModal() {
  document.getElementById('studentModal').classList.remove('open');
}

document.getElementById('studentModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>