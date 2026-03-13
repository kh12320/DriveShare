<?php
require_once __DIR__ . '/includes/middleware.php';
require_once __DIR__ . '/classes/CarModel.php';

$user = requireAuth('owner');
$carModel = new CarModel();

$carId = $_GET['car_id'] ?? '';
$car = $carId ? $carModel->getCar($carId) : null;

if (!$car || $car['owner_id'] !== $user['id']) {
    header('Location: /dashboard/owner.php?tab=my-cars&msg=Car+not+found&type=danger');
    exit;
}

$images = $car['images'];
$pageTitle = 'Manage Photos – ' . $car['name'];

$sidebarContent = <<<HTML
<span class="sidebar-nav-label">Navigation</span>
<a class="sidebar-nav-item" href="/dashboard/owner.php?tab=my-cars"><i class="bi bi-arrow-left"></i> Back to My Cars</a>
<hr class="sidebar-divider">
<div style="padding:0.8rem;background:rgba(108,99,255,0.08);border:1px solid rgba(108,99,255,0.15);border-radius:10px;font-size:0.82rem;color:rgba(255,255,255,0.5)">
    <strong style="color:#fff;display:block;margin-bottom:4px">{$car['name']}</strong>
    {$car['type']} · {$car['capacity']} seats<br>₹{$car['price_24h']}/day
</div>
HTML;

include __DIR__ . '/includes/dashboard_header.php';
?>

<div class="section-header">
    <h1 class="section-title"><i class="bi bi-images"></i> Manage Photos</h1>
    <span style="font-size:0.85rem;color:rgba(255,255,255,0.4)">
        <?= htmlspecialchars($car['name']) ?> —
        <?= count($images) ?> photo
        <?= count($images) != 1 ? 's' : '' ?>
    </span>
</div>

<!-- Current Photos -->
<div class="dash-card mb-4">
    <div class="dash-card-header">
        <h5 class="dash-card-title"><i class="bi bi-grid-3x3-gap-fill"></i> Current Photos</h5>
    </div>
    <div class="dash-card-body">
        <?php if (empty($images)): ?>
            <div class="empty-state" style="padding:2rem">
                <i class="bi bi-image-alt"></i>
                <p>No photos yet. Upload some below!</p>
            </div>
        <?php else: ?>
            <div class="image-preview-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
                <?php foreach ($images as $img): ?>
                    <div class="preview-img-wrap" style="aspect-ratio:4/3">
                        <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="">
                        <?php if ($img['is_primary']): ?>
                            <span
                                style="position:absolute;bottom:4px;left:4px;background:var(--primary);color:#fff;font-size:0.6rem;padding:2px 8px;border-radius:4px;font-weight:600">PRIMARY</span>
                        <?php endif; ?>
                        <button class="remove-img-btn" onclick="deleteImage('<?= $img['id'] ?>', this)" title="Remove photo">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload More -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5 class="dash-card-title"><i class="bi bi-cloud-upload-fill"></i> Add More Photos</h5>
    </div>
    <div class="dash-card-body">
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('photoInput').click()">
                <i class="bi bi-cloud-upload-fill"></i>
                <p>Click or drag & drop photos</p>
                <small>JPEG/PNG, max 5MB each</small>
            </div>
            <input type="file" id="photoInput" name="image" multiple accept="image/*" style="display:none"
                onchange="queueFiles(this)">
            <div class="image-preview-grid mt-3" id="newPreviewGrid"></div>
            <div id="uploadStatus" class="mt-2" style="font-size:0.85rem;color:rgba(255,255,255,0.5)"></div>
            <button type="button" class="btn-dash-primary mt-3 d-none" id="uploadBtn" onclick="uploadAll()">
                <i class="bi bi-upload"></i> Upload Photos
                <span class="spinner-border spinner-border-sm d-none" id="uploadSpinner"></span>
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>

<script>
    let filesToUpload = [];

    function queueFiles(input) {
        filesToUpload = Array.from(input.files);
        const grid = document.getElementById('newPreviewGrid');
        grid.innerHTML = '';

        filesToUpload.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.createElement('div');
                wrap.className = 'preview-img-wrap';
                wrap.style.aspectRatio = '4/3';
                wrap.innerHTML = `<img src="${e.target.result}" alt="new"><button type="button" class="remove-img-btn" onclick="removeQueued(${idx})"><i class="bi bi-x"></i></button>`;
                grid.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });

        document.getElementById('uploadBtn').classList.toggle('d-none', filesToUpload.length === 0);
    }

    function removeQueued(idx) {
        filesToUpload.splice(idx, 1);
        const dt = new DataTransfer();
        filesToUpload.forEach(f => dt.items.add(f));
        document.getElementById('photoInput').files = dt.files;
        queueFiles(document.getElementById('photoInput'));
    }

    async function uploadAll() {
        const status = document.getElementById('uploadStatus');
        const spinner = document.getElementById('uploadSpinner');
        const btn = document.getElementById('uploadBtn');
        const carId = '<?= $car['id'] ?>';

        btn.disabled = true;
        spinner.classList.remove('d-none');
        status.textContent = 'Uploading...';

        let success = 0;
        for (const file of filesToUpload) {
            const fd = new FormData();
            fd.append('car_id', carId);
            fd.append('image', file);
            try {
                const res = await fetch('/api/cars.php?action=add_image', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) success++;
            } catch (_) { }
        }

        status.textContent = `${success}/${filesToUpload.length} photo(s) uploaded successfully.`;
        spinner.classList.add('d-none');
        btn.disabled = false;

        if (success > 0) {
            setTimeout(() => location.reload(), 1200);
        }
    }

    // Drag & drop
    const zone = document.getElementById('uploadZone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const inp = document.getElementById('photoInput');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        inp.files = dt.files;
        queueFiles(inp);
    });

    async function deleteImage(imageId, btn) {
        if (!confirm('Remove this photo?')) return;
        btn.disabled = true;
        const fd = new FormData();
        fd.append('image_id', imageId);
        const res = await fetch('/api/cars.php?action=delete_image', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            btn.closest('.preview-img-wrap').remove();
        } else {
            alert('Failed to delete image.');
            btn.disabled = false;
        }
    }
</script>