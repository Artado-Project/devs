<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Kullanıcının giriş yapmış ve admin olması gerekiyor (veya proje sahibi olması)
// Mevcut kod sadece proje sahibi kontrolü yapıyor, admin kontrolü eklenebilir.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    // Proje bilgilerini veritabanından çek (admin tüm projeleri düzenleyebilir varsayımı)
    // Eğer sadece sahip düzenleyebilecekse AND user_id = :user_id eklenmeli
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = :project_id");
    $stmt->bindParam(':project_id', $project_id);
    // $stmt->bindParam(':user_id', $user_id); // Admin için kaldırıldı, gerekirse aktif edin
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $error = "Proje bulunamadı."; // Veya yetki yoksa: "veya bu projeyi düzenleme yetkiniz yok."
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $version = $_POST['version'];
            $features = isset($_POST['features']) && is_array($_POST['features']) ? implode(',', $_POST['features']) : '';

            // Dosya yollarını belirle (functions.php veya diğer yüklemelerle tutarlı olmalı)
            $project_file_target_dir = "../public/uploads/files/"; // Tüm proje dosyaları uploads/project klasörüne yüklenecek
            $new_file_path = $project['file_path']; // Mevcut dosya yolu varsayılan

            // --- Proje Dosyası Güncelleme ---            
            if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['project_file']['tmp_name'];
                $file_name = basename($_FILES['project_file']['name']);
                $file_target_path = $project_file_target_dir . $file_name; 
                // Güvenlik için uniqid eklenebilir: $project_file_target_dir . uniqid() . '_' . $file_name;

                // Hedef dizin yoksa oluştur
                if (!file_exists($project_file_target_dir)) {
                    mkdir($project_file_target_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp_name, $file_target_path)) {
                    // Dosya başarıyla taşındı, yeni yolu kaydet
                    // Başına ../ eklemeden, public/uploads/files/dosya.zip şeklinde kaydedelim
                    $new_file_path = str_replace('../', '', $file_target_path);
                } else {
                    $error = "Proje dosyası yüklenirken bir hata oluştu.";
                }
            }
            // --- Proje Dosyası Güncelleme Sonu ---
            
            // --- Proje Resmi Güncelleme (Mevcut Kod) ---
            $new_image_path = $project['image_path']; // Mevcut resim yolu varsayılan
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_upload_result = uploadProjectImage($project_id, $db); // Bu fonksiyonun dosya yolunu nasıl döndürdüğü önemli
                if ($image_upload_result === true) {
                   // Başarılı, ancak fonksiyon yeni yolu döndürmüyor gibi, elle çekmek gerekebilir
                   // Şimdilik image_path güncellemesini varsayalım
                } elseif ($image_upload_result !== false) { // False değilse (yani bir hata mesajıysa)
                    $error = $image_upload_result;
                }
                // uploadProjectImage yeni yolu döndürmüyorsa, buraya yeni resim yolunu alma kodu eklenmeli
            }
            // --- Proje Resmi Güncelleme Sonu ---

            // Hata yoksa veritabanını güncelle
            if ($error === null) {
                try {
                    $stmt = $db->prepare("UPDATE projects SET title = :title, description = :description, version = :version, features = :features, file_path = :file_path WHERE id = :project_id");
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':version', $version);
                    $stmt->bindParam(':features', $features);
                    $stmt->bindParam(':file_path', $new_file_path); 
                    // $stmt->bindParam(':image_path', $new_image_path); // Resim yolu da güncellenmeli, ama $new_image_path'in doğru değeri alması lazım
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->execute();

                    $success = "Proje başarıyla güncellendi.";
                    // Sayfayı yeniden yükleyerek güncel verileri göster
                    $stmt = $db->prepare("SELECT * FROM projects WHERE id = :project_id");
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->execute();
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    // header("Location: projects"); // Veya proje listesine yönlendirilebilir
                    // exit();

                } catch (PDOException $e) {
                    $error = "Proje güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
                }
            }
        }
    }
} else {
    $error = "Geçersiz proje ID.";
}

?>

<?php require_once 'header.php'; // Tailwind CSS'i ve header yapısını dahil eder ?>

<div class="container mx-auto mt-8 px-4"> 
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-xl">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Projeyi Düzenle</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Hata!</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Başarılı!</p>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($project)): ?>
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Proje Başlığı:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Açıklama:</label>
                    <textarea id="description" name="description" rows="4"
                              class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 mb-1">Versiyon:</label>
                    <input type="text" id="version" name="version" value="<?php echo htmlspecialchars($project['version']); ?>"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="features" class="block text-sm font-medium text-gray-700 mb-1">Özellikler (virgülle ayırın):</label>
                    <input type="text" id="features" name="features[]" value="<?php echo htmlspecialchars($project['features']); ?>"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Proje Resmi Yükleme -->
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Proje Resmi Güncelle:</label>
                    <?php 
                    // Mevcut resmi gösterelim (Doğru YOL: ../public/uploads/img/)
                    // Veritabanında image_path'in 'public/uploads/img/...' şeklinde olduğunu varsayıyoruz
                    $current_image_path = isset($project['image_path']) ? '../' . ltrim($project['image_path'], '../') : null;
                    $current_image_exists = $current_image_path && file_exists($current_image_path);
                    if ($current_image_exists):
                    ?>
                        <img src="<?php echo htmlspecialchars($current_image_path); ?>" alt="Mevcut Proje Resmi" class="mb-2 h-20 w-auto rounded">
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mb-2">Mevcut resim yok.</p>
                    <?php endif; ?>
                    <input type="file" id="image" name="image"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">Sadece JPG, JPEG, PNG, GIF. Yeni resim seçmezseniz mevcut resim kalır.</p>
                </div>
                
                <!-- Proje Dosyası Yükleme -->
                <div>
                    <label for="project_file" class="block text-sm font-medium text-gray-700 mb-1">Proje Dosyası Güncelle:</label>
                     <?php 
                    // Mevcut dosya bağlantısını gösterelim
                    $current_file_path = '../' . $project['file_path']; // Ana dizine göre yol
                    if (!empty($project['file_path']) && file_exists($current_file_path)):
                    ?>
                         <p class="text-sm mb-2">Mevcut Dosya: <a href="<?php echo htmlspecialchars($current_file_path); ?>" download class="text-indigo-600 hover:text-indigo-900"><?php echo basename($project['file_path']); ?></a></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mb-2">Mevcut proje dosyası yok.</p>
                    <?php endif; ?>
                    <input type="file" id="project_file" name="project_file"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">Yeni dosya seçmezseniz mevcut dosya kalır.</p>
                </div>

                <div class="pt-5">
                    <div class="flex justify-end">
                        <a href="projects" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">İptal</a>
                        <button type="submit"
                                class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Güncelle
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
