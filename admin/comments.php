<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

// Comment işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve_comment') {
        $comment_id = $_POST['comment_id'];
        
        try {
            $stmt = $db->prepare("UPDATE workshop_comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            $_SESSION['success'] = "Comment approved successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        
        header("Location: comments.php");
        exit();
    }
    
    if ($action === 'reject_comment') {
        $comment_id = $_POST['comment_id'];
        
        try {
            $stmt = $db->prepare("UPDATE workshop_comments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            $_SESSION['success'] = "Comment rejected!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        
        header("Location: comments.php");
        exit();
    }
    
    if ($action === 'delete_comment') {
        $comment_id = $_POST['comment_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM workshop_comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            $_SESSION['success'] = "Comment deleted successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        
        header("Location: comments.php");
        exit();
    }
}

// Filtreleme
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Yorumları çek
try {
    $sql = "
        SELECT wc.*, p.title as project_title, u.username as user_name 
        FROM workshop_comments wc
        LEFT JOIN projects p ON wc.project_id = p.id
        LEFT JOIN users u ON wc.user_id = u.id
    ";
    
    $params = [];
    
    if ($status_filter !== 'all') {
        $sql .= " WHERE wc.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search)) {
        $sql .= (empty($params) ? " WHERE" : " AND") . " (wc.comment LIKE ? OR p.title LIKE ? OR u.username LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $sql .= " ORDER BY wc.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $comments = [];
}

// İstatistikler
try {
    $stats = [];
    $stmt = $db->query("SELECT COUNT(*) as total FROM workshop_comments");
    $stats['total'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as pending FROM workshop_comments WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as approved FROM workshop_comments WHERE status = 'approved'");
    $stats['approved'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as rejected FROM workshop_comments WHERE status = 'rejected'");
    $stats['rejected'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

?>

<?php require_once 'header.php'; ?>

<!-- Page Content -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Comment Management</h2>
            <p class="text-gray-600 mt-1">Manage workshop comments and reviews</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-600 text-sm font-medium">Total Comments</p>
                    <p class="text-2xl font-bold text-blue-800"><?php echo $stats['total']; ?></p>
                </div>
                <i class="fas fa-comments text-blue-500 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-600 text-sm font-medium">Pending</p>
                    <p class="text-2xl font-bold text-yellow-800"><?php echo $stats['pending']; ?></p>
                </div>
                <i class="fas fa-clock text-yellow-500 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-600 text-sm font-medium">Approved</p>
                    <p class="text-2xl font-bold text-green-800"><?php echo $stats['approved']; ?></p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-600 text-sm font-medium">Rejected</p>
                    <p class="text-2xl font-bold text-red-800"><?php echo $stats['rejected']; ?></p>
                </div>
                <i class="fas fa-times-circle text-red-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search comments, projects, or users..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </form>
    </div>

    <!-- Comments Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comment['project_title']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($comment['user_name']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($comment['comment']); ?>">
                                <?php echo htmlspecialchars(substr($comment['comment'], 0, 100)); ?><?php echo strlen($comment['comment']) > 100 ? '...' : ''; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($comment['rating']): ?>
                                <div class="flex text-yellow-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-sm <?= $i <= $comment['rating'] ? '' : 'text-gray-300' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                if ($comment['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                elseif ($comment['status'] === 'approved') echo 'bg-green-100 text-green-800';
                                else echo 'bg-red-100 text-red-800';
                                ?>">
                                <?php echo ucfirst($comment['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M j, Y H:i', strtotime($comment['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="showCommentModal(<?php echo $comment['id']; ?>)" 
                                    class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </button>
                            
                            <?php if ($comment['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this comment?')">
                                    <input type="hidden" name="action" value="approve_comment">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="text-green-600 hover:text-green-900 mr-2">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this comment?')">
                                    <input type="hidden" name="action" value="reject_comment">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 mr-2">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this comment permanently?')">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($comments)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-comments text-4xl mb-3"></i>
                <p>No comments found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Comment View Modal -->
<div id="commentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Comment Details</h3>
            <div id="commentDetails" class="space-y-3">
                <!-- Content will be loaded here -->
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="closeCommentModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Comment data for modal
const commentData = <?php echo json_encode($comments); ?>;

function showCommentModal(commentId) {
    const comment = commentData.find(c => c.id === commentId);
    if (!comment) return;
    
    const detailsHtml = `
        <div>
            <p><strong>Project:</strong> ${comment.project_title}</p>
            <p><strong>User:</strong> ${comment.user_name}</p>
            <p><strong>Date:</strong> ${new Date(comment.created_at).toLocaleString()}</p>
            <p><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full ${
                comment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                comment.status === 'approved' ? 'bg-green-100 text-green-800' :
                'bg-red-100 text-red-800'
            }">${comment.status}</span></p>
            ${comment.rating ? `<p><strong>Rating:</strong> ${'★'.repeat(comment.rating)}${'☆'.repeat(5-comment.rating)}</p>` : ''}
            <div class="mt-3">
                <p><strong>Comment:</strong></p>
                <p class="bg-gray-50 p-3 rounded mt-1">${comment.comment}</p>
            </div>
        </div>
    `;
    
    document.getElementById('commentDetails').innerHTML = detailsHtml;
    document.getElementById('commentModal').classList.remove('hidden');
}

function closeCommentModal() {
    document.getElementById('commentModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('commentModal');
    if (event.target === modal) {
        closeCommentModal();
    }
}
</script>

<?php require_once 'footer.php'; ?>
