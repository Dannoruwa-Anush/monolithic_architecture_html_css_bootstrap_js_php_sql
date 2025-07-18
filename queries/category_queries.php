<?php

    //----
    function createCategoriesTableIfNotExists($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS categories (
                category_id INT AUTO_INCREMENT PRIMARY KEY,
                category_name VARCHAR(100) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );";
        $conn->query($sql);
    }
    //----

    //----
    function validateCategory($category_name) {
        $category_name = trim($category_name);

        // Check if empty
        if (empty($category_name)) {
            return ['valid' => false, 'error' => 'Category name cannot be empty.'];
        }

        // Check if it's letters only (no numbers, symbols, or whitespace)
        if (!preg_match('/^[a-zA-Z\s\'\-]+$/', $category_name)) {
            return ['valid' => false, 'error' => 'Category name must contain only letters, spaces, hyphens, or apostrophes.'];
        }

        return ['valid' => true];
    }
    //----

    //----
    function insertCategory($conn, $category_name) {
        $validation = validateCategory($category_name);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        try{
            $sql = "INSERT INTO categories (category_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_name);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to insert category.'];
            }
        } catch (Exception $e) {
            if ($e->getCode() == 1062) {
                // SQL Error code for duplications: 1062
                return ['success' => false, 'error' => 'Category name already exists.'];
            }
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }
    //----

    //----
    function updateCategory($conn, $category_id, $category_name) {
        $validation = validateCategory($category_name);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        try{
            $sql = "UPDATE categories SET category_name = ? WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $category_name, $category_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to update category.'];
            }
        } catch (Exception $e) {
            if ($e->getCode() == 1062) {
                // SQL Error code for duplications: 1062
                return ['success' => false, 'error' => 'Category name already exists.'];
            }
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }
    //----

    //----
    function deleteCategory($conn, $category_id) {
        try{
            $sql = "DELETE FROM categories WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $category_id);
            return $stmt->execute();
        }
        catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    //----

    //----
    function getAllCategories($conn) {
        $sql = "SELECT * FROM categories";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    //----

    //----
    function getCategoriesPaginated($conn, $limit, $offset) {
        $sql = "SELECT * FROM categories ORDER BY category_id DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    function getTotalCategoryCount($conn) {
        $sql = "SELECT COUNT(*) AS total FROM categories";
        $result = $conn->query($sql);
        return $result->fetch_assoc()['total'];
    }
    //----

    //----
    function getCategoryById($conn, $category_id) {
        $sql = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    //----

    //----
    function getCategoryByCategoryName($conn, $categoryName) {
        $sql = "SELECT * FROM categories WHERE category_name LIKE ?";
        $stmt = $conn->prepare($sql);
        $likeParam = '%' . $categoryName . '%';
        $stmt->bind_param("s", $likeParam);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    //----

    //----
    function getAllCategoriesWithAssociatedSubCategories($conn) {
        $sql = "
            SELECT 
                c.category_id, 
                c.category_name, 
                s.subcategory_id, 
                s.subcategory_name
            FROM 
                categories c
            LEFT JOIN 
                subcategories s ON c.category_id = s.category_id
            ORDER BY 
                c.category_name, s.subcategory_name
        ";
    
        $result = $conn->query($sql);
    
        $categories = [];
    
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cat_id = $row['category_id'];
                if (!isset($categories[$cat_id])) {
                    $categories[$cat_id] = [
                        'category_id' => $row['category_id'],
                        'category_name' => $row['category_name'],
                        'subcategories' => []
                    ];
                }
    
                if (!empty($row['subcategory_id'])) {
                    $categories[$cat_id]['subcategories'][] = [
                        'subcategory_id' => $row['subcategory_id'],
                        'subcategory_name' => $row['subcategory_name']
                    ];
                }
            }
        }
    
        return array_values($categories); // Reset keys for cleaner output
    }    
    //----
?>
