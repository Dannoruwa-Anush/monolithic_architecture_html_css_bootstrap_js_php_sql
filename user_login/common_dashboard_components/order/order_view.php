<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Order Details') ?>
<!-- ######################### [End-header part] ############################################## -->

<?php
include("setup/config.php");
include("db_connection.php");
include('queries/customerOrder_queries.php');
include('common_components/modal/confirm_modal.php');

$current_user_role = $_SESSION['user_info']['role_name'] ?? 'guest';

if (isset($_GET['id'])) {
    $orderId = $_GET['id'];
    $orderDetails = getOrderByOrderId($mysqli_conn, $orderId);
    $current_order_status = htmlspecialchars($orderDetails['order_status']);
    $user_role = htmlspecialchars($orderDetails['role_name']);
}

// Initialize button visibility and next order status
$isCancelBtnVisible = false;
$isPrintBtnVisible = false;
$isProcessBtnVisible = false;
$next_order_status = '';

if ($current_order_status) {
    switch ($current_order_status) {
        case 'pending':
            $next_order_status = 'shipped';

            // Show cancel only for logged-in customer
            if ($current_user_role === 'customer') {
                $isCancelBtnVisible = true;
            }

            // Show process only for admin or manager
            if (in_array($current_user_role, ['admin', 'manager'])) {
                $isProcessBtnVisible = true;
            }

            $isPrintBtnVisible = true;
            break;

        case 'shipped':
            $next_order_status = 'delivered';

            // Only admin or manager can process to delivered
            if (in_array($current_user_role, ['admin', 'manager'])) {
                $isProcessBtnVisible = true;
            }
            break;
    }
}


// Handle Cancel Order
$error_message = '';
$success_message = '';
if (isset($_POST['cancel_order'])) {
    $order_id = $_GET['id'];

    if (updateOrder($mysqli_conn, $order_id, 'cancelled')) {
        $success_message = 'Order is cancelled successfully';
        header('Location: index.php?page=order_info');
        exit;
    }

    $error_message = 'Error occurred while canceling the order';
}

// Handle Process Order
if (isset($_POST['process_order'])) {
    $order_id = $_GET['id'];

    if (updateOrder($mysqli_conn, $order_id, $next_order_status)) {
        $success_message = "Order is $next_order_status successfully";
        header('Location: index.php?page=order_info');
        exit;
    }

    $error_message = "Error occurred while $next_order_status the order";
}
?>

<!-- ######################### [Start- Order details part] ################################# -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="nav flex-column nav-pills">
                <button onclick="window.location.href='index.php?page=order_info'" class="nav-link">
                    <i class="fa fa-arrow-left"></i> Back To Orders
                </button>
            </div>
        </div>

        <div class="col-md-10">
            <div id="tab-content">
                <div class="card shadow-sm rounded p-4">

                    <!-- Error and Success Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div id="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php elseif (!empty($error_message)): ?>
                        <div id="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Title and Contact -->
                    <div class="text-center mb-2" style="font-size: 24px; font-weight: bold;">ONLINE HARDWARE</div>
                    <div class="text-center mb-3" style="font-size: 12px; font-weight: bold;">
                        Online Hardware, No 11, Colombo<br>
                        011 111 2222
                    </div>

                    <hr style="border: 1px solid #ccc; margin-bottom: 20px;">

                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <p><strong>Invoice No:</strong> #<?= htmlspecialchars($orderDetails['order_id']) ?></p>
                        </div>

                        <div style="line-height: 1.6; background-color: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 0 5px rgba(0,0,0,0.1); font-size: 14px;">
                            <p style="font-weight: bold; margin-bottom: 8px;">Customer</p>
                            <p style="margin: 2px 0;"><?= htmlspecialchars($orderDetails['user_name']) ?></p>
                            <p style="margin: 2px 0;"><?= htmlspecialchars($orderDetails['user_address']) ?></p>
                            <p style="margin: 2px 0;"><?= htmlspecialchars($orderDetails['user_telephone_no']) ?></p>
                        </div>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; padding: 8px;">NO</th>
                                <th style="border: 1px solid #000; padding: 8px;">Product Name</th>
                                <th style="border: 1px solid #000; padding: 8px;">Variant</th>
                                <th style="border: 1px solid #000; padding: 8px;">Unit Price</th>
                                <th style="border: 1px solid #000; padding: 8px;">Qty</th>
                                <th style="border: 1px solid #000; padding: 8px;">Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            foreach ($orderDetails['products'] as $item): ?>
                                <tr>
                                    <td style="border: 1px solid #000; padding: 8px;"><?= $counter ?></td>
                                    <td style="border: 1px solid #000; padding: 8px;"><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td style="border: 1px solid #000; padding: 8px;"><?= htmlspecialchars($item['variant_name']) ?></td>
                                    <td style="border: 1px solid #000; padding: 8px;"><?= htmlspecialchars($item['product_price']) ?></td>
                                    <td style="border: 1px solid #000; padding: 8px;"><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td style="border: 1px solid #000; padding: 8px;"><?= htmlspecialchars($item['sub_total_amount']) ?></td>
                                </tr>
                            <?php $counter++;
                            endforeach; ?>
                        </tbody>
                    </table>

                    <div style="text-align: right; font-size: 22px; font-weight: bold; color: red;">
                        <p><strong>Total:</strong> (Rs.) <?= htmlspecialchars($orderDetails['total_amount']) ?></p>
                    </div>
                </div>

                <div class="container mt-3 d-flex justify-content-end gap-3">
                    <!-- Cancel Order Button with Modal -->
                    <?php if ($isCancelBtnVisible): ?>
                        <form id="cancelForm" method="POST">
                            <input type="hidden" name="cancel_order" value="1">
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelConfirmModal">Cancel Order</button>
                        </form>
                    <?php endif; ?>

                    <!-- Process Order Button with Modal -->
                    <?php if ($isProcessBtnVisible): ?>
                        <form id="processForm" method="POST">
                            <input type="hidden" name="process_order" value="1">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processConfirmModal">
                                Process : <?= $next_order_status ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Export to PDF using TCPDF -->
                    <?php if ($isPrintBtnVisible): ?>
                        <a class="btn btn-secondary" href="index.php?page=export_invoice_pdf&id=<?= $orderId ?>" target="_blank">
                            Export Invoice PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ######################### [End- Order details part] ################################# -->

<!-- jQuery & Bootstrap JS (if not already included) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Auto fade alert messages
    $(document).ready(function() {
        setTimeout(function() {
            $('#successMessage, #errorMessage').fadeOut('slow');
        }, 5000);
    });
</script>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-labelledby="cancelConfirmLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="cancelConfirmLabel">Confirm Cancel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to cancel this order?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" id="confirmCancelBtn" class="btn btn-warning">Yes, Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Process Confirmation Modal -->
<div class="modal fade" id="processConfirmModal" tabindex="-1" aria-labelledby="processConfirmLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="processConfirmLabel">Confirm Process</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to process this order to <strong><?= htmlspecialchars($next_order_status) ?></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" id="confirmProcessBtn" class="btn btn-primary">Yes, Process</button>
            </div>
        </div>
    </div>
</div>

<!-- Handle Modal Confirmations -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('confirmCancelBtn')?.addEventListener('click', function() {
            document.getElementById('cancelForm')?.submit();
        });

        document.getElementById('confirmProcessBtn')?.addEventListener('click', function() {
            document.getElementById('processForm')?.submit();
        });
    });
</script>

<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->