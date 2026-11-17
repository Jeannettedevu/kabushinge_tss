<?php
// login.php
session_start();
require 'db.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Uzuza Username na Password byombi!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_photo'] = (!empty($admin['photo']) && file_exists($admin['photo']))
                    ? $admin['photo']
                    : 'uploads/default_avatar.svg';
                header("Location: index.php");
                exit;
            } else {
                $error = "Username cyangwa Password sibyo! Ongera ugerageze.";
            }
        } catch (PDOException $e) {
            $error = "Habaye ikibazo cya Database: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - KABUSHINGE TSS Library</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.login-page {
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
        }
        .login-box {
            background: #ffffff;
            padding: 35px;
            border-radius: 10px;
            width: 100%;
            max-width: 390px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }
        .login-box h2 {
            text-align: center;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        .login-box p.subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
        .login-box .form-group {
            margin-bottom: 15px;
        }
        .login-box .btn {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            margin-top: 10px;
        }
    </style>
</head>
<body class="login-page">

<div class="login-box">
    <h2>KABUSHINGE TSS</h2>
    <p class="subtitle">Library Management System Login</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

</body>
</html>
