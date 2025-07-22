<?php
include_once 'db_connection.php';

// Check connection status
if ($db) {
    // Get database name from connection
    $db_name = mysqli_query($db, "SELECT DATABASE()")->fetch_row()[0];
    echo "Connected successfully to database: " . htmlspecialchars($db_name);
} else {
    echo "Connection failed";
}
?>