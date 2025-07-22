<?php
require_once 'db_connection.php';
require_once 'header.php';

// Get stats
$categoriesCount = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch(PDO::FETCH_ASSOC);
$clientsCount = $conn->query("SELECT COUNT(*) as count FROM clients")->fetch(PDO::FETCH_ASSOC);
$pendingCount = $conn->query("SELECT COUNT(*) as count FROM clients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch(PDO::FETCH_ASSOC);
$revenue = $conn->query("SELECT SUM(id) as total FROM clients")->fetch(PDO::FETCH_ASSOC); // Dummy revenue

// Get recent clients
$stmt = $conn->query("SELECT * FROM clients ORDER BY created_at DESC LIMIT 3");
$recentClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Dashboard Styles */
    .dashboard {
        padding: 25px;
        background-color: #f8fafc;
        min-height: calc(100vh - 80px);
    }
    
    .dashboard-header {
        margin-bottom: 30px;
    }
    
    .dashboard-header h1 {
        font-size: 2.2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 8px;
    }
    
    .dashboard-header p {
        font-size: 1.1rem;
        color: #718096;
        max-width: 600px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 25px;
        margin-bottom: 35px;
    }
    
    .stats-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        padding: 25px;
        transition: all 0.3s ease;
        border-left: 5px solid #05A045;
        position: relative;
        overflow: hidden;
    }
    
    .stats-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 25px rgba(5, 160, 69, 0.15);
    }
    
    .stats-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #05A045, #04813a);
        opacity: 0.8;
    }
    
    .stats-card-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    
    .stats-info {
        flex: 1;
    }
    
    .stats-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #718096;
        margin-bottom: 12px;
    }
    
    .stats-card .value {
        font-size: 2.2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 8px;
    }
    
    .stats-card .trend {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        font-weight: 500;
    }
    
    .trend.up {
        color: #38a169;
    }
    
    .trend.down {
        color: #e53e3e;
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: #e6f4eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #05A045;
        font-size: 1.8rem;
    }
    
    .dashboard-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
    }
    
    @media (max-width: 992px) {
        .dashboard-content {
            grid-template-columns: 1fr;
        }
    }
    
    .dashboard-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        padding: 25px;
        margin-bottom: 25px;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #edf2f7;
    }
    
    .card-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
    }
    
    .card-header a {
        color: #05A045;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
    }
    
    .card-header a:hover {
        color: #04813a;
        transform: translateX(3px);
    }
    
    .welcome-card {
        background: linear-gradient(135deg, #05A045 0%, #04813a 100%);
        color: white;
        text-align: center;
        padding: 40px 25px;
    }
    
    .welcome-icon {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 2.5rem;
    }
    
    .welcome-card h2 {
        font-size: 1.8rem;
        margin-bottom: 15px;
    }
    
    .welcome-card p {
        max-width: 400px;
        margin: 0 auto 25px;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }
    
    .welcome-btn {
        display: inline-block;
        background: white;
        color: #05A045;
        padding: 12px 28px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        padding-bottom: 20px;
        border-bottom: 1px solid #edf2f7;
        position: relative;
        padding-left: 35px;
    }
    
    .activity-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .activity-item::before {
        content: "";
        position: absolute;
        left: 0;
        top: 8px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #05A045;
    }
    
    .activity-icon {
        position: absolute;
        left: 3px;
        top: 11px;
        color: white;
        font-size: 0.7rem;
        z-index: 2;
    }
    
    .activity-content h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 5px;
    }
    
    .activity-content p {
        color: #718096;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .activity-time {
        color: #a0aec0;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Chart Styles */
    .chart-container {
        margin-top: 15px;
        height: 250px;
        position: relative;
    }
    
    .chart-grid {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: space-between;
    }
    
    .grid-line {
        border-left: 1px dashed #e2e8f0;
        height: 100%;
    }
    
    .chart-bars {
        display: flex;
        align-items: flex-end;
        justify-content: space-around;
        height: 100%;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    }
    
    .chart-bar {
        width: 40px;
        background: linear-gradient(to top, #05A045, #48bb78);
        border-radius: 6px 6px 0 0;
        position: relative;
        transition: height 1s ease;
    }
    
    .chart-label {
        position: absolute;
        bottom: -30px;
        left: 0;
        width: 100%;
        text-align: center;
        font-size: 0.85rem;
        color: #718096;
    }
    
    .chart-value {
        position: absolute;
        top: -25px;
        left: 0;
        width: 100%;
        text-align: center;
        font-weight: 600;
        color: #2d3748;
    }
    
    /* Animation for stats cards */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stats-card {
        animation: fadeInUp 0.5s ease forwards;
        opacity: 0;
    }
    
    .stats-card:nth-child(1) { animation-delay: 0.1s; }
    .stats-card:nth-child(2) { animation-delay: 0.2s; }
    .stats-card:nth-child(3) { animation-delay: 0.3s; }
    .stats-card:nth-child(4) { animation-delay: 0.4s; }
    
    /* Animation for activity items */
    .activity-item {
        animation: fadeInUp 0.5s ease forwards;
        opacity: 0;
    }
    
    .activity-item:nth-child(1) { animation-delay: 0.2s; }
    .activity-item:nth-child(2) { animation-delay: 0.3s; }
    .activity-item:nth-child(3) { animation-delay: 0.4s; }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-header h1 {
            font-size: 1.8rem;
        }
        
        .stats-card .value {
            font-size: 1.8rem;
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }
        
        .welcome-card {
            padding: 30px 20px;
        }
        
        .welcome-icon {
            width: 70px;
            height: 70px;
            font-size: 2rem;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .dashboard-header h1 {
            font-size: 1.6rem;
        }
        
        .dashboard-header p {
            font-size: 1rem;
        }
    }
</style>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Dashboard Overview</h1>
        <p>Welcome back! Here's what's happening today.</p>
    </div>
    
    <div class="stats-grid">
        
        <!-- Stats Card 2: Active Users -->
        <div class="stats-card">
            <div class="stats-card-content">
                <div class="stats-info">
                    <h3>Active Users</h3>
                    <div class="value"><?= number_format($clientsCount['count']) ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up mr-1"></i>
                        8.2% from last month
                    </div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <!-- Stats Card 3: Pending Orders -->
        <div class="stats-card">
            <div class="stats-card-content">
                <div class="stats-info">
                    <h3>Pending Orders</h3>
                    <div class="value"><?= number_format($pendingCount['count']) ?></div>
                    <div class="trend down">
                        <i class="fas fa-arrow-down mr-1"></i>
                        3.1% from last month
                    </div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        
        <!-- Stats Card 4: New Customers -->
        <div class="stats-card">
            <div class="stats-card-content">
                <div class="stats-info">
                    <h3>New Customers</h3>
                    <div class="value"><?= number_format($pendingCount['count']) ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up mr-1"></i>
                        5.7% from last month
                    </div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-content">
        <!-- Left Column -->
        <div>
            <!-- Welcome Card -->
            <div class="dashboard-card welcome-card">
                <div class="welcome-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Your Dashboard is Ready</h2>
                <p>Start adding content to your dashboard to see valuable insights and metrics about your business.</p>
                <a href="#" class="welcome-btn">Get Started</a>
            </div>
            
          
        </div>
        
        <!-- Right Column: Recent Activity -->
        <div>
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Activity</h2>
                    <a href="#">
                        View All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="activity-list">
                    <?php foreach($recentClients as $client): 
                        $time = date('F j, Y', strtotime($client['created_at']));
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <h3>New client registered</h3>
                            <p><?= htmlspecialchars($client['name']) ?> joined the platform</p>
                            <div class="activity-time">
                                <i class="far fa-clock"></i>
                                <?= $time ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
          
        </div>
    </div>
</div>

<script>
    // Simple animation for chart bars
    document.addEventListener('DOMContentLoaded', function() {
        const bars = document.querySelectorAll('.chart-bar');
        bars.forEach(bar => {
            const height = bar.style.height;
            bar.style.height = '0%';
            
            setTimeout(() => {
                bar.style.height = height;
            }, 500);
        });
    });
</script>

<?php require_once 'footer.php'; ?>