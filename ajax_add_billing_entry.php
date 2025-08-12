<?php
// ajax_add_billing_entry.php
session_start();
require_once 'Database.php';
header('Content-Type: application/json');

// --- Security & Authorization ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0; // Get user ID from session

if (empty($lager_id) || empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request or Session Expired']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- CRITICAL AUTHORIZATION CHECK ---
// Verify that the flight camp ID belongs to the logged-in user
$auth_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Authorization failed']);
    exit;
}
$auth_stmt->close();
// --- End Security ---

$type = $_POST['type'] ?? '';

try {
    if ($type === 'participant') {
        $fullname = trim($_POST['name'] ?? '');
        if (empty($fullname)) throw new Exception('Name ist ein Pflichtfeld.');
        
        $parts = explode(' ', $fullname, 2);
        $vorname = $parts[0];
        $nachname = $parts[1] ?? '';
        $anreise = empty($_POST['anreise']) ? null : $_POST['anreise'];
        $abreise = empty($_POST['abreise']) ? null : $_POST['abreise'];
        $camping_nights = (int)($_POST['camping_nights'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO teilnehmer (fluglager_id, vorname, nachname, hat_teilgenommen, aufenthalt_von, aufenthalt_bis, abrechnung_naechte_camping) VALUES (?, ?, ?, 1, ?, ?, ?)");
        $stmt->bind_param('issssi', $lager_id, $vorname, $nachname, $anreise, $abreise, $camping_nights);
        $message = "Teilnehmer erfolgreich hinzugefügt.";
        
    } elseif ($type === 'aircraft') {
        // ... Logic for adding aircraft ...
    } else {
        throw new Exception('Invalid type specified.');
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => $message]);
    } else {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
?>