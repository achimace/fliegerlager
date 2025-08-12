<?php
// verify.php

require_once 'Database.php';

// --- Dokumentation ---
// Dieses Skript validiert den E-Mail-Bestätigungs-Token eines neuen Benutzers.
// Wenn der Token gültig und nicht abgelaufen ist, wird das Konto aktiviert.

$message = 'Ungültiger oder abgelaufener Bestätigungslink.'; // Standard-Fehlermeldung

// 1. Token aus der URL holen
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // 2. Datenbankverbindung herstellen
    $db = new Database();
    $conn = $db->getConnection();

    // 3. Benutzer mit dem Token suchen, der noch nicht bestätigt ist und dessen Token nicht abgelaufen ist
    $stmt = $conn->prepare("SELECT id, token_ablauf FROM users WHERE bestaetigungs_token = ? AND email_bestaetigt = FALSE");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $jetzt = new DateTime();
        $token_ablauf_zeit = new DateTime($user['token_ablauf']);

        // 4. Prüfen, ob der Token abgelaufen ist
        if ($jetzt < $token_ablauf_zeit) {
            // 5. Token ist gültig -> Konto aktivieren
            $update_stmt = $conn->prepare("UPDATE users SET email_bestaetigt = TRUE, bestaetigungs_token = NULL, token_ablauf = NULL WHERE id = ?");
            $update_stmt->bind_param('i', $user['id']);
            if ($update_stmt->execute()) {
                // Erfolgsmeldung für die Weiterleitung
                $message = 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt! Sie können sich nun einloggen.';
                // Leite zum Login weiter mit Erfolgsmeldung
                header('Location: login_customer.php?message=' . urlencode($message));
                exit;
            }
            $update_stmt->close();
        }
    }
    $stmt->close();
}

// Bei Fehler oder ungültigem Token zum Login mit Fehlermeldung weiterleiten
header('Location: login_customer.php?message=' . urlencode($message));
exit;
?>