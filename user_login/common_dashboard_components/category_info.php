<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Category Info') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/category_queries.php');
require_once 'common_components/tbl_pagination_UI/pagination.php';
require_once 'common_components/tbl_pagination_UI/pagination_links.php';
include('common_components/modal/confirm_modal.php');

createCategoriesTableIfNotExists($mysqli_conn);

$role = $_SESSION['user_info']['role_name'];

$editingCategoryId = "";
$editingCategoryName = "";
$message = "";
$errorMessage = "";
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Show messages via GET status
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status === 'added') $message = "Category added successfully!";
    elseif ($status === 'updated') $message = "Category updated successfully!";
    elseif ($status === 'deleted') $message = "Category deleted successfully!";
    elseif ($status === 'fail') $errorMessage = "Something went wrong.";
}

// =================== Handle POST: Add, Update, Delete ===================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['categoryName'])) {
        $categoryName = htmlspecialchars(trim($_POST['categoryName']));
        $categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

        if ($categoryId) {
            $result = updateCategory($mysqli_conn, $categoryId, $categoryName);
            header("Location: index.php?page=category_info&status=" . ($result['success'] ? 'updated' : 'fail'));
            exit();
        } else {
            $result = insertCategory($mysqli_conn, $categoryName);
            header("Location: index.php?page=category_info&status=" . ($result['success'] ? 'added' : 'fail'));
            exit();
        }
    }

    if (isset($_POST['deleteCategoryId'])) {
        $categoryId = intval($_POST['deleteCategoryId']);
        $deleted = deleteCategory($mysqli_conn, $categoryId);
        header("Location: index.php?page=category_info&status=" . ($deleted ? 'deleted' : 'fail'));
        exit();
    }
}

// =================== Handle GET: Edit mode ===================
if (isset($_GET['edit'])) {
    $editingCategoryId = intval($_GET['edit']);
    $category = getCategoryById($mysqli_conn, $editingCategoryId);
    $editingCategoryName = $category ? $category['category_name'] : '';
    if (!$category) $errorMessage = "Invalid category ID.";
}

// =================== Fetch Categories ===================
if (!empty($searchQuery)) {
    $categories = getCategoryByCategoryName($mysqli_conn, $searchQuery);
    $isSearchMode = true;
} else {
    $pagination = paginate(
        $mysqli_conn,
        'getTotalCategoryCount',
        'getCategoriesPaginated',
        '?page=category_info',
        5
    );
    $categories = $pagination['items'];
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

                    <!-- [Add/Edit Form] -->
                    <div class="mt-2">
                        <h3><?= $editingCategoryId ? 'Edit' : 'Add' ?> Category</h3>
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="card-body bg-light p-4">
                                    <?php if ($message): ?>
                                        <div class="alert alert-success"><?= $message ?></div>
                                    <?php endif; ?>
                                    <?php if ($errorMessage): ?>
                                        <div class="alert alert-danger"><?= $errorMessage ?></div>
                                    <?php endif; ?>

                                    <form method="POST" id="categoryForm">
                                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($editingCategoryId) ?>" />
                                        <div class="mb-3">
                                            <label for="categoryName" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="categoryName" name="categoryName"
                                                value="<?= htmlspecialchars($editingCategoryName) ?>" required />
                                        </div>
                                        <div class="form-footer d-flex justify-content-end">
                                            <?php if ($editingCategoryId): ?>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmUpdateModal">
                                                    Update
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-primary">Add</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- [List] -->
                    <div class="mt-5">
                        <h4>Category List</h4>
                        <div class="card shadow-sm rounded">
                            <div class="card-body">
                                <div class="card-body bg-light p-4">
                                    <form method="GET" class="mb-3">
                                        <div class="input-group">
                                            <input type="hidden" name="page" value="category_info">
                                            <input type="text" name="search" class="form-control"
                                                placeholder="Search categories..." value="<?= htmlspecialchars($searchQuery) ?>">
                                            <button class="btn btn-outline-secondary" type="submit">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </div>
                                    </form>

                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($categories)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No categories found.</td>
                                                </tr>
                                                <?php else:
                                                $counter = isset($offset) ? $offset + 1 : 1;
                                                foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td><?= $counter ?></td>
                                                        <td><?= htmlspecialchars($category['category_name']) ?></td>
                                                        <td>
                                                            <a href="index.php?page=category_info&edit=<?= $category['category_id'] ?>"
                                                                class="btn btn-warning btn-sm">Edit</a>

                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                                                data-id="<?= $category['category_id'] ?>">Delete</button>
                                                        </td>
                                                    </tr>
                                                <?php $counter++;
                                                endforeach; ?>
                                            <?php endif; ?>
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

        // Clean up ?status from URL
        const url = new URL(window.location);
        if (url.searchParams.has('status')) {
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    });

    // Handle Delete modal input
    document.addEventListener('DOMContentLoaded', function() {
        const deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const categoryId = button.getAttribute('data-id');
                const input = deleteModal.querySelector('input[name="deleteCategoryId"]');
                if (input) input.value = categoryId;
            });
        }

        const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
        if (confirmUpdateBtn) {
            confirmUpdateBtn.addEventListener('click', function() {
                const form = document.getElementById('categoryForm');
                if (form) form.submit();
            });
        }
    });
</script>

<!-- Delete Confirmation Modal -->
<?php
renderConfirmationModal(
    'confirmDeleteModal',
    'Confirm Delete',
    'Are you sure you want to delete this category?',
    '',
    'deleteCategoryId',
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
                Are you sure you want to update this category?
            </div>
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