<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcı admin mi kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login");
  exit();
}

// Duyuru ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Yeni duyuru ekleme
    if (empty($_POST['id'])) {
        $stmt = $db->prepare("INSERT INTO announcements (title, description) VALUES (?, ?)");
        $stmt->execute([$title, $description]);
        $message = "Duyuru başarıyla eklendi!";
    } 
    // Duyuru düzenleme işlemi
    else {
        $id = $_POST['id'];
        $stmt = $db->prepare("UPDATE announcements SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$title, $description, $id]);
        $message = "Duyuru başarıyla güncellendi!";
    }
}

// Duyuruları çekme işlemi
$stmt = $db->query("SELECT * FROM announcements");
$announcements = $stmt->fetchAll();

// Duyuru silme işlemi
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Veritabanından silme
    $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Duyuru düzenleme işlemi
$announcementToEdit = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$edit_id]);
    $announcementToEdit = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Duyuru Ekle/Düzenle</title>
  <!-- TailwindCSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

  <!-- Header Include -->
  <?php require_once 'header.php'; ?>

  <main class="container mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
      <h2 class="text-2xl font-semibold mb-4"><?php echo isset($announcementToEdit) ? 'Duyuru Düzenle' : 'Yeni Duyuru Ekle'; ?></h2>

      <?php if (isset($message)): ?>
        <div class="alert alert-success bg-green-500 text-white p-4 rounded mb-4">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <!-- Duyuru ID gizli input olarak ekleniyor -->
        <?php if (isset($announcementToEdit)): ?>
          <input type="hidden" name="id" value="<?php echo $announcementToEdit['id']; ?>">
        <?php endif; ?>

        <div class="mb-4">
          <label for="title" class="block text-sm font-medium text-gray-700">Duyuru Başlığı</label>
          <input type="text" id="title" name="title" value="<?php echo isset($announcementToEdit) ? htmlspecialchars($announcementToEdit['title']) : ''; ?>" class="form-control block w-full mt-1 p-3 border rounded-md shadow-sm" required>
        </div>

        <div class="mb-4">
          <label for="description" class="block text-sm font-medium text-gray-700">Duyuru Açıklaması</label>
          <textarea id="description" name="description" class="form-control block w-full mt-1 p-3 border rounded-md shadow-sm" rows="5" required><?php echo isset($announcementToEdit) ? htmlspecialchars($announcementToEdit['description']) : ''; ?></textarea>
        </div>

        <button type="submit" class="btn bg-blue-500 text-white px-6 py-3 rounded-md shadow hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"><?php echo isset($announcementToEdit) ? 'Duyuru Güncelle' : 'Duyuru Ekle'; ?></button>
      </form>
    </div>

    <!-- Duyuru Listeleme ve Yönetim -->
    <div>
      <h2 class="text-xl font-semibold">Yayınlanan Duyurular</h2>
      
      <?php if (count($announcements) > 0): ?>
        <table class="min-w-full table-auto mt-4 border-collapse border border-gray-300">
          <thead>
            <tr class="bg-gray-200">
              <th class="p-3 text-left border-b">Başlık</th>
              <th class="p-3 text-left border-b">Açıklama</th>
              <th class="p-3 text-left border-b">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($announcements as $announcement): ?>
              <tr>
                <td class="p-3 border-b"><?php echo htmlspecialchars($announcement['title']); ?></td>
                <td class="p-3 border-b"><?php echo htmlspecialchars($announcement['description']); ?></td>
                <td class="p-3 border-b">
                  <a href="?edit_id=<?php echo $announcement['id']; ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-md shadow hover:bg-yellow-600">Düzenle</a>
                  <a href="?delete_id=<?php echo $announcement['id']; ?>" onclick="return confirm('Duyuruyu silmek istediğinizden emin misiniz?');" class="bg-red-500 text-white px-4 py-2 rounded-md shadow hover:bg-red-600 ml-2">Sil</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="mt-4 text-gray-700">Henüz yayınlanmış duyuru bulunmamaktadır.</p>
      <?php endif; ?>
    </div>
  </main>

  <!-- Footer Include -->
  <?php require_once 'footer.php'; ?>

</body>
</html>
