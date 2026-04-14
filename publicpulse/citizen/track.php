<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart(); requireRole('citizen');
$user=currentUser();
$ref=strtoupper(trim(getVal('ref')));
$complaint=null; $logs=[];
if($ref){
    $complaint=Database::fetchOne("SELECT c.*,cat.name AS category_name,cat.icon AS cat_icon,cat.color AS cat_color FROM complaints c JOIN categories cat ON cat.id=c.category_id WHERE c.reference_no=?",[$ref]);
    if($complaint) $logs=Database::fetchAll("SELECT sl.new_status,sl.note,sl.created_at,u.name AS by FROM status_logs sl JOIN users u ON u.id=sl.changed_by WHERE sl.complaint_id=? ORDER BY sl.created_at ASC",[$complaint['id']]);
}

echo renderHead('Track Complaint');
echo '<div class="app-shell">';
echo renderSidebar('citizen_track',$user);
echo '<div class="main-content">';
echo renderTopbar('Track Complaint','Look up any complaint by reference number');
?>
<div class="page-content">
  <div style="max-width:680px;margin:0 auto">
    <div class="page-header"><div><div class="page-title">Track Complaint Status</div><div class="page-subtitle">Enter your reference number to see the latest update</div></div></div>

    <div class="card" style="margin-bottom:var(--space-lg)">
      <div class="card-body">
        <form method="GET" style="display:flex;gap:8px">
          <div class="input-group" style="flex:1">
            <span class="input-icon"><?=icon('hash')?></span>
            <input type="text" name="ref" class="form-control" style="padding-left:34px;font-family:var(--font-mono);font-size:.95rem;letter-spacing:.05em"
                   placeholder="PP-2024-001" value="<?=htmlspecialchars($ref)?>" maxlength="20" autocomplete="off">
          </div>
          <button type="submit" class="btn btn-primary"><?=icon('search')?> Track</button>
        </form>
        <div class="form-hint" style="margin-top:8px">Format: <code style="font-family:var(--font-mono);background:var(--bg-elevated);padding:1px 6px;border-radius:3px;border:1px solid var(--border)">PP-YYYY-XXXX</code></div>
      </div>
    </div>

    <?php if($ref && !$complaint): ?>
      <div class="alert alert-danger"><?=icon('x-circle')?><div><strong>Reference not found.</strong> No complaint matches <code><?=htmlspecialchars($ref)?></code>. Please check the number and try again.</div></div>
    <?php elseif($complaint): ?>
      <div class="card">
        <div class="card-header">
          <div style="display:flex;align-items:center;gap:9px">
            <div style="width:34px;height:34px;border-radius:var(--radius-sm);background:<?=htmlspecialchars($complaint['cat_color'])?>18;display:flex;align-items:center;justify-content:center;color:<?=htmlspecialchars($complaint['cat_color'])?>;flex-shrink:0">
              <?=icon($complaint['cat_icon']??'tag')?>
            </div>
            <div>
              <div style="font-family:var(--font-mono);font-size:.68rem;color:var(--primary);font-weight:600"><?=htmlspecialchars($complaint['reference_no'])?></div>
              <div style="font-weight:700;font-size:.9rem"><?=htmlspecialchars($complaint['title'])?></div>
            </div>
          </div>
          <div style="display:flex;gap:5px">
            <?=statusBadge($complaint['status'])?>
            <?=priorityBadge($complaint['priority'])?>
          </div>
        </div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:var(--space-md);margin-bottom:var(--space-lg)">
            <?php foreach([
              ['Category',$complaint['category_name']],
              ['Submitted',formatDate($complaint['created_at'],'d M Y')],
              ['Last Update',timeAgo($complaint['updated_at'])],
              ['Ward',$complaint['ward']??'Not specified'],
            ] as [$l,$v]): ?>
              <div style="background:var(--bg-elevated);border-radius:var(--radius-md);padding:var(--space-md)">
                <div style="font-family:var(--font-mono);font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:4px"><?=$l?></div>
                <div style="font-size:.84rem;font-weight:600"><?=htmlspecialchars($v)?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <h4 style="font-weight:700;font-size:.86rem;margin-bottom:var(--space-md);display:flex;align-items:center;gap:6px"><?=icon('activity')?> Timeline</h4>
          <ul class="timeline">
            <li class="timeline-item">
              <div class="timeline-dot"><?=icon('plus','',10)?></div>
              <div class="timeline-content">
                <div class="timeline-status">Complaint Received</div>
                <div class="timeline-meta"><?=formatDate($complaint['created_at'],'d M Y H:i')?></div>
              </div>
            </li>
            <?php foreach($logs as $log): ?>
              <li class="timeline-item">
                <div class="timeline-dot"><?=icon('arrow-right','',10)?></div>
                <div class="timeline-content">
                  <div class="timeline-status">Updated to <?=statusBadge($log['new_status'])?></div>
                  <div class="timeline-meta"><?=formatDate($log['created_at'],'d M Y H:i')?></div>
                  <?php if($log['note']): ?><div class="timeline-note"><?=htmlspecialchars($log['note'])?></div><?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?=renderFoot()?>
</div></div>
