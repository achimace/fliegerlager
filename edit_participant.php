<?php
// edit_participant.php (vollständige, aktualisierte Datei)
session_start();
require_once 'Database.php';

// Sicherheitsprüfungen... (wie zuvor)
if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    header('Location: login_customer.php');
    exit;
}

$participant_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$db = new Database();
$conn = $db->getConnection();

// Autorisierung und Teilnehmerdaten abrufen... (wie zuvor)
$stmt = $conn->prepare("SELECT t.*, f.id as lager_id FROM teilnehmer t JOIN fluglager f ON t.fluglager_id = f.id WHERE t.id = ? AND f.user_id = ?");
$stmt->bind_param('ii', $participant_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$person = $result->fetch_assoc();
$stmt->close();

// Flugzeuge für Dropdown abrufen... (wie zuvor)
$aircraft_stmt = $conn->prepare("SELECT id, kennzeichen, typ FROM flugzeuge WHERE fluglager_id = ?");
$aircraft_stmt->bind_param('i', $person['lager_id']);
$aircraft_stmt->execute();
$aircraft_list = $aircraft_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$aircraft_stmt->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Teilnehmer bearbeiten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Teilnehmer bearbeiten: <?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></h1>
            <a href="edit_fluglager.php?id=<?php echo $person['lager_id']; ?>" class="btn">Zurück zum Fluglager</a>
        </header>
        <main style="margin-top: 20px;">
            <form action="handle_edit_participant.php" method="post">
                <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>">
                <input type="hidden" name="lager_id" value="<?php echo $person['lager_id']; ?>">
                
                <div class="form-group half-width">
                    <label>Vorname</label>
                    <input type="text" name="vorname" value="<?php echo htmlspecialchars($person['vorname']); ?>" required>
                </div>
                <div class="form-group half-width">
                    <label>Nachname</label>
                    <input type="text" name="nachname" value="<?php echo htmlspecialchars($person['nachname']); ?>" required>
                </div>
                <div class="form-group half-width">
                    <label>Geburtsdatum</label>
                    <input type="date" name="geburtsdatum" value="<?php echo htmlspecialchars($person['geburtsdatum']); ?>">
                </div>
                <div class="form-group half-width">
                    <label>Vereinsflieger-Nr.</label>
                    <input type="text" name="vereinsflieger_nr" value="<?php echo htmlspecialchars($person['vereinsflieger_nr']); ?>">
                </div>
                <div class="form-group half-width">
                    <label>Anreise (individuell)</label>
                    <input type="date" name="aufenthalt_von" value="<?php echo htmlspecialchars($person['aufenthalt_von']); ?>">
                </div>
                <div class="form-group half-width">
                    <label>Abreise (individuell)</label>
                    <input type="date" name="aufenthalt_bis" value="<?php echo htmlspecialchars($person['aufenthalt_bis']); ?>">
                </div>
                <div class="form-group">
                    <input type="checkbox" id="camping" name="camping" value="1" <?php if ($person['camping']) echo 'checked'; ?>>
                    <label for="camping">Nimmt am Camping teil</label>
                </div>
                
                <hr>
                <div class="form-group">
                    <label for="pilot_of_aircraft_id">Pilot für Flugzeug (optional)</label>
                    <select id="pilot_of_aircraft_id" name="pilot_of_aircraft_id">
                        <option value="">-- Kein Flugzeug zugeordnet --</option>
                        <?php foreach ($aircraft_list as $aircraft): ?>
                            <option value="<?php echo $aircraft['id']; ?>" <?php if ($person['pilot_of_aircraft_id'] == $aircraft['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($aircraft['kennzeichen'] . ' (' . $aircraft['typ'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Änderungen speichern</button>
            </form>
        </main>
    </div>
</body>
</html>