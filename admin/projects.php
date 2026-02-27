<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

// Tüm projeleri veritabanından çek (resimlerle birlikte)
try {
    $stmt = $db->query("
        SELECT p.*, u.username, 
               COALESCE(pi.image_path, p.image_path) as display_image
        FROM projects p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN (
            SELECT project_id, MIN(image_path) as image_path 
            FROM project_images 
            GROUP BY project_id
        ) pi ON p.id = pi.project_id
        ORDER BY p.created_at DESC
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $projects = [];
}

?>

<?php require_once 'header.php'; ?>

<!-- Page Content -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Proje Yönetimi</h2>
            <p class="text-gray-600 mt-1">Toplam <?php echo count($projects); ?> proje</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Yenile
            </button>
            <a href="add_project.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Yeni Proje
            </a>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <input type="text" id="searchProjects" placeholder="Proje ara..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <select id="categoryFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tüm Kategoriler</option>
                <option value="web">Web</option>
                <option value="mobil">Mobil</option>
                <option value="desktop">Desktop</option>
                <option value="game">Oyun</option>
                <option value="other">Diğer</option>
            </select>
            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tüm Durumlar</option>
                <option value="published">Yayında</option>
                <option value="draft">Taslak</option>
                <option value="archived">Arşiv</option>
            </select>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="projectsGrid">
        <?php if (empty($projects)): ?>
            <div class="col-span-full text-center py-12 text-gray-500">
                <i class="fas fa-folder-open text-6xl mb-4 block"></i>
                <h3 class="text-xl font-semibold mb-2">Henüz proje bulunmuyor</h3>
                <p class="mb-6">İlk projeyi eklemek için butona tıklayın</p>
                <a href="add_project.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>İlk Projeyi Ekle
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <div class="project-card bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300"
                     data-category="<?php echo htmlspecialchars($project['category'] ?? 'other'); ?>"
                     data-status="<?php echo htmlspecialchars($project['status'] ?? 'published'); ?>"
                     data-search="<?php echo htmlspecialchars(strtolower($project['title'] . ' ' . $project['description'] . ' ' . $project['username'])); ?>">
                    
                    <!-- Project Image -->
                    <div class="h-48 bg-gradient-to-br from-indigo-100 to-purple-100 relative overflow-hidden">
                        <?php if (!empty($project['display_image']) && file_exists('../' . $project['display_image'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($project['display_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($project['title']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <i class="fas fa-image text-4xl text-indigo-300 mb-2"></i>
                                    <p class="text-indigo-500 text-sm">Resim Yok</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Category Badge -->
                        <div class="absolute top-4 right-4">
                            <span class="bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium text-indigo-600">
                                <?php 
                                $categories = [
                                    'web' => 'Web',
                                    'mobil' => 'Mobil', 
                                    'desktop' => 'Desktop',
                                    'game' => 'Oyun',
                                    'other' => 'Diğer'
                                ];
                                echo $categories[$project['category']] ?? 'Diğer';
                                ?>
                            </span>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-4 left-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php 
                                $status_colors = [
                                    'published' => 'bg-green-100 text-green-700',
                                    'draft' => 'bg-yellow-100 text-yellow-700',
                                    'archived' => 'bg-gray-100 text-gray-700'
                                ];
                                echo $status_colors[$project['status']] ?? 'bg-green-100 text-green-700';
                                ?>">
                                <?php 
                                $status_text = [
                                    'published' => 'Yayında',
                                    'draft' => 'Taslak',
                                    'archived' => 'Arşiv'
                                ];
                                echo $status_text[$project['status']] ?? 'Yayında';
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Project Content -->
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">
                            <?php echo htmlspecialchars($project['title']); ?>
                        </h3>
                        
                        <p class="text-gray-600 mb-4 line-clamp-3">
                            <?php echo htmlspecialchars($project['description']); ?>
                        </p>
                        
                        <!-- Project Meta -->
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($project['username']); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d.m.Y', strtotime($project['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <a href="../Workshop/project.php?id=<?php echo $project['id']; ?>" 
                               target="_blank"
                               class="flex-1 bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition-colors text-center text-sm">
                                <i class="fas fa-eye mr-1"></i>Görüntüle
                            </a>
                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" 
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg transition-colors text-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteProject(<?php echo $project['id']; ?>)" 
                                    class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-lg transition-colors text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="mt-8 flex items-center justify-between">
        <div class="text-sm text-gray-600">
            <?php echo count($projects); ?> proje gösteriliyor
        </div>
        <div class="flex space-x-2">
            <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="px-3 py-1 bg-indigo-600 text-white rounded-lg">1</button>
            <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

</main>
</div>

<script>
// Search functionality
document.getElementById('searchProjects').addEventListener('input', function(e) {
    filterProjects();
});

document.getElementById('categoryFilter').addEventListener('change', filterProjects);
document.getElementById('statusFilter').addEventListener('change', filterProjects);

function filterProjects() {
    const searchTerm = document.getElementById('searchProjects').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const cards = document.querySelectorAll('.project-card');
    
    cards.forEach(card => {
        const matchesSearch = card.dataset.search.includes(searchTerm);
        const matchesCategory = !categoryFilter || card.dataset.category === categoryFilter;
        const matchesStatus = !statusFilter || card.dataset.status === statusFilter;
        
        card.style.display = matchesSearch && matchesCategory && matchesStatus ? '' : 'none';
    });
    
    // Check if any projects are visible
    const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
    const grid = document.getElementById('projectsGrid');
    
    if (visibleCards.length === 0 && cards.length > 0) {
        if (!grid.querySelector('.no-results')) {
            const noResults = document.createElement('div');
            noResults.className = 'col-span-full text-center py-12 text-gray-500 no-results';
            noResults.innerHTML = `
                <i class="fas fa-search text-6xl mb-4 block"></i>
                <h3 class="text-xl font-semibold mb-2">Arama kriterlerinize uygun proje bulunamadı</h3>
                <p>Filtreleri değiştirmeyi deneyin</p>
            `;
            grid.appendChild(noResults);
        }
    } else {
        const noResults = grid.querySelector('.no-results');
        if (noResults) {
            noResults.remove();
        }
    }
}

// Delete project
function deleteProject(projectId) {
    if (confirm('Bu projeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
        window.location.href = 'delete_project.php?id=' + projectId;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    filterProjects();
});
</script>

</body>
</html>
