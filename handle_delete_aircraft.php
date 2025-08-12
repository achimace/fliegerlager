<?php
// handle_delete_aircraft.php
session_start();
require_once 'Database.php';

// Sicherheitsprüfungen
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

$aircraft_id = $_POST['aircraft_id'] ?? 0;
$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (empty($aircraft_id) || empty($lager_id)) {
    header('Location: dashboard.php?error=Ungültige Anfrage.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Autorisierungsprüfung
$auth_stmt = $conn->prepare("
    SELECT f.id FROM fluglager f JOIN flugzeuge p ON f.id = p.fluglager_id WHERE p.id = ? AND f.user_id = ?");
$auth_stmt->bind_param('ii', $aircraft_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert.');
    exit;
}
$auth_stmt->close();

// Löschen
$delete_stmt = $conn->prepare("DELETE FROM flugzeuge WHERE id = ?");
$delete_stmt->bind_param('i', $aircraft_id);

if ($delete_stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Flugzeug erfolgreich gelöscht.'));
} else {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Löschen des Flugzeugs.'));
}

$delete_stmt->close();
exit;
?>