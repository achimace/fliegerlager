<?php
// verwaltung/handle_admin_corrections.php
session_start();
require_once '../Database.php';
require_once '../Mail.php';

// --- Security & Basic Setup ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin_admin'])) {
    header('Location: login.php');
    exit;
}

$lager_id = $_POST['lager_id'] ?? 0;
$action = $_POST['action'] ?? '';
$admin_name = $_SESSION['admin_user']['username'] ?? 'Admin';

if (empty($lager_id) || empty($action)) {
    header('Location: index.php?error=Ungültige Anfrage.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- ACTION 1: Add a correction to the log ---
if ($action === 'add_correction') {
    $korrektur_betrag = (float)($_POST['korrektur_betrag'] ?? 0);
    $korrektur_grund = trim($_POST['korrektur_grund'] ?? '');

    if (empty($korrektur_grund) || $korrektur_betrag == 0) {
        header('Location: view_lager.php?id=' . $lager_id . '&error=' . urlencode('Bitte Betrag und Grund für die Korrektur angeben.'));
        exit;
    }

    try {
        $log_message = "Korrektur von Admin '" . $admin_name . "': " . number_format($korrektur_betrag, 2, ',', '.') . " € (" . $korrektur_grund . ")";
        $stmt_log = $conn->prepare("INSERT INTO status_log (fluglager_id, status, nachricht) VALUES (?, 'info', ?)");
        $stmt_log->bind_param('is', $lager_id, $log_message);
        $stmt_log->execute();
        $stmt_log->close();

        header('Location: view_lager.php?id=' . $lager_id . '&message=' . urlencode('Korrektur wurde erfolgreich zum Verlauf hinzugefügt.'));

    } catch (Exception $e) {
        header('Location: view_lager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Speichern der Korrektur.'));
    }
    exit;
}

// --- ACTION 2: Accept billing and notify customer ---
elseif ($action === 'accept_billing') {
    $conn->begin_transaction();
    try {
        // 1. Update flight camp status
        $new_status = 'fertig_abgerechnet';
        $stmt_update = $conn->prepare("UPDATE fluglager SET status = ? WHERE id = ?");
        $stmt_update->bind_param('si', $new_status, $lager_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 2. Log the status change
        $log_message = "Abrechnung von Admin '" . $admin_name . "' als fertig markiert.";
        $stmt_log = $conn->prepare("INSERT INTO status_log (fluglager_id, status, nachricht) VALUES (?, ?, ?)");
        $stmt_log->bind_param('iss', $lager_id, $new_status, $log_message);
        $stmt_log->execute();
        $stmt_log->close();

        // 3. Fetch customer data for the notification email
        $customer_stmt = $conn->prepare("SELECT u.vorname, u.email FROM users u JOIN fluglager f ON u.id = f.user_id WHERE f.id = ?");
        $customer_stmt->bind_param('i', $lager_id);
        $customer_stmt->execute();
        $customer = $customer_stmt->get_result()->fetch_assoc();
        $customer_stmt->close();

        // 4. Send confirmation email to the customer
        if ($customer) {
            $subject = "Ihre Fluglager-Abrechnung wurde bearbeitet";
            $message = "<h1>Hallo " . htmlspecialchars($customer['vorname']) . ",</h1>
                         <p>Ihre übermittelten Abrechnungsdaten für das Fluglager (ID: $lager_id) wurden von uns geprüft und abgeschlossen.</p>
                         <p>Sie erhalten die finale Rechnung in den nächsten Tagen per separater E-Mail.</p>
                         <p>Vielen Dank für Ihren Besuch!</p>
                         <p>Mit freundlichen Grüßen,<br>Ihr Team vom Flugplatz Ohlstadt</p>";
            
            $mail = new Mail();
            $mail->sendEmail($customer['email'], $subject, $message, true);
        }

        $conn->commit();
        header('Location: view_lager.php?id=' . $lager_id . '&message=' . urlencode('Abrechnung erfolgreich abgeschlossen. Der Kunde wurde benachrichtigt.'));

    } catch (Exception $e) {
        $conn->rollback();
        header('Location: view_lager.php?id=' . $lager_id . '&error=' . urlencode('Ein Fehler ist aufgetreten: ' . $e->getMessage()));
    }
    exit;
}

// Fallback redirect
header('Location: index.php');
exit;
?>