-- Artado Developers - Temiz Veritabanı Kurulum SQL Dosyası
-- Sıfırdan kurulum için kullanılır
-- Tüm mevcut veriler silinir, sadece tablo yapısı oluşturulur

-- Veritabanı karakter seti ayarları
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Mevcut tabloları temizle (varsa)
DROP TABLE IF EXISTS `workshop_comments`;
DROP TABLE IF EXISTS `todo_items`;
DROP TABLE IF EXISTS `project_images`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `users`;

-- Users Tablosu
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Projects Tablosu
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `project_link` text DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `download_count` int(11) DEFAULT 0,
  `is_private` tinyint(1) DEFAULT 0 COMMENT 'Proje gizli mi',
  `approval_status` enum('pending','approved','rejected') DEFAULT 'approved' COMMENT 'Onay durumu',
  `approval_requested_at` datetime DEFAULT NULL COMMENT 'Onay talep zamanı',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Onaylayan admin',
  `approved_at` datetime DEFAULT NULL COMMENT 'Onay zamanı',
  `rejection_reason` text DEFAULT NULL COMMENT 'Red sebebi',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upload_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category` (`category`),
  KEY `status` (`status`),
  KEY `is_private` (`is_private`),
  KEY `approval_status` (`approval_status`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_approval_fk` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Project Images Tablosu
CREATE TABLE `project_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_images_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Announcements Tablosu
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Todo Items Tablosu
CREATE TABLE `todo_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` datetime DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `project_id` (`project_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  CONSTRAINT `todo_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `todo_items_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Workshop Yorumlar Tablosu
CREATE TABLE `workshop_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `rating` tinyint(1) DEFAULT NULL COMMENT '1-5 arası puan',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `workshop_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workshop_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Project Privacy Requests Tablosu
CREATE TABLE `project_privacy_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('make_private','make_public') NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL COMMENT 'İşlemi yapan admin',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `project_privacy_requests_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_privacy_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_privacy_requests_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Password Resets Tablosu
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  KEY `used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan Admin Kullanıcısı (şifre: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES 
('admin', 'admin@artado.dev', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Varsayılan Duyurular
INSERT INTO `announcements` (`title`, `description`, `type`, `created_by`) VALUES 
('Hoş Geldiniz!', 'Artado Developers platformuna hoş geldiniz. Projelerinizi oluşturmaya başlayabilirsiniz.', 'success', 1),
('Kurulum Tamamlandı', 'Veritabanı kurulumu başarıyla tamamlandı. Sistem kullanıma hazır.', 'info', 1);

-- Örnek Workshop Yorumları
INSERT INTO `workshop_comments` (`project_id`, `user_id`, `comment`, `rating`, `status`) VALUES 
(1, 1, 'Harika bir proje! Çok beğendim, emeğinize sağlık.', 5, 'approved'),
(1, 1, 'Kullanımı kolay, arayüzü güzel. Teşekkürler.', 4, 'approved'),
(2, 1, 'Geliştirilebilir, güzel bir başlangıç olmuş.', 3, 'approved');

SET FOREIGN_KEY_CHECKS = 1;

-- Kurulum Bilgisi
SELECT 'Artado Developers veritabanı kurulumu başarıyla tamamlandı!' as message;
SELECT 'Admin kullanıcı: admin@artadosearch.com / şifre: admin123' as admin_info;
SELECT 'Lütfen admin şifresini güvenlik nedeniyle değiştirin!' as security_warning;
