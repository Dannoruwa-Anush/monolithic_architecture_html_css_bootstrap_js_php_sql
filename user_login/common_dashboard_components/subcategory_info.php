<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Subcategory Info') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/category_queries.php');
include('queries/subcategory_queries.php');
require_once 'common_components/tbl_pagination_UI/pagination.php';
require_once 'common_components/tbl_pagination_UI/pagination_links.php';
include('common_components/modal/confirm_modal.php');

createSubCategoriesTableIfNotExists($mysqli_conn);

$role = $_SESSION['user_info']['role_name'];

$editingSubCategoryId = "";
$editingSubCategoryName = "";
$editingCategoryId = "";
$message = "";
$errorMessage = "";
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Show messages via GET status
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status === 'added') $message = "Sub category added successfully!";
    elseif ($status === 'updated') $message = "Sub category updated successfully!";
    elseif ($status === 'deleted') $message = "Sub category deleted successfully!";
    elseif ($status === 'fail') $errorMessage = "Something went wrong.";
}

// =================== Handle POST: Add, Update, Delete ===================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Add or Update
    if (isset($_POST['subcategoryName'])) {
        $subcategoryName = htmlspecialchars(trim($_POST['subcategoryName']));
        $subcategoryId = isset($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
        $categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

        if ($subcategoryId) {
            $result = updateSubCategory($mysqli_conn, $subcategoryId, $subcategoryName, $categoryId);
            header("Location: index.php?page=subcategory_info&status=" . ($result['success'] ? 'updated' : 'fail'));
            exit();
        } else {
            $result = insertSubCategory($mysqli_conn, $subcategoryName, $categoryId);
            header("Location: index.php?page=subcategory_info&status=" . ($result['success'] ? 'added' : 'fail'));
            exit();
        }
    }

    // Delete
    if (isset($_POST['deleteSubCategoryId'])) {
        $subcategoryId = intval($_POST['deleteSubCategoryId']);
        $deleted = deleteSubCategory($mysqli_conn, $subcategoryId);
        header("Location: index.php?page=subcategory_info&status=" . ($deleted ? 'deleted' : 'fail'));
        exit();
    }

    // Populate for Edit
    if (isset($_POST['editSubCategoryId'])) {
        $editingSubCategoryId = intval($_POST['editSubCategoryId']);
        $subcategory = getSubCategoryByIdWithCategory($mysqli_conn, $editingSubCategoryId);
        if ($subcategory) {
            $editingSubCategoryName = $subcategory['subcategory_name'];
            $editingCategoryId = $subcategory['category_id'];
        } else {
            $errorMessage = "Invalid sub category ID.";
        }
    }
}

// Load categories for dropdown
$categories = getAllCategories($mysqli_conn);

// Fetch subcategories list
if (!empty($searchQuery)) {
    $subcategories = getSubCategoryBySubCategoryName($mysqli_conn, $searchQuery);
    $isSearchMode = true;
} else {
    $pagination = paginate(
        $mysqli_conn,
        'getTotalSubCategoryCount',
        'getSubCategoriesPaginated',
        '?page=subcategory_info',
        5
    );
    $subcategories = $pagination['items'];
    $offset = $pagination['offset'];
    $isSearchMode = false;
}
?>

<!-- ######################### [Start-body/admin_dashboard part] ############################### -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2">
            <div class="nav flex-column nav-pills" id="v-tabs" role="tablist" aria-orientation="vertical">
                <button onclick="redirectToDashboard()" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To <?= ucfirst($role); ?> Dashboard
                </button>
            </div>
        </div>

        <div class="col-md-10">
            <div id="tab-content">
                <div class="container mt-3">

                    <!-- Add/Edit Form -->
                    <div class="mt-2">
                        <h3><?= $editingSubCategoryId ? 'Edit' : 'Add' ?> Sub Category</h3>
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="card-body bg-light p-4">
                                    <?php if ($message): ?>
                                        <div class="alert alert-success"><?= $message ?></div>
                                    <?php endif; ?>
                                    <?php if ($errorMessage): ?>
                                        <div class="alert alert-danger"><?= $errorMessage ?></div>
                                    <?php endif; ?>

                                    <form class="form" method="POST" id="subcategoryForm">
                                        <input type="hidden" name="subcategory_id" value="<?= htmlspecialchars($editingSubCategoryId) ?>" />
                                        <div class="mb-3">
                                            <label for="subcategoryName" class="form-label">Name</label>
                                            <input type="text" class="form-control" name="subcategoryName" id="subcategoryName"
                                                value="<?= htmlspecialchars($editingSubCategoryName) ?>" required />
                                        </div>

                                        <div class="mb-3">
                                            <label for="categorySelect" class="form-label">Category</label>
                                            <select class="form-control" name="category_id" id="categorySelect" required>
                                                <option value="" disabled <?= !$editingCategoryId ? 'selected' : '' ?>>Select</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['category_id'] ?>"
                                                        <?= ($editingCategoryId == $category['category_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($category['category_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-footer d-flex justify-content-end">
                                            <?php if ($editingSubCategoryId): ?>
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

                    <!-- List -->
                    <div class="mt-5">
                        <h4>Sub Category List</h4>
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="card-body bg-light p-4">
                                    <!-- Search -->
                                    <form method="GET" action="" class="mb-3">
                                        <div class="input-group">
                                            <input type="hidden" name="page" value="subcategory_info">
                                            <input type="text" name="search" class="form-control" placeholder="Search sub categories..."
                                                value="<?= htmlspecialchars($searchQuery) ?>">
                                            <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                                        </div>
                                    </form>

                                    <!-- Table -->
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Sub Category Name</th>
                                                <th>Category Name</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($subcategories)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No sub categories found.</td>
                                                </tr>
                                                <?php else:
                                                $counter = isset($offset) ? $offset + 1 : 1;
                                                foreach ($subcategories as $subcategory): ?>
                                                    <tr>
                                                        <td><?= $counter ?></td>
                                                        <td><?= htmlspecialchars($subcategory['subcategory_name']) ?></td>
                                                        <td><?= htmlspecialchars($subcategory['category_name']) ?></td>
                                                        <td>
                                                            <form method="POST" action="" style="display:inline;">
                                                                <input type="hidden" name="editSubCategoryId" value="<?= $subcategory['subcategory_id'] ?>" />
                                                                <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                                                            </form>
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                                                data-id="<?= $subcategory['subcategory_id'] ?>">
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php $counter++;
                                                endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
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

<!-- Delete Modal -->
<?php
renderConfirmationModal(
    'confirmDeleteModal',
    'Confirm Delete',
    'Are you sure you want to delete this sub category?',
    '',
    'deleteSubCategoryId',
    'Delete',
    'btn-danger'
);
?>

<!-- Update Confirmation Modal -->
<div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="confirmUpdateModalLabel">Confirm Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to update this sub category?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmUpdateBtn" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- JS Scripts -->
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

    document.addEventListener('DOMContentLoaded', function() {
        const success = document.querySelector('.alert-success');
        const danger = document.querySelector('.alert-danger');

        [success, danger].forEach(alert => {
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
        });

        // Remove ?status= from URL
        const url = new URL(window.location);
        if (url.searchParams.has('status')) {
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }

        // Confirm Delete Modal
        const deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const input = deleteModal.querySelector('input[name="deleteSubCategoryId"]');
                if (input) input.value = id;
            });
        }

        // Confirm Update Modal
        const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
        if (confirmUpdateBtn) {
            confirmUpdateBtn.addEventListener('click', function() {
                const form = document.getElementById('subcategoryForm');
                if (form) form.submit();
            });
        }
    });
</script>
<!-- ######################### [End-body/admin_dashboard part] ################################# -->
<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->