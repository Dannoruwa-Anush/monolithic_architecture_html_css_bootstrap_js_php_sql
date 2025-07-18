<?php

//----
function createCustomerOrdersTableIfNotExists($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS customer_orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            total_amount DECIMAL(9, 2) NOT NULL, 
            order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            order_status VARCHAR(100) NOT NULL,
            date_shipped DATE,  
            date_delivered DATE, 
            FOREIGN KEY (customer_id) REFERENCES users(user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        );";
    $conn->query($sql);
}
//----


//----
function insertOrder($conn, $customer_id, $cart_Item_arr)
{
    $conn->begin_transaction();

    try {
        $total_amount = 0.0;
        $item_data = []; // Collects price, qty, etc. for inserting later

        foreach ($cart_Item_arr as $cart_key => $item) {
            $product_id = intval($item['product_id']);
            $variant_id = $item['variant_id'] !== null ? intval($item['variant_id']) : null;
            $qty = intval($item['qty']);
            $price = null;
            $available_qoh = null;

            if ($variant_id) {
                // Variant logic
                $sql = "SELECT variant_price, variant_qoh FROM product_variants WHERE variant_id = ? AND product_id = ? FOR UPDATE";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $variant_id, $product_id);
                $stmt->execute();
                $stmt->bind_result($price, $available_qoh);
                $found = $stmt->fetch();
                $stmt->close();

                if (!$found || $price === null || $available_qoh === null) {
                    throw new Exception("Variant ID $variant_id not found or incomplete.");
                }

                if ($available_qoh < $qty) {
                    throw new Exception("Insufficient stock for Variant ID $variant_id.");
                }
            } else {
                // Product logic
                $sql = "SELECT product_price, product_qoh FROM products WHERE product_id = ? FOR UPDATE";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $stmt->bind_result($price, $available_qoh);
                $found = $stmt->fetch();
                $stmt->close();

                if (!$found || $price === null || $available_qoh === null) {
                    throw new Exception("Product ID $product_id not found or incomplete.");
                }

                if ($available_qoh < $qty) {
                    throw new Exception("Insufficient stock for Product ID $product_id.");
                }
            }

            $sub_total = round($price * $qty, 2);
            $total_amount += $sub_total;

            $item_data[] = [
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'qty' => $qty,
                'price' => $price,
                'sub_total' => $sub_total
            ];
        }

        $total_amount = round($total_amount, 2);

        // Step 2: Insert into customer_orders
        $sql_order = "INSERT INTO customer_orders (customer_id, total_amount, order_status) VALUES (?, ?, ?)";
        $stmt_order = $conn->prepare($sql_order);
        $status = "pending";
        $stmt_order->bind_param("ids", $customer_id, $total_amount, $status);
        if (!$stmt_order->execute()) {
            throw new Exception("Failed to insert order.");
        }

        $order_id = $conn->insert_id;

        // Step 3: Insert into order_products & update QOH
        $sql_insert = "INSERT INTO order_products (order_id, product_id, variant_id, quantity, sub_total_amount) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);

        $sql_update_product = "UPDATE products SET product_qoh = product_qoh - ? WHERE product_id = ?";
        $stmt_update_product = $conn->prepare($sql_update_product);

        $sql_update_variant = "UPDATE product_variants SET variant_qoh = variant_qoh - ? WHERE variant_id = ?";
        $stmt_update_variant = $conn->prepare($sql_update_variant);

        foreach ($item_data as $item) {
            $stmt_insert->bind_param(
                "iiiid",
                $order_id,
                $item['product_id'],
                $item['variant_id'],
                $item['qty'],
                $item['sub_total']
            );
            if (!$stmt_insert->execute()) {
                throw new Exception("Failed to insert order_products row.");
            }

            if ($item['variant_id']) {
                $stmt_update_variant->bind_param("ii", $item['qty'], $item['variant_id']);
                if (!$stmt_update_variant->execute()) {
                    throw new Exception("Failed to update variant stock.");
                }
            } else {
                $stmt_update_product->bind_param("ii", $item['qty'], $item['product_id']);
                if (!$stmt_update_product->execute()) {
                    throw new Exception("Failed to update product stock.");
                }
            }
        }

        // Commit all
        $conn->commit();

        // Cleanup
        $stmt_order->close();
        $stmt_insert->close();
        $stmt_update_product->close();
        $stmt_update_variant->close();

        return $order_id;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Order Error: " . $e->getMessage());
        return false;
    }
}
//----


//----
function getAllOrderStatus($conn, $order_status)
{
    $sql = "
        SELECT 
            co.order_id,
            co.total_amount,
            co.order_date,
            GROUP_CONCAT(
                CASE 
                    WHEN pv.variant_name IS NOT NULL 
                        THEN CONCAT(p.product_name, ' - ', pv.variant_name) 
                    ELSE p.product_name 
                END 
                SEPARATOR ', '
            ) AS product_list
        FROM 
            customer_orders co
        JOIN order_products op ON co.order_id = op.order_id
        LEFT JOIN products p ON op.product_id = p.product_id
        LEFT JOIN product_variants pv ON op.variant_id = pv.variant_id
        WHERE 
            co.order_status = ?
        GROUP BY 
            co.order_id
        ORDER BY 
            co.order_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $order_status);
    $stmt->execute();

    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    return $orders;
}
//----


//----
function getOrderByOrderId($conn, $order_id)
{
    $sql = "
        SELECT 
            co.order_id,
            co.total_amount,
            co.order_date,
            co.order_status,

            u.user_name,
            u.user_email,
            u.user_address,
            u.user_telephone_no,
            u.role_id,

            op.quantity,
            op.sub_total_amount,

            p.product_id,
            p.product_name,
            p.product_price,

            pv.variant_id,
            pv.variant_name,
            pv.variant_price
        FROM 
            customer_orders co
        INNER JOIN users u ON co.customer_id = u.user_id
        INNER JOIN order_products op ON co.order_id = op.order_id
        INNER JOIN products p ON op.product_id = p.product_id
        LEFT JOIN product_variants pv ON op.variant_id = pv.variant_id
        WHERE
            co.order_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $order = null;
    $products = [];

    while ($row = $result->fetch_assoc()) {
        if ($order === null) {
            $order = [
                'order_id' => $row['order_id'],
                'total_amount' => $row['total_amount'],
                'order_date' => $row['order_date'],
                'order_status' => $row['order_status'],
                'user_name' => $row['user_name'],
                'user_email' => $row['user_email'],
                'user_address' => $row['user_address'],
                'user_telephone_no' => $row['user_telephone_no'],
                'role_id' => $row['role_id'],
            ];
        }

        // Append product with variant if exists
        $products[] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_price' => $row['product_price'],
            'quantity' => $row['quantity'],
            'sub_total_amount' => $row['sub_total_amount'],
            'variant_id' => $row['variant_id'],
            'variant_name' => $row['variant_name'],
            'variant_price' => $row['variant_price']
        ];
    }

    if ($order !== null) {
        // Get role name
        $role_sql = "SELECT role_name FROM roles WHERE role_id = ?";
        $role_stmt = $conn->prepare($role_sql);
        $role_stmt->bind_param("i", $order['role_id']);
        $role_stmt->execute();
        $role_result = $role_stmt->get_result();
        $role_row = $role_result->fetch_assoc();

        $order['role_name'] = $role_row['role_name'];
        $order['products'] = $products;
    }

    return $order;
}
//----

//----
function updateOrder($conn, $order_id, $setting_orderStatus)
{
    $conn->begin_transaction();

    try {
        // Get the current order status
        $sql_current_orderStatus = "SELECT order_status FROM customer_orders WHERE order_id = ?";
        $stmt = $conn->prepare($sql_current_orderStatus);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $current_orderStatus = $stmt->get_result()->fetch_assoc()['order_status'];

        if ($current_orderStatus == 'pending') {
            if ($setting_orderStatus == 'shipped') {
                $sql = "UPDATE customer_orders SET order_status = ?, date_shipped = CURDATE() WHERE order_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $setting_orderStatus, $order_id);
            } elseif ($setting_orderStatus == 'cancelled') {
                // Update order status
                $sql = "UPDATE customer_orders SET order_status = ? WHERE order_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $setting_orderStatus, $order_id);

                // Get products + variants in the order
                $sql_order_products = "SELECT product_id, variant_id, quantity FROM order_products WHERE order_id = ?";
                $stmt_order_products = $conn->prepare($sql_order_products);
                $stmt_order_products->bind_param("i", $order_id);
                $stmt_order_products->execute();
                $result = $stmt_order_products->get_result();

                while ($row = $result->fetch_assoc()) {
                    $product_id = $row['product_id'];
                    $variant_id = $row['variant_id'];
                    $qty = $row['quantity'];

                    if ($variant_id) {
                        // Update stock for variant
                        $sql_update_variant_qoh = "UPDATE product_variants SET variant_qoh = variant_qoh + ? WHERE variant_id = ?";
                        $stmt_variant = $conn->prepare($sql_update_variant_qoh);
                        $stmt_variant->bind_param("ii", $qty, $variant_id);
                        if (!$stmt_variant->execute()) {
                            throw new Exception("Failed to update variant stock for variant_id $variant_id.");
                        }
                    } else {
                        // Update stock for non-variant product
                        $sql_update_qoh = "UPDATE products SET product_qoh = product_qoh + ? WHERE product_id = ?";
                        $stmt_product = $conn->prepare($sql_update_qoh);
                        $stmt_product->bind_param("ii", $qty, $product_id);
                        if (!$stmt_product->execute()) {
                            throw new Exception("Failed to update stock for product_id $product_id.");
                        }
                    }
                }
            } else {
                throw new Exception("Invalid status transition for pending order.");
            }
        } elseif ($current_orderStatus == 'shipped') {
            if ($setting_orderStatus == 'delivered') {
                $sql = "UPDATE customer_orders SET order_status = ?, date_delivered = CURDATE() WHERE order_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $setting_orderStatus, $order_id);
            } else {
                throw new Exception("Shipped orders can only be updated to 'delivered'.");
            }
        } else {
            throw new Exception("Orders in status '$current_orderStatus' cannot be updated.");
        }

        // Final status update execution
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order status.");
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Order Update Error: " . $e->getMessage());
        return false;
    }
}
//----

//----
function getAllOrdersMatchToSearchKey($conn, $order_status, $search_key)
{
    $orders = [];

    if (is_numeric($search_key)) {
        // Search by order_id
        $sql = "
            SELECT DISTINCT co.*
            FROM customer_orders co
            WHERE co.order_id = ? AND co.order_status = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $search_key, $order_status);
    } else {
        // Partial search in product name, variant name, or order date
        $search_key = "%{$search_key}%";
        $sql = "
            SELECT DISTINCT co.*
            FROM customer_orders co
            JOIN order_products op ON co.order_id = op.order_id
            LEFT JOIN products p ON op.product_id = p.product_id
            LEFT JOIN product_variants pv ON op.variant_id = pv.variant_id
            WHERE co.order_status = ?
              AND (
                    co.order_date LIKE ?
                    OR p.product_name LIKE ?
                    OR pv.variant_name LIKE ?
                )
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $order_status, $search_key, $search_key, $search_key);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    return $orders;
}
//----


//----
function getOrderStatusPaginated($conn, $order_status, $limit, $offset)
{
    $sql = "
        SELECT 
            co.order_id,
            co.total_amount,
            co.order_date,
            u.user_name,
            u.user_email
        FROM 
            customer_orders co
        INNER JOIN users u ON co.customer_id = u.user_id
        WHERE 
            co.order_status = ?
        ORDER BY 
            co.order_date DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $order_status, $limit, $offset);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalOrderStatus($conn, $order_status)
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM customer_orders
        WHERE order_status = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $order_status);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}
//----

//----
function getDeliveredProductReport($conn, $start_date, $end_date)
{
    $sql = "
        SELECT 
            p.product_name,
            c.category_name,
            sc.subcategory_name,
            pv.variant_name,
            COALESCE(pv.variant_price, p.product_price) AS unit_price,
            SUM(op.quantity) AS qty,
            SUM(op.sub_total_amount) AS sub_total
        FROM 
            customer_orders co
        JOIN order_products op ON co.order_id = op.order_id
        JOIN products p ON op.product_id = p.product_id
        LEFT JOIN product_variants pv ON op.variant_id = pv.variant_id
        JOIN subcategories sc ON p.subcategory_id = sc.subcategory_id
        JOIN categories c ON sc.category_id = c.category_id
        WHERE 
            co.order_status = 'delivered'
            AND co.date_delivered BETWEEN ? AND ?
        GROUP BY 
            p.product_id, pv.variant_id
        ORDER BY 
            qty DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();

    $result = $stmt->get_result();
    $report = [];

    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }

    return $report;
}
//----
