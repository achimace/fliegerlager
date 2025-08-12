<?php
// handle_edit_camp_contact.php
session_start();
require_once 'Database.php';

// Security-Check: Ist der User eingeloggt und ist es eine POST-Anfrage?
if (!isset($_SESSION['loggedin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_customer.php');
    exit;
}

// Formulardaten abrufen und validieren
$lager_id = $_POST['lager_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (empty($lager_id) || empty($user_id)) {
    header('Location: dashboard.php?error=Ungültige Anfrage');
    exit;
}

// Daten aus dem Formular holen und bereinigen
$vorname = trim($_POST['ansprechpartner_vorname'] ?? '');
$nachname = trim($_POST['ansprechpartner_nachname'] ?? '');
$email = trim($_POST['ansprechpartner_email'] ?? '');
$telefon = trim($_POST['ansprechpartner_telefon'] ?? '');

$db = new Database();
$conn = $db->getConnection();

// Sicherheitsprüfung: Gehört dieses Fluglager wirklich dem eingeloggten User?
$stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $lager_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // Wenn nicht, Zugriff verweigern und umleiten
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}

// Wenn alles passt, die Daten in der Datenbank aktualisieren
$update_stmt = $conn->prepare("
    UPDATE fluglager 
    SET ansprechpartner_vorname = ?, ansprechpartner_nachname = ?, ansprechpartner_email = ?, ansprechpartner_telefon = ?
    WHERE id = ?
");
$update_stmt->bind_param('ssssi', $vorname, $nachname, $email, $telefon, $lager_id);

if ($update_stmt->execute()) {
    // Erfolgreich -> Zurück zur Bearbeitungsseite mit Erfolgsmeldung
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=Kontaktdaten erfolgreich aktualisiert.');
} else {
    // Fehler -> Zurück zur Bearbeitungsseite mit Fehlermeldung
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=Fehler beim Aktualisieren der Kontaktdaten.');
}

$update_stmt->close();
$conn->close();
exit;