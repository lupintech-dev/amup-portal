<?php
$base = '../';
require_once '../includes/config.php';
if (isAdminLoggedIn()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        redirect('dashboard.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:var(--maroon-dark);display:flex;align-items:center;justify-content:center;min-height:100vh;">

<div style="background:white;border-radius:16px;padding:48px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
  <div style="text-align:center;margin-bottom:32px;">
    <img src="../assets/img/logo.png" style="width:80px;margin-bottom:16px;" alt="AMUP">
    <h2 style="font-family:'Playfair Display',serif;color:var(--maroon-dark);">Admin Login</h2>
    <p style="color:#6b7280;font-size:14px;">Ave Maria University Piyanko</p>
  </div>

  <?php if ($error): ?>
    <div style="background:#fef2f2;color:#dc2626;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px;">
      <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="margin-bottom:16px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">USERNAME OR EMAIL</label>
      <input type="text" name="username" placeholder="admin" required
        style="width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;">
    </div>
    <div style="margin-bottom:24px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">PASSWORD</label>
      <input type="password" name="password" placeholder="••••••••" required
        style="width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;">
    </div>
    <button type="submit"
      style="width:100%;padding:14px;background:linear-gradient(135deg,#7B1C3E,#550f28);color:white;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">
      <i class="fas fa-user-shield"></i> Login as Admin
    </button>
  </form>

  <div style="text-align:center;margin-top:20px;">
    <a href="../index.php" style="color:#7B1C3E;font-size:13px;font-weight:600;">← Back to Student Portal</a>
  </div>
</div>

</body>
</html>