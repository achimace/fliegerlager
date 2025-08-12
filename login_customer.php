<?php
// login_customer.php
session_start();

// Redirect if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-body">

    <div class="login-container">
        <div class="logo">
            <img src="pics/logo.png" alt="Flugplatz Ohlstadt Logo">
        </div>
        <h1>Willkommen zurück!</h1>
        <p class="subtitle">Melde dich an, um ein Fluglager in Ohlstadt zu organisieren.</p>

        <?php // Display success or error messages
        if (isset($_GET['message'])) {
            echo '<div class="login-message success">' . htmlspecialchars($_GET['message']) . '</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="login-message error">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>

        <form action="handle_login_customer.php" method="post">
            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Anmelden</button>
        </form>

        <div class="login-links">
            <a href="forgot_password.php">Passwort vergessen?</a> | <a href="register.php">Noch kein Konto?</a>
        </div>
    </div>

</body>
</html>