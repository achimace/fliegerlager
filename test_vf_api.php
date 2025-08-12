<?php
// test_vf_api.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Vereinsflieger API Test</h1>";

require_once 'VereinsfliegerRestInterface.php';
$config = require_once 'config.php';

// Credentials are now loaded from config.php
$vf_admin_user = $config['vf_admin_user'];
$vf_admin_pass = $config['vf_admin_pass'];

// 1. Create an instance of the interface
$vfApi = new VereinsfliegerRestInterface();

echo "<p>Versuche, mich bei Vereinsflieger anzumelden...</p>";

// 2. Attempt to sign in
if ($vfApi->SignIn($vf_admin_user, $vf_admin_pass)) {
    
    echo "<p style='color: green; font-weight: bold;'>Anmeldung ERFOLGREICH!</p>";
    
    // 3. If sign-in was successful, try to create a calendar event
    echo "<p>Versuche, einen Test-Termin für heute zu erstellen...</p>";
    
    // ================== HIER IST DIE ÄNDERUNG ==================
    // We use PHP's DateTime object to create a perfectly formatted string.
    // The API expects the format "YYYY-MM-DD HH:MM".
    $timezone = new DateTimeZone('Europe/Berlin');
    
    $dateFromObj = new DateTime('today 15:00', $timezone);
    $dateToObj = new DateTime('today 16:00', $timezone);

    $title = "API Test-Termin";
    $dateFrom = $dateFromObj->format('Y-m-d H:i');
    $dateTo = $dateToObj->format('Y-m-d H:i');
    $comment = "Dieser Termin wurde automatisch vom Buchungssystem-Testskript erstellt.";
    $location = "Test-Ort";
    // ==========================================================

    if ($vfApi->createCalendarAppointment($title, $dateFrom, $dateTo, $comment, $location)) {
        echo "<p style='color: green; font-weight: bold;'>Termin-Erstellung ERFOLGREICH!</p>";
        echo "<p>Bitte prüfen Sie jetzt den Vereinsflieger-Kalender. Der neue Termin sollte nun das korrekte Datum und die korrekte Uhrzeit haben.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>FEHLER: Termin-Erstellung fehlgeschlagen, obwohl die Anmeldung erfolgreich war.</p>";
    }

    // 4. Sign out from the API
    $vfApi->SignOut();
    echo "<p>Abmeldung von der API durchgeführt.</p>";

} else {
    echo "<p style='color: red; font-weight: bold;'>FEHLER: Anmeldung bei Vereinsflieger ist fehlgeschlagen.</p>";
}

echo "<hr><p>Test beendet.</p>";
?>