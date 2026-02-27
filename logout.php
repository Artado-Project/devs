<?php
// Oturumu başlat
session_start();

// Tüm oturum verilerini temizle
session_unset();

// Oturumu sonlandır
session_destroy();

// Kullanıcıyı ana sayfaya veya giriş sayfasına yönlendir
header('Location: index.php');  // Ana sayfaya veya istediğiniz sayfaya yönlendirebilirsiniz
exit();
?>
