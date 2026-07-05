<?php
require_once '../includes/config.php';
if (isset($_SESSION['bursar_id'])) {
    header("Location: dashboard.php"); exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM bursars WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $bursar = $stmt->get_result()->fetch_assoc();

    if ($bursar && password_verify($password, $bursar['password'])) {
        if ($bursar['status'] !== 'active') {
            $error = 'Your account has been deactivated. Contact admin.';
        } else {
            $_SESSION['bursar_id']   = $bursar['id'];
            $_SESSION['bursar_name'] = $bursar['full_name'];
            header("Location: dashboard.php"); exit();
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bursar Login — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#3d0a1a;min-height:100vh;display:flex;align-items:center;justify-content:center;">

<div style="display:grid;grid-template-columns:1fr 420px;max-width:900px;width:100%;margin:20px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.4);">

  <!-- Left Side -->
  <div style="background:linear-gradient(135deg,#3d0a1a,#7B1C3E);padding:60px 40px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;">
    <img src="../assets/img/logoi.png" style="width:100px;margin-bottom:24px;filter:drop-shadow(0 4px 16px rgba(0,0,0,0.4));" alt="AMUP">
    <h2 style="font-family:'Playfair Display',serif;color:white;font-size:1.8rem;margin-bottom:8px;">Bursar Portal</h2>
    <p style="color:rgba(255,255,255,0.7);font-size:14px;margin-bottom:40px;">Ave Maria University Piyanko</p>
    <div style="text-align:left;width:100%;">
      <?php foreach([
        ['fas fa-money-bill-wave', 'View all student fee records'],
        ['fas fa-check-circle',    'Confirm and update payments'],
        ['fas fa-search',          'Search students by name or reg'],
        ['fas fa-file-invoice',    'Generate payment summaries'],
        ['fas fa-bell',            'Send fee reminders to students'],
      ] as [$icon, $text]): ?>
      <div style="display:flex;align-items:center;gap:12px;color:rgba(255,255,255,0.8);font-size:13px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);">
        <div style="width:32px;height:32px;background:rgba(212,168,37,0.2);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#F0D060;flex-shrink:0;">
          <i class="<?= $icon ?>"></i>
        </div>
        <?= $text ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right Side -->
  <div style="background:white;padding:48px 40px;display:flex;flex-direction:column;justify-content:center;">
    <div style="text-align:center;margin-bottom:32px;">
      <div style="width:60px;height:60px;background:#fef9c3;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;">
        💰
      </div>
      <h3 style="font-family:'Playfair Display',serif;color:#3d0a1a;font-size:1.5rem;margin-bottom:4px;">Bursar Login</h3>
      <p style="color:#6b7280;font-size:13px;">Fee Management Access</p>
    </div>

    <?php if ($error): ?>
      <div style="background:#fef2f2;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;border:1px solid #fecaca;">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div style="margin-bottom:16px;">
        <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Username or Email</label>
        <div style="position:relative;">
          <i class="fas fa-user" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;"></i>
          <input type="text" name="username" placeholder="bursar" required
            style="width:100%;padding:12px 14px 12px 38px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;font-family:inherit;">
        </div>
      </div>
      <div style="margin-bottom:24px;">
        <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Password</label>
        <div style="position:relative;">
          <i class="fas fa-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;"></i>
          <input type="password" name="password" id="pw" placeholder="••••••••" required
            style="width:100%;padding:12px 38px 12px 38px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;font-family:inherit;">
          <i class="fas fa-eye" onclick="togglePw()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#9ca3af;cursor:pointer;font-size:13px;"></i>
        </div>
      </div>
      <button type="submit"
        style="width:100%;padding:14px;background:linear-gradient(135deg,#3d0a1a,#7B1C3E);color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;">
        <i class="fas fa-sign-in-alt"></i> Login to Bursar Portal
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;">
      <a href="../index.php" style="color:#3d0a1a;font-size:13px;font-weight:600;text-decoration:none;">← Back to Student Portal</a>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const f = document.getElementById('pw');
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>