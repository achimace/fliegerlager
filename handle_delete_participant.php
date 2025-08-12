<?php
// handle_delete_participant.php
session_start();
require_once 'Database.php';

// 1. Sicherheitsprüfungen
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

// 2. Formulardaten abrufen
$participant_id = $_POST['participant_id'] ?? 0;
$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (empty($participant_id) || empty($lager_id)) {
    header('Location: dashboard.php?error=Ungültige Anfrage.');
    exit;
}

// 3. Datenbankverbindung
$db = new Database();
$conn = $db->getConnection();

// 4. Autorisierungsprüfung: Prüfen, ob das Fluglager, zu dem der Teilnehmer gehört,
//    auch wirklich dem eingeloggten Benutzer gehört.
$auth_stmt = $conn->prepare("
    SELECT f.id 
    FROM fluglager f
    JOIN teilnehmer t ON f.id = t.fluglager_id
    WHERE t.id = ? AND f.user_id = ?
");
$auth_stmt->bind_param('ii', $participant_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    // Wenn keine Zeile zurückkommt, hat der User keine Berechtigung!
    header('Location: dashboard.php?error=Zugriff verweigert.');
    exit;
}
$auth_stmt->close();

// 5. Teilnehmer aus der Datenbank löschen
$delete_stmt = $conn->prepare("DELETE FROM teilnehmer WHERE id = ?");
$delete_stmt->bind_param('i', $participant_id);

if ($delete_stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Teilnehmer erfolgreich gelöscht.'));
} else {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Löschen des Teilnehmers.'));
}

$delete_stmt->close();
exit;
?>