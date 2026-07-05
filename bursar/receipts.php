<?php
require_once '../includes/config.php';
if (!isset($_SESSION['bursar_id'])) {
    header("Location: login.php"); exit();
}

$bursar_name = $_SESSION['bursar_name'];

// Fetch all paid fee records with student info
$search    = sanitize($conn, $_GET['search'] ?? '');
$s_session = sanitize($conn, $_GET['session'] ?? '');

$where = "WHERE f.status = 'paid'";
if ($search)    $where .= " AND (s.full_name LIKE '%$search%' OR s.reg_number LIKE '%$search%' OR f.receipt_no LIKE '%$search%')";
if ($s_session) $where .= " AND f.session = '$s_session'";

$records = $conn->query("
    SELECT f.*, s.full_name, s.reg_number, s.department, s.level
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
    ORDER BY f.paid_date DESC, f.id DESC
");

$sessions = $conn->query("SELECT DISTINCT session FROM fees ORDER BY session DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Receipts — AMUP Bursar</title>
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

/* Receipt Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden;}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;padding:20px 28px;border-top:1px solid #f0eaec;}

/* Receipt layout */
.receipt{padding:36px 36px 24px;}
.receipt-header{text-align:center;margin-bottom:24px;padding-bottom:20px;border-bottom:2px dashed #e5e7eb;}
.receipt-header img{width:60px;margin-bottom:10px;}
.receipt-header h2{font-family:'Playfair Display',serif;color:var(--maroon);font-size:1.3rem;}
.receipt-header p{font-size:12px;color:var(--muted);}
.receipt-title{text-align:center;background:var(--maroon);color:#fff;padding:8px;border-radius:8px;font-weight:700;font-size:13px;margin-bottom:20px;letter-spacing:1px;}
.receipt-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5f0f2;font-size:13px;}
.receipt-row:last-child{border:none;}
.receipt-label{color:var(--muted);}
.receipt-value{font-weight:600;text-align:right;}
.receipt-total{background:#f5f0f2;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;margin-top:16px;}
.receipt-total span{font-size:13px;color:var(--muted);}
.receipt-total strong{font-size:1.2rem;color:var(--maroon);font-weight:700;}
.receipt-footer{text-align:center;margin-top:20px;font-size:11px;color:var(--muted);padding-top:16px;border-top:1px dashed #e5e7eb;}

@media print {
  body * { visibility: hidden; }
  #printArea, #printArea * { visibility: visible; }
  #printArea { position: fixed; top: 0; left: 0; width: 100%; }
  .modal-footer { display: none !important; }
}
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
    <a href="record_payment.php" class="nav-link"><i class="fas fa-plus-circle"></i> Record Payment</a>
    <div class="nav-section">Reports</div>
    <a href="receipts.php" class="nav-link active"><i class="fas fa-file-invoice"></i> Receipts</a>
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
      <h2>Receipts</h2>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="badge-user">
      <div class="av"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
      <?= htmlspecialchars($bursar_name) ?>
    </div>
  </div>

  <div class="content">

    <form method="GET" class="filter-bar">
      <input type="text" name="search" placeholder="Search by student name, reg number or receipt no..." value="<?= htmlspecialchars($search) ?>">
      <select name="session">
        <option value="">All Sessions</option>
        <?php while($row = $sessions->fetch_assoc()): ?>
          <option value="<?= $row['session'] ?>" <?= $s_session==$row['session']?'selected':'' ?>><?= $row['session'] ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
      <a href="receipts.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
      <div class="table-header">
        <h3>Paid Fee Receipts</h3>
        <span style="font-size:13px;color:var(--muted);"><?= $records->num_rows ?> record(s)</span>
      </div>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Reg Number</th>
            <th>Fee Type</th>
            <th>Amount Paid</th>
            <th>Receipt No</th>
            <th>Paid Date</th>
            <th>Session</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        if ($records->num_rows === 0):
        ?>
          <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:40px;">No paid records found.</td></tr>
        <?php else: while($r = $records->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
            <td style="font-family:monospace;font-size:12px;"><?= $r['reg_number'] ?></td>
            <td><?= $r['fee_type'] ?></td>
            <td style="color:#16a34a;font-weight:600;">₦<?= number_format($r['amount_paid'],2) ?></td>
            <td style="font-family:monospace;font-size:12px;"><?= $r['receipt_no'] ?: '<span style="color:var(--muted);">N/A</span>' ?></td>
            <td><?= $r['paid_date'] ? date('d M Y', strtotime($r['paid_date'])) : 'N/A' ?></td>
            <td><?= $r['session'] ?></td>
            <td>
              <button class="btn btn-primary btn-sm" onclick="openReceipt(<?= htmlspecialchars(json_encode($r)) ?>)">
                <i class="fas fa-print"></i> View
              </button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receiptModal">
  <div class="modal" id="printArea">
    <div class="receipt" id="receiptContent"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Close</button>
      <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Receipt</button>
    </div>
  </div>
</div>

<script>
function openReceipt(r) {
  const fmt = n => '₦' + parseFloat(n).toLocaleString('en-NG', {minimumFractionDigits:2});
  const paidDate = r.paid_date ? new Date(r.paid_date).toLocaleDateString('en-GB', {day:'2-digit',month:'long',year:'numeric'}) : 'N/A';

  document.getElementById('receiptContent').innerHTML = `
    <div class="receipt-header">
      <img src="../assets/img/logo.png" alt="AMUP">
      <h2>Ave Maria University Piyanko</h2>
      <p>Official Fee Payment Receipt</p>
    </div>
    <div class="receipt-title">PAYMENT RECEIPT</div>
    <div class="receipt-row"><span class="receipt-label">Receipt No</span><span class="receipt-value">${r.receipt_no || 'N/A'}</span></div>
    <div class="receipt-row"><span class="receipt-label">Student Name</span><span class="receipt-value">${r.full_name}</span></div>
    <div class="receipt-row"><span class="receipt-label">Reg Number</span><span class="receipt-value">${r.reg_number}</span></div>
    <div class="receipt-row"><span class="receipt-label">Department</span><span class="receipt-value">${r.department}</span></div>
    <div class="receipt-row"><span class="receipt-label">Level</span><span class="receipt-value">${r.level}</span></div>
    <div class="receipt-row"><span class="receipt-label">Fee Type</span><span class="receipt-value">${r.fee_type}</span></div>
    <div class="receipt-row"><span class="receipt-label">Session</span><span class="receipt-value">${r.session} / ${r.semester}</span></div>
    <div class="receipt-row"><span class="receipt-label">Date Paid</span><span class="receipt-value">${paidDate}</span></div>
    ${r.remark ? `<div class="receipt-row"><span class="receipt-label">Remark</span><span class="receipt-value">${r.remark}</span></div>` : ''}
    <div class="receipt-total">
      <span>Amount Paid</span>
      <strong>${fmt(r.amount_paid)}</strong>
    </div>
    <div class="receipt-footer">
      This receipt is computer generated and valid without signature.<br>
      © ${new Date().getFullYear()} Ave Maria University Piyanko — Bursar's Office
    </div>
  `;
  document.getElementById('receiptModal').classList.add('open');
}

function closeModal() {
  document.getElementById('receiptModal').classList.remove('open');
}

document.getElementById('receiptModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>