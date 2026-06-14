<?php
// Start output buffering to prevent "Headers Already Sent" errors across different hosting configurations
ob_start();

// PHP Database Connection Script
// Update these values with your actual shared hosting database credentials!

$db_host = 'localhost';
$db_user = 'root';          // Replace with your shared hosting DB username (e.g. u123456789_user)
$db_pass = '';              // Replace with your shared hosting DB password
$db_name = 'bookclub_db';    // Replace with your shared hosting DB name (e.g. u123456789_db)

// Enable error reporting to help you debug easily on hosting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create connection (try direct first, fallback without database to handle initial local setups)
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // If connection with database failed, try connecting without database to create it
    $conn = @mysqli_connect($db_host, $db_user, $db_pass);
    if (!$conn) {
        die("Database Connection failed: " . mysqli_connect_error());
    }
    
    // Attempt database creation (suppressed in case user has no global db-creation privileges on hosting)
    $create_db_query = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    @mysqli_query($conn, $create_db_query);
    
    // Now try selecting the database
    if (!@mysqli_select_db($conn, $db_name)) {
        die("Database selection failed. Make sure the database exists and your user credentials have access. Error: " . mysqli_error($conn));
    }
}

// Set charset to support emojis and international characters
mysqli_set_charset($conn, "utf8mb4");

// Start standard user session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Self-healing database tables installer: If users table doesn't exist, auto-import schema and seed data
$table_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$table_check || mysqli_num_rows($table_check) === 0) {
    $sql_file = __DIR__ . '/database.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Execute multi-query to create schema and insert seeds
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
