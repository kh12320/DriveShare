<?php
require_once __DIR__ . '/includes/middleware.php';
require_once __DIR__ . '/classes/CarModel.php';

$user = requireAuth('customer');
$carModel = new CarModel();

$carId = $_GET['id'] ?? '';
$car = $carId ? $carModel->getCar($carId) : null;

if (!$car) {
    header('Location: /dashboard/customer.php?msg=Car+not+found&type=danger');
    exit;
}

$images = $car['images'];
$pageTitle = $car['name'];

$sidebarContent = <<<HTML
<span class="sidebar-nav-label">Navigation</span>
<a class="sidebar-nav-item" href="/dashboard/customer.php"><i class="bi bi-arrow-left"></i> Back to Browse</a>
<a class="sidebar-nav-item" href="/dashboard/customer.php?tab=rides"><i class="bi bi-calendar-check"></i> My Rides</a>
<hr class="sidebar-divider">
<div style="padding:0.8rem;background:rgba(108,99,255,0.1);border:1px solid rgba(108,99,255,0.2);border-radius:12px;">
    <div style="font-size:0.8rem;font-weight:600;color:rgba(255,255,255,0.5);margin-bottom:0.5rem">Quick Info</div>
    <div style="font-size:0.85rem;color:#fff;margin-bottom:4px"><i class="bi bi-car-front" style="color:var(--primary)"></i> {$car['type']}</div>
    <div style="font-size:0.85rem;color:#fff;margin-bottom:4px"><i class="bi bi-people-fill" style="color:var(--primary)"></i> {$car['capacity']} seats</div>
    <div style="font-size:1.1rem;font-weight:800;color:var(--primary);margin-top:8px">₹{$car['price_24h']}<span style="font-size:0.75rem;font-weight:400;color:rgba(255,255,255,0.4)">/day</span></div>
</div>
HTML;

include __DIR__ . '/includes/dashboard_header.php';
?>

<div style="max-width:1100px">
    <a href="/dashboard/customer.php" class="d-inline-flex align-items-center gap-2 mb-3"
        style="font-size:0.88rem;color:rgba(255,255,255,0.5);text-decoration:none;transition:color 0.2s"
        onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">
        <i class="bi bi-arrow-left"></i> Back to all cars
    </a>

    <div class="row g-4">
        <!-- LEFT: Gallery + Info -->
        <div class="col-lg-7">
            <!-- Photo Gallery -->
            <?php if (!empty($images)): ?>
                <div class="car-gallery mb-4">
                    <div class="gallery-main" id="mainImgWrap">
                        <img src="<?= htmlspecialchars($images[0]['image_url']) ?>" id="mainImg"
                            alt="<?= htmlspecialchars($car['name']) ?>">
                    </div>
                    <?php if (count($images) > 1): ?>
                        <?php foreach (array_slice($images, 1, 4) as $img): ?>
                            <div class="thumb" onclick="setMain('<?= htmlspecialchars($img['image_url']) ?>')">
                                <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div
                    style="height:320px;background:linear-gradient(135deg,#13122a,#1a1929);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem">
                    <div style="text-align:center;color:rgba(255,255,255,0.15)">
                        <i class="bi bi-car-front" style="font-size:4rem"></i>
                        <p style="margin:0.5rem 0 0;font-size:0.9rem">No photos available</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Car Info -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3 class="dash-card-title" style="font-size:1.2rem"><i class="bi bi-info-circle-fill"></i> Car
                        Details</h3>
                    <span class="status-badge status-available">Available</span>
                </div>
                <div class="dash-card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small style="color:rgba(255,255,255,0.4);display:block;margin-bottom:4px">Car Name</small>
                            <strong style="color:#fff"><?= htmlspecialchars($car['name']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small style="color:rgba(255,255,255,0.4);display:block;margin-bottom:4px">Type</small>
                            <strong style="color:#fff"><?= htmlspecialchars($car['type']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small style="color:rgba(255,255,255,0.4);display:block;margin-bottom:4px">Seating
                                Capacity</small>
                            <strong style="color:#fff"><?= $car['capacity'] ?> Seats</strong>
                        </div>
                        <div class="col-6">
                            <small style="color:rgba(255,255,255,0.4);display:block;margin-bottom:4px">Price per
                                Day</small>
                            <strong
                                style="color:var(--primary);font-size:1.1rem">₹<?= number_format($car['price_24h'], 0) ?></strong>
                        </div>
                        <div class="col-12">
                            <small style="color:rgba(255,255,255,0.4);display:block;margin-bottom:4px">Owner</small>
                            <strong style="color:#fff"><i class="bi bi-person-circle me-1"
                                    style="color:var(--primary)"></i>
                                <?= htmlspecialchars($car['owner_name']) ?>
                            </strong>
                        </div>
                        <?php if ($car['description']): ?>
                            <div class="col-12">
                                <small
                                    style="color:rgba(255,255,255,0.4);display:block;margin-bottom:4px">Description</small>
                                <p style="color:rgba(255,255,255,0.7);font-size:0.9rem;line-height:1.6;margin:0">
                                    <?= nl2br(htmlspecialchars($car['description'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Booking Form -->
        <div class="col-lg-5">
            <div class="dash-card" style="position:sticky;top:calc(var(--topbar-height) + 20px)">
                <div class="dash-card-header">
                    <h4 class="dash-card-title"><i class="bi bi-calendar-check-fill"></i> Book This Car</h4>
                </div>
                <div class="dash-card-body">
                    <form id="bookingForm" method="POST" action="/api/booking.php?action=create" novalidate>
                        <input type="hidden" name="car_id" value="<?= $car['id'] ?>">

                        <!-- Start Date + Time -->
                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="dash-form-label">Start Date <span
                                        style="color:var(--accent)">*</span></label>
                                <input type="date" id="startDate" name="start_date" class="dash-input"
                                    min="<?= date('Y-m-d') ?>" required onchange="calcPrice()">
                            </div>
                            <div class="col-5">
                                <label class="dash-form-label">Start Time <span
                                        style="color:var(--accent)">*</span></label>
                                <input type="time" id="startTime" name="start_time" class="dash-input" value="10:00"
                                    required onchange="calcPrice()">
                            </div>
                        </div>

                        <!-- End Date + Time -->
                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="dash-form-label">End Date <span
                                        style="color:var(--accent)">*</span></label>
                                <input type="date" id="endDate" name="end_date" class="dash-input"
                                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="calcPrice()">
                            </div>
                            <div class="col-5">
                                <label class="dash-form-label">End Time <span
                                        style="color:var(--accent)">*</span></label>
                                <input type="time" id="endTime" name="end_time" class="dash-input" value="10:00"
                                    required onchange="calcPrice()">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="dash-form-label">Your Name <span style="color:var(--accent)">*</span></label>
                            <input type="text" name="customer_name" class="dash-input"
                                value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="mt-2">
                            <label class="dash-form-label">Contact Number <span
                                    style="color:var(--accent)">*</span></label>
                            <input type="tel" name="customer_contact" class="dash-input"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+91 9876543210"
                                required>
                        </div>

                        <!-- Live Price Calculator -->
                        <div class="price-calc-box" id="priceBox" style="display:none">
                            <div class="price-calc-row">
                                <span>Price per day</span>
                                <span>₹<?= number_format($car['price_24h'], 0) ?></span>
                            </div>
                            <div class="price-calc-row">
                                <span>Duration</span>
                                <span id="calcDays">—</span>
                            </div>
                            <div class="price-calc-total">
                                <span class="label">Total Estimate</span>
                                <span class="amount" id="calcTotal">₹0</span>
                            </div>
                        </div>

                        <div id="bookingError"
                            style="background:rgba(255,107,107,0.12);border:1px solid rgba(255,107,107,0.25);color:#ff8f8f;border-radius:10px;padding:0.7rem 1rem;font-size:0.85rem;margin-top:0.8rem;display:none">
                        </div>

                        <button type="submit" class="btn-dash-primary w-100 mt-3" id="bookBtn">
                            <i class="bi bi-send-fill"></i>
                            <span id="bookBtnText">Request Booking</span>
                            <span class="spinner-border spinner-border-sm d-none" id="bookSpinner"></span>
                        </button>

                        <p style="font-size:0.78rem;color:rgba(255,255,255,0.3);text-align:center;margin:0.7rem 0 0">
                            <i class="bi bi-shield-check me-1" style="color:var(--primary)"></i>
                            Booking will be pending until the owner approves.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>

<script>
    const pricePerDay = <?= (float) $car['price_24h'] ?>;

    function setMain(url) {
        document.getElementById('mainImg').src = url;
    }

    function calcPrice() {
        const startDate = document.getElementById('startDate').value;
        const startTime = document.getElementById('startTime').value || '10:00';
        const endDate = document.getElementById('endDate').value;
        const endTime = document.getElementById('endTime').value || '10:00';
        const box = document.getElementById('priceBox');

        if (!startDate || !endDate) { box.style.display = 'none'; return; }

        const start = new Date(startDate + 'T' + startTime + ':00');
        const end = new Date(endDate + 'T' + endTime + ':00');

        const diffMs = end - start;
        const diffHours = diffMs / (1000 * 60 * 60);

        if (diffHours <= 0) {
            box.style.display = 'none';
            return;
        }

        // Calculate: charged per day, partial days rounded up to nearest half-day
        const days = diffHours / 24;
        const total = Math.ceil(days * 2) / 2 * pricePerDay; // round to nearest 0.5 day

        // Human-readable duration string
        const fullDays = Math.floor(diffHours / 24);
        const remHours = Math.round(diffHours % 24);
        let durationStr = '';
        if (fullDays > 0) durationStr += fullDays + ' day' + (fullDays > 1 ? 's' : '');
        if (remHours > 0) durationStr += (durationStr ? ' ' : '') + remHours + ' hr' + (remHours > 1 ? 's' : '');

        document.getElementById('calcDays').textContent = durationStr || '< 1 hr';
        document.getElementById('calcTotal').textContent = '₹' + Math.ceil(total).toLocaleString('en-IN');
        box.style.display = '';
    }

    document.getElementById('bookingForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const err = document.getElementById('bookingError');
        const btn = document.getElementById('bookBtn');
        const spin = document.getElementById('bookSpinner');
        const txt = document.getElementById('bookBtnText');

        err.style.display = 'none';

        const startDate = document.getElementById('startDate').value;
        const startTime = document.getElementById('startTime').value || '10:00';
        const endDate = document.getElementById('endDate').value;
        const endTime = document.getElementById('endTime').value || '10:00';

        if (!startDate || !endDate) {
            err.textContent = 'Please select start and end dates.';
            err.style.display = '';
            return;
        }

        const start = new Date(startDate + 'T' + startTime);
        const end = new Date(endDate + 'T' + endTime);

        if (end <= start) {
            err.textContent = 'End date/time must be after start date/time.';
            err.style.display = '';
            return;
        }

        btn.disabled = true;
        spin.classList.remove('d-none');
        txt.textContent = 'Sending...';

        const fd = new FormData(this);
        // Append combined date+time strings for the API
        fd.set('start_date', startDate);
        fd.set('end_date', endDate);

        try {
            const res = await fetch(this.action, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                window.location.href = '/dashboard/customer.php?tab=rides&msg=' +
                    encodeURIComponent('Booking submitted! Awaiting owner approval.') + '&type=success';
            } else {
                err.textContent = data.message;
                err.style.display = '';
            }
        } catch (_) {
            err.textContent = 'Connection error. Please try again.';
            err.style.display = '';
        } finally {
            btn.disabled = false;
            spin.classList.add('d-none');
            txt.textContent = 'Request Booking';
        }
    });
</script>