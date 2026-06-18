<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

$error = '';

$columnaMunicipalidad = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'municipalidad_id'");
if ($columnaMunicipalidad && $columnaMunicipalidad->num_rows === 0) {
    $conn->query("ALTER TABLE usuarios ADD COLUMN municipalidad_id INT NULL AFTER rol_id");
    $conn->query("ALTER TABLE usuarios ADD INDEX idx_usuarios_municipalidad_id (municipalidad_id)");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $contrasena === '') {
        $error = "Ingresa un correo y contraseña válidos.";
    } else {
        $sql = "SELECT u.id, u.nombre, u.contrasena, u.municipalidad_id, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.correo = ? AND u.estado = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            session_regenerate_id(true);

            $_SESSION['id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['nombre_rol'];
            $_SESSION['municipalidad_id'] = $usuario['municipalidad_id'];

            header("Location: dashboard.php");
            exit();
        }

        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login SGMCR</title>
<link rel="stylesheet" href="login.css">
</head>
<body>
<div class="login-box">
    <div class="login-left">
        <h1>DATASS</h1>
        <p>Diagnóstico sobre abastecimiento de agua y saneamiento en el ámbito rural</p>
    </div>
    <div class="login-right">
        <h2>Acceso al Sistema</h2>
        <form method="post">
            <input type="email" name="correo" placeholder="Usuario o correo electrónico" required><br>
            <input type="password" name="contrasena" placeholder="Contraseña" required><br>
            <button type="submit">Iniciar</button>
        </form>
        <?php if ($error): ?>
            <div class="error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <div class="login-footer">
            ¿Necesitas una cuenta? <a href="#">Solicitar aquí</a>
        </div>
    </div>
</div>
</body>
</html>
