<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole(['admin','analyst']);
$user=currentUser();
$categories=Database::fetchAll('SELECT id,name FROM categories WHERE is_active=1 ORDER BY name');
$totalRows=(int)Database::fetchScalar('SELECT COUNT(*) FROM complaints');

echo renderHead('Export Data');
echo '<div class="app-shell">';
echo renderSidebar('analyst_export',$user);
echo '<div class="main-content">';
echo renderTopbar('Export Data','Download complaint data in CSV format');
?>
<div class="page-content">
  <div style="max-width:680px;margin:0 auto">
    <div class="page-header">
      <div><div class="page-title">Export Data</div><div class="page-subtitle"><?=number_format($totalRows)?> total records available</div></div>
    </div>

    <div class="card" style="margin-bottom:var(--space-lg)">
      <div class="card-header"><div class="card-title"><?=icon('settings')?> Export Configuration</div></div>
      <div class="card-body">
        <form action="<?=APP_URL?>/api/export.php" method="GET" id="exportForm">
          <input type="hidden" name="format" value="csv">
          <div class="grid-2" style="gap:var(--space-md)">
            <div class="form-group" style="margin:0">
              <label class="form-label">From Date</label>
              <input type="date" name="from" class="form-control" value="<?=date('Y-m-01')?>">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">To Date</label>
              <input type="date" name="to" class="form-control" value="<?=date('Y-m-d')?>">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Status Filter</label>
              <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <?php foreach(['submitted','in_review','in_progress','resolved','closed'] as $s): ?>
                  <option value="<?=$s?>"><?=ucwords(str_replace('_',' ',$s))?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Priority Filter</label>
              <select name="priority" class="form-control">
                <option value="">All Priorities</option>
                <?php foreach(['critical','high','medium','low'] as $p): ?>
                  <option value="<?=$p?>"><?=ucfirst($p)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin:0;grid-column:span 2">
              <label class="form-label">Category Filter</label>
              <select name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                  <option value="<?=$cat['id']?>"><?=htmlspecialchars($cat['name'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Preview columns -->
          <div style="margin-top:var(--space-lg);background:var(--bg-elevated);border-radius:var(--radius-md);padding:var(--space-md)">
            <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.08em">Exported Columns</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
              <?php foreach(['Reference No','Title','Description','Category','Status','Priority','Location','Ward','AI Risk Score','Citizen Name','Citizen Email','Submitted At','Last Updated'] as $col): ?>
                <span style="background:var(--primary-bg);color:var(--primary);padding:3px 9px;border-radius:20px;font-family:var(--font-mono);font-size:.67rem;border:1px solid var(--primary-border)"><?=$col?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="margin-top:var(--space-lg)">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
              <?=icon('download')?> Download CSV
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><?=icon('info')?> Export Notes</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <?php foreach([
          'Maximum 5,000 records per export',
          'CSV is UTF-8 encoded and Excel-compatible (BOM included)',
          'Citizen personal data included — handle with care per POPIA regulations',
          'All exports are logged in the system for audit purposes',
          'Leave date fields empty to export all records',
        ] as $note): ?>
          <div style="display:flex;align-items:flex-start;gap:8px;font-size:.8rem;color:var(--text-secondary)">
            <?=icon('check-circle','',14)?> <?=$note?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?=renderFoot()?>
</div></div>
