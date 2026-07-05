<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Graduated Students';

// Search & Filter
$search  = sanitize($conn, $_GET['search']  ?? '');
$dept    = sanitize($conn, $_GET['dept']    ?? '');
$session = sanitize($conn, $_GET['session'] ?? '');

$where = "WHERE status='graduated'";
if ($search) {
    $where .= " AND (full_name LIKE '%$search%'
                OR reg_number LIKE '%$search%'
                OR email LIKE '%$search%'
                OR phone LIKE '%$search%')";
}
if ($dept)    $where .= " AND department='$dept'";
if ($session) $where .= " AND graduation_session='$session'";

$students = $conn->query("SELECT * FROM students $where ORDER BY graduated_at DESC");
$total    = $conn->query("SELECT COUNT(*) c FROM students $where")->fetch_assoc()['c'];

// For filter dropdowns — all departments and sessions that have graduates
$deptList    = $conn->query("SELECT DISTINCT department FROM students WHERE status='graduated' ORDER BY department");
$sessionList = $conn->query("SELECT DISTINCT graduation_session FROM students WHERE status='graduated' AND graduation_session IS NOT NULL ORDER BY graduation_session DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Graduated Students — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">

  <!-- Page Header -->
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <div>
      <h1 style="display:flex;align-items:center;gap:10px;">
        <i class="fas fa-graduation-cap" style="color:#3d0a1a;"></i> Graduated Students
      </h1>
      <p style="color:#6b7280;margin-top:4px;"><?= $total ?> graduate(s) found</p>
    </div>
  </div>

  <!-- Stats Row -->
  <?php
  $totalGrads   = $conn->query("SELECT COUNT(*) c FROM students WHERE status='graduated'")->fetch_assoc()['c'];
  $totalSessions = $conn->query("SELECT COUNT(DISTINCT graduation_session) c FROM students WHERE status='graduated' AND graduation_session IS NOT NULL")->fetch_assoc()['c'];
  $totalDepts    = $conn->query("SELECT COUNT(DISTINCT department) c FROM students WHERE status='graduated'")->fetch_assoc()['c'];
  ?>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="stat-card">
      <div class="stat-icon" style="background:#f0fdf4;"><i class="fas fa-user-graduate" style="color:#16a34a;"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalGrads ?></div>
        <div class="stat-label">Total Graduates</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#eff6ff;"><i class="fas fa-calendar-alt" style="color:#1d4ed8;"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalSessions ?></div>
        <div class="stat-label">Graduation Sessions</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fdf4ff;"><i class="fas fa-building" style="color:#7e22ce;"></i></div>
      <div class="stat-info">
        <div class="stat-number"><?= $totalDepts ?></div>
        <div class="stat-label">Departments</div>
      </div>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px;">
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

        <!-- Search -->
        <div style="flex:1;min-width:220px;">
          <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">Search</label>
          <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   class="form-control" style="padding-left:36px;"
                   placeholder="Name, Reg Number, Email...">
          </div>
        </div>

        <!-- Department -->
        <div style="min-width:180px;">
          <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">Department</label>
          <select name="dept" class="form-control">
            <option value="">All Departments</option>
            <?php while($d = $deptList->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($d['department']) ?>"
              <?= $dept === $d['department'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['department']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Session -->
        <div style="min-width:160px;">
          <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">Session</label>
          <select name="session" class="form-control">
            <option value="">All Sessions</option>
            <?php while($s = $sessionList->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($s['graduation_session']) ?>"
              <?= $session === $s['graduation_session'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['graduation_session']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Filter
          </button>
          <a href="graduated_students.php" class="btn btn-outline">
            <i class="fas fa-times"></i> Clear
          </a>
        </div>

      </form>
    </div>
  </div>

  <!-- Active filters display -->
  <?php if ($search || $dept || $session): ?>
  <div style="margin-bottom:12px;font-size:13px;color:#6b7280;">
    Filtering by:
    <?php if ($search): ?> <strong style="color:#3d0a1a;">"<?= htmlspecialchars($search) ?>"</strong><?php endif; ?>
    <?php if ($dept):   ?> &bull; Dept: <strong style="color:#3d0a1a;"><?= htmlspecialchars($dept) ?></strong><?php endif; ?>
    <?php if ($session):?> &bull; Session: <strong style="color:#3d0a1a;"><?= htmlspecialchars($session) ?></strong><?php endif; ?>
    — <?= $total ?> result(s)
    <a href="graduated_students.php" style="margin-left:8px;color:#dc2626;font-size:12px;">Clear all</a>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-graduation-cap"></i> Graduates List</h3>
      <span style="font-size:13px;color:#6b7280;"><?= $total ?> total</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($total == 0): ?>
        <div style="text-align:center;padding:56px;color:#9ca3af;">
          <i class="fas fa-graduation-cap" style="font-size:48px;margin-bottom:16px;display:block;opacity:0.3;"></i>
          <p style="font-size:16px;">No graduates found<?= $search ? " for \"$search\"" : '' ?></p>
          <?php if ($search || $dept || $session): ?>
            <a href="graduated_students.php" class="btn btn-outline" style="margin-top:12px;">View All Graduates</a>
          <?php else: ?>
            <p style="font-size:13px;margin-top:8px;">
              Go to a student's profile → Status tab → Set status to <strong>Graduated</strong> and pick a session.
            </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Reg Number</th>
            <th>Department</th>
            <th>Level</th>
            <th>Session</th>
            <th>Graduated On</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($s = $students->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <?php if (!empty($s['photo'])): ?>
                  <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($s['photo']) ?>"
                       style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid #7B1C3E;" alt="">
                <?php else: ?>
                  <div style="width:34px;height:34px;border-radius:50%;background:#7B1C3E;
                              display:flex;align-items:center;justify-content:center;
                              color:#fff;font-weight:700;font-size:14px;flex-shrink:0;">
                    <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:600;color:#111;"><?= htmlspecialchars($s['full_name']) ?></div>
                  <div style="font-size:12px;color:#9ca3af;"><?= htmlspecialchars($s['email']) ?></div>
                </div>
              </div>
            </td>
            <td><strong style="color:#3d0a1a;"><?= $s['reg_number'] ?></strong></td>
            <td><?= htmlspecialchars($s['department']) ?></td>
            <td><?= $s['level'] ?></td>
            <td>
              <?php if (!empty($s['graduation_session'])): ?>
              <span style="background:#dcfce7;color:#166534;padding:3px 10px;
                           border-radius:20px;font-size:12px;font-weight:600;">
                🎓 <?= htmlspecialchars($s['graduation_session']) ?>
              </span>
              <?php else: ?>
                <span style="color:#9ca3af;">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:#6b7280;">
              <?= !empty($s['graduated_at']) ? date('d M Y', strtotime($s['graduated_at'])) : '—' ?>
            </td>
            <td>
              <a href="view_student.php?id=<?= $s['id'] ?>"
                 class="btn btn-sm btn-outline" title="View Full Profile">
                <i class="fas fa-eye"></i> View Profile
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

</div><!-- end main-content -->
</body>
</html>