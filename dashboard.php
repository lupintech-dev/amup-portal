<?php
require_once 'includes/config.php';if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
    <p>You are logged into the AMUP Portal.</p>
</div>

</body>
</html>