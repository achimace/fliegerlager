<?php
// handle_delete_camp.php
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
    header('Location: dashboard.php?error=Ungültige Anfrage.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Authorization check: Ensure the camp belongs to the user AND has the correct status.
$auth_stmt = $conn->prepare("SELECT id, status FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
$result = $auth_stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert.');
    exit;
}

$lager = $result->fetch_assoc();
// NEW: Also check if the status allows deletion
if ($lager['status'] !== 'in_planung') {
    header('Location: dashboard.php?error=' . urlencode('Dieses Fluglager kann nicht gelöscht werden, da es bereits eingereicht wurde.'));
    exit;
}
$auth_stmt->close();

// Delete the flight camp
$delete_stmt = $conn->prepare("DELETE FROM fluglager WHERE id = ?");
$delete_stmt->bind_param('i', $lager_id);

if ($delete_stmt->execute()) {
    header('Location: dashboard.php?message=' . urlencode('Fluglager erfolgreich gelöscht.'));
} else {
    header('Location: dashboard.php?error=' . urlencode('Fehler beim Löschen des Fluglagers.'));
}

$delete_stmt->close();
exit;
?>