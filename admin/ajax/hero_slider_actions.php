<?php
// ajax/hero_slider_actions.php

// Set the header FIRST to guarantee the response type is JSON.
header('Content-Type: application/json');

// Start the session immediately, as it's needed for the functions.
session_start();

// Use a try-catch block to gracefully handle any fatal errors
// and still return a valid JSON response.
try {
    // Use __DIR__ to build reliable paths that will not fail.
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    $response = ['status' => 'error', 'message' => 'Invalid Request'];

    // --- Handle DELETE Action ---
    if (isset($_POST['delete_item'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt_img = $pdo->prepare("SELECT image FROM hero_products WHERE id = :id");
            $stmt_img->execute([':id' => $id]);
            $image_name = $stmt_img->fetchColumn();

            $delete_stmt = $pdo->prepare("DELETE FROM hero_products WHERE id = :id");
            if ($delete_stmt->execute([':id' => $id])) {
                if ($image_name) {
                    $image_path = __DIR__ . '/../assets/uploads/' . $image_name;
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                $response = ['status' => 'success', 'message' => 'Item deleted successfully.'];
            } else {
                $response['message'] = 'Failed to delete item from the database.';
            }
        }
    }

    // --- Handle UPDATE Action ---
    if (isset($_POST['update_item'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($id && $product_id && !empty($title)) {
            $stmt_img = $pdo->prepare("SELECT image FROM hero_products WHERE id = :id");
            $stmt_img->execute([':id' => $id]);
            $current_image = $stmt_img->fetchColumn();

            // This function call is now safe.
            $image_to_save = handleImageUpload($_FILES['image'], $current_image);

            if ($image_to_save !== false) {
                $stmt = $pdo->prepare(
                    "UPDATE hero_products SET 
                        product_id = :product_id, title = :title, subtitle = :subtitle, 
                        image = :image, is_active = :is_active, updated_at = NOW() 
                    WHERE id = :id"
                );
                $params = [
                    ':product_id' => $product_id, ':title' => $title, ':subtitle' => $subtitle,
                    ':image' => $image_to_save, ':is_active' => $is_active, ':id' => $id
                ];
                if ($stmt->execute($params)) {
                    $response = ['status' => 'success', 'message' => 'Item updated successfully.'];
                } else {
                    $response['message'] = 'Database update failed.';
                }
            } else {
                $response['message'] = $_SESSION['error_message'] ?? 'Image upload failed.';
            }
        } else {
            $response['message'] = 'Missing required fields (ID, Product, Title).';
        }
    }

} catch (Throwable $e) {
    // If any fatal error occurs, catch it and report it as a clean JSON error.
    // This prevents the HTML error message from being sent.
    $response = [
        'status' => 'error',
        'message' => 'A server error occurred. Please check the server logs.',
        'error_details' => $e->getMessage(), // For debugging
        'error_file' => $e->getFile(), // For debugging
        'error_line' => $e->getLine() // For debugging
    ];
}

// Finally, echo the clean JSON response.
echo json_encode($response);