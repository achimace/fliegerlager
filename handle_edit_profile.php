<?php
// handle_edit_profile.php
session_start();
require_once 'Database.php';

// Sicherheitsprüfungen
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Formulardaten abrufen
$vorname = trim($_POST['vorname'] ?? '');
$nachname = trim($_POST['nachname'] ?? '');
$mobiltelefon = trim($_POST['mobiltelefon'] ?? '');
$vereinsname = trim($_POST['vereinsname'] ?? '');
$strasse = trim($_POST['strasse'] ?? '');
$plz = trim($_POST['plz'] ?? '');
$ort = trim($_POST['ort'] ?? '');

if (empty($vorname) || empty($nachname)) {
    header('Location: edit_profile.php?error=Vor- und Nachname sind Pflichtfelder.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Benutzerdaten aktualisieren
$stmt = $conn->prepare("
    UPDATE users 
    SET vorname = ?, nachname = ?, mobiltelefon = ?, vereinsname = ?, strasse = ?, plz = ?, ort = ?
    WHERE id = ?
");
$stmt->bind_param('sssssssi', $vorname, $nachname, $mobiltelefon, $vereinsname, $strasse, $plz, $ort, $user_id);

if ($stmt->execute()) {
    // Session-Namen aktualisieren, falls geändert
    $_SESSION['user_vorname'] = $vorname;
    header('Location: dashboard.php?message=' . urlencode('Ihre Daten wurden erfolgreich aktualisiert.'));
} else {
    header('Location: edit_profile.php?error=' . urlencode('Fehler beim Speichern der Daten.'));
}

$stmt->close();
exit;
?>