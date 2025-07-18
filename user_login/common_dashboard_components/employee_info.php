<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Employee Info') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/user_queries.php');
include('queries/role_queries.php');
require_once 'common_components/tbl_pagination_UI/pagination.php';
require_once 'common_components/tbl_pagination_UI/pagination_links.php';
include('common_components/modal/confirm_modal.php');
include_once('utils/utils.php');

// Setup
createRolesTableIfNotExists($mysqli_conn);
insertDefaultRoles($mysqli_conn);
createUsersTableIfNotExists($mysqli_conn);

// Role for dashboard
$role = $_SESSION['user_info']['role_name'];

// Messages
$status = $_GET['status'] ?? '';
$message = '';
$errorMessage = '';
if ($status === 'added') $message = 'Employee added successfully!';
elseif ($status === 'updated') $message = 'Employee updated successfully!';
elseif ($status === 'deleted') $message = 'Employee deleted successfully!';
elseif ($status === 'fail') $errorMessage = 'Operation failed. Please try again.';

// Search
$searchQuery = $_GET['search'] ?? '';

// Edit state
$editingUserId = '';
$editingName = '';
$editingEmail = '';
$editingAddress = '';
$editingContactNo = '';
$editingUserRoleId = '';

// =================== Handle POST ===================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['username'], $_POST['email'], $_POST['telNo'], $_POST['address'])) {
        $userId = $_POST['user_id'] ?? null;
        $user_name = trim($_POST['username']);
        $user_email = trim($_POST['email']);
        $user_telephone_no = trim($_POST['telNo']);
        $user_address = trim($_POST['address']);
        $userRole_id = $_POST['userRole_id'];
        $user_password = "123";

        if ($userId) {
            $result = updateEmployeeInfo($mysqli_conn, $userId, $user_name, $user_address, $user_telephone_no, $userRole_id);
            header("Location: index.php?page=employee_info&status=" . ($result['success'] ? 'updated' : 'fail'));
            exit();
        } else {
            $result = insertUser($mysqli_conn, $user_name, $user_password, $user_email, $user_address, $user_telephone_no, $userRole_id);
            header("Location: index.php?page=employee_info&status=" . ($result['success'] ? 'added' : 'fail'));
            exit();
        }
    }

    if (isset($_POST['editUserId'])) {
        $editingUserId = intval($_POST['editUserId']);
        $employee = getUserById($mysqli_conn, $editingUserId);
        if ($employee) {
            $editingName = $employee['user_name'];
            $editingEmail = $employee['user_email'];
            $editingAddress = $employee['user_address'];
            $editingContactNo = $employee['user_telephone_no'];
            $editingUserRoleId = $employee['role_id'];
        } else {
            $errorMessage = "Invalid user ID.";
        }
    }

    if (isset($_POST['deleteUserId'])) {
        $userId = intval($_POST['deleteUserId']);
        $deleted = deleteStaffUser($mysqli_conn, $userId);
        header("Location: index.php?page=employee_info&status=" . ($deleted ? 'deleted' : 'fail'));
        exit();
    }
}

// Fetch data
$userRoles = getAllStaffRoles($mysqli_conn);
if (!empty($searchQuery)) {
    $employees = getStaffUserByUserName($mysqli_conn, $searchQuery);
    $isSearchMode = true;
} else {
    $pagination = paginate($mysqli_conn, 'getTotalStaffUserCount', 'getStaffUsersPaginated', '?page=employee_info', 5);
    $employees = $pagination['items'];
    $offset = $pagination['offset'];
    $isSearchMode = false;
}
?>

<!-- ######################### [Start-body/admin_dashboard part] ############################### -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2">
            <div class="nav flex-column nav-pills" role="tablist">
                <button onclick="redirectToDashboard()" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To <?= ucfirst($role); ?> Dashboard
                </button>
            </div>
        </div>

        <div class="col-md-10">
            <div id="tab-content">
                <div class="container mt-3">
                    <!-- [Form Section] -->
                    <div class="mt-2">
                        <h3><?= $editingUserId ? 'Edit' : 'Add' ?> Employee</h3>
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="card-body bg-light p-4">
                                    <?php if ($message): ?>
                                        <div class="alert alert-success"><?= $message ?></div>
                                    <?php endif; ?>
                                    <?php if ($errorMessage): ?>
                                        <div class="alert alert-danger"><?= $errorMessage ?></div>
                                    <?php endif; ?>

                                    <form method="POST" id="employeeForm">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($editingUserId) ?>" />

                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($editingName) ?>" required />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editingEmail) ?>" required />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Telephone Number</label>
                                            <input type="text" class="form-control" name="telNo" value="<?= htmlspecialchars($editingContactNo) ?>" required />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" name="address" rows="2" required><?= htmlspecialchars($editingAddress) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select class="form-control" name="userRole_id" required>
                                                <option disabled <?= !$editingUserId ? 'selected' : '' ?>>Select</option>
                                                <?php foreach ($userRoles as $userRole): ?>
                                                    <option value="<?= $userRole['role_id'] ?>" <?= $editingUserRoleId == $userRole['role_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($userRole['role_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-footer d-flex justify-content-end">
                                            <?php if ($editingUserId): ?>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmUpdateModal">Update</button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-primary">Add</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- [List Section] -->
                    <div class="mt-5">
                        <h4>Employee List</h4>
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="card-body bg-light p-4">
                                    <form method="GET" class="mb-3">
                                        <input type="hidden" name="page" value="employee_info">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" placeholder="Search employees..." value="<?= htmlspecialchars($searchQuery) ?>">
                                            <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                                        </div>
                                    </form>

                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($employees)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No employees found.</td>
                                                </tr>
                                                <?php else:
                                                $counter = isset($offset) ? $offset + 1 : 1;
                                                foreach ($employees as $employee): ?>
                                                    <tr>
                                                        <td><?= $counter++ ?></td>
                                                        <td><?= htmlspecialchars($employee['user_name']) ?></td>
                                                        <td><?= htmlspecialchars($employee['user_email']) ?></td>
                                                        <td><?= htmlspecialchars($employee['role_name']) ?></td>
                                                        <td>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="editUserId" value="<?= $employee['user_id'] ?>" />
                                                                <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                                                            </form>
                                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= $employee['user_id'] ?>">Delete</button>
                                                        </td>
                                                    </tr>
                                            <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>

                                    <?php if (!$isSearchMode): ?>
                                        <?php
                                        renderPaginationLinks(
                                            $pagination['currentPage'],
                                            $pagination['totalPages'],
                                            $pagination['baseHref'],
                                            $pagination['queryPageParam']
                                        );
                                        ?>
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

<!-- ####################### JavaScript Logic ####################### -->
<script>
    function redirectToDashboard() {
        const role = "<?= $role ?>";
        const dashboards = {
            admin: "admin_dashboard",
            manager: "manager_dashboard",
            cashier: "cashier_dashboard",
            customer: "user_dashboard"
        };
        if (dashboards[role]) {
            window.location.href = "index.php?page=" + dashboards[role];
        } else {
            alert("Dashboard not defined for this role.");
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        ['.alert-success', '.alert-danger'].forEach(selector => {
            const alert = document.querySelector(selector);
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
        });

        const url = new URL(window.location);
        if (url.searchParams.has('status')) {
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }

        const deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-id');
                const input = deleteModal.querySelector('input[name="deleteUserId"]');
                if (input) input.value = userId;
            });
        }

        const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
        if (confirmUpdateBtn) {
            confirmUpdateBtn.addEventListener('click', () => {
                const form = document.getElementById('employeeForm');
                if (form) form.submit();
            });
        }
    });
</script>

<!-- Modals -->
<?php
renderConfirmationModal(
    'confirmDeleteModal',
    'Confirm Delete',
    'Are you sure you want to delete this employee?',
    '',
    'deleteUserId',
    'Delete',
    'btn-danger'
);
?>

<div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="confirmUpdateModalLabel">Confirm Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to update this employee?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmUpdateBtn" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- ######################### [End-body/admin_dashboard part] ################################# -->
<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->