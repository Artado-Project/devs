<?php
$host = "/";
$dbname = "";
$username = "";
$password = "+";

try {
  $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch(PDOException $e) {
  echo "Veritabanı bağlantı hatası: " . $e->getMessage();
}