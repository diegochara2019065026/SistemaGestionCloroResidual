<?php
session_start();

// 1. Verificamos si el usuario ha iniciado sesión
if (!isset($_SESSION['rol'])) {
    header('Location: login.php');
    exit();
}

// 2. Definimos los roles permitidos para esta página
$rolesPermitidos = ['admin', 'editor'];

// 3. Verificamos si el rol actual está dentro de los permitidos
if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
    // Si no tiene permiso, lo redirigimos a una página de acceso denegado
    header('Location: acceso-denegado.php');
    exit();
}

// Si pasa la validación, carga el resto de la página
echo "¡Bienvenido al panel de control!";
?>
