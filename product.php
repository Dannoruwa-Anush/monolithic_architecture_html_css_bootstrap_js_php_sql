<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Product') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/product_queries.php');
include('queries/product_variants_queries.php');


if (isset($_GET['id'])) {
  $product_id = (int)$_GET['id'];
  $product = getProductById($mysqli_conn, $product_id);
  $variants = $product['has_variants'] ? getVariantsByProductId($mysqli_conn, $product_id) : [];
}
?>

<!-- ######################### [Start-body/product part] ######################################### -->
<div class="card shadow-sm rounded mt-5 mb-3" style="width: 100%; min-height: 350px;">
  <div class="row g-0 align-items-center h-100">

    <div class="col-md-4 text-center p-3">
      <img src="<?= htmlspecialchars($product['product_img']) ?>" class="img-fluid rounded-start" style="max-height: 250px;" alt="<?= htmlspecialchars($product['product_name']) ?>">
    </div>

    <div class="col-md-8">
      <div class="card-body d-flex flex-column justify-content-center h-100">

        <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
        <p class="card-text"><?= htmlspecialchars($product['product_description']) ?></p>

        <?php if (empty($product['has_variants'])): ?>
          <p class="fw-bold mb-1">Rs. <?= htmlspecialchars($product['product_price']) ?></p>
        <?php endif; ?>

        <form action="index.php?page=cart" method="POST" class="d-flex flex-column mt-3" id="addToCartForm">
          <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
          <input type="hidden" name="add_to_cart" value="1">

          <?php if ($product['has_variants']): ?>
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Select</th>
                  <th>Variant</th>
                  <th>Price (Rs.)</th>
                  <th>QOH</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($variants as $variant): ?>
                  <tr>
                    <td>
                      <input type="radio" name="selected_variant_id"
                        value="<?= $variant['variant_id'] ?>"
                        data-qoh="<?= $variant['variant_qoh'] ?>"
                        <?= $variant['variant_qoh'] == 0 ? 'disabled' : '' ?>
                        required>
                    </td>
                    <td><?= htmlspecialchars($variant['variant_name']) ?></td>
                    <td><?= htmlspecialchars($variant['variant_price']) ?></td>
                    <td>
                      <?= $variant['variant_qoh'] == 0 ? '<span class="text-danger fw-bold">Out of Stock</span>' : htmlspecialchars($variant['variant_qoh']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

          <div class="d-flex align-items-center mt-3">
            <input type="number" name="product_purchased_qty" class="form-control me-2"
              placeholder="Qty" min="1" style="width: 80px;" id="qtyInput"
              <?= $product['has_variants'] ? 'disabled' : 'value="1" max="' . htmlspecialchars($product['product_qoh']) . '" ' ?>>

            <?php if (!$product['has_variants'] && $product['product_qoh'] == 0): ?>
              <button type="button" class="btn btn-secondary disabled">Out of Stock</button>
            <?php else: ?>
              <button type="submit" class="btn btn-success">Add to Cart</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="selected_variant_id"]');
    const qtyInput = document.getElementById('qtyInput');

    radios.forEach(radio => {
      radio.addEventListener('change', () => {
        const qoh = parseInt(radio.dataset.qoh);
        qtyInput.disabled = false;
        qtyInput.value = 1;
        qtyInput.max = qoh;
      });
    });
  });
</script>
<!-- ######################### [End-body/product part] ######################################### -->


<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->