<?php
/**
 * PublicPulse AI — Layout v2.0
 * Sidebar · Topbar · Notification Dropdown · Flash Messages
 */

require_once __DIR__ . '/icons.php';

function renderHead(string $title, array $extraCss = []): string
{
    sessionStart();
    $base   = APP_URL;
    $csrf   = csrfToken();
    $role   = $_SESSION['user_role'] ?? 'citizen';
    $extras = implode("\n", array_map(fn($h) => "<link rel=\"stylesheet\" href=\"{$h}\">", $extraCss));

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{$csrf}">
<title>{$title} — PublicPulse AI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{$base}/assets/css/app.css">
{$extras}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  const PP_URL = '{$base}';
  const PP_ROLE = '{$role}';
</script>
</head>
<body>
HTML;
}

function renderSidebar(string $active = '', ?array $user = null): string
{
    $base  = APP_URL;
    $role  = $user['role'] ?? 'citizen';
    $name  = htmlspecialchars($user['name'] ?? 'User');
    $init  = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1));
    $roles = ['admin' => 'Administrator', 'analyst' => 'Data Analyst', 'citizen' => 'Citizen'];
    $rl    = $roles[$role] ?? 'User';
    $openCount = _getSidebarOpenCount();

    // Logo SVG
    $logoSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="none"><path d="M3 21h18M4 21V7l8-4 8 4v14M9 21V12h6v9M9 9h1m4 0h1M9 15h1m4 0h1"/></svg>';

    $nav = '';
    if ($role === 'citizen') {
        $nav .= _navItem($base.'/citizen/dashboard.php',    'dashboard',   'Dashboard',         'citizen_dashboard',  $active);
        $nav .= _navItem($base.'/citizen/submit.php',       'plus-circle', 'Submit Complaint',  'citizen_submit',     $active);
        $nav .= _navItem($base.'/citizen/complaints.php',   'list',        'My Complaints',     'citizen_complaints', $active);
        $nav .= _navItem($base.'/citizen/track.php',        'search',      'Track Status',      'citizen_track',      $active);
        $nav .= _navItem($base.'/citizen/notifications.php','bell',        'Notifications',     'citizen_notif',      $active);
    } elseif ($role === 'admin') {
        $nav .= _navSection('Overview');
        $nav .= _navItem($base.'/admin/dashboard.php',     'dashboard',   'Dashboard',         'admin_dashboard',    $active);
        $nav .= _navItem($base.'/admin/complaints.php',    'inbox',       'All Complaints',    'admin_complaints',   $active, $openCount);
        $nav .= _navItem($base.'/admin/ai_insights.php',   'brain',       'AI Insights',       'admin_ai',           $active);
        $nav .= _navSection('Management');
        $nav .= _navItem($base.'/admin/users.php',         'users',       'Manage Users',      'admin_users',        $active);
        $nav .= _navItem($base.'/admin/categories.php',    'tag',         'Categories',        'admin_categories',   $active);
        $nav .= _navItem($base.'/admin/reports.php',       'file-text',   'Reports',           'admin_reports',      $active);
        $nav .= _navSection('Location');
        $nav .= _navItem($base.'/location.php',            'map',         'Location Map',      'location',           $active);
    } elseif ($role === 'analyst') {
        $nav .= _navSection('Analytics');
        $nav .= _navItem($base.'/analyst/dashboard.php',   'chart-line',  'Analytics Hub',     'analyst_dashboard',  $active);
        $nav .= _navItem($base.'/location.php',            'map',         'Location Map',      'location',           $active);
        $nav .= _navSection('Reports');
        $nav .= _navItem($base.'/analyst/reports.php',     'file-text',   'Generate Reports',  'analyst_reports',    $active);
        $nav .= _navItem($base.'/analyst/export.php',      'download',    'Export Data',       'analyst_export',     $active);
    }

    $nav .= _navSection('Account');
    $nav .= _navItem($base.'/settings.php', 'settings', 'Settings', 'settings', $active);

    return <<<HTML
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">{$logoSvg}</div>
    <div>
      <div class="sidebar-logo-text">Public<span>Pulse</span></div>
      <div class="sidebar-logo-sub">Intelligence Platform</div>
    </div>
  </div>
  <nav class="sidebar-nav">{$nav}</nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar">{$init}</div>
      <div class="user-meta" style="flex:1;min-width:0">
        <div class="user-name">{$name}</div>
        <div class="user-role">{$rl}</div>
      </div>
      <a href="{$base}/auth/logout.php" title="Sign out"
         style="width:30px;height:30px;border:1px solid rgba(255,255,255,.15);background:transparent;color:rgba(255,255,255,.6);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;text-decoration:none"
         onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='transparent'">
        <?= icon('logout','',14) ?>
      </a>
    </div>
  </div>
</aside>
HTML;
}

function _navSection(string $label): string
{
    return "<div class=\"nav-section-label\">{$label}</div>";
}

function _navItem(string $href, string $iconName, string $label, string $key, string $active, int $badge = 0): string
{
    $isActive  = $active === $key ? 'active' : '';
    $badgeHtml = $badge > 0 ? "<span class=\"nav-badge\">{$badge}</span>" : '';
    $ic = icon($iconName);
    return "<a href=\"{$href}\" class=\"nav-item {$isActive}\">{$ic} {$label}{$badgeHtml}</a>";
}

function _getSidebarOpenCount(): int
{
    try {
        return (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE status NOT IN ('resolved','closed')");
    } catch (\Throwable $e) { return 0; }
}

function renderTopbar(string $title, string $subtitle = '', array $actions = []): string
{
    $base   = APP_URL;
    $uid    = $_SESSION['user_id'] ?? 0;
    $unread = 0;
    $notifHtml = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.82rem">No notifications</div>';

    if ($uid) {
        try {
            $unread  = getUnreadCount($uid);
            $notifs  = Database::fetchAll(
                'SELECT n.*,c.reference_no FROM notifications n LEFT JOIN complaints c ON c.id=n.complaint_id WHERE n.user_id=? ORDER BY n.is_read ASC, n.created_at DESC LIMIT 6',
                [$uid]
            );
            if ($notifs) {
                $notifHtml = '';
                $role = $_SESSION['user_role'] ?? 'citizen';
                foreach ($notifs as $n) {
                    $unreadCls = !$n['is_read'] ? 'unread' : '';
                    $dotStyle  = !$n['is_read'] ? '' : 'style="background:#cbd5e1"';
                    $link      = '#';
                    if ($n['complaint_id']) {
                        $link = $role === 'citizen'
                            ? "{$base}/citizen/complaint_detail.php?id={$n['complaint_id']}"
                            : "{$base}/admin/complaint_view.php?id={$n['complaint_id']}";
                    }
                    $readUrl   = "{$base}/api/notifications.php?read={$n['id']}&redirect=" . urlencode($link);
                    $title_esc = htmlspecialchars($n['title']);
                    $msg_esc   = htmlspecialchars(mb_substr($n['message'], 0, 72)) . (mb_strlen($n['message']) > 72 ? '...' : '');
                    $time_esc  = timeAgo($n['created_at']);
                    $ref       = $n['reference_no'] ? "<span class=\"badge badge-blue\" style=\"font-size:.58rem\">{$n['reference_no']}</span>" : '';
                    $notifHtml .= <<<HTML
<a href="{$readUrl}" class="notif-item {$unreadCls}">
  <div class="notif-dot" {$dotStyle}></div>
  <div>
    <div class="notif-item-title">{$title_esc}</div>
    <div class="notif-item-msg">{$msg_esc}</div>
    <div class="notif-item-time" style="display:flex;align-items:center;gap:6px;margin-top:3px">{$time_esc} {$ref}</div>
  </div>
</a>
HTML;
                }
            }
        } catch (\Throwable $e) {}
    }

    $badgeDsp  = $unread > 0 ? 'flex' : 'none';
    $badgeNum  = $unread;
    $role      = $_SESSION['user_role'] ?? 'citizen';
    $allHref   = $role === 'citizen' ? "{$base}/citizen/notifications.php" : "{$base}/admin/notifications.php";
    $markAllUrl= "{$base}/api/notifications.php?mark_all_read=1";

    $actionHtml = '';
    foreach ($actions as $btn) {
        $ic = icon($btn['icon'] ?? 'arrow-right');
        $actionHtml .= "<a href=\"{$btn['href']}\" class=\"btn btn-{$btn['type']} btn-sm\">{$ic} {$btn['label']}</a>";
    }

    $menuIc = icon('menu');
    $bellIc = icon('bell');
    $settIc = icon('settings');
    $title_esc = htmlspecialchars($title);
    $sub_esc   = htmlspecialchars($subtitle);

    return <<<HTML
<header class="topbar">
  <button id="sidebarToggle" class="icon-btn" style="display:none" aria-label="Menu">{$menuIc}</button>
  <div class="topbar-title">{$title_esc}<span>{$sub_esc}</span></div>
  <div class="topbar-actions">
    {$actionHtml}
    <div class="notif-wrapper" style="position:relative">
      <button class="icon-btn" id="notifBtn" title="Notifications" aria-label="Notifications">
        {$bellIc}
        <span class="notif-badge" id="notifBadge" style="display:{$badgeDsp}">{$badgeNum}</span>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
          <h4>Notifications</h4>
          <a href="{$allHref}" style="font-size:.75rem;color:var(--primary);font-weight:600">View all</a>
        </div>
        <div class="notif-list" id="notifList">{$notifHtml}</div>
        <div class="notif-footer">
          <a href="{$markAllUrl}" id="markAllBtn">Mark all as read</a>
        </div>
      </div>
    </div>
    <a href="{$base}/settings.php" class="icon-btn" title="Settings">{$settIc}</a>
  </div>
</header>
HTML;
}

function renderFoot(array $extraJs = []): string
{
    $base    = APP_URL;
    $scripts = implode("\n", array_map(fn($s) => "<script src=\"{$s}\"></script>", $extraJs));

    return <<<HTML
<script src="{$base}/assets/js/app.js"></script>
{$scripts}
</body></html>
HTML;
}

function flashMsg(): string
{
    sessionStart();
    if (!empty($_SESSION['flash'])) {
        $msg  = htmlspecialchars($_SESSION['flash']['msg'],  ENT_QUOTES);
        $type = htmlspecialchars($_SESSION['flash']['type'], ENT_QUOTES);
        unset($_SESSION['flash']);
        return "<div id=\"flashMsg\" data-msg=\"{$msg}\" data-type=\"{$type}\" style=\"display:none\"></div>";
    }
    return '';
}
