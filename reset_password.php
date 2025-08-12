<?php
// reset_password.php
require_once 'Database.php';

$token_is_valid = false;
$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Kein Token angegeben.";
} else {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT id FROM users WHERE passwort_reset_token = ? AND reset_token_ablauf > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $token_is_valid = true;
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // Handle form submission for the new password
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($password) || $password !== $password_confirm) {
                $error = 'Die Passwörter stimmen nicht überein oder sind leer.';
            } elseif (strlen($password) < 8) {
                $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
            } else {
                // Hash and update password in DB
                $passwort_hash = password_hash($password, PASSWORD_BCRYPT);
                $update_stmt = $conn->prepare("UPDATE users SET passwort_hash = ?, passwort_reset_token = NULL, reset_token_ablauf = NULL WHERE id = ?");
                $update_stmt->bind_param('si', $passwort_hash, $user_id);
                $update_stmt->execute();

                // Redirect to login with success message
                header('Location: login_customer.php?message=' . urlencode('Ihr Passwort wurde erfolgreich zurückgesetzt.'));
                exit;
            }
        }
    } else {
        $error = "Der Link ist ungültig oder abgelaufen.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Neues Passwort festlegen - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-body">

    <div class="login-container">
        <div class="logo">
            <img src="pics/logo.png" alt="Flugplatz Ohlstadt Logo">
        </div>
        <h1>Neues Passwort festlegen</h1>
        
        <?php if ($error): ?>
            <div class="login-message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($token_is_valid): ?>
            <p class="subtitle">Bitte gib dein neues Passwort ein.</p>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
                <div class="form-group">
                    <label for="password">Neues Passwort (min. 8 Zeichen)</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Neues Passwort bestätigen</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                <button type="submit">Passwort speichern</button>
            </form>
        <?php else: ?>
            <div class="login-links">
                <a href="login_customer.php">Zurück zum Login</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>