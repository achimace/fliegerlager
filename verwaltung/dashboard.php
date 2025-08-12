<?php
// verwaltung/index.php
session_start();
require_once '../Database.php';
require_once '../helpers.php'; // Für die Status-Badges

// Check if admin is logged in
if (!isset($_SESSION['loggedin_admin']) || $_SESSION['loggedin_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$allowed_stati = ['eingereicht', 'bestaetigt', 'abgelehnt'];
// Der Standard ist jetzt 'alle', wenn kein Status in der URL steht.
$status_filter = $_GET['status'] ?? 'alle';

$sql_where_clause = "";
// Die WHERE-Klausel wird nur hinzugefügt, wenn ein gültiger, spezifischer Status gewählt wurde.
if (in_array($status_filter, $allowed_stati)) {
    $sql_where_clause = "WHERE f.status = ?";
}


$query = "SELECT f.id, f.startdatum, f.enddatum, f.status, u.vereinsname 
          FROM fluglager f 
          JOIN users u ON f.user_id = u.id 
          $sql_where_clause
          ORDER BY f.startdatum ASC";

$stmt = $conn->prepare($query);

// Parameter binden, falls ein Filter aktiv ist
if (!empty($sql_where_clause)) {
    $stmt->bind_param('s', $status_filter);
}

$stmt->execute();
$anfragen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lade alle aktuell geblockten Zeiträume
$query_blocks = "SELECT * FROM kalender_block ORDER BY startdatum DESC";
$result_blocks = $conn->query($query_blocks);
$sperrungen = $result_blocks->fetch_all(MYSQLI_ASSOC);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="../styles.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="index.php"><img src="../pics/logo.png" alt="Logo"></a>
        </div>
        <nav class="app-header-nav">
            <a href="index.php" class="active">Anfragen</a>
            <a href="einstellungen.php">Einstellungen</a>
            <span>|</span>
            <span>Admin-Bereich</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Admin-Dashboard</h1>
        
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <div class="section">
            <h3>Fluglager-Anfragen</h3>
            
            <nav class="filter-nav">
                <a href="?status=eingereicht" class="<?php if($status_filter == 'eingereicht') echo 'active'; ?>">Eingereicht</a>
                <a href="?status=bestaetigt" class="<?php if($status_filter == 'bestaetigt') echo 'active'; ?>">Bestätigt</a>
                <a href="?status=abgelehnt" class="<?php if($status_filter == 'abgelehnt') echo 'active'; ?>">Abgelehnt</a>
                <a href="?status=alle" class="<?php if($status_filter == 'alle') echo 'active'; ?>">Alle Anzeigen</a>
            </nav>
            <?php if (empty($anfragen)): ?>
                <p style="margin-top: 20px;">Es gibt keine Anfragen mit diesem Status.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Verein</th>
                            <th>Zeitraum</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anfragen as $anfrage): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($anfrage['vereinsname']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($anfrage['startdatum'])) . ' - ' . date('d.m.Y', strtotime($anfrage['enddatum'])); ?></td>
                                <td><?php echo getStatusBadge($anfrage['status']); ?></td>
                                <td>
                                    <a href="view_lager.php?id=<?php echo $anfrage['id']; ?>" class="btn btn-primary">Details & Bearbeiten</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>Zeiträume manuell sperren</h3>
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
                    <label for="grund">Grund der Sperre (z.B. Wartung)</label>
                    <input type="text" id="grund" name="grund" required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" name="action" value="block" class="btn">Zeitraum sperren</button>
                </div>
            </form>
            
            <h4 style="margin-top: 30px;">Aktive Sperrungen</h4>
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
                                    <form action="handle_block_dates.php" method="post" style="display:inline;">
                                        <input type="hidden" name="block_id" value="<?php echo $sperre['id']; ?>">
                                        <button type="submit" name="action" value="unblock" class="btn btn-danger">Sperre aufheben</button>
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