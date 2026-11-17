<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Kullanıcı bilgilerini veritabanından çek
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Kullanıcı bulunamadı.";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            if (isset($_POST['reset_password'])) {
                // Rastgele bir şifre oluştur
                $new_password = bin2hex(random_bytes(8)); // Bu kısmı kaldıracağız.
            
                // Kullanıcı tarafından girilen yeni şifreyi al
                $new_password = $_POST['new_password'];
            
                // Şifreyi hashle
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
                try {
                    // Şifreyi veritabanında güncelle
                    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
            

                } catch (PDOException $e) {
                    $error = "Şifre sıfırlanırken bir hata oluştu.";
                }
            }
            
            try {
                // Kullanıcıyı veritabanında güncelle
                $stmt = $db->prepare("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :user_id");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                // Güncelleme başarılı, kullanıcılar sayfasına yönlendir
                header("Location: users");
                exit();
            } catch (PDOException $e) {
                $error = "Kullanıcı başarıyla Güncellendi.";
            }
        }
    }
} else {
    $error = "Geçersiz kullanıcı ID.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kullanıcıyı Düzenle</title>
    <link rel="icon" href="../uploads/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }
    </script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-8">
    <h2 class="text-2xl font-bold mb-4">Kullanıcıyı Düzenle</h2>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php else: ?>
        <form method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                    Kullanıcı Adı:
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       id="username" name="username" type="text" value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    E-posta:
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       id="email" name="email" type="email" value="<?php echo $user['email']; ?>" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                    Rol:
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                        id="role" name="role">
                    <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>Kullanıcı</option>
                    <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Güncelle
                </button>
                <button type="button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="openPasswordModal()">
                    Şifreyi Sıfırla
                </button>
            </div>
        </form>
    <?php endif; ?>

    <!-- Şifre Sıfırlama Modal -->
    <div id="passwordModal" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50" style="display:none;">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Yeni Şifre Belirle</h3>
            <form method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">
                        Yeni Şifre:
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="new_password" name="new_password" type="password" required>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="reset_password" value="1">
                        Şifreyi Sıfırla
                    </button>
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closePasswordModal()">
                        Kapat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
