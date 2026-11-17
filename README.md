# Artado Developers Platform

Artado Developers platformu, geliştiricilerin projelerini paylaşabileceği, yönetebileceği ve işbirliği yapabileceği kapsamlı bir geliştirici portalıdır.

## 📋 İçerik

- [Özellikler](#özellikler)
- [Kurulum](#kurulum)
- [Veritabanı Yapısı](#veritabanı-yapısı)
- [Dosya Yapısı](#dosya-yapısı)
- [Yönetim Paneli](#yönetim-paneli)
- [Workshop](#workshop)
- [API](#api)
- [Güvenlik](#güvenlik)
- [Yapılması Gereken Adımlar](#yapılması-gereken-adımlar)

---

## 🚀 Özellikler

### Kullanıcı Özellikleri
- ✅ Kullanıcı kayıt ve giriş sistemi
- ✅ Profil yönetimi
- ✅ Proje oluşturma ve yönetimi
- ✅ Proje gizlilik ayarları (onay sistemi ile)
- ✅ Workshop entegrasyonu
- ✅ Yorum ve değerlendirme sistemi
- ✅ Duyuru sistemi
- ✅ Todo list yönetimi

### Yönetici Özellikleri
- ✅ Kullanıcı yönetimi
- ✅ Proje yönetimi
- ✅ Gizlilik istekleri onay sistemi
- ✅ Yorum yönetimi ve moderasyon
- ✅ İstatistikler ve raporlama
- ✅ Duyuru yayınlama

### Teknik Özellikler
- ✅ PHP 8+ ve PDO ile veritabanı yönetimi
- ✅ Modern TailwindCSS arayüz
- ✅ Responsive tasarım
- ✅ Email bildirim sistemi (TLS SMTP)
- ✅ Güvenli oturum yönetimi
- ✅ Dosya yükleme sistemi

---

## 🛠️ Kurulum

### Gereksinimler
- PHP 8.0 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucu (Apache/Nginx)
- Composer (PHPMailer için)

### Adım 1: Veritabanı Kurulumu
```bash
# Veritabanı oluşturun
mysql -u root -p
CREATE DATABASE artadodevs;
CREATE USER 'artado'@'localhost' IDENTIFIED BY 'şifreniz';
GRANT ALL PRIVILEGES ON artadodevs.* TO 'artado'@'localhost';
FLUSH PRIVILEGES;
```

### Adım 2: Dosyaları Yükleme
```bash
# Projeyi sunucuya yükleyin
git clone [repository-url]
cd devs
```

### Adım 3: Konfigürasyon
```bash
# Veritabanı ayarlarını düzenleyin
nano config.php
```

### Adım 5: Composer Dependencies
```bash
# PHPMailer kurulumu
composer install
```

### Adım 6: Dosya İzinleri
```bash
# Dosya izinlerini ayarlayın
chmod -R 755 .
chmod -R 777 public/uploads/
```

---

## 🗄️ Veritabanı Yapısı

### Ana Tablolar

#### `users`
Kullanıcı bilgilerini tutar:
- `id`, `username`, `email`, `password`
- `profile_photo`, `title`, `bio`
- `role` (user/admin), `created_at`

#### `projects`
Proje bilgilerini tutar:
- `id`, `title`, `description`, `category`
- `image_path`, `download_link`, `github_link`
- `user_id`, `is_private`, `approval_status`
- `created_at`, `updated_at`

#### `workshop_comments`
Workshop yorumlarını tutar:
- `id`, `project_id`, `user_id`, `comment`
- `rating` (1-5), `status` (pending/approved/rejected)
- `created_at`

#### `project_privacy_requests`
Gizlilik değişiklik isteklerini tutar:
- `id`, `project_id`, `user_id`, `requested_privacy`
- `reason`, `status` (pending/approved/rejected)
- `admin_notes`, `created_at`, `processed_at`

#### `password_resets`
Şifre sıfırlama tokenlarını tutar:
- `id`, `email`, `token`, `expires_at`
- `used`, `created_at`

---

## 📁 Dosya Yapısı

```
devs/
├── 📄 Ana Dosyalar
│   ├── index.php                 # Ana sayfa
│   ├── login.php                 # Giriş sayfası
│   ├── register.php              # Kayıt sayfası
│   ├── header.php                # Ana header
│   ├── footer.php                # Ana footer
│   └── config.php                # Veritabanı konfigürasyonu
│
├── 👤 Kullanıcı Paneli (/user/)
│   ├── index.php                 # Kullanıcı ana panel
│   ├── account-profile.php       # Profil yönetimi
│   ├── account-security.php      # Güvenlik ayarları
│   ├── projects.php              # Proje yönetimi
│   ├── announcements.php         # Duyurular
│   ├── todo-list.php             # Todo list
│   ├── create-*.php              # Proje oluşturma formları
│   └── auth-*.php                # Auth sayfaları
│
├── 🛡️ Admin Paneli (/admin/)
│   ├── index.php                 # Admin dashboard
│   ├── users.php                 # Kullanıcı yönetimi
│   ├── projects.php              # Proje yönetimi
│   ├── comments.php              # Yorum yönetimi
│   ├── privacy_requests.php      # Gizlilik istekleri
│   ├── duyuru.php                # Duyuru yönetimi
│   ├── statistics.php            # İstatistikler
│   └── header.php                # Admin header
│
├── 🔧 Workshop (/Workshop/)
│   ├── index.php                 # Workshop ana sayfa
│   ├── project.php               # Proje detay sayfası
│   ├── api.php                   # Workshop API
│   ├── comment_handler.php       # Yorum işleyici
│   └── footer.php                # Workshop footer
│
├── 📚 Kütüphaneler (/includes/)
│   ├── database.php              # Veritabanı bağlantısı
│   ├── auth.php                  # Oturum yönetimi
│   ├── session_start.php         # Oturum başlatma
│   ├── functions.php             # Yardımcı fonksiyonlar
│   ├── mailer.php                # Email gönderme
│   └── file_upload_helper.php    # Dosya yükleme yardımcısı
│
├── 🗂️ Kurulum Dosyaları
│   ├── install_privacy.php       # Gizlilik özellikleri kurulumu
│   ├── install_password_resets.php # Şifre sıfırlama kurulumu
│   └── add_privacy_fields.sql    # Veritabanı migration script
│
├── 🖼️ Public Dosyalar (/public/)
│   ├── uploads/                  # Yüklenen dosyalar
│   └── logo.png                  # Site logosu
│
└── 📂 Diğer
    ├── assets/                   # CSS/JS dosyaları
    ├── vendor/                   # Composer packages
    └── README.md                 # Bu dosya
```

---

## 🎛️ Yönetim Paneli

Admin paneline erişmek için:
1. Admin kullanıcı ile giriş yapın
2. `/admin/` dizinine gidin

### Özellikler:
- **Dashboard**: Genel istatistikler ve hızlı erişim
- **Kullanıcı Yönetimi**: Kullanıcıları düzenleme, silme, rol atama
- **Proje Yönetimi**: Projeleri onaylama, düzenleme, silme
- **Yorum Yönetimi**: Yorumları onaylama, reddetme, silme
- **Gizlilik İstekleri**: Proje gizlilik isteklerini değerlendirme
- **Duyurular**: Sistem duyurularını yönetme
- **İstatistikler**: Detaylı raporlar ve grafikler

---

## 🛠️ Workshop

Workshop, geliştiricilerin projelerini paylaşabildiği merkezi platformdur.

### Özellikler:
- **Proje Gösterimi**: Kategorilere göre proje listeleme
- **Detay Sayfası**: Proje detayları, yorumlar, değerlendirmeler
- **Yorum Sistemi**: 5 yıldızlı değerlendirme sistemi
- **API Entegrasyonu**: Dış uygulamalar için API desteği
- **Filtreleme**: Kategori ve arama filtreleri

### API Kullanımı:
```php
GET /Workshop/api.php
```

Döndürülen veriler:
```json
{
  "themes": [...],
  "plugins": [...]
}
```

---

## 🔐 Güvenlik

### Güvenlik Önlemleri:
- ✅ PDO ile SQL injection koruması
- ✅ XSS koruması (htmlspecialchars)
- ✅ CSRF token koruması
- ✅ Güvenli şifre hashing (password_hash)
- ✅ Oturum güvenliği
- ✅ Dosya yükleme güvenliği
- ✅ Input validation ve sanitizasyon

### Email Konfigürasyonu:
- Sunucu: `mail-sunucusu`
- Güvenlik: TLS (SMTPS)
- Kullanıcı: `noreply@seninmailin.com`

---

## ⚡ Yapılması Gereken Adımlar

### 1. 🗄️ Veritabanı Kurulumu
```bash
# Veritabanı tablolarını oluşturun
mysql -u root -p artadodevs < clean_database.sql
```

### 2. 📧 Email Test
```bash
# Email gönderimini test edin
# Şifre sıfırlama fonksiyonunu deneyin
```

### 3. 🖼️ Logo Dosyası
```bash
# logo.png dosyasının ana dizinde olduğundan emin olun
ls -la logo.png
```

### 4. 🔧 İzinler
```bash
# Dosya izinlerini kontrol edin
chmod -R 755 .
chmod -R 777 public/uploads/
```

### 5. 🧪 Test Etmek
- Kullanıcı kaydı ve giriş
- Proje oluşturma ve gizlilik ayarları
- Workshop yorum sistemi
- Email bildirimleri
- Admin paneli fonksiyonları

---

## 📞 Destek

Sorunlarınız için:
- 📧 Email: sxi@artadosearch.com
- 💬 Forum: https://forum.artado.xyz
- 📱 Matrix: https://matrix.to/#/#artadoproject:matrix.org

---

## 📄 Lisans

Bu proje MIT lisansı altında dağıtılmaktadır.

---

## 🔄 Versiyon Geçmişi

### v2.0.0 (Güncel)
- ✅ Proje gizlilik sistemi eklendi
- ✅ Yorum ve değerlendirme sistemi
- ✅ Admin paneli iyileştirmeleri
- ✅ Email bildirim sistemi
- ✅ API güncellemeleri
- ✅ Güvenlik iyileştirmeleri

### v1.0.0
- ✅ Temel kullanıcı sistemi
- ✅ Proje yönetimi
- ✅ Workshop sistemi
- ✅ Admin paneli

---

**Not**: Bu platform Artado Developers topluluğu için geliştirilmiştir. Katkıda bulunmak için lütfen GitHub repository'muzu ziyaret edin.
