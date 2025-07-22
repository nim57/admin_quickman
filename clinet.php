<?php
require_once 'db_connection.php';
require_once 'header.php';

// Handle client operations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $logo = $_POST['current_logo'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $badge = $_POST['badge'] ?? 'silver'; // Default to silver if not selected
    
    if($action == 'add') {
        $uploadedLogo = '';
        if(isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            // Upload to Cloudinary
            $result = $cloudinary->uploadApi()->upload($_FILES['logo']['tmp_name']);
            $uploadedLogo = $result['secure_url'];
        }
        
        $stmt = $conn->prepare("INSERT INTO clients (name, logo, description, category_id, badge) VALUES (:name, :logo, :description, :category_id, :badge)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':logo', $uploadedLogo);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':badge', $badge);
        $stmt->execute();
    } 
    elseif($action == 'edit') {
        $uploadedLogo = $logo;
        if(isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            // Delete old logo from Cloudinary
            if(!empty($logo)) {
                $publicId = basename(parse_url($logo, PHP_URL_PATH), PATHINFO_FILENAME);
                $cloudinary->uploadApi()->destroy($publicId);
            }
            
            // Upload new image
            $result = $cloudinary->uploadApi()->upload($_FILES['logo']['tmp_name']);
            $uploadedLogo = $result['secure_url'];
        }
        
        $stmt = $conn->prepare("UPDATE clients SET name = :name, logo = :logo, description = :description, category_id = :category_id, badge = :badge WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':logo', $uploadedLogo);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':badge', $badge);
        $stmt->execute();
    }
    elseif($action == 'delete') {
        // Get logo URL
        $stmt = $conn->prepare("SELECT logo FROM clients WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete from Cloudinary
        if($client && !empty($client['logo'])) {
            $publicId = basename(parse_url($client['logo'], PHP_URL_PATH), PATHINFO_FILENAME);
            $cloudinary->uploadApi()->destroy($publicId);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
}

$stmt = $conn->query("
    SELECT clients.*, categories.name AS category_name 
    FROM clients 
    LEFT JOIN categories ON clients.category_id = categories.id 
    ORDER BY clients.id DESC
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all active categories for the dropdown
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Base Styles */
        :root {
            --primary: #05A045;
            --primary-light: #E6F4EB;
            --primary-dark: #04813a;
            --secondary: #3F51B5;
            --accent: #FF6B6B;
            --light: #F7FAFC;
            --dark: #2D3748;
            --gray: #718096;
            --light-gray: #EDF2F7;
            --border: #E2E8F0;
            --shadow: rgba(0, 0, 0, 0.08);
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #E53E3E;
            --info: #2196F3;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .client-header {
            display: flex;
            flex-direction: column;
            margin-bottom: 2rem;
            padding-top: 1.5rem;
        }

        .client-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .client-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        @media (min-width: 768px) {
            .client-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .header-actions {
                flex-direction: row;
                align-items: center;
                margin-top: 0;
            }
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 320px;
        }

        .search-container input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: var(--light);
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 160, 69, 0.1);
            background-color: #fff;
        }

        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .add-client-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(5, 160, 69, 0.15);
            white-space: nowrap;
        }

        .add-client-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(5, 160, 69, 0.2);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stats-text h3 {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .stats-text .value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 8px rgba(5, 160, 69, 0.2);
        }

        .stats-trend {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stats-trend.up {
            color: var(--success);
        }

        .stats-trend i {
            font-size: 0.8rem;
        }

        /* Client Table */
        .table-container {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2.5rem;
            border: 1px solid var(--border);
        }

        .table-header {
            padding: 1.25rem 1.5rem;
            background-color: var(--light);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dark);
        }

        .table-controls {
            display: flex;
            gap: 0.75rem;
        }

        .table-controls button {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background-color: #fff;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .table-controls button:hover {
            background-color: var(--light);
            color: var(--primary);
            border-color: var(--primary);
        }

        .client-table {
            width: 100%;
            border-collapse: collapse;
        }

        .client-table thead {
            background-color: var(--light);
        }

        .client-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }

        .client-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .client-table tbody tr {
            transition: background-color 0.2s;
        }

        .client-table tbody tr:hover {
            background-color: rgba(5, 160, 69, 0.03);
        }

        .client-id {
            font-weight: 600;
            color: var(--secondary);
        }

        .client-name {
            font-weight: 600;
            color: var(--dark);
        }

        .category-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .logo-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
        }

        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-placeholder {
            color: var(--gray);
            font-size: 0.8rem;
            text-align: center;
            padding: 5px;
        }

        .client-description {
            font-size: 0.95rem;
            color: var(--gray);
            max-width: 300px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: transparent;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .edit-btn {
            color: var(--info);
            background-color: rgba(33, 150, 243, 0.1);
        }

        .edit-btn:hover {
            background-color: rgba(33, 150, 243, 0.2);
        }

        .delete-btn {
            color: var(--danger);
            background-color: rgba(229, 62, 62, 0.1);
        }

        .delete-btn:hover {
            background-color: rgba(229, 62, 62, 0.2);
        }

        /* Pagination */
        .table-footer {
            padding: 1.25rem 1.5rem;
            background-color: var(--light);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-info {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* Modal Styles - FIXED */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            max-height: 65vh; /* Fixed max height for scrollability */
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 160, 69, 0.1);
            background-color: #fff;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper button {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--light);
            color: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .file-input-wrapper button:hover {
            background-color: #e2e8f0;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            background-color: var(--light);
            border: 1px dashed var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview span {
            color: var(--gray);
            font-size: 0.9rem;
            text-align: center;
            padding: 10px;
        }

        .file-info {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }

        .modal-footer {
            padding: 1.25rem 1.5rem;
            background-color: var(--light);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid var(--border);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
            min-width: 120px;
        }

        .btn-cancel {
            background-color: #fff;
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .btn-cancel:hover {
            background-color: var(--light);
            border-color: var(--gray);
        }

        .btn-save {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        .btn-save:hover {
            background-color: var(--primary-dark);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="client-header">
            <div>
                <h1>Client Management</h1>
                <p>Manage your clients and their categories</p>
            </div>
            
            <div class="header-actions">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="clientSearch" placeholder="Search clients...">
                </div>
                <button id="addClientBtn" class="add-client-btn">
                    <i class="fas fa-plus mr-2"></i>
                    Add Client
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-text">
                        <h3>Total Clients</h3>
                        <div class="value"><?= count($clients) ?></div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stats-trend up">
                    <i class="fas fa-arrow-up"></i>
                    8.2% from last month
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-text">
                        <h3>Active Clients</h3>
                        <div class="value"><?= count($clients) ?></div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stats-trend up">
                    <i class="fas fa-arrow-up"></i>
                    5.7% from last month
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-text">
                        <h3>Categories</h3>
                        <div class="value"><?= count($categories) ?></div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
                <div class="stats-trend up">
                    <i class="fas fa-arrow-up"></i>
                    3 new this month
                </div>
            </div>
        </div>

       <div class="table-container">
            <div class="table-header">
                <h2>All Clients</h2>
                <div class="table-controls">
                    <button aria-label="Filter">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button aria-label="Sort" title="Sort">
                        <i class="fas fa-sort"></i>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Client Name</th>
                            <th>Category</th>
                            <th>Badge</th>
                            <th>Shop Logo</th>
                            <th>Description</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientTableBody">
                        <?php if(count($clients) > 0): ?>
                            <?php foreach($clients as $client): 
                                // Get badge value with fallback to 'silver'
                                $badge = $client['badge'] ?? 'silver';
                                // Set badge colors
                                $bgColor = '#C0C0C0'; // Default silver
                                $textColor = '#000';
                                
                                if($badge === 'gold') {
                                    $bgColor = '#FFD700';
                                } elseif($badge === 'platinum') {
                                    $bgColor = '#E5E4E2';
                                } elseif($badge === 'forget') {
                                    $bgColor = '#FF6B6B';
                                    $textColor = '#fff';
                                }
                            ?>
                            <tr>
                                <td class="client-id">CLI-<?= str_pad($client['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td class="client-name"><?= htmlspecialchars($client['name']) ?></td>
                                <td>
                                    <?php if(!empty($client['category_name'])): ?>
                                        <span class="category-badge"><?= htmlspecialchars($client['category_name']) ?></span>
                                    <?php else: ?>
                                        <span class="category-badge" style="background-color: #e2e8f0; color: #718096;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-badge" style="
                                        background-color: <?= $bgColor ?>;
                                        color: <?= $textColor ?>;
                                        padding: 0.35rem 0.75rem;
                                        border-radius: 20px;
                                        font-size: 0.85rem;
                                        font-weight: 500;
                                        text-transform: capitalize;
                                    ">
                                        <?= $badge ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="logo-preview">
                                        <?php if(!empty($client['logo'])): ?>
                                            <img src="<?= $client['logo'] ?>" alt="<?= $client['name'] ?> Logo">
                                        <?php else: ?>
                                            <div class="logo-placeholder">No Logo</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="client-description"><?= htmlspecialchars($client['description']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn edit-client" 
                                            data-id="<?= $client['id'] ?>"
                                            data-name="<?= htmlspecialchars($client['name']) ?>"
                                            data-description="<?= htmlspecialchars($client['description']) ?>"
                                            data-logo="<?= $client['logo'] ?>"
                                            data-category-id="<?= $client['category_id'] ?>"
                                            data-badge="<?= $badge ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn delete-client" 
                                            data-id="<?= $client['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <!-- ... (empty state remains unchanged) ... -->
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if(count($clients) > 0): ?>
            <div class="table-footer">
                <div class="pagination-info">
                    Showing <span class="font-medium">1</span> to <span class="font-medium"><?= count($clients) ?></span> of <span class="font-medium"><?= count($clients) ?></span> clients
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="clientModalTitle">Add New Client</h3>
                <button class="close-modal" id="closeClientModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="clientForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" id="clientAction" value="add">
                    <input type="hidden" name="id" id="clientId">
                    <input type="hidden" name="current_logo" id="currentLogo">
                    
                    <div class="form-group">
                        <label for="clientName">Client Name</label>
                        <input type="text" id="clientName" name="name" required 
                            class="form-control"
                            placeholder="Enter client name">
                    </div>
                    
                    <div class="form-group">
                        <label for="clientCategory">Category</label>
                        <select id="clientCategory" name="category_id" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="clientBadge">Badge</label>
                        <select id="clientBadge" name="badge" class="form-control">
                            <option value="silver" selected>Silver</option>
                            <option value="gold">Gold</option>
                            <option value="platinum">Platinum</option>
                            <option value="forget">Forget</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Shop Logo</label>
                        <div class="image-preview" id="imagePreview">
                            <span>Logo Preview</span>
                        </div>
                        <div class="file-input-wrapper">
                            <button type="button">
                                <i class="fas fa-upload mr-2"></i> Choose File
                            </button>
                            <input type="file" id="clientLogo" name="logo" accept="image/*">
                        </div>
                        <p class="file-info">JPG, PNG or GIF (Max 2MB)</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="clientDescription">Description</label>
                        <textarea id="clientDescription" name="description" rows="3"
                            class="form-control"
                            placeholder="Enter client description"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" id="cancelClientModalBtn">Cancel</button>
                    <button type="submit" class="btn btn-save">Save Client</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM elements
            const addClientBtn = document.getElementById('addClientBtn');
            const addFirstClientBtn = document.getElementById('addFirstClientBtn');
            const closeClientModalBtn = document.getElementById('closeClientModalBtn');
            const cancelClientModalBtn = document.getElementById('cancelClientModalBtn');
            const clientModal = document.querySelector('.modal');
            const clientForm = document.getElementById('clientForm');
            const clientModalTitle = document.getElementById('clientModalTitle');
            const clientAction = document.getElementById('clientAction');
            const clientId = document.getElementById('clientId');
            const clientName = document.getElementById('clientName');
            const clientCategory = document.getElementById('clientCategory');
            const clientBadge = document.getElementById('clientBadge');
            const clientDescription = document.getElementById('clientDescription');
            const clientLogo = document.getElementById('clientLogo');
            const imagePreview = document.getElementById('imagePreview');
            const currentLogo = document.getElementById('currentLogo');
            
            // Open modal for adding client
            function openAddClientModal() {
                clientModalTitle.textContent = "Add New Client";
                clientAction.value = "add";
                clientForm.reset();
                clientCategory.value = "";
                clientBadge.value = "silver"; // Set default badge
                imagePreview.innerHTML = '<span>Logo Preview</span>';
                currentLogo.value = '';
                clientModal.classList.add('active');
                clientName.focus();
            }
            
            addClientBtn.addEventListener('click', openAddClientModal);
            if(addFirstClientBtn) {
                addFirstClientBtn.addEventListener('click', openAddClientModal);
            }
            
            // Open modal for editing client
            document.querySelectorAll('.edit-client').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const description = this.getAttribute('data-description');
                    const logo = this.getAttribute('data-logo');
                    const categoryId = this.getAttribute('data-category-id');
                    const badge = this.getAttribute('data-badge');
                    
                    clientModalTitle.textContent = "Edit Client";
                    clientAction.value = "edit";
                    clientId.value = id;
                    clientName.value = name;
                    clientDescription.value = description;
                    clientCategory.value = categoryId || "";
                    clientBadge.value = badge; // Set badge value
                    currentLogo.value = logo;
                    
                    if(logo) {
                        imagePreview.innerHTML = `<img src="${logo}" alt="Logo Preview">`;
                    } else {
                        imagePreview.innerHTML = '<span>Logo Preview</span>';
                    }
                    
                    clientModal.classList.add('active');
                    clientName.focus();
                });
            });
            
            // Handle client deletion
            document.querySelectorAll('.delete-client').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    
                    if(confirm("Are you sure you want to delete this client?")) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'delete';
                        
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        idInput.value = id;
                        
                        form.appendChild(actionInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            // Handle logo upload preview
            if(clientLogo) {
                clientLogo.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Close modal
            function closeClientModal() {
                clientModal.classList.remove('active');
                clientForm.reset();
                clientCategory.value = "";
                clientBadge.value = "silver"; // Reset badge to default
                imagePreview.innerHTML = '<span>Logo Preview</span>';
            }
            
            closeClientModalBtn.addEventListener('click', closeClientModal);
            cancelClientModalBtn.addEventListener('click', closeClientModal);
            
            // Close modal when clicking outside
            clientModal.addEventListener('click', (e) => {
                if (e.target === clientModal) {
                    closeClientModal();
                }
            });
            
            // Client search
            const clientSearch = document.getElementById('clientSearch');
            if(clientSearch) {
                clientSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#clientTableBody tr');
                    
                    rows.forEach(row => {
                        if(row.cells.length > 1) {
                            const name = row.cells[1].textContent.toLowerCase();
                            const category = row.cells[2].textContent.toLowerCase();
                            if(name.includes(searchTerm) || category.includes(searchTerm)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
<?php require_once 'footer.php'; ?>