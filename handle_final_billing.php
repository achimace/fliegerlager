<?php
// handle_final_billing.php
session_start();
require_once 'Database.php';
require_once 'helpers.php';
require_once 'Mail.php';

// --- Security & Basic Setup ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    header('Location: login_customer.php');
    exit;
}
$lager_id = $_POST['lager_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
if (empty($lager_id) || empty($action)) {
    header('Location: dashboard.php?error=Ungültige Anfrage');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- Authorization Check ---
$auth_stmt = $conn->prepare("SELECT id FROM fluglager WHERE id = ? AND user_id = ?");
$auth_stmt->bind_param('ii', $lager_id, $user_id);
$auth_stmt->execute();
if ($auth_stmt->get_result()->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$auth_stmt->close();


// ===================================================================
// --- ACTION 1: Save billing data and show summary ---
// ===================================================================
if ($action === 'save_and_summarize') {
    $conn->begin_transaction();
    try {
        // --- Update Existing Participants ---
        if (!empty($_POST['teilnehmer'])) {
            $stmt_p = $conn->prepare("UPDATE teilnehmer SET hat_teilgenommen=?, rolle=?, aufenthalt_von=?, aufenthalt_bis=?, abrechnung_naechte_camping=? WHERE id=?");
            foreach ($_POST['teilnehmer'] as $id => $data) {
                $teilnahme = isset($data['hat_teilgenommen']) ? 1 : 0;
                $rolle = $data['rolle'] ?? 'Begleitperson';
                $anreise = empty($data['anreise']) ? null : $data['anreise'];
                $abreise = empty($data['abreise']) ? null : $data['abreise'];
                $camping = (int)($data['naechte_camping'] ?? 0);
                $stmt_p->bind_param('isssii', $teilnahme, $rolle, $anreise, $abreise, $camping, $id);
                $stmt_p->execute();
            }
            $stmt_p->close();
        }
        
        // --- Add New Participants ---
        if (!empty($_POST['new_teilnehmer_name'])) {
            $stmt_add_p = $conn->prepare("INSERT INTO teilnehmer (fluglager_id, vorname, nachname, rolle, hat_teilgenommen, aufenthalt_von, aufenthalt_bis, abrechnung_naechte_camping) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['new_teilnehmer_name'] as $index => $fullname) {
                if (empty(trim($fullname))) continue; // Skip empty rows
                $parts = explode(' ', trim($fullname), 2);
                $vorname = $parts[0]; $nachname = $parts[1] ?? '';
                $rolle = $_POST['new_teilnehmer_rolle'][$index] ?? 'Begleitperson';
                $teilnahme = isset($_POST['new_teilnehmer_teilnahme'][$index]) ? 1 : 0;
                $anreise = empty($_POST['new_teilnehmer_anreise'][$index]) ? null : $_POST['new_teilnehmer_anreise'][$index];
                $abreise = empty($_POST['new_teilnehmer_abreise'][$index]) ? null : $_POST['new_teilnehmer_abreise'][$index];
                $camping = (int)($_POST['new_teilnehmer_camping'][$index] ?? 0);
                $stmt_add_p->bind_param('isssissi', $lager_id, $vorname, $nachname, $rolle, $teilnahme, $anreise, $abreise, $camping);
                $stmt_add_p->execute();
            }
            $stmt_add_p->close();
        }

        // --- Update Existing Aircraft ---
        if (!empty($_POST['flugzeuge'])) {
            $stmt_f = $conn->prepare("UPDATE flugzeuge SET hat_teilgenommen=?, abrechnung_anreise=?, abrechnung_abreise=?, abrechnung_tage_halle=? WHERE id=?");
            foreach ($_POST['flugzeuge'] as $id => $data) {
                $teilnahme = isset($data['hat_teilgenommen']) ? 1 : 0;
                $anreise = empty($data['anreise']) ? null : $data['anreise'];
                $abreise = empty($data['abreise']) ? null : $data['abreise'];
                $halle = (int)($data['tage_halle'] ?? 0);
                $stmt_f->bind_param('issii', $teilnahme, $anreise, $abreise, $halle, $id);
                $stmt_f->execute();
            }
            $stmt_f->close();
        }
        
        // --- Add New Aircraft ---
        if (!empty($_POST['new_flugzeug_kennzeichen'])) {
            $stmt_add_f = $conn->prepare("INSERT INTO flugzeuge (fluglager_id, kennzeichen, hat_teilgenommen, abrechnung_anreise, abrechnung_abreise, abrechnung_tage_halle) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($_POST['new_flugzeug_kennzeichen'] as $index => $kennzeichen) {
                if (empty(trim($kennzeichen))) continue; // Skip empty rows
                $teilnahme = isset($_POST['new_flugzeug_teilnahme'][$index]) ? 1 : 0;
                $ankunft = empty($_POST['new_flugzeug_ankunft'][$index]) ? null : $_POST['new_flugzeug_ankunft'][$index];
                $abreise = empty($_POST['new_flugzeug_abreise'][$index]) ? null : $_POST['new_flugzeug_abreise'][$index];
                $halle = (int)($_POST['new_flugzeug_halle'][$index] ?? 0);
                $stmt_add_f->bind_param('isissi', $lager_id, $kennzeichen, $teilnahme, $ankunft, $abreise, $halle);
                $stmt_add_f->execute();
            }
            $stmt_add_f->close();
        }

        $conn->commit();
        header('Location: abrechnung.php?id=' . $lager_id . '&summary=1&message=' . urlencode('Daten erfolgreich gespeichert.'));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header('Location: abrechnung.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Speichern: ' . $e->getMessage()));
        exit;
    }
}

// ===================================================================
// --- ACTION 2: Send billing to accounting ---
// ===================================================================
elseif ($action === 'send_to_accounting') {
    $conn->begin_transaction();
    try {
        // 1. Update flight camp status
        $new_status = 'abrechnung_gesendet';
        $stmt_update = $conn->prepare("UPDATE fluglager SET status = ?, abrechnung_gesendet = 1 WHERE id = ?");
        $stmt_update->bind_param('si', $new_status, $lager_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 2. Log the status change
        $log_message = "Abrechnung vom Kunden an die Buchhaltung übermittelt.";
        $stmt_log = $conn->prepare("INSERT INTO status_log (fluglager_id, status, nachricht) VALUES (?, ?, ?)");
        $stmt_log->bind_param('iss', $lager_id, $new_status, $log_message);
        $stmt_log->execute();
        $stmt_log->close();

        // 3. Send Notification Email
        $einstellungen = ladeEinstellungen($conn);
        $notification_emails_string = $einstellungen['abrechnungsbenachrichtigung_emails'] ?? '';
        
        if (!empty($notification_emails_string)) {
            // Fetch data for email body
            $user_info_stmt = $conn->prepare("SELECT vereinsname FROM users WHERE id = ?");
            $user_info_stmt->bind_param('i', $user_id);
            $user_info_stmt->execute();
            $user_info = $user_info_stmt->get_result()->fetch_assoc();
            $user_info_stmt->close();
            
            $lager_info_stmt = $conn->prepare("SELECT startdatum, enddatum FROM fluglager WHERE id = ?");
            $lager_info_stmt->bind_param('i', $lager_id);
            $lager_info_stmt->execute();
            $lager_info = $lager_info_stmt->get_result()->fetch_assoc();
            $lager_info_stmt->close();
            
            $subject = "Fluglager-Abrechnung übermittelt: " . htmlspecialchars($user_info['vereinsname']);
            $message = "<h1>Fluglager-Abrechnung übermittelt</h1>
                        <p>Das Fluglager von <strong>" . htmlspecialchars($user_info['vereinsname']) . "</strong> im Zeitraum vom <strong>" . date('d.m.Y', strtotime($lager_info['startdatum'])) . "</strong> bis <strong>" . date('d.m.Y', strtotime($lager_info['enddatum'])) . "</strong> wurde zur Abrechnung freigegeben.</p>
                        <p>Sie können die Daten jetzt in der Verwaltung prüfen.</p>
                        <p>Lager-ID: " . $lager_id . "</p>";

            $mail = new Mail();
            $admin_emails = explode(',', $notification_emails_string);
            foreach ($admin_emails as $email) {
                $clean_email = trim($email);
                if (filter_var($clean_email, FILTER_VALIDATE_EMAIL)) {
                    $mail->sendEmail($clean_email, $subject, $message, true);
                }
            }
        }

        $conn->commit();
        header('Location: abrechnung.php?id=' . $lager_id . '&message=' . urlencode('Abrechnung erfolgreich an die Buchhaltung übermittelt.'));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header('Location: abrechnung.php?id=' . $lager_id . '&error=' . urlencode('Fehler beim Senden der Abrechnung: ' . $e->getMessage()));
        exit;
    }
}

// Fallback redirect if action is unknown
header('Location: abrechnung.php?id=' . $lager_id);
exit;
?>