<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_admin();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $rol_id = (int) ($_POST['rol'] ?? 0);

    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($contrasena) < 6 || $rol_id <= 0) {
        $error = "Completa todos los campos. La contraseña debe tener al menos 6 caracteres.";
    } else {
        $stmtRol = $conn->prepare("SELECT id FROM roles WHERE id = ?");
        $stmtRol->bind_param("i", $rol_id);
        $stmtRol->execute();
        $rolExiste = $stmtRol->get_result()->num_rows > 0;

        if (!$rolExiste) {
            $error = "Selecciona un rol válido.";
        } else {
            $stmtCorreo = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmtCorreo->bind_param("s", $correo);
            $stmtCorreo->execute();
            $correoExiste = $stmtCorreo->get_result()->num_rows > 0;

            if ($correoExiste) {
                $error = "El correo ya está registrado.";
            } else {
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios (nombre, correo, contrasena, rol_id) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nombre, $correo, $hash, $rol_id);

                if ($stmt->execute()) {
                    $success = "Usuario creado correctamente.";
                } else {
                    $error = "No se pudo crear el usuario. Intenta nuevamente.";
                }
            }
        }
    }
}

$roles = $conn->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol");
$usuarios = $conn->query("
    SELECT u.id, u.nombre, u.correo, u.estado, u.fecha_creacion, r.nombre_rol
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    ORDER BY u.fecha_creacion DESC, u.id DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Usuario</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { margin-bottom: 18px; }
.page-title h2 { margin: 0 0 6px; font-size: 26px; color: #2f343b; }
.page-title p { margin: 0; color: #777; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 14px; margin-bottom: 16px; }
.form-grid label { display: flex; flex-direction: column; gap: 6px; color: #333; font-size: 14px; font-weight: bold; }
.form-grid input, .form-grid select { padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
.form-actions { display: flex; gap: 10px; align-items: center; }
.button, button { padding: 10px 15px; border: none; border-radius: 6px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
.section-title { margin: 24px 0 12px; color: #333; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 10px; border-bottom: 1px solid #e7e7e7; text-align: left; }
th { background: #f7f8fa; color: #666; font-size: 13px; text-transform: uppercase; }
tr:hover td { background: #fafafa; }
.status { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #e8f5e9; color: #1b5e20; font-size: 12px; font-weight: bold; }
@media (max-width: 800px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="crear_usuario.php">Crear Usuario</a></li>
        <?php if (has_any_role(['Administrador', 'Municipalidad'])): ?>
            <li><a href="solicitar_asistencia.php">Solicitar Asistencia</a></li>
        <?php endif; ?>
        <?php if (has_any_role(['Administrador', 'Gobierno Regional'])): ?>
            <li><a href="gestionar_asistencia.php">Gestionar Asistencia</a></li>
        <?php endif; ?>
        <li>
            <a href="#" class="submenu-toggle">Mantenimiento &#9662;</a>
            <ul class="submenu">
                <li><a href="mantenimiento_centros.php">Centros Poblados</a></li>
                <li><a href="mantenimiento_municipalidades.php">Municipalidades</a></li>
                <li><a href="mantenimiento_jass.php">JASS</a></li>
                <li><a href="mantenimiento_sistemas_agua.php">Sistemas de Agua</a></li>
                <li><a href="mantenimiento_ficha.php">Ficha Cloración</a></li>
            </ul>
        </li>
        <li><a href="logout.php">Cerrar sesión</a></li>
    </ul>
</div>

<div class="main">
    <header class="datass-header">
        <div class="datass-brand">
            <div class="brand-mark">PERÚ</div>
            <div class="brand-ministry">Ministerio de Vivienda, Construcción y Saneamiento</div>
            <strong>DATASS</strong>
        </div>
        <div class="datass-clock">
            <span class="clock-icon"></span>
            <div>
                <strong id="currentDateTime"></strong>
                <span>Tacna, Tacna, Peru (PE)</span>
            </div>
        </div>
        <div class="datass-user">
            <span class="bell-icon">!</span>
            <div class="user-avatar"><?php echo e(strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1))); ?></div>
            <div>
                <strong><?php echo e($_SESSION['nombre']); ?></strong>
                <span><?php echo e($_SESSION['rol']); ?></span>
            </div>
        </div>
    </header>

    <section class="content">
        <div class="page-title">
            <h2>Crear Usuario</h2>
            <p>Registra usuarios y asigna roles para el acceso al sistema.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-grid">
                <label>
                    Nombre completo
                    <input type="text" name="nombre" required>
                </label>
                <label>
                    Correo electrónico
                    <input type="email" name="correo" required>
                </label>
                <label>
                    Contraseña
                    <input type="password" name="contrasena" minlength="6" required>
                </label>
                <label>
                    Rol
                    <select name="rol" required>
                        <option value="">Seleccionar rol</option>
                        <?php while ($r = $roles->fetch_assoc()): ?>
                            <option value="<?php echo e($r['id']); ?>"><?php echo e($r['nombre_rol']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit">Crear usuario</button>
                <a class="button light" href="dashboard.php">Volver</a>
            </div>
        </form>

        <h3 class="section-title">Usuarios registrados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha creación</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios->num_rows === 0): ?>
                    <tr><td colspan="6">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($usuario['id']); ?></td>
                        <td><?php echo e($usuario['nombre']); ?></td>
                        <td><?php echo e($usuario['correo']); ?></td>
                        <td><?php echo e($usuario['nombre_rol']); ?></td>
                        <td><span class="status"><?php echo ((int) $usuario['estado'] === 1) ? 'Activo' : 'Inactivo'; ?></span></td>
                        <td><?php echo e($usuario['fecha_creacion']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</div>

<script>
const toggles = document.querySelectorAll('.submenu-toggle');
toggles.forEach(t => {
    t.addEventListener('click', function(e) {
        e.preventDefault();
        this.nextElementSibling.classList.toggle('open');
    });
});

function updateDateTime() {
    const target = document.getElementById('currentDateTime');
    const now = new Date();
    const date = now.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    const time = now.toLocaleTimeString('es-PE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    target.textContent = `${date}, ${time}`;
}

updateDateTime();
setInterval(updateDateTime, 1000);
</script>
</body>
</html>
