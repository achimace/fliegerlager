<?php
// handle_register.php
session_start();
require_once 'Database.php';
require_once 'Mail.php';

// Only execute if the form was sent via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// 1. Get and validate form data
$vorname = trim($_POST['vorname'] ?? '');
$nachname = trim($_POST['nachname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$dsgvo = isset($_POST['dsgvo']);

if (empty($vorname) || empty($nachname) || empty($email) || empty($password) || !$dsgvo) {
    header('Location: register.php?error=' . urlencode('Bitte füllen Sie alle Felder aus und akzeptieren Sie die DSGVO.'));
    exit;
}

// 2. Establish database connection
$db = new Database();
$conn = $db->getConnection();

// 3. Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    header('Location: register.php?error=' . urlencode('Diese E-Mail-Adresse ist bereits registriert.'));
    exit;
}
$stmt->close();

// 4. Hash the password securely
$passwort_hash = password_hash($password, PASSWORD_BCRYPT);

// 5. Generate a unique verification token
$bestaetigungs_token = bin2hex(random_bytes(32));
$token_ablauf = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token is valid for 1 hour

// 6. Insert the new user into the database
$stmt = $conn->prepare(
    "INSERT INTO users (vorname, nachname, email, passwort_hash, email_bestaetigt, bestaetigungs_token, token_ablauf) 
     VALUES (?, ?, ?, ?, FALSE, ?, ?)"
);
$stmt->bind_param('ssssss', $vorname, $nachname, $email, $passwort_hash, $bestaetigungs_token, $token_ablauf);

if ($stmt->execute()) {
    // 7. Send the verification email
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $bestaetigungs_token;
    
    $subject = "Bitte bestätigen Sie Ihre Registrierung";
    $message = "<h1>Hallo $vorname,</h1>
                 <p>vielen Dank für Ihre Registrierung. Bitte klicken Sie auf den folgenden Link, um Ihr Konto zu aktivieren:</p>
                 <p><a href='$verification_link'>Konto jetzt aktivieren</a></p>
                 <p>Der Link ist eine Stunde lang gültig.</p>
                 <p>Mit freundlichen Grüßen,<br>Ihr Team vom Flugplatz Ohlstadt</p>";

    try {
        $mail = new Mail();
        // Call the now fully functional sendEmail method
        $mail->sendEmail($email, $subject, $message, true);
        
        // Redirect to the registration page with a success message
        $success_message = 'Registrierung erfolgreich! Bitte prüfen Sie Ihr E-Mail-Postfach, um Ihr Konto zu aktivieren.';
        header('Location: register.php?message=' . urlencode($success_message));
        exit;

    } catch (Exception $e) {
        // If the email fails, log the error and notify the user
        $log_message = date('Y-m-d H:i:s') . " - Registration Mail Error: " . $e->getMessage() . "\n";
        file_put_contents('mail_errors.log', $log_message, FILE_APPEND);

        $error_message = 'Ihr Konto wurde erstellt, aber die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte kontaktieren Sie den Support.';
        header('Location: register.php?error=' . urlencode($error_message));
        exit;
    }

} else {
    // Error during the database operation
    header('Location: register.php?error=' . urlencode('Fehler bei der Registrierung. Bitte versuchen Sie es erneut.'));
    exit;
}

$stmt->close();
?>