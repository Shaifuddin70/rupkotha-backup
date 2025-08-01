<?php
require_once 'includes/header.php';

$category_id = $_GET['id'] ?? null;
if (!$category_id) {
    echo "<p class='text-danger'>Invalid category ID.</p>";
    include 'includes/footer.php';
    exit;
}

// Get category name
$stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    echo "<p class='text-danger'>Category not found.</p>";
    include 'includes/footer.php';
    exit;
}

// Fetch items under this category
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll();
?>

<h2 class="page-title">Items in Category: <?= htmlspecialchars($category['name']) ?></h2>

<?php if ($products): ?>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Product ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Created At</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $prod): ?>
            <tr>
                <td><?= $prod['id'] ?></td>
                <td><img src="assets/uploads/<?= $prod['image'] ?>" alt="" width="60"></td>
                <td><?= htmlspecialchars($prod['name']) ?></td>
                <td><?= $prod['price'] ?></td>
                <td><?= $prod['created_at'] ?></td>

            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No products found in this category.</p>
<?php endif; ?>

<a href="categories" class="btn btn-secondary mt-3">← Back to Categories</a>

<?php include 'includes/footer.php'; ?>
