<?php
//----
function createRolesTableIfNotExists($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS roles (
                    role_id INT AUTO_INCREMENT PRIMARY KEY,
                    role_name VARCHAR(100) NOT NULL UNIQUE,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );";

    $conn->query($sql);
}
//----

//----
function insertDefaultRoles($conn)
{
    $sql = "INSERT INTO roles (role_name)
                VALUES ('admin'), ('manager'), ('cashier'), ('customer')
                ON DUPLICATE KEY UPDATE role_name = role_name;";
    $conn->query($sql);
}
//----

//----
function getAllRoles($conn)
{
    $sql = "SELECT * FROM roles";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
//----

//----
function getAllStaffRoles($conn)
{
    $sql = "SELECT * FROM roles WHERE role_name IN ('manager', 'cashier')";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
//----
