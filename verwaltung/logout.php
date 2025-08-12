<?php
/**
 * logout.php
 * Beendet die aktuelle Session und leitet zur Login-Seite weiter.
 */

// 1. Session starten, um auf sie zugreifen zu können
session_start();

// 2. Alle Session-Variablen löschen, indem das Session-Array geleert wird
$_SESSION = array();

// 3. Die Session selbst auf dem Server zerstören
session_destroy();

// 4. Den Benutzer zur Haupt-Login-Seite für Kunden weiterleiten
header('Location: login.php');
exit;
?>