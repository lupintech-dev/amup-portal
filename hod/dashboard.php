<?php
require_once '../includes/config.php';
require_once 'auth.php';

// Total students in this department
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE department = ?");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$totalStudents = $stmt->get_result()->fetch_assoc()['total'];

// Active students
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE department = ? AND status = 'active'");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$activeStudents = $stmt->get_result()->fetch_assoc()['total'];

// Results uploaded for this department
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT r.student_id) AS total
    FROM results r
    JOIN students s ON s.id = r.student_id
    WHERE s.department = ?
");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$resultsUploaded = $stmt->get_result()->fetch_assoc()['total'];

// Students eligible for exam (active + fully paid fees)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT s.id) AS total
    FROM students s
    JOIN fees f ON f.student_id = s.id
    WHERE s.department = ? AND s.status = 'active' AND f.status = 'paid'
");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$eligibleStudents = $stmt->get_result()->fetch_assoc()['total'];

// Graduated students
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE department = ? AND status = 'graduated'");
$stmt->bind_param('s', $hodDepartment);
$stmt->execute();
$graduatedStudents = $stmt->get_result()->fetch_assoc()['total'];

// Filters
$search       = trim($_GET['search'] ?? '');
$levelFilter  = $_GET['level'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where  = "WHERE s.department = ?";
$params = [$hodDepartment];
$types  = 's';

if ($search) {
    $where   .= " AND (s.full_name LIKE ? OR s.reg_number LIKE ? OR s.email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
if ($levelFilter) {
    $where   .= " AND s.level = ?";
    $params[] = $levelFilter;
    $types   .= 's';
}
if ($statusFilter) {
    $where   .= " AND s.status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

$stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(SUM(f.amount_paid), 0) AS paid_fees,
           COALESCE(SUM(CASE WHEN f.status != 'paid' THEN f.amount - f.amount_paid ELSE 0 END), 0) AS owed_fees
    FROM students s
    LEFT JOIN fees f ON f.student_id = s.id
    $where
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HOD Dashboard — <?= htmlspecialchars($hodDepartment) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --maroon:    #6b0f2b;
    --maroon-d:  #4a0a1e;
    --maroon-l:  #8b1a3a;
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
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w);
    background: var(--maroon-d);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 100;
    overflow-y: auto;
  }
  .sidebar-brand {
    padding: 22px 20px;
    border-bottom: 1px solid rgba(255,255,255,.1);
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .sidebar-brand img { width: 42px; border-radius: 6px; }
  .sidebar-brand-text .name { color: var(--white); font-weight: 600; font-size: .9rem; }
  .sidebar-brand-text .sub  { color: rgba(255,255,255,.45); font-size: .75rem; }
  .sidebar-section {
    padding: 16px 20px 6px;
    font-size: .68rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: rgba(255,255,255,.35);
    font-weight: 600;
  }
  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: rgba(255,255,255,.65);
    text-decoration: none;
    font-size: .88rem;
    transition: background .15s, color .15s;
  }
  .sidebar-link:hover,
  .sidebar-link.active {
    background: rgba(201,168,76,.15);
    color: var(--gold-l);
  }
  .sidebar-link .ico {
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .sidebar-bottom {
    margin-top: auto;
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,.08);
  }
  .hod-info { color: rgba(255,255,255,.75); font-size: .83rem; }
  .hod-info .dept { color: var(--gold); font-size: .75rem; margin-top: 2px; }
  .logout-btn {
    display: flex; align-items: center; gap: 8px;
    margin-top: 12px;
    color: rgba(255,255,255,.5);
    text-decoration: none;
    font-size: .83rem;
    transition: color .2s;
  }
  .logout-btn:hover { color: #ff9999; }

  /* ── Main ── */
  .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

  .topbar {
    background: var(--white);
    border-bottom: 1px solid #eedde3;
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .topbar-title h1 { font-size: 1.35rem; font-family: 'Playfair Display', serif; color: var(--maroon-d); }
  .topbar-title p  { color: var(--muted); font-size: .82rem; margin-top: 2px; }
  .dept-pill {
    background: var(--maroon); color: var(--white);
    padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 600;
  }

  .content { padding: 28px 32px; flex: 1; }

  /* ── Stat Cards ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
  }
  .stat-card {
    background: var(--white); border-radius: var(--radius);
    padding: 22px 24px; border-top: 3px solid var(--maroon);
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    text-decoration: none; color: inherit;
    transition: transform .15s, box-shadow .15s;
    cursor: pointer;
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,.1);
  }
  .stat-card .s-icon {
    width: 48px; height: 48px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-card .s-val { font-size: 1.55rem; font-weight: 700; color: var(--maroon-d); }
  .stat-card .s-lbl { font-size: .78rem; color: var(--muted); margin-top: 2px; }

  /* ── Table Card ── */
  .table-card {
    background: var(--white); border-radius: var(--radius);
    box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden;
  }
  .table-header {
    padding: 20px 24px; border-bottom: 1px solid #f0e8eb;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
  }
  .table-header h2 { font-size: 1rem; font-weight: 600; color: var(--maroon-d); }
  .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .filters input, .filters select {
    padding: 8px 12px; border: 1.5px solid #e0d0d5; border-radius: 8px;
    font-family: 'DM Sans', sans-serif; font-size: .85rem; outline: none;
    transition: border-color .2s;
  }
  .filters input:focus, .filters select:focus { border-color: var(--maroon); }
  .filters input { width: 220px; }
  .btn-filter {
    padding: 8px 16px; background: var(--maroon); color: var(--white);
    border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif;
    font-size: .85rem; font-weight: 600; cursor: pointer; transition: background .2s;
  }
  .btn-filter:hover { background: var(--maroon-d); }

  table { width: 100%; border-collapse: collapse; }
  thead tr { background: var(--maroon-d); }
  thead th {
    padding: 12px 16px; text-align: left; font-size: .78rem;
    font-weight: 600; color: rgba(255,255,255,.85);
    letter-spacing: .04em; text-transform: uppercase;
  }
  tbody tr { border-bottom: 1px solid #f5edf0; transition: background .15s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #fdf7f9; }
  tbody td { padding: 13px 16px; font-size: .875rem; }

  .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: .75rem; font-weight: 600; }
  .badge-active    { background: #e6f9ee; color: #1a7a40; }
  .badge-inactive  { background: #fde8e8; color: #c0392b; }
  .badge-suspended { background: #fff3cd; color: #856404; }
  .badge-graduated { background: #e8f0ff; color: #1a3a7a; }

  .btn-view {
    padding: 5px 12px; background: #f5edf0; color: var(--maroon);
    border: 1px solid #e0c0c8; border-radius: 6px; text-decoration: none;
    font-size: .8rem; font-weight: 600; transition: background .2s;
  }
  .btn-view:hover { background: #f0d5dc; }

  .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
  .empty-state .big { font-size: 3rem; margin-bottom: 10px; }
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
  <a href="dashboard.php" class="sidebar-link active">
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
  <a href="results.php" class="sidebar-link">
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
      <h1>HOD Dashboard</h1>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="dept-pill"><?= htmlspecialchars($hodDepartment) ?></div>
  </div>

  <div class="content">
    <div class="stats-grid">

      <a href="dashboard.php" class="stat-card">
        <div class="s-icon" style="background:#f0e8ff">
          <svg width="22" height="22" fill="none" stroke="#6b0f2b" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div><div class="s-val"><?= $totalStudents ?></div><div class="s-lbl">Total Students</div></div>
      </a>

      <a href="dashboard.php?status=active" class="stat-card">
        <div class="s-icon" style="background:#e6f9ee">
          <svg width="22" height="22" fill="none" stroke="#1a7a40" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div><div class="s-val"><?= $activeStudents ?></div><div class="s-lbl">Active Students</div></div>
      </a>

      <a href="results.php" class="stat-card">
        <div class="s-icon" style="background:#e6f5ff">
          <svg width="22" height="22" fill="none" stroke="#1a4a8a" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div><div class="s-val"><?= $resultsUploaded ?></div><div class="s-lbl">Results Uploaded</div></div>
      </a>

      <a href="dashboard.php?status=active" class="stat-card" style="border-top-color:#1a7a40">
        <div class="s-icon" style="background:#e6f9ee">
          <svg width="22" height="22" fill="none" stroke="#1a7a40" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        </div>
        <div><div class="s-val"><?= $eligibleStudents ?></div><div class="s-lbl">Eligible for Exam</div></div>
      </a>

    </div>

    <div class="table-card">
      <div class="table-header">
        <h2>Students — <?= htmlspecialchars($hodDepartment) ?> (<?= count($students) ?>)</h2>
        <form method="GET" class="filters">
          <input type="text" name="search" placeholder="Search name, reg, email…"
                 value="<?= htmlspecialchars($search) ?>">
          <select name="level">
            <option value="">All Levels</option>
            <?php foreach (['100L','200L','300L','400L','500L'] as $lvl): ?>
              <option value="<?= $lvl ?>" <?= $levelFilter === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status">
            <option value="">All Status</option>
            <option value="active"    <?= $statusFilter === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="inactive"  <?= $statusFilter === 'inactive'  ? 'selected' : '' ?>>Inactive</option>
            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="graduated" <?= $statusFilter === 'graduated' ? 'selected' : '' ?>>Graduated</option>
          </select>
          <button type="submit" class="btn-filter">Filter</button>
          <a href="dashboard.php" class="btn-filter" style="text-decoration:none;background:#888">Reset</a>
        </form>
      </div>

      <?php if ($students): ?>
      <table>
        <thead>
          <tr>
            <th>#</th><th>Name</th><th>Reg Number</th><th>Level</th>
            <th>Email</th><th>Fees Paid</th><th>Outstanding</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($s['reg_number']) ?></td>
            <td><?= htmlspecialchars($s['level']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td>₦<?= number_format($s['paid_fees'], 2) ?></td>
            <td><?= $s['owed_fees'] > 0 ? '₦'.number_format($s['owed_fees'],2) : '—' ?></td>
            <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
            <td><a href="student_detail.php?id=<?= $s['id'] ?>" class="btn-view">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty-state">
          <div class="big">—</div>
          <p>No students found<?= $search ? ' for "'.htmlspecialchars($search).'"' : '' ?>.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

</body>
</html>