<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
// edit_fluglager.php (Die neue Hauptdatei)
session_start();
require_once 'Database.php';
require_once 'helpers.php';

// --- 1. Security & Data Loading ---
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

// --- 2. Business Logic ---
$deposit_participants = 0;
foreach ($teilnehmer as $person) {
    if (($person['rolle'] === 'Pilot' || $person['rolle'] === 'Flugschüler') && $person['hat_teilgenommen']) {
        $deposit_participants++;
    }
}
$deposit_amount = $deposit_participants * ($einstellungen['preis_anzahlung'] ?? 35.00);
$club_name = $user_data['vereinsname'] ?? 'Dein Verein';

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
    <link rel="stylesheet" href="styles.css?v=1.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="app-body">

    <?php require 'components/header.php'; ?>

    <div class="app-container">
        <h1>Fluglager vom <?php echo date('d.m.Y', strtotime($lager['startdatum'])); ?> bearbeiten</h1>
        
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <?php require 'components/fluglager_anzeige_infobox.php'; ?>
        
        <?php if ($is_confirmed && $isCampFinished) { require 'components/fluglager_anzeige_abrechnung.php'; } ?>
        <?php if ($is_confirmed) { require 'components/fluglager_anzeige_anzahlung.php'; } ?>
        <?php if ($is_planning_phase) { require 'components/fluglager_anzeige_einreichen.php'; } ?>
    
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <?php require 'components/fluglager_form_grunddaten.php'; ?>
            <aside class="section">
                <h3>Aktuelle Belegung</h3>
                <?php require 'components/calendar_component.php'; ?>
            </aside>
        </div>
        <?php require 'components/fluglager_hinweise.php'; ?>
        <?php require 'components/fluglager_form_ansprechpartner.php'; ?>
        <?php require 'components/fluglager_tabelle_teilnehmer.php'; ?>
        <?php require 'components/fluglager_tabelle_flugzeuge.php'; ?>
    </div>
    
    <?php require 'components/footer_scripts.php'; ?>
</body>
</html>