<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Report Info') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/customerOrder_queries.php');

$role = $_SESSION['user_info']['role_name'] ?? 'guest';

$reportData = [];
$report_type = $_GET['report_type'] ?? '';
$start_date = $end_date = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $report_type) {
    switch ($report_type) {
        case 'daily':
            $start_date = $end_date = $_GET['date'] ?? '';
            break;
        case 'weekly':
            $start_date = $_GET['start'] ?? '';
            $end_date = $_GET['end'] ?? '';
            break;
        case 'monthly':
            $start_date = ($_GET['month'] ?? '') . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
            break;
        case 'yearly':
            $year = $_GET['year'] ?? '';
            $start_date = "$year-01-01";
            $end_date = "$year-12-31";
            break;
    }

    if (!empty($start_date) && !empty($end_date)) {
        $reportData = getDeliveredProductReport($mysqli_conn, $start_date, $end_date);
    } else {
        $reportData = [];
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="nav flex-column nav-pills">
                <button onclick="redirectToDashboard()" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To <?= ucfirst($role) ?> Dashboard
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <div id="tab-content">
                <div class="container mt-5">
                    <h3>Delivered Orders Report</h3>

                    <div class="card p-4 shadow-sm mb-4">
                        <form id="reportForm" method="GET" action="">
                            <input type="hidden" name="page" value="report_info">

                            <!-- Report Type -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Select Report Type:</label>
                                <div class="btn-group" role="group">
                                    <?php foreach (['daily', 'weekly', 'monthly', 'yearly'] as $type): ?>
                                        <input type="radio" class="btn-check" name="report_type" id="<?= $type ?>" value="<?= $type ?>" <?= ($report_type === $type ? 'checked' : '') ?>>
                                        <label class="btn btn-outline-primary" for="<?= $type ?>"><?= ucfirst($type) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Dynamic Inputs -->
                            <div id="reportInputs">
                                <div id="dailyInput" class="input-group mb-3 d-none">
                                    <label class="input-group-text">Select Date</label>
                                    <input type="date" class="form-control" name="date" id="dailyDate" value="<?= $_GET['date'] ?? '' ?>">
                                </div>

                                <div id="weeklyInput" class="row mb-3 d-none">
                                    <div class="col">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start" id="weeklyStart" value="<?= $_GET['start'] ?? '' ?>">
                                    </div>
                                    <div class="col">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end" id="weeklyEnd" value="<?= $_GET['end'] ?? '' ?>">
                                    </div>
                                </div>

                                <div id="monthlyInput" class="input-group mb-3 d-none">
                                    <label class="input-group-text">Select Month</label>
                                    <input type="month" class="form-control" name="month" id="monthlyMonth" value="<?= $_GET['month'] ?? '' ?>">
                                </div>

                                <div id="yearlyInput" class="input-group mb-3 d-none">
                                    <label class="input-group-text">Select Year</label>
                                    <input type="number" class="form-control" name="year" id="yearlyYear" min="2000" max="2100" value="<?= $_GET['year'] ?? '' ?>">
                                </div>
                            </div>

                            <!-- View Button -->
                            <div id="viewButtonWrapper" class="mt-3 d-none">
                                <button type="submit" class="btn btn-primary">View</button>
                                <a id="exportPdfBtn" class="btn btn-danger ms-2 d-none" target="_blank">Export to PDF</a>
                            </div>
                        </form>
                    </div>

                    <div class="mt-5 <?= !empty($reportData) ? '' : 'd-none' ?>" id="orderListWrapper">
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="bg-light p-4">
                                    <?php if (!empty($reportData)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Product</th>
                                                        <th>Variant</th>
                                                        <th>Category</th>
                                                        <th>Subcategory</th>
                                                        <th class="text-end">Unit Price</th>
                                                        <th class="text-end">Qty</th>
                                                        <th class="text-end">Sub Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($reportData as $row): ?>
                                                        <tr>
                                                            <td><?= $counter++ ?></td>
                                                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                            <td><?= $row['variant_name'] ? htmlspecialchars($row['variant_name']) : '-' ?></td> 
                                                            <td><?= htmlspecialchars($row['category_name']) ?></td>
                                                            <td><?= htmlspecialchars($row['subcategory_name']) ?></td>
                                                            <td class="text-end"><?= number_format($row['unit_price'], 2) ?></td>
                                                            <td class="text-end"><?= $row['qty'] ?></td>
                                                            <td class="text-end"><?= number_format($row['sub_total'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php elseif ($report_type): ?>
                                        <div class="alert alert-warning">No data found for the selected range.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script>
    function redirectToDashboard() {
        const role = "<?= $role ?>";
        const map = {
            admin: 'admin_dashboard',
            manager: 'manager_dashboard',
            cashier: 'cashier_dashboard',
            customer: 'user_dashboard'
        };
        if (map[role]) {
            window.location.href = 'index.php?page=' + map[role];
        } else {
            alert("No dashboard found for role: " + role);
        }
    }

    function toggleInputs(value) {
        const sections = ['daily', 'weekly', 'monthly', 'yearly'];
        sections.forEach(section => {
            document.getElementById(section + 'Input').classList.add('d-none');
        });
        document.getElementById(value + 'Input').classList.remove('d-none');
        validateInputs(value);
    }

    function validateInputs(reportType) {
        let isValid = false;
        let url = "?page=export_report_pdf&report_type=" + reportType;

        switch (reportType) {
            case 'daily':
                const dailyDate = document.getElementById('dailyDate').value;
                isValid = dailyDate !== '';
                if (isValid) url += `&date=${dailyDate}`;
                break;
            case 'weekly':
                const start = document.getElementById('weeklyStart').value;
                const end = document.getElementById('weeklyEnd').value;
                isValid = start !== '' && end !== '';
                if (isValid) url += `&start=${start}&end=${end}`;
                break;
            case 'monthly':
                const month = document.getElementById('monthlyMonth').value;
                isValid = month !== '';
                if (isValid) url += `&month=${month}`;
                break;
            case 'yearly':
                const year = document.getElementById('yearlyYear').value;
                isValid = year !== '';
                if (isValid) url += `&year=${year}`;
                break;
        }

        const viewBtn = document.getElementById('viewButtonWrapper');
        const exportBtn = document.getElementById('exportPdfBtn');
        const orderListWrapper = document.getElementById('orderListWrapper');

        if (isValid) {
            // d-none : hide => remove()
            // Show view btn
            viewBtn.classList.remove('d-none');
            exportBtn.classList.remove('d-none');
            exportBtn.href = url;
        } else {
            // d-none : hide
            // Hide view btn
            viewBtn.classList.add('d-none');
            exportBtn.classList.add('d-none');
        }
    }

    document.querySelectorAll('input[name="report_type"]').forEach(radio => {
        radio.addEventListener('change', () => {
            // Clear all input values
            document.getElementById('dailyDate').value = '';
            document.getElementById('weeklyStart').value = '';
            document.getElementById('weeklyEnd').value = '';
            document.getElementById('monthlyMonth').value = '';
            document.getElementById('yearlyYear').value = '';

            // Hide results and buttons
            document.getElementById('orderListWrapper').classList.add('d-none');
            document.getElementById('viewButtonWrapper').classList.add('d-none');
            document.getElementById('exportPdfBtn').classList.add('d-none');

            // Show correct input section
            toggleInputs(radio.value);

            // Reset the URL to remove old query params
            const newUrl = window.location.pathname + '?page=report_info&report_type=' + radio.value;
            history.replaceState({}, '', newUrl);
        });
    });

    ['dailyDate', 'weeklyStart', 'weeklyEnd', 'monthlyMonth', 'yearlyYear'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', () => {
                const type = document.querySelector('input[name="report_type"]:checked').value;
                validateInputs(type);
            });
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const current = document.querySelector('input[name="report_type"]:checked');
        if (current) toggleInputs(current.value);
    });
</script>

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->