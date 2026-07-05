<?php
$isAdmin = isset($_SESSION['admin_id']);
$name = $isAdmin ? ($_SESSION['admin_name'] ?? 'Admin') : ($_SESSION['student_name'] ?? 'Student');
$unreadCount = (!$isAdmin && isset($_SESSION['student_id']))
    ? getUnreadCount($conn, $_SESSION['student_id']) : 0;

// Load student photo if logged in as student
$studentPhoto = '';
if (!$isAdmin && isset($_SESSION['student_id'])) {
    $sid = (int)$_SESSION['student_id'];
    $photoRes = $conn->query("SELECT photo FROM students WHERE id=$sid");
    if ($photoRes && $row = $photoRes->fetch_assoc()) {
        $studentPhoto = $row['photo'] ?? '';
    }
}
?>
<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
      <i class="fas fa-bars"></i>
    </button>
    <div>
      <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
      <div class="topbar-sub"><?= date('l, d F Y') ?></div>
    </div>
  </div>
  <div class="topbar-right">
    <?php if (!$isAdmin): ?>
    <a href="<?= $base ?>student/notifications.php" class="topbar-icon" title="Notifications">
      <i class="fas fa-bell"></i>
      <?php if ($unreadCount > 0): ?>
        <span class="icon-badge"><?= $unreadCount ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>
    <div style="display:flex;align-items:center;gap:10px;">
      <?php if (!$isAdmin && $studentPhoto): ?>
        <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($studentPhoto) ?>"
             alt="<?= htmlspecialchars($name) ?>"
             style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--maroon);">
      <?php else: ?>
        <div style="width:36px;height:36px;font-size:0.9rem;background:var(--maroon);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;">
          <?= strtoupper(substr($name, 0, 1)) ?>
        </div>
      <?php endif; ?>
      <span style="font-size:0.875rem;font-weight:600;color:var(--gray-800);">
        <?= htmlspecialchars($name) ?>
      </span>
    </div>
  </div>
</header>