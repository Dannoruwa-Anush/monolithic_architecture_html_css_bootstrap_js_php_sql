<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Product Info') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/product_queries.php');
include('queries/subcategory_queries.php');
include('queries/product_variants_queries.php');
require_once 'common_components/tbl_pagination_UI/pagination.php';
require_once 'common_components/tbl_pagination_UI/pagination_links.php';
include('common_components/modal/confirm_modal.php');

createProductsTableIfNotExists($mysqli_conn);

$role = $_SESSION['user_info']['role_name'];

$editingProductId = "";
$editingProductName = "";
$editingProductDescription = "";
$editingProductQOH = "";
$editingProductPrice = "";
$editingSubCategoryId = "";
$editingProductImage = "";
$editingHasVariants = "";
$editingProductVariants = $editingProductVariants ?? [];
$message = "";
$errorMessage = "";
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $message = match ($status) {
        'added' => "Product added successfully!",
        'updated' => "Product updated successfully!",
        'deleted' => "Product deleted successfully!",
        default => ''
    };
    if ($status === 'fail') $errorMessage = "Something went wrong.";
}

// =================== Handle POST: Add, Update, Delete ===================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --------- ADD / UPDATE PRODUCT -------------
    if (isset($_POST['productName'], $_POST['subcategory_id'])) {
        $productName = htmlspecialchars(trim($_POST['productName']));
        $productDescription = htmlspecialchars(trim($_POST['productDescription']));
        $productPrice = htmlspecialchars(trim($_POST['productPrice']));
        $productQOH = htmlspecialchars(trim($_POST['productQOH']));
        $productImage = "";
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
        $subcategoryId = intval($_POST['subcategory_id']);

        // Handle Image Upload
        if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'images/uploads/products/';
            $imageName = basename($_FILES['productImage']['name']);
            $uploadPath = $uploadDir . $imageName;

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadPath)) {
                if (!empty($productId)) {
                    $product = getProductByIdWithSubCategoryAndCategory($mysqli_conn, $productId);
                    if (!empty($product['product_img']) && file_exists($product['product_img']) && $product['product_img'] !== $uploadPath) {
                        unlink($product['product_img']);
                    }
                }
                $productImage = $uploadPath;
            } else {
                $errorMessage = "Failed to upload image.";
            }
        } else if (!empty($productId)) {
            $product = getProductByIdWithSubCategoryAndCategory($mysqli_conn, $productId);
            $productImage = $product['product_img'];
        }

        // Handle variants
        $hasVariants = isset($_POST['hasVariants']) ? true : false;
        $variants = [];

        if ($hasVariants && isset($_POST['variant_name'], $_POST['variant_price'], $_POST['variant_qoh'])) {
            $names = $_POST['variant_name'];
            $prices = $_POST['variant_price'];
            $qohs = $_POST['variant_qoh'];

            for ($i = 0; $i < count($names); $i++) {
                if (!empty($names[$i]) && isset($prices[$i]) && isset($qohs[$i])) {
                    $variants[] = [
                        'name' => htmlspecialchars(trim($names[$i])),
                        'price' => floatval($prices[$i]),
                        'qoh' => intval($qohs[$i])
                    ];
                }
            }
        }

        if (empty($errorMessage)) {
            $result = $productId
                ? updateProduct($mysqli_conn, $productId, $productName, $productDescription, $productPrice, $productQOH, $productImage, $subcategoryId, $hasVariants, $variants)
                : insertProduct($mysqli_conn, $productName, $productDescription, $productPrice, $productQOH, $productImage, $subcategoryId, $hasVariants, $variants);

            header("Location: index.php?page=product_info&status=" . ($result['success'] ? ($productId ? 'updated' : 'added') : 'fail'));
            exit();
        }
    }

    // --------- DELETE PRODUCT -------------
    if (isset($_POST['deleteProductId'])) {
        $deleted = deleteProduct($mysqli_conn, intval($_POST['deleteProductId']));
        header("Location: index.php?page=product_info&status=" . ($deleted ? 'deleted' : 'fail'));
        exit();
    }

    // --------- LOAD PRODUCT FOR EDITING -------------
    if (isset($_POST['editProductId'])) {
        $editingProductId = intval($_POST['editProductId']);
        $product = getProductByIdWithSubCategoryAndCategory($mysqli_conn, $editingProductId);

        if ($product) {
            $editingProductName = $product['product_name'];
            $editingProductDescription = $product['product_description'];
            $editingProductPrice = $product['product_price'];
            $editingProductQOH = $product['product_qoh'];
            $editingProductImage = $product['product_img'];
            $editingSubCategoryId = $product['subcategory_id'];
            $editingHasVariants = $product['has_variants'];

            if ($editingHasVariants) {
                $editingProductVariants = getVariantsByProductId($mysqli_conn, $editingProductId);
            } else {
                $editingProductVariants = [];
            }
        } else {
            $errorMessage = "Invalid product ID.";
        }
    }
}

$subcategories = getAllSubCategories($mysqli_conn);

if (!empty($searchQuery)) {
    $products = getProductByProductName($mysqli_conn, $searchQuery);
    $isSearchMode = true;
} else {
    $pagination = paginate($mysqli_conn, 'getTotalProductCount', 'getProductsPaginated', '?page=product_info', 5);
    $products = $pagination['items'];
    $offset = $pagination['offset'];
    $isSearchMode = false;
}
?>

<!-- ######################### [Start-body/product info part] ############################### -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2">
            <div class="nav flex-column nav-pills">
                <button onclick="redirectToDashboard()" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To <?= ucfirst($role); ?> Dashboard
                </button>
            </div>
        </div>

        <div class="col-md-10">
            <div class="container mt-3">

                <!-- Add/Edit Form -->
                <div class="mt-2">
                    <h3><?= $editingProductId ? 'Edit' : 'Add' ?> Product</h3>
                    <div class="card shadow-sm rounded">
                        <div class="card-body bg-light p-4">
                            <?php if ($message): ?>
                                <div class="alert alert-success"><?= $message ?></div>
                            <?php endif; ?>
                            <?php if ($errorMessage): ?>
                                <div class="alert alert-danger"><?= $errorMessage ?></div>
                            <?php endif; ?>

                            <form class="form" method="POST" enctype="multipart/form-data" id="productForm">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($editingProductId) ?>" />

                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="productName" value="<?= htmlspecialchars($editingProductName) ?>" required />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="productDescription" rows="3" required><?= htmlspecialchars($editingProductDescription) ?></textarea>
                                </div>

                                <!-- Start : variants -->
                                <div class="mb-3">
                                    <label class="form-label">Has Variants?</label>
                                    <input type="checkbox" id="hasVariantsCheckbox" name="hasVariants" value="1" <?= $editingHasVariants ? 'checked' : '' ?>>
                                </div>

                                <div class="mb-3" id="variantTableDiv" style="display: none;">
                                    <label class="form-label">Variants</label>
                                    <table class="table table-bordered" id="variantTable">
                                        <thead>
                                            <tr>
                                                <th>Variant Name</th>
                                                <th>Price (Rs.)</th>
                                                <th>QOH</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($editingProductVariants)): ?>
                                                <?php foreach ($editingProductVariants as $variant): ?>
                                                    <tr>
                                                        <td><input type="text" name="variant_name[]" class="form-control" value="<?= htmlspecialchars($variant['variant_name']) ?>" required></td>
                                                        <td><input type="number" name="variant_price[]" step="0.01" min="0.01" class="form-control" value="<?= htmlspecialchars($variant['variant_price']) ?>" required></td>
                                                        <td><input type="number" name="variant_qoh[]" min="0" class="form-control" value="<?= htmlspecialchars($variant['variant_qoh']) ?>" required></td>
                                                        <td><button type="button" class="btn btn-danger btn-sm remove-variant">Remove</button></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <button type="button" id="addVariantBtn" class="btn btn-outline-success btn-sm" title="Add Variant">
                                        <i class="fa fa-plus-circle fa-lg"></i>
                                    </button>
                                </div>
                                <!-- End :   variants -->

                                <!-- Start :   non variants QOH & price -->
                                <div id="nonVariantQohPriceDiv">
                                    <div class="mb-3">
                                        <label class="form-label">Price (Rs.)</label>
                                        <input type="number" class="form-control" name="productPrice" min="0.01" step="0.01" value="<?= htmlspecialchars($editingProductPrice) ?>" />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">QOH</label>
                                        <input type="number" class="form-control" name="productQOH" min="1" value="<?= htmlspecialchars($editingProductQOH) ?>" />
                                    </div>
                                </div>
                                <!-- End :   non variants QOH & price -->

                                <div class="mb-3">
                                    <label class="form-label">Sub Category</label>
                                    <select class="form-control" name="subcategory_id" required>
                                        <option value="" disabled <?= !$editingSubCategoryId ? 'selected' : '' ?>>Select</option>
                                        <?php foreach ($subcategories as $subcategory): ?>
                                            <option value="<?= $subcategory['subcategory_id'] ?>" <?= $editingSubCategoryId == $subcategory['subcategory_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($subcategory['subcategory_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Image</label>
                                    <input type="file" class="form-control" name="productImage" accept="image/*" <?= !$editingProductId ? 'required' : '' ?> />
                                    <img id="previewImage" class="mt-2" src="<?= $editingProductImage ?>" style="<?= $editingProductImage ? 'max-width:150px;' : 'display:none; max-width:150px;' ?>" />
                                </div>

                                <div class="form-footer d-flex justify-content-end">
                                    <?php if ($editingProductId): ?>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmUpdateModal">Update</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-primary">Add</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="mt-5">
                    <h4>Product List</h4>
                    <div class="card shadow-sm rounded">
                        <div class="card-body bg-light p-4">
                            <!-- Search -->
                            <form method="GET" class="mb-3">
                                <div class="input-group">
                                    <input type="hidden" name="page" value="product_info">
                                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </form>

                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>QOH</th>
                                        <th>Sub Category</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No products found.</td>
                                        </tr>
                                        <?php else:
                                        $counter = isset($offset) ? $offset + 1 : 1;
                                        foreach ($products as $product): ?>
                                            <tr>
                                                <td><?= $counter++ ?></td>
                                                <td><img src="<?= htmlspecialchars($product['product_img']) ?>" width="60" height="60" /></td>
                                                <td class="text-wrap" style="max-width: 200px;">
                                                    <!-- text-wrap : Show multiple lines -->
                                                    <?= htmlspecialchars($product['product_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($product['product_price']) ?></td>
                                                <td><?= htmlspecialchars($product['product_qoh']) ?></td>
                                                <td><?= htmlspecialchars($product['subcategory_name']) ?></td>
                                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="editProductId" value="<?= $product['product_id'] ?>" />
                                                        <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                                                    </form>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= $product['product_id'] ?>">Delete</button>
                                                </td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>

                            <?php if (!$isSearchMode): ?>
                                <?php renderPaginationLinks($pagination['currentPage'], $pagination['totalPages'], $pagination['baseHref'], $pagination['queryPageParam']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ######################### [Start-footers & modals] ######################################## -->
<?php
renderConfirmationModal('confirmDeleteModal', 'Confirm Delete', 'Are you sure you want to delete this product?', '', 'deleteProductId', 'Delete', 'btn-danger');
?>

<div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Confirm Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to update this product?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmUpdateBtn" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    function redirectToDashboard() {
        const dashboards = {
            admin: "admin_dashboard",
            manager: "manager_dashboard",
            cashier: "cashier_dashboard",
            customer: "user_dashboard"
        };
        const role = "<?= $role ?>";
        window.location.href = dashboards[role] ? `index.php?page=${dashboards[role]}` : "#";
    }

    /*--- Start :   variants -- */
    document.addEventListener("DOMContentLoaded", function() {
        const hasVariantsCheckbox = document.getElementById("hasVariantsCheckbox");
        const nonVariantQohPriceDiv = document.getElementById("nonVariantQohPriceDiv");
        const variantTableDiv = document.getElementById("variantTableDiv");
        const addVariantBtn = document.getElementById("addVariantBtn");
        const variantTableBody = document.querySelector("#variantTable tbody");

        // Auto-expand if variants already exist in table
        const hasPreloadedVariants = document.querySelectorAll("#variantTable tbody tr").length > 0;
        toggleVariantSection(hasVariantsCheckbox.checked || hasPreloadedVariants);

        // Event Listener
        hasVariantsCheckbox.addEventListener("change", function() {
            toggleVariantSection(this.checked);
        });

        addVariantBtn.addEventListener("click", function() {
            const row = document.createElement("tr");

            row.innerHTML = `
            <td><input type="text" name="variant_name[]" class="form-control" required></td>
            <td><input type="number" name="variant_price[]" step="0.01" min="0.01" class="form-control" required></td>
            <td><input type="number" name="variant_qoh[]" min="0" class="form-control" required></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-variant">Remove</button></td>
        `;

            variantTableBody.appendChild(row);
        });

        // Remove row
        variantTableBody.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-variant")) {
                e.target.closest("tr").remove();
            }
        });

        function toggleVariantSection(hasVariants) {
            nonVariantQohPriceDiv.style.display = hasVariants ? "none" : "block";
            variantTableDiv.style.display = hasVariants ? "block" : "none";
        }
    });
    /*--- End   :   variants -- */

    document.addEventListener('DOMContentLoaded', () => {
        const preview = document.getElementById('previewImage');
        const imageInput = document.querySelector('input[name="productImage"]');

        if (imageInput && preview) {
            imageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    preview.src = URL.createObjectURL(file);
                    preview.style.display = 'block';
                }
            });
        }

        const updateBtn = document.getElementById('confirmUpdateBtn');
        if (updateBtn) {
            updateBtn.addEventListener('click', () => document.getElementById('productForm').submit());
        }

        const deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', (event) => {
                const productId = event.relatedTarget.getAttribute('data-id');
                deleteModal.querySelector('input[name="deleteProductId"]').value = productId;
            });
        }

        // Hide alerts after a few seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 3000);

        // Remove ?status= from URL
        const url = new URL(window.location);
        if (url.searchParams.has('status')) {
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    });
</script>

<!-- ######################### [End-footer part] ############################################### -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->