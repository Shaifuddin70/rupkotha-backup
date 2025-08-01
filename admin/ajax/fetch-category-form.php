<?php
// admin/ajax/fetch-category-form.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
if (!isset($_SESSION['admin_id'])) {
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit;
}

$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$category_id) {
    echo '<div class="alert alert-danger">Invalid category ID.</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo '<div class="alert alert-danger">Category not found.</div>';
    exit;
}
?>

<!-- This HTML is sent back to the modal -->
<input type="hidden" name="id" value="<?= esc_html($category['id']) ?>">
<div class="mb-3">
    <label for="edit_category_name" class="form-label">Category Name</label>
    <input type="text" name="name" id="edit_category_name" class="form-control" value="<?= esc_html($category['name']) ?>" required>
</div>
