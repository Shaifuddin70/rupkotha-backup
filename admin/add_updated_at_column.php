<?php
/**
 * Quick Script: Add updated_at column to orders table
 * 
 * This script adds the missing updated_at column to track order modifications
 */

require_once 'includes/db.php';

try {
    // Check if column already exists
    $check_column = $pdo->query("SHOW COLUMNS FROM orders LIKE 'updated_at'");
    
    if ($check_column->rowCount() > 0) {
        echo "✅ The updated_at column already exists in the orders table.\n";
    } else {
        // Add the updated_at column
        $pdo->exec("ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        
        // Initialize existing orders with their created_at date
        $pdo->exec("UPDATE orders SET updated_at = created_at WHERE updated_at IS NULL");
        
        echo "✅ Successfully added updated_at column to orders table!\n";
        echo "✅ Initialized existing orders with their creation dates.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
