<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    echo "<p>Invalid ID</p>";
    include 'includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    header("Location: categories");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();
?>

<h2>Edit Category</h2>
<form method="post" class="w-50">
    <div class="mb-3">
        <label>Category Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" class="form-control" required>
    </div>
    <button class="btn btn-primary">Update</button>
    <a href="categories" class="btn btn-secondary">Cancel</a>
</form>

<?php include 'includes/footer.php'; ?>
