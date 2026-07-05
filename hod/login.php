<?php
require_once '../includes/config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect
if (isset($_SESSION['hod_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM hods WHERE email = ? AND status = 'active'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $hod    = $result->fetch_assoc();

        if ($hod && password_verify($password, $hod['password'])) {
            $_SESSION['hod_id']         = $hod['id'];
            $_SESSION['hod_name']       = $hod['full_name'];
            $_SESSION['hod_email']      = $hod['email'];
            $_SESSION['hod_department'] = $hod['department'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HOD Login — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --maroon:   #6b0f2b;
    --maroon-d: #4a0a1e;
    --maroon-l: #8b1a3a;
    --gold:     #c9a84c;
    --gold-l:   #e8c96d;
    --white:    #ffffff;
    --off:      #f8f4f0;
    --muted:    #888;
    --radius:   12px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    display: flex;
    background: var(--maroon-d);
  }

  /* ── Left panel ── */
  .left {
    flex: 1;
    background: linear-gradient(145deg, var(--maroon-d) 0%, var(--maroon) 60%, var(--maroon-l) 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 40px;
    position: relative;
    overflow: hidden;
  }
  .left::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    border-radius: 50%;
    background: rgba(201,168,76,.08);
    top: -120px; left: -120px;
  }
  .left::after {
    content: '';
    position: absolute;
    width: 350px; height: 350px;
    border-radius: 50%;
    background: rgba(201,168,76,.06);
    bottom: -80px; right: -80px;
  }

  .logo-wrap { text-align: center; margin-bottom: 40px; position: relative; z-index: 1; }
  .logo-wrap img { width: 90px; }
  .logo-wrap h1 {
    font-family: 'Playfair Display', serif;
    color: var(--white);
    font-size: 2rem;
    margin-top: 12px;
    line-height: 1.2;
  }
  .logo-wrap span { color: var(--gold); }
  .logo-wrap p { color: rgba(255,255,255,.55); font-style: italic; margin-top: 6px; }

  .features { list-style: none; position: relative; z-index: 1; }
  .features li {
    color: rgba(255,255,255,.75);
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    font-size: .95rem;
  }
  .features li .icon {
    width: 36px; height: 36px;
    background: rgba(201,168,76,.15);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
  }

  /* ── Right panel ── */
  .right {
    width: 420px;
    background: var(--white);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 48px;
  }

  .right h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.9rem;
    color: var(--maroon-d);
    margin-bottom: 6px;
  }
  .right .subtitle { color: var(--muted); margin-bottom: 32px; font-size: .9rem; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff3f7;
    border: 1px solid #f0c0ce;
    color: var(--maroon);
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 20px;
    margin-bottom: 20px;
  }

  .error-box {
    background: #fff0f0;
    border: 1px solid #ffb3b3;
    color: #c0392b;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: .88rem;
    margin-bottom: 18px;
  }

  label { display: block; font-size: .82rem; font-weight: 600; color: #444; margin-bottom: 6px; letter-spacing: .04em; text-transform: uppercase; }

  .input-wrap {
    position: relative;
    margin-bottom: 20px;
  }
  .input-wrap .ico {
    position: absolute;
    left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 1rem;
  }
  .input-wrap input {
    width: 100%;
    padding: 12px 14px 12px 40px;
    border: 1.5px solid #e0e0e0;
    border-radius: var(--radius);
    font-size: .95rem;
    font-family: 'DM Sans', sans-serif;
    background: #fafafa;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  .input-wrap input:focus {
    border-color: var(--maroon);
    box-shadow: 0 0 0 3px rgba(107,15,43,.1);
    background: #fff;
  }

  .btn-login {
    width: 100%;
    padding: 14px;
    background: var(--maroon);
    color: var(--white);
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: background .2s, transform .1s;
    margin-top: 6px;
  }
  .btn-login:hover { background: var(--maroon-d); }
  .btn-login:active { transform: scale(.98); }

  .divider {
    display: flex; align-items: center; gap: 12px;
    margin: 24px 0 16px;
    color: var(--muted); font-size: .8rem;
  }
  .divider hr { flex: 1; border: none; border-top: 1px solid #eee; }

  .back-link {
    display: flex; align-items: center; justify-content: center;
    gap: 6px;
    color: var(--maroon);
    font-size: .88rem;
    font-weight: 500;
    text-decoration: none;
    padding: 10px;
    border: 1.5px solid #f0c0ce;
    border-radius: var(--radius);
    transition: background .2s;
  }
  .back-link:hover { background: #fff3f7; }

  .copy { text-align: center; color: #bbb; font-size: .78rem; margin-top: 32px; }

  @media (max-width: 768px) {
    .left { display: none; }
    .right { width: 100%; padding: 40px 28px; }
  }
</style>
</head>
<body>

<!-- LEFT -->
<div class="left">
  <div class="logo-wrap">
    <img src="../assets/img/logoi.png" alt="AMUP Logo" onerror="this.style.display='none'">
    <h1>Ave Maria<br>University <span>Piyanko</span></h1>
    <p>"Learning for Service"</p>
  </div>
  <ul class="features">
    <li><div class="icon">👥</div> View all students in your department</li>
    <li><div class="icon">📊</div> Track academic results & GPA</li>
    <li><div class="icon">💰</div> Monitor fee payment status</li>
    <li><div class="icon">🔔</div> Send department notifications</li>
    <li><div class="icon">📅</div> Manage course enrollment</li>
  </ul>
</div>

<!-- RIGHT -->
<div class="right">
  <div class="badge">🎓 HOD Portal</div>
  <h2>HOD Login</h2>
  <p class="subtitle">Head of Department access — AMUP Management Portal</p>

  <?php if ($error): ?>
    <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="email">Email Address</label>
    <div class="input-wrap">
      <span class="ico">✉️</span>
      <input type="email" id="email" name="email" placeholder="hod.department@amup.edu.ng"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>

    <label for="password">Password</label>
    <div class="input-wrap">
      <span class="ico">🔒</span>
      <input type="password" id="password" name="password" placeholder="••••••••••" required>
    </div>

    <button type="submit" class="btn-login">🔐 Login to HOD Portal</button>
  </form>

  <div class="divider"><hr> or <hr></div>
  <a href="../index.php" class="back-link">← Back to Student Portal</a>

  <p class="copy">© <?= date('Y') ?> Ave Maria University Piyanko. All rights reserved.</p>
</div>

</body>
</html>
