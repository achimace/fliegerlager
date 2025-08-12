<?php
// verwaltung/sperrzeiten.php
session_start();
require_once '../Database.php';

// Admin-Login prüfen
if (!isset($_SESSION['loggedin_admin'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Lade alle aktuell geblockten Zeiträume
$query_blocks = "SELECT * FROM kalender_block ORDER BY startdatum DESC";
$result_blocks = $conn->query($query_blocks);
$sperrungen = $result_blocks->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Sperrzeiten verwalten</title>
    <link rel="stylesheet" href="../styles.css">
	 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="index.php"><img src="../pics/logo.png" alt="Logo"></a>
        </div>
       <nav class="app-header-nav">
            <a href="index.php">Anfragen</a>
            <a href="sperrzeiten.php" class="active">Sperrzeiten</a>
            <a href="preise.php">Preise</a>
            <a href="einstellungen.php">Einstellungen</a>
            <span>|</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Kalender-Sperrzeiten verwalten</h1>
        <p>Hier können Sie Zeiträume im Kalender manuell blockieren, z.B. für Wartungsarbeiten oder Vereinsveranstaltungen.</p>
        
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>
        
        <div class="section">
            <h3>Neue Sperrzeit anlegen</h3>
            <form action="handle_block_dates.php" method="post" class="form-grid">
                <div class="form-group">
                    <label for="startdatum">Startdatum der Sperre</label>
                    <input type="date" id="startdatum" name="startdatum" required>
                </div>
                <div class="form-group">
                    <label for="enddatum">Enddatum der Sperre</label>
                    <input type="date" id="enddatum" name="enddatum" required>
                </div>
                <div class="form-group full-width">
                    <label for="grund">Grund der Sperre (wird im Kalender angezeigt)</label>
                    <input type="text" id="grund" name="grund" required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" name="action" value="block" class="btn btn-primary">Zeitraum sperren</button>
                </div>
            </form>
        </div>

        <div class="section">
            <h3>Aktive Sperrungen</h3>
            <?php if (empty($sperrungen)): ?>
                <p>Es sind keine Zeiträume manuell gesperrt.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Grund</th>
                            <th>Zeitraum</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sperrungen as $sperre): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sperre['grund']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($sperre['startdatum'])) . ' - ' . date('d.m.Y', strtotime($sperre['enddatum'])); ?></td>
                                <td>
                                     <form action="handle_block_dates.php" method="post" style="display:inline;" onsubmit="return confirm('Möchten Sie diese Sperre wirklich aufheben?');">
                                        <input type="hidden" name="block_id" value="<?php echo $sperre['id']; ?>">
                                        <button type="submit" name="action" value="unblock" class="btn btn-danger btn-icon" title="Sperre aufheben"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>