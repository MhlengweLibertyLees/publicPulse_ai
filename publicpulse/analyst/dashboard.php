<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/ai_engine.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole(['admin','analyst']);
$user=currentUser();

try { AIEngine::runFullAnalysis(); } catch(\Throwable $e){}

$kpis    = Analytics::getKPIs();
$monthly = Analytics::monthlyTrend(6);
$daily   = Analytics::dailyVolume(30);
$byCat   = Analytics::byCategory();
$byWard  = Analytics::byWard();
$resRate = Analytics::resolutionRateOverTime(6);
$catComp = Analytics::categoryComparison();
$insights= AIEngine::getDashboardInsights();
$byPri   = Analytics::byPriority();

$totalCrit=(int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE priority='critical'");
$avgAge=Database::fetchScalar("SELECT AVG(DATEDIFF(NOW(),created_at)) FROM complaints WHERE status NOT IN ('resolved','closed')");

echo renderHead('Analytics Hub');
echo '<div class="app-shell">';
echo renderSidebar('analyst_dashboard',$user);
echo '<div class="main-content">';
echo renderTopbar('Analytics Hub','Data Intelligence & Pattern Detection',[
    ['href'=>APP_URL.'/analyst/reports.php','icon'=>'file-text','label'=>'Generate Report','type'=>'primary'],
    ['href'=>APP_URL.'/analyst/export.php', 'icon'=>'download',  'label'=>'Export Data',    'type'=>'secondary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <div class="page-header">
    <div>
      <div class="page-title">Data Intelligence Analytics</div>
      <div class="page-subtitle">Pattern detection, trends, and predictive insights · <?=date('d M Y')?></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;background:var(--success-bg);border:1px solid var(--success-border);border-radius:20px;padding:5px 14px;font-size:.75rem;color:var(--success);font-weight:600">
      <span class="live-dot"></span> AI ENGINE ACTIVE
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card blue"><div class="kpi-header"><div class="kpi-label">Total Complaints</div><div class="kpi-icon"><?=icon('inbox')?></div></div><div class="kpi-value" data-counter="<?=$kpis['total']?>"><?=number_format($kpis['total'])?></div><div class="kpi-change"><?=icon('calendar')?> <?=$kpis['today']?> today</div></div>
    <div class="kpi-card green"><div class="kpi-header"><div class="kpi-label">Resolution Rate</div><div class="kpi-icon"><?=icon('percent')?></div></div><div class="kpi-value" style="font-size:1.6rem"><?=$kpis['resRate']?>%</div><div class="kpi-change <?=$kpis['resRate']>=60?'up':'down'?>"><?=icon($kpis['resRate']>=60?'trending-up':'trending-down')?> <?=$kpis['resRate']>=60?'On track':'Needs improvement'?></div></div>
    <div class="kpi-card red"><div class="kpi-header"><div class="kpi-label">Total Critical</div><div class="kpi-icon"><?=icon('zap')?></div></div><div class="kpi-value" data-counter="<?=$totalCrit?>"><?=$totalCrit?></div><div class="kpi-change down"><?=icon('alert-triangle')?> Urgent</div></div>
    <div class="kpi-card yellow"><div class="kpi-header"><div class="kpi-label">Avg Open Age</div><div class="kpi-icon"><?=icon('clock')?></div></div><div class="kpi-value" style="font-size:1.5rem"><?=round((float)$avgAge,1)?>d</div><div class="kpi-change"><?=icon('clock')?> Days open</div></div>
    <div class="kpi-card purple"><div class="kpi-header"><div class="kpi-label">AI Flags</div><div class="kpi-icon"><?=icon('brain')?></div></div><div class="kpi-value" data-counter="<?=count($insights)?>"><?=count($insights)?></div><div class="kpi-change"><?=icon('flag')?> Active alerts</div></div>
    <div class="kpi-card teal"><div class="kpi-header"><div class="kpi-label">Avg Resolution</div><div class="kpi-icon"><?=icon('activity')?></div></div><div class="kpi-value" style="font-size:1.5rem"><?=$kpis['avg_resolution_hours']?>h</div><div class="kpi-change"><?=icon('clock')?> Average</div></div>
  </div>

  <!-- AI Insights -->
  <?php if(!empty($insights)): ?>
  <div class="card" style="margin-bottom:var(--space-lg)">
    <div class="card-header">
      <div class="card-title"><?=icon('brain')?> AI Detected Patterns <span style="font-size:.67rem;background:var(--primary-bg);color:var(--primary);padding:2px 8px;border-radius:10px;font-family:var(--font-mono)">LIVE</span></div>
      <a href="<?=APP_URL?>/admin/ai_insights.php" style="font-size:.75rem;color:var(--primary);font-weight:600">View all</a>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:8px">
      <?php foreach(array_slice($insights,0,4) as $ins):
        $typeIcons=['hotspot'=>'fire','trend'=>'trending-up','prediction'=>'target','anomaly'=>'zap'];
      ?>
        <div class="insight-card <?=htmlspecialchars($ins['severity'])?>">
          <div class="insight-icon"><?=icon($typeIcons[$ins['type']]??'alert-triangle')?></div>
          <div class="insight-body">
            <div class="insight-title"><?=htmlspecialchars($ins['title'])?></div>
            <div class="insight-desc"><?=htmlspecialchars(mb_substr($ins['description'],0,100))?>...</div>
            <div class="insight-meta">
              <span class="insight-tag"><?=strtoupper($ins['type'])?></span>
              <?php if($ins['ward']): ?><span class="insight-tag"><?=htmlspecialchars($ins['ward'])?></span><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Charts Row 1 -->
  <div class="grid-2" style="margin-bottom:var(--space-lg)">
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('chart-line')?> Monthly Volume: Filed vs Resolved</div></div>
      <div class="card-body"><canvas id="chartMonthly" height="220"></canvas></div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('activity')?> Daily Volume (30 Days)</div></div>
      <div class="card-body"><canvas id="chartDaily" height="220"></canvas></div>
    </div>
  </div>

  <!-- Charts Row 2 -->
  <div class="grid-3-2" style="margin-bottom:var(--space-lg)">
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('chart-line')?> Resolution Rate Trend (6 Months)</div></div>
      <div class="card-body"><canvas id="chartResRate" height="220"></canvas></div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('chart-pie')?> Complaints by Category</div></div>
      <div class="card-body"><canvas id="chartCat" height="220"></canvas></div>
    </div>
  </div>

  <!-- Charts Row 3 -->
  <div class="grid-2" style="margin-bottom:var(--space-lg)">
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('map-pin')?> Ward Hotspot Ranking (Top 10)</div></div>
      <div class="card-body"><canvas id="chartWard" height="260"></canvas></div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('chart-bar')?> Category: Current vs Previous 30 Days</div></div>
      <div class="card-body"><canvas id="chartCatComp" height="260"></canvas></div>
    </div>
  </div>

  <!-- Category performance table -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?=icon('layers')?> Category Performance Analysis</div>
      <span style="font-family:var(--font-mono);font-size:.67rem;color:var(--text-muted);background:var(--bg-elevated);padding:3px 8px;border-radius:10px">CURRENT vs PREVIOUS 30 DAYS</span>
    </div>
    <div class="card-body no-pad">
      <table class="data-table">
        <thead><tr><th>Category</th><th>Prev 30d</th><th>Current 30d</th><th>Change</th><th>Trend</th></tr></thead>
        <tbody>
          <?php foreach($catComp as $row):
            $prev=(int)$row['prev_period']; $curr=(int)$row['current_period'];
            $change=$prev>0?round((($curr-$prev)/$prev)*100):($curr>0?100:0);
            $trend=$change>10?'Rising':($change<-10?'Falling':'Stable');
            $tc=$trend==='Rising'?'var(--danger)':($trend==='Falling'?'var(--success)':'var(--text-muted)');
            $ti=$trend==='Rising'?'trending-up':($trend==='Falling'?'trending-down':'activity');
          ?>
            <tr>
              <td style="font-weight:600"><?=htmlspecialchars($row['category'])?></td>
              <td style="font-family:var(--font-mono)"><?=$prev?></td>
              <td style="font-family:var(--font-mono);font-weight:700"><?=$curr?></td>
              <td style="font-family:var(--font-mono);color:<?=$tc?>"><?=$change>=0?'+':''?><?=$change?>%</td>
              <td><span style="display:inline-flex;align-items:center;gap:4px;font-size:.78rem;color:<?=$tc?>;font-weight:600"><?=icon($ti,'',12)?> <?=$trend?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?=renderFoot()?>
<script>
renderLine('chartMonthly',
  <?=json_encode(array_column($monthly,'label'))?>,
  [
    {label:'Total Filed',data:<?=json_encode(array_column($monthly,'total'))?>,    color:'#3b82f6'},
    {label:'Resolved',   data:<?=json_encode(array_column($monthly,'resolved'))?>, color:'#059669',fill:false},
  ]
);
renderLine('chartDaily',
  <?=json_encode(array_column($daily,'label'))?>,
  [{label:'Daily Complaints',data:<?=json_encode(array_column($daily,'value'))?>,color:'#0891b2'}]
);
renderLine('chartResRate',
  <?=json_encode(array_column($resRate,'label'))?>,
  [{label:'Resolution Rate %',data:<?=json_encode(array_column($resRate,'rate'))?>,color:'#059669'}],
  {scales:{y:{max:100,ticks:{callback:v=>v+'%'}}}}
);
renderDoughnut('chartCat',{
  labels:<?=json_encode(array_column($byCat,'label'))?>,
  values:<?=json_encode(array_column($byCat,'value'))?>,
  colors:<?=json_encode(array_column($byCat,'color'))?>,
});
renderHBar('chartWard',
  <?=json_encode(array_column(array_slice($byWard,0,10),'label'))?>,
  <?=json_encode(array_column(array_slice($byWard,0,10),'total'))?>,
  '#3b82f6'
);
renderBar('chartCatComp',
  <?=json_encode(array_column($catComp,'category'))?>,
  [
    {label:'Current 30d', data:<?=json_encode(array_column($catComp,'current_period'))?>,color:'#3b82f6'},
    {label:'Previous 30d',data:<?=json_encode(array_column($catComp,'prev_period'))?>,   color:'#bfdbfe'},
  ]
);
</script>
</div></div>
