<?php
// verwaltung/print_flugzeuge.php
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
$flugzeuge = $flugzeuge_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$flugzeuge_stmt->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Druckansicht: Flugzeugliste</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.4; }
        h1 { font-size: 22px; } h2 { font-size: 18px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body onload="window.print()">
    <h1>Flugzeugliste</h1>
    <h2>Fluglager: <?php echo htmlspecialchars($lager['vereinsname']); ?> (<?php echo date('d.m.Y', strtotime($lager['startdatum'])) . ' - ' . date('d.m.Y', strtotime($lager['enddatum'])); ?>)</h2>
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