<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Ensure admin is logged in
if (!isAdmin()) {
    redirect('login.php');
}

// --- PAGINATION SETUP ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 15;
$offset = ($page - 1) * $per_page;

$total_customers = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();
$total_pages = ceil($total_customers / $per_page);

// Fetch customers for the current page
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="page-title">Manage Customers</h2>

<div class="card p-4">
    <h4 class="mb-3">Customer List</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Registered On</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No customers found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= esc_html($customer['id']) ?></td>
                        <td><?= esc_html($customer['username']) ?></td>
                        <td><?= esc_html($customer['email']) ?></td>
                        <td><?= esc_html($customer['phone'] ?? 'N/A') ?></td>
                        <td><?= esc_html($customer['address'] ?? 'N/A') ?></td>
                        <td><?= format_date($customer['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
