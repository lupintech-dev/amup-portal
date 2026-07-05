<?php
$unread = getUnreadCount($conn, $_SESSION['student_id']);
$currentPage = basename($_SERVER['PHP_SELF']);

// Check suspension count for badge
$sid = (int)$_SESSION['student_id'];
$activeSusp = $conn->query("SELECT COUNT(*) c FROM student_suspensions WHERE student_id=$sid AND status='active'")->fetch_assoc()['c'];
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="<?= $base ?>assets/img/logo.png" class="sidebar-logo" alt="AMUP Logo">
    <div class="sidebar-brand-text">
      <div class="name">Ave Maria University</div>
      <div class="sub">Piyanko Portal</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">STUDENT MENU</div>

    <a href="<?= $base ?>student/dashboard.php"
       class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="<?= $base ?>student/results.php"
       class="nav-item <?= $currentPage === 'results.php' ? 'active' : '' ?>">
      <i class="fas fa-chart-bar"></i> My Results
    </a>
    <a href="<?= $base ?>student/fees.php"
       class="nav-item <?= $currentPage === 'fees.php' ? 'active' : '' ?>">
      <i class="fas fa-money-bill-wave"></i> Fee Payments
    </a>
    <a href="<?= $base ?>student/calendar.php"
       class="nav-item <?= $currentPage === 'calendar.php' ? 'active' : '' ?>">
      <i class="fas fa-calendar-alt"></i> Academic Calendar
    </a>
    <a href="<?= $base ?>student/notifications.php"
       class="nav-item <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
      <i class="fas fa-bell"></i> Notifications
      <?php if ($unread > 0): ?>
        <span class="nav-badge"><?= $unread ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= $base ?>student/suspensions.php"
       class="nav-item <?= $currentPage === 'suspensions.php' ? 'active' : '' ?>">
      <i class="fas fa-ban"></i> My Suspensions
      <?php if ($activeSusp > 0): ?>
        <span class="nav-badge" style="background:#dc2626;"><?= $activeSusp ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= $base ?>student/profile.php"
       class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
      <i class="fas fa-user"></i> My Profile
    </a>

    <div class="nav-section" style="margin-top:16px;">ACCOUNT</div>
    <a href="<?= $base ?>student/change_password.php"
       class="nav-item <?= $currentPage === 'change_password.php' ? 'active' : '' ?>">
      <i class="fas fa-key"></i> Change Password
    </a>
    <a href="<?= $base ?>logout.php" class="nav-item" style="color:#fca5a5;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <?php
        $photoRes = $conn->query("SELECT photo FROM students WHERE id=$sid");
        $sPhoto = ($photoRes && $pr = $photoRes->fetch_assoc()) ? $pr['photo'] : '';
      ?>
      <?php if ($sPhoto): ?>
        <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($sPhoto) ?>"
             style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.4);"
             alt="Photo">
      <?php else: ?>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['student_name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div>
        <div style="font-size:0.82rem;color:rgba(255,255,255,0.9);font-weight:600;line-height:1.2;">
          <?= htmlspecialchars($_SESSION['student_name'] ?? '') ?>
        </div>
        <div style="font-size:0.72rem;color:rgba(255,255,255,0.5);">
          <?= htmlspecialchars($_SESSION['student_reg'] ?? '') ?>
        </div>
      </div>
    </div>
  </div>
</aside>