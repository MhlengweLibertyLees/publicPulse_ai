<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

sessionStart();
if (!isLoggedIn()) jsonResponse(false, 'Unauthorized');

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'citizen';

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    Database::execute('UPDATE notifications SET is_read=1 WHERE user_id=?', [$uid]);
    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        jsonResponse(true, 'All marked read');
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? APP_URL));
    exit;
}

// Mark single as read + redirect
if (isset($_GET['read'], $_GET['redirect'])) {
    $nid = (int)$_GET['read'];
    if ($nid > 0) Database::execute('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?', [$nid, $uid]);
    $dest = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
    if (!$dest || !str_starts_with($dest, APP_URL)) $dest = APP_URL;
    header('Location: ' . $dest);
    exit;
}

// JSON list (for AJAX)
$notifs = Database::fetchAll(
    'SELECT n.*,c.reference_no FROM notifications n LEFT JOIN complaints c ON c.id=n.complaint_id WHERE n.user_id=? ORDER BY n.is_read ASC, n.created_at DESC LIMIT 20',
    [$uid]
);
foreach ($notifs as &$n) { $n['time_ago'] = timeAgo($n['created_at']); }
unset($n);

$unread = getUnreadCount($uid);
jsonResponse(true, 'OK', ['notifications' => $notifs, 'unread' => $unread]);
