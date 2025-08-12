<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/vendor/autoload.php';

class Mail {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            // Server settings
            //$this->mail->SMTPDebug = SMTP::DEBUG_OFF; // Turn off debugging for normal use
			$this->mail->SMTPDebug = 0;
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.office365.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'sfg@flugplatz-ohlstadt.de';
            $this->mail->Password = 'Twin1Astir#'; // Remember to use your actual password
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 587;
            $this->mail->setFrom('sfg@flugplatz-ohlstadt.de', 'Flugplatz Ohlstadt');
            $this->mail->CharSet = 'UTF-8';
            $this->mail->addReplyTo('sfg@flugplatz-ohlstadt.de', 'Flugplatz Ohlstadt');
        } catch (Exception $e) {
            error_log("Mailer Construction Error: {$this->mail->ErrorInfo}");
        }
    }

    public function sendEmail($to, $subject, $body, $isHTML = true) {
		try {
			// Clear previous recipient and settings for this sending
			$this->mail->clearAddresses();
			$this->mail->clearAttachments(); // Make sure no old attachments are sent

			$this->mail->addAddress($to);
			$this->mail->isHTML($isHTML);
			$this->mail->Subject = $subject;
			$this->mail->Body = $body;
			$this->mail->AltBody = strip_tags($body);

			$this->mail->send();
			return true;
		} catch (Exception $e) {
			// Log the error instead of showing it to the user
			error_log("Nachricht konnte nicht gesendet werden. Mailer Error: {$this->mail->ErrorInfo}");
			// Re-throw the exception so the calling script knows there was a problem
			throw $e;
		}
	}
    
    public function sendCalendarInvite($to, $subject, $body, $icsContent, $isHTML = true) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);

            // ### CRITICAL CHANGE HERE ###
            // This content type is better for calendar invitations.
            $this->mail->addStringAttachment(
                $icsContent,
                'termin.ics',
                'base64',
                'text/calendar; charset=utf-8; method=REQUEST'
            );
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Nachricht konnte nicht gesendet werden. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}