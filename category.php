<?php
require_once 'db_connection.php';
require_once 'header.php';

// Handle category operations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    
    if($action == 'add') {
        $status = 'active';
        $stmt = $conn->prepare("INSERT INTO categories (name, status) VALUES (:name, :status)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    } 
    elseif($action == 'edit') {
        $status = $_POST['status'] ?? 'active';
        $stmt = $conn->prepare("UPDATE categories SET name = :name, status = :status WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    }
    elseif($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
}

// Get categories
$stmt = $conn->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Icons and colors for categories
$icons = ['fas fa-laptop', 'fas fa-tshirt', 'fas fa-home', 'fas fa-book', 'fas fa-utensils', 'fas fa-car'];
$colors = ['#4CAF50', '#2196F3', '#FFC107', '#9C27B0', '#FF5252', '#3F51B5'];
?>

<style>
    /* Category Management Styles */
    .category-header {
        display: flex;
        flex-direction: column;
        margin-bottom: 1.5rem;
    }
    
    .category-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2D3748;
        margin-bottom: 0.25rem;
    }
    
    .category-header p {
        color: #718096;
        font-size: 1rem;
    }
    
    .category-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    @media (min-width: 768px) {
        .category-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-actions {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
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
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background-color: #F7FAFC;
    }
    
    .search-container input:focus {
        outline: none;
        border-color: #05A045;
        box-shadow: 0 0 0 3px rgba(5, 160, 69, 0.1);
        background-color: #fff;
    }
    
    .search-container i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #A0AEC0;
    }
    
    .add-category-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #05A045;
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s;
        box-shadow: 0 4px 6px rgba(5, 160, 69, 0.15);
    }
    
    .add-category-btn:hover {
        background-color: #04813a;
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(5, 160, 69, 0.2);
    }
    
    .category-table-container {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .table-header {
        padding: 1.25rem 1.5rem;
        background-color: #F7FAFC;
        border-bottom: 1px solid #E2E8F0;
    }
    
    .table-header h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2D3748;
    }
    
    .category-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .category-table thead {
        background-color: #F7FAFC;
    }
    
    .category-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-size: 0.8rem;
        font-weight: 600;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #E2E8F0;
    }
    
    .category-table td {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #EDF2F7;
    }
    
    .category-table tbody tr {
        transition: background-color 0.2s;
    }
    
    .category-table tbody tr:hover {
        background-color: #F7FAFC;
    }
    
    .category-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .category-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
    }
    
    .category-name {
        font-weight: 500;
        color: #2D3748;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #E6F4EB;
        color: #05A045;
    }
    
    .status-inactive {
        background-color: #EDF2F7;
        color: #718096;
    }
    
    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background-color: transparent;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .edit-btn {
        color: #3182CE;
    }
    
    .edit-btn:hover {
        background-color: #EBF8FF;
    }
    
    .delete-btn {
        color: #E53E3E;
    }
    
    .delete-btn:hover {
        background-color: #FFF5F5;
    }
    
    .table-footer {
        padding: 1rem 1.5rem;
        background-color: #F7FAFC;
        border-top: 1px solid #E2E8F0;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    @media (min-width: 768px) {
        .table-footer {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }
    
    .pagination-info {
        color: #718096;
        font-size: 0.9rem;
    }
    
    /* Modal Styles */
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
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 480px;
        transform: translateY(-20px);
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    
    .modal.active .modal-content {
        transform: translateY(0);
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #EDF2F7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2D3748;
    }
    
    .close-modal {
        background: none;
        border: none;
        color: #A0AEC0;
        font-size: 1.25rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .close-modal:hover {
        color: #718096;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #4A5568;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.875rem;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
        background-color: #F7FAFC;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #05A045;
        box-shadow: 0 0 0 3px rgba(5, 160, 69, 0.1);
        background-color: #fff;
    }
    
    .modal-footer {
        padding: 1.25rem 1.5rem;
        background-color: #F7FAFC;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .cancel-btn {
        padding: 0.75rem 1.25rem;
        background-color: #fff;
        color: #4A5568;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .cancel-btn:hover {
        background-color: #F7FAFC;
        border-color: #CBD5E0;
    }
    
    .save-btn {
        padding: 0.75rem 1.25rem;
        background-color: #05A045;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .save-btn:hover {
        background-color: #04813a;
    }
</style>

<div class="category-header">
    <div>
        <h1>Category Management</h1>
        <p>Organize and manage your content categories</p>
    </div>
</div>

<div class="category-actions">
    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="categorySearch" placeholder="Search categories...">
    </div>
    
    <button id="addCategoryBtn" class="add-category-btn">
        <i class="fas fa-plus"></i>
        Add Category
    </button>
</div>

<div class="category-table-container">
    <div class="table-header">
        <h2>All Categories</h2>
    </div>
    
    <table class="category-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="categoryTableBody">
            <?php foreach($categories as $index => $category): 
                $icon = $icons[$index % count($icons)];
                $color = $colors[$index % count($colors)];
            ?>
            <tr>
                <td class="font-medium">CAT-<?= str_pad($category['id'], 3, '0', STR_PAD_LEFT) ?></td>
                <td>
                    <div class="category-info">
                        <div class="category-icon" style="background-color: <?= $color ?>">
                            <i class="<?= $icon ?>"></i>
                        </div>
                        <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                    </div>
                </td>
                <td class="text-center">
                    <span class="status-badge <?= $category['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                        <?= ucfirst($category['status']) ?>
                    </span>
                </td>
                <td class="text-right">
                    <div class="action-buttons">
                        <button class="action-btn edit-btn edit-category" 
                            data-id="<?= $category['id'] ?>"
                            data-name="<?= htmlspecialchars($category['name']) ?>"
                            data-status="<?= $category['status'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn delete-category" 
                            data-id="<?= $category['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="table-footer">
        <div class="pagination-info">
            Showing <span class="font-medium">1</span> to <span class="font-medium"><?= count($categories) ?></span> of <span class="font-medium"><?= count($categories) ?></span> categories
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="categoryModalTitle">Add New Category</h3>
            <button class="close-modal" id="closeCategoryModalBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="categoryForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="categoryAction" value="add">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label for="categoryName">Category Name</label>
                    <input type="text" id="categoryName" name="name" required placeholder="Enter category name">
                </div>
                
                <div class="form-group" id="statusField">
                    <label for="categoryStatus">Status</label>
                    <select id="categoryStatus" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelCategoryModalBtn">Cancel</button>
                <button type="submit" class="save-btn">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM elements
        const addCategoryBtn = document.getElementById('addCategoryBtn');
        const closeCategoryModalBtn = document.getElementById('closeCategoryModalBtn');
        const cancelCategoryModalBtn = document.getElementById('cancelCategoryModalBtn');
        const categoryModal = document.getElementById('categoryModal');
        const categoryForm = document.getElementById('categoryForm');
        const categoryModalTitle = document.getElementById('categoryModalTitle');
        const categoryAction = document.getElementById('categoryAction');
        const categoryId = document.getElementById('categoryId');
        const categoryName = document.getElementById('categoryName');
        const categoryStatus = document.getElementById('categoryStatus');
        const statusField = document.getElementById('statusField');
        
        // Open modal for adding category
        addCategoryBtn.addEventListener('click', () => {
            categoryModalTitle.textContent = "Add New Category";
            categoryAction.value = "add";
            statusField.style.display = "none";
            categoryForm.reset();
            categoryModal.classList.add('active');
            categoryName.focus();
        });
        
        // Open modal for editing category
        document.querySelectorAll('.edit-category').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const status = this.getAttribute('data-status');
                
                categoryModalTitle.textContent = "Edit Category";
                categoryAction.value = "edit";
                categoryId.value = id;
                categoryName.value = name;
                categoryStatus.value = status;
                statusField.style.display = "block";
                
                categoryModal.classList.add('active');
                categoryName.focus();
            });
        });
        
        // Handle category deletion
        document.querySelectorAll('.delete-category').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                
                if(confirm("Are you sure you want to delete this category?")) {
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
        
        // Close modal
        function closeCategoryModal() {
            categoryModal.classList.remove('active');
            categoryForm.reset();
        }
        
        closeCategoryModalBtn.addEventListener('click', closeCategoryModal);
        cancelCategoryModalBtn.addEventListener('click', closeCategoryModal);
        
        // Close modal when clicking outside
        categoryModal.addEventListener('click', (e) => {
            if (e.target === categoryModal) {
                closeCategoryModal();
            }
        });
        
        // Category search
        const categorySearch = document.getElementById('categorySearch');
        if(categorySearch) {
            categorySearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#categoryTableBody tr');
                
                rows.forEach(row => {
                    const name = row.querySelector('.category-name').textContent.toLowerCase();
                    if(name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

<?php require_once 'footer.php'; ?>