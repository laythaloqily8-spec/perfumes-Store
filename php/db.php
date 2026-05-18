<?php
/**
 * Database connection configuration.
 * Uses MySQLi with prepared statements for security.
 */
$db_host = 'localhost';
$db_name = 'perfume_store';
$db_user = 'root';
$db_pass = '';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset('utf8');
?>
