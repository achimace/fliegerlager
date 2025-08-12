<?php
// handle_edit_participant.php
session_start();
require_once 'Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

// Alle Formulardaten abrufen
$participant_id = $_POST['participant_id'] ?? 0;
$lager_id = $_POST['lager_id'] ?? 0;
$vorname = trim($_POST['vorname'] ?? '');
$nachname = trim($_POST['nachname'] ?? '');
$geburtsdatum = empty($_POST['geburtsdatum']) ? null : $_POST['geburtsdatum'];
$email = trim($_POST['email'] ?? '');
$vereinsflieger_nr = trim($_POST['vereinsflieger_nr'] ?? '');
$aufenthalt_von = empty($_POST['aufenthalt_von']) ? null : $_POST['aufenthalt_von'];
$aufenthalt_bis = empty($_POST['aufenthalt_bis']) ? null : $_POST['aufenthalt_bis'];
$camping = isset($_POST['camping']) ? 1 : 0;
$rolle = trim($_POST['rolle'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($participant_id) || empty($lager_id) || empty($vorname) || empty($nachname)) {
    header('Location: dashboard.php?error=Ungültige Anfrage.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Autorisierungsprüfung (wie zuvor)
$auth_stmt = $conn->prepare("SELECT f.id FROM teilnehmer t JOIN fluglager f ON t.fluglager_id = f.id WHERE t.id = ? AND f.user_id = ?");
$auth_stmt->bind_param('ii', $participant_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert.');
    exit;
}
$auth_stmt->close();


// Teilnehmer aktualisieren
$stmt = $conn->prepare(
    "UPDATE teilnehmer SET vorname=?, nachname=?, geburtsdatum=?, email=?, vereinsflieger_nr=?, aufenthalt_von=?, aufenthalt_bis=?, camping=?, rolle=? WHERE id = ?"
);

// KORRIGIERTE ZEILE: Typen-String angepasst (s, s, s, s, s, s, s, i, s, i)
$stmt->bind_param('sssssssisi', $vorname, $nachname, $geburtsdatum, $email, $vereinsflieger_nr, $aufenthalt_von, $aufenthalt_bis, $camping, $rolle, $participant_id);

if ($stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Teilnehmer erfolgreich aktualisiert.'));
} else {
    header('Location: edit_participant.php?id=' . $participant_id . '&error=' . urlencode('Fehler beim Speichern der Änderungen.'));
}
$stmt->close();
exit;
?>