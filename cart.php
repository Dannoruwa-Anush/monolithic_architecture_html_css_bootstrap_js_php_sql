<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Shopping Cart') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php

include("setup/config.php");
include("db_connection.php");
include('queries/product_queries.php');
include('queries/customerOrder_queries.php');
include('queries/order_product_queries.php');
include_once('queries/product_variants_queries.php');

createOrder_ProductsTableIfNotExists($mysqli_conn);
createCustomerOrdersTableIfNotExists($mysqli_conn);

// ==================== PRG (Post/Redirect/Get): Redirect after POST ====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Handle Add to Cart
    if (isset($_POST['add_to_cart'])) {
        if (isset($_POST['product_id'], $_POST['product_purchased_qty'])) {
            $productId = intval($_POST['product_id']);
            $productQTY = intval($_POST['product_purchased_qty']);
            $variantId = isset($_POST['selected_variant_id']) ? intval($_POST['selected_variant_id']) : null;

            if ($productQTY > 0) {
                $product = getProductById($mysqli_conn, $productId);

                if ($product) {
                    $cart_key = $variantId ? "{$productId}_{$variantId}" : (string)$productId;

                    if (!isset($_SESSION['cart_items']) || !is_array($_SESSION['cart_items'])) {
                        $_SESSION['cart_items'] = [];
                    }

                    if (isset($_SESSION['cart_items'][$cart_key])) {
                        $_SESSION['cart_items'][$cart_key]['qty'] += $productQTY;
                    } else {
                        $_SESSION['cart_items'][$cart_key] = [
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'qty' => $productQTY
                        ];
                    }
                }
            }
        }

        header('Location: index.php?page=cart');
        exit;
    }

    // Handle Update Cart
    if (isset($_POST['update_cart']) && isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $cart_key => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $_SESSION['cart_items'][$cart_key]['qty'] = $qty;
            } else {
                unset($_SESSION['cart_items'][$cart_key]);
            }
        }

        header('Location: index.php?page=cart');
        exit;
    }

    // Handle Remove Item
    if (isset($_POST['remove_item']) && isset($_POST['remove_id'])) {
        $removeId = $_POST['remove_id'];
        unset($_SESSION['cart_items'][$removeId]);
        header('Location: index.php?page=cart');
        exit;
    }

    // Handle Place Order
    if (isset($_POST['place_order'])) {
        if (empty($_SESSION['user_info'])) {
            $_SESSION['redirect_after_login'] = 'index.php?page=cart';
            header("Location: index.php?page=login");
            exit;
        } else {
            $customer_id = $_SESSION['user_info']['user_id'];
            $cart_Item_arr = $_SESSION['cart_items'];

            insertOrder($mysqli_conn, $customer_id, $cart_Item_arr);
            unset($_SESSION['cart_items']);

            header("Location: index.php?page=order_confirmation");
            exit;
        }
    }
}

// =========================== BUILD CART ITEMS FOR DISPLAY ==============================

$cart_items = [];
$subtotal = 0;

if (isset($_SESSION['cart_items']) && is_array($_SESSION['cart_items'])) {

    foreach ($_SESSION['cart_items'] as $cart_key => $item) {
        $product = getProductById($mysqli_conn, $item['product_id']);

        $variant = null;
        $price = $product['product_price'];
        $name = $product['product_name'];

        if (!empty($item['variant_id'])) {
            $variant = getVariantById($mysqli_conn, $item['variant_id']);
            if ($variant) {
                $price = $variant['variant_price'];
                $name .= " (" . $variant['variant_name'] . ")";
            }
        }

        $qty = $item['qty'];
        $sub_total = $price * $qty;
        $subtotal += $sub_total;

        $cart_items[] = [
            'cart_key'      => $cart_key,
            'product_id'    => $item['product_id'],
            'variant_id'    => $item['variant_id'],
            'product_name'  => $name,
            'product_img'   => $product['product_img'],
            'product_price' => $price,
            'qty'           => $qty,
            'sub_total'     => $sub_total,
        ];
    }
}
?>

<!-- ######################### [Start-body/cart part] ####################################### -->
<div class="container mt-5">
    <div class="main-content-header">
        <h2>Shopping Cart</h2>
    </div>

    <form action="index.php?page=cart" method="POST">
        <div class="card shadow-sm rounded">
            <div class="card-body">
                <table class="table table-borderless align-middle">
                    <thead class="fw-bold">
                        <tr>
                            <th>Product</th>
                            <th>Unit Price (Rs.)</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($cart_items)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Your cart is empty</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($item['product_img']) ?>" width="50" height="50" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </td>
                                    <td>Rs. <?= htmlspecialchars($item['product_price']) ?></td>
                                    <td style="width: 120px;">
                                        <input type="number" name="quantities[<?= $item['cart_key'] ?>]" class="form-control" min="1" value="<?= htmlspecialchars($item['qty']) ?>">
                                    </td>
                                    <td>Rs. <?= number_format($item['sub_total'], 2) ?></td>
                                    <td>
                                        <!-- Remove Button is a separate form, outside the main form -->
                                        <form action="index.php?page=cart" method="POST" style="display:inline;">
                                            <input type="hidden" name="remove_id" value="<?= $item['cart_key'] ?>">
                                            <button type="submit" name="remove_item" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($cart_items)): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h5>Subtotal: Rs. <?= number_format($subtotal, 2) ?></h5>
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
    </form>
    <form action="index.php?page=cart" method="POST">
        <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
    </form>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>

<!-- ######################### [End-body/cart part] ####################################### -->

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->