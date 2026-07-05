<?php
require_once 'includes/config.php';
if (isStudentLoggedIn()) redirect('student/dashboard.php');
if (isAdminLoggedIn())   redirect('admin/dashboard.php');
 
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($conn, $_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
 
    $stmt = $conn->prepare("SELECT * FROM students WHERE reg_number=? OR email=? LIMIT 1");
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
 
    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id']   = $student['id'];
        $_SESSION['student_name'] = $student['full_name'];
        $_SESSION['student_reg']  = $student['reg_number'];
        redirect('student/dashboard.php');
    } else {
        $error = 'Invalid registration number/email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ave Maria University Piyanko Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
  :root {
    --maroon: #7B1C3E;
    --maroon-dark: #550f28;
    --gold: #D4A825;
    --gold-light: #f0c940;
    --yellow: #F5D020;
    --cream: #FDF8F0;
    --white: #ffffff;
    --gray: #6b7280;
    --light: #f9f6f0;
  }
 
  * { margin:0; padding:0; box-sizing:border-box; }
 
  body {
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    background: var(--maroon-dark);
    overflow-x: hidden;
  }
 
  .bg-pattern {
    position: fixed; inset: 0; z-index: 0;
    background: 
      radial-gradient(ellipse 80% 60% at 20% 10%, rgba(212,168,37,0.15) 0%, transparent 60%),
      radial-gradient(ellipse 60% 80% at 80% 90%, rgba(123,28,62,0.4) 0%, transparent 60%),
      linear-gradient(135deg, #3d0a1a 0%, #7B1C3E 40%, #550f28 100%);
  }
 
  .floating-shapes {
    position: fixed; inset: 0; z-index: 0; overflow: hidden;
  }
  .shape {
    position: absolute; border-radius: 50%;
    background: rgba(212,168,37,0.06);
    animation: float 8s ease-in-out infinite;
  }
  .shape:nth-child(1) { width:300px; height:300px; top:-100px; left:-80px; animation-delay: 0s; }
  .shape:nth-child(2) { width:200px; height:200px; top:50%; right:-60px; animation-delay: 2s; }
  .shape:nth-child(3) { width:150px; height:150px; bottom:10%; left:10%; animation-delay: 4s; }
  .shape:nth-child(4) { width:80px; height:80px; top:30%; left:40%; animation-delay: 1s; }
 
  @keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
  }
 
  .page-wrapper {
    position: relative; z-index: 1;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 480px;
  }
 
  .hero {
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 60px 80px;
    text-align: center;
  }
 
  .logo-wrap { position: relative; margin-bottom: 32px; }
  .logo-wrap img {
    width: 130px; height: 130px; object-fit: contain;
    filter: drop-shadow(0 8px 32px rgba(0,0,0,0.4));
    animation: logo-in 0.8s ease both;
  }
  @keyframes logo-in {
    from { opacity:0; transform: scale(0.7) rotate(-10deg); }
    to { opacity:1; transform: scale(1) rotate(0deg); }
  }
 
  .hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(28px, 3.5vw, 48px);
    font-weight: 900; color: var(--white);
    line-height: 1.1; margin-bottom: 8px;
    animation: slide-up 0.8s 0.2s ease both;
  }
  .hero h1 span { color: var(--gold); }
  .hero .subtitle {
    font-size: 15px; color: rgba(255,255,255,0.6);
    letter-spacing: 3px; text-transform: uppercase;
    margin-bottom: 24px;
    animation: slide-up 0.8s 0.3s ease both;
  }
  .hero .tagline {
    font-family: 'Playfair Display', serif;
    font-style: italic; font-size: 20px;
    color: var(--gold-light); margin-bottom: 48px;
    animation: slide-up 0.8s 0.4s ease both;
  }
  .feature-list { list-style: none; text-align: left; animation: slide-up 0.8s 0.5s ease both; }
  .feature-list li {
    display: flex; align-items: center; gap: 12px;
    color: rgba(255,255,255,0.75); font-size: 14px; padding: 8px 0;
  }
  .feature-list li i {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(212,168,37,0.2);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold); font-size: 13px; flex-shrink: 0;
  }
  @keyframes slide-up {
    from { opacity:0; transform: translateY(30px); }
    to { opacity:1; transform: translateY(0); }
  }
 
  .auth-panel {
    background: var(--white);
    display: flex; flex-direction: column;
    justify-content: center; padding: 56px 48px;
    position: relative; overflow: hidden;
    animation: panel-in 0.6s 0.1s ease both;
  }
  @keyframes panel-in {
    from { transform: translateX(60px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  .auth-panel::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 5px;
    background: linear-gradient(90deg, var(--maroon), var(--gold), var(--maroon));
  }
  .auth-panel h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px; color: var(--maroon-dark); margin-bottom: 6px;
  }
  .auth-panel .sub { color: var(--gray); font-size: 14px; margin-bottom: 32px; }
 
  .form-group { margin-bottom: 18px; }
  .form-group label {
    display: block; font-size: 12px; font-weight: 600;
    color: #374151; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
  }
  .form-group .input-wrap { position: relative; }
  .form-group .input-wrap i {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 14px;
  }
  .form-group input {
    width: 100%; padding: 12px 14px 12px 40px;
    border: 1.5px solid #e5e7eb; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 14px;
    color: #111827; background: #f9fafb;
    transition: all 0.2s; outline: none;
  }
  .form-group input:focus {
    border-color: var(--maroon); background: #fff;
    box-shadow: 0 0 0 3px rgba(123,28,62,0.08);
  }

  .forgot-link {
    display: block; text-align: right;
    font-size: 12px; color: var(--maroon);
    font-weight: 600; text-decoration: none;
    margin-top: -10px; margin-bottom: 16px;
  }
  .forgot-link:hover { text-decoration: underline; }

  .btn-primary {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, var(--maroon) 0%, var(--maroon-dark) 100%);
    color: white; border: none; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600;
    cursor: pointer; transition: all 0.2s; margin-top: 8px;
  }
  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(123,28,62,0.35);
  }
  .divider {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0; color: #9ca3af; font-size: 13px;
  }
  .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
  .admin-link {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px; border: 1.5px solid #e5e7eb; border-radius: 10px;
    color: var(--maroon); font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
  }
  .admin-link:hover { background: #fff1f5; border-color: var(--maroon); }
  .alert {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;
    font-size: 13px; font-weight: 500;
  }
  .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
  .footer-text { text-align: center; margin-top: 24px; color: #9ca3af; font-size: 12px; }
 
  @media (max-width: 900px) {
    .page-wrapper { grid-template-columns: 1fr; }
    .hero { padding: 40px 24px; }
    .auth-panel { padding: 40px 24px; border-radius: 24px 24px 0 0; }
  }
</style>
</head>
<body>
<div class="bg-pattern"></div>
<div class="floating-shapes">
  <div class="shape"></div><div class="shape"></div>
  <div class="shape"></div><div class="shape"></div>
</div>
 
<div class="page-wrapper">
  <div class="hero">
    <div class="logo-wrap">
      <img src="assets/img/logoi.png" alt="AMUP Logo">
    </div>
    <div class="subtitle">School Portal</div>
    <h1>Ave Maria<br>University <span>Piyanko</span></h1>
    <p class="tagline">"Learning for Service"</p>
    <ul class="feature-list">
      <li><i class="fas fa-chart-bar"></i> View Academic Results & GPA</li>
      <li><i class="fas fa-money-bill-wave"></i> Track Fee Payments & Invoices</li>
      <li><i class="fas fa-calendar-alt"></i> Suspension & Resumption Dates</li>
      <li><i class="fas fa-bell"></i> Real-time Notifications</li>
      <li><i class="fas fa-shield-alt"></i> Secure Student Dashboard</li>
    </ul>
  </div>
 
  <div class="auth-panel">
    <h2>Welcome Back</h2>
    <p class="sub">Login to access your student portal</p>
 
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>
 
    <form method="POST">
      <div class="form-group">
        <label>Registration Number or Email</label>
        <div class="input-wrap">
          <i class="fas fa-id-card"></i>
          <input type="text" name="identifier" placeholder="e.g. AMUP/2021/001" required>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
      <button type="submit" class="btn-primary">
        <i class="fas fa-sign-in-alt"></i> Login to Portal
      </button>
    </form>
 
    <div class="divider">Staff Access</div>
    <a href="admin/login.php" class="admin-link">
      <i class="fas fa-user-shield"></i> Administrator Login
    </a>
    <a href="hod/login.php" class="admin-link" style="margin-top:10px;">
      <i class="fas fa-chalkboard-teacher"></i> HOD Login
    </a>
    <a href="bursar/login.php" class="admin-link" style="margin-top:10px;">
      <i class="fas fa-money-bill-wave"></i> Bursar Login
    </a>
    <div class="footer-text">© 2021 Ave Maria University Piyanko. All rights reserved.</div>
  </div>
</div>
</body>
</html>