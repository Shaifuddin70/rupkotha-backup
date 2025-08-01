<?php
// ajax/hero_slider_partial.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Pagination
$limit = 5;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

$total_items = $pdo->query("SELECT COUNT(id) FROM hero_products")->fetchColumn();
$total_pages = ceil($total_items / $limit);

$stmt = $pdo->prepare(
    "SELECT h.*, p.name AS product_name FROM hero_products h JOIN products p ON h.product_id = p.id ORDER BY h.id DESC LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$hero_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h4 class="mb-3">Slider Items</h4>
<table class="table table-bordered table-hover align-middle">
    <thead>
    <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Title</th>
        <th>Product Linked</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($hero_items)): ?>
        <tr><td colspan="6" class="text-center">No slider items found.</td></tr>
    <?php else: ?>
        <?php foreach ($hero_items as $item): ?>
            <tr id="hero-item-row-<?= $item['id'] ?>">
                <td><?= esc_html($item['id']) ?></td>
                <td><img src="assets/uploads/<?= esc_html($item['image']) ?>" width="100" class="img-thumbnail"></td>
                <td><?= esc_html($item['title']) ?></td>
                <td><?= esc_html($item['product_name']) ?></td>
                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch" data-id="<?= $item['id'] ?>" <?= $item['is_active'] ? 'checked' : '' ?>>
                        <span class="badge <?= $item['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $item['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </div>
                </td>
                <td>
                    <button class="btn btn-sm btn-warning edit-btn" data-id="<?= $item['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $item['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<!-- Pagination Links -->
<?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
