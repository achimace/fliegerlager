<?php
// verwaltung/handle_status_change.php
session_start();
require_once '../Database.php';
require_once '../Mail.php';
require_once '../VereinsfliegerRestInterface.php'; 
require_once '../helpers.php';

// Security check
if (!isset($_SESSION['loggedin_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$lager_id = $_POST['lager_id'] ?? 0;
$new_status = $_POST['new_status'] ?? '';
$nachricht = trim($_POST['nachricht'] ?? '');

if ($lager_id <= 0 || !in_array($new_status, ['bestaetigt', 'abgelehnt'])) {
    header('Location: index.php?error=Ungültige Anfrage');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

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

// 3. Load flight camp data and settings for emails/API
$einstellungen = ladeEinstellungen($conn); 
$config = require '../config.php'; // Load config for API credentials
$stmt_lager = $conn->prepare("
    SELECT f.*, u.vereinsname, u.email AS customer_email
    FROM fluglager f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.id = ?
");
$stmt_lager->bind_param('i', $lager_id);
$stmt_lager->execute();
$lager_data = $stmt_lager->get_result()->fetch_assoc();
$stmt_lager->close();

$mailer = new Mail();

// 4. Send notifications and create API entries based on new status
if ($new_status === 'bestaetigt') {
    
    // --- Create calendar entry in Vereinsflieger via API ---
    try {
        // Load credentials from config file
        $vf_user = $config['vf_admin_user'];
        $vf_pass = $config['vf_admin_pass'];

        $vfApi = new VereinsfliegerRestInterface();
        if ($vfApi->SignIn($vf_user, $vf_pass)) {
            // Prepare data for the API calendar event
            $timezone = new DateTimeZone('Europe/Berlin');
            $dateFromObj = new DateTime($lager_data['startdatum'] . ' 08:00', $timezone);
            $dateToObj = new DateTime($lager_data['enddatum'] . ' 18:00', $timezone);
            
            $title = "Fluglager: " . $lager_data['vereinsname'];
            $dateFrom = $dateFromObj->format('Y-m-d H:i');
            $dateTo = $dateToObj->format('Y-m-d H:i');
            $comment = "Bestätigtes Fluglager.\nAnsprechpartner: " . $lager_data['ansprechpartner_vorname'] . " " . $lager_data['ansprechpartner_nachname'];
            $location = "Flugplatz Ohlstadt";

            // Call the API to create the calendar event
            $vfApi->createCalendarAppointment($title, $dateFrom, $dateTo, $comment, $location);
            $vfApi->SignOut();
        }
    } catch (Exception $e) {
        error_log("Vereinsflieger API Error: " . $e->getMessage());
    }

    // --- Create .ics calendar file content for the admin email ---
    $dtstart = (new DateTime($lager_data['startdatum']))->format('Ymd');
    $dtend = (new DateTime($lager_data['enddatum']))->modify('+1 day')->format('Ymd');
    $summary = "Fluglager: " . $lager_data['vereinsname'];
    $description = "Bestätigtes Fluglager für den Verein " . $lager_data['vereinsname'] . ".\\nAnsprechpartner: " . $lager_data['ansprechpartner_vorname'] . " " . $lager_data['ansprechpartner_nachname'] . ".";
    $location = "Flugplatz Ohlstadt";
    $uid = uniqid() . "@flugplatz-ohlstadt.de";

    $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//FlugplatzOhlstadt//Buchungssystem//DE\r\nBEGIN:VEVENT\r\n";
    $icsContent .= "UID:" . $uid . "\r\nDTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z\r\n";
    $icsContent .= "DTSTART;VALUE=DATE:" . $dtstart . "\r\nDTEND;VALUE=DATE:" . $dtend . "\r\n";
    $icsContent .= "SUMMARY:" . $summary . "\r\nDESCRIPTION:" . $description . "\r\nLOCATION:" . $location . "\r\n";
    $icsContent .= "END:VEVENT\r\nEND:VCALENDAR\r\n";

    // --- Email 1: Send calendar invite to all admins in the settings list ---
    $admin_emails_string = $einstellungen['notification_emails'] ?? '';
    $admin_emails = explode(',', $admin_emails_string);

    if (!empty($admin_emails_string)) {
        $admin_subject = 'Neues Fluglager bestätigt: ' . $lager_data['vereinsname'];
        $admin_body = "Ein neues Fluglager wurde soeben bestätigt.<br><br><b>Verein:</b> " . $lager_data['vereinsname'] . "<br><b>Zeitraum:</b> " . date('d.m.Y', strtotime($lager_data['startdatum'])) . " - " . date('d.m.Y', strtotime($lager_data['enddatum'])) . "<br><br>Ein Eintrag wurde im Vereinsflieger-Kalender erstellt. Zusätzlich finden Sie im Anhang einen Kalendereintrag (.ics) für lokale Kalender.";
        
        foreach ($admin_emails as $admin_email) {
            $trimmed_email = trim($admin_email);
            if (filter_var($trimmed_email, FILTER_VALIDATE_EMAIL)) {
                $mailer->sendCalendarInvite($trimmed_email, $admin_subject, $admin_body, $icsContent);
            }
        }
    }
    
    // --- Email 2: Send confirmation with calendar invite to customer ---
    $customer_email = !empty($lager_data['ansprechpartner_email']) ? $lager_data['ansprechpartner_email'] : $lager_data['customer_email'];
    $customer_subject = 'Dein Fluglager am Flugplatz Ohlstadt wurde bestätigt!';
    $customer_body = "Gute Nachrichten!<br><br>Dein angefragtes Fluglager vom <b>" . date('d.m.Y', strtotime($lager_data['startdatum'])) . " bis zum " . date('d.m.Y', strtotime($lager_data['enddatum'])) . "</b> wurde soeben von uns bestätigt.<br><br>Im Anhang findest Du einen Kalendereintrag für eure Planung.<br><br>Der nächste Schritt ist die Überweisung der Anzahlung, um die Buchung final abzuschließen. Alle Details dazu findest du in unserer Fluglager Verwaltung, dort kasst Du jetzt auch wieder die Teilnehmer- und Flugzeugliste bearbeiten<br><a href=\"https://gastgruppe.flugplatz-ohlstadt.de\">Fluglager Verwaltung öffnen</a><br<br> Wir freuen uns auf euren Besuch!<br>Euer Team vom Flugplatz Ohlstadt";
    if (!empty($nachricht)) {
        $customer_body .= "<br><br>---<br>Ihre persönliche Nachricht von uns:<br><i>" . nl2br(htmlspecialchars($nachricht)) . "</i>";
    }
    $mailer->sendCalendarInvite($customer_email, $customer_subject, $customer_body, $icsContent);

} elseif ($new_status === 'abgelehnt') {
    // Send rejection/cancellation email to customer
    $customer_email = !empty($lager_data['ansprechpartner_email']) ? $lager_data['ansprechpartner_email'] : $lager_data['customer_email'];
    $customer_subject = 'Wichtige Information zu Deinem Fluglager am Flugplatz Ohlstadt';
    $customer_body = "Guten Tag,<br><br>leider müssen wir Dir eine wichtige Information zu Deinem Fluglager vom <b>" . date('d.m.Y', strtotime($lager_data['startdatum'])) . " bis zum " . date('d.m.Y', strtotime($lager_data['enddatum'])) . "</b> mitteilen.<br><br>Der Status Ihrer Anfrage wurde auf 'Abgelehnt' geändert.<br><br>";
    if (!empty($nachricht)) {
        $customer_body .= "Grund:<br><i>" . nl2br(htmlspecialchars($nachricht)) . "</i><br><br>";
    }
    $customer_body .= "Bitte kontaktiere uns bei Rückfragen.<br><br>Mit freundlichen Grüßen,<br>Ihr Team vom Flugplatz Ohlstadt";
    $mailer->sendEmail($customer_email, $customer_subject, $customer_body);
}

header("Location: view_lager.php?id=$lager_id&message=Status erfolgreich geändert.");
exit;