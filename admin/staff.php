<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Staff Management';

$success = ''; $error = '';

// Delete
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM staff WHERE id=".(int)$_GET['delete']);
    redirect('staff.php');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $tid     = (int)$_GET['toggle'];
    $current = $conn->query("SELECT status FROM staff WHERE id=$tid")->fetch_assoc()['status'];
    $new     = $current === 'active' ? 'inactive' : 'active';
    $conn->query("UPDATE staff SET status='$new' WHERE id=$tid");
    redirect('staff.php');
}

// Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize($conn, $_POST['full_name']  ?? '');
    $email      = sanitize($conn, $_POST['email']      ?? '');
    $department = sanitize($conn, $_POST['department'] ?? '');
    $role       = sanitize($conn, $_POST['role']       ?? 'Lecturer');
    $phone      = sanitize($conn, $_POST['phone']      ?? '');

    if (!$full_name || !$email) {
        $error = 'Please fill all required fields.';
    } else {
        $check = $conn->prepare("SELECT id FROM staff WHERE email=?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO staff (full_name, email, department, role, phone) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss', $full_name, $email, $department, $role, $phone);
            $stmt->execute()
                ? $success = "Staff record created for $full_name successfully!"
                : $error   = 'Failed to create staff record.';
        }
    }
}

// Search
$search = sanitize($conn, $_GET['search'] ?? '');
$where  = $search ? "WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR department LIKE '%$search%' OR role LIKE '%$search%'" : "";
$staffList = $conn->query("SELECT * FROM staff $where ORDER BY department, full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — AMUP Admin</title>
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
      <h1>Staff Management</h1>
      <p>Manage lecturers and staff records</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>

  <!-- Add Staff Form -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3><i class="fas fa-user-plus"></i> Add New Staff Record</h3></div>
    <div class="card-body">
      <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>Full Name <span style="color:red;">*</span></label>
            <input type="text" name="full_name" class="form-control"
                   placeholder="Dr. Jane Doe" required>
          </div>
          <div class="form-group">
            <label>Email <span style="color:red;">*</span></label>
            <input type="email" name="email" class="form-control"
                   placeholder="staff@amup.edu.ng" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control"
                   placeholder="08012345678">
          </div>
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" class="form-control"
                   list="dept-list" placeholder="e.g. Computer Science">
            <datalist id="dept-list">
              <option value="Computer Science">
              <option value="Software Engineering">
              <option value="Cyber Security">
              <option value="Computer Engineering">
              <option value="Accounting">
              <option value="Business Administration">
              <option value="Nursing Science">
              <option value="Medicine & Surgery">
              <option value="Law">
              <option value="Mass Communication">
              <option value="Electrical Engineering">
              <option value="Civil Engineering">
              <option value="Economics">
              <option value="Pharmacy">
              <option value="Biochemistry">
              <option value="Microbiology">
              <option value="Public Health">
            </datalist>
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
              <option value="Lecturer">Lecturer</option>
              <option value="Senior Lecturer">Senior Lecturer</option>
              <option value="Professor">Professor</option>
              <option value="Associate Professor">Associate Professor</option>
              <option value="Assistant Lecturer">Assistant Lecturer</option>
              <option value="Lab Technician">Lab Technician</option>
              <option value="Administrative Staff">Administrative Staff</option>
              <option value="Bursar">Bursar</option>
              <option value="Registrar">Registrar</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-user-plus"></i> Add Staff Record
        </button>
      </form>
    </div>
  </div>

  <!-- Search -->
  <div style="margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
      <div style="position:relative;flex:1;max-width:400px;">
        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               class="form-control" style="padding-left:36px;"
               placeholder="Search by name, email, department or role...">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <?php if ($search): ?>
        <a href="staff.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Staff List -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-user-tie"></i> All Staff Records</h3>
      <span style="font-size:13px;color:#6b7280;"><?= $staffList->num_rows ?> staff member(s)</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($staffList->num_rows === 0): ?>
        <div style="padding:48px;text-align:center;color:#9ca3af;">
          <i class="fas fa-user-tie" style="font-size:48px;margin-bottom:16px;display:block;"></i>
          <p style="font-size:16px;">
            <?= $search ? "No staff found for \"$search\"" : "No staff records yet." ?>
          </p>
          <?php if ($search): ?>
            <a href="staff.php" class="btn btn-outline" style="margin-top:12px;">View All Staff</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Role</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($s = $staffList->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td>
              <div style="font-weight:600;color:#111;">
                <?= htmlspecialchars($s['full_name']) ?>
              </div>
              <div style="font-size:12px;color:#9ca3af;"><?= $s['email'] ?></div>
            </td>
            <td>
              <span class="badge badge-info"><?= htmlspecialchars($s['role']) ?></span>
            </td>
            <td><?= htmlspecialchars($s['department'] ?: '—') ?></td>
            <td><?= $s['phone'] ?: '—' ?></td>
            <td>
              <span class="badge badge-<?= $s['status']==='active'?'success':'danger' ?>">
                <?= ucfirst($s['status']) ?>
              </span>
            </td>
            <td style="display:flex;gap:6px;">
              <a href="staff.php?toggle=<?= $s['id'] ?>"
                 class="btn btn-sm btn-outline"
                 title="<?= $s['status']==='active'?'Deactivate':'Activate' ?>">
                <i class="fas fa-toggle-<?= $s['status']==='active'?'on':'off' ?>"></i>
              </a>
              <a href="staff.php?delete=<?= $s['id'] ?>"
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