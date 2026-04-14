<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

sessionStart();
requireRole(['admin', 'analyst']);

$from     = getVal('from');
$to       = getVal('to');
$status   = getVal('status');
$priority = getVal('priority');
$category = (int)getVal('category');
$ward     = getVal('ward');

$where  = 'WHERE 1=1';
$params = [];
if ($from)     { $where .= ' AND DATE(c.created_at)>=?'; $params[] = $from; }
if ($to)       { $where .= ' AND DATE(c.created_at)<=?'; $params[] = $to; }
if ($status)   { $where .= ' AND c.status=?';             $params[] = $status; }
if ($priority) { $where .= ' AND c.priority=?';           $params[] = $priority; }
if ($category) { $where .= ' AND c.category_id=?';        $params[] = $category; }
if ($ward)     { $where .= ' AND c.ward=?';               $params[] = $ward; }

$rows = Database::fetchAll("
    SELECT c.reference_no, c.title, c.description, cat.name AS category,
           c.status, c.priority, c.location, c.latitude, c.longitude, c.ward,
           c.ai_score, u.name AS citizen_name, u.email AS citizen_email,
           c.created_at, c.updated_at
    FROM complaints c
    JOIN users u ON u.id=c.user_id
    JOIN categories cat ON cat.id=c.category_id
    {$where}
    ORDER BY c.created_at DESC
    LIMIT 5000
", $params);

$filename = 'publicpulse_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

fputcsv($out, [
    'Reference No','Title','Description','Category','Status','Priority',
    'Location','Latitude','Longitude','Ward','AI Risk Score',
    'Citizen Name','Citizen Email','Submitted At','Last Updated'
]);

foreach ($rows as $row) {
    fputcsv($out, [
        $row['reference_no'],
        $row['title'],
        $row['description'],
        $row['category'],
        ucwords(str_replace('_', ' ', $row['status'])),
        ucfirst($row['priority']),
        $row['location'],
        $row['latitude'],
        $row['longitude'],
        $row['ward'],
        $row['ai_score'] ?? '',
        $row['citizen_name'],
        $row['citizen_email'],
        $row['created_at'],
        $row['updated_at'],
    ]);
}
fclose($out);
exit;
