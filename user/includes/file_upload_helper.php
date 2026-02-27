<?php

/**
 * Dosya yükleme yardımcı fonksiyonu
 * İzinleri otomatik ayarlar ve güvenlik kontrolleri yapar
 */
function secure_file_upload($file, $target_dir, $allowed_types = [], $max_size = 52428800, $prefix = '') {
    try {
        // Dosya yükleme kontrolü
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Dosya yüklenirken hata oluştu: " . $file['error']);
        }
        
        // Dosya tipi kontrolü
        if (!empty($allowed_types) && !in_array($file['type'], $allowed_types)) {
            throw new Exception("İzin verilmeyen dosya türü: " . $file['type']);
        }
        
        // Dosya boyutu kontrolü
        if ($file['size'] > $max_size) {
            throw new Exception("Dosya boyutu çok büyük. Maksimum: " . ($max_size / 1024 / 1024) . "MB");
        }
        
        // Güvenli dosya adı oluştur
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_filename = $prefix . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = $safe_filename . '.' . $file_extension;
        
        // Hedef dizini kontrol et ve oluştur
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                throw new Exception("Dizin oluşturulamadı: " . $target_dir);
            }
        }
        
        // Dizin izinlerini ayarla
        chmod($target_dir, 0777);
        
        // Tam dosya yolu
        $target_path = $target_dir . $filename;
        
        // Dosyayı taşı
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            throw new Exception("Dosya taşınamadı.");
        }
        
        // Dosya izinlerini ayarla
        chmod($target_path, 0644);
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $target_path,
            'relative_path' => ltrim(str_replace('../', '', $target_path), './')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Resim yükleme yardımcı fonksiyonu
 */
function secure_image_upload($file, $target_dir, $max_size = 5242880, $prefix = '') {
    $allowed_types = [
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp'
    ];
    
    return secure_file_upload($file, $target_dir, $allowed_types, $max_size, $prefix);
}

/**
 * Arşiv dosyası yükleme yardımcı fonksiyonu
 */
function secure_archive_upload($file, $target_dir, $max_size = 52428800, $prefix = '') {
    $allowed_types = [
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-zip-compressed'
    ];
    
    return secure_file_upload($file, $target_dir, $allowed_types, $max_size, $prefix);
}

/**
 * Eski dosyayı güvenli sil
 */
function secure_unlink($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return true; // Dosya yoksa başarılı say
}

?>
