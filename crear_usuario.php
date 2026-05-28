<?php
session_start();
require 'conexion.php';

// Verificar que sea administrador
if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    $rol_id = $_POST['rol'];

    // Generar hash de la contraseña
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre, correo, contrasena, rol_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $correo, $hash, $rol_id);

    if ($stmt->execute()) {
        $success = "Usuario creado correctamente.";
    } else {
        $error = "Error al crear usuario: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Usuario</title>
<link rel="stylesheet" href="registro.css">
</head>
<body>
<div class="register-box">
    <!-- Panel izquierdo con información -->
    <div class="info-panel">
        <h1>DATASS</h1>
        <p>Registro de nuevo usuario para el sistema de gestión de agua y saneamiento.</p>
    </div>

    <!-- Panel derecho con formulario -->
    <div class="form-panel">
        <h2>Crear cuenta</h2>

        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        <?php if($success) echo "<div class='success'>$success</div>"; ?>

        <form method="post">
            <input type="text" name="nombre" placeholder="Nombre completo" required><br>
            <input type="email" name="correo" placeholder="Correo electrónico" required><br>
            <input type="password" name="contrasena" placeholder="Contraseña" required><br>
            <select name="rol" required>
                <option value="">Seleccionar rol</option>
                <?php
                $roles = $conn->query("SELECT id, nombre_rol FROM roles");
                while($r = $roles->fetch_assoc()){
                    echo "<option value='{$r['id']}'>{$r['nombre_rol']}</option>";
                }
                ?>
            </select><br><br>
            <button type="submit">Registrarse</button>
        </form>

        <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar Sesión</a></p>
    </div>
</div>
</body>
</html>