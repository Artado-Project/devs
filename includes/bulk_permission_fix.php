<?php

/**
 * Tüm dosya yükleme script'lerini güncelleyen script
 * Bu script tüm create-* dosyalarındaki izin sorunlarını düzeltir
 */

function updateAllUploadFiles($baseDir) {
    $uploadFiles = [
        'user/create-eklenti.php',
        'user/create-tema.php', 
        'user/create-logo.php',
        'user/create-uyg-pc.php',
        'user/create-uyg-mobil.php',
        'user/create-game-pc.php',
        'user/create-game-mobil.php',
        'user/account-profile.php',
        'user/update_project.php',
        'admin/add_project.php',
        'admin/edit_project.php'
    ];
    
    $results = [];
    
    foreach ($uploadFiles as $file) {
        $filePath = $baseDir . '/' . $file;
        if (file_exists($filePath)) {
            $result = updateFilePermissions($filePath);
            $results[$file] = $result;
        } else {
            $results[$file] = ['status' => 'not_found', 'message' => 'Dosya bulunamadı'];
        }
    }
    
    return $results;
}

function updateFilePermissions($filePath) {
    try {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['status' => 'error', 'message' => 'Dosya okunamadı'];
        }
        
        $originalContent = $content;
        
        // mkdir(0755) -> mkdir(0777) + chmod() ekle
        $content = preg_replace_callback(
            '/mkdir\(([^,]+),\s*0755,\s*true\)/',
            function($matches) {
                $dir = $matches[1];
                return "mkdir($dir, 0777, true)\n                chmod($dir, 0777)";
            },
            $content
        );
        
        // Mevcut mkdir satırlarına chmod ekle
        $content = preg_replace_callback(
            '/(mkdir\(([^,]+),\s*0777,\s*true\))\s*(?!\s*chmod)/',
            function($matches) {
                return $matches[1] . "\n                chmod(" . $matches[2] . ", 0777)";
            },
            $content
        );
        
        // move_uploaded_file sonrası chmod ekle
        $content = preg_replace_callback(
            '/(move_uploaded_file\([^)]+\))\s*(?!\s*chmod)/',
            function($matches) {
                return $matches[1] . " {\n                    chmod(\$target_file, 0644);\n                }";
            },
            $content
        );
        
        // Değişiklik varsa dosyayı güncelle
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) !== false) {
                return ['status' => 'updated', 'message' => 'Dosya güncellendi'];
            } else {
                return ['status' => 'error', 'message' => 'Dosya yazılamadı'];
            }
        } else {
            return ['status' => 'no_changes', 'message' => 'Değişiklik gerekmiyor'];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Script doğrudan çalıştırıldığında
if (basename($_SERVER['PHP_SELF']) === 'bulk_permission_fix.php') {
    $baseDir = dirname(__DIR__);
    $results = updateAllUploadFiles($baseDir);
    
    header('Content-Type: text/plain');
    echo "=== Toplu İzin Düzeltme Raporu ===\n\n";
    
    foreach ($results as $file => $result) {
        echo "$file: {$result['status']} - {$result['message']}\n";
    }
    
    echo "\nToplu izin düzeltme tamamlandı!";
}

?>
