<?php
// handle_status_abrechnung.php
session_start();
require_once 'Database.php';

// Security checks
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

// --- Security: Verify ownership ---
$check_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$check_stmt->bind_param('ii', $lager_id, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$check_stmt->close();

// --- Update status and create log entry ---
$new_status = 'abrechnung_durchfuehren';
$nachricht = 'Kunde hat die Abrechnungsdaten vervollständigt und zur Prüfung freigegeben.';

// 1. Update status in the main table
$stmt_update = $conn->prepare("UPDATE fluglager SET status = ? WHERE id = ?");
$stmt_update->bind_param('si', $new_status, $lager_id);
$stmt_update->execute();
$stmt_update->close();

// 2. Insert the change into the log table
$stmt_log = $conn->prepare("INSERT INTO status_log (fluglager_id, status, nachricht) VALUES (?, ?, ?)");
$stmt_log->bind_param('iss', $lager_id, $new_status, $nachricht);
$stmt_log->execute();
$stmt_log->close();

header('Location: abrechnung.php?id=' . $lager_id . '&message=Status auf "Abrechnung durchführen" gesetzt. Die Verwaltung wird informiert.');
exit;