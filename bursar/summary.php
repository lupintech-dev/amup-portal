<?php
require_once '../includes/config.php';
if (!isset($_SESSION['bursar_id'])) {
    header("Location: login.php"); exit();
}

$bursar_name = $_SESSION['bursar_name'];

// Filters
$s_session = sanitize($conn, $_GET['session'] ?? '');
$s_dept    = sanitize($conn, $_GET['department'] ?? '');

$where = "WHERE 1=1";
if ($s_session) $where .= " AND f.session = '$s_session'";
if ($s_dept)    $where .= " AND s.department = '$s_dept'";

// Overall totals
$totals = $conn->query("
    SELECT
        COALESCE(SUM(f.amount),0)       AS total_billed,
        COALESCE(SUM(f.amount_paid),0)  AS total_collected,
        COALESCE(SUM(f.amount - f.amount_paid),0) AS total_outstanding,
        COUNT(*) AS total_records,
        SUM(f.status='paid')    AS paid_count,
        SUM(f.status='partial') AS partial_count,
        SUM(f.status='unpaid')  AS unpaid_count
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
")->fetch_assoc();

// By fee type
$by_type = $conn->query("
    SELECT
        f.fee_type,
        COALESCE(SUM(f.amount),0)      AS billed,
        COALESCE(SUM(f.amount_paid),0) AS collected,
        COALESCE(SUM(f.amount - f.amount_paid),0) AS outstanding,
        COUNT(*) AS records
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
    GROUP BY f.fee_type
    ORDER BY collected DESC
");

// By department
$by_dept = $conn->query("
    SELECT
        s.department,
        COALESCE(SUM(f.amount),0)      AS billed,
        COALESCE(SUM(f.amount_paid),0) AS collected,
        COALESCE(SUM(f.amount - f.amount_paid),0) AS outstanding,
        COUNT(DISTINCT s.id) AS students
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
    GROUP BY s.department
    ORDER BY collected DESC
");

// By student
$by_student = $conn->query("
    SELECT
        s.full_name, s.reg_number, s.department, s.level,
        COALESCE(SUM(f.amount),0)      AS billed,
        COALESCE(SUM(f.amount_paid),0) AS collected,
        COALESCE(SUM(f.amount - f.amount_paid),0) AS outstanding
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
    GROUP BY s.id
    ORDER BY outstanding DESC
");

// Sessions & departments for filters
$sessions = $conn->query("SELECT DISTINCT session FROM fees ORDER BY session DESC");
$depts    = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Summary Report — AMUP Bursar</title>
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
.topbar-right{display:flex;align-items:center;gap:12px;}
.badge-user{display:flex;align-items:center;gap:8px;background:var(--bg);padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;color:var(--maroon);}
.badge-user .av{width:30px;height:30px;border-radius:50%;background:var(--maroon);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;}
.content{padding:28px 32px;flex:1;}

.btn{padding:9px 18px;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,var(--maroon),var(--maroon2));color:#fff;}
.btn-secondary{background:#f3f4f6;color:var(--text);}
.btn-success{background:#16a34a;color:#fff;}
.btn-sm{padding:6px 12px;font-size:12px;}

.filter-bar{background:var(--white);border-radius:14px;padding:18px 24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:24px;border:1px solid rgba(61,10,26,0.06);}
.filter-bar select{padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-family:inherit;outline:none;color:var(--text);}

/* Stat Cards */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:var(--white);border-radius:16px;padding:20px;border:1px solid rgba(61,10,26,0.06);box-shadow:0 2px 12px rgba(61,10,26,0.05);}
.stat-label{font-size:11px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
.stat-value{font-size:1.4rem;font-weight:700;color:var(--maroon);}
.stat-sub{font-size:12px;color:var(--muted);margin-top:4px;}

/* Progress bar */
.progress-wrap{margin-top:10px;}
.progress-bar{height:6px;background:#f0eaec;border-radius:4px;overflow:hidden;}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--maroon),var(--maroon2));border-radius:4px;transition:width .6s;}

/* Tables */
.section{margin-bottom:28px;}
.section-title{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--maroon);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.table-wrap{background:var(--white);border-radius:16px;border:1px solid rgba(61,10,26,0.06);overflow:hidden;box-shadow:0 2px 12px rgba(61,10,26,0.05);}
table{width:100%;border-collapse:collapse;}
th{background:#faf6f7;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid #f0eaec;}
td{padding:12px 16px;font-size:13px;border-bottom:1px solid #faf6f7;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fdf9fa;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-green{background:#dcfce7;color:#16a34a;}
.badge-red{background:#fee2e2;color:#dc2626;}

@media print {
  .sidebar,.topbar,.filter-bar,.no-print{display:none!important;}
  .main{margin-left:0!important;}
  .content{padding:0!important;}
  body{background:#fff!important;}
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
    <a href="receipts.php" class="nav-link"><i class="fas fa-file-invoice"></i> Receipts</a>
    <a href="summary.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Summary Report</a>
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
      <h2>Summary Report</h2>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="topbar-right">
      <button class="btn btn-success no-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
      <div class="badge-user">
        <div class="av"><?= strtoupper(substr($bursar_name,0,1)) ?></div>
        <?= htmlspecialchars($bursar_name) ?>
      </div>
    </div>
  </div>

  <div class="content">

    <!-- Filters -->
    <form method="GET" class="filter-bar no-print">
      <select name="session">
        <option value="">All Sessions</option>
        <?php while($row = $sessions->fetch_assoc()): ?>
          <option value="<?= $row['session'] ?>" <?= $s_session==$row['session']?'selected':'' ?>><?= $row['session'] ?></option>
        <?php endwhile; ?>
      </select>
      <select name="department">
        <option value="">All Departments</option>
        <?php while($row = $depts->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($row['department']) ?>" <?= $s_dept==$row['department']?'selected':'' ?>><?= $row['department'] ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
      <a href="summary.php" class="btn btn-secondary">Reset</a>
    </form>

    <!-- Overall Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-label">Total Billed</div>
        <div class="stat-value">₦<?= number_format($totals['total_billed'],2) ?></div>
        <div class="stat-sub"><?= $totals['total_records'] ?> fee records</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Collected</div>
        <div class="stat-value" style="color:#16a34a;">₦<?= number_format($totals['total_collected'],2) ?></div>
        <?php $pct = $totals['total_billed'] > 0 ? round(($totals['total_collected']/$totals['total_billed'])*100) : 0; ?>
        <div class="progress-wrap">
          <div style="font-size:11px;color:var(--muted);margin-bottom:4px;"><?= $pct ?>% collected</div>
          <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%;"></div></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Outstanding</div>
        <div class="stat-value" style="color:#dc2626;">₦<?= number_format($totals['total_outstanding'],2) ?></div>
        <div class="stat-sub"><?= $totals['unpaid_count'] ?> unpaid · <?= $totals['partial_count'] ?> partial</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Fully Paid Records</div>
        <div class="stat-value"><?= $totals['paid_count'] ?></div>
        <div class="stat-sub">out of <?= $totals['total_records'] ?> total records</div>
      </div>
    </div>

    <!-- By Fee Type -->
    <div class="section">
      <div class="section-title"><i class="fas fa-tags" style="color:var(--gold);"></i> Breakdown by Fee Type</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Fee Type</th>
              <th>Records</th>
              <th>Total Billed</th>
              <th>Collected</th>
              <th>Outstanding</th>
              <th>Collection %</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row = $by_type->fetch_assoc()):
            $p = $row['billed'] > 0 ? round(($row['collected']/$row['billed'])*100) : 0;
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($row['fee_type']) ?></strong></td>
              <td><?= $row['records'] ?></td>
              <td>₦<?= number_format($row['billed'],2) ?></td>
              <td style="color:#16a34a;font-weight:600;">₦<?= number_format($row['collected'],2) ?></td>
              <td style="color:#dc2626;font-weight:600;">₦<?= number_format($row['outstanding'],2) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="progress-bar" style="width:80px;"><div class="progress-fill" style="width:<?= $p ?>%;"></div></div>
                  <span style="font-size:12px;color:var(--muted);"><?= $p ?>%</span>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- By Department -->
    <div class="section">
      <div class="section-title"><i class="fas fa-building" style="color:var(--gold);"></i> Breakdown by Department</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Department</th>
              <th>Students</th>
              <th>Total Billed</th>
              <th>Collected</th>
              <th>Outstanding</th>
              <th>Collection %</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row = $by_dept->fetch_assoc()):
            $p = $row['billed'] > 0 ? round(($row['collected']/$row['billed'])*100) : 0;
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($row['department']) ?></strong></td>
              <td><?= $row['students'] ?></td>
              <td>₦<?= number_format($row['billed'],2) ?></td>
              <td style="color:#16a34a;font-weight:600;">₦<?= number_format($row['collected'],2) ?></td>
              <td style="color:#dc2626;font-weight:600;">₦<?= number_format($row['outstanding'],2) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="progress-bar" style="width:80px;"><div class="progress-fill" style="width:<?= $p ?>%;"></div></div>
                  <span style="font-size:12px;color:var(--muted);"><?= $p ?>%</span>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- By Student -->
    <div class="section">
      <div class="section-title"><i class="fas fa-user-graduate" style="color:var(--gold);"></i> Breakdown by Student</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Reg Number</th>
              <th>Department</th>
              <th>Level</th>
              <th>Total Billed</th>
              <th>Collected</th>
              <th>Outstanding</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row = $by_student->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
              <td style="font-family:monospace;font-size:12px;"><?= $row['reg_number'] ?></td>
              <td><?= htmlspecialchars($row['department']) ?></td>
              <td><?= $row['level'] ?></td>
              <td>₦<?= number_format($row['billed'],2) ?></td>
              <td style="color:#16a34a;font-weight:600;">₦<?= number_format($row['collected'],2) ?></td>
              <td style="color:#dc2626;font-weight:600;">₦<?= number_format($row['outstanding'],2) ?></td>
              <td>
                <?php if($row['outstanding'] <= 0): ?>
                  <span class="badge badge-green">Cleared</span>
                <?php else: ?>
                  <span class="badge badge-red">Owing</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</body>
</html>