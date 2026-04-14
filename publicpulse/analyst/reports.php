<?php
// Analyst Reports — redirect to shared reports page
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
sessionStart();
requireRole(['admin','analyst']);
header('Location: '.APP_URL.'/admin/reports.php');
exit;
