<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcı oturum kontrolü
$user_id = $_SESSION['user_id'] ?? null;

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Varsayılan profil fotoğrafı
$default_profile_photo = 'https://artado.xyz/assest/img/artado-yeni.png';

// Başarı ve hata mesajlarını tanımla
$success_message = '';
$error_message = '';

// Kullanıcı bilgilerini çek
if ($user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $profile_photo = $user['profile_photo'] ?: $default_profile_photo;
} else {
    $profile_photo = $default_profile_photo;
}

// Başarı ve hata mesajlarını tanımla
$success_message = '';
$error_message = '';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Şifre alanları boş mu kontrolü
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Lütfen tüm alanları doldurunuz.";
    }
    // Yeni şifreler eşleşiyor mu kontrolü
    elseif ($new_password !== $confirm_password) {
        $error_message = "Yeni şifreler eşleşmiyor.";
    }
    // Yeni şifre minimum uzunluk kontrolü
    elseif (strlen($new_password) < 6) {
        $error_message = "Yeni şifre en az 6 karakter uzunluğunda olmalıdır.";
    }
    else {
        // Mevcut şifreyi doğrula
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($current_password, $row['password'])) {
            $error_message = "Mevcut şifreniz hatalı.";
        } else {
            // Yeni şifre mevcut şifre ile aynı mı kontrolü
            if (password_verify($new_password, $row['password'])) {
                $error_message = "Yeni şifreniz mevcut şifrenizle aynı olamaz.";
            } else {
                // Yeni şifreyi şifrele
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Veritabanında şifreyi güncelle
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);

                if ($stmt->execute()) {
                    $success_message = "Şifreniz başarıyla güncellendi.";
                } else {
                    $error_message = "Şifre güncellenirken bir hata oluştu.";
                }
            }
        }
    }
}

// Mevcut kodun başına ekleyin
$delete_error_message = '';
$delete_success_message = '';

// Hesap silme işlemi için form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'] ?? '';
    
    if (empty($password)) {
        $delete_error_message = "Hesabınızı silmek için şifrenizi girmelisiniz.";
    } else {
        // Şifreyi doğrula
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($password, $row['password'])) {
            $delete_error_message = "Girdiğiniz şifre hatalı.";
        } else {
            try {
                // Kullanıcının tüm verilerini silme işlemleri
                $db->beginTransaction();

                // Kullanıcının projelerini sil
                $stmt = $db->prepare("DELETE FROM projects WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                // Kullanıcının hesabını sil
                $stmt = $db->prepare("DELETE FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                $db->commit();

                // Oturumu sonlandır
                session_destroy();
                
                // Kullanıcıyı giriş sayfasına yönlendir
                header("Location: login.php?message=account_deleted");
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $delete_error_message = "Hesap silinirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenlik Ayarları - Artado Developers</title>
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/png">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app-dark.css">
</head>

<body>
    <script src="assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="index.php"><img src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" alt="Logo"></a>
                        </div>
                        <div class="theme-toggle d-flex gap-2  align-items-center mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20"
                                height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                                        opacity=".3"></path>
                                    <g transform="translate(-210 -1)">
                                        <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                        <circle cx="220.5" cy="11.5" r="4"></circle>
                                        <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2">
                                        </path>
                                    </g>
                                </g>
                            </svg>
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input  me-0" type="checkbox" id="toggle-dark"
                                    style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20"
                                preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                                </path>
                            </svg>
                        </div>
                        <div class="sidebar-toggler  x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Konsol</li>

                        <li class="sidebar-item ">
                            <a href="index.php" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="../Workshop/" class='sidebar-link'>
                                <i class="bi bi-tools"></i>
                                <span>Workshop</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="projects.php" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span>Projelerim</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="announcements.php" class='sidebar-link'>
                                <i class="bi bi-journal-check"></i>
                                <span>Duyurular</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="todo-list.php" class='sidebar-link'>
                                <i class="bi bi-file-earmark-medical-fill"></i>
                                <span>Yapılacaklar Listesi</span>
                            </a>
                        </li>

                        <li class="sidebar-item active">
                            <a href="account-profile.php" class='sidebar-link'>
                                <i class="bi bi-person-circle"></i>
                                <span>Hesap</span>
                            </a>
                        </li>

                        <li class="sidebar-item active">
                            <a href="account-security.php" class='sidebar-link'>
                                <i class="bi bi-shield-lock"></i>
                                <span>Güvenlik</span>
                            </a>
                        </li>

                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="https://artadosearch.com" class="submenu-link">Artado Search</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://forum.artado.xyz" class="submenu-link">Forum</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://matrix.to/#/#artadoproject:matrix.org" class="submenu-link">Matrix</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://x.com/ArtadoL" class="submenu-link">Twitter</a>
                                </li>
                            </ul>

                        </li>

                        <li class="sidebar-item  has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Destekde Bulunun</span>
                            </a>

                            <ul class="submenu ">



                                <li class="submenu-item  ">
                                    <a href="https://kreosus.com/artadosoft?hl=tr" class="submenu-link">Kreosus</a>

                                </li>
                            </ul>


                        </li>

                        <li class="sidebar-title">Çalışma Panelim</li>

                        <li class="sidebar-item  has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-hexagon-fill"></i>
                                <span>Proje Oluştur</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="create-eklenti.php" class="submenu-link">Eklenti</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-logo.php" class="submenu-link">Logo</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-theme.php" class="submenu-link">Tema</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-mobil.php" class="submenu-link">Mobil Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-pc.php" class="submenu-link">PC Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-pc.php" class="submenu-link">PC Oyun</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-mobil.php" class="submenu-link">Mobil Oyun</a>
                                </li>
                            </ul>


                        </li>

                        <li class="sidebar-item  ">
                            <a href="todo-list" class='sidebar-link'>
                                <i class="bi bi-file-earmark-medical-fill"></i>
                                <span>Yapılacaklar Listesi</span>
                            </a>


                        </li>

                        <li class="sidebar-item">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-journal-check"></i>
                                <span>Duyurular</span>
                            </a>



                        </li>


                        <li class="sidebar-title">Ayarlar</li>



                        <li class="sidebar-item active has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-person-circle"></i>
                                <span>Account</span>
                            </a>

                            <ul class="submenu active">

                                <li class="submenu-item ">
                                    <a href="account-profile.php" class="submenu-link">Hesap Ayarları</a>

                                </li>

                                <li class="submenu-item active ">
                                    <a href="account-security.php" class="submenu-link">Güvenlik Ayarları</a>

                                </li>

                            </ul>


                        </li>

    
                    </ul>
                </div>
            </div>
        </div>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <div class="page-title">
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3>Hesap Ayarları</h3>
                            <p class="text-subtitle text-muted">Hesabınızın güvenlik ayarlarını değiştirebilirsiniz.</p>
                        </div>
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">Ana Sayfa</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Güvenlik</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <section class="section">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Şifreyi değiştir</h5>
                                </div>
                                <div class="card-body">
                                    <?php if($success_message): ?>
                                        <div class="alert alert-success">
                                            <?php echo $success_message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($error_message): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="post">
                                        <div class="form-group my-2">
                                            <label for="current_password" class="form-label">Güncel şifrenizi giriniz</label>
                                            <input type="password" name="current_password" id="current_password"
                                                class="form-control" placeholder="Güncel Şifrenizi giriniz">
                                        </div>
                                        <div class="form-group my-2">
                                            <label for="password" class="form-label">Yeni şifrenizi giriniz</label>
                                            <input type="password" name="password" id="password" class="form-control"
                                                placeholder="Yeni şifrenizi giriniz">
                                        </div>
                                        <div class="form-group my-2">
                                            <label for="confirm_password" class="form-label">Şifrenizi tekrar giriniz</label>
                                            <input type="password" name="confirm_password" id="confirm_password"
                                                class="form-control" placeholder="Yeni şifrenizi tekrar giriniz">
                                        </div>

                                        <div class="form-group my-2 d-flex justify-content-end">
                                            <button type="submit" name="change_password" class="btn btn-primary">Değişiklikleri kaydet</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>



                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Geliştirici Hesabımı Sil</h5>
                                </div>
                                <div class="card-body">
                                    <?php if($delete_error_message): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $delete_error_message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="post">
                                        <p>Artado Ayrıcalıklarından ve Avantajlarından Mahrum kalacaksınız hesabınızı silmek için işaretleyiniz</p>
                                        <div class="form-check">
                                            <div class="checkbox">
                                                <input type="checkbox" id="iaggree" class="form-check-input">
                                                <label for="iaggree">Hesabı silmek için işaretleyiniz</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group my-2">
                                            <label for="delete_password" class="form-label">Güvenlik için şifrenizi giriniz</label>
                                            <input type="password" name="delete_password" id="delete_password" class="form-control" required>
                                        </div>

                                        <div class="form-group my-2 d-flex justify-content-end">
                                            <button type="submit" name="delete_account" class="btn btn-danger" id="btn-delete-account" disabled>Hesabımı sil</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>2025 &copy; Artado Software</p>
                    </div>
                    <div class="float-end">
                        <p>Sxinar Tarafından <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                            ile <a href="https://sxi.is-a.dev">Sxinar</a></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="assets/static/js/components/dark.js"></script>
    <script src="assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>


    <script src="assets/compiled/js/app.js"></script>



    <script>
        const checkbox = document.getElementById("iaggree")
        const buttonDeleteAccount = document.getElementById("btn-delete-account")
        checkbox.addEventListener("change", function () {
            const checked = checkbox.checked
            if (checked) {
                buttonDeleteAccount.removeAttribute("disabled")
            } else {
                buttonDeleteAccount.setAttribute("disabled", true)
            }
        })
    </script>

</body>

</html>