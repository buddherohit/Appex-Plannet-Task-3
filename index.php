<?php
/**
 * Smart User Management System - Application Entrypoint
 */
require_once __DIR__ . '/config/database.php';
start_secure_session();

if (is_logged_in()) {
    header("Location: dashboard.php");
} else {
    header("Location: auth/login.php");
}
exit();
?>
