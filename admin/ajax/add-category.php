<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Category name required.']);
    exit;
}

// Check for duplicate category name
$stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
$stmt->execute([$name]);
$exists = $stmt->fetchColumn();

if ($exists > 0) {
    echo json_encode(['success' => false, 'message' => 'Category already exists.']);
    exit;
}

// Insert category
$stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
$success = $stmt->execute([$name]);

if ($success) {
    $id = $pdo->lastInsertId();
    $created_at = date('Y-m-d H:i:s');
    $escapedName = htmlspecialchars($name);

    // Return the HTML row to insert
    $newRow = "
        <tr id='cat-{$id}'>
            <td>New</td>
            <td>{$escapedName}</td>
            <td>{$created_at}</td>
            <td>
                <a href='category-items?id={$id}' class='btn btn-sm btn-primary'>View Items</a>
                <a href='edit-category?id={$id}' class='btn btn-sm btn-warning'>Edit</a>
                <button class='btn btn-sm btn-danger delete-btn' data-id='{$id}'>Delete</button>
            </td>
        </tr>
    ";

    echo json_encode(['success' => true, 'newRowHtml' => $newRow]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed.']);
}
