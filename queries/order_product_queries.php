<?php

    //----
    function createOrder_ProductsTableIfNotExists($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS order_products (
                    order_product_id INT AUTO_INCREMENT PRIMARY KEY, 
                    order_id INT,
                    product_id INT NOT NULL,           -- Always set, even for variants
                    variant_id INT NULL,               -- NULL for non-variant items
                    quantity INT NOT NULL,
                    sub_total_amount DECIMAL(9, 2) NOT NULL, 
                    FOREIGN KEY (order_id) REFERENCES customer_orders(order_id)
                        ON DELETE RESTRICT
                        ON UPDATE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(product_id)
                        ON DELETE RESTRICT
                        ON UPDATE CASCADE,
                    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
                        ON DELETE RESTRICT
                        ON UPDATE CASCADE
                );";
        $conn->query($sql);
    }
    //----
?>