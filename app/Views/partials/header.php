<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATLAS by SuperCom</title>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?= site_url('manifest.json') ?>">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ATLAS">
    <link rel="apple-touch-icon" href="<?= site_url('assets/icons/icon-180x180.png') ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- DataTables CSS for Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Tom Select CSS (Searchable Dropdowns) -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="<?= site_url('assets/css/styles.css') ?>">

</head>

<body>

    <!-- <div class="roll-announcer text-center" style="background-color: black; color: white; padding: 10px;">
        <p class="mb-0">La multi ani, Romania! 🇷🇴</p>
    </div> -->
    <!-- TOAST NOTIFICATION (Modern Sonner-style) -->
    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1060; pointer-events: none;">
        <div id="liveToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="pointer-events: auto; min-width: 300px; max-width: 500px;">
            <div id="toastContent" class="d-flex align-items-center gap-3 p-3 rounded-3" style="background: white; border-left: 4px solid;">
                <i id="toastIcon" class="bi bi-check-circle-fill" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                <span id="toastMessage" class="fw-medium text-dark flex-grow-1" style="font-size: 0.875rem; line-height: 1.4;"></span>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="toast" aria-label="Close" style="flex-shrink: 0; opacity: 0.5;"></button>
            </div>
        </div>
    </div>

    <!-- SIDEBAR OVERLAY FOR MOBILE -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="d-flex">

        <!-- SIDEBAR -->
        <?= $this->include('partials/sidebar') ?>

        <!-- MAIN CONTENT -->
        <main class="main-content w-100">

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-white border d-md-none shadow-sm p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <!-- BREADCRUMBS -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" id="breadcrumbs">
                            <!-- JS Injected -->
                        </ol>
                    </nav>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Notifications Dropdown -->
                    <?php if (session()->get('is_logged_in')): ?>
                        <div class="dropdown">
                            <button class="btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="notificationDropdownButton">
                                <i class="bi bi-bell"></i>
                                <span class="notification-badge" style="display: none;"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom notification-dropdown-list" style="max-width: 350px;">
                                <li>
                                    <h6 class="dropdown-header small fw-bold text-muted">Notificări Recente</h6>
                                </li>
                                <!-- Notifications will be dynamically loaded here -->
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-center small text-black fw-medium mark-all-read-btn" href="#" style="cursor: pointer;">
                                        Marchează toate ca citite
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Global Search -->
                    <?php if (session()->get('is_logged_in') && session()->get('role') !== 'executant'): ?>
                        <div class="global-search-wrapper d-none d-sm-flex position-relative">
                            <div class="input-group global-search-input-group">
                                <span class="input-group-text global-search-icon">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    type="text"
                                    class="form-control global-search-input"
                                    id="globalSearchInput"
                                    placeholder="Caută orice..."
                                    autocomplete="off">
                            </div>

                            <!-- Search Results Dropdown -->
                            <div id="globalSearchResults" class="global-search-results" style="display: none;">
                                <div class="global-search-loading" style="display: none;">
                                    <div class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="text-muted small mt-2">Căutare...</div>
                                    </div>
                                </div>
                                <div id="globalSearchResultsContent" class="global-search-results-content">
                                    <!-- Results will be injected here -->
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>