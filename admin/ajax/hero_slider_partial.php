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
<table class="table table-modern align-middle mb-0">
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
            <tr>
                <td colspan="6" class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-images"></i></div>
                    <h4>No slider items found</h4>
                    <p class="text-muted">Create your first slider item using the form above</p>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($hero_items as $item): ?>
                <tr id="hero-item-row-<?= $item['id'] ?>">
                    <td><?= esc_html($item['id']) ?></td>
                    <td>
                        <img src="assets/uploads/<?= esc_html($item['image']) ?>" alt="<?= esc_html($item['title']) ?>" class="product-image">
                    </td>
                    <td><?= esc_html($item['title']) ?></td>
                    <td><?= esc_html($item['product_name']) ?></td>
                    <td>
                        <label class="toggle-switch me-2">
                            <input class="status-toggle" type="checkbox" data-id="<?= $item['id'] ?>" <?= $item['is_active'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="badge badge-modern <?= $item['is_active'] ? 'badge-status' : 'badge-deleted' ?>">
                            <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-action btn-edit edit-btn" data-id="<?= $item['id'] ?>">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </button>
                            <button class="btn btn-action btn-delete delete-btn" data-id="<?= $item['id'] ?>">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination Links -->
<?php if ($total_pages > 1): ?>
    <nav class="pagination-modern">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link-modern" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link-modern" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link-modern" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
<?php endif; ?>