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
    // Update existing participants
    if (!empty($_POST['teilnehmer_teilnahme'])) {
        $stmt_update = $conn->prepare("UPDATE teilnehmer SET hat_teilgenommen=?, aufenthalt_von=?, aufenthalt_bis=?, camping=? WHERE id=?");
        foreach ($_POST['teilnehmer_teilnahme'] as $id => $teilnahme) {
            $anreise = $_POST['teilnehmer_anreise'][$id] ?? null;
            $abreise = $_POST['teilnehmer_abreise'][$id] ?? null;
            $camping = isset($_POST['teilnehmer_camping'][$id]) ? 1 : 0;
            $stmt_update->bind_param('issii', $teilnahme, $anreise, $abreise, $camping, $id);
            $stmt_update->execute();
        }
        $stmt_update->close();
    }

    // Add new participants
    if (!empty($_POST['new_teilnehmer_name'])) {
        $stmt_add = $conn->prepare("INSERT INTO teilnehmer (fluglager_id, vorname, nachname, hat_teilgenommen, aufenthalt_von, aufenthalt_bis, camping) VALUES (?, ?, ?, 1, ?, ?, ?)");
        foreach ($_POST['new_teilnehmer_name'] as $index => $fullname) {
            if (empty(trim($fullname))) continue;
            $parts = explode(' ', trim($fullname), 2);
            $vorname = $parts[0];
            $nachname = $parts[1] ?? '';
            $anreise = $_POST['new_teilnehmer_anreise'][$index];
            $abreise = $_POST['new_teilnehmer_abreise'][$index];
            $camping = isset($_POST['new_teilnehmer_camping'][$index]) ? 1 : 0;
            $stmt_add->bind_param('issssi', $lager_id, $vorname, $nachname, $anreise, $abreise, $camping);
            $stmt_add->execute();
        }
        $stmt_add->close();
    }

    $conn->commit();
    header('Location: abrechnung.php?id=' . $lager_id . '&summary=1&message=' . urlencode('Teilnehmerdaten erfolgreich gespeichert.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: abrechnung.php?id=' . $lager_id . '&error=' . urlencode('Fehler: ' . $e->getMessage()));
}
exit;
?>