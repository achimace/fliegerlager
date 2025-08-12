<?php
// handle_abrechnung_teilnehmer.php
session_start();
require_once 'Database.php';

if (!isset($_SESSION['loggedin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_customer.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lager_id = $_POST['lager_id'] ?? 0;

// Security and ownership check... (same as in your original handler)

$db = new Database();
$conn = $db->getConnection();

if (isset($_POST['teilnehmer_anreise'])) {
    $stmt = $conn->prepare("UPDATE teilnehmer SET aufenthalt_von = ?, aufenthalt_bis = ?, camping = ?, hat_teilgenommen = ? WHERE id = ? AND fluglager_id = ?");
    
    foreach ($_POST['teilnehmer_anreise'] as $id => $anreise) {
        $abreise = $_POST['teilnehmer_abreise'][$id] ?? null;
        $camping = isset($_POST['teilnehmer_camping'][$id]) ? 1 : 0;
        $teilnahme = $_POST['teilnehmer_teilnahme'][$id] ?? 0;
        
        $stmt->bind_param('ssiiii', $anreise, $abreise, $camping, $teilnahme, $id, $lager_id);
        $stmt->execute();
    }
    $stmt->close();
}

header('Location: abrechnung.php?id=' . $lager_id . '&message=Teilnehmerdaten erfolgreich gespeichert.');
exit;