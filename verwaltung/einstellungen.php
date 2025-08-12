<?php
// verwaltung/einstellungen.php
session_start();
require_once '../Database.php';
require_once '../helpers.php';

if (!isset($_SESSION['loggedin_admin']) || $_SESSION['loggedin_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Formularverarbeitung zum AKTUALISIEREN der Einstellungen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Array mit allen Einstellungen, die gespeichert werden sollen
    $einstellungen_zum_speichern = [
        'preis_anzahlung' => filter_input(INPUT_POST, 'preis_anzahlung', FILTER_VALIDATE_FLOAT),
        'kontonummer_anzahlung' => trim($_POST['kontonummer_anzahlung']),
        'notification_emails' => trim($_POST['notification_emails']),
        'max_teilnehmer' => filter_input(INPUT_POST, 'max_teilnehmer', FILTER_VALIDATE_INT),
        'max_flugzeuge' => filter_input(INPUT_POST, 'max_flugzeuge', FILTER_VALIDATE_INT)
    ];

    $stmt = $conn->prepare("UPDATE einstellungen SET einstellung_wert = ? WHERE einstellung_name = ?");

    foreach ($einstellungen_zum_speichern as $name => $wert) {
        $stmt->bind_param('ss', $wert, $name);
        $stmt->execute();
    }
    $stmt->close();

    header('Location: einstellungen.php?message=Einstellungen erfolgreich gespeichert.');
    exit;
}

// Aktuelle Einstellungen laden, um sie im Formular anzuzeigen
$einstellungen = ladeEinstellungen($conn);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Einstellungen - Admin-Bereich</title>
    <link rel="stylesheet" href="../styles.css?v=1.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="index.php"><img src="../pics/logo.png" alt="Logo"></a>
        </div>
        <nav class="app-header-nav">
            <a href="index.php">Anfragen</a>
            <a href="sperrzeiten.php">Sperrzeiten</a>
            <a href="preise.php">Preise</a>
            <a href="einstellungen.php" class="active">Einstellungen</a>
            <span>|</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>
    <div class="app-container">
        <h1>Einstellungen</h1>

        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <div class="section">
            <h3>Allgemeine Parameter</h3>
            <form action="einstellungen.php" method="post">
                <div class="form-group">
                    <label for="preis_anzahlung">Preis pro Anzahlung (€)</label>
                    <input type="number" step="0.01" id="preis_anzahlung" name="preis_anzahlung" value="<?php echo htmlspecialchars($einstellungen['preis_anzahlung'] ?? 35.00); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="kontonummer_anzahlung">Kontonummer für Anzahlung (IBAN)</label>
                    <input type="text" id="kontonummer_anzahlung" name="kontonummer_anzahlung" value="<?php echo htmlspecialchars($einstellungen['kontonummer_anzahlung'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="notification_emails">E-Mail-Adressen für Benachrichtigungen</label>
					 <input type="text" id="notification_emails" name="notification_emails" value="<?php echo htmlspecialchars($einstellungen['notification_emails'] ?? ''); ?>" required>
                    <small>Mehrere Adressen durch Komma trennen.</small>
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

                <div class="form-group">
                    <label for="max_teilnehmer">Maximale Teilnehmer pro Fluglager</label>
                    <input type="number" id="max_teilnehmer" name="max_teilnehmer" value="<?php echo htmlspecialchars($einstellungen['max_teilnehmer'] ?? 40); ?>" required>
                </div>

                <div class="form-group">
                    <label for="max_flugzeuge">Maximale Flugzeuge pro Fluglager</label>
                    <input type="number" id="max_flugzeuge" name="max_flugzeuge" value="<?php echo htmlspecialchars($einstellungen['max_flugzeuge'] ?? 10); ?>" required>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>