</main>
</div>

<!-- MODAL ADD REGION -->
<div class="modal fade" id="modalAddRegion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">Regiune Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4">
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Denumire</label>
                    <input type="text" class="form-control" placeholder="Ex: Muntenia Sud">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Director Desemnat</label>
                    <select class="form-select">
                        <option selected>Alege user...</option>
                        <option>Ion Popescu</option>
                        <option>Andrei Ionescu</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-spor-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-spor-primary" onclick="saveRegion()">Creează</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Tom Select JS (Added for searchable dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
    // === STATE & NAVIGATION ===
    const breadcrumbs = [{
        name: 'Dashboard',
        id: 'view-dashboard',
        data: {}
    }];

    let regionModal;
    let usersTable;
    let chartInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('modalAddRegion')) {
            regionModal = new bootstrap.Modal(document.getElementById('modalAddRegion'));
        }
        renderBreadcrumbs();

        // Check for flashdata and show toast notifications
        checkFlashdata();

        // Note: DataTables initialization is now handled in individual views
        // to avoid double initialization errors

        // Initialize Tom Select on Add Task inputs
        new TomSelect('#select-dept', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        new TomSelect('#select-region', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        new TomSelect('#select-subdiv', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        new TomSelect('#select-user', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });

        initChart();
    });

    /**
     * Check for session flashdata and display toast notifications
     */
    function checkFlashdata() {
        <?php
        // Check for success flashdata
        $successMsg = session()->getFlashdata('success');
        if ($successMsg):
        ?>
            showToast(<?= json_encode($successMsg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'success');
        <?php endif; ?>

        <?php
        // Check for error flashdata
        $errorMsg = session()->getFlashdata('error');
        if ($errorMsg):
        ?>
            showToast(<?= json_encode($errorMsg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'error');
        <?php endif; ?>

        <?php
        // Check for errors array flashdata (validation errors)
        $errors = session()->getFlashdata('errors');
        if ($errors && is_array($errors)):
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                if (is_array($messages)) {
                    $errorMessages = array_merge($errorMessages, $messages);
                } else {
                    $errorMessages[] = $messages;
                }
            }
            if (!empty($errorMessages)):
        ?>
                showToast(<?= json_encode(implode('<br>', array_map('htmlspecialchars', $errorMessages)), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'error');
        <?php
            endif;
        endif;
        ?>
    }

    function initChart() {
        const ctx = document.getElementById('regionTasksChart');
        if (ctx) {
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Nord-Vest', 'Sud', 'Est', 'Vest', 'Centru'],
                    datasets: [{
                        label: 'Sarcini Deschise',
                        data: [32, 45, 12, 28, 19],
                        backgroundColor: '#2563eb',
                        borderRadius: 4,
                        barThickness: 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#18181b',
                            titleFont: {
                                family: 'Inter',
                                size: 13
                            },
                            bodyFont: {
                                family: 'Inter',
                                size: 13
                            },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                color: '#64748b'
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                color: '#64748b'
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }

    function navigateTo(viewId, data = {}) {
        const existingIndex = breadcrumbs.findIndex(b => b.id === viewId);
        if (existingIndex >= 0) {
            breadcrumbs.splice(existingIndex + 1);
        } else {
            breadcrumbs.push({
                name: data.name || 'Detail',
                id: viewId,
                data: data
            });
        }

        // Hide all views including user profile and add task
        ['view-dashboard', 'view-region', 'view-contract', 'view-subdivision', 'view-task-details', 'view-users', 'view-add-user', 'view-user-profile', 'view-add-task'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('d-none');
                el.classList.remove('d-block');
            }
        });

        const target = document.getElementById(viewId);
        if (target) {
            target.classList.remove('d-none');
            target.classList.add('d-block');
        }

        // Update Dynamic Titles
        if (viewId === 'view-region' && data.name) document.getElementById('region-title').innerText = data.name;
        if (viewId === 'view-contract' && data.name) document.getElementById('contract-title').innerText = data.name;
        if (viewId === 'view-subdivision' && data.name) document.getElementById('subdiv-title').innerText = data.name;
        if (viewId === 'view-task-details' && data.name) document.getElementById('task-title').innerText = data.name;

        renderBreadcrumbs();
    }

    function renderBreadcrumbs() {
        const container = document.getElementById('breadcrumbs');
        container.innerHTML = '';

        breadcrumbs.forEach((crumb, index) => {
            const li = document.createElement('li');
            li.className = `breadcrumb-item ${index === breadcrumbs.length - 1 ? 'active' : ''}`;

            if (index === breadcrumbs.length - 1) {
                li.innerText = crumb.name;
            } else {
                const a = document.createElement('a');
                a.href = "#";
                a.innerText = crumb.name;
                a.onclick = (e) => {
                    e.preventDefault();
                    navigateTo(crumb.id, {
                        name: crumb.name
                    });
                };
                li.appendChild(a);
            }
            container.appendChild(li);
        });
    }

    function openModal(modalName) {
        if (modalName === 'modalAddRegion') regionModal.show();
    }

    function saveRegion() {
        regionModal.hide();
        showToast('Regiunea a fost adăugată.', 'success');
    }

    function saveUserFromPage() {
        navigateTo('view-users');
        showToast('Utilizator creat cu succes!', 'success');
    }

    function saveTask() {
        navigateTo('view-dashboard');
        showToast('Task creat și asignat cu succes!', 'success');
    }

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        const toastContent = document.getElementById('toastContent');

        // Support HTML in message (for validation errors with <br> tags)
        toastMsg.innerHTML = message;

        // Update icon and styling based on type
        if (type === 'success') {
            toastIcon.className = "bi bi-check-circle-fill";
            toastIcon.style.color = "#10b981"; // green-500
            toastContent.style.borderLeftColor = "#10b981";
        } else {
            toastIcon.className = "bi bi-exclamation-circle-fill";
            toastIcon.style.color = "#ef4444"; // red-500
            toastContent.style.borderLeftColor = "#ef4444";
        }

        // Configure toast options for modern feel
        const toastOptions = {
            autohide: true,
            delay: type === 'error' ? 5000 : 4000, // Errors stay longer
            animation: true
        };

        const toast = new bootstrap.Toast(toastEl, toastOptions);
        toast.show();
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }

    // === GLOBAL SEARCH FUNCTIONALITY ===
    (function() {
        const searchInput = document.getElementById('globalSearchInput');
        const searchResults = document.getElementById('globalSearchResults');
        const searchResultsContent = document.getElementById('globalSearchResultsContent');
        const searchLoading = document.querySelector('.global-search-loading');

        if (!searchInput || !searchResults) return;

        let searchTimeout;
        let currentSearchAbortController = null;

        // Category labels mapping
        const categoryLabels = {
            'regions': 'Sucursale',
            'contracts': 'Contracte',
            'tasks': 'Sarcini',
            'users': 'Utilizatori',
            'departments': 'Departamente',
            'subdivisions': 'Activități'
        };

        // Icon mapping
        const typeIcons = {
            'region': 'bi-geo-alt-fill',
            'contract': 'bi-file-earmark-text',
            'task': 'bi-check2-square',
            'user': 'bi-person',
            'department': 'bi-building',
            'subdivision': 'bi-diagram-3'
        };

        // Handle input focus
        searchInput.addEventListener('focus', function() {
            if (searchInput.value.trim().length >= 2) {
                searchResults.style.display = 'block';
            }
        });

        // Handle input changes with debouncing
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            // Clear previous timeout
            clearTimeout(searchTimeout);

            // Hide results if query is too short
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Cancel previous request if exists
            if (currentSearchAbortController) {
                currentSearchAbortController.abort();
            }
            currentSearchAbortController = new AbortController();

            // Show loading
            searchResults.style.display = 'block';
            searchLoading.style.display = 'block';
            searchResultsContent.innerHTML = '';

            // Debounce search
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Hide results when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchResults.style.display = 'none';
                searchInput.blur();
            }
        });

        function performSearch(query) {
            fetch('<?= site_url('/api/search') ?>?q=' + encodeURIComponent(query), {
                    signal: currentSearchAbortController.signal
                })
                .then(response => response.json())
                .then(data => {
                    searchLoading.style.display = 'none';
                    displaySearchResults(data.results, query);
                })
                .catch(error => {
                    if (error.name !== 'AbortError') {
                        console.error('Search error:', error);
                        searchLoading.style.display = 'none';
                        searchResultsContent.innerHTML = '<div class="global-search-empty">Eroare la căutare</div>';
                    }
                });
        }

        function displaySearchResults(results, query) {
            if (!results || Object.keys(results).length === 0) {
                searchResultsContent.innerHTML = '<div class="global-search-empty">Nu s-au găsit rezultate pentru "' + escapeHtml(query) + '"</div>';
                return;
            }

            let html = '';
            let hasResults = false;

            // Display results by category
            Object.keys(categoryLabels).forEach(category => {
                const categoryResults = results[category] || [];
                if (categoryResults.length === 0) return;

                hasResults = true;
                html += '<div class="global-search-category">';
                html += '<div class="global-search-category-header">' + categoryLabels[category] + '</div>';

                categoryResults.forEach(item => {
                    const iconClass = typeIcons[item.type] || 'bi-circle';
                    const iconStyle = item.color ? `background-color: ${item.color}20; color: ${item.color};` : '';

                    html += '<a href="' + escapeHtml(item.url) + '" class="global-search-result-item">';
                    html += '<div class="icon-wrapper" style="' + iconStyle + '"><i class="bi ' + iconClass + '"></i></div>';
                    html += '<div class="content">';
                    html += '<div class="title">' + highlightMatch(escapeHtml(item.title), query) + '</div>';
                    if (item.subtitle) {
                        html += '<div class="subtitle">' + escapeHtml(item.subtitle) + '</div>';
                    }
                    html += '</div>';
                    html += '</a>';
                });

                html += '</div>';
            });

            if (!hasResults) {
                html = '<div class="global-search-empty">Nu s-au găsit rezultate pentru "' + escapeHtml(query) + '"</div>';
            }

            searchResultsContent.innerHTML = html;
        }

        function highlightMatch(text, query) {
            if (!query) return text;
            const regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
            return text.replace(regex, '<mark style="background-color: #fef08a; padding: 0 2px; border-radius: 2px;">$1</mark>');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // Keyboard shortcut: Ctrl+K / Cmd+K to focus search
        document.addEventListener('keydown', function(e) {
            // Check for Ctrl+K (Windows/Linux) or Cmd+K (Mac)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('globalSearchInput');
                if (searchInput && searchInput.offsetParent !== null) { // Check if visible
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // Show search hint on first focus
        let searchHintShown = false;
        searchInput.addEventListener('focus', function() {
            if (!searchHintShown && this.value.trim().length === 0) {
                // Optional: could show a tooltip here
                searchHintShown = true;
            }
        });
    })();
</script>

<?php if (session()->get('is_logged_in')): ?>
    <!-- Pusher & Notifications (only for authenticated users) -->
    <script src="<?= site_url('assets/js/pusher-client.js') ?>"></script>
    <script src="<?= site_url('assets/js/notifications.js') ?>"></script>
<?php endif; ?>

<!-- Register Service Worker for PWA -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?= site_url('sw.js') ?>')
                .then(function(registration) {
                    console.log('ServiceWorker registration successful');
                })
                .catch(function(err) {
                    // Silent fail - not critical if it doesn't register
                    console.log('ServiceWorker registration failed');
                });
        });
    }
</script>
</body>

</html>