<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// --- Handle Add Category with Post/Redirect/Get Pattern ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Category name cannot be empty.'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => "Category '" . esc_html($name) . "' already exists."];
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            if ($stmt->execute([$name])) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Category added successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to add category.'];
            }
        }
    }
    redirect('categories.php');
}

// Get statistics
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Get categories added this week
$week_ago = date('Y-m-d', strtotime('-7 days'));
$categories_this_week = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE created_at >= ?");
$categories_this_week->execute([$week_ago]);
$categories_this_week = $categories_this_week->fetchColumn();

// Get recently updated categories (last 30 days)
$month_ago = date('Y-m-d', strtotime('-30 days'));
$recently_updated = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE updated_at >= ? AND updated_at != created_at");
$recently_updated->execute([$month_ago]);
$recently_updated = $recently_updated->fetchColumn();

// Get products linked (if you have a products table with category_id)
$products_linked = 0;
try {
    $products_linked = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id IS NOT NULL")->fetchColumn();
} catch (Exception $e) {
    // Products table might not exist or have category_id column
    $products_linked = 0;
}

// Fetch categories for display
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            padding: 2rem 0;
        }

        .page-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .page-subtitle {
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .stats-container {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem 2rem;
        }

        .btn-add-category {
            background: var(--success-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
            color: white;
        }

        .btn-add-category:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.4);
            color: white;
        }

        .table-container {
            padding: 0;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }

        .table td {
            padding: 1rem 1.5rem;
            border-color: #f1f3f4;
            vertical-align: middle;
        }

        .category-id {
            font-weight: 600;
            color: #6c757d;
            font-family: 'Courier New', monospace;
        }

        .category-name {
            font-weight: 600;
            color: #495057;
            font-size: 1.1rem;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0 0.25rem;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
            color: white;
        }

        .btn-delete {
            background: var(--danger-gradient);
            border: none;
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(250, 112, 154, 0.4);
            color: white;
        }

        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #f1f3f4;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-input {
            padding-left: 3rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
            min-width: 300px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> flash-message alert-dismissible fade show" role="alert">
            <strong><?= $_SESSION['flash_message']['type'] === 'success' ? 'Success!' : 'Error!' ?></strong>
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="container-fluid">


        <!-- Statistics Cards -->
        <div class="stats-container fade-in">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-collection text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $total_categories ?></div>
                                <div class="text-muted">Total Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-plus-circle text-success" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $categories_this_week ?></div>
                                <div class="text-muted">Added This Week</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-arrow-up-circle text-info" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $recently_updated ?></div>
                                <div class="text-muted">Recently Updated</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-box-seam text-warning" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= $products_linked ?></div>
                                <div class="text-muted">Products Linked</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="main-card fade-in">
            <div class="card-header-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>All Categories
                    </h5>
                    <button type="button" class="btn btn-add-category" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Category
                    </button>
                    <div class="search-container">
                        <input type="text" class="form-control search-input" placeholder="Search categories..." id="searchInput">
                        <i class="bi bi-search search-icon"></i>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table" id="categoriesTable">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash me-2"></i>ID</th>
                                <th><i class="bi bi-tag me-2"></i>Name</th>
                                <th><i class="bi bi-calendar-plus me-2"></i>Created</th>
                                <th><i class="bi bi-calendar-check me-2"></i>Updated</th>
                                <th class="text-end"><i class="bi bi-gear me-2"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h5>No categories found</h5>
                                        <p class="text-muted">Start by adding your first category</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr id="category-row-<?= $cat['id'] ?>" class="fade-in">
                                    <td><span class="category-id">#<?= str_pad($cat['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                    <td class="category-name-cell" data-id="<?= $cat['id'] ?>">
                                        <span class="category-name"><?= esc_html($cat['name']) ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= format_date($cat['created_at']) ?></small></td>
                                    <td class="category-updated-cell" data-id="<?= $cat['id'] ?>">
                                        <small class="text-muted"><?= format_date($cat['updated_at']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-action btn-edit edit-category-btn"
                                            data-id="<?= $cat['id'] ?>" data-name="<?= esc_html($cat['name']) ?>">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-action btn-delete delete-category-btn"
                                            data-id="<?= $cat['id'] ?>">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Add New Category
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name_add" class="form-label">
                                <i class="bi bi-tag me-2"></i>Category Name
                            </label>
                            <input type="text" name="name" id="category_name_add" required class="form-control" placeholder="Enter category name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-add-category">
                            <i class="bi bi-check-circle me-2"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editCategoryForm">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil me-2"></i>Edit Category
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">
                                <i class="bi bi-tag me-2"></i>Category Name
                            </label>
                            <input type="text" name="name" id="edit_category_name" required class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-add-category">
                            <i class="bi bi-check-circle me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('editCategoryModal');
            const editModal = new bootstrap.Modal(editModalEl);
            const editForm = document.getElementById('editCategoryForm');

            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#categoriesTableBody tr');

                rows.forEach(row => {
                    const categoryNameCell = row.querySelector('.category-name');
                    if (categoryNameCell) {
                        const categoryName = categoryNameCell.textContent.toLowerCase();
                        row.style.display = categoryName.includes(searchTerm) ? '' : 'none';
                    }
                });
            });

            // Auto-dismiss flash messages after 5 seconds
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                setTimeout(() => {
                    const alert = new bootstrap.Alert(flashMessage);
                    alert.close();
                }, 5000);
            }

            // --- Handle Edit Button Click ---
            document.querySelectorAll('.edit-category-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const categoryId = this.dataset.id;
                    const categoryName = this.dataset.name;

                    // Populate the modal fields
                    document.getElementById('edit_category_id').value = categoryId;
                    document.getElementById('edit_category_name').value = categoryName;

                    editModal.show();
                });
            });

            // --- Handle Edit Form Submission ---
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                // Add loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Saving...';
                submitBtn.disabled = true;

                fetch('ajax/update_category.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Update the table row
                            const nameCell = document.querySelector(`.category-name-cell[data-id='${data.id}'] .category-name`);
                            const updatedCell = document.querySelector(`.category-updated-cell[data-id='${data.id}'] small`);
                            const editBtn = document.querySelector(`.edit-category-btn[data-id='${data.id}']`);

                            if (nameCell) nameCell.textContent = data.name;
                            if (updatedCell) updatedCell.textContent = data.updated_at;
                            if (editBtn) editBtn.dataset.name = data.name;

                            editModal.hide();

                            // Show success message
                            showFlashMessage('Category updated successfully!', 'success');
                        } else {
                            showFlashMessage('Error: ' + data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showFlashMessage('An error occurred while updating the category.', 'danger');
                    })
                    .finally(() => {
                        // Restore button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });

            // --- Handle Delete Button Click ---
            document.querySelectorAll('.delete-category-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const categoryId = this.dataset.id;
                    const categoryRow = document.getElementById(`category-row-${categoryId}`);
                    const categoryName = categoryRow.querySelector('.category-name').textContent;

                    if (!confirm(`Are you sure you want to delete "${categoryName}"? This may affect existing products.`)) return;

                    // Add loading state
                    this.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Deleting...';
                    this.disabled = true;

                    fetch('ajax/delete_category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${encodeURIComponent(categoryId)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Animate row removal
                                categoryRow.style.animation = 'fadeOut 0.3s ease-out';
                                setTimeout(() => {
                                    categoryRow.remove();

                                    // Update statistics
                                    const totalCategories = document.querySelector('.stat-number');
                                    if (totalCategories) {
                                        const currentCount = parseInt(totalCategories.textContent);
                                        totalCategories.textContent = currentCount - 1;
                                    }

                                    // Show success message
                                    showFlashMessage('Category deleted successfully!', 'success');
                                }, 300);
                            } else {
                                showFlashMessage('Error: ' + data.message, 'danger');
                                // Restore button state
                                this.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showFlashMessage('An error occurred while deleting the category.', 'danger');
                            // Restore button state
                            this.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
                            this.disabled = false;
                        });
                });
            });

            // Function to show flash messages
            function showFlashMessage(message, type) {
                const existingMessage = document.querySelector('.flash-message');
                if (existingMessage) {
                    existingMessage.remove();
                }

                const flashMessage = document.createElement('div');
                flashMessage.className = `alert alert-${type} flash-message alert-dismissible fade show`;
                flashMessage.innerHTML = `
                    <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(flashMessage);

                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    if (flashMessage && flashMessage.parentNode) {
                        const alert = new bootstrap.Alert(flashMessage);
                        alert.close();
                    }
                }, 5000);
            }
        });
    </script>
</body>

</html>

<?php include 'includes/footer.php'; ?>