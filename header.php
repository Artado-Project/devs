<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artado Devs</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Popup açılış animasyonu */
        @keyframes popup-enter {
            0% {
                transform: scale(0.9) translateX(-20px);
                opacity: 0;
            }
            100% {
                transform: scale(1) translateX(0);
                opacity: 1;
            }
        }

        .popup-enter {
            animation: popup-enter 0.3s ease-out forwards;
        }

        .popup-exit {
            animation: fadeOut 0.3s ease-out forwards;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        /* Açık siyah arka plan ve ince beyaz detaylar */
        .bg-light-black {
            background-color: #000000; /* Daha açık siyah */
        }

        .border-white-light {
            border-color: rgba(255, 255, 255, 0.4);
        }

        body.announcement-page header {
  padding-left: 0;
  padding-right: 0;
}

flex items-center space-x-4 {
  margin-left: 0;
  margin-right: 0;
  padding: 1.5rem;
}
body.announcement-page header {
  padding-left: 0;
  padding-right: 0;
}

body.announcement-page .announcement {
  margin-left: 0;
  margin-right: 0;
  padding: 1.5rem;
}

    </style>
</head>
<body class="bg-light-black text-white">

<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Kullanıcı oturum kontrolü
$user_id = $_SESSION['user_id'] ?? null;

// Varsayılan profil fotoğrafı
$default_profile_photo = 'logo.png';

// Kullanıcı bilgilerini çek
if ($user_id) {
    $stmt = $db->prepare("SELECT profile_photo FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    require_once 'includes/functions.php';
    // Header kök dizinde olduğu için $is_in_user_dir = false (varsayılan)
    $profile_photo = get_user_avatar($user['profile_photo'] ?? null);
} else {
    $profile_photo = $default_profile_photo;
}
?>

<!-- Açık siyah header -->
<header class="bg-light-black text-white shadow-lg border-b border-white-light">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo -->
        <div class="flex items-center space-x-4">
        <img src="/public/logo.png" alt="Logo" class="w-10 h-10">            <span class="text-2xl font-bold tracking-wide">Artado Devs</span>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex space-x-8 text-lg font-medium">
            <a href="./" class="hover:underline hover:text-gray-300 transition">Ana Sayfa</a>
            <a href="https://forum.artado.xyz" class="hover:underline hover:text-gray-300 transition">Forum</a>
            <a href="https://artado.xyz" class="hover:underline hover:text-gray-300 transition">Hizmetler</a>
            <a href="mailto:arda@artadosearch.com" class="hover:underline hover:text-gray-300 transition">İletişim</a>
        </nav>

        <!-- Profil & Hamburger Menüsü -->

    </div>

    <!-- Mobil Menü -->
    <nav id="mobile-menu" class="hidden md:hidden bg-light-black border-t border-white-light">
        <ul class="flex flex-col items-center space-y-4 py-4">
            <li><a href="./" class="hover:underline hover:text-gray-300 transition">Ana Sayfa</a></li>
            <li><a href="https://forum.artado.xyz" class="hover:underline hover:text-gray-300 transition">Forum</a></li>
            <li><a href="https://artado.xyz" class="hover:underline hover:text-gray-300 transition">Hizmetler</a></li>
            <li><a href="mailto:arda@artadosearch.com" class="hover:underline hover:text-gray-300 transition">İletişim</a></li>
        </ul>
    </nav>
</header>

<script>
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const profilePhoto = document.getElementById('profile-photo');
    const username = document.getElementById('username');
    const profilePopup = document.getElementById('profile-popup');
    const closePopup = document.getElementById('close-popup');

    menuToggle.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    [profilePhoto, username].forEach(el => el.addEventListener('click', () => {
        profilePopup.classList.remove('hidden');
    }));
    closePopup.addEventListener('click', () => profilePopup.classList.add('hidden'));
</script>

</body>
</html>
