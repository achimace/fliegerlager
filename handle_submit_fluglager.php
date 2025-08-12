<?php
// handle_submit_fluglager.php
session_start();
require_once 'Database.php';
require_once 'Mail.php';
require_once 'helpers.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
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
$einstellungen = ladeEinstellungen($conn);

// --- Security: Verify ownership and get contact person details ---
$check_stmt = $conn->prepare("SELECT status, ansprechpartner_vorname, ansprechpartner_nachname, ansprechpartner_email, ansprechpartner_telefon FROM fluglager WHERE id = ? AND user_id = ?");
$check_stmt->bind_param('ii', $lager_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$lager_data = $result->fetch_assoc();
$aktueller_status = $lager_data['status'];
$check_stmt->close();

// Also check if the status allows submission
if ($aktueller_status !== 'in_planung' && $aktueller_status !== 'abgelehnt') {
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Dieses Fluglager kann nicht eingereicht werden.'));
    exit;
}

// --- Update status and create log entry ---
$new_status = 'eingereicht';
$nachricht = 'Fluglager vom Kunden zur Prüfung eingereicht.';

// 1. Update status in the main table
$stmt_update = $conn->prepare("UPDATE fluglager SET status = ? WHERE id = ?");
$stmt_update->bind_param('si', $new_status, $lager_id);

if ($stmt_update->execute()) {
    $stmt_update->close();

    // 2. Insert the change into your log table
    $stmt_log = $conn->prepare("INSERT INTO status_log (fluglager_id, status, nachricht) VALUES (?, ?, ?)");
    $stmt_log->bind_param('iss', $lager_id, $new_status, $nachricht);
    $stmt_log->execute();
    $stmt_log->close();

    // 3. Send notification emails to admin list
    try {
        $notification_list_string = $einstellungen['notification_emails'] ?? '';
        
        if (!empty($notification_list_string)) {
            $admin_emails = explode(',', $notification_list_string);
            
            // Get the club name from the user who owns the account
            $user_stmt = $conn->prepare("SELECT vereinsname FROM users WHERE id = ?");
            $user_stmt->bind_param('i', $user_id);
            $user_stmt->execute();
            $user_info = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();

            $subject = "Neues Fluglager zur Prüfung eingereicht (ID: $lager_id)";
            
            $ansprechpartner_fullname = trim($lager_data['ansprechpartner_vorname'] . ' ' . $lager_data['ansprechpartner_nachname']);
            
            $message = "<h1>Hallo Admin-Team,</h1>
                         <p>ein neues Fluglager wurde zur Prüfung eingereicht und wartet auf eine Entscheidung.</p>
                         <p>
                            <strong>Lager-ID:</strong> " . $lager_id . "<br>
                            <strong>Verein:</strong> " . htmlspecialchars($user_info['vereinsname']) . "<br>
                            <strong>Ansprechpartner:</strong> " . htmlspecialchars($ansprechpartner_fullname) . "<br>
                            <strong>E-Mail:</strong> " . htmlspecialchars($lager_data['ansprechpartner_email']) . "<br>
							<strong>Telefon:</strong> " . htmlspecialchars($lager_data['ansprechpartner_telefon']) . "
                         </p>
                         <p>Bitte loggen Sie sich in Gästeverwaltung ein, um die Anfrage zu bearbeiten.<br>
						<a href=\"https://gastgruppe.flugplatz-ohlstadt.de/verwaltung/login.php\">Login Gästeverwaltung</a></p>";
            
            $mail = new Mail();

            foreach ($admin_emails as $email) {
                $clean_email = trim($email);
                if (filter_var($clean_email, FILTER_VALIDATE_EMAIL)) {
                    $mail->sendEmail($clean_email, $subject, $message, true);
                }
            }
        }
    } catch (Exception $e) {
        // Log mail error but continue
        $log_message = date('Y-m-d H:i:s') . " - Submit Mail Error: " . $e->getMessage() . "\n";
        file_put_contents('mail_errors.log', $log_message, FILE_APPEND);
    }

    // 4. Redirect back with a success message
    header('Location: edit_fluglager.php?id=' . $lager_id . '&message=Fluglager erfolgreich eingereicht. Wir werden es prüfen und uns bei Ihnen melden.');
    exit;

} else {
    // Error during the database operation
    header('Location: edit_fluglager.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Einreichen des Fluglagers.'));
    exit;
}
?>