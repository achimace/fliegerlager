<?php
// verwaltung/export.php
session_start();
require_once '../Database.php';

// 1. Admin-Login und Parameter prüfen
if (!isset($_SESSION['loggedin_admin']) || !isset($_GET['id']) || !isset($_GET['type'])) {
    // Falls nicht eingeloggt oder Parameter fehlen, abbruch.
    exit('Zugriff verweigert.');
}

$lager_id = $_GET['id'];
$export_type = $_GET['type'];

$db = new Database();
$conn = $db->getConnection();

$data = [];
$filename = "export.csv";
$headers = [];

// 2. Daten basierend auf dem Export-Typ abrufen
if ($export_type === 'teilnehmer') {
    $filename = "teilnehmer_lager_" . $lager_id . ".csv";
    $headers = ['Vorname', 'Nachname', 'Geburtsdatum', 'Telefon', 'Email', 'Camping', 'Rolle'];
    $stmt = $conn->prepare("SELECT vorname, nachname, geburtsdatum, telefon, email, camping, rolle FROM teilnehmer WHERE fluglager_id = ?");
    $stmt->bind_param('i', $lager_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} elseif ($export_type === 'flugzeuge') {
    $filename = "flugzeuge_lager_" . $lager_id . ".csv";
    $headers = ['Kennzeichen', 'Typ', 'FLARM ID', 'SPOT'];
    $stmt = $conn->prepare("SELECT kennzeichen, typ, flarm_id, spot FROM flugzeuge WHERE fluglager_id = ?");
    $stmt->bind_param('i', $lager_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} else {
    exit('Ungültiger Export-Typ.');
}

// 3. CSV-Datei generieren und zum Download anbieten
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM für Excel-Kompatibilität hinzufügen
fputs($output, "\xEF\xBB\xBF");

// Header-Zeile schreiben
fputcsv($output, $headers);

// Datenzeilen schreiben
foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>