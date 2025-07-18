<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Category Info') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/user_queries.php');
include('common_components/modal/confirm_modal.php');

createUsersTableIfNotExists($mysqli_conn);

//Role-based back to dashboard
$role = $_SESSION['user_info']['role_name'];

$editingUserId = $_SESSION['user_info']['user_id'] ?? "";

$editingName = "";
$editingEmail = "";
$editingAddress = "";
$editingContactNo = "";
$message = "";
$errorMessage = "";

$user = getUserById($mysqli_conn, $editingUserId);
if ($user) {
    $editingName = $user['user_name'];
    $editingEmail = $user['user_email'];
    $editingAddress = $user['user_address'];
    $editingContactNo = $user['user_telephone_no'];
} else {
    $errorMessage = "Failed to load user information.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_name']) && isset($_POST['user_address']) && isset($_POST['user_telephone_no'])) {
    $username = htmlspecialchars(trim($_POST['user_name']));
    $userAddress = htmlspecialchars(trim($_POST['user_address']));
    $userContactNo = htmlspecialchars(trim($_POST['user_telephone_no']));
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

    if ($userId) {
        $result = updateCustomerInfo($mysqli_conn, $userId, $username, $userAddress, $userContactNo);
        if ($result['success']) {
            $message = "Profile updated successfully!";
            $user = getUserById($mysqli_conn, $userId);
            if ($user) {
                $editingName = $user['user_name'];
                $editingEmail = $user['user_email'];
                $editingAddress = $user['user_address'];
                $editingContactNo = $user['user_telephone_no'];
            }
        } else {
            $errorMessage = "Error: " . $result['error'];
        }
    } else {
        $errorMessage = "Invalid user ID.";
    }
}
?>

<!-- ######################### [Start-body/admin_dashboard part] ############################### -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2">
            <div class="nav flex-column nav-pills">
                <button onclick="redirectToDashboard()" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To <?php echo ucfirst($role); ?> Dashboard
                </button>
            </div>
        </div>

        <div class="col-md-10">
            <div class="container mt-3">
                <div class="card shadow-sm rounded">
                    <div class="card-body">
                        <div class="bg-light p-4">
                            <?php if (!empty($message)): ?>
                                <div class="alert alert-success"><?= $message ?></div>
                            <?php endif; ?>
                            <?php if (!empty($errorMessage)): ?>
                                <div class="alert alert-danger"><?= $errorMessage ?></div>
                            <?php endif; ?>

                            <form class="form" action="" method="post" id="userProfileForm">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($editingUserId) ?>" />

                                <div class="mb-3">
                                    <label for="user_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="user_name" name="user_name"
                                        value="<?= htmlspecialchars($editingName) ?>" readonly />
                                </div>

                                <div class="mb-3">
                                    <label for="user_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="user_email" name="user_email"
                                        value="<?= htmlspecialchars($editingEmail) ?>" readonly />
                                </div>

                                <div class="mb-3">
                                    <label for="user_address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="user_address" name="user_address"
                                        value="<?= htmlspecialchars($editingAddress) ?>" readonly />
                                </div>

                                <div class="mb-3">
                                    <label for="user_telephone_no" class="form-label">Contact No</label>
                                    <input type="text" class="form-control" id="user_telephone_no" name="user_telephone_no"
                                        value="<?= htmlspecialchars($editingContactNo) ?>" readonly />
                                </div>

                                <div class="form-footer d-flex justify-content-end mt-3">
                                    <i class="fa fa-edit me-3" id="editToggle" title="Edit Form" style="cursor: pointer;"></i>
                                    <button type="button" class="btn btn-primary d-none" id="saveBtn">Update</button>
                                </div>
                            </form>
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


<!-- Form edit enable -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editBtn = document.getElementById('editToggle');
        const form = document.getElementById('userProfileForm');
        const saveBtn = document.getElementById('saveBtn');

        // Toggle form to editable mode
        editBtn.addEventListener('click', () => {
            const editableFields = ['user_name', 'user_address', 'user_telephone_no'];
            editableFields.forEach(id => {
                const input = form.querySelector(`#${id}`);
                if (input) input.readOnly = false;
            });

            // Hide edit icon, show save button
            editBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
        });

        // Show confirmation modal before submitting form
        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('confirmUpdateModal'));
            modal.show();
        });

        // Submit form on confirmation
        const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
        if (confirmUpdateBtn && form) {
            confirmUpdateBtn.addEventListener('click', function() {
                form.submit();
            });
        }
    });
</script>


<!-- Alert fade -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hideAlert = (alert) => {
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
        };
        hideAlert(document.querySelector('.alert-success'));
        hideAlert(document.querySelector('.alert-danger'));
    });
</script>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Confirm Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to update your profile?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmUpdateBtn" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->