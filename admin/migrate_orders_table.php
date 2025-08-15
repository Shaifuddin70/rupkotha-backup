<?php
/**
 * Optional Migration Script: Add updated_at column to orders table
 * 
 * This script adds an `updated_at` timestamp column to the orders table
 * to track when orders are last modified.
 * 
 * Run this ONLY ONCE if you want to add timestamp tracking for order updates.
 * 
 * WARNING: Make sure to backup your database before running this migration!
 */

require_once 'includes/header.php';
require_once 'includes/functions.php';

// Ensure admin is logged in
if (!isAdmin()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        // Check if column already exists
        $check_column = $pdo->query("SHOW COLUMNS FROM orders LIKE 'updated_at'");
        if ($check_column->rowCount() > 0) {
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'The updated_at column already exists in the orders table.'];
        } else {
            // Add the updated_at column
            $pdo->exec("ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
            
            // Initialize existing orders with their created_at date
            $pdo->exec("UPDATE orders SET updated_at = created_at WHERE updated_at IS NULL");
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Successfully added updated_at column to orders table.'];
        }
    } catch (Exception $e) {
        error_log("Migration error: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Migration failed: ' . $e->getMessage()];
    }
    
    redirect('migrate_orders_table.php');
}

?>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
        <strong><?= $_SESSION['flash_message']['type'] === 'success' ? 'Success!' : ($_SESSION['flash_message']['type'] === 'warning' ? 'Warning!' : 'Error!') ?></strong>
        <?= $_SESSION['flash_message']['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<div class="main-card">
    <div class="card-header-modern">
        <h3 class="card-title-modern">Database Migration: Add updated_at to Orders</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h5><i class="bi bi-info-circle me-2"></i>Migration Information</h5>
            <p>This migration will add an <code>updated_at</code> timestamp column to the orders table to track when orders are last modified.</p>
            <p><strong>What this migration does:</strong></p>
            <ul>
                <li>Adds <code>updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP</code> column</li>
                <li>Initializes existing orders with their <code>created_at</code> date</li>
                <li>Future order updates will automatically update this timestamp</li>
            </ul>
        </div>
        
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Important Notes:</h6>
            <ul class="mb-0">
                <li><strong>Backup your database before running this migration!</strong></li>
                <li>This migration is optional - the order system works without it</li>
                <li>Only run this migration once</li>
                <li>If you run this migration, you can update the orders.php files to use the updated_at column</li>
            </ul>
        </div>

        <form method="post" onsubmit="return confirm('Are you sure you want to run this migration? Make sure you have backed up your database first!')">
            <button type="submit" name="run_migration" value="1" class="btn btn-primary">
                <i class="bi bi-database me-2"></i>Run Migration
            </button>
            <a href="orders.php" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Orders
            </a>
        </form>
    </div>
</div>

<style>
.main-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin: 2rem 0;
}

.card-header-modern {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
}

.card-title-modern {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.card-body {
    padding: 1.5rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
