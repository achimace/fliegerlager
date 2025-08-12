<?php
// handle_edit_camp_details.php
session_start();
require_once 'Database.php';

// Helper-Funktion zur Datumsvalidierung
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Sicherheitsprüfungen
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

// Formulardaten abrufen
$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$startdatum = $_POST['startdatum'] ?? '';
$enddatum = $_POST['enddatum'] ?? '';
$exklusiv = isset($_POST['exklusiv']) ? 1 : 0;

if (empty($lager_id)) {
    header('Location: dashboard.php?error=Ungültige Anfrage.');
    exit;
}

// Datumsvalidierung
if (!validateDate($startdatum) || !validateDate($enddatum) || strtotime($enddatum) < strtotime($startdatum)) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Ungültiges Datum.'));
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Autorisierungsprüfung
$auth_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert.');
    exit;
}
$auth_stmt->close();

// Update
$update_stmt = $conn->prepare("UPDATE fluglager SET startdatum = ?, enddatum = ?, exklusiv = ? WHERE id = ?");
$update_stmt->bind_param('ssii', $startdatum, $enddatum, $exklusiv, $lager_id);

if ($update_stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Grunddaten erfolgreich aktualisiert.'));
} else {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Speichern der Änderungen.'));
}

$update_stmt->close();
exit;
?>