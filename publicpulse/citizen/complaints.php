<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart(); requireRole('citizen');
$user=$user=currentUser(); $uid=$user['id'];
$status=getVal('status'); $search=getVal('search');
$page=max(1,(int)($_GET['page']??1)); $perPage=10; $offset=($page-1)*$perPage;

$where='WHERE c.user_id=?'; $params=[$uid];
if($status){$where.=' AND c.status=?';$params[]=$status;}
if($search){$where.=' AND (c.title LIKE ? OR c.reference_no LIKE ?)';$l="%$search%";$params[]=$l;$params[]=$l;}
$total=(int)Database::fetchScalar("SELECT COUNT(*) FROM complaints c {$where}",$params);
$totPgs=(int)ceil($total/$perPage);
$complaints=Database::fetchAll(
    "SELECT c.id,c.reference_no,c.title,c.status,c.priority,c.created_at,c.updated_at,
            cat.name AS category_name,cat.icon AS cat_icon,cat.color AS cat_color
     FROM complaints c JOIN categories cat ON cat.id=c.category_id {$where}
     ORDER BY c.updated_at DESC LIMIT ? OFFSET ?",
    array_merge($params,[$perPage,$offset])
);

echo renderHead('My Complaints');
echo '<div class="app-shell">';
echo renderSidebar('citizen_complaints',$user);
echo '<div class="main-content">';
echo renderTopbar('My Complaints','All your submitted reports',[
    ['href'=>APP_URL.'/citizen/submit.php','icon'=>'plus','label'=>'New Complaint','type'=>'primary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>
  <div class="page-header">
    <div><div class="page-title">My Complaints</div><div class="page-subtitle"><?= $total ?> complaint<?= $total!==1?'s':'' ?></div></div>
  </div>

  <form method="GET">
    <div class="filter-bar">
      <div class="input-group"><span class="input-icon"><?= icon('search') ?></span>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search..." style="padding-left:32px;min-width:180px"></div>
      <select name="status">
        <option value="">All Statuses</option>
        <?php foreach(['submitted','in_review','in_progress','resolved','closed'] as $s): ?>
          <option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=ucwords(str_replace('_',' ',$s))?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><?=icon('filter')?> Filter</button>
      <?php if($status||$search): ?><a href="<?=APP_URL?>/citizen/complaints.php" class="btn btn-secondary btn-sm"><?=icon('x')?> Clear</a><?php endif; ?>
    </div>
  </form>

  <?php if(empty($complaints)): ?>
    <div class="card"><div class="empty-state">
      <div class="empty-icon"><?=icon('inbox')?></div>
      <h3><?=$search||$status?'No results':'No complaints yet'?></h3>
      <p><?=$search||$status?'Try different filters.':'Submit your first complaint to get started.'?></p>
      <?php if(!$search&&!$status): ?><a href="<?=APP_URL?>/citizen/submit.php" class="btn btn-primary" style="margin-top:var(--space-md)"><?=icon('plus')?> Submit Complaint</a><?php endif; ?>
    </div></div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach($complaints as $c): ?>
        <a href="<?=APP_URL?>/citizen/complaint_detail.php?id=<?=$c['id']?>" class="complaint-card">
          <div style="display:flex;align-items:center;gap:14px">
            <div style="width:40px;height:40px;border-radius:var(--radius-sm);background:<?=htmlspecialchars($c['cat_color'])?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?=htmlspecialchars($c['cat_color'])?>">
              <?=icon($c['cat_icon']??'tag')?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-family:var(--font-mono);font-size:.65rem;color:var(--primary);font-weight:600"><?=htmlspecialchars($c['reference_no'])?></div>
              <div style="font-weight:700;font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px"><?=htmlspecialchars($c['title'])?></div>
              <div style="font-size:.73rem;color:var(--text-muted);margin-top:2px"><?=htmlspecialchars($c['category_name'])?> · <?=formatDate($c['created_at'],'d M Y')?> · Updated <?=timeAgo($c['updated_at'])?></div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
              <?=statusBadge($c['status'])?>
              <?=priorityBadge($c['priority'])?>
            </div>
            <?=icon('chevron-right','',14)?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if($totPgs>1): ?>
      <div class="pagination">
        <?php for($p=1;$p<=$totPgs;$p++){$q=http_build_query(array_filter(['status'=>$status,'search'=>$search,'page'=>$p]));echo"<a href=\"?{$q}\" class=\"page-link ".($p===$page?'active':'')."\">{$p}</a>";}?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?=renderFoot()?>
</div></div>
