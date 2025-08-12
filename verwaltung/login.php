<?php
// verwaltung/login.php
session_start();
require_once '../LoginSystem.php';

// Redirect if admin is already logged in
if (isset($_SESSION['loggedin_admin']) && $_SESSION['loggedin_admin'] === true) {
    header('Location: index.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $loginSystem = new LoginSystem();
    $response = $loginSystem->login($username, $password);

    if ($response['status']) {
        $_SESSION['loggedin_admin'] = true; 
        $_SESSION['admin_user'] = $response['user'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Login fehlgeschlagen. Bitte prüfe deine Anmeldedaten.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="../styles.css">

    <style>
        .login-body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f6f9;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .login-container .logo img {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .login-container h1 {
            margin-bottom: 10px;
        }
        .login-container .subtitle {
            color: #6c757d;
            margin-bottom: 25px;
        }
        .login-container .form-group input {
            width: 100%;
            box-sizing: border-box;
        }
        .login-container button {
            width: 100%;
        }
        .login-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
        }
    </style>

</head>
<body class="login-body">

    <div class="login-container">
        <div class="logo">
            <img src="../pics/logo.png" alt="Flugplatz Ohlstadt Logo">
        </div>
        <h1>Admin-Login</h1>
        <p class="subtitle">Bitte melde dich mit deinen Anmeldedaten an.</p>

        <?php if ($error): ?>
            <div class="login-message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Anmelden</button>
        </form>
    </div>

</body>
</html>