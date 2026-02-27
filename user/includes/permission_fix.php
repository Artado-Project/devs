<?php

/**
 * Dosya izinlerini kalıcı olarak düzelten fonksiyon
 * Bu script tüm upload dizinlerinin izinlerini ayarlar
 */

function fixDirectoryPermissions($basePath) {
    $directories = [
        $basePath . '/public/uploads',
        $basePath . '/public/uploads/img',
        $basePath . '/public/uploads/files',
        $basePath . '/public/uploads/temp',
        $basePath . '/uploads',
        $basePath . '/uploads/img',
        $basePath . '/uploads/files',
        $basePath . '/uploads/temp'
    ];
    
    $fixed = [];
    $errors = [];
    
    foreach ($directories as $dir) {
        try {
            // Dizin yoksa oluştur
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    $errors[] = "Dizin oluşturulamadı: $dir";
                    continue;
                }
            }
            
            // Dizin izinlerini ayarla
            if (chmod($dir, 0777)) {
                $fixed[] = "Dizin izinleri ayarlandı: $dir (0777)";
            } else {
                $errors[] = "Dizin izinleri ayarlanamadı: $dir";
            }
            
            // Alt dizinlerdeki dosyaların izinlerini de ayarla
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $file) {
                    if ($file->isDir()) {
                        chmod($file->getPathname(), 0777);
                    } elseif ($file->isFile()) {
                        chmod($file->getPathname(), 0644);
                    }
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "Hata oluştu: " . $e->getMessage();
        }
    }
    
    return ['fixed' => $fixed, 'errors' => $errors];
}

/**
 * Dosya yükleme sonrası izinleri otomatik ayarlayan fonksiyon
 */
function setFilePermissions($filePath) {
    if (file_exists($filePath)) {
        // Dosya izinlerini ayarla (okuma/yazma için)
        chmod($filePath, 0644);
        
        // Dosyanın olduğu dizinin izinlerini kontrol et ve ayarla
        $dir = dirname($filePath);
        if (is_dir($dir)) {
            chmod($dir, 0777);
        }
        
        return true;
    }
    return false;
}

/**
 * Güvenli dosya yükleme fonksiyonu (izinsiz)
 */
function secureUploadWithPermissions($file, $targetDir, $filename = null) {
    try {
        // Hedef dizini kontrol et ve oluştur
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Dizin izinlerini ayarla
        chmod($targetDir, 0777);
        
        // Dosya adı belirle
        if ($filename === null) {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        }
        
        $targetPath = $targetDir . '/' . $filename;
        
        // Dosyayı yükle
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Dosya izinlerini ayarla
            setFilePermissions($targetPath);
            return [
                'success' => true,
                'path' => $targetPath,
                'filename' => $filename,
                'relative_path' => str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $targetPath)
            ];
        } else {
            return ['success' => false, 'error' => 'Dosya yüklenemedi'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Bu script doğrudan çalıştırıldığında izinleri düzelt
if (basename($_SERVER['PHP_SELF']) === 'permission_fix.php') {
    $basePath = dirname(__DIR__);
    $result = fixDirectoryPermissions($basePath);
    
    header('Content-Type: text/plain');
    echo "=== Dosya İzinleri Düzeltme Raporu ===\n\n";
    
    if (!empty($result['fixed'])) {
        echo "Başarıyla düzeltilenler:\n";
        foreach ($result['fixed'] as $item) {
            echo "✓ $item\n";
        }
        echo "\n";
    }
    
    if (!empty($result['errors'])) {
        echo "Hatalar:\n";
        foreach ($result['errors'] as $error) {
            echo "✗ $error\n";
        }
    }
    
    echo "\nİzin düzeltme tamamlandı!";
}

?>
