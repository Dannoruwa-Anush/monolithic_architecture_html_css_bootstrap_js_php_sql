<?php

/*-----------------[Start-database connection function]----------------*/
function mysqli_connect_mysql($db_host, $db_name, $db_userName, $db_password)
{
    // Database configuration
    define('DB_HOST', $db_host);
    define('DB_NAME', $db_name);
    define('DB_USER', $db_userName);
    define('DB_PASS', $db_password);

    // Enable MySQLi error reporting for better debugging
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Create a new MySQLi connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // If connection is successful, output a JS console log
        echo "<script>console.log('Database connection successful');</script>";

        return $conn;
    } catch (mysqli_sql_exception $e) {

        echo "<script>console.log('Database connection failed');</script>";

        // Log the error and exit
        error_log("Database connection failed: " . $e->getMessage());
        exit("Database connection error. Please try again later.");
    }
}
/*-----------------[End-database connection function]------------------*/

?>

