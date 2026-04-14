<?php
/**
 * PublicPulse AI — Admin: Notifications Center
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole(['admin','analyst']);
$user = currentUser();
$uid  = $user['id'];

// Mark all as read if requested
if (isset($_GET['mark_all'])) {
    Database::execute('UPDATE notifications SET is_read=1 WHERE user_id=?', [$uid]);
    $_SESSION['flash'] = ['msg'=>'All notifications marked as read.','type'=>'success'];
    redirect('admin/notifications.php');
}

$page    = max(1,(int)($_GET['page']??1));
$perPage = 20;
$offset  = ($page-1)*$perPage;

$total   = (int)Database::fetchScalar('SELECT COUNT(*) FROM notifications WHERE user_id=?',[$uid]);
$pages   = (int)ceil($total/$perPage);
$notifs  = Database::fetchAll(
    'SELECT n.*,c.reference_no FROM notifications n LEFT JOIN complaints c ON c.id=n.complaint_id
     WHERE n.user_id=? ORDER BY n.is_read ASC, n.created_at DESC LIMIT ? OFFSET ?',
    [$uid, $perPage, $offset]
);
$unread  = (int)Database::fetchScalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0',[$uid]);

echo renderHead('Notifications');
echo '<div class="app-shell">';
echo renderSidebar('admin_dashboard', $user);
echo '<div class="main-content">';
echo renderTopbar('Notifications','Your alerts and system messages');
?>
<div class="page-content">
  <?= flashMsg() ?>
  <div class="page-header">
    <div>
      <div class="page-title">Notifications</div>
      <div class="page-subtitle"><?= $total ?> total · <?= $unread ?> unread</div>
    </div>
    <div class="page-actions">
      <?php if ($unread > 0): ?>
        <a href="?mark_all=1" class="btn btn-secondary"><?= icon('check') ?> Mark All Read</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body no-pad">
      <?php if (empty($notifs)): ?>
        <div class="empty-state">
          <div class="empty-icon"><?= icon('bell') ?></div>
          <h3>No notifications</h3>
          <p>You're all caught up! Notifications will appear here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifs as $n):
          $typeIcon  = match($n['type']) { 'status_update'=>'activity', 'new_complaint'=>'inbox', default=>'bell' };
          $typeColor = match($n['type']) { 'new_complaint'=>'var(--primary)', 'status_update'=>'var(--success)', default=>'var(--warning)' };
          $link = $n['complaint_id'] ? APP_URL.'/admin/complaint_view.php?id='.$n['complaint_id'] : '#';
        ?>
          <a href="<?= APP_URL ?>/api/notifications.php?read=<?= $n['id'] ?>&redirect=<?= urlencode($link) ?>"
             style="display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background var(--transition);<?= !$n['is_read']?'background:var(--primary-bg)':'' ?>"
             onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='<?= !$n['is_read']?'var(--primary-bg)':'' ?>'">
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $typeColor ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $typeColor ?>">
              <?= icon($typeIcon) ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:<?= $n['is_read']?'500':'700' ?>;font-size:.85rem"><?= htmlspecialchars($n['title']) ?></div>
              <div style="font-size:.78rem;color:var(--text-secondary);margin-top:2px"><?= htmlspecialchars($n['message']) ?></div>
              <div style="display:flex;align-items:center;gap:10px;margin-top:5px">
                <span style="font-size:.68rem;color:var(--text-muted);font-family:var(--font-mono)"><?= timeAgo($n['created_at']) ?></span>
                <?php if ($n['reference_no']): ?>
                  <span class="badge badge-blue"><?= htmlspecialchars($n['reference_no']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!$n['is_read']): ?>
              <div style="width:9px;height:9px;border-radius:50%;background:var(--primary-light);flex-shrink:0;margin-top:6px"></div>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php for ($p=1;$p<=$pages;$p++): ?>
        <a href="?page=<?= $p ?>" class="page-link <?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?= renderFoot() ?>
</div></div>
