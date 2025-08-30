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
    $short_description = trim($_POST['short_description']);
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
                $insert_stmt = $pdo->prepare("INSERT INTO products (name, short_description, price, cost_price, category_id, image, description, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$name, $short_description, $price, $cost_price, $category_id, $image_name, $description, $stock]);

                $product_id = $pdo->lastInsertId();
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
    $short_description = trim($_POST['short_description']);
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
                "UPDATE products SET name = ?, short_description = ?, price = ?, cost_price = ?, category_id = ?, description = ?, stock = ?, image = ?, updated_at = NOW() WHERE id = ?"
            );
            $update_stmt->execute([$name, $short_description, $price, $cost_price, $category_id, $description, $stock, $image_to_save, $product_id]);

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







<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> flash-message alert-dismissible fade show" role="alert">
        <strong><?= $_SESSION['flash_message']['type'] === 'success' ? 'Success!' : 'Error!' ?></strong>
        <?= $_SESSION['flash_message']['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>


<div class="filter-tabs">
    <a href="products.php" class="filter-tab <?= !$view_deleted ? 'active' : '' ?>">
        <i class="bi bi-list-ul me-2"></i>Active Products
    </a>
    <a href="products.php?view_deleted=1" class="filter-tab <?= $view_deleted ? 'active' : '' ?>">
        <i class="bi bi-trash me-2"></i>Deleted Products
    </a>
</div>

<div class="main-card">
    <div class="card-header-modern d-flex justify-content-between align-items-center">
        <h3 class="card-title-modern">
            <?= $view_deleted ? 'Deleted' : 'Active' ?> Product List
        </h3>
        <?php if (!$view_deleted): ?>
            <button type="button" class="btn btn-primary-modern btn-modern" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Product
            </button>
        <?php endif; ?>
        <div class="search-container">
            <input type="text" class="form-control search-input" placeholder="Search Products..." id="searchInput">
            <i class="bi bi-search search-icon"></i>
        </div>

    </div>

    <div class="table-responsive">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi <?= $view_deleted ? 'bi-archive' : 'bi-box-seam' ?>"></i>
                </div>
                <h4>No <?= $view_deleted ? 'deleted' : 'active' ?> products found</h4>
                <p class="text-muted">
                    <?= $view_deleted
                        ? 'No products have been deleted yet.'
                        : 'Start by adding your first product to the inventory.'
                    ?>
                </p>
                <?php if (!$view_deleted): ?>
                    <button type="button" class="btn btn-primary-modern btn-modern mt-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Your First Product
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table id="productsTable" class="table table-modern">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Pricing</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <?php foreach ($products as $prod): ?>
                        <tr id="product-row-<?= $prod['id'] ?>">
                            <td data-label="ID">
                                <span class="fw-bold">#<?= esc_html($prod['id']) ?></span>
                            </td>
                            <td data-label="Image">
                                <img src="assets/uploads/<?= esc_html($prod['image'] ?? 'default.png') ?>"
                                    alt="<?= esc_html($prod['name']) ?>"
                                    class="product-image">
                            </td>
                            <td data-label="Product">
                                <div>
                                    <div class="fw-bold product-name"><?= esc_html($prod['name']) ?></div>
                                    <?php if (!empty($prod['short_description'])): ?>
                                        <div class="text-muted small mt-1"><?= esc_html($prod['short_description']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Category">
                                <div class="fw-bold"> <?= esc_html($prod['category_name']) ?></div>
                            </td>
                            <td data-label="Pricing">
                                <div class="price-display">
                                    <div class="selling-price">৳<?= number_format($prod['price'], 2) ?></div>
                                    <div class="cost-price">Cost: ৳<?= number_format($prod['cost_price'], 2) ?></div>
                                </div>
                            </td>
                            <td data-label="Stock">
                                <?php
                                $stock_class = 'stock-good';
                                if ($prod['stock'] <= 5) $stock_class = 'stock-low';
                                elseif ($prod['stock'] <= 20) $stock_class = 'stock-medium';
                                ?>
                                <span class="stock-indicator <?= $stock_class ?>">
                                    <?= esc_html($prod['stock']) ?> units
                                </span>
                            </td>
                            <td data-label="Status">
                                <?php if (!$view_deleted): ?>
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="status-toggle"
                                            data-id="<?= $prod['id'] ?>"
                                            <?= $prod['is_active'] ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                <?php else: ?>
                                    <span class="badge badge-modern badge-deleted">Deleted</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <div class="action-buttons">
                                    <?php if ($view_deleted): ?>
                                        <button type="button" class="btn btn-action btn-restore restore-product-btn"
                                            data-id="<?= htmlspecialchars($prod['id']) ?>">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Restore
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-action btn-images manage-images-btn"
                                            data-bs-toggle="modal" data-bs-target="#manageImagesModal"
                                            data-id="<?= htmlspecialchars($prod['id']) ?>">
                                            <i class="bi bi-images me-1"></i>Images
                                        </button>
                                        <button type="button" class="btn btn-action btn-edit edit-product-btn"
                                            data-bs-toggle="modal" data-bs-target="#editProductModal"
                                            data-id="<?= $prod['id'] ?>"
                                            data-name="<?= esc_html($prod['name']) ?>"
                                            data-category-id="<?= $prod['category_id'] ?>"
                                            data-description="<?= esc_html($prod['description']) ?>"
                                            data-short-description="<?= esc_html($prod['short_description']) ?>"
                                            data-price="<?= $prod['price'] ?>"
                                            data-cost-price="<?= $prod['cost_price'] ?>"
                                            data-stock="<?= $prod['stock'] ?>">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                        <button type="button" class="btn btn-action btn-delete delete-product-btn"
                                            data-id="<?= htmlspecialchars($prod['id']) ?>">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-modern">
                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link-modern" href="?page=<?= $page - 1 ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link-modern" href="?page=<?= $i ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link-modern" href="?page=<?= $page + 1 ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade modal-modern" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="add_product_name" class="form-label form-label-modern">Product Name *</label>
                            <input type="text" name="name" id="add_product_name" required class="form-control form-control-modern">
                        </div>
                        <div class="col-md-6">
                            <label for="add_product_category" class="form-label form-label-modern">Category *</label>
                            <select name="category_id" id="add_product_category" required class="form-select form-control-modern">
                                <option value="" disabled selected>Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc_html($cat['id']) ?>"><?= esc_html($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="add_product_short_description" class="form-label form-label-modern">Short Description</label>
                            <input type="text" name="short_description" id="add_product_short_description"
                                class="form-control form-control-modern" maxlength="255"
                                placeholder="A brief, one-line summary of the product">
                            <div class="form-text text-muted">Optional: Brief product summary for quick overview</div>
                        </div>
                        <div class="col-12">
                            <label for="add_product_description" class="form-label form-label-modern">Full Description *</label>
                            <textarea name="description" id="add_product_description" required
                                class="form-control form-control-modern" rows="4"
                                placeholder="Detailed product description..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="add_product_cost_price" class="form-label form-label-modern">Cost Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="cost_price" id="add_product_cost_price" required
                                    class="form-control form-control-modern" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="add_product_price" class="form-label form-label-modern">Selling Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="price" id="add_product_price" required
                                    class="form-control form-control-modern" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="add_product_stock" class="form-label form-label-modern">Initial Stock *</label>
                            <input type="number" name="stock" id="add_product_stock" required
                                class="form-control form-control-modern" min="0" placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label for="add_product_image" class="form-label form-label-modern">Main Image *</label>
                            <input type="file" name="image" id="add_product_image"
                                class="form-control form-control-modern" required accept="image/*">
                            <div class="form-text text-muted">This will be the primary product image</div>
                        </div>
                        <div class="col-md-6">
                            <label for="add_additional_images" class="form-label form-label-modern">Additional Images</label>
                            <input type="file" name="additional_images[]" id="add_additional_images"
                                class="form-control form-control-modern" multiple accept="image/*">
                            <div class="form-text text-muted">Optional: Upload multiple images</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" name="add_product" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-modern" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="edit_product_name" class="form-label form-label-modern">Product Name *</label>
                            <input type="text" name="name" id="edit_product_name" required class="form-control form-control-modern">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_product_category" class="form-label form-label-modern">Category *</label>
                            <select name="category_id" id="edit_product_category" required class="form-select form-control-modern">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc_html($cat['id']) ?>"><?= esc_html($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_product_short_description" class="form-label form-label-modern">Short Description</label>
                            <input type="text" name="short_description" id="edit_product_short_description"
                                class="form-control form-control-modern" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label for="edit_product_description" class="form-label form-label-modern">Full Description *</label>
                            <textarea name="description" id="edit_product_description" required
                                class="form-control form-control-modern" rows="4"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_cost_price" class="form-label form-label-modern">Cost Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="cost_price" id="edit_product_cost_price" required
                                    class="form-control form-control-modern" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_price" class="form-label form-label-modern">Selling Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="price" id="edit_product_price" required
                                    class="form-control form-control-modern" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_stock" class="form-label form-label-modern">Current Stock *</label>
                            <input type="number" name="stock" id="edit_product_stock" required
                                class="form-control form-control-modern" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_product_image" class="form-label form-label-modern">Change Main Image</label>
                            <input type="file" name="image" id="edit_product_image"
                                class="form-control form-control-modern" accept="image/*">
                            <div class="form-text text-muted">Leave blank to keep current image</div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_additional_images" class="form-label form-label-modern">Add More Images</label>
                            <input type="file" name="additional_images[]" id="edit_additional_images"
                                class="form-control form-control-modern" multiple accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" name="update_product" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-modern" id="manageImagesModal" tabindex="-1" aria-labelledby="manageImagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageImagesModalLabel">
                    <i class="bi bi-images me-2"></i>Manage Product Images
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="file" id="newImageInput" class="form-control" accept="image/*" style="display: none;">
                    <button type="button" id="uploadNewImageBtn" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-upload me-2"></i>Upload New Image
                    </button>
                </div>
                <div id="image-gallery-container" class="row g-3">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-modern btn-modern" data-bs-dismiss="modal">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Product Modal Handler
        const editProductModal = document.getElementById('editProductModal');
        if (editProductModal) {
            editProductModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;

                // Get data from button attributes
                const id = button.dataset.id;
                const name = button.dataset.name;
                const shortDescription = button.dataset.shortDescription;
                const categoryId = button.dataset.categoryId;
                const description = button.dataset.description;
                const price = button.dataset.price;
                const costPrice = button.dataset.costPrice;
                const stock = button.dataset.stock;

                // Populate form fields
                modal.querySelector('#edit_product_id').value = id;
                modal.querySelector('#edit_product_name').value = name;
                modal.querySelector('#edit_product_short_description').value = shortDescription;
                modal.querySelector('#edit_product_category').value = categoryId;
                modal.querySelector('#edit_product_description').value = description;
                modal.querySelector('#edit_product_price').value = price;
                modal.querySelector('#edit_product_cost_price').value = costPrice;
                modal.querySelector('#edit_product_stock').value = stock;
            });
        }

        // Image Management
        const productsTable = document.getElementById('productsTable');
        const manageImagesModalEl = document.getElementById('manageImagesModal');
        const newImageInput = document.getElementById('newImageInput');
        const uploadNewImageBtn = document.getElementById('uploadNewImageBtn');

        // Function to fetch and render images
        function fetchAndRenderImages(productId) {
            const imageGalleryContainer = document.getElementById('image-gallery-container');
            imageGalleryContainer.innerHTML = `
                    <div class="col-12 text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading images...</p>
                    </div>`;

            fetch(`ajax/fetch_product_images.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        imageGalleryContainer.innerHTML = '';
                        if (data.images.length === 0) {
                            imageGalleryContainer.innerHTML = `
                                    <div class="col-12 text-center p-5">
                                        <i class="bi bi-images" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-2 text-muted">No images found for this product.</p>
                                    </div>`;
                            return;
                        }

                        data.images.forEach(img => {
                            const col = document.createElement('div');
                            col.className = 'col-md-4 col-lg-3';
                            col.id = `image-card-${img.id}`;

                            let badge = img.is_main ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2">Main</span>' : '';
                            let setMainBtn = !img.is_main ? `<button class="btn btn-sm btn-outline-success set-main-btn" data-image-path="${img.path}">Set as Main</button>` : '';
                            let deleteBtn = !img.is_main ? `<button class="btn btn-sm btn-outline-danger delete-image-btn" data-image-id="${img.id}">Delete</button>` : '<span class="text-muted small d-block mt-2">Cannot delete main image</span>';

                            col.innerHTML = `
                                    <div class="card h-100 position-relative" style="border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-md);">
                                        <img src="assets/uploads/${img.path}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                        ${badge}
                                        <div class="card-body text-center">
                                            <div class="d-grid gap-2">
                                                ${setMainBtn}
                                                ${deleteBtn}
                                            </div>
                                        </div>
                                    </div>`;
                            imageGalleryContainer.appendChild(col);
                        });
                    } else {
                        imageGalleryContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger">${data.message}</div></div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    imageGalleryContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load images.</div></div>';
                });
        }

        // Handle manage images modal
        if (manageImagesModalEl) {
            manageImagesModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const productId = button.dataset.id;
                this.dataset.productId = productId;
                fetchAndRenderImages(productId);
            });

            uploadNewImageBtn.addEventListener('click', function() {
                newImageInput.click();
            });

            newImageInput.addEventListener('change', function() {
                const productId = manageImagesModalEl.dataset.productId;
                const file = this.files[0];

                if (file) {
                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('image', file);

                    fetch('ajax/upload_product_image.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                fetchAndRenderImages(productId);
                            } else {
                                alert(`Error: ${data.message}`);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });

            // Event delegation for image management buttons
            document.getElementById('image-gallery-container').addEventListener('click', function(e) {
                const productId = manageImagesModalEl.dataset.productId;

                // Handle Set as Main
                if (e.target.classList.contains('set-main-btn')) {
                    const imagePath = e.target.dataset.imagePath;
                    if (confirm('Set this as the main image? The current main image will become an additional image.')) {
                        fetch('ajax/set_main_image.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `product_id=${productId}&image_path=${imagePath}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    fetchAndRenderImages(productId);
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }

                // Handle Delete Image
                if (e.target.classList.contains('delete-image-btn')) {
                    const imageId = e.target.dataset.imageId;
                    if (confirm('Permanently delete this image?')) {
                        fetch('ajax/delete_product_image.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `image_id=${imageId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById(`image-card-${imageId}`).remove();
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }
            });
        }

        // Handle product actions
        if (productsTable) {
            productsTable.addEventListener('click', function(e) {
                const target = e.target.closest('button');
                if (!target) return;

                const productId = target.dataset.id;

                // Delete product
                if (target.classList.contains('delete-product-btn')) {
                    if (confirm('Are you sure you want to delete this product?')) {
                        fetch('ajax/soft_delete_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
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

                // Restore product
                if (target.classList.contains('restore-product-btn')) {
                    if (confirm('Are you sure you want to restore this product?')) {
                        fetch('ajax/restore_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
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

            // Handle status toggle
            productsTable.addEventListener('change', function(e) {
                if (e.target.classList.contains('status-toggle')) {
                    const toggle = e.target;
                    const productId = toggle.dataset.id;
                    const newStatus = toggle.checked ? 1 : 0;

                    fetch('ajax/toggle_product_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${productId}&status=${newStatus}`
                        })
                        .then(response => response.json())
                        .catch(error => console.error('Error:', error));
                }
            });
        }

        // Add smooth transitions and animations
        const cards = document.querySelectorAll('.main-card, .stat-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate__animated', 'animate__fadeInUp');
        });

        // Add hover effects to buttons
        const buttons = document.querySelectorAll('.btn-modern');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#productsTableBody tr');

        rows.forEach(row => {
            const categoryNameCell = row.querySelector('.product-name');
            if (categoryNameCell) {
                const categoryName = categoryNameCell.textContent.toLowerCase();
                row.style.display = categoryName.includes(searchTerm) ? '' : 'none';
            }
        });
    });
</script>


<?php include 'includes/footer.php'; ?>