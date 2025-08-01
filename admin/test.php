<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// --- VIEW STATE (Active vs. Deleted) ---
$view_deleted = isset($_GET['view_deleted']) && $_GET['view_deleted'] == 1;

// --- PAGINATION SETUP ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(id) FROM products WHERE " . ($view_deleted ? "deleted_at IS NOT NULL" : "deleted_at IS NULL");
$total_products = $pdo->query($count_sql)->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// --- FORM HANDLING for ADD Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $cost_price = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if (empty($name) || $price === false || $cost_price === false || $category_id === false || $stock === null) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid input for new product. Please check all fields.'];
    } else {
        $pdo->beginTransaction();
        try {
            $image_name = handleImageUpload($_FILES['image']);
            if ($image_name !== false) {
                $insert_stmt = $pdo->prepare("INSERT INTO products (name, price, cost_price, category_id, image, description, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$name, $price, $cost_price, $category_id, $image_name, $description, $stock]);

                $product_id = $pdo->lastInsertId();

                // Handle the additional images
                handleAdditionalImages($_FILES['additional_images'], $product_id, $pdo);

                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Product added successfully!'];
            } else {
                $pdo->rollBack();
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'A main image is required. Product not added.'];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
    redirect('products.php');
}

// --- FORM HANDLING for UPDATE Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $cost_price = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if (!$product_id || empty($name) || $price === false || $cost_price === false || $category_id === false || $stock === null) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid input for product update. Please check all fields.'];
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_image = $stmt->fetchColumn();
            $image_to_save = handleImageUpload($_FILES['image'], $current_image);

            $update_stmt = $pdo->prepare(
                "UPDATE products SET name = ?, price = ?, cost_price = ?, category_id = ?, description = ?, stock = ?, image = ?, updated_at = NOW() WHERE id = ?"
            );
            $update_stmt->execute([$name, $price, $cost_price, $category_id, $description, $stock, $image_to_save, $product_id]);

            // Handle any newly uploaded additional images
            handleAdditionalImages($_FILES['additional_images'], $product_id, $pdo);

            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Product #" . $product_id . " was updated successfully."];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred during update: ' . $e->getMessage()];
        }
    }
    redirect('products.php');
}


// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch products based on the view
$fetch_sql = "SELECT p.*, c.name AS category_name
              FROM products p
              JOIN categories c ON p.category_id = c.id
              WHERE " . ($view_deleted ? "p.deleted_at IS NOT NULL" : "p.deleted_at IS NULL") . "
              ORDER BY p.id DESC
              LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($fetch_sql);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">Manage Products</h2>
    <div>
        <?php if (!$view_deleted): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Product
            </button>
        <?php endif; ?>
        <a href="products.php?view_deleted=<?= $view_deleted ? '0' : '1' ?>" class="btn btn-outline-secondary">
            <i class="bi <?= $view_deleted ? 'bi-list-ul' : 'bi-trash' ?> me-2"></i>View <?= $view_deleted ? 'Active' : 'Deleted' ?>
            Products
        </a>
    </div>
</div>

<!-- Product List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><?= $view_deleted ? 'Deleted' : 'Active' ?> Product List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="productsTable" class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">No products found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($products as $prod): ?>
                    <tr id="product-row-<?= $prod['id'] ?>">
                        <td><?= esc_html($prod['id']) ?></td>
                        <td><img src="assets/uploads/<?= esc_html($prod['image'] ?? 'default.png') ?>"
                                 alt="<?= esc_html($prod['name']) ?>" width="60" class="img-thumbnail rounded"></td>
                        <td><?= esc_html($prod['name']) ?></td>
                        <td><span class="badge bg-light text-dark"><?= esc_html($prod['category_name']) ?></span></td>
                        <td><?= formatPrice($prod['cost_price']) ?></td>
                        <td><?= formatPrice($prod['price']) ?></td>
                        <td><?= esc_html($prod['stock']) ?></td>
                        <td>
                            <?php if (!$view_deleted): ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                           data-id="<?= $prod['id'] ?>" <?= $prod['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label"></label>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-danger">Deleted</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($view_deleted): ?>
                                <button type="button" class="btn btn-sm btn-success restore-product-btn"
                                        data-id="<?= htmlspecialchars($prod['id']) ?>">Restore
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-product-btn"
                                        data-bs-toggle="modal" data-bs-target="#editProductModal"
                                        data-id="<?= $prod['id'] ?>"
                                        data-name="<?= esc_html($prod['name']) ?>"
                                        data-category-id="<?= $prod['category_id'] ?>"
                                        data-description="<?= esc_html($prod['description']) ?>"
                                        data-price="<?= $prod['price'] ?>"
                                        data-cost-price="<?= $prod['cost_price'] ?>"
                                        data-stock="<?= $prod['stock'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-product-btn"
                                        data-id="<?= htmlspecialchars($prod['id']) ?>"><i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">«</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link"
                               href="?page=<?= $i ?><?= $view_deleted ? '&view_deleted=1' : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">»</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="add_product_name" class="form-label">Product
                                Name</label><input type="text" name="name" id="add_product_name" required
                                                   class="form-control"></div>
                        <div class="col-md-6"><label for="add_product_category"
                                                     class="form-label">Category</label><select name="category_id"
                                                                                                id="add_product_category"
                                                                                                required
                                                                                                class="form-select">
                                <option value="" disabled selected>-- Select --
                                </option><?php foreach ($categories as $cat): ?>
                                    <option
                                    value="<?= esc_html($cat['id']) ?>"><?= esc_html($cat['name']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-12"><label for="add_product_description"
                                                   class="form-label">Description</label><textarea name="description"
                                                                                                   id="add_product_description"
                                                                                                   required
                                                                                                   class="form-control"
                                                                                                   rows="3"></textarea>
                        </div>
                        <div class="col-md-4"><label for="add_product_cost_price" class="form-label">Cost Price</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number"
                                                                                                   name="cost_price"
                                                                                                   id="add_product_cost_price"
                                                                                                   required
                                                                                                   class="form-control"
                                                                                                   step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4"><label for="add_product_price" class="form-label">Selling Price</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number"
                                                                                                   name="price"
                                                                                                   id="add_product_price"
                                                                                                   required
                                                                                                   class="form-control"
                                                                                                   step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4"><label for="add_product_stock" class="form-label">Stock</label><input
                                    type="number" name="stock" id="add_product_stock" required class="form-control"
                                    min="0"></div>
                        <div class="col-md-6"><label for="add_product_image" class="form-label">Main Image</label><input
                                    type="file" name="image" id="add_product_image" class="form-control" required><small
                                    class="form-text text-muted">This is the featured image.</small></div>
                        <div class="col-md-6"><label for="add_additional_images" class="form-label">Additional
                                Images</label><input type="file" name="additional_images[]" id="add_additional_images"
                                                     class="form-control" multiple></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="edit_product_name" class="form-label">Product
                                Name</label><input type="text" name="name" id="edit_product_name" required
                                                   class="form-control"></div>
                        <div class="col-md-6"><label for="edit_product_category"
                                                     class="form-label">Category</label><select name="category_id"
                                                                                                id="edit_product_category"
                                                                                                required
                                                                                                class="form-select"><?php foreach ($categories as $cat): ?>
                                    <option
                                    value="<?= esc_html($cat['id']) ?>"><?= esc_html($cat['name']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-12"><label for="edit_product_description" class="form-label">Description</label><textarea
                                    name="description" id="edit_product_description" required class="form-control"
                                    rows="3"></textarea></div>
                        <div class="col-md-4"><label for="edit_product_cost_price" class="form-label">Cost Price</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number"
                                                                                                   name="cost_price"
                                                                                                   id="edit_product_cost_price"
                                                                                                   required
                                                                                                   class="form-control"
                                                                                                   step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4"><label for="edit_product_price" class="form-label">Selling Price</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number"
                                                                                                   name="price"
                                                                                                   id="edit_product_price"
                                                                                                   required
                                                                                                   class="form-control"
                                                                                                   step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4"><label for="edit_product_stock" class="form-label">Set Stock</label><input
                                    type="number" name="stock" id="edit_product_stock" required class="form-control"
                                    min="0"></div>
                        <div class="col-md-6"><label for="edit_product_image" class="form-label">Change Main
                                Image</label><input type="file" name="image" id="edit_product_image"
                                                    class="form-control"><small class="form-text text-muted">Leave blank
                                to keep current image.</small></div>
                        <div class="col-md-6"><label for="edit_additional_images" class="form-label">Add More
                                Images</label><input type="file" name="additional_images[]" id="edit_additional_images"
                                                     class="form-control" multiple></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productsTable = document.getElementById('productsTable');
        const editProductModal = document.getElementById('editProductModal');

        if (editProductModal) {
            editProductModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const modal = this;

                const id = button.dataset.id;
                const name = button.dataset.name;
                const categoryId = button.dataset.categoryId;
                const description = button.dataset.description;
                const price = button.dataset.price;
                const costPrice = button.dataset.costPrice;
                const stock = button.dataset.stock;

                modal.querySelector('#edit_product_id').value = id;
                modal.querySelector('#edit_product_name').value = name;
                modal.querySelector('#edit_product_category').value = categoryId;
                modal.querySelector('#edit_product_description').value = description;
                modal.querySelector('#edit_product_price').value = price;
                modal.querySelector('#edit_product_cost_price').value = costPrice;
                modal.querySelector('#edit_product_stock').value = stock;
            });
        }

        if (productsTable) {
            productsTable.addEventListener('click', function (e) {
                const target = e.target.closest('button');
                if (!target) return;

                const productId = target.dataset.id;

                if (target.classList.contains('delete-product-btn')) {
                    if (confirm('Are you sure you want to delete this product?')) {
                        fetch('ajax/soft_delete_product.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `id=${productId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById(`product-row-${productId}`).remove();
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }

                if (target.classList.contains('restore-product-btn')) {
                    if (confirm('Are you sure you want to restore this product?')) {
                        fetch('ajax/restore_product.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `id=${productId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById(`product-row-${productId}`).remove();
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }
            });

            productsTable.addEventListener('change', function (e) {
                if (e.target.classList.contains('status-toggle')) {
                    const toggle = e.target;
                    const productId = toggle.dataset.id;
                    const newStatus = toggle.checked ? 1 : 0;

                    fetch('ajax/toggle_product_status.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `id=${productId}&status=${newStatus}`
                    })
                        .then(response => response.json())
                        .catch(error => console.error('Error:', error));
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
