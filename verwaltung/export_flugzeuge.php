<?php
// verwaltung/export_flugzeuge.php
session_start();
require_once '../Database.php';

if (!isset($_SESSION['loggedin_admin'])) { die('Zugriff verweigert.'); }

$lager_id = $_GET['id'] ?? 0;
if ($lager_id <= 0) { die('Ungültige Anfrage-ID.'); }

$db = new Database();
$conn = $db->getConnection();

// Geändert: Holt jetzt auch das Start- und Enddatum
$lager_stmt = $conn->prepare("SELECT u.vereinsname, f.startdatum, f.enddatum FROM fluglager f JOIN users u ON f.user_id = u.id WHERE f.id = ?");
$lager_stmt->bind_param('i', $lager_id);
$lager_stmt->execute();
$lager = $lager_stmt->get_result()->fetch_assoc();
$lager_stmt->close();

$flugzeuge_stmt = $conn->prepare("SELECT p.*, t.vorname AS pilot_vorname, t.nachname AS pilot_nachname FROM flugzeuge p LEFT JOIN teilnehmer t ON p.pilot_id = t.id WHERE p.fluglager_id = ? ORDER BY p.kennzeichen");
$flugzeuge_stmt->bind_param('i', $lager_id);
$flugzeuge_stmt->execute();
$result = $flugzeuge_stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="flugzeugliste_' . $lager_id . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
// Header-Zeile
fputcsv($output, ['Kennzeichen', 'Muster', 'Typ', 'Pilot'], ';');

// Geändert: Fügt eine Leerzeile und die Lagerinformationen hinzu
fputcsv($output, [], ';');
fputcsv($output, ['Fluglager:', htmlspecialchars($lager['vereinsname'])], ';');
fputcsv($output, ['Zeitraum:', date('d.m.Y', strtotime($lager['startdatum'])) . ' - ' . date('d.m.Y', strtotime($lager['enddatum']))], ';');
fputcsv($output, [], ';');

// Datenzeilen
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['kennzeichen'],
        $row['muster'],
        $row['typ'],
        $row['pilot_vorname'] . ' ' . $row['pilot_nachname']
    ], ';');
}

fclose($output);
$flugzeuge_stmt->close();
exit();