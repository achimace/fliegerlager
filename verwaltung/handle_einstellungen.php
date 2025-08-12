<?php
// verwaltung/handle_einstellungen.php
session_start();
require_once '../Database.php';

// Admin-Login prüfen
if (!isset($_SESSION['loggedin_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("UPDATE einstellungen SET einstellung_wert = ? WHERE einstellung_name = ?");

// Loop through all submitted POST data and save it
foreach ($_POST as $name => $value) {
    $stmt->bind_param('ss', $value, $name);
    $stmt->execute();
}

$stmt->close();

header('Location: einstellungen.php?success=1');
exit;