<?php
// ajax_send_billing_mail.php
session_start();
require_once 'Database.php';
require_once 'Mail.php';
require_once 'helpers.php';

header('Content-Type: application/json');

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin_admin'])) {
    // Note: Or use !isset($_SESSION['loggedin']) if customers can send this
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (empty($lager_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Authorization check
$auth_stmt = $conn->prepare("SELECT * FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
$lager = $auth_stmt->get_result()->fetch_assoc();
if (!$lager) {
    echo json_encode(['status' => 'error', 'message' => 'Authorization failed']);
    exit;
}
$auth_stmt->close();

try {
    // 1. Mark billing as sent in the database
    $conn->query("UPDATE fluglager SET abrechnung_gesendet = 1 WHERE id = " . intval($lager_id));

    // 2. Fetch all data needed for the email summary
    $einstellungen = ladeEinstellungen($conn);
    $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->bind_param('i', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();

    $teilnehmer_stmt = $conn->prepare("SELECT * FROM teilnehmer WHERE fluglager_id = ? AND hat_teilgenommen = 1");
    $teilnehmer_stmt->bind_param('i', $lager_id);
    $teilnehmer_stmt->execute();
    $teilnehmer = $teilnehmer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // (A similar query for aircraft would go here if needed)

    // 3. Construct a detailed HTML email
    $subject = "Abrechnungsdaten für Fluglager (ID: $lager_id) liegen vor";
    $message = "<h1>Abrechnungsdaten für Fluglager</h1>";
    $message .= "<p>Der Kunde hat die Dateneingabe für die Abrechnung abgeschlossen.</p>";
    $message .= "<ul>";
    $message .= "<li><strong>Lager-ID:</strong> " . $lager_id . "</li>";
    $message .= "<li><strong>Verein:</strong> " . htmlspecialchars($user['vereinsname']) . "</li>";
    $message .= "<li><strong>Zeitraum:</strong> " . date('d.m.Y', strtotime($lager['startdatum'])) . " - " . date('d.m.Y', strtotime($lager['enddatum'])) . "</li>";
    $message .= "</ul>";

    $message .= "<h3>Teilnehmerübersicht</h3><table border='1' cellpadding='5' cellspacing='0'><thead><tr><th>Name</th><th>Anreise</th><th>Abreise</th><th>Camping</th></tr></thead><tbody>";
    foreach($teilnehmer as $person) {
        $message .= "<tr>";
        $message .= "<td>" . htmlspecialchars($person['vorname'] . ' ' . $person['nachname']) . "</td>";
        $message .= "<td>" . date('d.m.Y', strtotime($person['aufenthalt_von'])) . "</td>";
        $message .= "<td>" . date('d.m.Y', strtotime($person['aufenthalt_bis'])) . "</td>";
        $message .= "<td>" . ($person['camping'] ? 'Ja' : 'Nein') . "</td>";
        $message .= "</tr>";
    }
    $message .= "</tbody></table>";
    // (A similar table for aircraft could be added here)

    // 4. Send email to the admin list
    $notification_list_string = $einstellungen['notification_emails'] ?? '';
    if (!empty($notification_list_string)) {
        $admin_emails = explode(',', $notification_list_string);
        $mail = new Mail();
        foreach ($admin_emails as $email) {
            $clean_email = trim($email);
            if (filter_var($clean_email, FILTER_VALIDATE_EMAIL)) {
                $mail->sendEmail($clean_email, $subject, $message, true);
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Abrechnung wurde an die Verwaltung gesendet.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()]);
}

exit;
?>