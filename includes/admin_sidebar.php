<?php
// ── includes/admin_sidebar.php ───────────────────
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="<?= $base ?>assets/img/logo.png" class="sidebar-logo" alt="AMUP Logo">
    <div class="sidebar-brand-text">
      <div class="name">AMUP Admin</div>
      <div class="sub">Management Portal</div>
    </div>
  </div>
 
  <nav class="sidebar-nav">
    <div class="nav-section">DASHBOARD</div>
    <a href="<?= $base ?>admin/dashboard.php"
       class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-tachometer-alt"></i> Overview
    </a>
 
    <div class="nav-section">STUDENTS</div>
    <a href="<?= $base ?>admin/students.php"
       class="nav-item <?= $currentPage === 'students.php' ? 'active' : '' ?>">
      <i class="fas fa-users"></i> All Students
    </a>
    <a href="<?= $base ?>admin/add_student.php"
       class="nav-item <?= $currentPage === 'add_student.php' ? 'active' : '' ?>">
      <i class="fas fa-user-plus"></i> Add Student
    </a>
    <a href="<?= $base ?>admin/suspended_students.php"
       class="nav-item <?= $currentPage === 'suspended_students.php' ? 'active' : '' ?>">
      <i class="fas fa-user-clock"></i> Suspended Students
    </a>
    <a href="<?= $base ?>admin/graduated_students.php"
       class="nav-item <?= $currentPage === 'graduated_students.php' ? 'active' : '' ?>">
      <i class="fas fa-graduation-cap"></i> Graduated Students
    </a>
 
    <div class="nav-section">ACADEMICS</div>
    <a href="<?= $base ?>admin/results.php"
       class="nav-item <?= $currentPage === 'results.php' ? 'active' : '' ?>">
      <i class="fas fa-chart-bar"></i> Results Manager
    </a>
    <a href="<?= $base ?>admin/courses.php"
       class="nav-item <?= $currentPage === 'courses.php' ? 'active' : '' ?>">
      <i class="fas fa-book"></i> Courses
    </a>
    <a href="<?= $base ?>admin/calendar.php"
       class="nav-item <?= $currentPage === 'calendar.php' ? 'active' : '' ?>">
      <i class="fas fa-calendar-alt"></i> Academic Calendar
    </a>
 
    <div class="nav-section">FINANCE</div>
    <a href="<?= $base ?>admin/fees.php"
       class="nav-item <?= $currentPage === 'fees.php' ? 'active' : '' ?>">
      <i class="fas fa-money-bill-wave"></i> Fee Management
    </a>
 
    <div class="nav-section">STAFF MANAGEMENT</div>
    <a href="<?= $base ?>admin/hod.php"
       class="nav-item <?= $currentPage === 'hod.php' ? 'active' : '' ?>">
      <i class="fas fa-chalkboard-teacher"></i> HOD Management
    </a>
    <a href="<?= $base ?>admin/staff.php"
       class="nav-item <?= $currentPage === 'staff.php' ? 'active' : '' ?>">
      <i class="fas fa-user-tie"></i> Staff Management
    </a>
 
    <div class="nav-section">COMMUNICATION</div>
    <a href="<?= $base ?>admin/notifications.php"
       class="nav-item <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
      <i class="fas fa-bell"></i> Notifications
    </a>
 
    <div class="nav-section">ACCOUNT</div>
    <a href="<?= $base ?>logout.php" class="nav-item" style="color:#fca5a5;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </nav>
 
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_name'], 0, 1)) ?></div>
      <div>
        <div style="font-size:0.82rem;color:rgba(255,255,255,0.9);font-weight:600;line-height:1.2;">
          <?= htmlspecialchars($_SESSION['admin_name']) ?>
        </div>
        <div style="font-size:0.72rem;color:rgba(255,255,255,0.5);">Administrator</div>
      </div>
    </div>
  </div>
</aside>