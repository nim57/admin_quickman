<?php
session_start();
if(!isset($_SESSION['isAuthenticated'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quickman Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header Styles */
        .admin-header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #05A045;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .logo-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            position: relative;
        }
        
        .avatar-initial {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e6f4eb;
            color: #05A045;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .username {
            font-weight: 500;
            color: #4a5568;
        }
        
        .logout-btn {
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-btn:hover {
            background-color: #f8f9fa;
            color: #e53e3e;
        }
        
        /* Main Container */
        .admin-container {
            display: flex;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Sidebar Styles */
        .desktop-sidebar {
            width: 250px;
            background: linear-gradient(180deg, #04813a 0%, #05A045 100%);
            color: white;
            padding: 25px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            margin-bottom: 30px;
            margin-top: 15px;
        }
        
        .sidebar-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .sidebar-item.active {
            background-color: rgba(255, 255, 255, 0.25);
        }
        
        .sidebar-item i {
            width: 24px;
            text-align: center;
        }
        
        .sidebar-support {
            margin-top: 40px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        
        .sidebar-support h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .sidebar-support p {
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .support-btn {
            width: 100%;
            background-color: white;
            color: #05A045;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .support-btn:hover {
            background-color: #f8f9fa;
        }
        
        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 50;
            padding: 10px 0;
        }
        
        .mobile-nav {
            display: flex;
            justify-content: space-around;
        }
        
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #718096;
            font-size: 0.8rem;
            gap: 5px;
        }
        
        .mobile-nav-item i {
            font-size: 1.3rem;
        }
        
        .mobile-nav-item.active {
            color: #05A045;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 25px;
            background-color: #f8fafc;
            overflow-y: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .admin-container {
                flex-direction: column;
            }
            
            .desktop-sidebar {
                width: 100%;
                display: none;
            }
            
            .mobile-menu {
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .header-container {
                padding: 12px 15px;
            }
            
            .logo-text {
                font-size: 1.2rem;
            }
            
            .username {
                display: none;
            }
            
            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-container">
            <div class="header-logo">
                <div class="logo-icon">
                    <img src="app_icon.png" alt="App Icon">
                </div>
                <span class="logo-text">Quickman Admin</span>
            </div>
            
            <div class="header-user">
                <div class="user-avatar">
                    <div class="avatar-initial"><?php echo substr($username, 0, 1); ?></div>
                </div>
                <span class="username"><?php echo htmlspecialchars($username); ?></span>
                <button class="logout-btn" onclick="window.location.href='logout.php'" aria-label="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Main Admin Container -->
    <div class="admin-container">
        <!-- Desktop Sidebar -->
        <nav class="desktop-sidebar">
            <div class="sidebar-header">
                <h2>Navigation</h2>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="category.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i>
                        <span>Category</span>
                    </a>
                </li>
                <li>
                    <a href="clinet.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Client</span>
                    </a>
                </li>
        
            </ul>
            
            <div class="sidebar-support">
                <h3>Need Help?</h3>
                <p>Our support team is here to assist you</p>
                <button class="support-btn">
                    Contact Support
                </button>
            </div>
        </nav>
        
        <!-- Main Content Area -->
        <main class="main-content">