<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Login') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
// Include the database connection and functions
include("setup/config.php");
include("db_connection.php");
include('queries/role_queries.php');
include('queries/user_queries.php');

// Initialize tables
createRolesTableIfNotExists($mysqli_conn);
insertDefaultRoles($mysqli_conn);
createUsersTableIfNotExists($mysqli_conn);

// ==================== PRG (Post/Redirect/Get): Redirect after POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        header("Location: index.php?page=login&status=empty");
        exit;
    }

    $user_info = authenticateUser($mysqli_conn, $email, $password);
    if ($user_info) {
        $_SESSION['user_info'] = $user_info;

        $role = $user_info['role_name'] ?? '';
        $dashboardMap = [
            'admin'    => 'admin_dashboard',
            'manager'  => 'manager_dashboard',
            'cashier'  => 'cashier_dashboard',
            'customer' => 'user_dashboard'
        ];

        $dashboardPage = $dashboardMap[$role] ?? 'index';
        header("Location: index.php?page=$dashboardPage");
        exit;
    } else {
        header("Location: index.php?page=login&status=invalid");
        exit;
    }
}

// Show message based on PRG status
$status = $_GET['status'] ?? '';
$message = '';
$messageClass = '';

if ($status === 'empty') {
    $message = 'Please enter both email and password.';
    $messageClass = 'alert-danger';
} elseif ($status === 'invalid') {
    $message = 'Invalid email or password.';
    $messageClass = 'alert-danger';
} elseif ($status === 'logout') {
    $message = 'You have been logged out successfully.';
    $messageClass = 'alert-success';
}
?>

<!-- ######################### [Start-body/login part] ################################# -->
<div class="login-form-container">
  <div class="card shadow-sm rounded">
    <div class="card-body">
      <h3 class="text-center mb-4">Login</h3>

      <div class="bg-light p-4">
        <!-- Error or success message -->
        <?php if ($message): ?>
          <div class="alert <?= $messageClass ?> text-center fade-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form class="form" action="" method="post" id="loginForm" autocomplete="off">
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" required />
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" id="password" required />
          </div>

          <div class="form-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Login</button>
          </div>

          <div class="mt-3 text-center">
            <small>Don't have an account? <a href="index.php?page=registration">Register</a></small>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ######################### [Fade-out for alert messages] ######################### -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const alertBox = document.querySelector('.fade-message');
    if (alertBox) {
      setTimeout(() => {
        alertBox.style.transition = 'opacity 0.5s ease-out';
        alertBox.style.opacity = '0';
        setTimeout(() => alertBox.remove(), 500);
      }, 3000); // Show for 3 seconds
    }
  });
</script>

<!-- ######################### [End-body/login part] ################################### -->

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->
