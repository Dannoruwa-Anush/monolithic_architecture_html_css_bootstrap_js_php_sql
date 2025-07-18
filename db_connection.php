<?php 

/*----------------- [Start-Connect to MySQL Database] -----------------------*/
include("setup/db_config.php");

$db_host = "localhost";
$db_name = "online_shopping_db";
$db_userName = "root";
$db_password = "";

$mysqli_conn = mysqli_connect_mysql($db_host, $db_name, $db_userName, $db_password); 
/*----------------- [End-Connect to MySQL Database] -------------------------*/

?>