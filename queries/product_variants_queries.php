<?php

//----
function createProduct_VariantsTableIfNotExists($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS 
            product_variants (
                variant_id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                variant_name VARCHAR(100) NOT NULL,     -- e.g., '1L', '2m'
                variant_price DECIMAL(9,2) NOT NULL,
                variant_qoh INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(product_id)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE
            );";
    $conn->query($sql);
}
//----

//----
function getVariantsByProductId($conn, $product_id)
{
    $stmt = $conn->prepare("SELECT  variant_id, variant_name, variant_price, variant_qoh FROM product_variants WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $variants = [];

    while ($row = $result->fetch_assoc()) {
        $variants[] = $row;
    }

    $stmt->close();
    return $variants;
}
//----

//----
function getVariantQOHs($conn, $product_id)
{
    $stmt = $conn->prepare("SELECT variant_qoh FROM product_variants WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $qohs = [];
    while ($row = $result->fetch_assoc()) {
        $qohs[] = (int)$row['variant_qoh'];
    }
    $stmt->close();
    return $qohs;
}
//----

//----
function getVariantQOHsSumByProductId($conn, $product_id)
{
    $stmt = $conn->prepare("SELECT SUM(variant_qoh) AS total_qoh FROM product_variants WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total_qoh'] ?? 0);
}
//----

//----
function getVariantById($conn, $variant_id)
{
    $stmt = $conn->prepare("
        SELECT variant_id, variant_name, variant_price, variant_qoh 
        FROM product_variants 
        WHERE variant_id = ?
    ");
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
//----
?>