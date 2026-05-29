<?php
session_start();
require_once 'db_config.php';

$error = '';

if ($_POST) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$user'");
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($pass, $row['password'])) {
            $_SESSION['username'] = $user;
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login | Easy 2x2 Bayanihan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 50%, #bfdbfe 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .card-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 28px 24px;
            text-align: center;
            color: white;
        }

        .logo-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header h1 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .card-header p {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 4px;
            text-transform: uppercase;
            font-weight: 500;
        }

        .card-body {
            padding: 32px 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        button {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 14px;
            cursor: pointer;
            transition: transform 0.1s ease, opacity 0.2s;
            margin-top: 8px;
        }

        button:active {
            transform: scale(0.98);
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 14px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #ef4444;
        }

        .helper-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.7rem;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
        }

        .helper-text a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .card-body {
                padding: 24px 20px;
            }
            .card-header {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="card">
        <div class="card-header">
            <div class="logo-icon">B</div>
            <h1>Easy 2x2</h1>
            <p>Bayanihan Program</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit">Login →</button>
            </form>

            <div class="helper-text">
                First time? <a href="register.php">Create an account</a><br>
                <span style="font-size: 0.65rem;">Demo: admin / admin123</span>
            </div>
        </div>
    </div>
</div>
</body>
</html>