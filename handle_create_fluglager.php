<?php
// handle_create_fluglager.php
session_start();
require_once 'Database.php';

// Helper-Funktion zur Datumsvalidierung
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// 1. Sicherheitsprüfungen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_fluglager.php');
    exit;
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login_customer.php?error=' . urlencode('Bitte zuerst einloggen.'));
    exit;
}

// 2. Formulardaten abrufen und User-ID prüfen
$user_id = $_SESSION['user_id'] ?? 0;

// --- NEUE PRÜFUNG ---
// Stellt sicher, dass eine gültige User-ID aus der Session kommt.
if ($user_id <= 0) {
    header('Location: login_customer.php?error=' . urlencode('Ihre Sitzung ist abgelaufen. Bitte erneut anmelden.'));
    exit;
}
// --- ENDE NEUE PRÜFUNG ---

$startdatum = $_POST['startdatum'] ?? '';
$enddatum = $_POST['enddatum'] ?? '';
$exklusiv = isset($_POST['exklusiv']) ? 1 : 0;

// 3. VALIDIERUNG
if (!validateDate($startdatum) || !validateDate($enddatum)) {
    $error_msg = 'Ungültiges Datumsformat. Bitte verwenden Sie das Format JJJJ-MM-TT.';
    header('Location: create_fluglager.php?error=' . urlencode($error_msg));
    exit;
}

if (strtotime($enddatum) < strtotime($startdatum)) {
    $error_msg = 'Das Abreisedatum darf nicht vor dem Anreisedatum liegen.';
    header('Location: create_fluglager.php?error=' . urlencode($error_msg));
    exit;
}

// 4. Datenbankverbindung
$db = new Database();
$conn = $db->getConnection();

// 5. Neues Fluglager in die Datenbank einfügen
$stmt = $conn->prepare(
    "INSERT INTO fluglager (user_id, startdatum, enddatum, exklusiv, status) 
     VALUES (?, ?, ?, ?, 'in_planung')"
);

$stmt->bind_param('issi', $user_id, $startdatum, $enddatum, $exklusiv);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    $success_msg = 'Fluglager angelegt. Fügen Sie nun Teilnehmer und Flugzeuge hinzu.';
    header('Location: edit_fluglager.php?id=' . $new_id . '&message=' . urlencode($success_msg));
    exit;
} else {
    // Dieser Fehler deutet auf das Foreign-Key-Problem hin
    $error_msg = 'Fehler beim Speichern des Fluglagers. Stellen Sie sicher, dass Ihr Benutzerkonto aktiv ist.';
    header('Location: create_fluglager.php?error=' . urlencode($error_msg));
    exit;
}