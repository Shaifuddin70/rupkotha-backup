<?php

if (!isset($_SESSION['admin_id'])) {
    // Not logged in as admin, redirect to login page
    header('Location: ../admin/login');
    exit;
}
?>
