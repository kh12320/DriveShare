<?php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../classes/CarModel.php';
require_once __DIR__ . '/../classes/BookingModel.php';

$user = requireAuth('customer');
$carModel = new CarModel();
$bookModel = new BookingModel();

// Filters
$filterType = $_GET['type'] ?? '';
$filterCapacity = (int) ($_GET['capacity'] ?? 0);

$filters = [];
if ($filterType)
    $filters['type'] = $filterType;
if ($filterCapacity)
    $filters['capacity'] = $filterCapacity;

$cars = $carModel->getAllCars($filters);
$carTypes = $carModel->getCarTypes();
$myRides = $bookModel->getCustomerBookings($user['id']);

$pendingCount = count(array_filter($myRides, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($myRides, fn($r) => $r['status'] === 'owner_approved'));

// ✅ FIX 1: Define $badgeHtml BEFORE the sidebar heredoc that uses it
$badgeHtml = '';
if ($approvedCount > 0)
    $badgeHtml = "<span class='badge-count'>{$approvedCount}</span>";

$pageTitle = 'Browse Cars';
$capVal = $filterCapacity ?: 1;

// Build sidebar
$sidebarContent = <<<HTML
<span class="sidebar-nav-label">Navigation</span>
<button class="sidebar-nav-item active" id="nav-browse" onclick="showSection('browse')">
    <i class="bi bi-search"></i> Browse Cars
</button>
<button class="sidebar-nav-item" id="nav-rides" onclick="showSection('rides')">
    <i class="bi bi-calendar-check"></i> My Rides
    {$badgeHtml}
</button>

<hr class="sidebar-divider">
<span class="sidebar-nav-label"><i class="bi bi-funnel"></i> Filter Cars</span>

<div class="sidebar-filter-section">
    <div class="sidebar-filter-title"><i class="bi bi-car-front"></i> Car Type</div>
    <select class="filter-select" id="filterType">
        <option value="">All Types</option>
HTML;

foreach ($carTypes as $type) {
    $sel = ($type === $filterType) ? 'selected' : '';
    $sidebarContent .= "<option value=\"{$type}\" {$sel}>{$type}</option>";
}

$sidebarContent .= <<<HTML
    </select>

    <div class="sidebar-filter-title" style="margin-top:0.8rem"><i class="bi bi-people"></i> Passengers</div>
    <div class="capacity-counter">
        <button class="count-btn" onclick="adjustCapacity(-1)"><i class="bi bi-dash"></i></button>
        <span class="count-val" id="capVal">{$capVal}</span>
        <button class="count-btn" onclick="adjustCapacity(1)"><i class="bi bi-plus"></i></button>
    </div>

    <button class="btn-apply-filter" onclick="applyFilters()">
        <i class="bi bi-funnel-fill me-1"></i> Apply Filters
    </button>
    <button class="btn-apply-filter mt-2" style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6);" onclick="clearFilters()">
        <i class="bi bi-x-circle me-1"></i> Clear
    </button>
</div>

<hr class="sidebar-divider">
<span class="sidebar-nav-label">Quick Rides</span>
HTML;

// Upcoming rides in sidebar
$upcomingRides = array_filter($myRides, fn($r) => in_array($r['status'], ['pending', 'owner_approved', 'customer_confirmed']));
$upcomingRides = array_slice(array_values($upcomingRides), 0, 3);

if (empty($upcomingRides)) {
    $sidebarContent .= '<p style="font-size:0.8rem;color:rgba(255,255,255,0.3);padding:0 0.5rem;">No upcoming rides</p>';
} else {
    foreach ($upcomingRides as $ride) {
        $statusLabel = match ($ride['status']) {
            'pending' => 'Awaiting Approval',
            'owner_approved' => 'Approved! Confirm Now',
            'customer_confirmed' => 'Confirmed',
            default => ucfirst($ride['status'])
        };
        $statusColor = match ($ride['status']) {
            'pending' => '#ffc107',
            'owner_approved' => '#8b83ff',
            default => '#06d6a0'
        };
        $sidebarContent .= <<<HTML
        <div class="upcoming-ride-item" onclick="showSection('rides')" style="cursor:pointer">
            <div class="ride-car-name">{$ride['car_name']}</div>
            <div class="ride-dates"><i class="bi bi-calendar2"></i> {$ride['start_date']} → {$ride['end_date']}</div>
            <span class="ride-status-badge" style="background:rgba(0,0,0,0.3);color:{$statusColor};border:1px solid {$statusColor}33">{$statusLabel}</span>
        </div>
HTML;
    }
}

// ✅ FIX 2: Define $extraScript BEFORE the include so dashboard_footer.php outputs it
$extraScript = <<<JS
<script>
function showSection(name) {
    document.getElementById('section-browse').style.display = name === 'browse' ? '' : 'none';
    document.getElementById('section-rides').style.display  = name === 'rides'  ? '' : 'none';
    document.getElementById('nav-browse').classList.toggle('active', name === 'browse');
    document.getElementById('nav-rides').classList.toggle('active', name === 'rides');
}

let capacity = {$capVal};

function adjustCapacity(delta) {
    capacity = Math.max(1, Math.min(12, capacity + delta));
    document.getElementById('capVal').textContent = capacity;
}

function applyFilters() {
    const type = document.getElementById('filterType').value;
    const params = new URLSearchParams();
    if (type) params.set('type', type);
    if (capacity > 1) params.set('capacity', capacity);
    window.location.href = '/dashboard/customer.php?' + params.toString();
}

function clearFilters() {
    window.location.href = '/dashboard/customer.php';
}

function filterRides(status, btn) {
    document.querySelectorAll('.dash-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.ride-full-card').forEach(card => {
        card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
    });
}

// Open rides tab if ?tab=rides in URL
if (new URLSearchParams(location.search).get('tab') === 'rides') {
    showSection('rides');
}
</script>
JS;

include __DIR__ . '/../includes/dashboard_header.php';

function generateWhatsAppLink(array $ride): string
{
    $phone = preg_replace('/[^0-9]/', '', $ride['owner_phone'] ?? '');
    if (strlen($phone) === 10)
        $phone = '91' . $phone;
    $msg = urlencode(
        "Hello! I'm *{$ride['customer_name']}* and I'd like to confirm my booking.\n\n" .
        "🚗 *Car:* {$ride['car_name']}\n" .
        "📅 *From:* {$ride['start_date']}\n" .
        "📅 *To:* {$ride['end_date']}\n" .
        "⏱️ *Days:* {$ride['total_days']}\n" .
        "💰 *Total:* ₹{$ride['total_price']}\n" .
        "📞 *My Contact:* {$ride['customer_contact']}\n\n" .
        "Please confirm the booking. Thank you! 🙏"
    );
    return "https://wa.me/{$phone}?text={$msg}";
}
?>

<!-- ============================================================
     BROWSE SECTION
     ============================================================ -->
<div id="section-browse">
    <div class="section-header">
        <h1 class="section-title"><i class="bi bi-grid-fill"></i> Available Cars</h1>
        <span style="font-size:0.85rem;color:rgba(255,255,255,0.4)"><?= count($cars) ?> cars found</span>
    </div>

    <?php if (empty($cars)): ?>
        <div class="empty-state">
            <i class="bi bi-car-front"></i>
            <h4>No cars match your filters</h4>
            <p>Try adjusting the filters or <a href="/dashboard/customer.php" style="color:var(--primary)">clear all
                    filters</a></p>
        </div>
    <?php else: ?>
        <div class="cars-grid" id="carsGrid">
            <?php foreach ($cars as $car): ?>
                <?php $img = $car['primary_image'] ?? ''; ?>
                <a class="car-card" href="/car-detail.php?id=<?= $car['id'] ?>">
                    <div class="car-card-img">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($car['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="car-card-no-img">
                                <i class="bi bi-car-front"></i>
                                <span>No Photo</span>
                            </div>
                        <?php endif; ?>
                        <span class="car-type-badge"><?= htmlspecialchars($car['type']) ?></span>
                        <span class="car-capacity-badge"><i class="bi bi-people-fill"></i> <?= $car['capacity'] ?></span>
                    </div>
                    <div class="car-card-body">
                        <div class="car-name"><?= htmlspecialchars($car['name']) ?></div>
                        <div class="car-owner-tag"><i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($car['owner_name']) ?></div>
                        <div class="car-card-footer">
                            <div class="car-price">₹<?= number_format($car['price_24h'], 0) ?> <span>/ day</span></div>
                            <span class="btn-book-now">View &amp; Book</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     MY RIDES SECTION
     ============================================================ -->
<div id="section-rides" style="display:none">
    <div class="section-header">
        <h2 class="section-title"><i class="bi bi-calendar-check-fill"></i> My Rides</h2>
    </div>

    <!-- Status Tabs -->
    <div class="dash-tabs">
        <button class="dash-tab-btn active" onclick="filterRides('all', this)"><i class="bi bi-list-ul"></i>
            All</button>
        <button class="dash-tab-btn" onclick="filterRides('pending', this)"><i class="bi bi-hourglass-split"></i>
            Pending</button>
        <button class="dash-tab-btn" onclick="filterRides('owner_approved', this)"><i class="bi bi-hand-thumbs-up"></i>
            Approved</button>
        <button class="dash-tab-btn" onclick="filterRides('customer_confirmed', this)"><i
                class="bi bi-check2-circle"></i> Confirmed</button>
    </div>

    <?php if (empty($myRides)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h4>No bookings yet</h4>
            <p>Browse available cars and make your first booking!</p>
            <button class="btn-dash-primary" onclick="showSection('browse')"><i class="bi bi-search"></i> Browse
                Cars</button>
        </div>
    <?php else: ?>
        <div id="ridesContainer">
            <?php foreach ($myRides as $ride): ?>
                <div class="ride-full-card" data-status="<?= $ride['status'] ?>">
                    <div class="dash-card mb-3">
                        <div class="dash-card-body">
                            <div class="d-flex align-items-start gap-3 flex-wrap">
                                <!-- Car Image -->
                                <div
                                    style="width:90px;height:70px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#1a1929">
                                    <?php if ($ride['car_image']): ?>
                                        <img src="<?= htmlspecialchars($ride['car_image']) ?>"
                                            style="width:100%;height:100%;object-fit:cover" alt="">
                                    <?php else: ?>
                                        <div
                                            style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.2)">
                                            <i class="bi bi-car-front" style="font-size:1.8rem"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Info -->
                                <div class="flex-grow-1">
                                    <div
                                        style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                                        <div>
                                            <h5 style="font-size:1rem;font-weight:700;color:#fff;margin:0">
                                                <?= htmlspecialchars($ride['car_name']) ?></h5>
                                            <p style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin:2px 0 0">by
                                                <?= htmlspecialchars($ride['owner_name']) ?></p>
                                        </div>
                                        <span class="status-badge status-<?= $ride['status'] ?>">
                                            <?= str_replace('_', ' ', ucfirst($ride['status'])) ?>
                                        </span>
                                    </div>

                                    <div class="row mt-2 g-2" style="font-size:0.82rem;color:rgba(255,255,255,0.5)">
                                        <div class="col-auto"><i class="bi bi-calendar2-week" style="color:var(--primary)"></i>
                                            <?= htmlspecialchars($ride['start_date']) ?> →
                                            <?= htmlspecialchars($ride['end_date']) ?>
                                        </div>
                                        <div class="col-auto"><i class="bi bi-moon-stars"
                                                style="color:var(--accent-yellow)"></i>
                                            <?= $ride['total_days'] ?> day<?= $ride['total_days'] > 1 ? 's' : '' ?>
                                        </div>
                                        <div class="col-auto"><i class="bi bi-currency-rupee"
                                                style="color:var(--accent-green)"></i>
                                            ₹<?= number_format($ride['total_price'], 0) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($ride['status'] === 'owner_approved'): ?>
                                <div class="price-calc-box mt-3">
                                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:0.8rem">
                                        <i class="bi bi-check-circle-fill" style="color:var(--accent-green);font-size:1.2rem"></i>
                                        <strong style="color:var(--accent-green)">Owner approved your booking!</strong>
                                    </div>
                                    <p style="font-size:0.85rem;color:rgba(255,255,255,0.5);margin-bottom:1rem">
                                        Owner's phone: <strong
                                            style="color:#fff"><?= htmlspecialchars($ride['owner_phone'] ?? 'N/A') ?></strong>
                                    </p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?= generateWhatsAppLink($ride) ?>" target="_blank" class="btn-whatsapp">
                                            <i class="bi bi-whatsapp"></i> Confirm via WhatsApp
                                        </a>
                                        <form method="POST" action="/api/booking.php?action=confirm" style="display:inline">
                                            <input type="hidden" name="booking_id" value="<?= $ride['id'] ?>">
                                            <button type="submit" class="btn-dash-primary">
                                                <i class="bi bi-check-lg"></i> Mark as Booked
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php elseif ($ride['status'] === 'pending'): ?>
                                <div style="margin-top:0.8rem">
                                    <form method="POST" action="/api/booking.php?action=cancel"
                                        onsubmit="return confirm('Cancel this booking?')">
                                        <input type="hidden" name="booking_id" value="<?= $ride['id'] ?>">
                                        <button type="submit" class="btn-reject"><i class="bi bi-x-circle"></i> Cancel
                                            Request</button>
                                    </form>
                                </div>
                            <?php elseif ($ride['status'] === 'customer_confirmed'): ?>
                                <div class="mt-2 d-flex align-items-center gap-2"
                                    style="font-size:0.85rem;color:var(--accent-green)">
                                    <i class="bi bi-check-all"></i> Booking confirmed! Have a great ride 🚗
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dashboard_footer.php'; ?>