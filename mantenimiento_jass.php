<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_admin();

$error = '';
$success = '';
$editando = null;
$accion = $_POST['accion'] ?? '';
$busqueda = trim($_GET['buscar'] ?? '');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } elseif ($accion === 'crear' || $accion === 'actualizar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $centro_poblado_id = (int) ($_POST['centro_poblado_id'] ?? 0);

        if ($nombre === '' || $centro_poblado_id <= 0) {
            $error = "Completa el nombre de la JASS y selecciona un centro poblado.";
        } elseif ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO jass (nombre, centro_poblado_id) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $centro_poblado_id);
            $success = $stmt->execute() ? "JASS registrada correctamente." : "No se pudo registrar la JASS.";
        } else {
            $stmt = $conn->prepare("UPDATE jass SET nombre = ?, centro_poblado_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $nombre, $centro_poblado_id, $id);
            $success = $stmt->execute() ? "JASS actualizada correctamente." : "No se pudo actualizar la JASS.";
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM jass WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute() ? "JASS eliminada correctamente." : "No se pudo eliminar la JASS.";
    }
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $conn->prepare("SELECT id, nombre, centro_poblado_id FROM jass WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();
}

$centros = $conn->query("SELECT id, nombre, distrito, provincia FROM centros_poblados ORDER BY nombre");

if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $stmt = $conn->prepare("
        SELECT j.id, j.nombre, c.nombre AS centro_poblado, c.distrito, c.provincia
        FROM jass j
        INNER JOIN centros_poblados c ON j.centro_poblado_id = c.id
        WHERE j.nombre LIKE ? OR c.nombre LIKE ? OR c.distrito LIKE ? OR c.provincia LIKE ?
        ORDER BY c.nombre, j.nombre
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $jass = $stmt->get_result();
} else {
    $jass = $conn->query("
        SELECT j.id, j.nombre, c.nombre AS centro_poblado, c.distrito, c.provincia
        FROM jass j
        INNER JOIN centros_poblados c ON j.centro_poblado_id = c.id
        ORDER BY c.nombre, j.nombre
    ");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento JASS</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.toolbar form { display: flex; gap: 8px; align-items: center; }
.toolbar input { min-width: 280px; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.button.secondary { background: #555; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
label { display: flex; flex-direction: column; gap: 5px; font-size: 14px; color: #333; }
input, select { padding: 9px; border: 1px solid #ccc; border-radius: 5px; }
.form-actions, .actions { display: flex; gap: 8px; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 9px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
.delete-form { margin: 0; }
@media (max-width: 800px) { .form-grid, .toolbar, .toolbar form { display: flex; flex-direction: column; align-items: stretch; } .toolbar input { min-width: 0; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
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
    <header><div class="user-info"><span><?php echo e($_SESSION['nombre']); ?></span> | <span><?php echo e($_SESSION['rol']); ?></span></div></header>
    <section class="content">
        <div class="toolbar">
            <h2>JASS</h2>
            <form method="get">
                <input type="text" name="buscar" value="<?php echo e($busqueda); ?>" placeholder="Buscar JASS o centro poblado">
                <button type="submit">Buscar</button>
                <?php if ($busqueda !== ''): ?><a class="button light" href="mantenimiento_jass.php">Limpiar</a><?php endif; ?>
            </form>
        </div>
        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?php echo e($success); ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="accion" value="<?php echo $editando ? 'actualizar' : 'crear'; ?>">
            <input type="hidden" name="id" value="<?php echo e($editando['id'] ?? ''); ?>">
            <div class="form-grid">
                <label>Nombre JASS
                    <input type="text" name="nombre" value="<?php echo e($editando['nombre'] ?? ''); ?>" required>
                </label>
                <label>Centro poblado
                    <select name="centro_poblado_id" required>
                        <option value="">Seleccionar</option>
                        <?php while ($centro = $centros->fetch_assoc()): ?>
                            <option value="<?php echo e($centro['id']); ?>" <?php echo (($editando['centro_poblado_id'] ?? '') == $centro['id']) ? 'selected' : ''; ?>>
                                <?php echo e($centro['nombre'] . ' - ' . $centro['distrito'] . ', ' . $centro['provincia']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit"><?php echo $editando ? 'Actualizar' : 'Registrar'; ?></button>
                <?php if ($editando): ?><a class="button secondary" href="mantenimiento_jass.php">Cancelar edición</a><?php endif; ?>
            </div>
        </form>
        <br>
        <table>
            <thead><tr><th>ID</th><th>JASS</th><th>Centro poblado</th><th>Distrito</th><th>Provincia</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if ($jass->num_rows === 0): ?><tr><td colspan="6">No hay JASS registradas.</td></tr><?php endif; ?>
                <?php while ($row = $jass->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['nombre']); ?></td>
                        <td><?php echo e($row['centro_poblado']); ?></td>
                        <td><?php echo e($row['distrito']); ?></td>
                        <td><?php echo e($row['provincia']); ?></td>
                        <td><div class="actions">
                            <a class="button light" href="mantenimiento_jass.php?editar=<?php echo e($row['id']); ?>">Editar</a>
                            <form class="delete-form" method="post" onsubmit="return confirm('¿Eliminar esta JASS?');">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</div>
<script>document.querySelectorAll('.submenu-toggle').forEach(t=>t.addEventListener('click',e=>{e.preventDefault();t.nextElementSibling.classList.toggle('open');}));</script>
</body>
</html>
