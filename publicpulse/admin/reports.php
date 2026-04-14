<?php
/**
 * PublicPulse AI — Admin: Reports
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole(['admin','analyst']);
$csrf = csrfToken();
$user = currentUser();

$generated = false;
$summary   = [];
$dateFrom  = postVal('date_from') ?: date('Y-m-01');
$dateTo    = postVal('date_to')   ?: date('Y-m-d');
$rptType   = postVal('report_type') ?: 'custom';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf(postVal('csrf_token'))) {
    $summary   = Analytics::generateReportSummary($dateFrom, $dateTo);
    $generated = true;
    Database::execute(
        'INSERT INTO reports (generated_by,report_type,date_from,date_to,summary_json) VALUES (?,?,?,?,?)',
        [$user['id'], $rptType, $dateFrom, $dateTo, json_encode($summary)]
    );
}

$past = Database::fetchAll("
    SELECT r.*,u.name AS by_name FROM reports r JOIN users u ON u.id=r.generated_by
    ORDER BY r.created_at DESC LIMIT 12
");

echo renderHead('Reports');
echo '<div class="app-shell">';
echo renderSidebar($_SESSION['user_role']==='admin'?'admin_reports':'analyst_reports', $user);
echo '<div class="main-content">';
echo renderTopbar('Reports', 'Generate and export analytics reports', [
    ['href'=>APP_URL.'/api/export.php?format=csv', 'icon'=>'download', 'label'=>'Export All CSV', 'type'=>'secondary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>
  <div class="page-header">
    <div><div class="page-title">Report Generator</div><div class="page-subtitle">Create period-based reports with complaint analytics</div></div>
  </div>

  <div class="grid-2-1" style="align-items:start">
    <div style="display:flex;flex-direction:column;gap:var(--space-lg)">

      <!-- Config card -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('settings') ?> Report Configuration</div></div>
        <div class="card-body">
          <form method="POST" id="rptForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="form-group">
              <label class="form-label">Report Period</label>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <?php foreach (['daily'=>'Today','weekly'=>'This Week','monthly'=>'This Month','custom'=>'Custom'] as $v=>$l): ?>
                  <button type="button" onclick="setPeriod('<?= $v ?>')"
                          class="btn btn-secondary btn-sm" id="btn_<?= $v ?>"
                          style="<?= $rptType===$v?'background:var(--primary);color:#fff;border-color:var(--primary)':'' ?>">
                    <?= $l ?>
                  </button>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="report_type" id="rptTypeInput" value="<?= $rptType ?>">
            </div>

            <div class="grid-2" style="gap:var(--space-md)">
              <div class="form-group" style="margin:0">
                <label class="form-label required">From Date</label>
                <input type="date" name="date_from" id="dateFrom" class="form-control" value="<?= $dateFrom ?>" required>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label required">To Date</label>
                <input type="date" name="date_to" id="dateTo" class="form-control" value="<?= $dateTo ?>" max="<?= date('Y-m-d') ?>" required>
              </div>
            </div>

            <div style="margin-top:var(--space-md);display:flex;gap:8px">
              <button type="submit" class="btn btn-primary" id="genBtn" style="flex:1;justify-content:center">
                <?= icon('chart-bar') ?> Generate Report
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Report output -->
      <?php if ($generated && !empty($summary)): ?>
        <div class="card" id="reportOutput">
          <div class="card-header">
            <div class="card-title"><?= icon('file-text') ?> Report: <?= $dateFrom ?> → <?= $dateTo ?></div>
            <div style="display:flex;gap:6px">
              <a href="<?= APP_URL ?>/api/export.php?format=csv&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="btn btn-secondary btn-sm">
                <?= icon('download') ?> CSV
              </a>
              <button onclick="window.print()" class="btn btn-secondary btn-sm"><?= icon('print') ?> Print</button>
            </div>
          </div>
          <div class="card-body">
            <!-- Summary KPIs -->
            <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:var(--space-lg)">
              <div class="kpi-card blue"><div class="kpi-header"><div class="kpi-label">Total</div><div class="kpi-icon"><?= icon('inbox') ?></div></div><div class="kpi-value"><?= $summary['total'] ?></div></div>
              <div class="kpi-card red"><div class="kpi-header"><div class="kpi-label">Critical</div><div class="kpi-icon"><?= icon('zap') ?></div></div><div class="kpi-value"><?= $summary['critical'] ?></div></div>
              <div class="kpi-card green"><div class="kpi-header"><div class="kpi-label">Resolved</div><div class="kpi-icon"><?= icon('check-circle') ?></div></div><div class="kpi-value"><?= $summary['resolved'] ?></div></div>
            </div>

            <div class="grid-2" style="gap:var(--space-lg)">
              <div>
                <h4 style="font-weight:700;font-size:.86rem;margin-bottom:var(--space-sm);display:flex;align-items:center;gap:6px"><?= icon('tag') ?> By Category</h4>
                <table class="data-table">
                  <thead><tr><th>Category</th><th>Count</th><th>%</th></tr></thead>
                  <tbody>
                    <?php foreach ($summary['by_category'] as $r):
                      $pct = $summary['total']>0 ? round(($r['total']/$summary['total'])*100,1) : 0;
                    ?>
                      <tr>
                        <td style="font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
                        <td style="font-family:var(--font-mono)"><?= $r['total'] ?></td>
                        <td>
                          <div style="display:flex;align-items:center;gap:6px">
                            <div class="progress-wrap" style="flex:1;max-width:60px"><div class="progress-fill" style="width:<?= $pct ?>%;background:var(--primary-light)"></div></div>
                            <span style="font-family:var(--font-mono);font-size:.72rem"><?= $pct ?>%</span>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div>
                <h4 style="font-weight:700;font-size:.86rem;margin-bottom:var(--space-sm);display:flex;align-items:center;gap:6px"><?= icon('activity') ?> By Status</h4>
                <table class="data-table">
                  <thead><tr><th>Status</th><th>Count</th></tr></thead>
                  <tbody>
                    <?php foreach ($summary['by_status'] as $r): ?>
                      <tr><td><?= statusBadge($r['status']) ?></td><td style="font-family:var(--font-mono);font-weight:700"><?= $r['total'] ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>

                <canvas id="rptChart" height="160" style="margin-top:var(--space-md)"></canvas>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right: Past reports -->
    <div class="card">
      <div class="card-header"><div class="card-title"><?= icon('clock') ?> Report History</div></div>
      <?php if (empty($past)): ?>
        <div class="empty-state card-body"><div class="empty-icon"><?= icon('file-text') ?></div><h3>No reports yet</h3><p>Generate your first report.</p></div>
      <?php else: ?>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px;padding:var(--space-md)">
          <?php foreach ($past as $r): ?>
            <div style="background:var(--bg-elevated);border-radius:var(--radius-md);padding:var(--space-md);display:flex;align-items:center;justify-content:space-between;gap:var(--space-sm)">
              <div>
                <div style="font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:5px">
                  <?= icon('file-text','',13) ?> <?= ucfirst($r['report_type']) ?> Report
                </div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;font-family:var(--font-mono)">
                  <?= htmlspecialchars($r['date_from']) ?> → <?= htmlspecialchars($r['date_to']) ?>
                </div>
                <div style="font-size:.7rem;color:var(--text-muted)">by <?= htmlspecialchars($r['by_name']) ?> · <?= timeAgo($r['created_at']) ?></div>
              </div>
              <a href="<?= APP_URL ?>/api/export.php?format=csv&from=<?= urlencode($r['date_from']) ?>&to=<?= urlencode($r['date_to']) ?>"
                 class="btn btn-secondary btn-sm"><?= icon('download') ?></a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?= renderFoot() ?>
<script>
<?php if ($generated && !empty($summary)): ?>
renderBar('rptChart',
  <?= json_encode(array_column($summary['by_status'],'status')) ?>,
  [{label:'Count', data:<?= json_encode(array_column($summary['by_status'],'total')) ?>, color:'#3b82f6'}]
);
<?php endif; ?>

function setPeriod(type) {
  const today = new Date();
  const fmt = d => d.toISOString().split('T')[0];
  const to  = document.getElementById('dateTo');
  const from= document.getElementById('dateFrom');
  to.value  = fmt(today);
  document.getElementById('rptTypeInput').value = type;

  if (type==='daily')   { from.value = fmt(today); }
  else if (type==='weekly')  { const w=new Date(today); w.setDate(today.getDate()-6); from.value=fmt(w); }
  else if (type==='monthly') { from.value=fmt(new Date(today.getFullYear(),today.getMonth(),1)); }

  document.querySelectorAll('[id^="btn_"]').forEach(b=>{
    b.style.background=b.id===`btn_${type}`?'var(--primary)':'';
    b.style.color=b.id===`btn_${type}`?'#fff':'';
    b.style.borderColor=b.id===`btn_${type}`?'var(--primary)':'';
  });
}

document.getElementById('rptForm').addEventListener('submit',()=>{
  const b=document.getElementById('genBtn');
  b.innerHTML='<span class="loader"></span> Generating...';
  b.disabled=true;
});
// Highlight active button on load
setPeriod('<?= $rptType ?>');
</script>
</div></div>
