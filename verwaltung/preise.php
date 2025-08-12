<?php
// verwaltung/preise.php
session_start();
require_once '../Database.php';

// Admin-Login prüfen
if (!isset($_SESSION['loggedin_admin'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Fetch all prices and group them by their "valid from" date
$price_history_raw = $conn->query("SELECT * FROM preise ORDER BY gueltig_ab DESC, preis_name ASC");
$price_history = [];
while ($row = $price_history_raw->fetch_assoc()) {
    $price_history[$row['gueltig_ab']][$row['preis_name']] = $row['wert'];
}

// Get the latest price list to pre-fill the form
$latest_prices = !empty($price_history) ? current($price_history) : [
    'pilot_pro_tag' => 0, 'camping_pro_nacht' => 0, 'flugzeug_stationierung_pro_tag' => 0, 'flugzeug_halle_pro_tag' => 0
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Preise verwalten</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo"><a href="index.php"><img src="../pics/logo.png" alt="Logo"></a></div>
        <nav class="app-header-nav">
            <a href="index.php">Anfragen</a>
            <a href="sperrzeiten.php">Sperrzeiten</a>
            <a href="preise.php" class="active">Preise</a>
            <a href="einstellungen.php">Einstellungen</a>
            <span>|</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Preise verwalten</h1>
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <div class="section">
            <h3>Neue Preisliste anlegen</h3>
            <p>Legen Sie hier neue Preise fest, die ab einem bestimmten Datum gelten. Bestehende Abrechnungen werden nicht beeinflusst.</p>
            <form action="handle_preise.php" method="post">
                <div class="form-group">
                    <label for="gueltig_ab">Gültig ab Datum</label>
                    <input type="date" id="gueltig_ab" name="gueltig_ab" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tagespreis Pilot/Copilot (€)</label>
                        <input type="number" step="0.01" name="pilot_pro_tag" value="<?php echo htmlspecialchars($latest_prices['pilot_pro_tag']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preis Camping pro Nacht (€)</label>
                        <input type="number" step="0.01" name="camping_pro_nacht" value="<?php echo htmlspecialchars($latest_prices['camping_pro_nacht']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preis Stationierung pro Tag (€)</label>
                        <input type="number" step="0.01" name="flugzeug_stationierung_pro_tag" value="<?php echo htmlspecialchars($latest_prices['flugzeug_stationierung_pro_tag']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preis Halle pro Tag (€)</label>
                        <input type="number" step="0.01" name="flugzeug_halle_pro_tag" value="<?php echo htmlspecialchars($latest_prices['flugzeug_halle_pro_tag']); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Neue Preisliste speichern</button>
            </form>
        </div>

        <div class="section">
            <h3>Preishistorie</h3>
            <?php if (empty($price_history)): ?>
                <p>Noch keine Preislisten angelegt.</p>
            <?php else: ?>
                <?php foreach ($price_history as $date => $prices): ?>
                    <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 15px;">
                        <h4>Gültig ab: <?php echo date('d.m.Y', strtotime($date)); ?></h4>
                        <ul>
                            <li>Tagespreis Pilot/Copilot: <strong><?php echo number_format($prices['pilot_pro_tag'], 2, ',', '.'); ?> €</strong></li>
                            <li>Preis Camping pro Nacht: <strong><?php echo number_format($prices['camping_pro_nacht'], 2, ',', '.'); ?> €</strong></li>
                            <li>Preis Stationierung pro Tag: <strong><?php echo number_format($prices['flugzeug_stationierung_pro_tag'], 2, ',', '.'); ?> €</strong></li>
                            <li>Preis Halle pro Tag: <strong><?php echo number_format($prices['flugzeug_halle_pro_tag'], 2, ',', '.'); ?> €</strong></li>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>