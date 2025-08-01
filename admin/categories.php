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

// Fetch categories for display
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">Manage Categories</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle me-2"></i>Add New Category
    </button>
</div>

<!-- Category List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">All Categories</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No categories found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($categories as $cat): ?>
                    <tr id="category-row-<?= $cat['id'] ?>">
                        <td><?= esc_html($cat['id']) ?></td>
                        <td class="category-name-cell" data-id="<?= $cat['id'] ?>"><?= esc_html($cat['name']) ?></td>
                        <td><?= format_date($cat['created_at']) ?></td>
                        <td class="category-updated-cell"
                            data-id="<?= $cat['id'] ?>"><?= format_date($cat['updated_at']) ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-category-btn"
                                    data-id="<?= $cat['id'] ?>" data-name="<?= esc_html($cat['name']) ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-category-btn"
                                    data-id="<?= $cat['id'] ?>">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name_add" class="form-label">Category Name</label>
                        <input type="text" name="name" id="category_name_add" required class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" name="name" id="edit_category_name" required class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModalEl = document.getElementById('editCategoryModal');
        const editModal = new bootstrap.Modal(editModalEl);
        const editForm = document.getElementById('editCategoryForm');

        // --- Handle Edit Button Click ---
        document.querySelectorAll('.edit-category-btn').forEach(button => {
            button.addEventListener('click', function () {
                const categoryId = this.dataset.id;
                const categoryName = this.dataset.name;

                // Populate the modal fields
                document.getElementById('edit_category_id').value = categoryId;
                document.getElementById('edit_category_name').value = categoryName;

                editModal.show();
            });
        });

        // --- Handle Edit Form Submission ---
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('ajax/update_category.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.querySelector(`.category-name-cell[data-id='${data.id}']`).textContent = data.name;
                        document.querySelector(`.category-updated-cell[data-id='${data.id}']`).textContent = data.updated_at;
                        editModal.hide();
                        // Consider adding a more elegant notification system (toast)
                        alert('Category updated successfully!');
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the category.');
                });
        });

        // --- Handle Delete Button Click ---
        document.querySelectorAll('.delete-category-btn').forEach(button => {
            button.addEventListener('click', function () {
                const categoryId = this.dataset.id;
                if (!confirm("Are you sure you want to delete this category? This may affect existing products.")) return;

                fetch('ajax/delete_category.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${encodeURIComponent(categoryId)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById(`category-row-${categoryId}`).remove();
                        } else {
                            alert(`Error: ${data.message}`);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the category.');
                    });
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
