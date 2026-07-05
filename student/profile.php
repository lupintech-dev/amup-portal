<?php
$base = '../';
require_once '../includes/config.php';
requireStudent();
$pageTitle = 'My Profile';
$id = (int)$_SESSION['student_id'];
$student = $conn->query("SELECT * FROM students WHERE id=$id")->fetch_assoc();

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone   = sanitize($conn, $_POST['phone'] ?? '');
    $address = sanitize($conn, $_POST['address'] ?? '');
    $photo   = $student['photo'] ?? '';

    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Photo must be JPG, PNG or WEBP.';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $error = 'Photo must be under 2MB.';
        } else {
            if ($photo && file_exists("../assets/uploads/students/$photo")) {
                unlink("../assets/uploads/students/$photo");
            }
            $reg      = strtolower(str_replace('/', '-', $student['reg_number']));
            $filename = $reg . '_' . time() . '.' . $ext;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/uploads/students/$filename")) {
                $error = 'Failed to upload photo.';
            } else {
                $photo = $filename;
            }
        }
    }

    if (!$error) {
        $conn->query("UPDATE students SET phone='$phone', address='$address', photo='$photo' WHERE id=$id");
        $success = 'Profile updated successfully!';
        $student = $conn->query("SELECT * FROM students WHERE id=$id")->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile — AMUP</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .profile-photo-wrap {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 28px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 12px;
    border: 1.5px solid #e5e7eb;
  }
  .profile-photo-wrap img,
  .profile-photo-wrap .photo-initials {
    width: 90px; height: 90px; border-radius: 50%;
    object-fit: cover; border: 3px solid #7B1C3E; flex-shrink: 0;
  }
  .profile-photo-wrap .photo-initials {
    background: #7B1C3E; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 700;
  }
  .photo-upload-btn {
    display: inline-block; padding: 8px 18px;
    background: #7B1C3E; color: #fff; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    position: relative; overflow: hidden;
  }
  .photo-upload-btn input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer;
  }
  .photo-upload-btn:hover { background: #550f28; }

  /* Read-only notice banner */
  .photo-readonly-notice {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; margin-top: 10px;
    background: #fffbeb; border: 1px solid #fcd34d;
    border-radius: 8px; font-size: 13px; color: #92400e;
  }
</style>
</head>
<body>
<?php include '../includes/topbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="page-header"><h1>My Profile</h1></div>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i> <?= $success ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width:680px;">
    <div class="card-header"><h3>Edit Profile</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">

        <!-- Photo Section -->
        <div class="profile-photo-wrap">

          <?php if (!empty($student['photo'])): ?>
            <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($student['photo']) ?>"
                 alt="Profile Photo" id="profilePreview">
          <?php else: ?>
            <div class="photo-initials" id="profileInitials">
              <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
            </div>
            <img id="profilePreview"
                 style="display:none;width:90px;height:90px;border-radius:50%;
                        object-fit:cover;border:3px solid #7B1C3E;"
                 src="" alt="">
          <?php endif; ?>

          <div>
            <div style="font-weight:700;font-size:16px;margin-bottom:4px;">
              <?= htmlspecialchars($student['full_name']) ?>
            </div>
            <div style="color:#6b7280;font-size:13px;margin-bottom:12px;">
              <?= $student['reg_number'] ?> — <?= $student['department'] ?>
            </div>

            <!-- Student can change their own photo -->
            <label class="photo-upload-btn">
              <i class="fas fa-camera"></i> Change Photo
              <input type="file" name="photo" accept="image/*"
                     onchange="previewProfilePhoto(this)">
            </label>
            <div style="font-size:12px;color:#9ca3af;margin-top:6px;">
              JPG, PNG or WEBP — max 2MB
            </div>

            <!-- Notice that admin can also update the photo -->
            <div class="photo-readonly-notice">
              <i class="fas fa-info-circle"></i>
              Your photo can also be updated by the admin on your behalf.
            </div>
          </div>
        </div>

        <!-- Read-only fields -->
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" class="form-control"
                 value="<?= htmlspecialchars($student['full_name']) ?>" disabled>
        </div>
        <div class="form-group">
          <label>Registration Number</label>
          <input type="text" class="form-control"
                 value="<?= $student['reg_number'] ?>" disabled>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="text" class="form-control"
                 value="<?= $student['email'] ?>" disabled>
        </div>

        <!-- Editable fields -->
        <div class="form-group">
          <label>Phone Number</label>
          <input type="text" name="phone" class="form-control"
                 value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" class="form-control"
                    rows="3"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function previewProfilePhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const preview  = document.getElementById('profilePreview');
      const initials = document.getElementById('profileInitials');
      preview.src = e.target.result;
      preview.style.display = 'block';
      if (initials) initials.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>