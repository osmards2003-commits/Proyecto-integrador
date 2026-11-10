<?php
$host = 'localhost';
$port = '3307';
$dbname = 'llantas_mr_garcia';
$username = 'root';
$password = 'david2003'; // Cambia esto si tu usuario de MySQL tiene contraseña

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>