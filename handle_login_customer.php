<?php
// handle_login_customer.php
session_start();
require_once 'Database.php';

// --- Dokumentation ---
// Verarbeitet den Kunden-Login.
// Prüft E-Mail und Passwort, verifiziert den Kontostatus und startet die Session.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_customer.php');
    exit;
}

// 1. Formulardaten abrufen
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login_customer.php?error=Bitte E-Mail und Passwort eingeben.');
    exit;
}

// 2. Datenbankverbindung
$db = new Database();
$conn = $db->getConnection();

// 3. Benutzerdaten aus der DB abrufen
$stmt = $conn->prepare("SELECT id, vorname, passwort_hash, email_bestaetigt FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // 4. Passwort und Kontostatus prüfen
    if (password_verify($password, $user['passwort_hash'])) {
        // Passwort ist korrekt. Nun prüfen, ob das Konto aktiviert ist.
        if ($user['email_bestaetigt']) {
            // 5. Erfolgreich! Session-Variablen setzen
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_vorname'] = $user['vorname'];
            
            // Leite zum Dashboard weiter
            header('Location: dashboard.php');
            exit;
        } else {
            // Konto noch nicht bestätigt
            header('Location: login_customer.php?error=Ihr Konto wurde noch nicht per E-Mail bestätigt.');
            exit;
        }
    }
}

// Wenn Benutzer nicht gefunden wurde oder Passwort falsch ist
header('Location: login_customer.php?error=E-Mail oder Passwort ist ungültig.');
exit;
?>