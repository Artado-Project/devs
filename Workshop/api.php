<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
 
require 'config.php';
 
try {
    // approval_status filtresi tamamen kaldırıldı
    $sql = "
        SELECT 
            p.id, p.title as name, p.description, p.category, 
            p.file_path, COALESCE(p.image_path, pi.image_path) as image_path, 
            u.username as author 
        FROM projects p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN (
            SELECT project_id, MIN(image_path) as image_path FROM project_images GROUP BY project_id
        ) pi ON p.id = pi.project_id
        WHERE p.is_private = 0 
        AND (
            p.category LIKE '%eklenti%' OR 
            p.category LIKE '%plugin%' OR 
            p.category LIKE '%tema%' OR 
            p.category LIKE '%theme%' OR 
            p.category LIKE '%ana_sayfa%' OR 
            p.category LIKE '%home%' OR
            p.category LIKE '%artado_logo%'
        )
        ORDER BY p.upload_date DESC
    ";
 
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    $themes = [];
    $plugins = [];
    $logos = []; 
    $homeThemes = []; 
 
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $basePath = str_replace(['/Workshop/api.php', '/workshop/api.php'], '/', $scriptPath);
    $basePath = preg_replace('#/+#', '/', $basePath);
    $baseUrl = $protocol . "://" . $host . rtrim($basePath, '/') . '/';
 
    foreach ($results as $row) {
        $path = $row['file_path'];
        $imagePath = $row['image_path'];
 
        $cleanPath = preg_replace('#^(\.+/*|/+)#', '', $path);
        $cleanImagePath = preg_replace('#^(\.+/*|/+)#', '', $imagePath);
 
        $item = [
            'id' => $row['id'],
            'name' => $row['name'],
            'author' => $row['author'] ?? 'Anonim',
            'description' => $row['description'],
            'download_url' => !empty($path) ? $baseUrl . $cleanPath : null,
            'image_url' => !empty($imagePath) ? $baseUrl . $cleanImagePath : null,
            'category' => $row['category']
        ];
 
        // Boşlukları temizle ve küçük harfe çevir
        $category = trim(mb_strtolower($row['category'] ?? '', 'UTF-8'));
        $lowerName = mb_strtolower($row['name'] ?? '', 'UTF-8');
 
        // Gelişmiş Kategori Kontrolü
        if ($category === 'artado_logo' || strpos($category, 'logo') !== false) {
            $logos[] = $item;
        } elseif ($category === 'artado_tema' || $category === 'site_tema' || strpos($category, 'tema') !== false || strpos($category, 'theme') !== false) {
            $themes[] = $item;
        } elseif ($category === 'ana_sayfa' || $category === 'home') {
            $homeThemes[] = $item;
        } elseif ($category === 'eklenti' || $category === 'plugin') {
            $plugins[] = $item;
        } else {
            // Geriye dönük isimle tahmin etme
            if (strpos($lowerName, 'tema') !== false || strpos($lowerName, 'theme') !== false) {
                $themes[] = $item;
            } elseif (strpos($lowerName, 'logo') !== false || strpos($lowerName, 'bayrak') !== false) {
                $logos[] = $item;
            } else {
                $plugins[] = $item;
            }
        }
    }
 
    foreach ($homeThemes as $homeTheme) {
        $homeTheme['category'] = 'home';
        $plugins[] = $homeTheme;
    }
 
    echo json_encode([
        'success' => true,
        'themes' => $themes,
        'plugins' => $plugins,
        'logos' => $logos 
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
 
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>