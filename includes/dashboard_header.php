<?php
/**
 * Shared Dashboard Layout Header
 * Include at top of every dashboard page
 * Expects: $pageTitle, $user, $activeNav
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($pageTitle ?? 'DriveShare') ?> – DriveShare
    </title>
    <meta name="description" content="DriveShare peer-to-peer car rental dashboard.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <?= $extraHead ?? '' ?>
</head>

<body class="dashboard-body">

    <!-- ===== TOPBAR ===== -->
    <nav class="dashboard-topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <a href="<?= $user['role'] === 'owner' ? '/dashboard/owner.php' : '/dashboard/customer.php' ?>"
                class="brand-link">
                <i class="bi bi-car-front-fill"></i>
                <span>DriveShare</span>
            </a>
        </div>
        <div class="topbar-right">
            <div class="topbar-user dropdown">
                <button class="user-badge dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="user-info d-none d-sm-block">
                        <span class="user-name">
                            <?= htmlspecialchars($user['name']) ?>
                        </span>
                        <span class="user-role">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </div>
                    <i class="bi bi-chevron-down ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dash-dropdown">
                    <li>
                        <h6 class="dropdown-header">
                            <?= htmlspecialchars($user['email']) ?>
                        </h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.1)">
                    </li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" onclick="logout()">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===== SIDEBAR ===== -->
    <div class="dashboard-sidebar" id="dashSidebar">
        <div class="sidebar-inner">
            <?= $sidebarContent ?? '' ?>
        </div>
    </div>

    <!-- ===== OVERLAY ===== -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="dashboard-main" id="dashMain">
        <!-- Flash message -->
        <?php if (!empty($_GET['msg'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'info') ?> alert-dismissible fade show dash-alert"
                role="alert">
                <i
                    class="bi bi-<?= ($_GET['type'] ?? 'info') === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>