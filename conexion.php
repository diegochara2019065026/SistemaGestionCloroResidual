<?php
$host = "localhost";
$usuario = "root";
$password = ""; // tu contraseña MySQL si la tienes
$base_de_datos = "sgmcr";

$conn = new mysqli($host, $usuario, $password, $base_de_datos);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>