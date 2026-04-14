<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('admin');
$user = currentUser();
$csrf = csrfToken();

$status   = getVal('status');
$priority = getVal('priority');
$category = getVal('category');
$search   = getVal('search');
$ward     = getVal('ward');
$page     = max(1,(int)($_GET['page']??1));
$perPage  = 20;
$offset   = ($page-1)*$perPage;

$where  = 'WHERE 1=1';
$params = [];
if ($status)   { $where .= ' AND c.status=?';      $params[]=$status; }
if ($priority) { $where .= ' AND c.priority=?';    $params[]=$priority; }
if ($category) { $where .= ' AND c.category_id=?'; $params[]=(int)$category; }
if ($ward)     { $where .= ' AND c.ward=?';         $params[]=$ward; }
if ($search)   {
    $where .= ' AND (c.title LIKE ? OR c.reference_no LIKE ? OR u.name LIKE ?)';
    $like    = "%$search%";
    $params[]=$like; $params[]=$like; $params[]=$like;
}

$baseSql  = "FROM complaints c JOIN users u ON u.id=c.user_id JOIN categories cat ON cat.id=c.category_id {$where}";
$total    = (int)Database::fetchScalar("SELECT COUNT(*) {$baseSql}", $params);
$totPgs   = (int)ceil($total/$perPage);

$complaints = Database::fetchAll(
    "SELECT c.id,c.reference_no,c.title,c.status,c.priority,c.ward,c.location,c.created_at,c.ai_score,
            u.name AS citizen_name, cat.name AS category_name, cat.color AS category_color
     {$baseSql}
     ORDER BY FIELD(c.priority,'critical','high','medium','low'),c.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params,[$perPage,$offset])
);

$categories = Database::fetchAll('SELECT id,name FROM categories WHERE is_active=1 ORDER BY name');
$wards      = Database::fetchAll('SELECT DISTINCT ward FROM complaints WHERE ward IS NOT NULL ORDER BY ward');

// Quick stats for header
$statOpen   = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE status NOT IN ('resolved','closed')");
$statCrit   = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE priority='critical' AND status NOT IN ('resolved','closed')");
$statToday  = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE DATE(created_at)=CURDATE()");

echo renderHead('Complaints');
echo '<div class="app-shell">';
echo renderSidebar('admin_complaints',$user);
echo '<div class="main-content">';
echo renderTopbar('All Complaints','Manage and respond to citizen reports',[
    ['href'=>APP_URL.'/admin/dashboard.php','icon'=>'dashboard','label'=>'Dashboard','type'=>'secondary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <div class="page-header">
    <div>
      <div class="page-title">Complaint Management</div>
      <div class="page-subtitle"><?= number_format($total) ?> record<?= $total!==1?'s':'' ?> found</div>
    </div>
    <div class="page-actions">
      <a href="<?= APP_URL ?>/api/export.php?format=csv&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>&category=<?= urlencode($category) ?>"
         class="btn btn-secondary"><?= icon('download') ?> Export CSV</a>
    </div>
  </div>

  <!-- Quick stat pills -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:var(--space-lg)">
    <a href="?status=" class="btn <?= !$status?'btn-primary':'btn-secondary' ?> btn-sm"><?= icon('inbox') ?> All (<?= number_format($total) ?>)</a>
    <a href="?status=submitted" class="btn <?= $status==='submitted'?'btn-primary':'btn-secondary' ?> btn-sm"><?= icon('send') ?> Submitted (<?= $statOpen ?>)</a>
    <a href="?priority=critical" class="btn <?= $priority==='critical'?'btn-danger':'btn-secondary' ?> btn-sm"><?= icon('zap') ?> Critical (<?= $statCrit ?>)</a>
    <a href="?date=today" class="btn <?= isset($_GET['date'])&&$_GET['date']==='today'?'btn-primary':'btn-secondary' ?> btn-sm"><?= icon('calendar') ?> Today (<?= $statToday ?>)</a>
  </div>

  <!-- Filters -->
  <form method="GET">
    <div class="filter-bar">
      <div class="input-group">
        <span class="input-icon"><?= icon('search') ?></span>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search complaints..." style="padding-left:32px;min-width:200px">
      </div>
      <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['submitted','in_review','in_progress','resolved','closed'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="priority">
        <option value="">All Priorities</option>
        <?php foreach (['critical','high','medium','low'] as $p): ?>
          <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (int)$category===(int)$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="ward">
        <option value="">All Wards</option>
        <?php foreach ($wards as $w): ?>
          <option value="<?= htmlspecialchars($w['ward']) ?>" <?= $ward===$w['ward']?'selected':'' ?>><?= htmlspecialchars($w['ward']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('filter') ?> Filter</button>
      <?php if ($status||$priority||$category||$search||$ward): ?>
        <a href="<?= APP_URL ?>/admin/complaints.php" class="btn btn-secondary btn-sm"><?= icon('x') ?> Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Table -->
  <div class="card">
    <div class="card-body no-pad">
      <?php if (empty($complaints)): ?>
        <div class="empty-state">
          <div class="empty-icon"><?= icon('inbox') ?></div>
          <h3>No complaints found</h3>
          <p>Adjust your filters or search terms to find complaints.</p>
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Reference</th><th>Title</th><th>Category</th><th>Citizen</th>
              <th>Ward</th><th>Status</th><th>Priority</th><th>AI</th><th>Filed</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($complaints as $c): ?>
              <tr data-href="<?= APP_URL ?>/admin/complaint_view.php?id=<?= $c['id'] ?>">
                <td><span class="ref-no"><?= htmlspecialchars($c['reference_no']) ?></span></td>
                <td style="max-width:180px">
                  <div style="font-weight:600;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($c['title']) ?></div>
                  <?php if ($c['ward']): ?><div style="font-size:.7rem;color:var(--text-muted);margin-top:1px"><?= htmlspecialchars($c['ward']) ?></div><?php endif; ?>
                </td>
                <td>
                  <span style="display:flex;align-items:center;gap:5px;font-size:.8rem">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= htmlspecialchars($c['category_color']) ?>;flex-shrink:0"></span>
                    <?= htmlspecialchars($c['category_name']) ?>
                  </span>
                </td>
                <td style="font-size:.8rem"><?= htmlspecialchars($c['citizen_name']) ?></td>
                <td style="font-size:.78rem;color:var(--text-secondary)"><?= htmlspecialchars($c['ward']??'—') ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?php
                  if ($c['ai_score']!==null){
                    $ac=$c['ai_score']>=75?'critical':($c['ai_score']>=50?'high':($c['ai_score']>=25?'medium':'low'));
                    echo "<span class=\"ai-score {$ac}\">{$c['ai_score']}</span>";
                  } else echo '<span style="color:var(--text-muted)">—</span>';
                ?></td>
                <td style="color:var(--text-muted);font-size:.75rem;white-space:nowrap"><?= timeAgo($c['created_at']) ?></td>
                <td style="text-align:right">
                  <a href="<?= APP_URL ?>/admin/complaint_view.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm"
                     onclick="event.stopPropagation()"><?= icon('eye') ?></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($totPgs > 1): ?>
    <div class="pagination">
      <?php
      $qBase = http_build_query(array_filter(['status'=>$status,'priority'=>$priority,'category'=>$category,'search'=>$search,'ward'=>$ward]));
      for ($p=1;$p<=$totPgs;$p++){
        $q = $qBase ? $qBase.'&page='.$p : 'page='.$p;
        echo "<a href=\"?{$q}\" class=\"page-link ".($p===$page?'active':'')."\">{$p}</a>";
      }
      ?>
    </div>
    <p style="text-align:center;font-size:.75rem;color:var(--text-muted)">Page <?= $page ?> of <?= $totPgs ?> · <?= number_format($total) ?> total</p>
  <?php endif; ?>
</div>
<?= renderFoot() ?>
</div></div>
