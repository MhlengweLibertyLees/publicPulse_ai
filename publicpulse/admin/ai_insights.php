<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai_engine.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole(['admin','analyst']);
$csrf = csrfToken();
$user = currentUser();

// Run AI
try { AIEngine::runFullAnalysis(); } catch(\Throwable $e){}

// Handle dismiss
if ($_SERVER['REQUEST_METHOD']==='POST' && postVal('action')==='dismiss' && verifyCsrf(postVal('csrf_token'))) {
    $iid = (int)postVal('insight_id');
    if ($iid > 0) {
        Database::execute('UPDATE ai_insights SET is_active=0 WHERE id=?',[$iid]);
        $_SESSION['flash']=['msg'=>'Insight dismissed.','type'=>'success'];
    }
    redirect('admin/ai_insights.php');
}

$typeF = getVal('type');
$sevF  = getVal('severity');

$where  = 'WHERE ai.is_active=1';
$params = [];
if ($typeF) { $where .= ' AND ai.type=?';     $params[]=$typeF; }
if ($sevF)  { $where .= ' AND ai.severity=?'; $params[]=$sevF; }

$insights = Database::fetchAll("
    SELECT ai.*,cat.name AS category_name,cat.icon AS cat_icon
    FROM ai_insights ai LEFT JOIN categories cat ON cat.id=ai.category_id
    {$where}
    ORDER BY FIELD(ai.severity,'critical','warning','info'),ai.created_at DESC
", $params);

$counts = ['total'=>count($insights),'critical'=>0,'warning'=>0,'info'=>0,'hotspot'=>0,'trend'=>0,'prediction'=>0,'anomaly'=>0];
foreach ($insights as $ins) { $counts[$ins['severity']]++; $counts[$ins['type']]++; }

echo renderHead('AI Insights');
echo '<div class="app-shell">';
echo renderSidebar('admin_ai',$user);
echo '<div class="main-content">';
echo renderTopbar('AI Insights Center','Pattern detection & predictive intelligence',[
    ['href'=>APP_URL.'/admin/dashboard.php','icon'=>'dashboard','label'=>'Dashboard','type'=>'secondary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <div class="page-header">
    <div>
      <div class="page-title">AI Intelligence Center</div>
      <div class="page-subtitle">Rule-based pattern detection engine — <?= count($insights) ?> active insights · <?= date('H:i d M') ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;background:var(--success-bg);border:1px solid var(--success-border);border-radius:20px;padding:5px 14px;font-size:.75rem;color:var(--success);font-weight:600">
      <span class="live-dot"></span> ENGINE ACTIVE
    </div>
  </div>

  <!-- AI Engine Banner -->
  <div style="background:linear-gradient(135deg,var(--primary-bg) 0%,var(--accent-bg) 100%);border:1px solid var(--primary-border);border-radius:var(--radius-lg);padding:var(--space-lg);margin-bottom:var(--space-lg);display:flex;align-items:center;gap:var(--space-xl);flex-wrap:wrap">
    <div style="flex:1">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
        <?= icon('brain') ?>
        <span style="font-weight:800;font-size:.95rem">PublicPulse AI Engine v1.0</span>
        <span class="badge badge-blue" style="font-size:.6rem">RULE-BASED PHASE 1</span>
      </div>
      <div style="font-size:.8rem;color:var(--text-secondary)">Hotspot detection · Trend analysis · Anomaly detection · Predictive recurrence patterns</div>
    </div>
    <div style="display:flex;gap:var(--space-xl);flex-wrap:wrap">
      <?php foreach ([
        ['Hotspots',$counts['hotspot'],'danger','fire'],
        ['Trends',$counts['trend'],'warning','trending-up'],
        ['Predictions',$counts['prediction'],'purple','target'],
        ['Anomalies',$counts['anomaly'],'teal','zap'],
      ] as [$label,$cnt,$color,$ic]): ?>
        <div style="text-align:center">
          <div style="font-size:1.5rem;font-weight:800;color:var(--<?= $color ?>);line-height:1"><?= $cnt ?></div>
          <div style="font-size:.68rem;color:var(--text-muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:.08em"><?= $label ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Severity KPIs -->
  <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:var(--space-lg)">
    <div class="kpi-card red"><div class="kpi-header"><div class="kpi-label">Critical</div><div class="kpi-icon"><?= icon('zap') ?></div></div><div class="kpi-value"><?= $counts['critical'] ?></div></div>
    <div class="kpi-card yellow"><div class="kpi-header"><div class="kpi-label">Warning</div><div class="kpi-icon"><?= icon('alert-triangle') ?></div></div><div class="kpi-value"><?= $counts['warning'] ?></div></div>
    <div class="kpi-card blue"><div class="kpi-header"><div class="kpi-label">Info</div><div class="kpi-icon"><?= icon('info') ?></div></div><div class="kpi-value"><?= $counts['info'] ?></div></div>
  </div>

  <!-- Filters -->
  <form method="GET">
    <div class="filter-bar">
      <select name="type">
        <option value="">All Types</option>
        <?php foreach (['hotspot','trend','prediction','anomaly'] as $t): ?>
          <option value="<?= $t ?>" <?= $typeF===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="severity">
        <option value="">All Severities</option>
        <?php foreach (['critical','warning','info'] as $s): ?>
          <option value="<?= $s ?>" <?= $sevF===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('filter') ?> Filter</button>
      <?php if ($typeF||$sevF): ?><a href="<?= APP_URL ?>/admin/ai_insights.php" class="btn btn-secondary btn-sm"><?= icon('x') ?> Clear</a><?php endif; ?>
    </div>
  </form>

  <!-- Insights -->
  <?php if (empty($insights)): ?>
    <div class="card">
      <div class="empty-state">
        <div class="empty-icon"><?= icon('check-circle') ?></div>
        <h3>No Active Alerts</h3>
        <p>All metrics are within normal thresholds. The AI engine continues monitoring.</p>
      </div>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-sm)">
      <?php foreach ($insights as $ins):
        $typeIcons=['hotspot'=>'fire','trend'=>'trending-up','prediction'=>'target','anomaly'=>'zap'];
        $ic = $typeIcons[$ins['type']] ?? 'alert-triangle';
        $data = $ins['data_json'] ? json_decode($ins['data_json'],true) : [];
      ?>
        <div class="card">
          <div class="card-body" style="display:flex;gap:var(--space-md);align-items:flex-start">
            <!-- Severity strip -->
            <div style="width:4px;border-radius:4px;background:<?= $ins['severity']==='critical'?'var(--danger)':($ins['severity']==='warning'?'var(--warning)':'var(--primary-light)') ?>;align-self:stretch;flex-shrink:0"></div>

            <!-- Icon -->
            <div style="width:40px;height:40px;border-radius:var(--radius-md);background:<?= $ins['severity']==='critical'?'var(--danger-bg)':($ins['severity']==='warning'?'var(--warning-bg)':'var(--primary-bg)') ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $ins['severity']==='critical'?'var(--danger)':($ins['severity']==='warning'?'var(--warning)':'var(--primary)') ?>">
              <?= icon($ic) ?>
            </div>

            <!-- Content -->
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:8px">
                <div>
                  <div style="font-weight:700;font-size:.9rem;margin-bottom:3px"><?= htmlspecialchars($ins['title']) ?></div>
                  <div style="font-size:.8rem;color:var(--text-secondary);line-height:1.5"><?= htmlspecialchars($ins['description']) ?></div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
                  <span class="badge <?= $ins['severity']==='critical'?'badge-red':($ins['severity']==='warning'?'badge-yellow':'badge-blue') ?>">
                    <?= strtoupper($ins['severity']) ?>
                  </span>
                  <span style="font-size:.68rem;color:var(--text-muted);font-family:var(--font-mono)"><?= timeAgo($ins['created_at']) ?></span>
                </div>
              </div>

              <!-- Tags -->
              <div style="display:flex;align-items:center;gap:6px;margin-top:10px;flex-wrap:wrap">
                <span class="insight-tag"><?= strtoupper($ins['type']) ?></span>
                <?php if ($ins['ward']): ?><span class="insight-tag"><?= icon('map-pin','',11) ?> <?= htmlspecialchars($ins['ward']) ?></span><?php endif; ?>
                <?php if ($ins['category_name']): ?><span class="insight-tag"><?= icon('tag','',11) ?> <?= htmlspecialchars($ins['category_name']) ?></span><?php endif; ?>
              </div>

              <!-- Data details -->
              <?php if (!empty($data)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
                  <?php foreach ($data as $k=>$v): ?>
                    <div style="background:var(--bg-elevated);border-radius:var(--radius-sm);padding:4px 10px;font-family:var(--font-mono);font-size:.7rem;border:1px solid var(--border)">
                      <span style="color:var(--text-muted)"><?= htmlspecialchars(str_replace('_',' ',ucfirst($k))) ?>:</span>
                      <span style="font-weight:700;margin-left:3px"><?= htmlspecialchars(is_array($v)?implode(', ',$v):(string)$v) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- Actions -->
              <div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap">
                <?php if ($ins['ward']): ?>
                  <a href="<?= APP_URL ?>/admin/complaints.php?ward=<?= urlencode($ins['ward']) ?>" class="btn btn-secondary btn-sm">
                    <?= icon('inbox') ?> View Ward Complaints
                  </a>
                <?php endif; ?>
                <?php if ($ins['category_id']): ?>
                  <a href="<?= APP_URL ?>/admin/complaints.php?category=<?= $ins['category_id'] ?>" class="btn btn-secondary btn-sm">
                    <?= icon('filter') ?> Filter by Category
                  </a>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                  <input type="hidden" name="action"      value="dismiss">
                  <input type="hidden" name="insight_id"  value="<?= $ins['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-sm"
                          onclick="return confirm('Dismiss this insight?')">
                    <?= icon('eye-off') ?> Dismiss
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?= renderFoot() ?>
</div></div>
