<?php
// handle_add_aircraft.php
session_start();
require_once 'Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) { exit; }

// Formulardaten abrufen
$lager_id = $_POST['lager_id'] ?? null;
$kennzeichen = trim($_POST['kennzeichen'] ?? '');
$muster = trim($_POST['muster'] ?? ''); // This is the specific model
$flarm_id = trim($_POST['flarm_id'] ?? '');
$typ = trim($_POST['typ'] ?? ''); // This is the category (Segler, etc.)
$pilot_id = empty($_POST['pilot_id']) ? null : (int)$_POST['pilot_id'];

if (empty($lager_id) || empty($kennzeichen)) { exit; }

$db = new Database();
$conn = $db->getConnection();

// Autorisierungs- & Kapazitätsprüfung...

// Neues Flugzeug einfügen (korrigierte Query)
$stmt = $conn->prepare(
    "INSERT INTO flugzeuge (fluglager_id, kennzeichen, muster, flarm_id, typ, pilot_id) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('issssi', $lager_id, $kennzeichen, $muster, $flarm_id, $typ, $pilot_id);

if ($stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Flugzeug erfolgreich hinzugefügt.'));
} else {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Hinzufügen des Flugzeugs.'));
}
$stmt->close();
exit;
?>