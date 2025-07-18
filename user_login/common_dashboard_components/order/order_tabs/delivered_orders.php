<?php

// Include the database connection and functions
include("setup/config.php");
include("db_connection.php");
include('queries/customerOrder_queries.php');
require_once 'common_components/tbl_pagination_UI/pagination.php';
require_once 'common_components/tbl_pagination_UI/pagination_links.php';

define('ORDER_STATUS_DELIVERED', 'delivered'); //constant
$order_status = ORDER_STATUS_DELIVERED;

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch orders
if (!empty($searchQuery)) {
    $delivered_orders = getAllOrdersMatchToSearchKey($mysqli_conn, $order_status, $searchQuery);
    $isSearchMode = true;
} else {
    $pagination = paginate(
        $mysqli_conn,
        function($conn) use ($order_status) {
            return getTotalOrderStatus($conn, $order_status);
        },
        function($conn, $limit, $offset) use ($order_status) {
            return getOrderStatusPaginated($conn, $order_status, $limit, $offset);
        },
        '?page=order_info',
        5 // No. items per page
    );

    $delivered_orders = $pagination['items'];
    $offset = $pagination['offset'];
    $isSearchMode = false;
}
?>

<!-- ######################### [Start table part] ##################################### -->
<div class="mt-5">
    <div class="card shadow-sm rounded">
        <div class="card-body">
            <div class="bg-light p-4">
                <form id="searchForm" method="GET" action="" class="mb-3">
                    <div class="input-group">
                        <input type="hidden" name="page" value="order_info">
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by Invoice No or Order Date..." value="<?= htmlspecialchars($searchQuery) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice No</th>
                            <th>Total Amount (Rs.)</th>
                            <th>Order Placed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($delivered_orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No delivered orders found.</td>
                            </tr>
                            <?php else:
                            $counter = isset($offset) ? $offset + 1 : 1;
                            foreach ($delivered_orders as $order): ?>
                                <tr id="orderRow<?= $order['order_id'] ?>">
                                    <td><?= $counter ?></td>
                                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                                    <td><?= htmlspecialchars($order['total_amount']) ?></td>
                                    <td><?= htmlspecialchars($order['order_date']) ?></td>
                                    <td><button class="btn btn-primary view-btn" data-id=<?= htmlspecialchars($order['order_id']) ?>>View</button></td>
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
                <?php endif; 
                ?>
            </div>
        </div>
    </div>
</div>
<!-- ######################### [End-table part] ##################################### -->