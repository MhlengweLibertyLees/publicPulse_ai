<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analytics.php';

sessionStart();
if (!isLoggedIn()) jsonResponse(false, 'Unauthorized');

$kpis   = Analytics::getKPIs();
$unread = getUnreadCount((int)$_SESSION['user_id']);
jsonResponse(true, 'OK', ['kpis' => $kpis, 'unread' => $unread]);
