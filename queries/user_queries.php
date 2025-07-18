<?php

//--------------------
function createUsersTableIfNotExists($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT,
        role_id INT,
        user_name VARCHAR(100) NOT NULL,
        user_password VARCHAR(255) NOT NULL,
        user_email VARCHAR(100) UNIQUE,
        user_address VARCHAR(100) NOT NULL,
        user_telephone_no VARCHAR(100) NOT NULL,
        PRIMARY KEY(user_id),
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    );";
    $conn->query($sql);
}
//--------------------


//----
function validateUser($user_name, $user_password, $user_email, $user_address, $user_telephone_no, $role_id)
{
    $user_name = trim($user_name);
    $user_password = trim($user_password);

    if ($role_id <= 0) {
        return ['valid' => false, 'error' => 'Role cannot be empty.'];
    }

    if (empty($user_name)) {
        return ['valid' => false, 'error' => 'Username cannot be empty or just spaces.'];
    }

    if (!preg_match('/^[a-zA-Z\s]+$/', $user_name)) {
        return ['valid' => false, 'error' => 'Username must contain only letters and spaces.'];
    }

    if (empty($user_password)) {
        return ['valid' => false, 'error' => 'Password cannot be empty.'];
    }

    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Invalid email format.'];
    }

    if (empty(trim($user_address))) {
        return ['valid' => false, 'error' => 'Address cannot be empty.'];
    }

    if (!preg_match('/^\d{10}$/', $user_telephone_no)) {
        return ['valid' => false, 'error' => 'Telephone number must be exactly 10 digits.'];
    }

    return ['valid' => true];
}
//----

//----
function validateCustomerProfileForUpdate($user_name, $user_address, $user_telephone_no)
{
    $user_name = trim($user_name);

    if (empty($user_name)) {
        return ['valid' => false, 'error' => 'Username cannot be empty or just spaces.'];
    }

    if (!preg_match('/^[a-zA-Z\s]+$/', $user_name)) {
        return ['valid' => false, 'error' => 'Username must contain only letters and spaces.'];
    }

    if (empty(trim($user_address))) {
        return ['valid' => false, 'error' => 'Address cannot be empty.'];
    }

    if (!preg_match('/^\d{10}$/', $user_telephone_no)) {
        return ['valid' => false, 'error' => 'Telephone number must be exactly 10 digits.'];
    }

    return ['valid' => true];
}
//----

//----
function validateEmployeeProfileForUpdate($user_name, $user_address, $user_telephone_no, $role_id)
{
    $user_name = trim($user_name);

    if ($role_id <= 0) {
        return ['valid' => false, 'error' => 'Role cannot be empty.'];
    }

    if (empty($user_name)) {
        return ['valid' => false, 'error' => 'Username cannot be empty or just spaces.'];
    }

    if (!preg_match('/^[a-zA-Z\s]+$/', $user_name)) {
        return ['valid' => false, 'error' => 'Username must contain only letters and spaces.'];
    }

    if (empty(trim($user_address))) {
        return ['valid' => false, 'error' => 'Address cannot be empty.'];
    }

    if (!preg_match('/^\d{10}$/', $user_telephone_no)) {
        return ['valid' => false, 'error' => 'Telephone number must be exactly 10 digits.'];
    }

    return ['valid' => true];
}
//----

//--------------------
function insertUser($conn, $user_name, $user_password, $user_email, $user_address, $user_telephone_no, $role_id)
{
    // If role_id is 0, assign the role_id for 'customer'
    if ($role_id == 0) {
        $sql = "SELECT role_id FROM roles WHERE role_name = 'customer' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $role_id = $row['role_id'];
        } else {
            return ['success' => false, 'error' => "'customer' role not found"];
        }
    }

    $validation = validateUser($user_name, $user_password, $user_email, $user_address, $user_telephone_no, $role_id);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    try {
        $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (user_name, user_password, user_email, user_address, user_telephone_no, role_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $user_name, $hashed_password, $user_email, $user_address, $user_telephone_no, $role_id);
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
            return ['success' => false, 'error' => 'Email already exists.'];
        }
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//--------------------


//--------------------
function updateUserInfo($conn, $user_id, $user_name, $user_email, $user_address, $user_telephone_no, $role_id)
{
    $sql = "UPDATE users 
            SET user_name = ?, user_email = ?, user_address = ?, user_telephone_no = ?, role_id = ? 
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $user_name, $user_email, $user_address, $user_telephone_no, $role_id, $user_id);
    return $stmt->execute();
}
//--------------------


//--------------------
function updateCustomerInfo($conn, $user_id, $user_name, $user_address, $user_telephone_no)
{
    $validation = validateCustomerProfileForUpdate($user_name, $user_address, $user_telephone_no);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    try {
        $sql = "UPDATE users 
            SET user_name = ?, user_address = ?, user_telephone_no = ?
            WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $user_name, $user_address, $user_telephone_no, $user_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to update profile.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//--------------------


//--------------------
function updateEmployeeInfo($conn, $user_id, $user_name, $user_address, $user_telephone_no, $role_id)
{
    $validation = validateEmployeeProfileForUpdate($user_name, $user_address, $user_telephone_no, $role_id);

    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    try {
        $sql = "UPDATE users 
            SET user_name = ?, user_address = ?, user_telephone_no = ?, role_id = ? 
            WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $user_name, $user_address, $user_telephone_no, $role_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to update employee.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
//--------------------


//--------------------
function updateUserPassword($conn, $user_id, $new_password)
{
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET user_password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    return $stmt->execute();
}
//--------------------


//----
function deleteStaffUser($conn, $user_id)
{
    try {
        $sql = "DELETE FROM users 
                WHERE user_id = ? 
                AND role_id IN (
                    SELECT role_id FROM roles WHERE role_name IN ('manager', 'cashier')
                )";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}
//----


//--------------------
// Get All Staff Users (excluding admin & customer)
function getAllStaffUsers($conn)
{
    $sql = "SELECT 
                u.user_name,
                u.user_email,
                u.user_address,
                u.user_telephone_no,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE r.role_name IN ('manager', 'cashier')";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
//--------------------


//----
function getStaffUsersPaginated($conn, $limit, $offset)
{
    $sql = "SELECT 
                u.user_id,
                u.user_name,
                u.user_email,
                u.user_address,
                u.user_telephone_no,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE r.role_name IN ('manager', 'cashier')
            ORDER BY u.user_name DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalStaffUserCount($conn)
{
    $sql = "SELECT COUNT(*) AS total
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE r.role_name IN ('manager', 'cashier')";

    $result = $conn->query($sql);
    return $result->fetch_assoc()['total'];
}
//----

//--------------------
function getUserById($conn, $user_id)
{
    $sql = "SELECT u.user_name, u.user_email, u.user_address, u.user_telephone_no, r.role_id, r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
//--------------------

//--------------------
function getStaffUserByUserName($conn, $userName)
{
    $sql = "SELECT 
                u.user_id,
                u.user_name,
                u.user_email,
                u.user_address,
                u.user_telephone_no,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE r.role_name IN ('manager', 'cashier')
              AND u.user_name LIKE ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return []; // Always return an array
    }

    $likeParam = '%' . $userName . '%';
    $stmt->bind_param("s", $likeParam);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        return [];
    }

    $employees = $result->fetch_all(MYSQLI_ASSOC);
    return is_array($employees) ? $employees : [];
}
//--------------------

//--------------------
function authenticateUser($conn, $user_email, $user_password)
{
    $stmt = $conn->prepare("SELECT 
                                u.user_id,
                                u.user_name,
                                u.user_password,
                                r.role_name
                            FROM users u
                            INNER JOIN roles r ON u.role_id = r.role_id
                            WHERE u.user_email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($user_password, $user['user_password'])) {
            // Success
            return [
                'user_id' => $user['user_id'],
                'user_name' => $user['user_name'],
                'role_name' => $user['role_name']
            ];
        }
    }

    return false; // Fail
}
//--------------------
