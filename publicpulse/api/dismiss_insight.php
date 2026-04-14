<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

sessionStart();
requireRole(['admin', 'analyst']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf(postVal('csrf_token'))) {
    $id = (int)postVal('insight_id');
    if ($id > 0) {
        Database::execute('UPDATE ai_insights SET is_active=0 WHERE id=?', [$id]);
        clearCache('ai_dashboard_insights');
        $_SESSION['flash'] = ['msg' => 'Insight dismissed.', 'type' => 'success'];
    }
}
header('Location: ' . APP_URL . '/admin/ai_insights.php');
exit;
