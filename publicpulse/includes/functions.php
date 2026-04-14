<?php
/**
 * PublicPulse AI — Core Functions v2.0
 * Security · Session · Validation · Formatting · Upload · Cache · Notifications
 */

require_once __DIR__ . '/config.php';

// ═══════════════════════════════════════════════════════════════════
// SESSION
// ═══════════════════════════════════════════════════════════════════

function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime',  SESSION_LIFETIME);
        session_start();
    }
}

function isLoggedIn(): bool
{
    sessionStart();
    if (!isset($_SESSION['user_id'], $_SESSION['user_role'])) return false;
    // Session expiry check
    if (isset($_SESSION['login_at']) && (time() - $_SESSION['login_at']) > SESSION_LIFETIME) {
        logout();
    }
    return true;
}

function requireLogin(): void
{
    if (!isLoggedIn()) redirect('login.php');
}

function requireRole(string|array $roles): void
{
    requireLogin();
    $allowed = (array)$roles;
    if (!in_array($_SESSION['user_role'], $allowed, true)) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><title>403 Access Denied</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
        <style>body{font-family:"Plus Jakarta Sans",sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f4f8}
        .box{text-align:center;padding:48px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:400px}
        h1{font-size:3rem;margin:0;color:#dc2626}p{color:#64748b;margin:12px 0 24px}a{background:#1d4ed8;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600}</style></head>
        <body><div class="box"><h1>403</h1><p>You do not have permission to access this page.</p>
        <a href="' . APP_URL . '">Go Home</a></div></body></html>');
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = Database::fetchOne(
        'SELECT id,name,email,role,ward,phone,is_active,created_at,updated_at FROM users WHERE id=? AND is_active=1',
        [$_SESSION['user_id']]
    );
    return $cache;
}

function login(array $user): void
{
    sessionStart();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['login_at']  = time();
}

function logout(): never
{
    sessionStart();
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    redirect('login.php');
}

// ═══════════════════════════════════════════════════════════════════
// SECURITY
// ═══════════════════════════════════════════════════════════════════

function hashPassword(string $pwd): string
{
    return password_hash($pwd, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

function verifyPassword(string $pwd, string $hash): bool
{
    return password_verify($pwd, $hash);
}

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrfToken(): string
{
    sessionStart();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function validateEmail(string $e): bool
{
    return (bool)filter_var($e, FILTER_VALIDATE_EMAIL);
}

function validatePassword(string $p): bool
{
    return strlen($p) >= 8
        && preg_match('/[A-Z]/', $p)
        && preg_match('/[0-9]/', $p)
        && preg_match('/[\W_]/', $p);
}

// ═══════════════════════════════════════════════════════════════════
// INPUT HELPERS
// ═══════════════════════════════════════════════════════════════════

function postVal(string $k, string $default = ''): string
{
    return isset($_POST[$k]) ? sanitize($_POST[$k]) : $default;
}

function getVal(string $k, string $default = ''): string
{
    return isset($_GET[$k]) ? sanitize($_GET[$k]) : $default;
}

// ═══════════════════════════════════════════════════════════════════
// RESPONSE
// ═══════════════════════════════════════════════════════════════════

function redirect(string $path): never
{
    $url = (str_starts_with($path, 'http')) ? $path : APP_URL . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

function jsonResponse(bool $success, string $message, array $data = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// FORMATTING
// ═══════════════════════════════════════════════════════════════════

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 0)      return 'just now';
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return (int)($diff/60)   . 'm ago';
    if ($diff < 86400)  return (int)($diff/3600)  . 'h ago';
    if ($diff < 604800) return (int)($diff/86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

function formatDate(string $datetime, string $format = 'd M Y'): string
{
    return date($format, strtotime($datetime));
}

function statusBadge(string $status): string
{
    $map = [
        'submitted'   => ['badge-blue',   'Submitted'],
        'in_review'   => ['badge-yellow', 'In Review'],
        'in_progress' => ['badge-purple', 'In Progress'],
        'resolved'    => ['badge-green',  'Resolved'],
        'closed'      => ['badge-gray',   'Closed'],
    ];
    [$cls, $lbl] = $map[$status] ?? ['badge-gray', ucfirst($status)];
    return "<span class=\"badge {$cls}\">{$lbl}</span>";
}

function priorityBadge(string $priority): string
{
    $map = [
        'low'      => ['badge-gray',   'Low'],
        'medium'   => ['badge-blue',   'Medium'],
        'high'     => ['badge-yellow', 'High'],
        'critical' => ['badge-red',    'Critical'],
    ];
    [$cls, $lbl] = $map[$priority] ?? ['badge-gray', ucfirst($priority)];
    return "<span class=\"badge {$cls}\">{$lbl}</span>";
}

function generateReference(): string
{
    $count = (int)Database::fetchScalar('SELECT COUNT(*)+1 FROM complaints');
    return 'PP-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ═══════════════════════════════════════════════════════════════════
// FILE UPLOAD
// ═══════════════════════════════════════════════════════════════════

function uploadImage(array $file, string $prefix = 'img'): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK)         return null;
    if ($file['size']  > MAX_FILE_SIZE)            return null;
    if (!in_array($file['type'], ALLOWED_TYPES))   return null;

    // Extra validation: check magic bytes
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_TYPES))           return null;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    return move_uploaded_file($file['tmp_name'], $dest) ? $filename : null;
}

// ═══════════════════════════════════════════════════════════════════
// CACHE
// ═══════════════════════════════════════════════════════════════════

function getCached(string $key): mixed
{
    $row = Database::fetchOne(
        'SELECT cache_value FROM analytics_cache WHERE cache_key=? AND expires_at>NOW()',
        [$key]
    );
    return $row ? json_decode($row['cache_value'], true) : null;
}

function setCache(string $key, mixed $value, int $ttl = 300): void
{
    $exp = date('Y-m-d H:i:s', time() + $ttl);
    Database::execute(
        'REPLACE INTO analytics_cache (cache_key,cache_value,expires_at) VALUES (?,?,?)',
        [$key, json_encode($value), $exp]
    );
}

function clearCache(string $prefix = ''): void
{
    if ($prefix) {
        Database::execute("DELETE FROM analytics_cache WHERE cache_key LIKE ?", [$prefix . '%']);
    } else {
        Database::execute("DELETE FROM analytics_cache");
    }
}

// ═══════════════════════════════════════════════════════════════════
// NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════════

function createNotification(int $userId, ?int $complaintId, string $type, string $title, string $message): void
{
    Database::execute(
        'INSERT INTO notifications (user_id,complaint_id,type,title,message) VALUES (?,?,?,?,?)',
        [$userId, $complaintId, $type, $title, $message]
    );
}

function notifyAdmins(?int $complaintId, string $type, string $title, string $message, int $excludeUserId = 0): void
{
    $admins = Database::fetchAll(
        'SELECT id FROM users WHERE role="admin" AND is_active=1' . ($excludeUserId ? ' AND id<>?' : ''),
        $excludeUserId ? [$excludeUserId] : []
    );
    foreach ($admins as $a) {
        createNotification($a['id'], $complaintId, $type, $title, $message);
    }
}

function getUnreadCount(int $userId): int
{
    return (int)Database::fetchScalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$userId]);
}
