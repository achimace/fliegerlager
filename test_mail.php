<?php
// test_mail.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Make sure the path to your Mail.php is correct
require_once 'Mail.php';

echo "Initializing Mail class...<br><hr>";

try {
    // This will create the mailer object and should immediately
    // start printing the SMTP debug output because of your constructor.
    $mail = new Mail();

    // --- CONFIGURE YOUR TEST ---
    $recipientEmail = "achimace@googlemail.com"; // !! CHANGE THIS
    $subject = "Test: Kalendereinladung vom Flugplatz";
    $body = "Hallo,<br><br>Dies ist ein Test der Kalenderfunktion.";

    // --- iCALENDAR EVENT DETAILS ---
    $eventName = "Test-Termin: Fluglager";
    $startDate = new DateTime('tomorrow 4:00pm'); // Starts tomorrow at 4 PM
    $endDate = new DateTime('tomorrow 5:00pm');   // Ends tomorrow at 5 PM

    // Create the iCalendar content string
    $icsContent = "BEGIN:VCALENDAR\r\n";
    $icsContent .= "VERSION:2.0\r\n";
    $icsContent .= "PRODID:-//Flugplatz Ohlstadt//Terminplanung//DE\r\n";
    $icsContent .= "BEGIN:VEVENT\r\n";
    $icsContent .= "UID:" . md5(uniqid()) . "@flugplatz-ohlstadt.de\r\n";
    $icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $icsContent .= "DTSTART:" . $startDate->format('Ymd\THis\Z') . "\r\n";
    $icsContent .= "DTEND:" . $endDate->format('Ymd\THis\Z') . "\r\n";
    $icsContent .= "SUMMARY:" . $eventName . "\r\n";
    $icsContent .= "END:VEVENT\r\n";
    $icsContent .= "END:VCALENDAR\r\n";
    
    echo "<hr>Attempting to send invite to: " . htmlspecialchars($recipientEmail) . "<br><hr>";

    // Call your specific method for sending calendar invites
    $success = $mail->sendCalendarInvite($recipientEmail, $subject, $body, $icsContent);
    
    if ($success) {
        echo "<br><b>Success!</b> The sendCalendarInvite() function returned true.";
    } else {
        echo "<br><b>Failure.</b> The sendCalendarInvite() function returned false. Check the debug output above and/or your server's error log.";
    }

} catch (Exception $e) {
    echo "<br><b>Fatal Error!</b> An exception was caught: <pre>" . $e->getMessage() . "</pre>";
}
?>