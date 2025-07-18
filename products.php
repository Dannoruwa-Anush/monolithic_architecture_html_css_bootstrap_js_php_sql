<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Products') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/category_queries.php');
include('queries/subcategory_queries.php');
include('queries/product_queries.php');
include('queries/product_variants_queries.php');
require_once 'common_components/tbl_pagination_UI/pagination.php';
require_once 'common_components/tbl_pagination_UI/pagination_links.php';

// ==================== PRG (Post/Redirect/Get): Redirect after POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_key'])) {
    $searchKey = trim($_POST['search_key']);
    $subcategoryId = $_POST['subcategory_id'] ?? '';

    // Build query parameters, omit empty subcategory
    $params = [
        'page' => 'products',
        'search_key' => $searchKey
    ];

    if (!empty($subcategoryId)) {
        $params['subcategory_id'] = $subcategoryId;
    }

    $query = http_build_query($params);
    header("Location: index.php?$query");
    exit;
}

// ==================== Input Parameters ====================
$selected_sub_category_id = $_GET['subcategory_id'] ?? '';
$searchKey = $_GET['search_key'] ?? '';

// ==================== Fetch Data ====================
$categories = getAllCategoriesWithAssociatedSubCategories($mysqli_conn);

if (!empty($searchKey)) {
    $products = getAllProductsMatchToSearchKey($mysqli_conn, $searchKey);
    foreach ($products as &$product) {
        if (!empty($product['has_variants'])) {
            $product['product_qoh'] = getVariantQOHsSumByProductId($mysqli_conn, $product['product_id']);
        }
    }
    $isSearchMode = true;
} elseif (!empty($selected_sub_category_id)) {
    $pagination = paginate(
        $mysqli_conn,
        function ($conn) use ($selected_sub_category_id) {
            return getTotalProductCountBySubCategory($conn, $selected_sub_category_id);
        },
        function ($conn, $limit, $offset) use ($selected_sub_category_id) {
            return getProductsBySubCategoryPaginated($conn, $selected_sub_category_id, $limit, $offset);
        },
        "?page=products&subcategory_id=" . urlencode($selected_sub_category_id),
        12
    );

    $products = $pagination['items'];
    foreach ($products as &$product) {
        if (!empty($product['has_variants'])) {
            $product['product_qoh'] = getVariantQOHsSumByProductId($mysqli_conn, $product['product_id']);
        }
    }
    unset($product);

    $isSearchMode = false;
} else {
    $pagination = paginate(
        $mysqli_conn,
        'getTotalProductCount',
        'getProductsPaginated',
        '?page=products',
        12
    );

    $products = $pagination['items'];
    foreach ($products as &$product) {
        if (!empty($product['has_variants'])) {
            $product['product_qoh'] = getVariantQOHsSumByProductId($mysqli_conn, $product['product_id']);
        }
    }
    unset($product);

    $offset = $pagination['offset'];
    $isSearchMode = false;
}
?>

<!-- ######################### [Start-body/products part] ##################################### -->
<div class="container-fluid px-0 mb-5">
    <div class="main-content-header mb-4">
        <h2>Our Products</h2>
    </div>

    <div class="row">
        <!-- Sidebar: Categories & Subcategories -->
        <div class="col-md-4">
            <div id="tab-content">
                <div class="nav flex-column nav-pills" id="v-tabs" role="tablist" aria-orientation="vertical">
                    <!-- Dropdowns inserted dynamically -->
                </div>
            </div>
        </div>

        <!-- Main Product Listing -->
        <div class="col-md-8">
            <div id="tab-content">
                <div class="row my-5 gy-1">
                    <form class="form d-flex" action="" method="post" id="productSearchForm" role="search">
                        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" name="search_key" id="search_key" value="<?= htmlspecialchars($searchKey) ?>">
                        <?php if (!empty($selected_sub_category_id)): ?>
                            <input type="hidden" name="subcategory_id" value="<?= htmlspecialchars($selected_sub_category_id) ?>">
                        <?php endif; ?>
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                    </form>
                </div>

                <div class="row gy-4">
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <p class="text-muted">No products found.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($products as $product): ?>
                        <div class="col-12 col-md-4">
                            <?php if ($product['product_qoh'] > 0): ?>
                                <a class="text-decoration-none text-dark d-block h-100" href="index.php?page=product&id=<?= urlencode($product['product_id']) ?>">
                                <?php endif; ?>

                                <div class="card shadow-sm rounded h-100 <?= $product['product_qoh'] == 0 ? 'opacity-50' : '' ?>">
                                    <img class="card-img-top py-2" src="<?= htmlspecialchars($product['product_img']) ?>" style="object-fit: cover; width: 100%; height: 250px;" alt="<?= htmlspecialchars($product['product_name']) ?>">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                                        <?php if (empty($product['has_variants'])): ?>
                                            <p class="fw-bold mb-1">Rs. <?= htmlspecialchars($product['product_price']) ?></p>
                                        <?php endif; ?>

                                        <?php if ($product['product_qoh'] == 0): ?>
                                            <p class="text-danger fw-bold mt-auto">Out of Stock</p>
                                        <?php else: ?>
                                            <p class="text-success mt-auto">In Stock: <?= htmlspecialchars($product['product_qoh']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($product['product_qoh'] > 0): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-end mt-5">
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

<!-- Sidebar script: Categories/Subcategories -->
<script>
    const categories = <?= json_encode($categories) ?>;
    const tabContainer = document.getElementById('v-tabs');

    categories.forEach((category) => {
        const dropdownDiv = document.createElement('div');
        dropdownDiv.className = 'dropdown mb-2';

        const dropdownButton = document.createElement('button');
        dropdownButton.className = 'btn btn-primary dropdown-toggle w-100 text-start';
        dropdownButton.type = 'button';
        dropdownButton.setAttribute('data-bs-toggle', 'dropdown');
        dropdownButton.setAttribute('aria-expanded', 'false');
        dropdownButton.textContent = category.category_name;

        if (category.subcategories && category.subcategories.length > 0) {
            const dropdownMenu = document.createElement('ul');
            dropdownMenu.className = 'dropdown-menu w-100';

            category.subcategories.forEach(subcategory => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.className = 'dropdown-item';
                a.href = `index.php?page=products&subcategory_id=${subcategory.subcategory_id}`;
                a.textContent = subcategory.subcategory_name;

                li.appendChild(a);
                dropdownMenu.appendChild(li);
            });

            dropdownDiv.appendChild(dropdownButton);
            dropdownDiv.appendChild(dropdownMenu);
        } else {
            dropdownDiv.appendChild(dropdownButton);
        }

        tabContainer.appendChild(dropdownDiv);
    });
</script>

<!-- ######################### [End-body/products part] ####################################### -->

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->