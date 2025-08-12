<?php
// edit_fluglager.php
session_start();
require_once 'Database.php';

// 1. Sicherheits- und Berechtigungsprüfungen
if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    header('Location: login_customer.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lager_id = $_GET['id'];

// 2. Datenbankverbindung und alle Daten für dieses Lager abrufen
$db = new Database();
$conn = $db->getConnection();

// a) Hauptdaten des Fluglagers abrufen und prüfen, ob es dem User gehört
$stmt = $conn->prepare("SELECT * FROM fluglager WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $lager_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    // Wenn das Lager nicht existiert oder nicht dem User gehört -> Abbruch
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$lager = $result->fetch_assoc();
$stmt->close();

// b) Teilnehmer für dieses Lager abrufen
$teilnehmer_stmt = $conn->prepare("SELECT * FROM teilnehmer WHERE fluglager_id = ? ORDER BY nachname, vorname");
$teilnehmer_stmt->bind_param('i', $lager_id);
$teilnehmer_stmt->execute();
$teilnehmer = $teilnehmer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$teilnehmer_stmt->close();

// c) Flugzeuge für dieses Lager abrufen (inkl. Pilotendaten)
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

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fluglager bearbeiten - Flugplatz Ohlstadt</title>
	
    <link rel="stylesheet" href="styles.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Fluglager vom <?php echo date('d.m.Y', strtotime($lager['startdatum'])); ?> bearbeiten</h1>
            <a href="dashboard.php" class="btn">Zurück zum Dashboard</a>
        </header>

        <?php if (isset($_GET['message'])) echo '<p style="color: green; margin-top: 10px;">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p style="color: red; margin-top: 10px;">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <?php if ($lager['status'] === 'in_planung' || $lager['status'] === 'abgelehnt'): ?>
        <div class="section">
            <h3>Fluglager zur Prüfung einreichen</h3>
            <p>Wenn Sie alle Daten vollständig erfasst haben, können Sie das Fluglager zur Prüfung durch den Admin einreichen.</p>
            <form action="handle_submit_fluglager.php" method="post" onsubmit="return confirm('Möchten Sie dieses Fluglager wirklich einreichen? Danach sind keine Änderungen mehr möglich, bis der Admin geantwortet hat.');">
                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                <button type="submit" class="btn" style="background-color: green;">Jetzt Einreichen</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="section">
    <h3>Grunddaten</h3>
    <form action="handle_edit_camp_details.php" method="post">
        <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
        <div class="form-group half-width">
            <label>Anreise</label>
            <input type="date" name="startdatum" value="<?php echo htmlspecialchars($lager['startdatum']); ?>" required>
        </div>
        <div class="form-group half-width">
            <label>Abreise</label>
            <input type="date" name="enddatum" value="<?php echo htmlspecialchars($lager['enddatum']); ?>" required>
        </div>
        <div class="form-group">
            <input type="checkbox" id="exklusiv" name="exklusiv" value="1" <?php if ($lager['exklusiv']) echo 'checked'; ?>>
            <label for="exklusiv">Wir möchten den Platz exklusiv buchen.</label>
        </div>
        <button type="submit">Grunddaten Speichern</button>
    </form>
</div>

<div class="section">
    <h3>Teilnehmer (<?php echo count($teilnehmer); ?> / 40)</h3>
    <div style="overflow-x: auto;">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Geburtsdatum</th>
                    <th>E-Mail</th>
                    <th>VF-Nr.</th>
                    <th>Aufenthalt</th>
                    <th>Camping</th>
                    <th>Funktion</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rollen = ['Pilot', 'Flugschüler', 'Begleitperson']; // Define roles for dropdowns
                foreach ($teilnehmer as $person):
                ?>
                    <tr id="participant-row-<?php echo $person['id']; ?>">
                        <td><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td>
                        <td><?php echo $person['geburtsdatum'] ? date('d.m.Y', strtotime($person['geburtsdatum'])) : ''; ?></td>
                        <td><?php echo htmlspecialchars($person['email']); ?></td>
                        <td><?php echo htmlspecialchars($person['vereinsflieger_nr']); ?></td>
                        <td><?php echo $person['aufenthalt_von'] ? date('d.m', strtotime($person['aufenthalt_von'])) . ' - ' . date('d.m', strtotime($person['aufenthalt_bis'])) : 'Lagerzeitraum'; ?></td>
                        <td><?php echo $person['camping'] ? 'Ja' : 'Nein'; ?></td>
                        <td><?php echo htmlspecialchars($person['rolle']); ?></td>
                        <td>
                            <button type="button" onclick="showParticipantEditForm(<?php echo $person['id']; ?>)" class="btn">Bearbeiten</button>
                            <form action="handle_delete_participant.php" method="post" style="display:inline;" onsubmit="return confirm('Teilnehmer wirklich löschen?');">
                                <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>">
                                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                <button type="submit" class="btn" style="background-color: red;">Löschen</button>
                            </form>
                        </td>
                    </tr>
                    <tr id="edit-participant-form-<?php echo $person['id']; ?>" style="display:none; background-color: #eef;">
                        <td colspan="8">
                            <form action="handle_edit_participant.php" method="post">
                                <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>">
                                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                <div style="display: grid; grid-template-columns: 2fr 1fr 2fr 1fr 2fr 1fr 1fr 1.5fr; gap: 10px; align-items: center;">
                                    <div>
                                        <input type="text" name="vorname" value="<?php echo htmlspecialchars($person['vorname']); ?>" placeholder="Vorname" required><br>
                                        <input type="text" name="nachname" value="<?php echo htmlspecialchars($person['nachname']); ?>" placeholder="Nachname" required>
                                    </div>
                                    <input type="date" name="geburtsdatum" value="<?php echo htmlspecialchars($person['geburtsdatum']); ?>">
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($person['email']); ?>" placeholder="E-Mail">
                                    <input type="text" name="vereinsflieger_nr" value="<?php echo htmlspecialchars($person['vereinsflieger_nr']); ?>" placeholder="VF-Nr.">
                                    <div>
                                        <input type="date" name="aufenthalt_von" value="<?php echo htmlspecialchars($person['aufenthalt_von']); ?>" title="Anreise"><br>
                                        <input type="date" name="aufenthalt_bis" value="<?php echo htmlspecialchars($person['aufenthalt_bis']); ?>" title="Abreise">
                                    </div>
                                    <input type="checkbox" name="camping" value="1" <?php if ($person['camping']) echo 'checked'; ?>>
                                    <select name="rolle">
                                        <option value="">-- Funktion --</option>
                                        <?php foreach($rollen as $rolle): ?>
                                            <option value="<?php echo $rolle; ?>" <?php if($person['rolle'] == $rolle) echo 'selected'; ?>><?php echo $rolle; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div>
                                        <button type="submit" class="btn">Speichern</button>
                                        <button type="button" onclick="hideParticipantEditForm(<?php echo $person['id']; ?>)" class="btn" style="background-color:#777;">X</button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr id="addParticipantForm" style="display:none; background-color: #f3f3f3;">
                     <td colspan="8">
                        <form action="handle_add_participant.php" method="post">
                            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                             <div style="display: grid; grid-template-columns: 2fr 1fr 2fr 1fr 2fr 1fr 1fr 1.5fr; gap: 10px; align-items: center;">
                                <div>
                                    <input type="text" name="vorname" placeholder="Vorname" required><br>
                                    <input type="text" name="nachname" placeholder="Nachname" required>
                                </div>
                                <input type="date" name="geburtsdatum" title="Geburtsdatum">
                                <input type="email" name="email" placeholder="E-Mail">
                                <input type="text" name="vereinsflieger_nr" placeholder="VF-Nr.">
                                <div>
                                    <input type="date" name="aufenthalt_von" title="Anreise"><br>
                                    <input type="date" name="aufenthalt_bis" title="Abreise">
                                </div>
                                <input type="checkbox" name="camping" value="1">
                                <select name="rolle">
                                    <option value="">-- Funktion wählen --</option>
                                    <?php foreach($rollen as $rolle): ?>
                                        <option value="<?php echo $rolle; ?>"><?php echo $rolle; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn">Speichern</button>
                            </div>
                        </form>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <button type="button" onclick="toggleForm('addParticipantForm')" class="btn" style="margin-top: 20px;">+ Neuen Teilnehmer hinzufügen</button>
</div>
<div class="section">
    <h3>Flugzeuge (<?php echo count($flugzeuge); ?> / 10)</h3>
    <div style="overflow-x: auto;">
        <table class="styled-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Kennzeichen</th>
                    <th style="width: 20%;">Muster</th>
                    <th style="width: 15%;">FLARM-ID</th>
                    <th style="width: 15%;">Typ</th>
                    <th style="width: 20%;">Pilot</th>
                    <th style="width: 15%;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flugzeuge as $flugzeug): ?>
                    <tr id="aircraft-row-<?php echo $flugzeug['id']; ?>">
                        <td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td>
                        <td><?php echo htmlspecialchars($flugzeug['muster']); ?></td>
                        <td><?php echo htmlspecialchars($flugzeug['flarm_id']); ?></td>
                        <td><?php echo htmlspecialchars($flugzeug['typ']); ?></td>
                        <td><?php echo $flugzeug['pilot_vorname'] ? htmlspecialchars($flugzeug['pilot_vorname'] . ' ' . $flugzeug['pilot_nachname']) : '<i>Kein Pilot</i>'; ?></td>
                        <td>
                            <button type="button" onclick="showAircraftEditForm(<?php echo $flugzeug['id']; ?>)" class="btn">Bearbeiten</button>
                            <form action="handle_delete_aircraft.php" method="post" style="display:inline;" onsubmit="return confirm('Flugzeug wirklich löschen?');">
                                <input type="hidden" name="aircraft_id" value="<?php echo $flugzeug['id']; ?>">
                                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                <button type="submit" class="btn" style="background-color: red;">Löschen</button>
                            </form>
                        </td>
                    </tr>
                    <tr id="edit-aircraft-form-<?php echo $flugzeug['id']; ?>" style="display:none;">
                        <td colspan="6" style="background-color: #eef; padding: 10px;">
                            <form action="handle_edit_aircraft.php" method="post">
                                <input type="hidden" name="aircraft_id" value="<?php echo $flugzeug['id']; ?>">
                                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                <div style="display: grid; grid-template-columns: 15% 20% 15% 15% 20% 15%; gap: 10px; align-items: center;">
                                    <input type="text" name="kennzeichen" value="<?php echo htmlspecialchars($flugzeug['kennzeichen']); ?>" required>
                                    <input type="text" name="muster" value="<?php echo htmlspecialchars($flugzeug['muster']); ?>">
                                    <input type="text" name="flarm_id" value="<?php echo htmlspecialchars($flugzeug['flarm_id']); ?>">
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
                                    <div>
                                        <button type="submit" class="btn">OK</button>
                                        <button type="button" onclick="hideAircraftEditForm(<?php echo $flugzeug['id']; ?>)" class="btn" style="background-color:#777;">X</button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr id="addAircraftForm" style="display:none;">
                    <td colspan="6" style="background-color: #f3f3f3; padding: 10px;">
                        <form action="handle_add_aircraft.php" method="post">
                            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                            <div style="display: grid; grid-template-columns: 15% 20% 15% 15% 20% 15%; gap: 10px; align-items: center;">
                                <input type="text" name="kennzeichen" placeholder="Kennzeichen" required>
                                <input type="text" name="muster" placeholder="Muster">
                                <input type="text" name="flarm_id" placeholder="FLARM-ID">
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
                                <button type="submit" class="btn">Speichern</button>
                            </div>
                        </form>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <button type="button" onclick="toggleForm('addAircraftForm')" class="btn" style="margin-top: 20px;">+ Neues Flugzeug hinzufügen</button>
</div>
    </div>
<script>
    // Blendet die "Hinzufügen"-Zeilen ein/aus
    function toggleForm(rowId) {
        var row = document.getElementById(rowId);
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }

    // --- FUNKTIONEN FÜR TEILNEHMER ---
    function showParticipantEditForm(id) {
        document.getElementById('participant-row-' + id).style.display = 'none';
        document.getElementById('edit-participant-form-' + id).style.display = 'table-row';
    }
    function hideParticipantEditForm(id) {
        document.getElementById('edit-participant-form-' + id).style.display = 'none';
        document.getElementById('participant-row-' + id).style.display = 'table-row';
    }

    // --- FUNKTIONEN FÜR FLUGZEUGE ---
    function showAircraftEditForm(id) {
        document.getElementById('aircraft-row-' + id).style.display = 'none';
        document.getElementById('edit-aircraft-form-' + id).style.display = 'table-row';
    }
    function hideAircraftEditForm(id) {
        document.getElementById('edit-aircraft-form-' + id).style.display = 'none';
        document.getElementById('aircraft-row-' + id).style.display = 'table-row';
    }
</script>
</body>
</html>