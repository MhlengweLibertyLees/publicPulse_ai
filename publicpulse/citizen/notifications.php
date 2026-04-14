<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart(); requireRole('citizen');
$user=currentUser(); $uid=$user['id'];

if(isset($_GET['mark_all'])){
    Database::execute('UPDATE notifications SET is_read=1 WHERE user_id=?',[$uid]);
    $_SESSION['flash']=['msg'=>'All notifications marked as read.','type'=>'success'];
    redirect('citizen/notifications.php');
}

Database::execute('UPDATE notifications SET is_read=1 WHERE user_id=?',[$uid]);
$notifs=Database::fetchAll('SELECT n.*,c.reference_no FROM notifications n LEFT JOIN complaints c ON c.id=n.complaint_id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 50',[$uid]);

echo renderHead('Notifications');
echo '<div class="app-shell">';
echo renderSidebar('citizen_notif',$user);
echo '<div class="main-content">';
echo renderTopbar('Notifications','Your alerts and status updates');
?>
<div class="page-content">
  <?= flashMsg() ?>
  <div style="max-width:680px;margin:0 auto">
    <div class="page-header">
      <div><div class="page-title">Notifications</div><div class="page-subtitle"><?=count($notifs)?> notifications</div></div>
    </div>

    <?php if(empty($notifs)): ?>
      <div class="card"><div class="empty-state">
        <div class="empty-icon"><?=icon('bell')?></div>
        <h3>No notifications yet</h3>
        <p>You'll receive updates here when your complaints are reviewed or resolved.</p>
      </div></div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach($notifs as $n):
          $ic=match($n['type']){'status_update'=>'activity','new_complaint'=>'inbox',default=>'bell'};
          $col=match($n['type']){'status_update'=>'var(--success)','new_complaint'=>'var(--primary)',default=>'var(--warning)'};
          $link=$n['complaint_id']?APP_URL.'/citizen/complaint_detail.php?id='.$n['complaint_id']:'#';
        ?>
          <a href="<?=$link?>" style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-md);text-decoration:none;color:inherit;transition:all var(--transition);box-shadow:var(--shadow-xs)"
             onmouseover="this.style.borderColor='var(--primary-light)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="width:36px;height:36px;border-radius:50%;background:<?=$col?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?=$col?>">
              <?=icon($ic)?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:.85rem"><?=htmlspecialchars($n['title'])?></div>
              <div style="font-size:.78rem;color:var(--text-secondary);margin-top:2px"><?=htmlspecialchars($n['message'])?></div>
              <div style="display:flex;align-items:center;gap:8px;margin-top:5px">
                <span style="font-size:.68rem;color:var(--text-muted);font-family:var(--font-mono)"><?=timeAgo($n['created_at'])?></span>
                <?php if($n['reference_no']): ?>
                  <span class="badge badge-blue" style="font-size:.6rem"><?=htmlspecialchars($n['reference_no'])?></span>
                <?php endif; ?>
              </div>
            </div>
            <?=icon('chevron-right','',13)?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?=renderFoot()?>
</div></div>
