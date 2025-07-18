<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Home') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/product_queries.php');
include('queries/product_variants_queries.php');

$recently_added_4_products = getRecentlyAdded_4Products($mysqli_conn);

foreach ($recently_added_4_products as &$product) {
    if (!empty($product['has_variants'])) {
        $product['product_qoh'] = getVariantQOHsSumByProductId($mysqli_conn, $product['product_id']);
    }
}
unset($product);

?>

<!-- ######################### [Start-body/home part] ########################################### -->

<!-- ***** Slideshow ***** -->
<div class="container-fluid px-4 mb-5">
    <div id="tab-content">
        <div id="homePage-slideshow" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="carousel-img" src="images/home_slide_show/im_slide1.jpg" alt="Slide 1">
                </div>
                <div class="carousel-item">
                    <img class="carousel-img" src="images/home_slide_show/im_slide2.jpg" alt="Slide 2">
                </div>
                <div class="carousel-item">
                    <img class="carousel-img" src="images/home_slide_show/im_slide3.jpg" alt="Slide 3">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#homePage-slideshow" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homePage-slideshow" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </div>
</div>

<!-- ***** Recently Added Products ***** -->
<div class="container-fluid px-4 mb-5">
    <div id="tab-content">
        <div class="main-content-header mb-4">
            <h2>New Arrivals</h2>
        </div>

        <div class="row gy-4">
            <?php foreach ($recently_added_4_products as $product): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
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
    </div>
</div>
<!-- ######################### [End-body/home part] ########################################### -->

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->