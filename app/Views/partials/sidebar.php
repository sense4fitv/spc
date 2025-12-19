<nav class="sidebar p-4" id="sidebar">
    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-3 d-md-none" onclick="toggleSidebar()">
        <i class="bi bi-x-lg"></i>
    </button>

    <div class="d-flex align-items-center gap-2 mb-5 px-2">
        <div class="d-flex align-items-center justify-content-center text-white rounded bg-dark" style="width: 32px; height: 32px; font-size: 0.9rem;">
            <i class="bi bi-grid-fill"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold tracking-tight text-dark" style="letter-spacing: -0.5px;">ATLAS</h6>
            <small class="text-muted" style="font-size: 0.7rem;">Enterprise ERP</small>
        </div>
    </div>

    <div class="nav flex-column gap-1">
        <?php
        // Ensure menu helper is loaded
        helper('menu');
        $menuItems = getMenuItems();
        if (!empty($menuItems)):
            $firstSection = true;
            foreach ($menuItems as $item):
                // Standalone link (not in a section)
                if (!isset($item['section'])): ?>
                    <a href="<?= esc($item['url'] ?? '#') ?>"
                        class="nav-link">
                        <i class="bi <?= esc($item['icon'] ?? 'bi-circle') ?>"></i> <?= esc($item['label']) ?>
                    </a>
                <?php else: ?>
                    <!-- Section with items -->
                    <?php if (!$firstSection): ?>
                        <div class="mt-4"></div>
                    <?php endif; ?>
                    <small class="text-uppercase text-secondary fw-bold mb-2 ms-3" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                        <?= esc($item['section']) ?>
                    </small>
                    <?php foreach ($item['items'] as $menuItem): ?>
                        <a href="<?= esc($menuItem['url'] ?? '#') ?>"
                            class="nav-link">
                            <i class="bi <?= esc($menuItem['icon'] ?? 'bi-circle') ?>"></i> <?= esc($menuItem['label']) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php $firstSection = false; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback if menuItems not available -->
            <a href="<?= route_to('dashboard') ?>" class="nav-link">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        <?php endif; ?>
    </div>

    <!-- User Profile Link in Sidebar -->
    <div class="mt-auto border-top pt-3">
        <a href="<?= site_url('/profile') ?>" class="text-decoration-none d-block mb-2" style="cursor: pointer;">
            <div class="d-flex align-items-center gap-3 px-2 py-1 rounded hover-bg-light transition">
                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary fw-bold" style="width: 36px; height: 36px; font-size: 0.8rem;"><?= session()->get('first_name')[0] ?><?= session()->get('last_name')[0] ?></div>
                <div style="line-height: 1.2;">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= session()->get('first_name') ?> <?= session()->get('last_name') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?= session()->get('email') ?></div>
                </div>
            </div>
        </a>

        <!-- Logout Button -->
        <a href="<?= site_url('auth/logout') ?>" class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2" style="font-size: 0.8rem; border-radius: 8px;">
            <i class="bi bi-box-arrow-right"></i>
            <span>Deconectare</span>
        </a>
    </div>
</nav>