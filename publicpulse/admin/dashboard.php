<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/ai_engine.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('admin');
$user = currentUser();

// Run AI silently
try { AIEngine::runFullAnalysis(); } catch(\Throwable $e) {}

$kpis     = Analytics::getKPIs();
$byCat    = Analytics::byCategory();
$monthly  = Analytics::monthlyTrend(6);
$byWard   = Analytics::byWard();
$byPri    = Analytics::byPriority();
$recent   = Analytics::recentComplaints(10);
$insights = AIEngine::getDashboardInsights();
$catComp  = Analytics::categoryComparison();
$daily    = Analytics::dailyVolume(14);

echo renderHead('Dashboard');
echo '<div class="app-shell">';
echo renderSidebar('admin_dashboard', $user);
echo '<div class="main-content">';
echo renderTopbar('Dashboard', 'Welcome back, ' . htmlspecialchars(explode(' ', $user['name'])[0]), [
    ['href' => APP_URL.'/admin/reports.php', 'icon' => 'download', 'label' => 'Reports', 'type' => 'secondary'],
    ['href' => APP_URL.'/admin/complaints.php?status=submitted', 'icon' => 'inbox', 'label' => 'New Cases', 'type' => 'primary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <div class="page-header">
    <div>
      <div class="page-title">Service Intelligence Dashboard</div>
      <div class="page-subtitle">Real-time overview · <?= date('l, d F Y') ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;background:var(--success-bg);border:1px solid var(--success-border);border-radius:20px;padding:5px 12px;font-size:.75rem;color:var(--success);font-weight:600">
      <span class="live-dot"></span> AI Engine Active
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="kpi-grid">
    <div class="kpi-card blue">
      <div class="kpi-header">
        <div class="kpi-label">Total Complaints</div>
        <div class="kpi-icon"><?= icon('inbox') ?></div>
      </div>
      <div class="kpi-value" data-counter="<?= $kpis['total'] ?>"><?= number_format($kpis['total']) ?></div>
      <div class="kpi-change"><?= icon('calendar') ?> <?= $kpis['today'] ?> new today</div>
    </div>
    <div class="kpi-card yellow">
      <div class="kpi-header">
        <div class="kpi-label">Open Cases</div>
        <div class="kpi-icon"><?= icon('clock') ?></div>
      </div>
      <div class="kpi-value" data-counter="<?= $kpis['open'] ?>"><?= number_format($kpis['open']) ?></div>
      <div class="kpi-change"><?= icon('alert-triangle') ?> Require attention</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-header">
        <div class="kpi-label">Resolved</div>
        <div class="kpi-icon"><?= icon('check-circle') ?></div>
      </div>
      <div class="kpi-value" data-counter="<?= $kpis['resolved'] ?>"><?= number_format($kpis['resolved']) ?></div>
      <div class="kpi-change up"><?= icon('trending-up') ?> <?= $kpis['resRate'] ?>% resolution rate</div>
    </div>
    <div class="kpi-card red">
      <div class="kpi-header">
        <div class="kpi-label">Critical Open</div>
        <div class="kpi-icon"><?= icon('zap') ?></div>
      </div>
      <div class="kpi-value" data-counter="<?= $kpis['critical'] ?>"><?= number_format($kpis['critical']) ?></div>
      <div class="kpi-change down"><?= icon('arrow-up') ?> Urgent action needed</div>
    </div>
    <div class="kpi-card teal">
      <div class="kpi-header">
        <div class="kpi-label">Avg Resolution</div>
        <div class="kpi-icon"><?= icon('activity') ?></div>
      </div>
      <div class="kpi-value" style="font-size:1.5rem"><?= $kpis['avg_resolution_hours'] ?>h</div>
      <div class="kpi-change"><?= icon('clock') ?> Average hours</div>
    </div>
    <div class="kpi-card purple">
      <div class="kpi-header">
        <div class="kpi-label">AI Alerts</div>
        <div class="kpi-icon"><?= icon('brain') ?></div>
      </div>
      <div class="kpi-value" data-counter="<?= count($insights) ?>"><?= count($insights) ?></div>
      <div class="kpi-change"><?= icon('flag') ?> Active flags</div>
    </div>
  </div>

  <!-- Row 1: Monthly trend + Doughnut -->
  <div class="grid-3-2" style="margin-bottom:var(--space-lg)">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('chart-line') ?> Complaint Volume (6 Months)</div>
        <span style="font-family:var(--font-mono);font-size:.67rem;color:var(--text-muted);background:var(--bg-elevated);padding:3px 8px;border-radius:10px">MONTHLY</span>
      </div>
      <div class="card-body">
        <canvas id="chartMonthly" height="200"></canvas>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('chart-pie') ?> By Category</div>
      </div>
      <div class="card-body">
        <canvas id="chartCategory" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Row 2: Ward bar + Priority -->
  <div class="grid-2" style="margin-bottom:var(--space-lg)">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('map-pin') ?> Complaints by Ward (Top 10)</div>
      </div>
      <div class="card-body">
        <canvas id="chartWard" height="240"></canvas>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('layers') ?> Priority Distribution</div>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        <?php
        $priColors = ['critical'=>'#dc2626','high'=>'#d97706','medium'=>'#3b82f6','low'=>'#94a3b8'];
        $priTotal  = array_sum(array_column($byPri,'value')) ?: 1;
        foreach ($byPri as $p):
            $pct   = round(($p['value']/$priTotal)*100);
            $col   = $priColors[strtolower($p['label'])] ?? '#94a3b8';
        ?>
          <div>
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:.8rem">
              <span style="font-weight:600;text-transform:capitalize"><?= htmlspecialchars($p['label']) ?></span>
              <span style="font-family:var(--font-mono);color:var(--text-muted)"><?= $p['value'] ?> <span style="color:<?= $col ?>">(<?= $pct ?>%)</span></span>
            </div>
            <div class="progress-wrap">
              <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
            </div>
          </div>
        <?php endforeach; ?>
        <canvas id="chartPriority" height="130" style="margin-top:6px"></canvas>
      </div>
    </div>
  </div>

  <!-- Row 3: AI Insights + Recent Complaints -->
  <div class="grid-1-2" style="margin-bottom:var(--space-lg);align-items:start">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('brain') ?> AI Insights <span style="font-size:.67rem;background:var(--primary-bg);color:var(--primary);padding:2px 7px;border-radius:10px;font-family:var(--font-mono)">LIVE</span></div>
        <a href="<?= APP_URL ?>/admin/ai_insights.php" style="font-size:.75rem;color:var(--primary);font-weight:600">View all</a>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <?php if (empty($insights)): ?>
          <div class="empty-state" style="padding:var(--space-lg)">
            <div class="empty-icon"><?= icon('check-circle') ?></div>
            <h3>All Clear</h3><p>No active AI alerts at this time.</p>
          </div>
        <?php else: ?>
          <?php foreach (array_slice($insights,0,5) as $ins):
            $typeIcons=['hotspot'=>'fire','trend'=>'trending-up','prediction'=>'target','anomaly'=>'zap'];
            $ic = $typeIcons[$ins['type']] ?? 'alert-triangle';
          ?>
            <div class="insight-card <?= htmlspecialchars($ins['severity']) ?>">
              <div class="insight-icon"><?= icon($ic) ?></div>
              <div class="insight-body">
                <div class="insight-title"><?= htmlspecialchars($ins['title']) ?></div>
                <div class="insight-desc"><?= htmlspecialchars(mb_substr($ins['description'],0,110)).'...' ?></div>
                <div class="insight-meta">
                  <span class="insight-tag"><?= strtoupper($ins['type']) ?></span>
                  <?php if ($ins['ward']): ?><span class="insight-tag"><?= htmlspecialchars($ins['ward']) ?></span><?php endif; ?>
                  <span class="insight-tag"><?= timeAgo($ins['created_at']) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('clock') ?> Recent Complaints</div>
        <a href="<?= APP_URL ?>/admin/complaints.php" style="font-size:.75rem;color:var(--primary);font-weight:600">View all</a>
      </div>
      <div class="card-body no-pad">
        <table class="data-table">
          <thead>
            <tr><th>Ref</th><th>Title</th><th>Category</th><th>Status</th><th>Priority</th><th>AI</th><th>Filed</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $c): ?>
              <tr data-href="<?= APP_URL ?>/admin/complaint_view.php?id=<?= $c['id'] ?>">
                <td><span class="ref-no"><?= htmlspecialchars($c['reference_no']) ?></span></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600">
                  <?= htmlspecialchars($c['title']) ?>
                </td>
                <td style="font-size:.78rem;color:var(--text-secondary)"><?= htmlspecialchars($c['category_name']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?php
                  if ($c['ai_score'] !== null) {
                    $ac = $c['ai_score']>=75?'critical':($c['ai_score']>=50?'high':($c['ai_score']>=25?'medium':'low'));
                    echo "<span class=\"ai-score {$ac}\">{$c['ai_score']}</span>";
                  } else echo '<span style="color:var(--text-muted)">—</span>';
                ?></td>
                <td style="color:var(--text-muted);font-size:.75rem;white-space:nowrap"><?= timeAgo($c['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Row 4: Daily volume + Cat comparison -->
  <div class="grid-2" style="margin-bottom:var(--space-lg)">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('activity') ?> Daily Volume (14 Days)</div>
      </div>
      <div class="card-body">
        <canvas id="chartDaily" height="180"></canvas>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('chart-bar') ?> Category: This vs Last 30 Days</div>
      </div>
      <div class="card-body">
        <canvas id="chartCatComp" height="180"></canvas>
      </div>
    </div>
  </div>

</div>
<?= renderFoot() ?>

<script>
// Ensure Chart.js defaults
Chart.defaults.maintainAspectRatio = true;

// Monthly trend
renderLine('chartMonthly',
  <?= json_encode(array_column($monthly,'label')) ?>,
  [
    {label:'Total Filed',  data:<?= json_encode(array_column($monthly,'total')) ?>,    color:'#3b82f6'},
    {label:'Resolved',     data:<?= json_encode(array_column($monthly,'resolved')) ?>, color:'#059669', fill:false},
  ]
);

// Category doughnut
renderDoughnut('chartCategory', {
  labels: <?= json_encode(array_column($byCat,'label')) ?>,
  values: <?= json_encode(array_column($byCat,'value')) ?>,
  colors: <?= json_encode(array_column($byCat,'color')) ?>,
});

// Ward horizontal bar
renderHBar('chartWard',
  <?= json_encode(array_column(array_slice($byWard,0,10),'label')) ?>,
  <?= json_encode(array_column(array_slice($byWard,0,10),'total')) ?>,
  '#3b82f6'
);

// Priority doughnut
renderDoughnut('chartPriority', {
  labels: <?= json_encode(array_column($byPri,'label')) ?>,
  values: <?= json_encode(array_column($byPri,'value')) ?>,
  colors: ['#dc2626','#d97706','#3b82f6','#94a3b8'],
});

// Daily volume bar
renderBar('chartDaily',
  <?= json_encode(array_column($daily,'label')) ?>,
  [{label:'Complaints', data:<?= json_encode(array_column($daily,'value')) ?>, color:'#3b82f6'}]
);

// Category comparison
renderBar('chartCatComp',
  <?= json_encode(array_column($catComp,'category')) ?>,
  [
    {label:'Current 30d', data:<?= json_encode(array_column($catComp,'current_period')) ?>, color:'#3b82f6'},
    {label:'Previous 30d',data:<?= json_encode(array_column($catComp,'prev_period')) ?>,    color:'#bfdbfe'},
  ]
);

initAutoRefresh(60000);
</script>
</div></div>
