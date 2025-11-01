<?php
/**
 * Logout Page
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

require_once __DIR__ . '/includes/auth.php';

$auth = getAuth();
$result = $auth->logout();

// Redirect to home page
header('Location: /?message=' . urlencode($result['message']));
exit;
