<?php
// register.php
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
    <title>Registrierung - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-body">

    <div class="login-container">
        <div class="logo">
            <img src="pics/logo.png" alt="Flugplatz Ohlstadt Logo">
        </div>
        <h1>Neues Konto erstellen</h1>
        <p class="subtitle">Registriere dich, um Fluglager zu planen und zu verwalten.</p>

        <?php // Display success or error messages
        if (isset($_GET['message'])) {
            echo '<div class="login-message success">' . htmlspecialchars($_GET['message']) . '</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="login-message error">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>

        <form action="handle_register.php" method="post">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="vorname">Vorname</label>
                    <input type="text" id="vorname" name="vorname" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="nachname">Nachname</label>
                    <input type="text" id="nachname" name="nachname" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Passwort (min. 8 Zeichen)</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group" style="text-align: left; font-size: 14px;">
                <input type="checkbox" id="dsgvo" name="dsgvo" required style="width: auto; margin-right: 10px;">
                <label for="dsgvo" style="display: inline;">Ich stimme der <a href="/datenschutz.html" target="_blank">Datenschutzerklärung</a> zu.</label>
            </div>
            <button type="submit">Konto erstellen</button>
        </form>

        <div class="login-links">
            <a href="login_customer.php">Bereits ein Konto? Jetzt anmelden</a>
        </div>
    </div>

</body>
</html>