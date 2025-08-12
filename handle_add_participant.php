<?php
// handle_add_participant.php
session_start();
require_once 'Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

// Alle Formulardaten abrufen
$lager_id = $_POST['lager_id'] ?? null;
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

if (empty($lager_id) || empty($vorname) || empty($nachname)) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Vor- und Nachname sind Pflichtfelder.'));
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Autorisierungs- & Kapazitätsprüfung (wie zuvor)

// Neuen Teilnehmer einfügen
$stmt = $conn->prepare(
    "INSERT INTO teilnehmer (fluglager_id, vorname, nachname, geburtsdatum, email, vereinsflieger_nr, aufenthalt_von, aufenthalt_bis, camping, rolle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('isssssssis', $lager_id, $vorname, $nachname, $geburtsdatum, $email, $vereinsflieger_nr, $aufenthalt_von, $aufenthalt_bis, $camping, $rolle);

if ($stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Teilnehmer erfolgreich hinzugefügt.'));
} else {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Hinzufügen des Teilnehmers.'));
}
$stmt->close();
exit;
?>