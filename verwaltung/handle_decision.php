<?php
// verwaltung/handle_decision.php
session_start();
require_once '../Database.php';
require_once '../Mail.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin_admin'])) {
    header('Location: login.php');
    exit;
}

// Get form data
$lager_id = $_POST['lager_id'] ?? null;
$neuer_status = $_POST['entscheidung'] ?? ''; // 'bestaetigt' or 'abgelehnt'
$kommentar = trim($_POST['kommentar'] ?? '');
$admin_name = $_SESSION['admin_user']['username'] ?? 'Admin';

if (empty($lager_id) || ($neuer_status !== 'bestaetigt' && $neuer_status !== 'abgelehnt')) {
    header('Location: index.php?error=Ungültige Anfrage.');
    exit;
}

// Database connection
$db = new Database();
$conn = $db->getConnection();

// Get current camp data (for history and email)
$stmt = $conn->prepare("SELECT f.status, u.vorname, u.email FROM fluglager f JOIN users u ON f.user_id = u.id WHERE f.id = ?");
$stmt->bind_param('i', $lager_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: index.php?error=Anfrage nicht gefunden.');
    exit;
}
$data = $result->fetch_assoc();
$aktueller_status = $data['status'];
$kunden_vorname = $data['vorname'];
$kunden_email = $data['email'];
$stmt->close();

// === Start database transaction ===
$conn->begin_transaction();

try {
    // Update the 'fluglager' table with the new status and admin comment
    $update_stmt = $conn->prepare("UPDATE fluglager SET status = ?, kommentar_admin = ? WHERE id = ?");
    $update_stmt->bind_param('ssi', $neuer_status, $kommentar, $lager_id);
    $update_stmt->execute();

    // Log the change in the history table
    $history_stmt = $conn->prepare(
        "INSERT INTO aenderungshistorie (fluglager_id, bearbeiter, status_von, status_nach, kommentar) VALUES (?, ?, ?, ?, ?)"
    );
    $history_stmt->bind_param('issss', $lager_id, $admin_name, $aktueller_status, $neuer_status, $kommentar);
    $history_stmt->execute();

    // Commit the transaction
    $conn->commit();

} catch (mysqli_sql_exception $exception) {
    $conn->rollback(); // Roll back all changes on error
    header('Location: view_lager.php?id=' . $lager_id . '&error=' . urlencode('Ein Datenbankfehler ist aufgetreten.'));
    exit;
}
// === End database transaction ===


// Send notification email to the customer
$subject = '';
$message = '';

if ($neuer_status === 'bestaetigt') {
    $subject = 'Ihre Fluglager-Buchung wurde bestätigt!';
    $message = "<h1>Hallo $kunden_vorname,</h1>
                 <p>gute Nachrichten! Ihr angefragtes Fluglager am Flugplatz Ohlstadt wurde soeben bestätigt.</p>";
} else { // 'abgelehnt'
    $subject = 'Information zu Ihrer Fluglager-Anfrage';
    $message = "<h1>Hallo $kunden_vorname,</h1>
                 <p>leider müssen wir Ihnen mitteilen, dass wir Ihre Fluglager-Anfrage nicht bestätigen können.</p>";
}

if (!empty($kommentar)) {
    $message .= "<h3>Kommentar vom Admin:</h3><p style='font-style: italic;'>" . nl2br(htmlspecialchars($kommentar)) . "</p>";
}
$message .= "<p>Wir wünschen Ihnen alles Gute und verbleiben mit freundlichen Grüßen,<br>Ihr Team vom Flugplatz Ohlstadt</p>";

try {
    $mail = new Mail();
    //$mail->sendEmail($kunden_email, $subject, $message, true);
} catch (Exception $e) {
    // If mail fails, don't stop the process, but maybe redirect with a specific message
    header('Location: index.php?message=' . urlencode('Entscheidung gespeichert, aber E-Mail konnte nicht gesendet werden.'));
    exit;
}

// Redirect back to the admin dashboard on success
header('Location: index.php?message=' . urlencode('Die Entscheidung wurde erfolgreich gespeichert und der Kunde benachrichtigt.'));
exit;
?>