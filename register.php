<?php
require_once 'includes/config.php';

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize($conn, $_POST['full_name'] ?? '');
    $reg_number = strtoupper(sanitize($conn, $_POST['reg_number'] ?? ''));
    $email      = sanitize($conn, $_POST['email'] ?? '');
    $department = sanitize($conn, $_POST['department'] ?? '');
    $level      = sanitize($conn, $_POST['level'] ?? '100L');
    $phone      = sanitize($conn, $_POST['phone'] ?? '');
    $gender     = sanitize($conn, $_POST['gender'] ?? 'Male');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (!$full_name || !$reg_number || !$email || !$department || !$password) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate
        $check = $conn->prepare("SELECT id FROM students WHERE reg_number=? OR email=?");
        $check->bind_param('ss', $reg_number, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Registration number or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO students (reg_number, full_name, email, phone, department, level, gender, password) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssss', $reg_number, $full_name, $email, $phone, $department, $level, $gender, $hash);
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                // Auto-create fee records for new student
                $fees = [
                    ["School Fees", 150000, "2024/2025", "First", date('Y-m-d', strtotime('+30 days'))],
                    ["Acceptance Fee", 25000, "2024/2025", "Full Year", date('Y-m-d', strtotime('+14 days'))],
                    ["Library Fee", 5000, "2024/2025", "Full Year", date('Y-m-d', strtotime('+30 days'))],
                    ["Sports Fee", 3000, "2024/2025", "Full Year", date('Y-m-d', strtotime('+30 days'))],
                ];
                $fs = $conn->prepare("INSERT INTO fees (student_id, fee_type, amount, session, semester, due_date, status) VALUES (?,?,?,?,?,?,'unpaid')");
                foreach ($fees as $f) {
                    $fs->bind_param('isdsss', $newId, $f[0], $f[1], $f[2], $f[3], $f[4]);
                    $fs->execute();
                }
                // Welcome notification
                $conn->query("INSERT INTO notifications (student_id, title, message, type) VALUES ($newId, 'Registration Successful', 'Welcome to Ave Maria University Piyanko Portal. Please complete your profile and clear all outstanding fees.', 'success')");
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-container" style="max-width:560px;">
    <div class="auth-header">
      <img src="assets/img/logoi.png" class="auth-logo" alt="AMUP">
      <h1>Student Registration</h1>
      <p>Ave Maria University Piyanko</p>
    </div>
    <div class="auth-body">
      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?>
          <a href="index.php" style="font-weight:700;margin-left:8px;">Login Now →</a>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label>Full Name <span class="req">*</span></label>
            <div class="input-group">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="full_name" class="form-control" placeholder="As on admission letter" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Registration Number <span class="req">*</span></label>
            <div class="input-group">
              <i class="fas fa-id-badge input-icon"></i>
              <input type="text" name="reg_number" class="form-control" placeholder="AMUP/2024/001" required value="<?= htmlspecialchars($_POST['reg_number'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email Address <span class="req">*</span></label>
            <div class="input-group">
              <i class="fas fa-envelope input-icon"></i>
              <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <div class="input-group">
              <i class="fas fa-phone input-icon"></i>
              <input type="text" name="phone" class="form-control" placeholder="08012345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Department <span class="req">*</span></label>
            <div class="input-group">
              <i class="fas fa-graduation-cap input-icon"></i>
              <select name="department" class="form-control" required>
                <option value="">Select Department</option>
                <?php
                $depts = ['Computer Science','Accounting','Business Administration','Nursing Science','Medicine & Surgery','Law','Mass Communication','Electrical Engineering','Civil Engineering','Economics'];
                foreach ($depts as $d) {
                    $sel = (($_POST['department'] ?? '') === $d) ? 'selected' : '';
                    echo "<option value=\"$d\" $sel>$d</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Level</label>
            <div class="input-group">
              <i class="fas fa-layer-group input-icon"></i>
              <select name="level" class="form-control">
                <?php foreach (['100L','200L','300L','400L','500L','600L'] as $l) {
                    $sel = (($_POST['level'] ?? '100L') === $l) ? 'selected' : '';
                    echo "<option value=\"$l\" $sel>$l</option>";
                } ?>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Gender</label>
          <div class="input-group">
            <i class="fas fa-venus-mars input-icon"></i>
            <select name="gender" class="form-control">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password <span class="req">*</span></label>
            <div class="input-group">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="password" id="pw" class="form-control" placeholder="Min. 6 characters" required>
              <i class="fas fa-eye password-toggle" onclick="togglePw('pw', this)"></i>
            </div>
          </div>
          <div class="form-group">
            <label>Confirm Password <span class="req">*</span></label>
            <div class="input-group">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="confirm_password" id="cpw" class="form-control" placeholder="Repeat password" required>
              <i class="fas fa-eye password-toggle" onclick="togglePw('cpw', this)"></i>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>

      <div style="text-align:center;margin-top:20px;font-size:0.875rem;color:var(--gray-600);">
        Already have an account? <a href="index.php" style="font-weight:700;">Login here</a>
      </div>
    </div>
  </div>
</div>
<script>
function togglePw(id, icon) {
  const f = document.getElementById(id);
  if (f.type === 'password') { f.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
  else { f.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>
</body>
</html>
