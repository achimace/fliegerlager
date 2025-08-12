<?php
// verwaltung/handle_block_dates.php
session_start();
require_once '../Database.php';

// 1. Security check: Ensure an admin is logged in and the request is a POST.
if (!isset($_SESSION['loggedin_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$action = $_POST['action'] ?? '';
$admin_id = $_SESSION['admin_user']['userid'] ?? 0; // Get admin ID from session if available

// --- ACTION: BLOCK A NEW PERIOD ---
if ($action === 'block') {
    $startdatum = $_POST['startdatum'] ?? '';
    $enddatum = $_POST['enddatum'] ?? '';
    $grund = trim($_POST['grund'] ?? '');

    // Validate the received data
    if (empty($startdatum) || empty($enddatum) || empty($grund) || strtotime($enddatum) < strtotime($startdatum)) {
        header('Location: sperrzeiten.php?error=' . urlencode('Bitte gültige Daten für die Sperre eingeben.'));
        exit;
    }

    // Insert the new block into the database
    $stmt = $conn->prepare("INSERT INTO kalender_block (startdatum, enddatum, grund, admin_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $startdatum, $enddatum, $grund, $admin_id);
    
    if ($stmt->execute()) {
        header('Location: sperrzeiten.php?message=' . urlencode('Zeitraum erfolgreich gesperrt.'));
    } else {
        header('Location: sperrzeiten.php?error=' . urlencode('Fehler beim Sperren des Zeitraums.'));
    }
    $stmt->close();

// --- ACTION: UNBLOCK AN EXISTING PERIOD ---
} elseif ($action === 'unblock') {
    $block_id = $_POST['block_id'] ?? 0;

    if (empty($block_id)) {
        header('Location: sperrzeiten.php?error=Ungültige Anfrage.');
        exit;
    }

    // Delete the block from the database
    $stmt = $conn->prepare("DELETE FROM kalender_block WHERE id = ?");
    $stmt->bind_param('i', $block_id);

    if ($stmt->execute()) {
        header('Location: sperrzeiten.php?message=' . urlencode('Sperre erfolgreich aufgehoben.'));
    } else {
        header('Location: sperrzeiten.php?error=' . urlencode('Fehler beim Aufheben der Sperre.'));
    }
    $stmt->close();
    
} else {
    // If the action is unknown, redirect to the admin dashboard
    header('Location: index.php');
}

exit;
?>