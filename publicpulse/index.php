<?php
/**
 * PublicPulse AI — Entry Point
 * Redirects to login or appropriate dashboard
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

sessionStart();

if (!isLoggedIn()) {
    redirect('login.php');
}

$role = $_SESSION['user_role'] ?? 'citizen';
redirect(match ($role) {
    'admin'    => 'admin/dashboard.php',
    'analyst'  => 'analyst/dashboard.php',
    default    => 'citizen/dashboard.php',
});
