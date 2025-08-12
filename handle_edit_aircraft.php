<?php
// handle_edit_aircraft.php
session_start();
require_once 'Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) { exit; }

// Formulardaten abrufen
$aircraft_id = $_POST['aircraft_id'] ?? 0;
$lager_id = $_POST['lager_id'] ?? 0;
$kennzeichen = trim($_POST['kennzeichen'] ?? '');
$muster = trim($_POST['muster'] ?? '');
$flarm_id = trim($_POST['flarm_id'] ?? '');
$typ = trim($_POST['typ'] ?? '');
$pilot_id = empty($_POST['pilot_id']) ? null : (int)$_POST['pilot_id'];

if (empty($aircraft_id) || empty($lager_id) || empty($kennzeichen)) { exit; }

$db = new Database();
$conn = $db->getConnection();

// Autorisierungsprüfung ...

// Flugzeug aktualisieren (korrigierte Query)
$stmt = $conn->prepare(
    "UPDATE flugzeuge SET kennzeichen=?, muster=?, flarm_id=?, typ=?, pilot_id=? WHERE id = ?"
);
$stmt->bind_param('ssssii', $kennzeichen, $muster, $flarm_id, $typ, $pilot_id, $aircraft_id);

if ($stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Flugzeug erfolgreich aktualisiert.'));
} else {
    header('Location: edit_aircraft.php?id=' . $aircraft_id . '&error=' . urlencode('Fehler beim Speichern der Änderungen.'));
}
$stmt->close();
exit;