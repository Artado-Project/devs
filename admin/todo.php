<?php
// admin/todo.php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Sadece admin erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 1. EKLEME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO todo_items (user_id, title, description, status, priority) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->execute([$_POST['user_id'], $_POST['title'], $_POST['description'], $_POST['priority']]);
    header("Location: todo.php?msg=added");
    exit();
}

// 2. GÜNCELLEME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $stmt = $db->prepare("UPDATE todo_items SET title = ?, description = ?, status = ?, priority = ? WHERE id = ?");
    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['status'], $_POST['priority'], $_POST['todo_id']]);
    header("Location: todo.php?msg=updated");
    exit();
}

// 3. SİLME İŞLEMİ
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM todo_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: todo.php?msg=deleted");
    exit();
}

// Verileri Hazırla
$todos = $db->query("SELECT t.*, u.username FROM todo_items t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$users = $db->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div class="container mx-auto mt-8 px-4">
    <!-- Üst: Ekleme Formu -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8 border border-gray-200">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Yeni Görev Tanımla</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="hidden" name="action" value="add">
            <select name="user_id" class="border p-2 rounded focus:ring-2 focus:ring-indigo-500" required>
                <?php foreach($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="title" placeholder="Başlık" class="border p-2 rounded" required>
            <input type="text" name="description" placeholder="Açıklama" class="border p-2 rounded">
            <select name="priority" class="border p-2 rounded">
                <option value="low">Düşük</option>
                <option value="medium" selected>Orta</option>
                <option value="high">Yüksek</option>
            </select>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">Ekle</button>
        </form>
    </div>

    <!-- Liste -->
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Görev Listesi</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="text-gray-600 border-b">
                    <th class="p-3">Kullanıcı</th>
                    <th class="p-3">Başlık</th>
                    <th class="p-3">Durum</th>
                    <th class="p-3">Öncelik</th>
                    <th class="p-3">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($todos as $todo): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3"><?php echo htmlspecialchars($todo['username']); ?></td>
                    <td class="p-3 font-medium"><?php echo htmlspecialchars($todo['title']); ?></td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded text-xs <?php echo $todo['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($todo['status']); ?>
                        </span>
                    </td>
                    <td class="p-3"><?php echo ucfirst($todo['priority']); ?></td>
                    <td class="p-3 flex gap-2">
                        <button onclick="editTodo(<?php echo htmlspecialchars(json_encode($todo)); ?>)" class="text-indigo-600 font-bold hover:underline">Düzenle</button>
                        <a href="?delete=<?php echo $todo['id']; ?>" class="text-red-600 font-bold hover:underline" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Görevi Güncelle</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="todo_id" id="edit_id">
            <input type="text" name="title" id="edit_title" class="w-full border p-2 mb-3 rounded" required>
            <textarea name="description" id="edit_desc" class="w-full border p-2 mb-3 rounded"></textarea>
            <select name="status" id="edit_status" class="w-full border p-2 mb-3 rounded">
                <option value="pending">Beklemede</option>
                <option value="in_progress">Devam Ediyor</option>
                <option value="completed">Tamamlandı</option>
            </select>
            <select name="priority" id="edit_priority" class="w-full border p-2 mb-3 rounded">
                <option value="low">Düşük</option>
                <option value="medium">Orta</option>
                <option value="high">Yüksek</option>
            </select>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 rounded">İptal</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editTodo(todo) {
        document.getElementById('edit_id').value = todo.id;
        document.getElementById('edit_title').value = todo.title;
        document.getElementById('edit_desc').value = todo.description;
        document.getElementById('edit_status').value = todo.status;
        document.getElementById('edit_priority').value = todo.priority;
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('editModal').classList.add('hidden'); }
</script>

<?php require_once 'footer.php'; ?>