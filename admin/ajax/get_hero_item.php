<?php
// ajax/get_hero_item.php

// STEP 1: Add error reporting to see any crashes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// STEP 2: Use __DIR__ to create a reliable path to the database file
require_once __DIR__ . '/../includes/db.php';

// Set the content type to JSON so the browser knows what to expect
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM hero_products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        // Use output buffering to capture HTML into a variable
        ob_start();
        ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

        <div class="mb-3">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-select" required>
                <?php foreach ($products as $p): ?>
                    <option value="<?= htmlspecialchars($p['id']) ?>" <?= ($p['id'] == $item['product_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($item['subtitle']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Change Image</label>
            <input type="file" name="image" class="form-control">
            <small class="text-muted">Leave blank to keep current image. <img src="assets/uploads/<?= htmlspecialchars($item['image']) ?>" width="150" class="img-thumbnail ms-2"></small>
        </div>

        <div class="form-check form-switch">
            <input type="checkbox" name="is_active" class="form-check-input" role="switch" <?= ($item['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label">Active</label>
        </div>
        <?php
        // Get the captured HTML and prepare the success response
        $html = ob_get_clean();
        $response = ['status' => 'success', 'html' => $html];
    } else {
        $response['message'] = "Item with ID {$id} not found.";
    }
} else {
    $response['message'] = 'Invalid or missing ID.';
}

// Always output a valid JSON response
echo json_encode($response);