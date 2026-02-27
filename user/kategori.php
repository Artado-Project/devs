<?php
require_once 'includes/database.php';

try {
    $sql = "ALTER TABLE projects ADD COLUMN category VARCHAR(50) DEFAULT 'artado_tema' AFTER features";
    $db->exec($sql);
    echo "Kategori sütunu başarıyla eklendi!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Kategori sütunu zaten mevcut.";
    } else {
        echo "Hata: " . $e->getMessage();
    }
}
?>