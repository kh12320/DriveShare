<?php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../classes/CarModel.php';
require_once __DIR__ . '/../classes/BookingModel.php';
require_once __DIR__ . '/../classes/ImageUploader.php';

$user = requireAuth('owner');
$carModel = new CarModel();
$bookModel = new BookingModel();

$myCars = $carModel->getOwnerCars($user['id']);
$bookings = $bookModel->getOwnerBookings($user['id']);
$stats = $bookModel->getOwnerStats($user['id']);

$pendingCount = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$totalCars = count($myCars);
$totalEarnings = array_sum(array_column(
    array_filter($bookings, fn($b) => in_array($b['status'], ['customer_confirmed', 'completed'])),
    'total_price'
));
$totalBookings = count($bookings);

$pageTitle = 'Owner Dashboard';
$activeNav = 'dashboard';

$pendingBadge = $pendingCount > 0 ? "<span class='badge-count'>{$pendingCount}</span>" : '';

$sidebarContent = <<<HTML
<span class="sidebar-nav-label">Dashboard</span>
<button class="sidebar-nav-item active" id="nav-overview"   onclick="showSection('overview')"><i class="bi bi-grid-1x2-fill"></i> Overview</button>
<button class="sidebar-nav-item"        id="nav-add-car"    onclick="showSection('add-car')"><i class="bi bi-plus-circle-fill"></i> Add New Car</button>
<button class="sidebar-nav-item"        id="nav-my-cars"    onclick="showSection('my-cars')"><i class="bi bi-car-front-fill"></i> My Cars <span style="margin-left:auto;font-size:0.75rem;color:rgba(255,255,255,0.35)">{$totalCars}</span></button>
<button class="sidebar-nav-item"        id="nav-requests"   onclick="showSection('requests')"><i class="bi bi-bell-fill"></i> Requests {$pendingBadge}</button>
<button class="sidebar-nav-item"        id="nav-history"    onclick="showSection('history')"><i class="bi bi-bar-chart-fill"></i> History & Stats</button>
HTML;

include __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- ============================================================
     OVERVIEW
     ============================================================ -->
<div id="section-overview">
    <div class="section-header">
        <h1 class="section-title"><i class="bi bi-speedometer2"></i> Welcome back,
            <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>!
        </h1>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-car-front-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-val">
                        <?= $totalCars ?>
                    </div>
                    <div class="stat-label">Cars Listed</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="bi bi-calendar-check-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-val">
                        <?= $totalBookings ?>
                    </div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-clock-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-val">
                        <?= $pendingCount ?>
                    </div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
                <div class="stat-info">
                    <div class="stat-val">₹
                        <?= number_format($totalEarnings, 0) ?>
                    </div>
                    <div class="stat-label">Total Earnings</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Requests -->
    <?php if ($pendingCount > 0): ?>
        <div class="dash-card mb-4">
            <div class="dash-card-header">
                <h5 class="dash-card-title"><i class="bi bi-bell-fill"></i> New Booking Requests</h5>
                <button class="btn-dash-secondary" style="padding:0.4rem 0.9rem;font-size:0.82rem"
                    onclick="showSection('requests')">View All</button>
            </div>
            <div class="dash-card-body" style="padding:0">
                <?php foreach (array_filter($bookings, fn($b) => $b['status'] === 'pending') as $req): ?>
                    <div
                        style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                        <div style="width:48px;height:40px;border-radius:8px;overflow:hidden;flex-shrink:0;background:#1a1929">
                            <?php if ($req['car_image']): ?>
                                <img src="<?= htmlspecialchars($req['car_image']) ?>"
                                    style="width:100%;height:100%;object-fit:cover" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <strong style="color:#fff;font-size:0.9rem">
                                <?= htmlspecialchars($req['car_name']) ?>
                            </strong>
                            <div style="font-size:0.78rem;color:rgba(255,255,255,0.4)">
                                <?= htmlspecialchars($req['customer_name']) ?> ·
                                <?= $req['start_date'] ?> →
                                <?= $req['end_date'] ?> · ₹
                                <?= number_format($req['total_price'], 0) ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="/api/booking.php?action=approve">
                                <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="redirect" value="owner">
                                <button type="submit" class="btn-approve"><i class="bi bi-check-lg"></i> Approve</button>
                            </form>
                            <form method="POST" action="/api/booking.php?action=decline"
                                onsubmit="return confirm('Are you sure you want to decline this booking request?')">
                                <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="redirect" value="owner">
                                <button type="submit" class="btn-reject"><i class="bi bi-x-lg"></i> Decline</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- My Cars Quick View -->
    <div class="dash-card">
        <div class="dash-card-header">
            <h5 class="dash-card-title"><i class="bi bi-car-front-fill"></i> My Cars</h5>
            <button class="btn-dash-primary" onclick="showSection('add-car')"><i class="bi bi-plus-lg"></i> Add
                Car</button>
        </div>
        <div class="dash-card-body">
            <?php if (empty($myCars)): ?>
                <div class="empty-state" style="padding:2rem">
                    <i class="bi bi-car-front"></i>
                    <h4>No cars listed yet</h4>
                    <p>Add your first car to start earning!</p>
                    <button class="btn-dash-primary" onclick="showSection('add-car')"><i class="bi bi-plus-lg"></i> Add
                        First Car</button>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach (array_slice($myCars, 0, 4) as $car): ?>
                        <div class="col-md-6 col-xl-3">
                            <div class="dash-card" style="cursor:default">
                                <div style="height:120px;overflow:hidden;border-radius:12px 12px 0 0;background:#1a1929">
                                    <?php if ($car['primary_image']): ?>
                                        <img src="<?= htmlspecialchars($car['primary_image']) ?>"
                                            style="width:100%;height:100%;object-fit:cover" alt="">
                                    <?php else: ?>
                                        <div
                                            style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.15)">
                                            <i class="bi bi-car-front" style="font-size:2rem"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="padding:0.9rem">
                                    <div style="font-size:0.9rem;font-weight:700;color:#fff;margin-bottom:2px">
                                        <?= htmlspecialchars($car['name']) ?>
                                    </div>
                                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4)">
                                        <?= $car['type'] ?> ·
                                        <?= $car['capacity'] ?> seats
                                    </div>
                                    <div style="font-size:1rem;font-weight:700;color:var(--primary);margin-top:6px">₹
                                        <?= number_format($car['price_24h'], 0) ?>/day
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     ADD CAR
     ============================================================ -->
<div id="section-add-car" style="display:none">
    <div class="section-header">
        <h2 class="section-title"><i class="bi bi-plus-circle-fill"></i> Add New Car</h2>
    </div>

    <div class="dash-card">
        <div class="dash-card-body">
            <form id="addCarForm" method="POST" action="/api/cars.php?action=add" enctype="multipart/form-data"
                novalidate>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="dash-form-label">Car Name <span style="color:var(--accent)">*</span></label>
                        <input type="text" name="name" class="dash-input" placeholder="e.g. Tesla Model 3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="dash-form-label">Car Type <span style="color:var(--accent)">*</span></label>
                        <select name="type" class="dash-input dash-select" required>
                            <option value="">Select type...</option>
                            <option>Sedan</option>
                            <option>SUV</option>
                            <option>Hatchback</option>
                            <option>Luxury</option>
                            <option>Sports</option>
                            <option>Electric</option>
                            <option>Van / MPV</option>
                            <option>Pickup Truck</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="dash-form-label">Price per 24 Hours (₹) <span
                                style="color:var(--accent)">*</span></label>
                        <input type="number" name="price_24h" class="dash-input" placeholder="e.g. 2500" min="100"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="dash-form-label">Seating Capacity <span
                                style="color:var(--accent)">*</span></label>
                        <select name="capacity" class="dash-input dash-select" required>
                            <?php for ($i = 2; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>">
                                    <?= $i ?> Seats
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="dash-form-label">Description</label>
                        <textarea name="description" class="dash-input"
                            placeholder="Describe your car — features, fuel type, transmission, AC, music system..."></textarea>
                    </div>

                    <!-- Photo Upload -->
                    <div class="col-12">
                        <label class="dash-form-label">Car Photos <span style="color:var(--accent)">*</span> <small
                                style="color:rgba(255,255,255,0.3)">(JPEG/PNG, max 5MB each, up to 8
                                photos)</small></label>
                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('carImages').click()">
                            <i class="bi bi-cloud-upload-fill"></i>
                            <p>Click or drag & drop photos here</p>
                            <small>First image will be the primary display photo</small>
                        </div>
                        <input type="file" id="carImages" name="images[]" multiple accept="image/*" style="display:none"
                            onchange="previewImages(this)">
                        <div class="image-preview-grid" id="previewGrid"></div>
                    </div>
                </div>

                <div id="addCarError" class="auth-error d-none mt-3"
                    style="background:rgba(255,107,107,0.12);padding:0.75rem 1rem;border-radius:10px;font-size:0.85rem;color:#ff8f8f;border:1px solid rgba(255,107,107,0.25)">
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn-dash-primary" id="addCarBtn">
                        <i class="bi bi-car-front"></i>
                        <span id="addCarBtnText">List My Car</span>
                        <span class="spinner-border spinner-border-sm d-none" id="addCarSpinner"></span>
                    </button>
                    <button type="button" class="btn-dash-secondary" onclick="showSection('my-cars')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MY CARS MANAGEMENT
     ============================================================ -->
<div id="section-my-cars" style="display:none">
    <div class="section-header">
        <h2 class="section-title"><i class="bi bi-car-front-fill"></i> My Cars</h2>
        <button class="btn-dash-primary" onclick="showSection('add-car')"><i class="bi bi-plus-lg"></i> Add New
            Car</button>
    </div>

    <?php if (empty($myCars)): ?>
        <div class="empty-state">
            <i class="bi bi-car-front"></i>
            <h4>No cars yet</h4>
            <p>Add your first car to start earning!</p>
            <button class="btn-dash-primary" onclick="showSection('add-car')"><i class="bi bi-plus-lg"></i> Add Car</button>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($myCars as $car): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="dash-card" style="cursor:default;height:100%">
                        <div
                            style="height:160px;overflow:hidden;border-radius:14px 14px 0 0;background:#1a1929;position:relative">
                            <?php if ($car['primary_image']): ?>
                                <img src="<?= htmlspecialchars($car['primary_image']) ?>"
                                    style="width:100%;height:100%;object-fit:cover" alt="">
                            <?php else: ?>
                                <div
                                    style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;color:rgba(255,255,255,0.15);gap:6px">
                                    <i class="bi bi-car-front" style="font-size:2.5rem"></i>
                                    <small>No photos</small>
                                </div>
                            <?php endif; ?>
                            <span
                                style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.6);padding:3px 8px;border-radius:100px;font-size:0.7rem;color:rgba(255,255,255,0.7)">
                                <i class="bi bi-images"></i>
                                <?= $car['image_count'] ?> photo
                                <?= $car['image_count'] != 1 ? 's' : '' ?>
                            </span>
                            <span class="status-badge status-<?= $car['status'] ?>" style="position:absolute;top:8px;left:8px">
                                <?= ucfirst($car['status']) ?>
                            </span>
                        </div>
                        <div class="dash-card-body">
                            <div style="font-size:1rem;font-weight:700;color:#fff">
                                <?= htmlspecialchars($car['name']) ?>
                            </div>
                            <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-bottom:8px">
                                <?= $car['type'] ?> ·
                                <?= $car['capacity'] ?> seats
                            </div>
                            <div style="font-size:1.1rem;font-weight:800;color:var(--primary);margin-bottom:1rem">₹
                                <?= number_format($car['price_24h'], 0) ?>/day
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn-dash-secondary" style="font-size:0.8rem;padding:0.4rem 0.8rem"
                                    onclick="openEditModal('<?= $car['id'] ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <a class="btn-dash-secondary" style="font-size:0.8rem;padding:0.4rem 0.8rem"
                                    href="/manage-car-photos.php?car_id=<?= $car['id'] ?>">
                                    <i class="bi bi-images"></i> Photos
                                </a>
                                <form method="POST" action="/api/cars.php?action=delete"
                                    onsubmit="return confirm('Delete this car? All bookings will also be removed.')"
                                    style="display:inline">
                                    <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                    <button type="submit" class="btn-reject" style="font-size:0.8rem;padding:0.4rem 0.8rem"><i
                                            class="bi bi-trash"></i> Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     REQUESTS / INQUIRIES
     ============================================================ -->
<div id="section-requests" style="display:none">
    <div class="section-header">
        <h2 class="section-title"><i class="bi bi-bell-fill"></i> Booking Requests</h2>
    </div>

    <div class="dash-tabs">
        <button class="dash-tab-btn active" onclick="filterReqs('all', this)"><i class="bi bi-list-ul"></i> All</button>
        <button class="dash-tab-btn" onclick="filterReqs('pending', this)"><i class="bi bi-hourglass"></i>
            Pending</button>
        <button class="dash-tab-btn" onclick="filterReqs('owner_approved', this)"><i class="bi bi-check-circle"></i>
            Approved</button>
        <button class="dash-tab-btn" onclick="filterReqs('customer_confirmed', this)"><i class="bi bi-check2-all"></i>
            Confirmed</button>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h4>No booking requests yet</h4>
            <p>When customers book your cars, requests will appear here.</p>
        </div>
    <?php else: ?>
        <div id="reqsContainer">
            <?php foreach ($bookings as $bk): ?>
                <div class="req-card" data-status="<?= $bk['status'] ?>">
                    <div class="dash-card mb-3">
                        <div class="dash-card-body">
                            <div class="d-flex align-items-start gap-3 flex-wrap">
                                <!-- Car Image -->
                                <div
                                    style="width:80px;height:64px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#1a1929">
                                    <?php if ($bk['car_image']): ?>
                                        <img src="<?= htmlspecialchars($bk['car_image']) ?>"
                                            style="width:100%;height:100%;object-fit:cover" alt="">
                                    <?php endif; ?>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <strong style="color:#fff;font-size:0.95rem">
                                                <?= htmlspecialchars($bk['car_name']) ?>
                                            </strong>
                                            <div style="font-size:0.8rem;color:rgba(255,255,255,0.4)">
                                                <i class="bi bi-person-fill"></i>
                                                <?= htmlspecialchars($bk['customer_name']) ?> &nbsp;·&nbsp;
                                                <i class="bi bi-telephone-fill"></i>
                                                <?= htmlspecialchars($bk['customer_contact']) ?>
                                            </div>
                                            <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:4px">
                                                <i class="bi bi-calendar2"></i>
                                                <?= $bk['start_date'] ?> →
                                                <?= $bk['end_date'] ?> &nbsp;·&nbsp;
                                                <i class="bi bi-moon-stars"></i>
                                                <?= $bk['total_days'] ?> day
                                                <?= $bk['total_days'] > 1 ? 's' : '' ?> &nbsp;·&nbsp;
                                                <strong style="color:var(--accent-green)">₹
                                                    <?= number_format($bk['total_price'], 0) ?>
                                                </strong>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?= $bk['status'] ?>">
                                            <?= str_replace('_', ' ', ucfirst($bk['status'])) ?>
                                        </span>
                                    </div>

                                    <?php if ($bk['status'] === 'pending'): ?>
                                        <div class="mt-2 d-flex gap-2">
                                            <form method="POST" action="/api/booking.php?action=approve">
                                                <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                                <input type="hidden" name="redirect" value="owner">
                                                <button type="submit" class="btn-approve"><i class="bi bi-check-lg"></i>
                                                    Approve</button>
                                            </form>
                                            <form method="POST" action="/api/booking.php?action=decline"
                                                onsubmit="return confirm('Are you sure you want to decline this booking request?')">
                                                <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                                <input type="hidden" name="redirect" value="owner">
                                                <button type="submit" class="btn-reject"><i class="bi bi-x-lg"></i>
                                                    Decline</button>
                                            </form>
                                        </div>
                                    <?php elseif ($bk['status'] === 'owner_approved'): ?>
                                        <div style="margin-top:8px;font-size:0.82rem;color:rgba(255,255,255,0.4)">
                                            <i class="bi bi-info-circle" style="color:var(--primary)"></i> Waiting for customer to
                                            confirm via WhatsApp.
                                        </div>
                                    <?php elseif ($bk['status'] === 'customer_confirmed'): ?>
                                        <div style="margin-top:8px;font-size:0.82rem;color:var(--accent-green)">
                                            <i class="bi bi-check-all"></i> Customer confirmed! Awaiting vehicle handover.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     HISTORY & STATS
     ============================================================ -->
<div id="section-history" style="display:none">
    <div class="section-header">
        <h2 class="section-title"><i class="bi bi-bar-chart-fill"></i> Rental History & Stats</h2>
    </div>

    <?php if (empty($stats)): ?>
        <div class="empty-state">
            <i class="bi bi-bar-chart"></i>
            <h4>No stats yet</h4>
            <p>Add cars and get bookings to see your stats here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($stats as $s): ?>
            <div class="history-bar">
                <div class="history-bar-icon"><i class="bi bi-car-front-fill"></i></div>
                <div class="flex-grow-1">
                    <div style="font-weight:600;color:#fff;font-size:0.95rem">
                        <?= htmlspecialchars($s['car_name']) ?>
                    </div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4)">
                        <?= $s['total_bookings'] ?> total bookings &nbsp;·&nbsp;
                        <?= $s['total_days_rented'] ?? 0 ?> days rented &nbsp;·&nbsp;
                        Revenue: <strong style="color:var(--accent-green)">₹
                            <?= number_format($s['total_revenue'] ?? 0, 0) ?>
                        </strong>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.2rem;font-weight:800;color:var(--primary)">₹
                        <?= number_format($s['total_revenue'] ?? 0, 0) ?>
                    </div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.35)">
                        <?= $s['total_bookings'] ?> bookings
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ============================================================
     EDIT CAR MODAL
     ============================================================ -->
<div class="modal fade" id="editCarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#1A1929;border:1px solid rgba(255,255,255,0.1);border-radius:16px">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.08)">
                <h5 class="modal-title" style="color:#fff"><i class="bi bi-pencil me-2"
                        style="color:var(--primary)"></i>Edit Car</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editCarModalBody">
                <div class="text-center py-4">
                    <div class="loader-ring" style="margin:auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
    function showSection(name) {
        const sections = ['overview', 'add-car', 'my-cars', 'requests', 'history'];
        sections.forEach(s => {
            document.getElementById('section-' + s).style.display = (s === name) ? '' : 'none';
            const nav = document.getElementById('nav-' + s);
            if (nav) nav.classList.toggle('active', s === name);
        });
    }

    function filterReqs(status, btn) {
        document.querySelectorAll('.dash-tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.req-card').forEach(card => {
            card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
        });
    }

    // Add car form AJAX
    document.getElementById('addCarForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const errEl = document.getElementById('addCarError');
        errEl.classList.add('d-none');

        const btn = document.getElementById('addCarBtn');
        const spinner = document.getElementById('addCarSpinner');
        const btnText = document.getElementById('addCarBtnText');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.textContent = 'Uploading...';

        const fd = new FormData(this);
        try {
            const res = await fetch(this.action, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                window.location.href = '/dashboard/owner.php?tab=my-cars&msg=' + encodeURIComponent('Car listed successfully!') + '&type=success';
            } else {
                errEl.textContent = data.message;
                errEl.classList.remove('d-none');
            }
        } catch (err) {
            errEl.textContent = 'Connection error. Please try again.';
            errEl.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            btnText.textContent = 'List My Car';
        }
    });

    // Image preview
    let selectedFiles = [];
    function previewImages(input) {
        const grid = document.getElementById('previewGrid');
        grid.innerHTML = '';
        selectedFiles = Array.from(input.files).slice(0, 8);
        selectedFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.createElement('div');
                wrap.className = 'preview-img-wrap';
                wrap.innerHTML = `<img src="${e.target.result}" alt="preview">
                <button type="button" class="remove-img-btn" onclick="removePreviewImg(${idx})"><i class="bi bi-x"></i></button>
                ${idx === 0 ? '<span style="position:absolute;bottom:4px;left:4px;background:var(--primary);color:#fff;font-size:0.6rem;padding:2px 6px;border-radius:4px">PRIMARY</span>' : ''}`;
                grid.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    }

    function removePreviewImg(idx) {
        selectedFiles.splice(idx, 1);
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        document.getElementById('carImages').files = dt.files;
        previewImages(document.getElementById('carImages'));
    }

    // Drag & drop on upload zone
    const zone = document.getElementById('uploadZone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const inp = document.getElementById('carImages');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        inp.files = dt.files;
        previewImages(inp);
    });

    // Open edit modal
    async function openEditModal(carId) {
        const modal = new bootstrap.Modal(document.getElementById('editCarModal'));
        modal.show();
        document.getElementById('editCarModalBody').innerHTML = '<div class="text-center py-4"><div class="loader-ring" style="margin:auto"></div></div>';
        try {
            const res = await fetch('/api/cars.php?action=get&car_id=' + carId);
            const data = await res.json();
            if (data.success) {
                const c = data.car;
                document.getElementById('editCarModalBody').innerHTML = `
            <form method="POST" action="/api/cars.php?action=update" onsubmit="return true">
                <input type="hidden" name="car_id" value="${c.id}">
                <div class="mb-3"><label class="dash-form-label">Car Name</label>
                <input type="text" name="name" class="dash-input" value="${escHtml(c.name)}" required></div>
                <div class="row g-2">
                    <div class="col-6"><label class="dash-form-label">Type</label>
                    <select name="type" class="dash-input dash-select">
                        ${['Sedan', 'SUV', 'Hatchback', 'Luxury', 'Sports', 'Electric', 'Van / MPV', 'Pickup Truck'].map(t => `<option${t === c.type ? ' selected' : ''}>${t}</option>`).join('')}
                    </select></div>
                    <div class="col-6"><label class="dash-form-label">Price/Day (₹)</label>
                    <input type="number" name="price_24h" class="dash-input" value="${c.price_24h}" required></div>
                    <div class="col-6"><label class="dash-form-label">Capacity</label>
                    <select name="capacity" class="dash-input dash-select">
                        ${Array.from({ length: 11 }, (_, i) => i + 2).map(n => `<option${n == c.capacity ? ' selected' : ''}>${n} Seats</option>`).join('')}
                    </select></div>
                    <div class="col-6"><label class="dash-form-label">Status</label>
                    <select name="status" class="dash-input dash-select">
                        <option value="available"${c.status === 'available' ? ' selected' : ''}>Available</option>
                        <option value="unavailable"${c.status === 'unavailable' ? ' selected' : ''}>Unavailable</option>
                    </select></div>
                </div>
                <div class="mt-2"><label class="dash-form-label">Description</label>
                <textarea name="description" class="dash-input">${escHtml(c.description || '')}</textarea></div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn-dash-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>`;
            }
        } catch (_) {
            document.getElementById('editCarModalBody').innerHTML = '<p style="color:var(--accent)" class="text-center">Failed to load car details.</p>';
        }
    }

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Open tab from URL ?tab=
    const tabParam = new URLSearchParams(location.search).get('tab');
    if (tabParam) showSection(tabParam);
</script>