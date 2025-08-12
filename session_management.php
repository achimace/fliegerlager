<?php
session_start();

// Set session timeout duration (in seconds)
$timeout_duration = 600; // 10 minutes

// Check if user is logged in and if the last activity timestamp is set
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // If the session has timed out, unset all session variables and destroy the session
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    // Update the last activity timestamp
    $_SESSION['last_activity'] = time();
}
?>
