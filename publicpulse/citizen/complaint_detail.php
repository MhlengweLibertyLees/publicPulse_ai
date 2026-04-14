<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart(); requireRole('citizen');
$user=currentUser(); $uid=$user['id'];
$id=(int)($_GET['id']??0);
if(!$id) redirect('citizen/complaints.php');

$complaint=Database::fetchOne("
    SELECT c.*,cat.name AS category_name,cat.icon AS cat_icon,cat.color AS cat_color
    FROM complaints c JOIN categories cat ON cat.id=c.category_id
    WHERE c.id=? AND c.user_id=?
",[$id,$uid]);
if(!$complaint) redirect('citizen/complaints.php');

$logs=Database::fetchAll("
    SELECT sl.new_status,sl.note,sl.created_at,u.name AS by_name
    FROM status_logs sl JOIN users u ON u.id=sl.changed_by
    WHERE sl.complaint_id=? ORDER BY sl.created_at ASC
",[$id]);

Database::execute('UPDATE notifications SET is_read=1 WHERE user_id=? AND complaint_id=?',[$uid,$id]);

$steps=['submitted'=>'Submitted','in_review'=>'Under Review','in_progress'=>'In Progress','resolved'=>'Resolved'];
$stepKeys=array_keys($steps);
$curIdx=array_search($complaint['status'],$stepKeys);

echo renderHead('Complaint: '.$complaint['reference_no']);
echo '<div class="app-shell">';
echo renderSidebar('citizen_complaints',$user);
echo '<div class="main-content">';
echo renderTopbar(htmlspecialchars($complaint['reference_no']),htmlspecialchars(mb_substr($complaint['title'],0,50)),[
    ['href'=>APP_URL.'/citizen/complaints.php','icon'=>'arrow-left','label'=>'Back','type'=>'secondary'],
]);
?>
<div class="page-content">
  <div style="max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:var(--space-lg)">

    <!-- Progress tracker -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?=icon('activity')?> Complaint Progress</div>
        <div style="display:flex;gap:6px">
          <?=statusBadge($complaint['status'])?>
          <?=priorityBadge($complaint['priority'])?>
        </div>
      </div>
      <div class="card-body">
        <?php if($complaint['status']!=='closed'): ?>
          <div class="status-steps" style="margin-bottom:var(--space-lg)">
            <?php foreach($steps as $sKey=>$sLabel):
              $isDone  = $curIdx!==false && array_search($sKey,$stepKeys)<=$curIdx;
              $isActive= $complaint['status']===$sKey;
              $stepClass= $isActive?'active':($isDone?'done':'');
            ?>
              <div class="status-step <?=$stepClass?>">
                <div class="step-dot">
                  <?php if($isDone||$isActive): ?>
                    <?=$isActive?icon('activity','',11):icon('check','',11)?>
                  <?php endif; ?>
                </div>
                <div class="step-label"><?=$sLabel?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if($complaint['status']==='resolved'): ?>
            <div class="alert alert-success" style="margin:0"><?=icon('check-circle')?> Your complaint has been resolved. Thank you for your patience!</div>
          <?php endif; ?>
        <?php else: ?>
          <div style="text-align:center;padding:var(--space-md);color:var(--text-muted)">
            <?=icon('archive','',20)?><br><span style="font-size:.85rem;margin-top:6px;display:block">This complaint has been closed and archived.</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Complaint info -->
    <div class="card">
      <div class="card-header">
        <div class="card-title" style="color:<?=htmlspecialchars($complaint['cat_color'])?>">
          <?=icon($complaint['cat_icon']??'tag')?>
          <?=htmlspecialchars($complaint['category_name'])?>
        </div>
        <span style="font-family:var(--font-mono);font-size:.7rem;color:var(--text-muted)">
          <?=formatDate($complaint['created_at'],'d M Y H:i')?>
        </span>
      </div>
      <div class="card-body">
        <h2 style="font-size:1.05rem;font-weight:800;margin-bottom:var(--space-md)"><?=htmlspecialchars($complaint['title'])?></h2>
        <p style="color:var(--text-secondary);line-height:1.7;font-size:.85rem;margin-bottom:var(--space-md)"><?=nl2br(htmlspecialchars($complaint['description']))?></p>
        <div class="divider"></div>
        <div style="display:flex;flex-wrap:wrap;gap:var(--space-xl);margin-top:var(--space-md);font-size:.8rem">
          <?php foreach([
            ['Reference',  $complaint['reference_no'],true],
            ['Submitted',  formatDate($complaint['created_at'],'d M Y H:i'),false],
            ['Location',   $complaint['location']??'Not specified',false],
            ['Ward',       $complaint['ward']??'Not specified',false],
          ] as [$l,$v,$mono]): ?>
            <div>
              <div style="font-family:var(--font-mono);font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:3px"><?=$l?></div>
              <div style="font-weight:600;<?=$mono?'font-family:var(--font-mono);color:var(--primary)':''?>"><?=htmlspecialchars($v)?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if($complaint['image_path']): ?>
          <div style="margin-top:var(--space-md)">
            <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);margin-bottom:8px">Attached Photo</div>
            <img src="<?=UPLOAD_URL.htmlspecialchars($complaint['image_path'])?>" alt="Complaint photo"
                 style="max-width:100%;max-height:260px;object-fit:cover;border-radius:var(--radius-md);border:1px solid var(--border)">
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Timeline -->
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('activity')?> Activity Timeline</div></div>
      <div class="card-body">
        <ul class="timeline">
          <li class="timeline-item">
            <div class="timeline-dot"><?=icon('plus','',10)?></div>
            <div class="timeline-content">
              <div class="timeline-status">Complaint Submitted</div>
              <div class="timeline-meta"><?=formatDate($complaint['created_at'],'d M Y H:i')?> — You</div>
              <div class="timeline-note">Your complaint has been received and will be reviewed shortly.</div>
            </div>
          </li>
          <?php foreach($logs as $log): ?>
            <li class="timeline-item">
              <div class="timeline-dot"><?=icon('arrow-right','',10)?></div>
              <div class="timeline-content">
                <div class="timeline-status">Status updated — <?=statusBadge($log['new_status'])?></div>
                <div class="timeline-meta"><?=formatDate($log['created_at'],'d M Y H:i')?> — <?=htmlspecialchars($log['by_name'])?></div>
                <?php if($log['note']): ?><div class="timeline-note"><?=htmlspecialchars($log['note'])?></div><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
          <?php if(empty($logs)): ?>
            <li class="timeline-item">
              <div class="timeline-dot" style="border-color:var(--border)"><?=icon('clock','',10)?></div>
              <div class="timeline-content">
                <div class="timeline-status" style="color:var(--text-muted)">Awaiting review by our team...</div>
              </div>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div style="text-align:center">
      <a href="<?=APP_URL?>/citizen/complaints.php" class="btn btn-secondary"><?=icon('arrow-left')?> Back to My Complaints</a>
    </div>
  </div>
</div>
<?=renderFoot()?>
</div></div>
