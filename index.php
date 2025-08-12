<?php
// index.php

// 1. Always start the session to check the login status.
session_start();

// 2. Check if the 'loggedin' session variable is set and is true.
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // 3. If the user is logged in, redirect them to their dashboard.
    header('Location: dashboard.php');
    exit;
} else {
    // 4. If the user is not logged in, redirect them to the customer login page.
    header('Location: login_customer.php');
    exit;
}
?>