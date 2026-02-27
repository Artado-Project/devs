<?php
$host = "localhost";
$dbname = "artadodevs";
$username = "artado";
$password = "artado";


try {
  $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Veritabanı bağlantı hatası: " . $e->getMessage();
}

?>