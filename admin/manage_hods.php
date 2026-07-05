<?php
// admin/manage_hods.php — place inside your existing admin folder
// Include your existing admin auth check at the top, e.g.:
// require_once 'auth.php';
require_once '../config/db.php';
session_start();

$message = '';
$error   = '';

// ── Handle actions ────────────────────────────────────────────────────────────

// Add HOD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name   = trim($_POST['full_name']);
    $email  = trim($_POST['email']);
    $dept   = trim($_POST['department']);
    $phone  = trim($_POST['phone'] ?? '');
    $pass   = $_POST['password'];

    if ($name && $email && $dept && $pass) {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO hods (full_name, email, password, department, phone) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $name, $email, $hashed, $dept, $phone);
        if ($stmt->execute()) {
            $message = "HOD '{$name}' added successfully.";
        } else {
            $error = "Error: " . $conn->error;
        }
    } else {
        $error = "Please fill all required fields.";
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE hods SET status = IF(status='active','inactive','active') WHERE id=$id");
    header('Location: manage_hods.php');
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM hods WHERE id=$id");
    header('Location: manage_hods.php');
    exit;
}

// ── Fetch HODs ─────────────────────────────────────────────────────────────────
$hods = $conn->query("SELECT * FROM hods ORDER BY department, full_name")->fetch_all(MYSQLI_ASSOC);

// ── Fetch departments for dropdown (from students table) ──────────────────────
$depts = $conn->query("SELECT DISTINCT department FROM students ORDER BY department")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage HODs — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{--maroon:#6b0f2b;--maroon-d:#4a0a1e;--gold:#c9a84c;--bg:#f5f0f2;--white:#fff;--radius:10px;--muted:#888;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'DM Sans',sans-serif;background:var(--bg);padding:30px;color:#2d1a22;}

  h1{font-size:1.4rem;color:var(--maroon-d);margin-bottom:6px;}
  .sub{color:var(--muted);font-size:.85rem;margin-bottom:24px;}

  .msg{padding:10px 16px;border-radius:8px;font-size:.875rem;margin-bottom:18px;}
  .msg-ok{background:#e6f9ee;color:#1a7a40;border:1px solid #b0e8c0;}
  .msg-err{background:#fde8e8;color:#c0392b;border:1px solid #f0b0b0;}

  /* Add form */
  .add-card{background:var(--white);border-radius:var(--radius);padding:24px;margin-bottom:28px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
  .add-card h2{font-size:.95rem;font-weight:600;color:var(--maroon-d);margin-bottom:18px;}
  .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;}
  label{display:block;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#555;margin-bottom:5px;}
  input,select{width:100%;padding:9px 12px;border:1.5px solid #e0d0d5;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.875rem;outline:none;}
  input:focus,select:focus{border-color:var(--maroon);}
  .btn{padding:10px 20px;background:var(--maroon);color:var(--white);border:none;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:18px;}
  .btn:hover{background:var(--maroon-d);}

  /* Table */
  .table-card{background:var(--white);border-radius:var(--radius);box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;}
  .table-card h2{padding:16px 22px;border-bottom:1px solid #f0e8eb;font-size:.95rem;font-weight:600;color:var(--maroon-d);}
  table{width:100%;border-collapse:collapse;}
  thead tr{background:var(--maroon-d);}
  thead th{padding:10px 16px;text-align:left;font-size:.74rem;font-weight:600;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.04em;}
  tbody tr{border-bottom:1px solid #f5edf0;}
  tbody tr:hover{background:#fdf7f9;}
  tbody td{padding:11px 16px;font-size:.875rem;}

  .badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.74rem;font-weight:600;}
  .badge-active{background:#e6f9ee;color:#1a7a40;}
  .badge-inactive{background:#fde8e8;color:#c0392b;}

  .act-link{font-size:.8rem;font-weight:600;text-decoration:none;padding:4px 10px;border-radius:6px;display:inline-block;}
  .act-toggle{background:#fff3e0;color:#e67e22;}
  .act-delete{background:#fde8e8;color:#c0392b;margin-left:6px;}
  .act-link:hover{opacity:.8;}
</style>
</head>
<body>

<h1>🎓 Manage Heads of Department</h1>
<p class="sub">Add, activate/deactivate, or remove HOD accounts. HODs can only see students in their own department.</p>

<?php if ($message): ?><div class="msg msg-ok">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="msg msg-err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Add form -->
<div class="add-card">
  <h2>➕ Add New HOD</h2>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div>
        <label>Full Name *</label>
        <input type="text" name="full_name" placeholder="Dr. Jane Doe" required>
      </div>
      <div>
        <label>Email *</label>
        <input type="email" name="email" placeholder="hod.dept@amup.edu.ng" required>
      </div>
      <div>
        <label>Department *</label>
        <select name="department" required>
          <option value="">— Select —</option>
          <?php foreach ($depts as $d): ?>
            <option value="<?= htmlspecialchars($d['department']) ?>">
              <?= htmlspecialchars($d['department']) ?>
            </option>
          <?php endforeach; ?>
          <!-- Manual entry fallback -->
          <option value="__custom__">Other (type below)</option>
        </select>
      </div>
      <div>
        <label>Phone</label>
        <input type="text" name="phone" placeholder="+234...">
      </div>
      <div>
        <label>Password *</label>
        <input type="password" name="password" placeholder="Temporary password" required>
      </div>
    </div>
    <button type="submit" class="btn">Add HOD</button>
  </form>
</div>

<!-- HOD table -->
<div class="table-card">
  <h2>All HODs (<?= count($hods) ?>)</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Department</th>
        <th>Phone</th>
        <th>Status</th>
        <th>Added</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($hods): ?>
        <?php foreach ($hods as $i => $h): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><strong><?= htmlspecialchars($h['full_name']) ?></strong></td>
          <td><?= htmlspecialchars($h['email']) ?></td>
          <td><?= htmlspecialchars($h['department']) ?></td>
          <td><?= htmlspecialchars($h['phone'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $h['status'] ?>"><?= ucfirst($h['status']) ?></span></td>
          <td><?= date('d M Y', strtotime($h['created_at'])) ?></td>
          <td>
            <a href="?toggle=<?= $h['id'] ?>" class="act-link act-toggle"
               onclick="return confirm('Toggle status?')">
              <?= $h['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
            </a>
            <a href="?delete=<?= $h['id'] ?>" class="act-link act-delete"
               onclick="return confirm('Delete this HOD? This cannot be undone.')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">No HODs added yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
