<?php
// handle_change_password.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'Database.php';

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header('Location: login_customer.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?error=Ungültige Anfrage');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header('Location: dashboard.php?error=Bitte alle Felder ausfüllen.');
    exit;
}
if (strlen($new_password) < 8) {
    header('Location: dashboard.php?error=Das neue Passwort muss mindestens 8 Zeichen lang sein.');
    exit;
}
if ($new_password !== $confirm_password) {
    header('Location: dashboard.php?error=Die neuen Passwörter stimmen nicht überein.');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// KORRIGIERT: "passwort_hash" statt "passwort"
$sql = "SELECT passwort_hash FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("SQL-Vorbereitungsfehler: " . htmlspecialchars($conn->error));
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: dashboard.php?error=Benutzer nicht gefunden.');
    exit;
}

$user = $result->fetch_assoc();
// KORRIGIERT: "passwort_hash" statt "passwort"
$hashed_password_from_db = $user['passwort_hash']; 

if (!password_verify($current_password, $hashed_password_from_db)) {
    header('Location: dashboard.php?error=Das aktuelle Passwort ist nicht korrekt.');
    exit;
}

$new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

// KORRIGIERT: "passwort_hash" statt "passwort"
$update_sql = "UPDATE users SET passwort_hash = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);

if ($update_stmt === false) {
    die("SQL-Update-Fehler: " . htmlspecialchars($conn->error));
}

$update_stmt->bind_param('si', $new_password_hashed, $user_id);

if ($update_stmt->execute()) {
    header('Location: dashboard.php?message=Ihr Passwort wurde erfolgreich geändert.');
} else {
    header('Location: dashboard.php?error=Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
}

$stmt->close();
$update_stmt->close();
$conn->close();
exit;