<?php
// edit_fluglager.php
session_start();
require_once 'Database.php';
require_once 'helpers.php';

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

$user_stmt = $conn->prepare("SELECT vereinsname FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$teilnehmer_stmt = $conn->prepare("SELECT * FROM teilnehmer WHERE fluglager_id = ? ORDER BY nachname, vorname");
$teilnehmer_stmt->bind_param('i', $lager_id);
$teilnehmer_stmt->execute();
$teilnehmer = $teilnehmer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$teilnehmer_stmt->close();

$flugzeuge_stmt = $conn->prepare("SELECT p.*, t.vorname AS pilot_vorname, t.nachname AS pilot_nachname FROM flugzeuge p LEFT JOIN teilnehmer t ON p.pilot_id = t.id WHERE p.fluglager_id = ? ORDER BY p.kennzeichen");
$flugzeuge_stmt->bind_param('i', $lager_id);
$flugzeuge_stmt->execute();
$flugzeuge = $flugzeuge_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$flugzeuge_stmt->close();

$deposit_participants = 0;
foreach ($teilnehmer as $person) {
    if (($person['rolle'] === 'Pilot' || $person['rolle'] === 'Flugschüler') && $person['hat_teilgenommen']) {
        $deposit_participants++;
    }
}
$deposit_amount = $deposit_participants * ($einstellungen['preis_anzahlung'] ?? 35.00);
$club_name = $user_data['vereinsname'] ?? 'Dein Verein';

// --- LOGIC FOR UI CONTROL ---
$is_planning_phase = ($lager['status'] === 'in_planung' || $lager['status'] === 'abgelehnt');
$is_submitted = ($lager['status'] === 'eingereicht');
$is_confirmed = ($lager['status'] === 'bestaetigt');

$can_edit_core_data = $is_planning_phase;
$can_edit_lists = ($is_planning_phase || $is_confirmed);

$endDate = new DateTime($lager['enddatum']);
$now = new DateTime();
$isCampFinished = ($now > $endDate);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fluglager bearbeiten - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css?v=1.5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo"><a href="dashboard.php"><img src="pics/logo.png" alt="Logo"></a></div>
        <nav class="app-header-nav">
            <a href="dashboard.php" class="<?php if($currentPage == 'dashboard.php') echo 'active'; ?>">Dashboard</a>
            <span>|</span>
            <a href="calendar.php" class="<?php if($currentPage == 'calendar.php') echo 'active'; ?>">Kalender</a>
            <span>|</span>
            <span>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Fluglager vom <?php echo date('d.m.Y', strtotime($lager['startdatum'])); ?> bearbeiten</h1>
        
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <?php if ($is_submitted): ?>
            <div class="message-info">
                <i class="fa-solid fa-hourglass-half"></i>
                <div>
                    Dein Fluglager wurde eingereicht und wird gerade geprüft. In dieser Phase kannst du leider keine Änderungen mehr vornehmen. Falls wir Rückfragen haben, melden wir uns bei dir.<br>
                    Solltet ihr selbst Fragen haben, erreichst du uns unter:<br>
                    E-Mail: geschaeftsstelle@flugplatz-ohlstadt.de<br>
                    Telefon: +49 (0)170 1240376
                </div>
            </div>
        <?php elseif ($is_confirmed): ?>
            <div class="message-info">
                <i class="fa-solid fa-lock"></i>
                <div>Dein Fluglager wurde bestätigt. Der Zeitraum ist jetzt festgelegt und kann nicht mehr geändert werden. Die Teilnehmer- und Flugzeuglisten kannst du aber weiterhin bearbeiten.</div>
            </div>
        <?php endif; ?>

        <?php if ($is_confirmed && $isCampFinished): ?>
        <div class="section">
            <h3>Abrechnung</h3>
            <p>Ihr Fluglager ist beendet. Bitte vervollständigen Sie hier die Daten für die Endabrechnung.</p>
            <a href="abrechnung.php?id=<?php echo $lager_id; ?>" class="btn btn-primary">
                <i class="fa-solid fa-file-invoice-dollar"></i> Abrechnungsdaten vorbereiten
            </a>
        </div>
        <?php endif; ?>

        <?php if ($is_confirmed): ?>
        <div class="section">
            <?php if ($lager['anzahlung_bezahlt'] == 0): ?>
                <h3>Nächster Schritt: Anzahlung</h3>
                <p class="message-info">Ihre Anfrage wurde bestätigt! Um die Buchung final abzuschließen, überweisen Sie bitte die fällige Anzahlung.</p>
                <div class="payment-details">
                    <p>Wir bitten um eine Anzahlung von <strong><?php echo number_format($einstellungen['preis_anzahlung'], 2, ',', '.'); ?> €</strong> pro teilnehmendem Piloten oder Flugschüler.</p>
                    <p>Aktuell fälliger Betrag: <strong><?php echo number_format($deposit_amount, 2, ',', '.'); ?> €</strong></p><hr>
                    <p><strong>Konto:</strong> <?php echo htmlspecialchars($einstellungen['kontonummer_anzahlung']); ?></p>
                    <p><strong>Verwendungszweck:</strong> Anzahlung Fluglager <?php echo htmlspecialchars($club_name); ?></p>
                </div>
            <?php else: ?>
                <h3>Buchung bestätigt</h3>
                <p class="message-success">Vielen Dank, Ihre Anzahlung wurde verbucht. Ihr Fluglager ist jetzt final bestätigt.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($is_planning_phase): ?>
        <div class="section">
            <h3>Fluglager zur Prüfung einreichen</h3>
            <?php
            $has_contact = !empty($lager['ansprechpartner_vorname']) && !empty($lager['ansprechpartner_nachname']);
            $has_participants = count($teilnehmer) > 0;
            $has_aircraft = count($flugzeuge) > 0;
            $can_submit = $has_contact && $has_participants && $has_aircraft;
            
            if ($can_submit):
            ?>
                <div class="submission-requirements">
                    <ul><li><i class="fa-solid fa-check-circle icon-green"></i> Alle Voraussetzungen sind erfüllt. Du kannst das Fluglager zur Prüfung an uns senden.</li></ul>
                </div><br>
                <form action="handle_submit_fluglager.php" method="post" onsubmit="return confirm('Möchten Sie dieses Fluglager wirklich einreichen? Danach sind keine Änderungen mehr möglich.');">
                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                    <button type="submit" class="btn" style="background-color: #28a745;">Jetzt Einreichen</button>
                </form>
            <?php else: ?>
                <div class="submission-requirements">
                    <p><strong>Das Fluglager kann noch nicht eingereicht werden.</strong> Bitte erfülle folgende Voraussetzungen:</p>
                    <ul>
                        <li><i class="fa-solid <?php echo $has_contact ? 'fa-check-circle icon-green' : 'fa-times-circle icon-red'; ?>"></i> Ansprechpartner ist hinterlegt</li>
                        <li><i class="fa-solid <?php echo $has_participants ? 'fa-check-circle icon-green' : 'fa-times-circle icon-red'; ?>"></i> Ein Teilnehmer ist hinzugefügt</li>
                        <li><i class="fa-solid <?php echo $has_aircraft ? 'fa-check-circle icon-green' : 'fa-times-circle icon-red'; ?>"></i> Ein Flugzeug ist hinzugefügt</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <div class="section">
                <h3>Grunddaten</h3>
                <p>Hier könnt ihr den Zeitraum eures Fluglagers planen. Auf der rechten Seite seht ihr die aktuelle Belegung.</p>
                <form action="handle_edit_camp_details.php" method="post">
                    <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
                    <div class="form-group">
                        <label><b>Anreise</b></label>
                        <input type="date" name="startdatum" value="<?php echo htmlspecialchars($lager['startdatum']); ?>" <?php if (!$can_edit_core_data) echo 'disabled'; ?> required>
                    </div>
                    <div class="form-group">
                        <label><b>Abreise</b></label>
                        <input type="date" name="enddatum" value="<?php echo htmlspecialchars($lager['enddatum']); ?>" <?php if (!$can_edit_core_data) echo 'disabled'; ?> required>
                    </div>
                    <div class="form-group">
                        <br><b>Exklusiv Buchen</b><br>
                        <input type="checkbox" id="exklusiv" name="exklusiv" value="1" <?php if ($lager['exklusiv']) echo 'checked'; ?> <?php if (!$can_edit_core_data) echo 'disabled'; ?> style="width: auto; margin-right: 10px;">
                        <label for="exklusiv" style="display: inline;">Wir möchten den Platz exklusiv buchen.</label>
                    </div> <br>
                    <?php if ($can_edit_core_data): ?>
                    <button type="submit" class="btn btn-edit">Grunddaten Speichern</button>
                    <?php endif; ?>
                </form>
            </div>
            <aside class="section">
                <h3>Aktuelle Belegung</h3>
                <?php require_once 'calendar_component.php'; ?>
            </aside>
        </div>
        
        <div class="section">
            <h3>Ansprechpartner / Organisator</h3>
            <div id="contact-display">
                <?php if (!empty($lager['ansprechpartner_vorname']) || !empty($lager['ansprechpartner_nachname'])): ?>
                    <table class="styled-table">
                        <tbody>
                            <tr>
                                <td style="width: 20%; text-align: right; font-weight: bold;">Name:</td>
                                <td style="text-align: left;"><?php echo htmlspecialchars($lager['ansprechpartner_vorname'] . ' ' . $lager['ansprechpartner_nachname']); ?></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; font-weight: bold;">E-Mail:</td>
                                <td style="text-align: left;"><?php echo htmlspecialchars($lager['ansprechpartner_email']); ?></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; font-weight: bold;">Telefon:</td>
                                <td style="text-align: left;"><?php echo htmlspecialchars($lager['ansprechpartner_telefon']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Es wurden noch keine Kontaktdaten für den Ansprechpartner hinterlegt.</p>
                <?php endif; ?>
                
                <?php if ($can_edit_core_data): ?>
                <button type="button" onclick="showContactEditForm()" class="btn btn-edit" style="margin-top: 15px;">Bearbeiten</button>
                <?php endif; ?>
            </div>
            <div id="contact-edit-form" style="display:none;">
                <form action="handle_edit_camp_contact.php" method="post">
                    <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
                    <table class="styled-table">
                        <tbody>
                            <tr>
                                <td style="width: 20%; text-align: right; font-weight: bold;"><label for="ap_vorname">Vorname:</label></td>
                                <td style="text-align: left;"><input type="text" id="ap_vorname" name="ansprechpartner_vorname" value="<?php echo htmlspecialchars($lager['ansprechpartner_vorname']); ?>" placeholder="Max"></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; font-weight: bold;"><label for="ap_nachname">Nachname:</label></td>
                                <td style="text-align: left;"><input type="text" id="ap_nachname" name="ansprechpartner_nachname" value="<?php echo htmlspecialchars($lager['ansprechpartner_nachname']); ?>" placeholder="Mustermann"></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; font-weight: bold;"><label for="ap_email">E-Mail:</label></td>
                                <td style="text-align: left;"><input type="email" id="ap_email" name="ansprechpartner_email" value="<?php echo htmlspecialchars($lager['ansprechpartner_email']); ?>" placeholder="max@musterverein.de"></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; font-weight: bold;"><label for="ap_telefon">Telefon:</label></td>
                                <td style="text-align: left;"><input type="tel" id="ap_telefon" name="ansprechpartner_telefon" value="<?php echo htmlspecialchars($lager['ansprechpartner_telefon']); ?>" placeholder="0123 456789"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-edit">Speichern</button>
                        <button type="button" onclick="hideContactEditForm()" class="btn btn-grey" style="background-color:#777;">Abbrechen</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="section">
            <h3>Teilnehmer (<?php echo count($teilnehmer); ?>)</h3>
            <div style="overflow-x: auto;">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Name</th><th>Geburtsdatum</th><th>Funktion</th><th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rollen = ['Pilot', 'Flugschüler', 'Begleitperson']; ?>
                        <?php foreach ($teilnehmer as $person): ?>
                        <tr id="participant-row-<?php echo $person['id']; ?>">
                            <td><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td>
                            <td><?php echo $person['geburtsdatum'] ? date('d.m.Y', strtotime($person['geburtsdatum'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($person['rolle']); ?></td>
                            <td>
                                <?php if ($can_edit_lists): ?>
                                    <button type="button" onclick="showParticipantEditForm(<?php echo $person['id']; ?>)" class="btn-edit btn">Bearbeiten</button>
                                    <form action="handle_delete_participant.php" method="post" style="display:inline;" onsubmit="return confirm('Teilnehmer wirklich löschen?');">
                                        <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>">
                                        <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                        <button type="submit" class="btn btn-danger btn-icon" title="Löschen"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span>Gesperrt</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($can_edit_lists): ?>
                        <tr id="edit-participant-form-<?php echo $person['id']; ?>" style="display:none; background-color: #eef;">
                            <td colspan="8">
                                <form action="handle_edit_participant.php" method="post" class="form-grid-condensed">
                                    <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>"><input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                    <input type="text" name="vorname" value="<?php echo htmlspecialchars($person['vorname']); ?>" placeholder="Vorname" required>
                                    <input type="text" name="nachname" value="<?php echo htmlspecialchars($person['nachname']); ?>" placeholder="Nachname" required>
                                    <input type="date" name="geburtsdatum" value="<?php echo htmlspecialchars($person['geburtsdatum']); ?>">
                                    <select name="rolle" required>
                                        <option value="">-- Funktion --</option>
                                        <?php foreach($rollen as $rolle): ?>
                                        <option value="<?php echo $rolle; ?>" <?php if($person['rolle'] == $rolle) echo 'selected'; ?>><?php echo $rolle; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div><button type="submit" class="btn btn-edit">OK</button><button type="button" onclick="hideParticipantEditForm(<?php echo $person['id']; ?>)" class="btn btn-grey">X</button></div>
                                </form>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($can_edit_lists): ?>
                    <tfoot>
                        <tr id="addParticipantForm" style="display:none; background-color: #f3f3f3;">
                           <td colspan="8">
                               <form action="handle_add_participant.php" method="post" class="form-grid-condensed">
                                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                    <input type="text" name="vorname" placeholder="Vorname" required>
                                    <input type="text" name="nachname" placeholder="Nachname" required>
                                    <input type="date" name="geburtsdatum" title="Geburtsdatum">
                                    <select name="rolle" required>
                                        <option value="">-- Funktion wählen --</option>
                                        <?php foreach($rollen as $rolle): ?>
                                        <option value="<?php echo $rolle; ?>"><?php echo $rolle; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-edit">Speichern</button>
                                </form>
                           </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <?php if ($can_edit_lists): ?>
            <button type="button" onclick="toggleForm('addParticipantForm')" class="btn btn-edit" style="margin-top: 20px;">+ Neuen Teilnehmer hinzufügen</button>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Flugzeuge (<?php echo count($flugzeuge); ?>)</h3>
            <div style="overflow-x: auto;">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Kennzeichen</th><th>Muster</th><th>Typ</th><th>Pilot</th><th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flugzeuge as $flugzeug): ?>
                        <tr id="aircraft-row-<?php echo $flugzeug['id']; ?>">
                            <td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td>
                            <td><?php echo htmlspecialchars($flugzeug['muster']); ?></td>
                            <td><?php echo htmlspecialchars($flugzeug['typ']); ?></td>
                            <td><?php echo $flugzeug['pilot_vorname'] ? htmlspecialchars($flugzeug['pilot_vorname'] . ' ' . $flugzeug['pilot_nachname']) : '<i>Kein Pilot</i>'; ?></td>
                            <td>
                                <?php if ($can_edit_lists): ?>
                                    <button type="button" onclick="showAircraftEditForm(<?php echo $flugzeug['id']; ?>)" class="btn btn-edit">Bearbeiten</button>
                                    <form action="handle_delete_aircraft.php" method="post" style="display:inline;" onsubmit="return confirm('Flugzeug wirklich löschen?');">
                                        <input type="hidden" name="aircraft_id" value="<?php echo $flugzeug['id']; ?>"><input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                        <button type="submit" class="btn btn-danger btn-icon" title="Löschen"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span>Gesperrt</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($can_edit_lists): ?>
                        <tr id="edit-aircraft-form-<?php echo $flugzeug['id']; ?>" style="display:none;">
                            <td colspan="6">
                                <form action="handle_edit_aircraft.php" method="post" class="form-grid-condensed">
                                    <input type="hidden" name="aircraft_id" value="<?php echo $flugzeug['id']; ?>"><input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                    <input type="text" name="kennzeichen" value="<?php echo htmlspecialchars($flugzeug['kennzeichen']); ?>" required>
                                    <input type="text" name="muster" value="<?php echo htmlspecialchars($flugzeug['muster']); ?>">
                                    <select name="typ">
                                        <option value="">-- Typ --</option>
                                        <option value="Segler" <?php if($flugzeug['typ'] == 'Segler') echo 'selected'; ?>>Segler</option>
                                        <option value="Eigenstarter" <?php if($flugzeug['typ'] == 'Eigenstarter') echo 'selected'; ?>>Eigenstarter</option>
                                        <option value="TMG" <?php if($flugzeug['typ'] == 'TMG') echo 'selected'; ?>>TMG</option>
                                        <option value="UL" <?php if($flugzeug['typ'] == 'UL') echo 'selected'; ?>>UL</option>
                                    </select>
                                    <select name="pilot_id">
                                        <option value="">-- Pilot --</option>
                                        <?php foreach($teilnehmer as $person): ?>
                                        <option value="<?php echo $person['id']; ?>" <?php if($flugzeug['pilot_id'] == $person['id']) echo 'selected'; ?>><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div><button type="submit" class="btn btn-edit">OK</button><button type="button" onclick="hideAircraftEditForm(<?php echo $flugzeug['id']; ?>)" class="btn btn-grey">X</button></div>
                                </form>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($can_edit_lists): ?>
                    <tfoot>
                        <tr id="addAircraftForm" style="display:none;">
                            <td colspan="6">
                                <form action="handle_add_aircraft.php" method="post" class="form-grid-condensed">
                                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                    <input type="text" name="kennzeichen" placeholder="Kennzeichen" required>
                                    <input type="text" name="muster" placeholder="Muster">
                                    <select name="typ" required>
                                        <option value="">-- Typ wählen --</option>
                                        <option value="Segler">Segler</option>
                                        <option value="Eigenstarter">Eigenstarter</option>
                                        <option value="TMG">TMG</option>
                                        <option value="UL">UL</option>
                                    </select>
                                    <select name="pilot_id">
                                        <option value="">-- Pilot wählen --</option>
                                        <?php foreach($teilnehmer as $person): ?>
                                        <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-edit">Speichern</button>
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <?php if ($can_edit_lists): ?>
            <button type="button" onclick="toggleForm('addAircraftForm')" class="btn btn-edit" style="margin-top: 20px;">+ Neues Flugzeug hinzufügen</button>
            <?php endif; ?>
        </div>

    </div>
    
    <script>
        function toggleForm(rowId) {
            var row = document.getElementById(rowId);
            if (row.style.display === 'none' || row.style.display === '') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }
        function showParticipantEditForm(id) {
            document.getElementById('participant-row-' + id).style.display = 'none';
            document.getElementById('edit-participant-form-' + id).style.display = 'table-row';
        }
        function hideParticipantEditForm(id) {
            document.getElementById('edit-participant-form-' + id).style.display = 'none';
            document.getElementById('participant-row-' + id).style.display = 'table-row';
        }
        function showAircraftEditForm(id) {
            document.getElementById('aircraft-row-' + id).style.display = 'none';
            document.getElementById('edit-aircraft-form-' + id).style.display = 'table-row';
        }
        function hideAircraftEditForm(id) {
            document.getElementById('edit-aircraft-form-' + id).style.display = 'none';
            document.getElementById('aircraft-row-' + id).style.display = 'table-row';
        }
        function showContactEditForm() {
            document.getElementById('contact-display').style.display = 'none';
            document.getElementById('contact-edit-form').style.display = 'block';
        }
        function hideContactEditForm() {
            document.getElementById('contact-display').style.display = 'block';
            document.getElementById('contact-edit-form').style.display = 'none';
        }
    </script>
</body>
</html>