<?php
// verwaltung/handle_preise.php
session_start();
require_once '../Database.php';

// Admin-Login prüfen
if (!isset($_SESSION['loggedin_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Get form data
$gueltig_ab = $_POST['gueltig_ab'] ?? '';
$prices = [
    'pilot_pro_tag' => $_POST['pilot_pro_tag'] ?? 0,
    'camping_pro_nacht' => $_POST['camping_pro_nacht'] ?? 0,
    'flugzeug_stationierung_pro_tag' => $_POST['flugzeug_stationierung_pro_tag'] ?? 0,
    'flugzeug_halle_pro_tag' => $_POST['flugzeug_halle_pro_tag'] ?? 0,
];

// Validate data
if (empty($gueltig_ab) || !strtotime($gueltig_ab)) {
    header('Location: preise.php?error=' . urlencode('Bitte ein gültiges Datum angeben.'));
    exit;
}
foreach ($prices as $key => $value) {
    if (!is_numeric($value)) {
        header('Location: preise.php?error=' . urlencode('Bitte gültige Preise eingeben.'));
        exit;
    }
}

$db = new Database();
$conn = $db->getConnection();

// Use a transaction to ensure all 4 prices are inserted together
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO preise (preis_name, wert, gueltig_ab) VALUES (?, ?, ?)");
    
    foreach ($prices as $name => $wert) {
        $stmt->bind_param('sds', $name, $wert, $gueltig_ab);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->commit();
    header('Location: preise.php?message=' . urlencode('Neue Preisliste erfolgreich gespeichert.'));

} catch (Exception $e) {
    $conn->rollback();
    header('Location: preise.php?error=' . urlencode('Fehler beim Speichern der Preise: ' . $e->getMessage()));
}

exit;
?>