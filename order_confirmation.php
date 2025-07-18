<!-- ######################### [Start-header part] ############################################ -->
<?= template_header('Order Confirmation') ?>
<!-- ######################### [End-header part] ############################################## -->


<
<!-- ######################### [End-body/order confirmation part] ############################# -->
<div class="container-fluid px-0 mb-5">
<div class="order-confirmation-container">
        <h5 class="order-confirmation-header">Order Confirmation</h5>
        <div class="order-confirmation-content">
            <p class="order-confirmation-greeting">
                Dear <strong><?= isset($_SESSION['user_info']['user_name']) ? htmlspecialchars($_SESSION['user_info']['user_name'], ENT_QUOTES, 'UTF-8') : '' ?></strong>,
            </p>
            <p class="order-confirmation-text">
                Thank you for your order with <strong>Online Hardware</strong>. We will promptly prepare your order and send you an email containing your payment details.
            </p>
            <p class="order-confirmation-link">
                You may view the specifics of your order by following this : 
                <a href="index.php?page=order_info" class="order-confirmation-link-text">Link</a>.
            </p>
            <p class="order-confirmation-signoff">
                Best regards,<br />
                <strong>Online Hardware</strong>
            </p>
        </div>
    </div>
<div>
<!-- ######################### [End-body/order confirmation part] ############################# -->


<!-- ######################### [Start-footer part] ############################################# -->
<?= template_footer() ?>
<!-- ######################### [End-footer part] ############################################### -->