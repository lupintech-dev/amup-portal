<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'All Students';

// Delete student
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM students WHERE id=$did");
    redirect('students.php');
}

// Search
$search = sanitize($conn, $_GET['search'] ?? '');
$filter = sanitize($conn, $_GET['filter'] ?? 'all');

$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (full_name LIKE '%$search%' 
                OR reg_number LIKE '%$search%' 
                OR email LIKE '%$search%'
                OR department LIKE '%$search%'
                OR phone LIKE '%$search%')";
}
if ($filter !== 'all') {
    $where .= " AND status='$filter'";
}

$students = $conn->query("SELECT * FROM students $where ORDER BY created_at DESC");
$total    = $conn->query("SELECT COUNT(*) c FROM students $where")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <h1>All Students</h1>
      <p><?= $total ?> student(s) found</p>
    </div>
    <a href="add_student.php" class="btn btn-primary">
      <i class="fas fa-user-plus"></i> Add Student
    </a>
  </div>

  <!-- Search & Filter Bar -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px;">
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        
        <!-- Search Input -->
        <div style="flex:1;min-width:250px;">
          <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">
            Search
          </label>
          <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   class="form-control" style="padding-left:36px;"
                   placeholder="Name, Reg Number, Email, Phone...">
          </div>
        </div>

        <!-- Filter by Status -->
        <div style="min-width:160px;">
          <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:6px;">
            Status
          </label>
          <select name="filter" class="form-control">
            <option value="all"    <?= $filter==='all'       ?'selected':'' ?>>All Students</option>
            <option value="active" <?= $filter==='active'    ?'selected':'' ?>>Active</option>
            <option value="suspended" <?= $filter==='suspended'?'selected':'' ?>>Suspended</option>
            <option value="graduated" <?= $filter==='graduated'?'selected':'' ?>>Graduated</option>
          </select>
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Search
          </button>
          <a href="students.php" class="btn btn-outline">
            <i class="fas fa-times"></i> Clear
          </a>
        </div>

      </form>
    </div>
  </div>

  <!-- Results -->
  <?php if ($search): ?>
  <div style="margin-bottom:12px;font-size:13px;color:#6b7280;">
    Showing results for: <strong style="color:#3d0a1a;">"<?= htmlspecialchars($search) ?>"</strong>
    — <?= $total ?> result(s) found
    <a href="students.php" style="margin-left:8px;color:#dc2626;font-size:12px;">Clear search</a>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-users"></i> Students List</h3>
      <span style="font-size:13px;color:#6b7280;"><?= $total ?> total</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($total == 0): ?>
        <div style="text-align:center;padding:48px;color:#9ca3af;">
          <i class="fas fa-search" style="font-size:48px;margin-bottom:16px;display:block;"></i>
          <p style="font-size:16px;">No students found<?= $search ? " for \"$search\"" : '' ?></p>
          <?php if ($search): ?>
            <a href="students.php" class="btn btn-outline" style="margin-top:12px;">View All Students</a>
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
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($s = $students->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td>
              <div style="font-weight:600;color:#111;"><?= htmlspecialchars($s['full_name']) ?></div>
              <div style="font-size:12px;color:#9ca3af;"><?= htmlspecialchars($s['email']) ?></div>
            </td>
            <td><strong style="color:#3d0a1a;"><?= $s['reg_number'] ?></strong></td>
            <td><?= htmlspecialchars($s['department']) ?></td>
            <td><?= $s['level'] ?></td>
            <td><?= $s['phone'] ?: '—' ?></td>
            <td>
              <span class="badge badge-<?= $s['status']==='active'?'success':($s['status']==='suspended'?'danger':'info') ?>">
                <?= ucfirst($s['status']) ?>
              </span>
            </td>
            <td style="display:flex;gap:6px;">
              <a href="view_student.php?id=<?= $s['id'] ?>" 
                 class="btn btn-sm btn-outline" title="View">
                <i class="fas fa-eye"></i>
              </a>
              <a href="students.php?delete=<?= $s['id'] ?>" 
                 class="btn btn-sm btn-danger" title="Delete"
                 onclick="return confirm('Delete <?= htmlspecialchars($s['full_name']) ?>?')">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>