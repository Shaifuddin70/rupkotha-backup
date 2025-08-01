<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Initialize messages from session
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- Handle ADD action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slider_item'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($product_id) || empty($title)) {
        $_SESSION['error_message'] = "Product and Title are required.";
    } else {
        $image_name = handleImageUpload($_FILES['image']); // The function now sets session errors internally
        if ($image_name !== false) {
            $stmt = $pdo->prepare(
                "INSERT INTO hero_products (product_id, title, subtitle, image, is_active) VALUES (:product_id, :title, :subtitle, :image, :is_active)"
            );
            $params = [
                ':product_id' => $product_id,
                ':title' => $title,
                ':subtitle' => $subtitle,
                ':image' => $image_name,
                ':is_active' => $is_active
            ];
            if ($stmt->execute($params)) {
                $_SESSION['success_message'] = "Slider item added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to add slider item.";
            }
        }
    }
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch products for the dropdown
$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

    <h2 class="page-title">Hero Slider Management</h2>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <div class="card p-4 mb-4">
        <form method="post" enctype="multipart/form-data">
            <h4>Add New Slider Item</h4>
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select" required>
                        <option value="" disabled selected>-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subtitle</label>
                    <input type="text" name="subtitle" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" class="form-check-input" role="switch" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success" name="add_slider_item">Add Item</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div id="hero-slider-table-container">
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit Slider Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editItemForm" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/hero-slider.js"></script>

<?php include 'includes/footer.php'; ?>