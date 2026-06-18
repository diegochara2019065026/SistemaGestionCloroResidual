<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_admin();

$error = '';
$success = '';
$editando = null;
$busqueda = trim($_GET['buscar'] ?? '');
$accion = $_POST['accion'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } elseif ($accion === 'crear' || $accion === 'actualizar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $distrito = trim($_POST['distrito'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');

        if ($nombre === '') {
            $error = "El nombre de la municipalidad es obligatorio.";
        } elseif ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO municipalidades (nombre, distrito, provincia, departamento) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $distrito, $provincia, $departamento);

            if ($stmt->execute()) {
                $success = "Municipalidad registrada correctamente.";
            } else {
                $error = "No se pudo registrar la municipalidad.";
            }
        } else {
            if ($id <= 0) {
                $error = "Selecciona una municipalidad válida para actualizar.";
            } else {
                $stmt = $conn->prepare("UPDATE municipalidades SET nombre = ?, distrito = ?, provincia = ?, departamento = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nombre, $distrito, $provincia, $departamento, $id);

                if ($stmt->execute()) {
                    $success = "Municipalidad actualizada correctamente.";
                } else {
                    $error = "No se pudo actualizar la municipalidad.";
                }
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $error = "Selecciona una municipalidad válida para eliminar.";
        } else {
            $stmt = $conn->prepare("DELETE FROM municipalidades WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $success = "Municipalidad eliminada correctamente.";
            } else {
                $error = "No se pudo eliminar la municipalidad.";
            }
        }
    }
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $conn->prepare("SELECT id, nombre, distrito, provincia, departamento FROM municipalidades WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();

    if (!$editando) {
        $error = "No se encontró la municipalidad seleccionada.";
    }
}

if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $stmt = $conn->prepare("
        SELECT id, nombre, distrito, provincia, departamento
        FROM municipalidades
        WHERE nombre LIKE ? OR distrito LIKE ? OR provincia LIKE ? OR departamento LIKE ?
        ORDER BY nombre
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $municipalidades = $stmt->get_result();
} else {
    $municipalidades = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM municipalidades ORDER BY nombre");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento de Municipalidades</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.toolbar form { display: flex; gap: 8px; align-items: center; }
.toolbar input { min-width: 280px; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.secondary { background: #555; }
.button.light { background: #eee; color: #333; }
.form-grid { display: grid; grid-template-columns: repeat(4, minmax(140px, 1fr)); gap: 12px; margin-bottom: 18px; }
.form-grid label { display: flex; flex-direction: column; gap: 5px; font-size: 14px; color: #333; }
.form-grid input { padding: 9px; border: 1px solid #ccc; border-radius: 5px; }
.form-actions { display: flex; gap: 8px; align-items: end; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 9px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
.actions { display: flex; gap: 6px; }
.delete-form { margin: 0; }
@media (max-width: 900px) {
    .form-grid { grid-template-columns: 1fr; }
    .toolbar { align-items: stretch; flex-direction: column; }
    .toolbar form { align-items: stretch; flex-direction: column; }
    .toolbar input { min-width: 0; }
}
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
            <li><a href="crear_usuario.php">Crear Usuario</a></li>
        <?php endif; ?>
        <?php if (has_any_role(['Administrador', 'Municipalidad'])): ?>
            <li><a href="solicitar_asistencia.php">Solicitar Asistencia</a></li>
        <?php endif; ?>
        <?php if (has_any_role(['Administrador', 'Gobierno Regional'])): ?>
            <li><a href="gestionar_asistencia.php">Gestionar Asistencia</a></li>
        <?php endif; ?>
        <li>
            <a href="#" class="submenu-toggle">Mantenimiento &#9662;</a>
            <ul class="submenu open">
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
    <header>
        <div class="user-info">
            <span><?php echo e($_SESSION['nombre']); ?></span> |
            <span><?php echo e($_SESSION['rol']); ?></span>
        </div>
    </header>

    <section class="content">
        <div class="toolbar">
            <h2>Municipalidades</h2>
            <form method="get">
                <input type="text" name="buscar" value="<?php echo e($busqueda); ?>" placeholder="Buscar por nombre, distrito, provincia o departamento">
                <button type="submit">Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a class="button light" href="mantenimiento_municipalidades.php">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="accion" value="<?php echo $editando ? 'actualizar' : 'crear'; ?>">
            <input type="hidden" name="id" value="<?php echo e($editando['id'] ?? ''); ?>">

            <div class="form-grid">
                <label>
                    Nombre
                    <input type="text" name="nombre" value="<?php echo e($editando['nombre'] ?? ''); ?>" required>
                </label>
                <label>
                    Distrito
                    <input type="text" name="distrito" value="<?php echo e($editando['distrito'] ?? ''); ?>">
                </label>
                <label>
                    Provincia
                    <input type="text" name="provincia" value="<?php echo e($editando['provincia'] ?? ''); ?>">
                </label>
                <label>
                    Departamento
                    <input type="text" name="departamento" value="<?php echo e($editando['departamento'] ?? ''); ?>">
                </label>
            </div>

            <div class="form-actions">
                <button type="submit"><?php echo $editando ? 'Actualizar' : 'Registrar'; ?></button>
                <?php if ($editando): ?>
                    <a class="button secondary" href="mantenimiento_municipalidades.php">Cancelar edición</a>
                <?php endif; ?>
            </div>
        </form>

        <br>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Distrito</th>
                    <th>Provincia</th>
                    <th>Departamento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($municipalidades->num_rows === 0): ?>
                    <tr>
                        <td colspan="6">No hay municipalidades registradas.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($row = $municipalidades->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['nombre']); ?></td>
                        <td><?php echo e($row['distrito']); ?></td>
                        <td><?php echo e($row['provincia']); ?></td>
                        <td><?php echo e($row['departamento']); ?></td>
                        <td>
                            <div class="actions">
                                <a class="button light" href="mantenimiento_municipalidades.php?editar=<?php echo e($row['id']); ?>">Editar</a>
                                <form class="delete-form" method="post" onsubmit="return confirm('¿Eliminar esta municipalidad?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                    <button type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
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
</script>
</body>
</html>
