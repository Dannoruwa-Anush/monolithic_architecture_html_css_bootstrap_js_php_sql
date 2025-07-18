<?php
//----
function createProductsTableIfNotExists($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS 
            products(
                product_id INT AUTO_INCREMENT PRIMARY KEY,
                subcategory_id INT NOT NULL,
                product_name VARCHAR(200) NOT NULL UNIQUE,
                product_description TEXT NOT NULL,
                product_price DECIMAL(9,2),
                product_qoh INT(11),
                product_img TEXT NOT NULL,
                has_variants BOOLEAN DEFAULT FALSE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (subcategory_id) REFERENCES subcategories(subcategory_id)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE
            );";
    $conn->query($sql);
}
//----


//----
function validateProduct($product_name, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants, $variants = [])
{
    $product_name = trim($product_name);

    if (empty($product_img)) {
        return ['valid' => false, 'error' => 'Image cannot be empty.'];
    }

    if ($subcategory_id <= 0) {
        return ['valid' => false, 'error' => 'Subcategory cannot be empty.'];
    }

    if (empty($product_name)) {
        return ['valid' => false, 'error' => 'Product name cannot be empty.'];
    }

    if (!preg_match('/^[a-zA-Z0-9\s\'\-]+$/', $product_name)) {
        return ['valid' => false, 'error' => 'Product name must contain only letters, numbers, spaces, hyphens, or apostrophes.'];
    }

    if ($has_variants) {
        if (empty($variants)) {
            return ['valid' => false, 'error' => 'At least one variant must be provided.'];
        }

        foreach ($variants as $index => $variant) {
            $name = trim($variant['name']);
            $price = $variant['price'];
            $qoh = $variant['qoh'];

            if (empty($name)) {
                return ['valid' => false, 'error' => "Variant name in row " . ($index + 1) . " is empty."];
            }

            if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $name)) {
                return ['valid' => false, 'error' => "Variant name in row " . ($index + 1) . " has invalid characters."];
            }

            if ($price <= 0) {
                return ['valid' => false, 'error' => "Variant price in row " . ($index + 1) . " must be greater than 0."];
            }

            if ($qoh < 0) {
                return ['valid' => false, 'error' => "Variant QOH in row " . ($index + 1) . " cannot be negative."];
            }
        }
    } else {
        if ($product_price <= 0) {
            return ['valid' => false, 'error' => 'Price must be greater than 0.'];
        }

        if ($product_qoh < 0) {
            return ['valid' => false, 'error' => 'QOH cannot be negative.'];
        }
    }

    return ['valid' => true];
}
//----

//----
function insertProduct($conn, $product_name, $product_description, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants = false, $variants = [])
{
    $validation = validateProduct($product_name, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants, $variants);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    try {
        $conn->begin_transaction();

        $sql = "INSERT INTO products (product_name, product_description, product_price, product_qoh, product_img, subcategory_id, has_variants)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisii", $product_name, $product_description, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants);
        $stmt->execute();
        $product_id = $conn->insert_id;
        $stmt->close();

        // Insert variants if applicable
        if ($has_variants && !empty($variants)) {
            $sql = "INSERT INTO product_variants (product_id, variant_name, variant_price, variant_qoh)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($variants as $variant) {
                $stmt->bind_param("isdi", $product_id, $variant['name'], $variant['price'], $variant['qoh']);
                $stmt->execute();
            }
            $stmt->close();
        }

        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();

        if ($e->getCode() == 1062) {
            return ['success' => false, 'error' => 'Product name already exists.'];
        }

        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//----

//----
function updateProduct($conn, $product_id, $product_name, $product_description, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants = false, $variants = [])
{
    $validation = validateProduct($product_name, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants, $variants);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    try {
        $conn->begin_transaction();

        $sql = "UPDATE products SET product_name = ?, product_description = ?, product_price = ?, product_qoh = ?, product_img = ?, subcategory_id = ?, has_variants = ?
                WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisiii", $product_name, $product_description, $product_price, $product_qoh, $product_img, $subcategory_id, $has_variants, $product_id);
        $stmt->execute();
        $stmt->close();

        // Handle variants
        if ($has_variants) {
            // Delete existing variants
            $conn->query("DELETE FROM product_variants WHERE product_id = $product_id");

            // Insert new ones
            if (!empty($variants)) {
                $stmt = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, variant_price, variant_qoh)
                                        VALUES (?, ?, ?, ?)");
                foreach ($variants as $variant) {
                    $stmt->bind_param("isdi", $product_id, $variant['name'], $variant['price'], $variant['qoh']);
                    $stmt->execute();
                }
                $stmt->close();
            }
        } else {
            // If no variants, ensure variants are deleted
            $conn->query("DELETE FROM product_variants WHERE product_id = $product_id");
        }

        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();

        if ($e->getCode() == 1062) {
            return ['success' => false, 'error' => 'Product name already exists.'];
        }

        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//----


//----
function deleteProduct($conn, $product_id)
{
    try {
        $conn->begin_transaction();

        // Delete variants first
        $stmt = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        // Then delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        return false;
    }
}
//----


//----
function getAllProducts($conn)
{
    $sql = "SELECT * FROM products";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
//----

//----
function getProductsPaginated($conn, $limit, $offset)
{
    $sql = "
            SELECT 
                products.product_id,
                products.product_name,
                products.product_description,
                products.product_price,
                products.product_qoh,
                products.product_img,
                products.has_variants,     
                subcategories.subcategory_id,
                subcategories.subcategory_name,
                categories.category_id,
                categories.category_name
            FROM 
                products
            INNER JOIN 
                subcategories ON products.subcategory_id = subcategories.subcategory_id
            INNER JOIN 
                categories ON subcategories.category_id = categories.category_id
            ORDER BY 
                products.product_id DESC
            LIMIT ? OFFSET ?
        ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalProductCount($conn)
{
    $sql = "SELECT COUNT(*) AS total FROM products";
    $result = $conn->query($sql);
    return $result->fetch_assoc()['total'];
}

function getProductsBySubCategoryPaginated($conn, $subcategory_id, $limit, $offset)
{
    $sql = "
        SELECT 
            products.product_id,
            products.product_name,
            products.product_description,
            products.product_price,
            products.product_qoh,
            products.product_img,
            products.has_variants,
            subcategories.subcategory_id,
            subcategories.subcategory_name,
            categories.category_id,
            categories.category_name
        FROM 
            products
        INNER JOIN 
            subcategories ON products.subcategory_id = subcategories.subcategory_id
        INNER JOIN 
            categories ON subcategories.category_id = categories.category_id
        WHERE 
            products.subcategory_id = ?
        ORDER BY 
            products.product_id DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $subcategory_id, $limit, $offset);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalProductCountBySubCategory($conn, $subcategory_id)
{
    $sql = "SELECT COUNT(*) AS total FROM products WHERE subcategory_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}
//----

//----
function getAllProductsWithSubCategoriesNameAndCategoryName($conn)
{
    $sql = "
            SELECT 
                products.product_id,
                products.product_name,
                products.product_description,
                products.product_price,
                products.product_qoh,
                products.product_img,
                products.has_variants,        
                subcategories.subcategory_id,
                subcategories.subcategory_name,
                categories.category_id,
                categories.category_name
            FROM 
                products
            INNER JOIN 
                subcategories ON products.subcategory_id = subcategories.subcategory_id
            INNER JOIN 
                categories ON subcategories.category_id = categories.category_id
        ";

    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}
//----

//----
function getProductByIdWithSubCategoryAndCategory($conn, $product_id)
{
    $sql = "
            SELECT 
                products.product_id,
                products.product_name,
                products.product_description,
                products.product_price,
                products.product_qoh,
                products.product_img,
                products.has_variants,       
                subcategories.subcategory_id,
                subcategories.subcategory_name,
                categories.category_id,
                categories.category_name
            FROM 
                products
            INNER JOIN 
                subcategories ON products.subcategory_id = subcategories.subcategory_id
            INNER JOIN 
                categories ON subcategories.category_id = categories.category_id
            WHERE
                products.product_id = ?
            LIMIT 1
        ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_assoc(); // One row expected
}
//----

//----
function getProductByProductName($conn, $productName)
{
    $sql = "
        SELECT 
            products.product_id,
            products.product_name,
            products.product_description,
            products.product_price,
            products.product_qoh,
            products.product_img,
            products.has_variants,     
            products.has_variants,     
            products.has_variants,     
            products.has_variants,     
            subcategories.subcategory_id,
            subcategories.subcategory_name,
            categories.category_id,
            categories.category_name
        FROM 
            products
        INNER JOIN 
            subcategories ON products.subcategory_id = subcategories.subcategory_id
        INNER JOIN 
            categories ON subcategories.category_id = categories.category_id
        WHERE 
            products.product_name LIKE ?
    ";
    $stmt = $conn->prepare($sql);
    $likeParam = '%' . $productName . '%';
    $stmt->bind_param("s", $likeParam);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
//----

//----
function getProductById($conn, $product_id)
{
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
//----


//----
function getRecentlyAdded_4Products($conn)
{
    $sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 4";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
//----


//----
function getAllProductsBySubCategoryId($conn, $subcategory_id)
{
    $sql = "
            SELECT 
                products.product_id,
                products.product_name,
                products.product_description,
                products.product_price,
                products.product_qoh,
                products.product_img
            FROM 
                products 
            WHERE 
                subcategory_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $subcategory_id);
        $stmt->execute();

        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();

        return $products;
    } else {
        // Handle error (optional: log it or throw an exception)
        return [];
    }
}
//----


//----
function getAllProductsMatchToSearchKey($conn, $search_key)
{
    $search_key = "%{$search_key}%"; // For partial matches
    $sql = "SELECT * FROM products WHERE product_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_key);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    return $products;
}
//----

