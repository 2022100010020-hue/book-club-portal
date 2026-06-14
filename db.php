<?php

ob_start();
$db_host = 'localhost';
$db_user = 'root';         
$db_pass = '';             
$db_name = 'bookclub_db';   
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    $conn = @mysqli_connect($db_host, $db_user, $db_pass);
    if (!$conn) {
        die("Database Connection failed: " . mysqli_connect_error());
    }
    $create_db_query = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    @mysqli_query($conn, $create_db_query);
    
    
    if (!@mysqli_select_db($conn, $db_name)) {
        die("Database selection failed. Make sure the database exists and your user credentials have access. Error: " . mysqli_error($conn));
    }
}
mysqli_set_charset($conn, "utf8mb4");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$table_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$table_check || mysqli_num_rows($table_check) === 0) {
    $sql_file = __DIR__ . '/database.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        if (mysqli_multi_query($conn, $sql_content)) {
            // Clear result sets to avoid database lock errors on subsequent queries
            do {
                if ($res = mysqli_store_result($conn)) {
                    mysqli_free_result($res);
                }
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        }
    }
}
?>
