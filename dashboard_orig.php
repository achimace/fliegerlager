<?php
// dashboard.php
session_start();
require_once 'Database.php';

// --- Dokumentation ---
// Das Dashboard ist die Startseite für eingeloggte Kunden.
// Es prüft die Session und zeigt eine Liste der Fluglager des Benutzers an.

// 1. Prüfen, ob der Benutzer eingeloggt ist. Wenn nicht, zum Login umleiten.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login_customer.php');
    exit;
}

// 2. Benutzer-ID und Namen aus der Session holen
$user_id = $_SESSION['user_id'];
$user_vorname = $_SESSION['user_vorname'];

// 3. Datenbankverbindung herstellen
$db = new Database();
$conn = $db->getConnection();

// 4. Alle Fluglager für den eingeloggten Benutzer abrufen
$stmt = $conn->prepare("SELECT id, startdatum, enddatum, status FROM fluglager WHERE user_id = ? ORDER BY startdatum DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$fluglager_liste = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Funktion zur Übersetzung des Status für die Anzeige
function getStatusBadge($status) {
    $farben = [
        'in_planung' => '#6F9ED4',  // Blau
        'eingereicht' => '#F68A1E', // Orange
        'bestaetigt' => 'green',     // Grün
        'abgelehnt' => 'red'       // Rot
    ];
    $text = ucfirst(str_replace('_', ' ', $status));
    return '<span style="background-color: ' . ($farben[$status] ?? '#989898') . '; color: white; padding: 5px 10px; border-radius: 5px;">' . htmlspecialchars($text) . '</span>';
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ihr Dashboard - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
	<style>
		/* Standardmäßig sind die Eingabefelder und Bearbeiten-Buttons versteckt */
		.profile-section .edit-view {
			display: none;
		}

		/* Im Bearbeiten-Modus... */
		.profile-section.edit-mode .edit-view {
			display: block; /* ...werden die Eingabefelder und Buttons sichtbar */
		}
		.profile-section.edit-mode .display-view {
			display: none; /* ...und die Anzeigetexte und der Bearbeiten-Button versteckt */
		}

		/* Layout für die Datenpaare */
		.profile-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 30px;
		}
		.data-pair-grid {
			display: grid;
			grid-template-columns: 120px 1fr;
			gap: 10px 15px;
			align-items: center;
		}
		.data-pair-grid strong {
			font-weight: bold;
		}
		.data-pair-grid input {
			width: 100%;
		}
		.profile-section.edit-mode .plz-ort-wrapper {
			display: flex;
			gap: 10px;
		}
	</style>
</head>
<body>
	
    <div class="container">
        <header>
            <h1>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</h1>
            <a href="logout.php" class="btn">Logout</a>
        </header>
<?php
// Lade die aktuellen Benutzerdaten für die Anzeige
$user_stmt = $conn->prepare("SELECT mobiltelefon, vereinsname FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();
?>

<?php
// Lade die aktuellen Benutzerdaten für die Anzeige (unverändert)
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();
?>

<div id="profile-section" class="section profile-section" style="margin-top: 20px;">
    <form action="handle_edit_profile.php" method="post">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Meine Daten</h3>
            <button type="button" onclick="showProfileEditForm()" class="btn display-view">Daten bearbeiten</button>
        </div>

        <div class="profile-grid">
            <div>
                <h4>Kontaktdaten</h4>
                <div class="data-pair-grid">
                    <strong>Vorname:</strong>
                    <div>
                        <span class="display-view"><?php echo htmlspecialchars($user_data['vorname']); ?></span>
                        <input class="edit-view" type="text" name="vorname" value="<?php echo htmlspecialchars($user_data['vorname']); ?>" required>
                    </div>

                    <strong>Nachname:</strong>
                    <div>
                        <span class="display-view"><?php echo htmlspecialchars($user_data['nachname']); ?></span>
                        <input class="edit-view" type="text" name="nachname" value="<?php echo htmlspecialchars($user_data['nachname']); ?>" required>
                    </div>

                    <strong>E-Mail:</strong>
                    <div>
                        <span class="display-view"><?php echo htmlspecialchars($user_data['email']); ?></span>
                        <input class="edit-view" type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled title="E-Mail kann nicht geändert werden.">
                    </div>

                    <strong>Mobiltelefon:</strong>
                    <div>
                        <span class="display-view"><?php echo htmlspecialchars($user_data['mobiltelefon'] ?: '<i>Nicht angegeben</i>'); ?></span>
                        <input class="edit-view" type="text" name="mobiltelefon" value="<?php echo htmlspecialchars($user_data['mobiltelefon']); ?>">
                    </div>
                </div>
            </div>
            <div>
                <h4>Vereinsdaten</h4>
                <div class="data-pair-grid">
                    <strong>Verein:</strong>
                    <div>
                        <span class="display-view"><?php echo htmlspecialchars($user_data['vereinsname'] ?: '<i>Nicht angegeben</i>'); ?></span>
                        <input class="edit-view" type="text" name="vereinsname" value="<?php echo htmlspecialchars($user_data['vereinsname']); ?>">
                    </div>

                    <strong>Straße:</strong>
                    <div>
                        <span class="display-view"><?php echo htmlspecialchars($user_data['strasse'] ?: '<i>Nicht angegeben</i>'); ?></span>
                        <input class="edit-view" type="text" name="strasse" value="<?php echo htmlspecialchars($user_data['strasse']); ?>">
                    </div>

<strong>PLZ / Ort:</strong>
<div>
    <span class="display-view"><?php echo htmlspecialchars($user_data['plz'] . ' ' . $user_data['ort']); ?></span>
    <div class="edit-view plz-ort-wrapper">
        <input type="text" name="plz" value="<?php echo htmlspecialchars($user_data['plz']); ?>" placeholder="PLZ" style="flex: 1;">
        <input type="text" name="ort" value="<?php echo htmlspecialchars($user_data['ort']); ?>" placeholder="Ort" style="flex: 2;">
    </div>
</div>
                </div>
            </div>
        </div>

        <div class="edit-view" style="margin-top: 20px;">
            <button type="submit" class="btn">Änderungen speichern</button>
            <button type="button" onclick="hideProfileEditForm()" class="btn" style="background-color:#777;">Abbrechen</button>
        </div>
    </form>
</div>
        <main style="margin-top: 20px;">
            <h2>Ihre Fluglager</h2>
            <a href="create_fluglager.php" class="btn" style="background-color: green; margin-bottom: 20px;">+ Neues Fluglager anlegen</a>

            <?php if (empty($fluglager_liste)): ?>
                <p>Sie haben noch keine Fluglager angelegt.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Zeitraum</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fluglager_liste as $lager): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($lager['startdatum']))) . ' - ' . htmlspecialchars(date('d.m.Y', strtotime($lager['enddatum']))); ?></td>
                                <td><?php echo getStatusBadge($lager['status']); ?></td>
                                <td>
                                   
                                    <?php if ($lager['status'] === 'in_planung' || $lager['status'] === 'abgelehnt'): ?>
                                        <a href="edit_fluglager.php?id=<?php echo $lager['id']; ?>" class="btn">Bearbeiten</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>
<script>
    function showProfileEditForm() {
        document.getElementById('profile-section').classList.add('edit-mode');
    }

    function hideProfileEditForm() {
        document.getElementById('profile-section').classList.remove('edit-mode');
    }
</script>
</body>
</html>