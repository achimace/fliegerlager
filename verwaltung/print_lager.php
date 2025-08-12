<?php
// verwaltung/print_lager.php
session_start();
require_once '../Database.php';
require_once '../helpers.php';

if (!isset($_SESSION['loggedin_admin'])) { die('Zugriff verweigert.'); }

$lager_id = $_GET['id'] ?? 0;
if ($lager_id <= 0) { die('Ungültige Anfrage-ID.'); }

$db = new Database();
$conn = $db->getConnection();

// --- Daten laden ---
$stmt = $conn->prepare("
    SELECT f.*, u.vereinsname, u.vorname, u.nachname, u.email, u.mobiltelefon
    FROM fluglager f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.id = ?
");
$stmt->bind_param('i', $lager_id);
$stmt->execute();
$lager = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lager) { die('Anfrage nicht gefunden.'); }

$teilnehmer_stmt = $conn->prepare("SELECT * FROM teilnehmer WHERE fluglager_id = ? ORDER BY nachname, vorname");
$teilnehmer_stmt->bind_param('i', $lager_id);
$teilnehmer_stmt->execute();
$teilnehmer = $teilnehmer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$teilnehmer_stmt->close();

$flugzeuge_stmt = $conn->prepare("
    SELECT p.*, t.vorname AS pilot_vorname, t.nachname AS pilot_nachname 
    FROM flugzeuge p 
    LEFT JOIN teilnehmer t ON p.pilot_id = t.id 
    WHERE p.fluglager_id = ? 
    ORDER BY p.kennzeichen
");
$flugzeuge_stmt->bind_param('i', $lager_id);
$flugzeuge_stmt->execute();
$flugzeuge = $flugzeuge_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$flugzeuge_stmt->close();

// --- Zusammenfassung berechnen ---
$summary = ['piloten' => 0, 'flugschueler' => 0, 'begleitpersonen' => 0];
foreach ($teilnehmer as $person) {
    if ($person['rolle'] === 'Pilot') $summary['piloten']++;
    if ($person['rolle'] === 'Flugschüler') $summary['flugschueler']++;
    if ($person['rolle'] === 'Begleitperson') $summary['begleitpersonen']++;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Druckansicht: Fluglager <?php echo htmlspecialchars($lager['vereinsname']); ?></title>
    <style>
        /* Grundlegende Stile für die Bildschirmansicht */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        h1, h2 {
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        h1 { font-size: 22px; }
        h2 { font-size: 18px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px; /* Reduziertes Padding */
            text-align: left;
        }
        th { background-color: #f2f2f2; }
        .header-grid {
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 8px;
        }
        .info-grid strong { font-weight: bold; }
        .summary-box {
            border: 1px solid #ccc;
            padding: 15px;
            background-color: #f9f9f9;
        }
        
        /* Druck-spezifische Optimierungen */
        @media print {
            @page {
                size: A4;
                margin: 1.5cm; /* Kleinere Ränder */
            }
            body {
                font-size: 10pt; /* Kleinere Schriftgröße */
                margin: 0;
                color: #000;
            }
            h1 { font-size: 18pt; }
            h2 { font-size: 14pt; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <h1>Fluglager-Übersicht: <?php echo htmlspecialchars($lager['vereinsname']); ?></h1>
    <p>Zeitraum: <strong><?php echo date('d.m.Y', strtotime($lager['startdatum'])) . ' - ' . date('d.m.Y', strtotime($lager['enddatum'])); ?></strong> | Stand: <?php echo date('d.m.Y H:i'); ?> Uhr</p>

    <div class="header-grid">
        <div class="stammdaten">
            <h2>Stammdaten</h2>
            <div class="info-grid">
                <strong>Verein:</strong>
                <span><?php echo htmlspecialchars($lager['vereinsname']); ?></span>
                <strong>Status:</strong>
                <span><?php echo ucfirst($lager['status']); ?></span>
                <strong>Exklusiv:</strong>
                <span><?php echo $lager['exklusiv'] ? 'Ja' : 'Nein'; ?></span>
                <strong>Organisator:</strong>
                <span><?php echo htmlspecialchars($lager['ansprechpartner_vorname'] . ' ' . $lager['ansprechpartner_nachname']); ?></span>
                <strong>Telefon:</strong>
                <span><?php echo htmlspecialchars($lager['ansprechpartner_telefon']); ?></span>
                <strong>E-Mail:</strong>
                <span><?php echo htmlspecialchars($lager['ansprechpartner_email']); ?></span>
            </div>
        </div>
        <div class="summary-box">
            <h2>Zusammenfassung</h2>
            <div class="info-grid">
                <strong>Teilnehmer:</strong>
                <span><?php echo count($teilnehmer); ?></span>
                <strong>- Piloten:</strong>
                <span><?php echo $summary['piloten']; ?></span>
                <strong>- Flugschüler:</strong>
                <span><?php echo $summary['flugschueler']; ?></span>
                <strong>- Begleitung:</strong>
                <span><?php echo $summary['begleitpersonen']; ?></span>
                <strong>Flugzeuge:</strong>
                <span><?php echo count($flugzeuge); ?></span>
            </div>
        </div>
    </div>

    <h2>Teilnehmerliste</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Funktion</th>
                <th>Camping</th>
                <th>E-Mail</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teilnehmer as $person): ?>
            <tr>
                <td><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td>
                <td><?php echo htmlspecialchars($person['rolle']); ?></td>
                <td><?php echo $person['camping'] ? 'Ja' : 'Nein'; ?></td>
                <td><?php echo htmlspecialchars($person['email']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Flugzeugliste</h2>
    <table>
        <thead>
            <tr>
                <th>Kennzeichen</th>
                <th>Muster</th>
                <th>Typ</th>
                <th>Pilot</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($flugzeuge as $flugzeug): ?>
            <tr>
                <td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td>
                <td><?php echo htmlspecialchars($flugzeug['muster']); ?></td>
                <td><?php echo htmlspecialchars($flugzeug['typ']); ?></td>
                <td><?php echo htmlspecialchars($flugzeug['pilot_vorname'] . ' ' . $flugzeug['pilot_nachname']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
</body>
</html>