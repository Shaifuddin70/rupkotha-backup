
<div>
    Showing <?= min($offset + 1, $totalHeroItems) ?> to <?= min($offset + $limit, $totalHeroItems) ?> of <?= $totalHeroItems ?> entries
</div>
<nav aria-label="Page navigation">
    <ul class="pagination mb-0">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
