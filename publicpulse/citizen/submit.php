<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('citizen');
$csrf = csrfToken();
$user = currentUser();

$error   = '';
$success = '';
$formData= [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrf(postVal('csrf_token'))) {
        $error='Security token expired. Please try again.';
    } else {
        $title      = postVal('title');
        $desc       = postVal('description');
        $catId      = (int)postVal('category_id');
        $location   = postVal('location');
        $ward       = postVal('ward');
        $lat        = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
        $lng        = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        $formData   = compact('title','desc','catId','location','ward');
        $errs=[];

        if (mb_strlen($title)<10)        $errs[]='Title must be at least 10 characters.';
        if (mb_strlen($title)>255)       $errs[]='Title cannot exceed 255 characters.';
        if (mb_strlen($desc)<20)         $errs[]='Description must be at least 20 characters.';
        if (!$catId)                     $errs[]='Please select a category.';
        if ($catId && !Database::fetchOne('SELECT id FROM categories WHERE id=? AND is_active=1',[$catId]))
            $errs[]='Invalid category selected.';

        $imagePath=null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error']===UPLOAD_ERR_OK) {
            $imagePath=uploadImage($_FILES['image'],'complaint');
            if (!$imagePath) $errs[]='Image upload failed. Allowed: JPG,PNG,WebP. Max 5MB.';
        }

        if (empty($errs)) {
            $priority='medium';
            $cat=Database::fetchOne('SELECT slug FROM categories WHERE id=?',[$catId]);
            if (in_array($cat['slug']??'',['water','safety','health'],true)) $priority='high';

            $refNo=generateReference();
            Database::execute("INSERT INTO complaints (reference_no,user_id,category_id,title,description,location,latitude,longitude,ward,priority,image_path)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [$refNo,$user['id'],$catId,$title,$desc,$location,$lat,$lng,$ward,$priority,$imagePath]);

            $newId=(int)Database::fetchScalar('SELECT LAST_INSERT_ID()');
            Database::execute('INSERT INTO status_logs (complaint_id,changed_by,new_status,note) VALUES (?,?,?,?)',
                [$newId,$user['id'],'submitted','Complaint submitted by citizen.']);

            // Notify admins
            $admins=Database::fetchAll("SELECT id FROM users WHERE role='admin' AND is_active=1");
            foreach ($admins as $a) {
                Database::execute('INSERT INTO notifications (user_id,complaint_id,type,title,message) VALUES (?,?,?,?,?)',
                    [$a['id'],$newId,'new_complaint',"New Complaint: {$refNo}","A new {$priority} priority complaint has been submitted: {$title}"]);
            }
            $success=$refNo; $formData=[];
        } else { $error=implode('<br>',$errs); }
    }
}

$categories=Database::fetchAll('SELECT id,name,icon,color,description FROM categories WHERE is_active=1 ORDER BY name');

echo renderHead('Submit Complaint');
echo '<div class="app-shell">';
echo renderSidebar('citizen_submit',$user);
echo '<div class="main-content">';
echo renderTopbar('Submit a Complaint','Report a public service issue in your area');
?>
<div class="page-content">
  <?= flashMsg() ?>
  <div style="max-width:800px;margin:0 auto">

    <?php if ($success): ?>
      <div style="text-align:center;padding:var(--space-2xl) var(--space-lg)">
        <div style="width:72px;height:72px;background:var(--success-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-lg);color:var(--success)">
          <?= icon('check-circle','',36) ?>
        </div>
        <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:8px">Complaint Submitted!</h2>
        <p style="color:var(--text-secondary);margin-bottom:var(--space-lg)">Your complaint has been received and will be reviewed shortly.</p>
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-lg);padding:var(--space-lg);display:inline-block;margin-bottom:var(--space-xl)">
          <div style="font-family:var(--font-mono);font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:6px">Your Reference Number</div>
          <div style="font-size:1.8rem;font-weight:800;font-family:var(--font-mono);color:var(--primary);letter-spacing:.05em"><?= htmlspecialchars($success) ?></div>
          <div style="font-size:.76rem;color:var(--text-muted);margin-top:4px">Save this number to track your complaint</div>
        </div>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
          <a href="<?= APP_URL ?>/citizen/complaints.php" class="btn btn-primary"><?= icon('list') ?> View My Complaints</a>
          <a href="<?= APP_URL ?>/citizen/submit.php" class="btn btn-secondary"><?= icon('plus') ?> Submit Another</a>
        </div>
      </div>
    <?php else: ?>
      <div class="page-header">
        <div><div class="page-title">Submit a Complaint</div><div class="page-subtitle">Report infrastructure or service issues in your community</div></div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= icon('x-circle') ?><div><?= $error ?></div></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="complaintForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="latitude"   id="hiddenLat">
        <input type="hidden" name="longitude"  id="hiddenLng">

        <!-- Step 1: Category -->
        <div class="card" style="margin-bottom:var(--space-lg)">
          <div class="card-header">
            <div class="card-title"><?= icon('tag') ?> 1. Select Category <span style="color:var(--danger)">*</span></div>
          </div>
          <div class="card-body">
            <div class="cat-grid">
              <?php foreach ($categories as $cat): ?>
                <label class="cat-option">
                  <input type="radio" name="category_id" value="<?= $cat['id'] ?>"
                         <?= (int)($formData['catId']??0)===$cat['id']?'checked':'' ?> required>
                  <div class="cat-card">
                    <div style="color:<?= htmlspecialchars($cat['color']) ?>"><?= icon($cat['icon']??'tag','',26) ?></div>
                    <div class="cat-card-name"><?= htmlspecialchars($cat['name']) ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Step 2: Details -->
        <div class="card" style="margin-bottom:var(--space-lg)">
          <div class="card-header"><div class="card-title"><?= icon('file-text') ?> 2. Complaint Details</div></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:var(--space-md)">
            <div class="form-group" style="margin:0">
              <label class="form-label required">Title</label>
              <input type="text" name="title" class="form-control" data-maxlength="255"
                     placeholder="Brief, specific description of the problem"
                     value="<?= htmlspecialchars($formData['title']??'') ?>" required>
              <div class="form-hint">Be specific — e.g. "Burst water pipe flooding Khumalo Street" is better than "Water problem"</div>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label required">Description</label>
              <textarea name="description" class="form-control" rows="5" data-maxlength="2000"
                        placeholder="Describe the problem in detail. Include: how long it has been happening, how many people are affected, any safety risks, what you have already tried..."
                        required><?= htmlspecialchars($formData['desc']??'') ?></textarea>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Photo Evidence <span style="color:var(--text-muted);font-weight:400">(optional, max 5MB)</span></label>
              <div class="upload-area" id="uploadArea" onclick="document.getElementById('imageInput').click()">
                <?= icon('upload') ?>
                <div style="font-size:.85rem;font-weight:600;color:var(--text-secondary)">Click to upload or drag & drop</div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px">JPG, PNG, WebP accepted</div>
              </div>
              <input type="file" name="image" id="imageInput" accept="image/*" style="display:none">
              <div id="imagePreview" style="display:none;margin-top:10px">
                <img id="previewImg" style="max-width:100%;max-height:220px;object-fit:cover;border-radius:var(--radius-md);border:1px solid var(--border)">
                <button type="button" onclick="clearImageUpload('imageInput','uploadArea','imagePreview')" class="btn btn-secondary btn-sm" style="margin-top:6px">
                  <?= icon('x') ?> Remove Image
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Location -->
        <div class="card" style="margin-bottom:var(--space-xl)">
          <div class="card-header"><div class="card-title"><?= icon('map-pin') ?> 3. Location</div></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:var(--space-md)">
            <div class="grid-2" style="gap:var(--space-md)">
              <div class="form-group" style="margin:0">
                <label class="form-label">Street / Area</label>
                <input type="text" name="location" class="form-control"
                       placeholder="e.g. 5 Khumalo Street, Soshanguve"
                       value="<?= htmlspecialchars($formData['location']??'') ?>">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Ward</label>
                <select name="ward" class="form-control">
                  <option value="">Select your ward...</option>
                  <?php for ($i=1;$i<=25;$i++): ?>
                    <option value="Ward <?= $i ?>" <?= ($formData['ward']??'')==="Ward $i"?'selected':'' ?>>Ward <?= $i ?></option>
                  <?php endfor; ?>
                </select>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
              <button type="button" class="btn btn-secondary btn-sm"
                      onclick="getGPSLocation('hiddenLat','hiddenLng','gpsStatus')">
                <?= icon('crosshairs') ?> Use My GPS Location
              </button>
              <span id="gpsStatus" style="font-size:.76rem;color:var(--text-muted)"></span>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:10px">
          <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" style="flex:1;justify-content:center">
            <?= icon('send') ?> Submit Complaint
          </button>
          <a href="<?= APP_URL ?>/citizen/dashboard.php" class="btn btn-secondary btn-lg"><?= icon('x') ?> Cancel</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?= renderFoot() ?>
<script>
initImageUpload('imageInput','uploadArea','imagePreview','previewImg');
document.getElementById('complaintForm')?.addEventListener('submit',()=>{
  const b=document.getElementById('submitBtn');
  b.innerHTML='<span class="loader"></span> Submitting...';
  b.disabled=true;
});
</script>
</div></div>
