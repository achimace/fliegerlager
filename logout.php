<?php
// logout.php

// 1. Always start the session to be able to access it.
session_start();

// 2. Unset all session variables to clear them.
$_SESSION = array();

// 3. Destroy the session completely.
session_destroy();

// 4. Redirect the user to the customer login page.
header('Location: login_customer.php?message=' . urlencode('You have been logged out.'));
exit;
?>