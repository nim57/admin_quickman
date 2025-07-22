<?php
// db_connection.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quickman_admin";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create categories table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create clients table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        logo VARCHAR(255),
        description TEXT,
        category_id INT DEFAULT NULL,
        badge ENUM('silver','gold','platinum','forget') DEFAULT 'silver',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");
    
    // Load Cloudinary SDK
    require 'vendor/autoload.php';
    
    // Configure Cloudinary (REPLACE WITH YOUR ACTUAL CREDENTIALS)
    $cloudinary = new \Cloudinary\Cloudinary([
        'cloud' => [
            'cloud_name' => 'dfgvylkpg',
            'api_key'    => '458566512632216',
            'api_secret' => 'lF3f5XNSDxn2dwrEjeM5wfKeWpY',
        ],
        'url' => [
            'secure' => true
        ]
    ]);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>