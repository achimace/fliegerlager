<?php
// verwaltung/handle_payment_status.php
session_start();
require_once '../Database.php';
require_once '../Mail.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin_admin'])) {
    header('Location: login.php');
    exit;
}

$lager_id = $_POST['lager_id'] ?? 0;
$status_action = $_POST['status'] ?? '';
$admin_name = $_SESSION['admin_user']['username'] ?? 'Admin';

if (empty($lager_id) || $status_action !== 'anzahlung_bezahlt') {
    header('Location: index.php?error=Ungültige Anfrage.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- Correct Summer Time ---
// Generate the current timestamp in PHP to ensure the correct timezone (CEST) is used.
$now_berlin = new DateTime("now", new DateTimeZone('Europe/Berlin'));
$payment_timestamp = $now_berlin->format('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // 1. Update the flight camp status using the correct timestamp
    $stmt_update = $conn->prepare("UPDATE fluglager SET anzahlung_bezahlt = 1, anzahlung_bezahlt_am = ? WHERE id = ?");
    $stmt_update->bind_param('si', $payment_timestamp, $lager_id);
    $stmt_update->execute();
    $stmt_update->close();

    // 2. Log the change in the status history
    $log_message = "Anzahlung von Admin '" . $admin_name . "' als bezahlt markiert.";
    $stmt_log = $conn->prepare("INSERT INTO status_log (fluglager_id, status, nachricht) VALUES (?, 'info', ?)");
    $stmt_log->bind_param('is', $lager_id, $log_message);
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
        $subject = "Ihre Anzahlung wurde verbucht";
        $message = "<h1>Hallo " . htmlspecialchars($customer['vorname']) . ",</h1>
                     <p>wir freuen uns, Ihnen mitteilen zu können, dass wir Ihre Anzahlung für das Fluglager (ID: $lager_id) erhalten und verbucht haben.</p>
                     <p>Vielen Dank!</p>
                     <p>Mit freundlichen Grüßen,<br>Ihr Team vom Flugplatz Ohlstadt</p>";
        
        $mail = new Mail();
        $mail->sendEmail($customer['email'], $subject, $message, true);
    }

    $conn->commit();
    header('Location: view_lager.php?id=' . $lager_id . '&message=' . urlencode('Anzahlung erfolgreich als bezahlt markiert.'));

} catch (Exception $e) {
    $conn->rollback();
    
    // --- Logging Removed ---
    // The custom payment_errors.log file is no longer written to.
    // We can use the generic server error log for production.
    error_log("Payment Status Error: " . $e->getMessage());

    header('Location: view_lager.php?id=' . $lager_id . '&error=' . urlencode('Ein Fehler ist aufgetreten.'));
}

exit;
?>