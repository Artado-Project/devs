<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

// Tüm kullanıcıları veritabanından çek
try {
    // Önce tablo yapısını kontrol et
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tarih kolonunu bul
    $date_column = 'id'; // Varsayılan sıralama
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'date') !== false || 
            strpos($column['Field'], 'created') !== false || 
            strpos($column['Field'], 'time') !== false) {
            $date_column = $column['Field'];
            break;
        }
    }
    
    error_log("Admin users.php - Sıralama kolonu: " . $date_column);
    
    // Kullanıcıları çek
    $stmt = $db->query("SELECT * FROM users ORDER BY $date_column DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Kullanıcı sayısını logla
    error_log("Admin users.php - Toplam kullanıcı sayısı: " . count($users));
    
} catch (PDOException $e) {
    error_log("Admin users.php - Veritabanı hatası: " . $e->getMessage());
    $users = [];
} catch (Exception $e) {
    error_log("Admin users.php - Genel hata: " . $e->getMessage());
    $users = [];
}

?>

<?php require_once 'header.php'; ?>

<!-- Page Content -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Kullanıcı Yönetimi</h2>
            <p class="text-gray-600 mt-1">Toplam <?php echo count($users); ?> kullanıcı</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Yenile
            </button>
            <a href="edit_user.php?action=add" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Yeni Kullanıcı
            </a>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <input type="text" id="searchUsers" placeholder="Kullanıcı ara..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <select id="roleFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tüm Roller</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tüm Durumlar</option>
                <option value="active">Aktif</option>
                <option value="inactive">İnaktif</option>
            </select>
        </div>
    </div>

    <!-- Users Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                    </th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kullanıcı</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">E-posta</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Rol</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kayıt Tarihi</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Durum</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">İşlemler</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            <i class="fas fa-users text-4xl mb-4 block"></i>
                            <p class="text-lg font-medium mb-2">Henüz kullanıcı bulunmuyor</p>
                            <p class="text-sm">Veritabanı bağlantısı kontrol ediliyor...</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 user-row" 
                            data-role="<?php echo htmlspecialchars($user['role'] ?? 'user'); ?>"
                            data-status="<?php echo htmlspecialchars($user['status'] ?? 'active'); ?>"
                            data-search="<?php echo htmlspecialchars(strtolower($user['username'] . ' ' . $user['email'])); ?>">
                            <td class="py-3 px-4">
                                <input type="checkbox" class="user-checkbox rounded border-gray-300" value="<?php echo $user['id']; ?>">
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <?php if (!empty($user['profile_photo'])): ?>
                                            <img src="<?php echo '../' . htmlspecialchars($user['profile_photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                                 class="w-10 h-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-indigo-600"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['username']); ?></p>
                                        <p class="text-sm text-gray-500">ID: <?php echo $user['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo ($user['role'] ?? 'user') === 'admin' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                                    <?php echo htmlspecialchars($user['role'] ?? 'user'); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2 text-gray-600">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    <span>
                                        <?php 
                                        // Tarih kolonunu kontrol et ve göster
                                        $date_field = null;
                                        foreach ($user as $key => $value) {
                                            if (strpos($key, 'date') !== false || strpos($key, 'created') !== false || strpos($key, 'time') !== false) {
                                                $date_field = $value;
                                                break;
                                            }
                                        }
                                        
                                        if ($date_field) {
                                            echo date('d.m.Y H:i', strtotime($date_field));
                                        } else {
                                            echo 'Belirtilmemiş';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo ($user['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
                                    <?php echo htmlspecialchars($user['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <a href="../user/account-profile.php?id=<?php echo $user['id']; ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800" title="Profili Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-800" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="toggleUserStatus(<?php echo $user['id']; ?>)" 
                                            class="text-yellow-600 hover:text-yellow-800" title="Durum Değiştir">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                            class="text-red-600 hover:text-red-800" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bulk Actions -->
    <div class="mt-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">
                <span id="selectedCount">0</span> kullanıcı seçildi
            </span>
            <div class="flex space-x-2">
                <button onclick="bulkAction('activate')" class="text-sm bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded transition-colors">
                    Aktif Et
                </button>
                <button onclick="bulkAction('deactivate')" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded transition-colors">
                    Deaktif Et
                </button>
                <button onclick="bulkAction('delete')" class="text-sm bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded transition-colors">
                    Sil
                </button>
            </div>
        </div>
        <div class="text-sm text-gray-600">
            Sayfa 1 / 1
        </div>
    </div>
</div>

</main>
</div>

<script>
// Search functionality
document.getElementById('searchUsers').addEventListener('input', function(e) {
    filterUsers();
});

document.getElementById('roleFilter').addEventListener('change', filterUsers);
document.getElementById('statusFilter').addEventListener('change', filterUsers);

function filterUsers() {
    const searchTerm = document.getElementById('searchUsers').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.user-row');
    
    rows.forEach(row => {
        const matchesSearch = row.dataset.search.includes(searchTerm);
        const matchesRole = !roleFilter || row.dataset.role === roleFilter;
        const matchesStatus = !statusFilter || row.dataset.status === statusFilter;
        
        row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
    });
}

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = e.target.checked;
    });
    updateSelectedCount();
});

document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    document.getElementById('selectedCount').textContent = checked.length;
}

// User actions
function toggleUserStatus(userId) {
    if (confirm('Kullanıcı durumunu değiştirmek istediğinizden emin misiniz?')) {
        // AJAX call to toggle status
        location.reload();
    }
}

function deleteUser(userId) {
    if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
        window.location.href = 'delete_user.php?id=' + userId;
    }
}

function bulkAction(action) {
    const selected = document.querySelectorAll('.user-checkbox:checked');
    if (selected.length === 0) {
        alert('Lütfen en az bir kullanıcı seçin.');
        return;
    }
    
    const actionText = {
        'activate': 'aktif etmek',
        'deactivate': 'deaktif etmek',
        'delete': 'silmek'
    };
    
    if (confirm(`Seçili ${selected.length} kullanıcıyı ${actionText[action]} istediğinizden emin misiniz?`)) {
        // Perform bulk action
        console.log('Bulk action:', action, selected);
    }
}
</script>

</body>
</html>
