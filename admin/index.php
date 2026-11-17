<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

// Toplam kullanıcı sayısını al
try {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_users = 0;
}

// Toplam proje sayısını al
try {
    $stmt = $db->query("SELECT COUNT(*) FROM projects");
    $total_projects = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_projects = 0;
}

// Son eklenen projeleri al (örneğin son 5 proje)
try {
    $stmt = $db->query("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
    $recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_projects = [];
}

// Son 7 günün istatistikleri
try {
    $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE created_at >= ?");
    $stmt->execute([$week_ago]);
    $new_users_week = $stmt->fetchColumn();
} catch (Exception $e) {
    $new_users_week = 0;
}

try {
    $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE created_at >= ?");
    $stmt->execute([$week_ago]);
    $new_projects_week = $stmt->fetchColumn();
} catch (Exception $e) {
    $new_projects_week = 0;
}

// Kategori bazında proje sayıları
try {
    $stmt = $db->query("SELECT category, COUNT(*) as count FROM projects GROUP BY category");
    $category_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $category_stats = [];
}

// En aktif kullanıcılar
try {
    $stmt = $db->query("SELECT u.username, COUNT(p.id) as project_count FROM users u LEFT JOIN projects p ON u.id = p.user_id GROUP BY u.id ORDER BY project_count DESC LIMIT 5");
    $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_users = [];
}

?>

<?php require_once 'header.php'; ?>

<!-- Dashboard Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-gray-600 mt-2">Admin paneline hoş geldiniz! Sistem genel bakış.</p>
            </div>
            <div class="flex space-x-3">
                <a href="../user" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-user-circle mr-2"></i>Kullanıcı Konsolu
                </a>
                <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Yenile
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Toplam Kullanıcı</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_users); ?></p>
                    <p class="text-sm text-green-600 mt-2">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +<?php echo $new_users_week; ?> bu hafta
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Projects -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Toplam Proje</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_projects); ?></p>
                    <p class="text-sm text-green-600 mt-2">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +<?php echo $new_projects_week; ?> bu hafta
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-folder-open text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Aktif Kullanıcılar</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format(count($top_users)); ?></p>
                    <p class="text-sm text-gray-500 mt-2">Proje sahibi</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Kategoriler</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format(count($category_stats)); ?></p>
                    <p class="text-sm text-gray-500 mt-2">Farklı tür</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-layer-group text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Category Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Kategori Dağılımı</h3>
            <div class="space-y-3">
                <?php if (empty($category_stats)): ?>
                    <p class="text-gray-500 text-center py-8">Henüz kategori verisi yok</p>
                <?php else: ?>
                    <?php foreach ($category_stats as $category => $count): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-indigo-500 rounded-full"></div>
                                <span class="text-gray-700"><?php echo htmlspecialchars($category); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: <?php echo ($count / $total_projects) * 100; ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600 w-12 text-right"><?php echo $count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Users -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">En Aktif Kullanıcılar</h3>
            <div class="space-y-3">
                <?php if (empty($top_users)): ?>
                    <p class="text-gray-500 text-center py-8">Henüz kullanıcı verisi yok</p>
                <?php else: ?>
                    <?php foreach ($top_users as $index => $user): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 font-semibold text-sm"><?php echo $index + 1; ?></span>
                                </div>
                                <span class="text-gray-700"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600"><?php echo $user['project_count']; ?> proje</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $total_projects > 0 ? ($user['project_count'] / $total_projects) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Son Eklenen Projeler</h3>
            <a href="projects.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                Tümünü Gör <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Proje</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Kullanıcı</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Kategori</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Tarih</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_projects)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">
                                <i class="fas fa-folder-open text-4xl mb-4 block"></i>
                                Henüz proje bulunmuyor
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_projects as $project): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-folder text-indigo-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($project['title']); ?></p>
                                            <p class="text-sm text-gray-500 line-clamp-1"><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-user text-gray-400"></i>
                                        <span><?php echo htmlspecialchars($project['username']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700">
                                        <?php echo htmlspecialchars($project['category']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2 text-gray-600">
                                        <i class="fas fa-calendar text-gray-400"></i>
                                        <span><?php echo date('d.m.Y', strtotime($project['created_at'])); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex space-x-2">
                                        <a href="../Workshop/project.php?id=<?php echo $project['id']; ?>" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800" title="Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_project.php?id=<?php echo $project['id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-800" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Hızlı İşlemler</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="add_project.php" class="bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg p-4 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-plus text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Yeni Proje Ekle</p>
                        <p class="text-sm text-gray-600">Proje oluştur</p>
                    </div>
                </div>
            </a>
            
            <a href="users.php" class="bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Kullanıcı Yönetimi</p>
                        <p class="text-sm text-gray-600">Kullanıcıları düzenle</p>
                    </div>
                </div>
            </a>
            
            <a href="duyuru.php" class="bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bullhorn text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Duyuru Yönetimi</p>
                        <p class="text-sm text-gray-600">Duyuruları düzenle</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

</main>
</div>

<script>
// Sayfa yenileme
setInterval(() => {
    // location.reload(); // Geliştirme aşamasında kapalı tutmak isteyebilirsiniz
}, 30000);

document.addEventListener('DOMContentLoaded', function() {
    const numbers = document.querySelectorAll('.text-3xl');
    numbers.forEach(num => {
        // Sadece rakamları al (virgül veya nokta varsa temizle)
        const textContent = num.textContent.trim();
        const finalValue = parseInt(textContent.replace(/\D/g, '')) || 0;
        
        if (finalValue === 0) return; // Sayı 0 ise animasyon yapma

        let currentValue = 0;
        const duration = 1000; // 1 saniye sürsün
        const steps = 50;
        const increment = finalValue / steps;
        const interval = duration / steps;

        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            // Sayıyı tekrar formatlı bir şekilde yaz
            num.textContent = Math.floor(currentValue).toLocaleString('tr-TR');
        }, interval);
    });
});
</script>

</body>
</html>
