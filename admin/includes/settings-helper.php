<?php
function getSiteSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$settings = getSiteSettings($pdo);
