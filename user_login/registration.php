<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Registration') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php

// Include the database connection and functions
include("setup/config.php");
include("db_connection.php");
include('queries/role_queries.php');
include('queries/user_queries.php');

// Initialize table creation if it doesn't exist
createRolesTableIfNotExists($mysqli_conn);
insertDefaultRoles($mysqli_conn);
createUsersTableIfNotExists($mysqli_conn);

// Variables for form state
$message = "";
$errorMessage = "";

// ==================== PRG (Post/Redirect/Get): Redirect after POST ====================
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['username'], $_POST['email'], $_POST['telNo'], $_POST['address'], $_POST['password'])) {
    $user_name = htmlspecialchars(trim($_POST['username']));
    $user_email = htmlspecialchars(trim($_POST['email']));
    $user_telephone_no = htmlspecialchars(trim($_POST['telNo']));
    $user_address = htmlspecialchars(trim($_POST['address']));
    $user_password = htmlspecialchars(trim($_POST['password']));
    $role_id = 0; // Default role is 'customer'

    $result = insertUser($mysqli_conn, $user_name, $user_password, $user_email, $user_address, $user_telephone_no, $role_id);
    
    if ($result['success']) {
        // Redirect to login page if successful (PRG)
        header("Location: index.php?page=login&status=registered");
        exit;
    } else {
        // Stay on the same page and show error
        $errorMessage = "Error: " . $result['error'];
    }
}
?>

<!-- ######################### [Start-body/registration part] ################################# -->
<div class="login-form-container">
    <div class="card shadow-sm rounded">
        <div class="card-body">
            <h3 class="text-center mb-4">Register</h3>

            <div class="bg-light p-4">
                <!-- PHP success/error messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success text-center"><?= $message ?></div>
                <?php elseif (!empty($errorMessage)): ?>
                    <div class="alert alert-danger text-center"><?= $errorMessage ?></div>
                <?php endif; ?>

                <form class="form" action="" method="post" id="registerForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Name</label>
                        <input type="text" class="form-control" id="username" name="username" required />
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required />
                    </div>

                    <div class="mb-3">
                        <label for="telNo" class="form-label">Telephone Number</label>
                        <input type="text" class="form-control" id="telNo" name="telNo" required />
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required />
                    </div>

                    <div class="form-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>

                    <div class="mt-3 text-center">
                        <small>Already have an account? <a href="index.php?page=login">Login</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- [Start : Alerts fade-out] -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const errorAlert = document.querySelector('.alert-danger');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.transition = 'opacity 0.5s ease-out';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }, 3000);
        }
    });
</script>
<!-- [End : Alerts fade-out] -->

<!-- ######################### [End-body/registration part] ################################### -->

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->
