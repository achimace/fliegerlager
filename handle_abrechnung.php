<?php
// handle_abrechnung.php
session_start();
require_once 'Database.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}

$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (empty($lager_id)) {
    header('Location: dashboard.php?error=Ungültige Anfrage');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Authorization Check: Verify user owns the flight camp
$auth_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$auth_stmt->close();

// Start a transaction to ensure all updates succeed or fail together
$conn->begin_transaction();

try {
    // 1. UPDATE EXISTING PARTICIPANTS
    if (!empty($_POST['teilnehmer_teilnahme'])) {
        $stmt_update_p = $conn->prepare("UPDATE teilnehmer SET hat_teilgenommen=?, aufenthalt_von=?, aufenthalt_bis=?, camping=? WHERE id=?");
        foreach ($_POST['teilnehmer_teilnahme'] as $id => $teilnahme) {
            $anreise = $_POST['teilnehmer_anreise'][$id] ?? $lager['startdatum'];
            $abreise = $_POST['teilnehmer_abreise'][$id] ?? $lager['enddatum'];
            $camping = isset($_POST['teilnehmer_camping'][$id]) ? 1 : 0;
            $stmt_update_p->bind_param('issii', $teilnahme, $anreise, $abreise, $camping, $id);
            $stmt_update_p->execute();
        }
        $stmt_update_p->close();
    }

    // 2. ADD NEW PARTICIPANTS
    if (!empty($_POST['new_teilnehmer_name'])) {
        $stmt_add_p = $conn->prepare("INSERT INTO teilnehmer (fluglager_id, vorname, nachname, hat_teilgenommen, aufenthalt_von, aufenthalt_bis, camping, rolle) VALUES (?, ?, ?, 1, ?, ?, ?, 'Begleitperson')");
        foreach ($_POST['new_teilnehmer_name'] as $index => $fullname) {
            if (empty(trim($fullname))) continue; // Skip empty rows
            $parts = explode(' ', trim($fullname), 2);
            $vorname = $parts[0];
            $nachname = $parts[1] ?? '';
            $anreise = $_POST['new_teilnehmer_anreise'][$index];
            $abreise = $_POST['new_teilnehmer_abreise'][$index];
            $camping = isset($_POST['new_teilnehmer_camping'][$index]) ? 1 : 0;
            $stmt_add_p->bind_param('issssi', $lager_id, $vorname, $nachname, $anreise, $abreise, $camping);
            $stmt_add_p->execute();
        }
        $stmt_add_p->close();
    }

    // 3. UPDATE EXISTING AIRCRAFT & 4. ADD NEW AIRCRAFT
    // (This would follow the same pattern as participants above)
    

    // If all queries were successful, commit the transaction
    $conn->commit();
    header('Location: abrechnung.php?id=' . $lager_id . '&summary=1&message=' . urlencode('Abrechnungsdaten erfolgreich gespeichert.'));

} catch (Exception $e) {
    // If any query fails, roll back all changes
    $conn->rollback();
    header('Location: abrechnung.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Speichern: ' . $e->getMessage()));
}

exit;
?>