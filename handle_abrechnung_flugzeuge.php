<?php
// handle_abrechnung_flugzeuge.php
session_start();
require_once 'Database.php';

// Security check and get POST data
if (!isset($_SESSION['loggedin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_customer.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lager_id = $_POST['lager_id'] ?? 0;

if (empty($lager_id)) {
    header('Location: dashboard.php?error=Ungültige Anfrage');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- Security: Verify the user owns this flight camp ---
$check_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$check_stmt->bind_param('ii', $lager_id, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$check_stmt->close();

// --- Process and update aircraft data ---
if (isset($_POST['flugzeug_ankunft'])) {
    $stmt_flugzeug = $conn->prepare("UPDATE flugzeuge SET 
        abrechnung_ankunft = ?, 
        abrechnung_abreise = ?, 
        abrechnung_tage_halle = ?, 
        abrechnung_tage_werkstatt = ?,
        hat_teilgenommen = ? 
        WHERE id = ? AND fluglager_id = ?");
    
    foreach ($_POST['flugzeug_ankunft'] as $id => $ankunft) {
        $abreise = $_POST['flugzeug_abreise'][$id] ?? null;
        $tage_halle = $_POST['flugzeug_halle'][$id] ?? 0;
        $tage_werkstatt = $_POST['flugzeug_werkstatt'][$id] ?? 0;
        $teilnahme = $_POST['flugzeug_teilnahme'][$id] ?? 0;

        $stmt_flugzeug->bind_param('ssiiiii', $ankunft, $abreise, $tage_halle, $tage_werkstatt, $teilnahme, $id, $lager_id);
        $stmt_flugzeug->execute();
    }
    $stmt_flugzeug->close();
}

// Redirect back with a specific success message
header('Location: abrechnung.php?id=' . $lager_id . '&message=Flugzeugdaten erfolgreich gespeichert.');
exit;