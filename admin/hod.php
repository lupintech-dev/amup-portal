<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'HOD Management';

$success = ''; $error = '';

// Reset password
if (isset($_GET['reset'])) {
    $rid  = (int)$_GET['reset'];
    $hash = password_hash('HOD@1234', PASSWORD_DEFAULT);
    $conn->query("UPDATE hods SET password='$hash' WHERE id=$rid");
    $success = 'Password reset to HOD@1234 successfully!';
}

// Toggle status
if (isset($_GET['toggle'])) {
    $tid     = (int)$_GET['toggle'];
    $current = $conn->query("SELECT status FROM hods WHERE id=$tid")->fetch_assoc()['status'];
    $new     = $current === 'active' ? 'inactive' : 'active';
    $conn->query("UPDATE hods SET status='$new' WHERE id=$tid");
    redirect('hod.php');
}

// Delete
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM hods WHERE id=".(int)$_GET['delete']);
    redirect('hod.php');
}

// Add HOD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize($conn, $_POST['full_name']  ?? '');
    $email      = sanitize($conn, $_POST['email']      ?? '');
    $username   = sanitize($conn, $_POST['username']   ?? '');
    $department = sanitize($conn, $_POST['department'] ?? '');
    $phone      = sanitize($conn, $_POST['phone']      ?? '');
    $password   = $_POST['password'] ?? 'HOD@1234';

    if (!$full_name || !$email || !$username || !$department) {
        $error = 'Please fill all required fields.';
    } else {
        $check = $conn->prepare("SELECT id FROM hods WHERE email=? OR username=?");
        $check->bind_param('ss', $email, $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email or username already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO hods (full_name, email, username, password, department, phone) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssssss', $full_name, $email, $username, $hash, $department, $phone);
            $stmt->execute()
                ? $success = "HOD account created for $full_name. Username: $username | Password: $password"
                : $error   = 'Failed to create HOD account.';
        }
    }
}

$hods = $conn->query("SELECT * FROM hods ORDER BY department ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HOD Management — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>HOD Management</h1>
    <p>Manage Heads of Department accounts</p>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>

  <!-- Add HOD Form -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3><i class="fas fa-user-plus"></i> Add New HOD Account</h3></div>
    <div class="card-body">
      <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>Full Name <span style="color:red;">*</span></label>
            <input type="text" name="full_name" class="form-control"
                   placeholder="Dr. John Smith" required>
          </div>
          <div class="form-group">
            <label>Username <span style="color:red;">*</span></label>
            <input type="text" name="username" class="form-control"
                   placeholder="hod_cs" required>
          </div>
          <div class="form-group">
            <label>Email <span style="color:red;">*</span></label>
            <input type="email" name="email" class="form-control"
                   placeholder="hod@amup.edu.ng" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control"
                   placeholder="08012345678">
          </div>
          <div class="form-group">
            <label>Department <span style="color:red;">*</span></label>
            <input type="text" name="department" class="form-control"
                   list="dept-list" placeholder="e.g. Computer Science" required>
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
            <label>Default Password</label>
            <input type="text" name="password" class="form-control" value="HOD@1234">
            <small style="color:#9ca3af;">HOD should change after first login</small>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-user-plus"></i> Create HOD Account
        </button>
      </form>
    </div>
  </div>

  <!-- HOD List -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-chalkboard-teacher"></i> All HOD Accounts</h3>
      <span style="font-size:13px;color:#6b7280;"><?= $hods->num_rows ?> HOD(s)</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($hods->num_rows === 0): ?>
        <div style="padding:32px;text-align:center;color:#9ca3af;">
          <i class="fas fa-chalkboard-teacher" style="font-size:48px;margin-bottom:16px;display:block;"></i>
          No HOD accounts yet. Add one above.
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Username</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($h = $hods->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($h['full_name']) ?></div>
              <div style="font-size:12px;color:#9ca3af;"><?= $h['email'] ?></div>
            </td>
            <td>
             <code style="background:#f3f4f6;padding:2px 8px;border-radius:4px;">
  <?= $h['username'] ?? '—' ?>
</code>
            </td>
            <td><?= htmlspecialchars($h['department']) ?></td>
            <td><?= $h['phone'] ?: '—' ?></td>
            <td>
              <span class="badge badge-<?= $h['status']==='active'?'success':'danger' ?>">
                <?= ucfirst($h['status']) ?>
              </span>
            </td>
            <td><?= date('d M Y', strtotime($h['created_at'])) ?></td>
            <td style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="../hod/dashboard.php" target="_blank"
                 class="btn btn-sm btn-outline" title="View HOD Portal">
                <i class="fas fa-external-link-alt"></i>
              </a>
              <a href="hod.php?toggle=<?= $h['id'] ?>"
                 class="btn btn-sm btn-outline"
                 title="<?= $h['status']==='active'?'Deactivate':'Activate' ?>">
                <i class="fas fa-toggle-<?= $h['status']==='active'?'on':'off' ?>"></i>
              </a>
              <a href="hod.php?reset=<?= $h['id'] ?>"
                 class="btn btn-sm btn-outline" title="Reset Password"
                 onclick="return confirm('Reset password for <?= htmlspecialchars($h['full_name']) ?>?')">
                <i class="fas fa-key"></i>
              </a>
              <a href="hod.php?delete=<?= $h['id'] ?>"
                 class="btn btn-sm btn-danger" title="Delete"
                 onclick="return confirm('Delete <?= htmlspecialchars($h['full_name']) ?>?')">
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