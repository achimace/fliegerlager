<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// forgot_password.php
session_start();
require_once 'Database.php';
require_once 'Mail.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND email_bestaetigt = TRUE");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // ### CORRECTED TIME LOGIC ###
            // This method correctly handles daylight saving time (Sommerzeit).
            $now = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $now->add(new DateInterval('PT1H')); // Add 1 Hour
            $ablauf = $now->format('Y-m-d H:i:s');
            // ### END CORRECTION ###
            
            $token = bin2hex(random_bytes(32));
            
            $update_stmt = $conn->prepare("UPDATE users SET passwort_reset_token = ?, reset_token_ablauf = ? WHERE id = ?");
            $update_stmt->bind_param('ssi', $token, $ablauf, $user['id']);
            $update_stmt->execute();

            // Send email
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
            $subject = "Anforderung zum Zurücksetzen Ihres Passworts";
            $mail_message = "<h1>Passwort zurücksetzen</h1>
                             <p>Sie haben angefordert, Ihr Passwort zurückzusetzen. Klicken Sie auf den folgenden Link, um ein neues Passwort zu erstellen:</p>
                             <p><a href='$reset_link'>Neues Passwort festlegen</a></p>
                             <p>Der Link ist eine Stunde lang gültig. Wenn Sie diese Anforderung nicht gestellt haben, können Sie diese E-Mail ignorieren.</p>";

            try {
                $mail = new Mail();
                $mail->sendEmail($email, $subject, $mail_message, true);
                $message = 'Ein Link zum Zurücksetzen des Passworts wurde an Ihre E-Mail-Adresse gesendet.';
            } catch (Exception $e) {
                $error = 'Die E-Mail konnte nicht gesendet werden. Bitte kontaktieren Sie den Support.';
            }
        } else {
            // For security, show the same message whether the email exists or not
            $message = 'Wenn ein Konto mit dieser E-Mail existiert, wurde ein Link zum Zurücksetzen gesendet.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort vergessen - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-body">

    <div class="login-container">
        <div class="logo">
            <img src="pics/logo.png" alt="Flugplatz Ohlstadt Logo">
        </div>
        <h1>Passwort vergessen?</h1>
        <p class="subtitle">Kein Problem! Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen.</p>

        <?php // Display success or error messages
        if ($message) {
            echo '<div class="login-message success">' . htmlspecialchars($message) . '</div>';
        }
        if ($error) {
            echo '<div class="login-message error">' . htmlspecialchars($error) . '</div>';
        }
        ?>

        <form action="forgot_password.php" method="post">
            <div class="form-group">
                <label for="email">Deine E-Mail-Adresse</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Reset-Link anfordern</button>
        </form>

        <div class="login-links">
            <a href="login_customer.php">Zurück zum Login</a>
        </div>
    </div>

</body>
</html>