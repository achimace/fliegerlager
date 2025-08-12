<?php
// get_events.php
header('Content-Type: application/json');
require_once 'Database.php';

// --- Dokumentation ---
// Dieses Skript liefert alle Events für den Belegungskalender.
// Es holt bestätigte Fluglager und manuell geblockte Zeiträume.

$db = new Database();
$conn = $db->getConnection();
$events = [];

// 1. Bestätigte Fluglager laden
$query_lager = "SELECT f.startdatum, f.enddatum, u.vorname, u.nachname 
                FROM fluglager f 
                JOIN users u ON f.user_id = u.id 
                WHERE f.status = 'bestaetigt'";
$result_lager = $conn->query($query_lager);
while ($row = $result_lager->fetch_assoc()) {
    $events[] = [
        'title' => 'Belegt (Lager von ' . $row['vorname'] . ')',
        'start' => $row['startdatum'],
        'end'   => date('Y-m-d', strtotime($row['enddatum'] . ' +1 day')), // Enddatum ist exklusiv in FullCalendar
        'color' => '#6F9ED4' // Blau für bestätigte Lager
    ];
}

// 2. Manuell geblockte Zeiten laden
$query_block = "SELECT * FROM kalender_block";
$result_block = $conn->query($query_block);
while ($row = $result_block->fetch_assoc()) {
    $events[] = [
        'title' => 'Gesperrt: ' . $row['grund'],
        'start' => $row['startdatum'],
        'end'   => date('Y-m-d', strtotime($row['enddatum'] . ' +1 day')),
        'color' => '#989898' // Grau für Sperrungen
    ];
}

echo json_encode($events);