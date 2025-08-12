<?php
// verwaltung/view_lager.php
session_start();
require_once '../Database.php';
require_once '../helpers.php';

// Admin-Login prüfen
if (!isset($_SESSION['loggedin_admin']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit;
}

$lager_id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();

// --- Fetch all necessary data ---
// a) Main flight camp data & requester
$stmt = $conn->prepare("
    SELECT f.*, u.vorname, u.nachname, u.email, u.vereinsname 
    FROM fluglager f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.id = ?
");
$stmt->bind_param('i', $lager_id);
$stmt->execute();
$result = $stmt->get_result();

// ### CRITICAL FIX: Check if a camp was actually found ###
if ($result->num_rows !== 1) {
    // If no record is found, redirect with an error and stop the script.
    header('Location: index.php?error=' . urlencode('Anfrage mit dieser ID nicht gefunden.'));
    exit;
}
$lager = $result->fetch_assoc();
echo "<pre>";
print_r($lager);
echo "</pre>";
$stmt->close();

// b) Participants
$teilnehmer = $conn->query("SELECT * FROM teilnehmer WHERE fluglager_id = $lager_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// c) Aircraft
$flugzeuge = $conn->query("SELECT * FROM flugzeuge WHERE fluglager_id = $lager_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// d) Change History (using 'id' for ordering)
$historie = $conn->query("SELECT * FROM status_log WHERE fluglager_id = $lager_id ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// --- Price & Billing Calculation (only if status is correct) ---
$summary_data = ['teilnehmer' => [], 'flugzeuge' => []];
$gesamtsumme = 0.00;
$preise = [];

if ($lager['status'] === 'abrechnung_gesendet' || $lager['status'] === 'fertig_abgerechnet') {
    // Fetch the correct price list for this flight camp
    $price_stmt = $conn->prepare("SELECT preis_name, wert FROM preise WHERE gueltig_ab <= ? AND id IN (SELECT MAX(id) FROM preise WHERE gueltig_ab <= ? GROUP BY preis_name)");
    $camp_start_date = $lager['startdatum'];
    $price_stmt->bind_param('ss', $camp_start_date, $camp_start_date);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    while ($row = $price_result->fetch_assoc()) {
        $preise[$row['preis_name']] = $row['wert'];
    }
    $price_stmt->close();

    // Perform the billing calculation
    foreach ($teilnehmer as $person) {
        if (empty($person['hat_teilgenommen'])) continue;
        $person_total = 0; $details = [];
        if ($person['rolle'] === 'Pilot' || $person['rolle'] === 'Flugschüler') {
            $start = new DateTime($person['aufenthalt_von'] ?? $lager['startdatum']);
            $end = new DateTime($person['aufenthalt_bis'] ?? $lager['enddatum']);
            $tage = max(1, $end->diff($start)->days + 1);
            $kosten_tage = $tage * ($preise['pilot_pro_tag'] ?? 0);
            if ($kosten_tage > 0) { $details[] = "$tage Tag(e) Anwesenheit: " . number_format($kosten_tage, 2, ',', '.') . " €"; $person_total += $kosten_tage; }
        }
        $camping_nights = (int)($person['abrechnung_naechte_camping'] ?? 0);
        if ($camping_nights > 0) {
            $kosten_camping = $camping_nights * ($preise['camping_pro_nacht'] ?? 0);
            $details[] = "$camping_nights Nächte Camping: " . number_format($kosten_camping, 2, ',', '.') . " €";
            $person_total += $kosten_camping;
        }
        if($person_total > 0) {
            $summary_data['teilnehmer'][] = ['name' => $person['vorname'] . ' ' . $person['nachname'], 'details' => $details, 'total' => $person_total];
            $gesamtsumme += $person_total;
        }
    }
    foreach ($flugzeuge as $flugzeug) {
        if (empty($flugzeug['hat_teilgenommen'])) continue;
        $plane_total = 0; $details = [];
        $start = new DateTime($flugzeug['abrechnung_anreise'] ?? $lager['startdatum']);
        $end = new DateTime($flugzeug['abrechnung_abreise'] ?? $lager['enddatum']);
        $tage_stationierung = max(1, $end->diff($start)->days + 1);
        $kosten_stationierung = $tage_stationierung * ($preise['flugzeug_stationierung_pro_tag'] ?? 0);
        if ($kosten_stationierung > 0) { $details[] = "$tage_stationierung Tag(e) Stationierung: " . number_format($kosten_stationierung, 2, ',', '.') . " €"; $plane_total += $kosten_stationierung; }
        $tage_halle = (int)($flugzeug['abrechnung_tage_halle'] ?? 0);
        if ($tage_halle > 0) {
            $kosten_halle = $tage_halle * ($preise['flugzeug_halle_pro_tag'] ?? 0);
            $details[] = "$tage_halle Tage Halle: " . number_format($kosten_halle, 2, ',', '.') . " €";
            $plane_total += $kosten_halle;
        }
        if($plane_total > 0) {
            $summary_data['flugzeuge'][] = ['name' => $flugzeug['kennzeichen'], 'details' => $details, 'total' => $plane_total];
            $gesamtsumme += $plane_total;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Anfragedetails</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">
    <header class="app-header">
        <div class="logo"><a href="index.php"><img src="../pics/logo.png" alt="Logo"></a></div>
        <nav class="app-header-nav">
            <a href="index.php">Anfragen</a>
            <a href="sperrzeiten.php">Sperrzeiten</a>
            <a href="preise.php">Preise</a>
            <a href="einstellungen.php">Einstellungen</a>
            <span>|</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>
    <div class="app-container">
        <h1>Details für Fluglager von <?php echo htmlspecialchars($lager['vereinsname']); ?></h1>
        <a href="print_lager.php?id=<?php echo $lager_id; ?>" target="_blank" class="btn">
                <i class="fa-solid fa-print"></i> Drucken
            </a>
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

<?php if ($lager['status'] === 'abrechnung_gesendet' || $lager['status'] === 'fertig_abgerechnet'): ?>
        <div class="section" id="summary-section">
            <h3>Zusammenfassung der Abrechnung</h3>
            <p>Die folgenden Daten wurden vom Kunden übermittelt.</p>
            
            <?php if (!empty($summary_data['teilnehmer'])): ?>
            <h4>Teilnehmer</h4>
            <table class="styled-table">
                <thead><tr><th>Name</th><th>Posten</th><th style="text-align: right;">Betrag</th></tr></thead>
                <tbody>
                <?php foreach($summary_data['teilnehmer'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                        <td><?php echo implode('<br>', $item['details']); ?></td>
                        <td style="text-align: right;"><strong><?php echo number_format($item['total'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php if (!empty($summary_data['flugzeuge'])): ?>
            <h4 style="margin-top: 20px;">Flugzeuge</h4>
            <table class="styled-table">
                 <thead><tr><th>Kennzeichen</th><th>Posten</th><th style="text-align: right;">Betrag</th></tr></thead>
                <tbody>
                <?php foreach($summary_data['flugzeuge'] as $item): ?>
                     <tr>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                        <td><?php echo implode('<br>', $item['details']); ?></td>
                        <td style="text-align: right;"><strong><?php echo number_format($item['total'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
             <?php endif; ?>
            <h3 style="text-align: right; margin-top: 20px;">Gesamtsumme: <?php echo number_format($gesamtsumme, 2, ',', '.'); ?> €</h3>
            <div class="status-form">
                <h4>Abrechnung bearbeiten & abschließen</h4>
                <?php if ($lager['status'] !== 'fertig_abgerechnet'): ?>
                <form action="handle_admin_corrections.php" method="post" style="margin-bottom: 20px;">
                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                    <div class="form-group"><label for="korrektur_betrag">Korrekturbetrag (€) - negativ für Rabatt, positiv für Zusatzkosten</label><input type="number" step="0.01" id="korrektur_betrag" name="korrektur_betrag" style="width: 200px;"></div>
                     <div class="form-group"><label for="korrektur_grund">Grund der Korrektur (wird im Verlauf geloggt)</label><textarea id="korrektur_grund" name="korrektur_grund" required></textarea></div>
                    <button type="submit" name="action" value="add_correction" class="btn">Korrektur hinzufügen</button>
                </form>
                <hr>
                <form action="handle_admin_corrections.php" method="post" onsubmit="return confirm('Möchten Sie diese Abrechnung wirklich als FERTIG markieren? Der Kunde wird benachrichtigt.');">
                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                    <button type="submit" name="action" value="accept_billing" class="btn btn-success"><i class="fa-solid fa-check-double"></i> Abrechnung akzeptieren & abschließen</button>
                </form>
                <?php else: ?>
                    <p class="message-info">Diese Abrechnung wurde bereits abgeschlossen.</p>
                <?php endif; ?>
            </div>
        </div>
<?php else: ?>

        <div class="section">
            <h3>Aktion durchführen</h3>
            <form action="handle_decision.php" method="post">
                <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
                <div class="form-group"><label for="kommentar">Kommentar für den Kunden (optional):</label><textarea id="kommentar" name="kommentar" rows="3" style="width: 100%;"></textarea></div>
                <button type="submit" name="entscheidung" value="bestaetigt" class="btn btn-success"><i class="fa-solid fa-check"></i> Bestätigen</button>
                <button type="submit" name="entscheidung" value="abgelehnt" class="btn btn-danger"><i class="fa-solid fa-times"></i> Ablehnen</button>
            </form>
        </div>
	<?php if ($lager['status'] === "bestaetigt"): ?>       
        <div class="section">
            <h3>Zahlungsstatus Anzahlung</h3>
            <?php if ($lager['anzahlung_bezahlt']): ?>
                <p class="message-success">Anzahlung wurde am <?php echo date('d.m.Y', strtotime($lager['anzahlung_bezahlt_am'])); ?> als bezahlt markiert.</p>
            <?php else: ?>
                <p>Die Anzahlung für dieses Fluglager wurde noch nicht als bezahlt markiert.</p>
                <form action="handle_payment_status.php" method="post" onsubmit="return confirm('Möchten Sie die Anzahlung für dieses Fluglager wirklich als bezahlt markieren?');">
                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                    <button type="submit" name="status" value="anzahlung_bezahlt" class="btn btn-success"><i class="fa-solid fa-euro-sign"></i> Anzahlung als bezahlt markieren</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
        <div class="section">
            <h3>Anfragedaten / Status: <?php echo getStatusBadge($lager['status']); ?></h3> 
			 <p><strong>Verein:</strong> <?php echo htmlspecialchars($lager['vereinsname']); ?></p>
            <p><strong>Anfragesteller:</strong> <?php echo htmlspecialchars($lager['vorname'] . ' ' . $lager['nachname']); ?> (<?php echo htmlspecialchars($lager['email']); ?>)</p>
           
            <p><strong>Zeitraum:</strong> <?php echo date('d.m.Y', strtotime($lager['startdatum'])); ?> bis <?php echo date('d.m.Y', strtotime($lager['enddatum'])); ?></p>
           
			<?php if (!empty($lager['hinweise_an_admin'])): ?>
				<p><strong>Hinweise / Wünsche:</strong><br><?php echo htmlspecialchars($lager['hinweise_an_admin']); ?></p>
			<?php endif; ?>
        </div>

		
        <div class="section">
            <h3>Teilnehmer (<?php echo count($teilnehmer); ?>)</h3>
			<div class="section-actions">
                    <a href="print_teilnehmer.php?id=<?php echo $lager_id; ?>" target="_blank"><i class="fa-solid fa-print"></i> Drucken</a>
                    <a href="export_teilnehmer.php?id=<?php echo $lager_id; ?>"><i class="fa-solid fa-file-csv"></i> Exportieren (CSV)</a>
                </div>
            <table class="styled-table">
                <thead><tr><th>Name</th><th>Rolle</th></tr></thead>
                <tbody><?php foreach ($teilnehmer as $person): ?><tr><td><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td><td><?php echo htmlspecialchars($person['rolle']); ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>

        <div class="section">
            <h3>Flugzeuge (<?php echo count($flugzeuge); ?>)</h3>
			<div class="section-actions">
                    <a href="print_flugzeuge.php?id=<?php echo $lager_id; ?>" target="_blank"><i class="fa-solid fa-print"></i> Drucken</a>
                    <a href="export_flugzeuge.php?id=<?php echo $lager_id; ?>"><i class="fa-solid fa-file-csv"></i> Exportieren (CSV)</a>
                </div>
            <table class="styled-table">
                <thead><tr><th>Kennzeichen</th><th>Typ</th></tr></thead>
                <tbody><?php foreach ($flugzeuge as $flugzeug): ?><tr><td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td><td><?php echo htmlspecialchars($flugzeug['typ']); ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
 <?php endif; ?>
        <div class="section">
            <h3>Status-Verlauf</h3>
            <table class="styled-table">
                <thead><tr><th>Zeitpunkt</th><th>Status</th><th>Nachricht</th></tr></thead>
                <tbody>
                    <?php foreach ($historie as $eintrag): ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($eintrag['geaendert_am'])); ?></td>
                        <td><?php echo getStatusBadge($eintrag['status']); ?></td>
                        <td><?php echo htmlspecialchars($eintrag['nachricht']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>