<?php
$base = '../';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = 'Add Student';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize($conn, $_POST['full_name'] ?? '');
    $reg_number = strtoupper(sanitize($conn, $_POST['reg_number'] ?? ''));
    $email      = sanitize($conn, $_POST['email'] ?? '');
    $phone      = sanitize($conn, $_POST['phone'] ?? '');
    $department = sanitize($conn, $_POST['department'] ?? '');
    $level      = sanitize($conn, $_POST['level'] ?? '100L');
    $gender     = sanitize($conn, $_POST['gender'] ?? 'Male');
    $session    = sanitize($conn, $_POST['session'] ?? '2024/2025');
    $password   = $_POST['password'] ?? 'Student@123';
    $photo      = '';

    if (!$full_name || !$reg_number || !$email || !$department) {
        $error = 'Please fill all required fields.';
    } else {
        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Photo must be JPG, PNG or WEBP.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $error = 'Photo must be under 2MB.';
            } else {
                $filename = strtolower(str_replace('/', '-', $reg_number)) . '_' . time() . '.' . $ext;
                $uploadPath = '../assets/uploads/students/' . $filename;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $error = 'Failed to upload photo. Check folder permissions.';
                } else {
                    $photo = $filename;
                }
            }
        }

        if (!$error) {
            $check = $conn->prepare("SELECT id FROM students WHERE reg_number=? OR email=?");
            $check->bind_param('ss', $reg_number, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'Registration number or email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO students (reg_number, full_name, email, phone, department, level, gender, session, password, photo, status) VALUES (?,?,?,?,?,?,?,?,?,?,'active')");
                $stmt->bind_param('ssssssssss', $reg_number, $full_name, $email, $phone, $department, $level, $gender, $session, $hash, $photo);
                if ($stmt->execute()) {
                    $newId = $conn->insert_id;
                    $fees = [
                        ["School Fees", 150000, $session, "First"],
                        ["Acceptance Fee", 25000, $session, "Full Year"],
                        ["Library Fee", 5000, $session, "Full Year"],
                        ["Sports Fee", 3000, $session, "Full Year"],
                        ["Chapel/Mass Levy", 2000, $session, "Full Year"],
                        ["Examination Fee", 10000, $session, "First"],
                    ];
                    $fs = $conn->prepare("INSERT INTO fees (student_id, fee_type, amount, session, semester, due_date, status) VALUES (?,?,?,?,?,?,'unpaid')");
                    foreach ($fees as $f) {
                        $due = date('Y-m-d', strtotime('+30 days'));
                        $fs->bind_param('isdsss', $newId, $f[0], $f[1], $f[2], $f[3], $due);
                        $fs->execute();
                    }
                    $conn->query("INSERT INTO notifications (student_id, title, message, type) VALUES ($newId, 'Welcome to AMUP Portal', 'Your account has been created by the administrator. Your default password is: $password — Please change it after first login.', 'success')");
                    $success = "Student $full_name ($reg_number) added successfully! Default password: $password";
                } else {
                    $error = 'Failed to add student.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Student — AMUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .photo-upload-wrap {
    border: 2px dashed #e5e7eb; border-radius: 12px;
    padding: 24px; text-align: center; cursor: pointer;
    transition: border-color 0.2s; position: relative;
  }
  .photo-upload-wrap:hover { border-color: #7B1C3E; }
  .photo-upload-wrap input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%;
  }
  .photo-preview {
    width: 90px; height: 90px; border-radius: 50%;
    object-fit: cover; margin-bottom: 10px;
    border: 3px solid #7B1C3E; display: none;
  }
  .photo-upload-wrap i { font-size: 32px; color: #d1d5db; margin-bottom: 8px; display: block; }
  .photo-upload-wrap p { font-size: 13px; color: #6b7280; margin: 0; }
  .photo-upload-wrap small { font-size: 12px; color: #9ca3af; }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-content">
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div><h1>Add Student</h1><p>Register a new student manually</p></div>
    <a href="students.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:700px;">
    <div class="card-header"><h3><i class="fas fa-user-plus"></i> Student Details</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">

        <!-- Photo Upload -->
        <div class="form-group" style="margin-bottom:24px;">
          <label>Profile Photo</label>
          <div class="photo-upload-wrap" id="photoWrap">
            <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
            <img id="photoPreview" class="photo-preview" src="" alt="Preview">
            <i class="fas fa-camera" id="photoIcon"></i>
            <p id="photoText">Click to upload photo</p>
            <small>JPG, PNG or WEBP — max 2MB</small>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>Full Name <span style="color:red;">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="As on admission letter" required>
          </div>
          <div class="form-group">
            <label>Registration Number <span style="color:red;">*</span></label>
            <input type="text" name="reg_number" class="form-control" placeholder="AMUP/2024/001" required>
          </div>
          <div class="form-group">
            <label>Email Address <span style="color:red;">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="student@email.com" required>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="08012345678">
          </div>
          <div class="form-group">
            <label>Department <span style="color:red;">*</span></label>
            <input type="text" name="department" class="form-control"
                   list="dept-list" placeholder="Type or select department" required>
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
            <label>Level</label>
            <select name="level" class="form-control">
              <?php foreach(['100L','200L','300L','400L','500L','600L'] as $l): ?>
              <option value="<?= $l ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Session</label>
            <input type="text" name="session" class="form-control" value="2024/2025">
          </div>
          <div class="form-group">
            <label>Default Password</label>
            <input type="text" name="password" class="form-control" value="Student@123">
            <small style="color:#9ca3af;">Student will be asked to change this after first login</small>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">
          <i class="fas fa-user-plus"></i> Add Student
        </button>
      </form>
    </div>
  </div>
</div>
<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const preview = document.getElementById('photoPreview');
      preview.src = e.target.result;
      preview.style.display = 'block';
      document.getElementById('photoIcon').style.display = 'none';
      document.getElementById('photoText').textContent = input.files[0].name;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>