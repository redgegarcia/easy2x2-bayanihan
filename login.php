<?php
session_start();
require_once 'db_config.php';

$error = '';
$step = 'email';
$email = '';
$message = '';

function sendOTPEmail($to_email, $otp) {
    $subject = "Your Login OTP - Easy 2x2";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Easy 2x2 Bayanihan <noreply@kalugar.com>\r\n";
    
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Easy 2x2 Bayanihan Program</h2>
        <p>Your One-Time Password (OTP) is:</p>
        <h1 style='color: #2563eb; font-size: 32px;'>{$otp}</h1>
        <p>This OTP is valid for 10 minutes.</p>
        <hr>
        <small>Easy 2x2 Bayanihan Program</small>
    </body>
    </html>
    ";
    
    return mail($to_email, $subject, $body, $headers);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_otp'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $otp = rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            mysqli_query($conn, "DELETE FROM otps WHERE email = '$email'");
            mysqli_query($conn, "INSERT INTO otps (email, otp, expires_at) VALUES ('$email', '$otp', '$expires')");
            
            if (sendOTPEmail($email, $otp)) {
                $step = 'otp';
                $message = "✓ OTP sent to $email";  // IISA LANG ITO
            } else {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            $error = "Email not found!";
        }
    } elseif (isset($_POST['verify_otp'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $otp = mysqli_real_escape_string($conn, $_POST['otp']);
        
        $check = mysqli_query($conn, "SELECT * FROM otps WHERE email = '$email' AND otp = '$otp' AND expires_at > NOW() AND is_used = 0");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE otps SET is_used = 1 WHERE email = '$email' AND otp = '$otp'");
            
            $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE email = '$email'"));
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid or expired OTP!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Easy 2x2</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 32px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 32px;
            text-align: center;
            color: white;
        }
        .logo-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.15);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 12px;
        }
        .card-header h1 { font-size: 1.4rem; }
        .card-body { padding: 32px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; font-weight: 700; margin-bottom: 6px; color: #334155; text-transform: uppercase; }
        input {
            width: 100%;
            padding: 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.9rem;
        }
        input:focus { outline: none; border-color: #2563eb; }
        button {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
        }
        .error { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 0.8rem; }
        .message { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="logo-icon">B</div>
        <h1>Easy 2x2</h1>
        <p style="font-size:0.7rem; opacity:0.8;">Bayanihan Program</p>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($step == 'email'): ?>
            <form method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="your@email.com" required autofocus>
                </div>
                <button type="submit" name="send_otp">Send OTP →</button>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <div class="form-group">
                    <label>Enter OTP</label>
                    <input type="text" name="otp" placeholder="6-digit code" maxlength="6" required autofocus>
                </div>
                <button type="submit" name="verify_otp">Verify OTP →</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>