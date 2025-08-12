<?php
// abrechnung.php
session_start();
require_once 'Database.php';
require_once 'helpers.php';

// --- Security checks and data fetching ---
if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    header('Location: login_customer.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$user_vorname = $_SESSION['user_vorname'] ?? 'User';
$lager_id = $_GET['id'];

$db = new Database();
$conn = $db->getConnection();
$einstellungen = ladeEinstellungen($conn);

// Authorization check and get flight camp data
$stmt = $conn->prepare("SELECT * FROM fluglager WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $lager_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$lager = $result->fetch_assoc();
$stmt->close();

// Fetch the correct price list for this flight camp
$price_stmt = $conn->prepare("SELECT preis_name, wert FROM preise WHERE gueltig_ab <= ? AND id IN (SELECT MAX(id) FROM preise WHERE gueltig_ab <= ? GROUP BY preis_name)");
$camp_start_date = $lager['startdatum'];
$price_stmt->bind_param('ss', $camp_start_date, $camp_start_date);
$price_stmt->execute();
$price_result = $price_stmt->get_result();
$preise = [];
while ($row = $price_result->fetch_assoc()) {
    $preise[$row['preis_name']] = $row['wert'];
}
$price_stmt->close();

// Get Participants and Aircraft
$teilnehmer = $conn->query("SELECT * FROM teilnehmer WHERE fluglager_id = $lager_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$flugzeuge = $conn->query("SELECT * FROM flugzeuge WHERE fluglager_id = $lager_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// Check if the camp has started
$startDate = new DateTime($lager['startdatum']);
$startDate->setTime(0,0,0);
$now = new DateTime("now", new DateTimeZone('Europe/Berlin'));
$now->setTime(0,0,0);
$isCampActive = ($now >= $startDate);

$show_summary = isset($_GET['summary']) && $_GET['summary'] == '1';

// Billing calculation logic
$summary_data = ['teilnehmer' => [], 'flugzeuge' => []];
$gesamtsumme = 0.00;
if ($show_summary) {
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
        $summary_data['teilnehmer'][] = ['name' => $person['vorname'] . ' ' . $person['nachname'], 'details' => $details, 'total' => $person_total];
        $gesamtsumme += $person_total;
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
        $summary_data['flugzeuge'][] = ['name' => $flugzeug['kennzeichen'], 'details' => $details, 'total' => $plane_total];
        $gesamtsumme += $plane_total;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Abrechnung - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css?v=1.9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">
    <header class="app-header">
        <div class="logo"><a href="dashboard.php"><img src="pics/logo.png" alt="Logo"></a></div>
        <nav class="app-header-nav">
            <a href="dashboard.php">Dashboard</a><span>|</span>
            <a href="calendar.php">Kalender</a><span>|</span>
            <span>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>
    <div class="app-container">
        <h1>Abrechnung für Fluglager vom <?php echo date('d.m.Y', strtotime($lager['startdatum'])); ?></h1>
        <p>Bitte erfassen Sie die finalen Anwesenheitsdaten und Leistungen für die Endabrechnung.</p>

        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>
        
        <?php if ($show_summary): ?>
        <div class="section" id="summary-section">
            <h3>Zusammenfassung der Abrechnungsdaten</h3>
            <p>Die folgenden Daten wurden gespeichert und bilden die Grundlage für die Endabrechnung.</p>
            <?php if (!empty($summary_data['teilnehmer'])): ?>
            <h4>Teilnehmer</h4>
            <table class="styled-table">
                <thead><tr><th>Name</th><th>Posten</th><th style="text-align: right;">Betrag</th></tr></thead>
                <tbody>
                <?php foreach($summary_data['teilnehmer'] as $item): if ($item['total'] <= 0) continue; ?>
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
                <?php foreach($summary_data['flugzeuge'] as $item): if ($item['total'] <= 0) continue; ?>
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
        </div>
        <?php endif; ?>
        
        <form action="handle_final_billing.php" method="post">
            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
            <div class="section">
                <div class="section-header">
                    <h3>Teilnehmer</h3>
                    <button type="button" onclick="toggleForm('addParticipantForm')" class="btn">+ Teilnehmer hinzufügen</button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="styled-table">
                        <thead><tr><th>Name</th><th style="text-align: center;">Hat teilgenommen</th><th>Anreise</th><th>Abreise</th><th>Nächte Camping</th></tr></thead>
                        <tbody>
                            <?php foreach ($teilnehmer as $person): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td>
                                <td style="text-align: center;"><input type="checkbox" name="teilnehmer[<?php echo $person['id']; ?>][hat_teilgenommen]" value="1" <?php if ($person['hat_teilgenommen']) echo 'checked'; ?>></td>
                                <td><input type="date" name="teilnehmer[<?php echo $person['id']; ?>][anreise]" value="<?php echo htmlspecialchars($person['aufenthalt_von'] ?? $lager['startdatum']); ?>"></td>
                                <td><input type="date" name="teilnehmer[<?php echo $person['id']; ?>][abreise]" value="<?php echo htmlspecialchars($person['aufenthalt_bis'] ?? $lager['enddatum']); ?>"></td>
                                <td><input type="number" name="teilnehmer[<?php echo $person['id']; ?>][naechte_camping]" value="<?php echo htmlspecialchars($person['abrechnung_naechte_camping']); ?>" min="0" style="width: 80px;"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr id="addParticipantForm" style="display:none;">
                                <td><input type="text" name="new_teilnehmer_name[]" placeholder="Vorname Nachname"></td>
                                <td style="text-align: center;"><input type="checkbox" checked disabled></td>
                                <td><input type="date" name="new_teilnehmer_anreise[]" value="<?php echo htmlspecialchars($lager['startdatum']); ?>"></td>
                                <td><input type="date" name="new_teilnehmer_abreise[]" value="<?php echo htmlspecialchars($lager['enddatum']); ?>"></td>
                                <td><input type="number" name="new_teilnehmer_camping[]" value="0" min="0" style="width: 80px;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="section">
                <div class="section-header">
                    <h3>Flugzeuge</h3>
                    <button type="button" onclick="toggleForm('addAircraftForm')" class="btn">+ Flugzeug hinzufügen</button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="styled-table">
                        <thead><tr><th>Kennzeichen</th><th style="text-align: center;">Hat teilgenommen</th><th>Ankunft</th><th>Abreise</th><th>Tage Halle</th></tr></thead>
                        <tbody>
                            <?php foreach ($flugzeuge as $flugzeug): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td>
                                <td style="text-align: center;"><input type="checkbox" name="flugzeuge[<?php echo $flugzeug['id']; ?>][hat_teilgenommen]" value="1" <?php if ($flugzeug['hat_teilgenommen']) echo 'checked'; ?>></td>
                                <td><input type="date" name="flugzeuge[<?php echo $flugzeug['id']; ?>][anreise]" value="<?php echo htmlspecialchars($flugzeug['abrechnung_anreise'] ?? $lager['startdatum']); ?>"></td>
                                <td><input type="date" name="flugzeuge[<?php echo $flugzeug['id']; ?>][abreise]" value="<?php echo htmlspecialchars($flugzeug['abrechnung_abreise'] ?? $lager['enddatum']); ?>"></td>
                                <td><input type="number" name="flugzeuge[<?php echo $flugzeug['id']; ?>][tage_halle]" value="<?php echo htmlspecialchars($flugzeug['abrechnung_tage_halle']); ?>" min="0" style="width: 80px;"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr id="addAircraftForm" style="display:none;">
                                <td><input type="text" name="new_flugzeug_kennzeichen[]" placeholder="Kennzeichen"></td>
                                <td style="text-align: center;"><input type="checkbox" checked disabled></td>
                                <td><input type="date" name="new_flugzeug_ankunft[]" value="<?php echo htmlspecialchars($lager['startdatum']); ?>"></td>
                                <td><input type="date" name="new_flugzeug_abreise[]" value="<?php echo htmlspecialchars($lager['enddatum']); ?>"></td>
                                <td><input type="number" name="new_flugzeug_halle[]" value="0" min="0" style="width: 80px;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="section">
                <button type="submit" name="action" value="save_and_summarize" class="btn btn-primary">Daten speichern & Zusammenfassung anzeigen</button>
            </div>
        </form>
        <div class="section">
            <h3>Abrechnung abschließen</h3>
            <?php if ($lager['abrechnung_gesendet']): ?>
                 <p class="message-info"><i class="fa-solid fa-check-circle"></i> Die Abrechnung wurde bereits an die Verwaltung gesendet.</p>
            <?php elseif ($isCampActive): ?>
                <p>Wenn alle Daten final erfasst und gespeichert sind, übermitteln Sie die Abrechnung an die Verwaltung.</p>
                <form action="handle_final_billing.php" method="post" onsubmit="return confirm('Möchten Sie die Abrechnung wirklich final an die Buchhaltung senden?');">
                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                    <button type="submit" name="action" value="send_to_accounting" class="btn btn-success">Abrechnung an Buchhaltung senden</button>
                </form>
            <?php else: ?>
                <p>Diese Aktion ist erst zu Beginn des Fluglagers (ab dem <?php echo $startDate->format('d.m.Y'); ?>) verfügbar.</p>
            <?php endif; ?>
        </div>
    </div>
<script>
    function toggleForm(rowId) {
        var row = document.getElementById(rowId);
        row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
    }
</script>
</body>
</html>