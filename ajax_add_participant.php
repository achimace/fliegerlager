<?php
// ajax_add_participant.php
session_start();
require_once 'Database.php';

// Set the content type to JSON for the response
header('Content-Type: application/json');

// --- Basic Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// --- Get Data from POST Request ---
$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$vorname = trim($_POST['vorname'] ?? '');
$nachname = trim($_POST['nachname'] ?? '');
$anreise = empty($_POST['anreise']) ? null : $_POST['anreise'];
$abreise = empty($_POST['abreise']) ? null : $_POST['abreise'];
$camping = isset($_POST['camping']) ? 1 : 0;

if (empty($lager_id) || empty($vorname) || empty($nachname)) {
    echo json_encode(['status' => 'error', 'message' => 'Vor- und Nachname sind Pflichtfelder.']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- Authorization Check: Verify user owns the flight camp ---
$auth_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Authorization failed']);
    exit;
}
$auth_stmt->close();

// --- Insert the new participant into the database ---
try {
    $stmt = $conn->prepare(
        "INSERT INTO teilnehmer (fluglager_id, vorname, nachname, hat_teilgenommen, aufenthalt_von, aufenthalt_bis, camping) VALUES (?, ?, ?, 1, ?, ?, ?)"
    );
    $stmt->bind_param('issssi', $lager_id, $vorname, $nachname, $anreise, $abreise, $camping);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Teilnehmer erfolgreich hinzugefügt.']);
    } else {
        throw new Exception('Database insert failed.');
    }
    $stmt->close();
} catch (Exception $e) {
    // Return a JSON error message
    echo json_encode(['status' => 'error', 'message' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()]);
}

exit;
?>