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


<div class="main-card">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h3 class="card-title-modern mb-0">Customer List</h3>
        <div class="search-container">
            <input type="text" id="customerSearchInput" class="form-control search-input" placeholder="Search by name or email...">
            <i class="bi bi-search search-icon"></i>
        </div>
    </div>
    <div class="table-responsive">
        <table id="customersTable" class="table table-modern">
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
            <tbody id="customersTableBody">
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                            <h4>No customers found</h4>
                            <p class="text-muted">Users will appear here after registration</p>
                        </td>
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
        <div class="pagination-modern">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
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
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script>
    // Client-side filtering by name/email/phone
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('customerSearchInput');
        const tbody = document.getElementById('customersTableBody');
        if (!input || !tbody) return;
        input.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            tbody.querySelectorAll('tr').forEach(tr => {
                const tds = tr.querySelectorAll('td');
                const hay = Array.from(tds).slice(1, 5).map(td => td.textContent.toLowerCase()).join(' ');
                tr.style.display = hay.includes(q) ? '' : 'none';
            });
        });
    });
</script>