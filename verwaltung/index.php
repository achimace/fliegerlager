<?php
// verwaltung/index.php
session_start();
require_once '../Database.php';
require_once '../helpers.php';

// Check if admin is logged in
if (!isset($_SESSION['loggedin_admin']) || $_SESSION['loggedin_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- Robust Filter Logic ---
$status_filter = $_GET['status'] ?? 'eingereicht'; // Default to 'eingereicht'

// Whitelist of statuses that require a specific filter in the query
$filterable_stati = ['eingereicht', 'bestaetigt', 'abgelehnt', 'abrechnung_gesendet', 'fertig_abgerechnet'];

// Base query
$query = "SELECT f.id, f.startdatum, f.enddatum, f.status, u.vorname, u.nachname 
          FROM fluglager f 
          JOIN users u ON f.user_id = u.id";

$params = [];
$types = '';

// Dynamically add the WHERE clause ONLY if a valid filter is applied
if (in_array($status_filter, $filterable_stati)) {
    $query .= " WHERE f.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$query .= " ORDER BY f.startdatum DESC";

$stmt = $conn->prepare($query);

// Bind the parameters ONLY if there are any to bind
if (!empty($params)) {
    // The splat operator (...) unpacks the array into individual arguments
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$anfragen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Anfragen</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="index.php"><img src="../pics/logo.png" alt="Logo"></a>
        </div>
        <nav class="app-header-nav">
            <a href="index.php" class="active">Anfragen</a>
            <a href="sperrzeiten.php">Sperrzeiten</a>
            <a href="preise.php">Preise</a>
            <a href="einstellungen.php">Einstellungen</a>
            <span>|</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Gastgruppe verwalten</h1>
        
        <div class="filter-nav">
            <a href="?status=eingereicht" class="<?php if($status_filter == 'eingereicht') echo 'active'; ?>">Eingereicht</a>
            <a href="?status=bestaetigt" class="<?php if($status_filter == 'bestaetigt') echo 'active'; ?>">Bestätigt</a>
            <a href="?status=abgelehnt" class="<?php if($status_filter == 'abgelehnt') echo 'active'; ?>">Abgelehnt</a>
            <a href="?status=abrechnung_gesendet" class="<?php if($status_filter == 'abrechnung_gesendet') echo 'active'; ?>">Abrechnung gesendet</a>
            <a href="?status=fertig_abgerechnet" class="<?php if($status_filter == 'fertig_abgerechnet') echo 'active'; ?>">Fertig Abgerechnet</a>
            <a href="?status=alle" class="<?php if(!in_array($status_filter, $filterable_stati)) echo 'active'; ?>">Alle Anzeigen</a>
        </div>

        <div class="section">
            <h3><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $status_filter))); ?> Anfragen</h3>
            <?php if (empty($anfragen)): ?>
                <p>Es gibt keine Anfragen mit diesem Status.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Anfragesteller</th>
                            <th>Zeitraum</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anfragen as $anfrage): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($anfrage['vorname'] . ' ' . $anfrage['nachname']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($anfrage['startdatum'])) . ' - ' . date('d.m.Y', strtotime($anfrage['enddatum'])); ?></td>
                                <td><?php echo getStatusBadge($anfrage['status']); ?></td>
                                <td>
                                    <a href="view_lager.php?id=<?php echo $anfrage['id']; ?>" class="btn">Details ansehen</a>
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