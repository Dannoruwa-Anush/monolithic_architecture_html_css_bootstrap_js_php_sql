<?php

//----
function createSubCategoriesTableIfNotExists($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS subcategories (
                subcategory_id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                subcategory_name VARCHAR(100) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(category_id)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE
            );";
    $conn->query($sql);
}
//----

//----
function validateSubCategory($subcategory_name, $category_id)
{
    $subcategory_name = trim($subcategory_name);

    if ($category_id <= 0) {
        return ['valid' => false, 'error' => 'Category cannot be empty.'];
    }

    if (empty($subcategory_name)) {
        return ['valid' => false, 'error' => 'Sub category name cannot be empty.'];
    }

    if (!preg_match('/^[a-zA-Z\s\'\-]+$/', $subcategory_name)) {
        return ['valid' => false, 'error' => 'Sub category name must contain only letters, spaces, hyphens, or apostrophes.'];
    }

    return ['valid' => true];
}
//----

//----
function insertSubCategory($conn, $subcategory_name, $category_id)
{
    $validation = validateSubCategory($subcategory_name, $category_id);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    try {
        $sql = "INSERT INTO subcategories (subcategory_name, category_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $subcategory_name, $category_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to insert sub category.'];
        }
    } catch (Exception $e) {
        if ($e->getCode() == 1062) {
            // SQL Error code for duplications: 1062
            return ['success' => false, 'error' => 'Sub category name already exists.'];
        }
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//----


//----
function updateSubCategory($conn, $subcategory_id, $subcategory_name, $category_id)
{
    $validation = validateSubCategory($subcategory_name, $category_id);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    try {
        $sql = "UPDATE subcategories SET subcategory_name = ?, category_id = ? WHERE subcategory_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $subcategory_name, $category_id, $subcategory_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to update sub category.'];
        }
    } catch (Exception $e) {
        if ($e->getCode() == 1062) {
            // SQL Error code for duplications: 1062
            return ['success' => false, 'error' => 'Sub category name already exists.'];
        }
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//----


//----
function deleteSubCategory($conn, $subcategory_id)
{
    try {
        $sql = "DELETE FROM subcategories WHERE subcategory_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $subcategory_id);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}
//----


//----
function getAllSubCategories($conn)
{
    $sql = "SELECT * FROM subcategories";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
//----

//----
function getSubCategoriesPaginated($conn, $limit, $offset)
{
    $sql = "
        SELECT 
            subcategories.subcategory_id,
            subcategories.subcategory_name,
            categories.category_id,
            categories.category_name
        FROM 
            subcategories
        INNER JOIN 
            categories ON subcategories.category_id = categories.category_id
        ORDER BY 
            subcategories.subcategory_id DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalSubCategoryCount($conn)
{
    $sql = "SELECT COUNT(*) AS total FROM subcategories";
    $result = $conn->query($sql);
    return $result->fetch_assoc()['total'];
}
//----

//----
function getAllSubCategoriesWithCategoryName($conn)
{
    $sql = "
            SELECT 
                subcategories.subcategory_id,
                subcategories.subcategory_name,
                categories.category_id,
                categories.category_name
            FROM 
                subcategories
            INNER JOIN 
                categories ON subcategories.category_id = categories.category_id
        ";

    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}
//----

//----
function getSubCategoryByIdWithCategory($conn, $subcategory_id)
{
    $sql = "
            SELECT 
                subcategories.subcategory_id,
                subcategories.subcategory_name,
                categories.category_id,
                categories.category_name
            FROM 
                subcategories
            INNER JOIN 
                categories ON subcategories.category_id = categories.category_id
            WHERE
                subcategories.subcategory_id = ?
            LIMIT 1
        ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_assoc(); // One row expected
}
//----

//----
function getSubCategoryById($conn, $subcategory_id)
{
    $sql = "SELECT * FROM subcategories WHERE subcategory_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
//----

//----
function getSubCategoryBySubCategoryName($conn, $SubCategoryName)
{
     $sql = "
            SELECT 
                subcategories.subcategory_id,
                subcategories.subcategory_name,
                categories.category_id,
                categories.category_name
            FROM 
                subcategories
            INNER JOIN 
                categories ON subcategories.category_id = categories.category_id
            WHERE subcategories.subcategory_name LIKE ?
        ";
    $stmt = $conn->prepare($sql);
    $likeParam = '%' . $SubCategoryName . '%';
    $stmt->bind_param("s", $likeParam);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
//----


//----
function getAllSubCategoriesByCategoryId($conn, $category_id)
{
    $sql = "
                SELECT 
                    subcategory_id, 
                    subcategory_name 
                FROM 
                    subcategories 
                WHERE 
                category_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();

    $result = $stmt->get_result();

    // Fetch all subcategories into an array
    $subcategories = [];
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }

    // Close statement
    $stmt->close();

    return $subcategories;
}
//----
