<?php
require_once 'includes/config.php';

$token = isset($_GET['token']) ? $conn->real_escape_string($_GET['token']) : '';
if (isset($_POST['token'])) {
    $token = $conn->real_escape_string($_POST['token']);
}

$message = ''; $type = ''; $valid = false; $reset = null;

if ($token) {
    $result = $conn->query("SELECT * FROM password_resets WHERE token='$token' AND expires_at > NOW() LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $reset = $result->fetch_assoc();
        $valid = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $type = 'danger';
        $valid = true;
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $type = 'danger';
        $valid = true;
    } else {
        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $email = $reset['email'];
        $email = $conn->real_escape_string($email);

        $conn->query("UPDATE students SET password='$hash' WHERE email='$email'");
        $conn->query("DELETE FROM password_resets WHERE email='$email'");

        $message = 'Password reset successful! You can now login.';
        $type = 'success';
        $valid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background:#550f28;display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="background:white;border-radius:16px;padding:48px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
    <div style="text-align:center;margin-bottom:32px;">
        <img src="assets/img/logoi.png" style="width:80px;margin-bottom:16px;" alt="AMUP">
        <h2 style="font-family:'Playfair Display',serif;color:#7B1C3E;">Reset Password</h2>
        <p style="color:#6b7280;font-size:14px;">Enter your new password below</p>
    </div>

    <?php if ($message): ?>
        <div style="background:<?= $type==='success' ? '#f0fdf4' : '#fef2f2' ?>;color:<?= $type==='success' ? '#16a34a' : '#dc2626' ?>;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px;">
            <i class="fas fa-<?= $type==='success' ? 'check' : 'exclamation' ?>-circle"></i> <?= $message ?>
            <?php if ($type==='success'): ?>
                <br><br><a href="index.php" style="font-weight:700;color:#16a34a;">← Login Now</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$valid && !$message): ?>
        <div style="background:#fef2f2;color:#dc2626;padding:12px;border-radius:8px;font-size:13px;">
            <i class="fas fa-exclamation-circle"></i> This reset link is invalid or has expired.
            <br><br><a href="forgot_password.php" style="font-weight:700;">Request a new one →</a>
        </div>
    <?php endif; ?>

    <?php if ($valid): ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div style="margin-bottom:16px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">NEW PASSWORD</label>
            <input type="password" name="password" placeholder="Min. 6 characters" required
                style="width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:24px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">CONFIRM PASSWORD</label>
            <input type="password" name="confirm_password" placeholder="Repeat password" required
                style="width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;">
        </div>
        <button type="submit"
            style="width:100%;padding:14px;background:linear-gradient(135deg,#7B1C3E,#550f28);color:white;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">
            <i class="fas fa-lock"></i> Reset Password
        </button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;">
        <a href="index.php" style="color:#7B1C3E;font-size:13px;font-weight:600;">← Back to Login</a>
    </div>
</div>
</body>
</html>