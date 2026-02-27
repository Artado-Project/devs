<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $version = $_POST['version'] ?? '';
    $features = isset($_POST['features']) ? $_POST['features'] : '';
    $project_link = $_POST['project_link'] ?? '';
    $category = $_POST['category'] ?? '';
    $selected_user_id = $_POST['user_id'] ?? $user_id;

    if (empty($title) || empty($description)) {
        $error = "Başlık ve açıklama alanları zorunludur.";
    } else {
        $project_file_path = null;
        $project_image_path = null;

        $upload_dir = '../public/uploads/files/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === UPLOAD_ERR_OK) {
            // Dosya türü kontrolü
            $file_extension = strtolower(pathinfo($_FILES['project_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = "Proje dosyası için sadece .js, .css, .png, .jpg, .jpeg veya .gif dosyaları yüklenebilir.";
            } else {
                $file_tmp_name = $_FILES['project_file']['tmp_name'];
                $file_name = basename($_FILES['project_file']['name']);
                $file_target_path = $upload_dir . uniqid() . '_' . $file_name;

                if (move_uploaded_file($file_tmp_name, $file_target_path)) {
                    $project_file_path = str_replace('../', '', $file_target_path);
                } else {
                    $error = "Proje dosyası yüklenirken bir hata oluştu.";
                }
            }
        }

        $image_result = uploadProjectImage();
        if (is_string($image_result) && $image_result !== '') {
            $error = $image_result;
        } elseif ($image_result !== null) {
            $project_image_path = $image_result;
        }

        if ($error === null) {
            try {
                $stmt = $db->prepare("INSERT INTO projects (user_id, title, description, version, features, file_path, project_link, category) VALUES (:user_id, :title, :description, :version, :features, :file_path, :project_link, :category)");
                $stmt->bindParam(':user_id', $selected_user_id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':version', $version);
                $stmt->bindParam(':features', $features);
                $stmt->bindParam(':file_path', $project_file_path);
                $stmt->bindParam(':project_link', $project_link);
                $stmt->bindParam(':category', $category);
                $stmt->execute();

                $project_id = $db->lastInsertId();

                if ($project_image_path) {
                    $stmt = $db->prepare("INSERT INTO project_images (project_id, image_path) VALUES (:project_id, :image_path)");
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->bindParam(':image_path', $project_image_path);
                    $stmt->execute();
                }

                $success = "Proje başarıyla eklendi!";
                header("Location: projects.php");
                exit();
            } catch (PDOException $e) {
                $error = "Proje eklenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

$stmt = $db->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once 'header.php'; ?>

<div class="container mx-auto mt-8 px-4">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-xl">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Yeni Proje Ekle</h2>

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

        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Proje Sahibi:</label>
                <select id="user_id" name="user_id" required
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($u['id'] == $user_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Proje Başlığı: *</label>
                <input type="text" id="title" name="title" required
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Açıklama: *</label>
                <textarea id="description" name="description" rows="6" required
                          class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 mb-1">Versiyon:</label>
                    <input type="text" id="version" name="version"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           value="<?php echo htmlspecialchars($_POST['version'] ?? ''); ?>">
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Kategori:</label>
                    <input type="text" id="category" name="category"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label for="features" class="block text-sm font-medium text-gray-700 mb-1">Özellikler (virgülle ayırın):</label>
                <input type="text" id="features" name="features"
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       placeholder="Örn: Hızlı, Güvenli, Modern"
                       value="<?php echo htmlspecialchars($_POST['features'] ?? ''); ?>">
            </div>

            <div>
                <label for="project_link" class="block text-sm font-medium text-gray-700 mb-1">Proje Bağlantısı (URL):</label>
                <input type="url" id="project_link" name="project_link"
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       placeholder="https://github.com/kullanici/proje"
                       value="<?php echo htmlspecialchars($_POST['project_link'] ?? ''); ?>">
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Proje Resmi:</label>
                <input type="file" id="image" name="image" accept="image/*"
                       class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                <p class="mt-1 text-xs text-gray-500">JPG, JPEG, PNG, GIF formatları desteklenir. Maksimum 5MB.</p>
            </div>

            <div>
                <label for="project_file" class="block text-sm font-medium text-gray-700 mb-1">Proje Dosyası:</label>
                <input type="file" id="project_file" name="project_file" accept=".js,.css,.png,.jpg,.jpeg,.gif"
                       class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                <p class="mt-1 text-xs text-gray-500">Desteklenen formatlar: .js, .css, .png, .jpg, .jpeg, .gif (ZIP kabul edilmiyor)</p>
            </div>

            <div class="pt-5 border-t border-gray-200">
                <div class="flex justify-end space-x-3">
                    <a href="projects.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        İptal
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Proje Ekle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
