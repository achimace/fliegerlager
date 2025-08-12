<?php
// handle_edit_camp_notes.php
session_start();
require_once 'Database.php';

// --- Security and Authorization Checks ---
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

// Authorization check: Verify user owns the flight camp
$auth_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert.');
    exit;
}
$auth_stmt->close();
// --- End Security ---

$hinweise = trim($_POST['hinweise_an_admin'] ?? '');

$stmt = $conn->prepare("UPDATE fluglager SET hinweise_an_admin=? WHERE id=?");
$stmt->bind_param('si', $hinweise, $lager_id);

if ($stmt->execute()) {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=' . urlencode('Hinweise erfolgreich gespeichert.'));
} else {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Speichern der Hinweise.'));
}
$stmt->close();
exit;
?>