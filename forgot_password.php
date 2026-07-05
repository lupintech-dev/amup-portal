<?php
require_once 'includes/config.php';

$message = ''; $type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name FROM students WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $conn->query("DELETE FROM password_resets WHERE email='$email'");
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
            $ins->bind_param('sss', $email, $token, $expires);
            $ins->execute();

            $resetLink = "https://avemariaschoolportal.site.je/reset_password.php?token=$token";

            // Send email using Gmail SMTP with raw socket (no PHPMailer needed)
            $to       = $email;
            $name     = $student['full_name'];
            $subject  = 'Password Reset - AMUP Portal';
            $body     = "Hello $name,\n\nYou requested a password reset for your AMUP Portal account.\n\nClick the link below to reset your password (valid for 1 hour):\n\n$resetLink\n\nIf you did not request this, please ignore this email.\n\nAve Maria University Piyanko";

            $headers  = "From: AMUP Portal <avemariaschoolportal@gmail.com>\r\n";
            $headers .= "Reply-To: avemariaschoolportal@gmail.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Use PHPMailer via manual include
            $phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/';

            if (file_exists($phpmailerPath . 'PHPMailer.php')) {
                require_once $phpmailerPath . 'PHPMailer.php';
                require_once $phpmailerPath . 'SMTP.php';
                require_once $phpmailerPath . 'Exception.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'your-email@gmail.com';
                    $mail->Password   = 'your-app-password';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;
                    $mail->setFrom('your-email@gmail.com', 'AMUP Portal');
                    $mail->addAddress($email, $name);
                    $mail->isHTML(false);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->send();
                    $message = 'Password reset link sent! Check your email inbox.';
                    $type = 'success';
                } catch (Exception $e) {
                    $message = 'Email could not be sent. Error: ' . $mail->ErrorInfo;
                    $type = 'danger';
                }
            } else {
                // Fallback: try PHP mail()
                if (mail($to, $subject, $body, $headers)) {
                    $message = 'Password reset link sent! Check your email inbox.';
                    $type = 'success';
                } else {
                    $message = 'Failed to send email. PHPMailer not found and mail() unavailable.';
                    $type = 'danger';
                }
            }
        } else {
            $message = 'No account found with that email address.';
            $type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — AMUP Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background:var(--maroon-dark);display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="background:white;border-radius:16px;padding:48px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
    <div style="text-align:center;margin-bottom:32px;">
        <img src="assets/img/logoi.png" style="width:80px;margin-bottom:16px;" alt="AMUP">
        <h2 style="font-family:'Playfair Display',serif;color:#7B1C3E;">Forgot Password</h2>
        <p style="color:#6b7280;font-size:14px;">Enter your email to receive a reset link</p>
    </div>

    <?php if ($message): ?>
        <div style="background:<?= $type==='success' ? '#f0fdf4' : '#fef2f2' ?>;color:<?= $type==='success' ? '#16a34a' : '#dc2626' ?>;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px;">
            <i class="fas fa-<?= $type==='success' ? 'check' : 'exclamation' ?>-circle"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">EMAIL ADDRESS</label>
            <input type="email" name="email" placeholder="your@email.com" required
                style="width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;">
        </div>
        <button type="submit"
            style="width:100%;padding:14px;background:linear-gradient(135deg,#7B1C3E,#550f28);color:white;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">
            <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
    </form>
    <div style="text-align:center;margin-top:20px;">
        <a href="index.php" style="color:#7B1C3E;font-size:13px;font-weight:600;">← Back to Login</a>
    </div>
</div>
</body>
</html>