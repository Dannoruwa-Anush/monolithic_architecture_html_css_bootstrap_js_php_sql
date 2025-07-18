<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Order Info') ?>
<!-- ######################### [End-header part] ############################################## -->


<?php

//Role-based back to dashboard
$role = $_SESSION['user_info']['role_name'];
?>

<!-- ######################### [End-body/order info part] ##################################### -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="nav flex-column nav-pills" id="v-tabs" role="tablist" aria-orientation="vertical">
                <button onclick="redirectToDashboard()" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To <?php echo ucfirst($role); ?> Dashboard
                </button>
            </div>
        </div>

        <div class="col-md-10">
            <div id="tab-content">
                <div class="container mt-5">
                    <h3>Orders</h3>

                    <div class="card shadow-sm rounded p-4">
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-pills" id="orderTabs" role="tablist">
                            <!-- Tab buttons will be dynamically inserted here -->
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="orderTabContent">
                            <!-- Tab content will be dynamically inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Role-based back to dashboard -->
<script>
    function redirectToDashboard() {
        var role = "<?= $role ?>";

        var dashboardMap = {
            admin: "admin_dashboard",
            manager: "manager_dashboard",
            cashier: "cashier_dashboard",
            customer: "user_dashboard"
        };

        if (dashboardMap[role]) {
            window.location.href = "index.php?page=" + dashboardMap[role];
        } else {
            alert("Dashboard not defined for this role: " + role);
        }
    }
</script>

<script>
    const tabs_btn_name = ['Pending Orders', 'Shipped Orders', 'Delivered Orders', 'Cancelled Orders'];
    const tableFiles = ['pending_orders', 'shipped_orders', 'delivered_orders', 'cancelled_orders'];

    function getCurrentTabIndexFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            // If tab is a number, convert; else try to find index by name
            const tabIndex = parseInt(tabParam) - 1;
            if (!isNaN(tabIndex) && tabIndex >= 0 && tabIndex < tabs_btn_name.length) {
                return tabIndex;
            }
            // fallback: if tabParam is string like 'shipped_orders'
            const idx = tableFiles.indexOf(tabParam);
            if (idx !== -1) return idx;
        }
        return 0; // default to first tab
    }

    function updateURLParams(params) {
        const url = new URL(window.location.href);
        Object.keys(params).forEach(key => {
            if (params[key] === null || params[key] === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, params[key]);
            }
        });
        window.history.replaceState({}, '', url.toString());
    }

    function generateTabs() {
        const tabList = document.querySelector('#orderTabs');
        const tabContent = document.querySelector('#orderTabContent');

        const activeTabIndex = getCurrentTabIndexFromURL();

        tabs_btn_name.forEach((tabName, index) => {
            const tabId = 'tab' + (index + 1);
            const isActiveClass = index === activeTabIndex ? 'active' : '';

            const tabButton = document.createElement('li');
            tabButton.classList.add('nav-item');
            tabButton.setAttribute('role', 'presentation');
            tabButton.innerHTML = `
            <a class="nav-link ${isActiveClass}" id="${tabId}" data-bs-toggle="pill" href="#content${tabId}" role="tab">${tabName}</a>
        `;
            tabList.appendChild(tabButton);

            const tabPane = document.createElement('div');
            tabPane.classList.add('tab-pane', 'fade');
            if (index === activeTabIndex) tabPane.classList.add('show', 'active');
            tabPane.id = 'content' + tabId;
            tabContent.appendChild(tabPane);

            document.getElementById(tabId).addEventListener('click', () => {
                loadTableContent(`#content${tabId}`, `index.php?page=${tableFiles[index]}`);

                // Update URL with selected tab
                updateURLParams({
                    tab: index + 1,
                    search: null,
                    page_num: null
                });
            });
        });

        loadTableContent(`#contenttab${activeTabIndex + 1}`, `index.php?page=${tableFiles[activeTabIndex]}`);
    }

    function loadTableContent(tabContentId, filePath) {
        fetch(filePath)
            .then(response => response.text())
            .then(html => {
                const container = document.querySelector(tabContentId);
                container.innerHTML = html;

                container.querySelectorAll('.view-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const orderId = btn.getAttribute('data-id');
                        window.location.href = `index.php?page=order_view&id=${orderId}`;
                    });
                });

                const searchForm = container.querySelector('#searchForm');
                if (searchForm) {
                    searchForm.addEventListener('submit', e => {
                        e.preventDefault();
                        const formData = new FormData(searchForm);
                        const searchQuery = formData.get('search');

                        const url = new URL(filePath, window.location.origin);
                        const currentPage = url.searchParams.get("page") || "pending_orders";
                        const tabParam = getCurrentTabIndexFromURL() + 1;

                        const newPath = `index.php?page=${currentPage}&search=${encodeURIComponent(searchQuery)}`;
                        loadTableContent(tabContentId, newPath);

                        // Update URL to preserve tab & search query (clear pagination)
                        updateURLParams({
                            tab: tabParam,
                            search: searchQuery,
                            page_num: null
                        });
                    });
                }

                container.querySelectorAll('.pagination-link').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        const pageNum = link.getAttribute('data-page');

                        const url = new URL(filePath, window.location.origin);
                        const currentPage = url.searchParams.get("page") || "pending_orders";
                        const searchParam = container.querySelector('input[name="search"]')?.value || '';
                        const tabParam = getCurrentTabIndexFromURL() + 1;

                        let newPath = `index.php?page=${currentPage}&page_num=${pageNum}`;
                        if (searchParam) newPath += `&search=${encodeURIComponent(searchParam)}`;

                        loadTableContent(tabContentId, newPath);

                        // Update URL to preserve tab, search and page_num
                        updateURLParams({
                            tab: tabParam,
                            search: searchParam,
                            page_num: pageNum
                        });
                    });
                });
            })
            .catch(err => console.error('Error loading table:', err));
    }

    document.addEventListener('DOMContentLoaded', generateTabs);
</script>
<!-- ######################### [End-body/order info part] ##################################### -->



<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->