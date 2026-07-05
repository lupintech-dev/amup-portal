<?php
$isAdmin = isset($_SESSION['admin_id']);
$name = $isAdmin ? ($_SESSION['admin_name'] ?? 'Admin') : ($_SESSION['student_name'] ?? 'Student');
$unreadCount = (!$isAdmin && isset($_SESSION['student_id']))
    ? getUnreadCount($conn, $_SESSION['student_id']) : 0;

$studentPhoto = '';
if (!$isAdmin && isset($_SESSION['student_id'])) {
    $sid = (int)$_SESSION['student_id'];
    $photoRes = $conn->query("SELECT photo FROM students WHERE id=$sid");
    if ($photoRes && $row = $photoRes->fetch_assoc()) {
        $studentPhoto = $row['photo'] ?? '';
    }
}
?>
<style>
.topbar {
  position: fixed; top: 0; left: 0; right: 0;
  height: 60px; background: #fff;
  border-bottom: 1px solid #e5e7eb;
  display: flex; align-items: center;
  justify-content: space-between;
  padding: 0 20px; z-index: 997;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-title { font-size: 15px; font-weight: 700; color: #111827; line-height: 1.2; }
.topbar-sub   { font-size: 11px; color: #9ca3af; }
.topbar-right { display: flex; align-items: center; gap: 12px; }

.sidebar-toggle {
  width: 38px; height: 38px;
  background: #f3f4f6; border: none;
  border-radius: 8px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #374151;
  transition: background 0.2s;
}
.sidebar-toggle:hover { background: #e5e7eb; }

/* Show hamburger always — JS handles desktop hiding */
@media (min-width: 769px) {
  .sidebar-toggle { display: none; }
}

.topbar-icon {
  position: relative; width: 36px; height: 36px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px; color: #6b7280;
  text-decoration: none; font-size: 16px;
  transition: background 0.2s;
}
.topbar-icon:hover { background: #f3f4f6; color: #7B1C3E; }
.icon-badge {
  position: absolute; top: 2px; right: 2px;
  background: #dc2626; color: white;
  font-size: 9px; font-weight: 700;
  min-width: 16px; height: 16px;
  border-radius: 10px; padding: 0 4px;
  display: flex; align-items: center; justify-content: center;
}
</style>

<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="toggleSidebar()">
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
    <div style="display:flex;align-items:center;gap:8px;">
      <?php if (!$isAdmin && $studentPhoto): ?>
        <img src="<?= $base ?>assets/uploads/students/<?= htmlspecialchars($studentPhoto) ?>"
             alt="<?= htmlspecialchars($name) ?>"
             style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid #7B1C3E;">
      <?php else: ?>
        <div style="width:34px;height:34px;font-size:0.85rem;background:#7B1C3E;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;">
          <?= strtoupper(substr($name, 0, 1)) ?>
        </div>
      <?php endif; ?>
      <span style="font-size:0.85rem;font-weight:600;color:#111827;display:none;" class="username-text">
        <?= htmlspecialchars($name) ?>
      </span>
    </div>
  </div>
</header>

<style>
@media (min-width: 600px) {
  .username-text { display:inline !important; }
}
</style>