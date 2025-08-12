<?php
session_start();
require_once 'Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) { exit; }

$lager_id = $_POST['lager_id'] ?? 0;
// ... (Authorization Check for $lager_id and $user_id) ...

$db = new Database();
$conn = $db->getConnection();
$conn->begin_transaction();

try {
    // Update existing aircraft
    if (!empty($_POST['flugzeug_teilnahme'])) {
        // ... Logic to loop and UPDATE existing aircraft ...
    }

    // Add new aircraft
    if (!empty($_POST['new_flugzeug_kennzeichen'])) {
        // ... Logic to loop and INSERT new aircraft ...
    }

    $conn->commit();
    header('Location: abrechnung.php?id=' . $lager_id . '&summary=1&message=' . urlencode('Flugzeugdaten erfolgreich gespeichert.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: abrechnung.php?id=' . $lager_id . '&error=' . urlencode('Fehler: ' . $e->getMessage()));
}
exit;
?>