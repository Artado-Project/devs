<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login");
  exit();
}

// Toplam kullanıcı sayısını al
$stmt = $db->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();

// Toplam proje sayısını al
$stmt = $db->query("SELECT COUNT(*) FROM projects");
$total_projects = $stmt->fetchColumn();

// Son eklenen projeleri al (örneğin son 5 proje)
$stmt = $db->query("SELECT p.title, u.username FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
$recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once 'header.php'; ?>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="icon" href="../uploads/logo.png" type="image/x-icon">

<div class="container mx-auto p-8">
  <h1 class="text-3xl font-bold mb-6">İstatistikler</h1>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-bold mb-4">Genel İstatistikler</h2>
      <ul>
        <li>Toplam Kullanıcı: <?php echo $total_users; ?></li>
        <li>Toplam Proje: <?php echo $total_projects; ?></li>
      </ul>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-bold mb-4">Son Eklenen Projeler</h2>
      <ul>
        <?php foreach ($recent_projects as $project): ?>
        <li><?php echo $project['title']; ?> (Yükleyen: <?php echo $project['username']; ?>)</li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow-md p-6 mt-6">
    <h2 class="text-xl font-bold mb-4">Kullanıcı ve Proje Sayıları</h2>
    <canvas id="userProjectChart"></canvas>
  </div>

  <script>
  const ctx = document.getElementById('userProjectChart');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Kullanıcılar', 'Projeler'],
      datasets: [{
        label: 'Sayı',
        data: [<?php echo $total_users; ?>, <?php echo $total_projects; ?>],
        backgroundColor: [
          'rgba(54, 162, 235, 0.2)',
          'rgba(255, 99, 132, 0.2)'
        ],
        borderColor: [
          'rgba(54, 162, 235, 1)',
          'rgba(255, 99, 132, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
  </script>

</div>

<?php require_once '../footer.php'; ?>