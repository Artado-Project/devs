<?php

// Kullanıcı avatar URL'sini döndürür
// Kullanıcı avatar URL'sini döndürür
function get_user_avatar($db_path, $is_in_user_dir = false) {
    $default = 'https://artado.xyz/assest/img/artado-yeni.png';
    
    // Yol boşsa varsayılanı döndür
    if (empty($db_path)) {
        return $default;
    }
    
    // Tam URL ise direkt döndür
    if (filter_var($db_path, FILTER_VALIDATE_URL)) {
        return $db_path;
    }
    
    // Başındaki gereksiz karakterleri temizle (./ veya /)
    $clean_path = ltrim($db_path, './');
    
    // Eğer veritabanında "public/" ile başlıyorsa ve biz "user/" klasörü içindeysek
    if ($is_in_user_dir) {
        // user/ dizininden public'e erişmek için bir üst dizine çık: "../"
        return '../' . $clean_path;
    }
    
    // Ana dizindeysek direkt yolu döndür
    return $clean_path;
}

// Proje resmi yükleme fonksiyonu
// Başarılı olursa veritabanına kaydedilecek yolu (örn: public/uploads/img/resim.jpg),
// resim yüklenmediyse null, hata varsa string hata mesajı döndürür.
function uploadProjectImage() {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB (örnek limit)

        if (!in_array($file['type'], $allowed_types)) {
            return "Resim için sadece JPEG, PNG ve GIF formatları desteklenir.";
        }
        if ($file['size'] > $max_size) {
            return "Resim dosya boyutu en fazla 5MB olabilir.";
        }

        // Proje kök dizinini belirle (__DIR__ includes klasörünü gösterir, bir üste çıkmalıyız)
        $project_root = dirname(__DIR__); 
        $upload_dir = $project_root . '/public/uploads/img/'; 

        // Hedef dizin yoksa oluştur
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("Proje resmi yükleme dizini oluşturulamadı: " . $upload_dir);
                return "Proje resmi yükleme dizini oluşturulamadı.";
            }
        }

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Benzersiz dosya adı oluşturmak daha iyi olur
        $file_name = uniqid('proj_img_') . '.' . $file_extension; 
        $target_path = $upload_dir . $file_name; // Tam sunucu yolu

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Veritabanına kaydedilecek yol (public/ ile başlayan)
            $db_image_path = 'public/uploads/img/' . $file_name; 
            return $db_image_path; // Başarılı, veritabanı yolunu döndür

        } else {
            error_log("Proje resmi taşınamadı (muhtemelen izinler): " . $target_path);
            return "Proje resmi yüklenirken bir hata oluştu.";
        }

    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        // Resim yüklenmemiş, bu bir hata değil.
        return null; 
    } elseif (isset($_FILES['image'])) {
        // Başka bir yükleme hatası
        return "Resim yükleme hatası (Kod: " . $_FILES['image']['error'] . ").";
    }

    // $_FILES['image'] hiç set edilmemişse.
    return null; 
}
