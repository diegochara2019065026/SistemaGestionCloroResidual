<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_login();

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
        $tipo_sistema = trim($_POST['tipo_sistema'] ?? '');
        $centro_poblado_id = (int) ($_POST['centro_poblado_id'] ?? 0);
        $municipalidad_id = ($_POST['municipalidad_id'] ?? '') === '' ? null : (int) $_POST['municipalidad_id'];

        if ($nombre === '' || $centro_poblado_id <= 0) {
            $error = "Completa el nombre del sistema y selecciona un centro poblado.";
        } elseif ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO sistemas_agua (nombre, tipo_sistema, centro_poblado_id, municipalidad_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $nombre, $tipo_sistema, $centro_poblado_id, $municipalidad_id);
            $success = $stmt->execute() ? "Sistema de agua registrado correctamente." : "No se pudo registrar el sistema.";
        } else {
            $stmt = $conn->prepare("UPDATE sistemas_agua SET nombre = ?, tipo_sistema = ?, centro_poblado_id = ?, municipalidad_id = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $nombre, $tipo_sistema, $centro_poblado_id, $municipalidad_id, $id);
            $success = $stmt->execute() ? "Sistema de agua actualizado correctamente." : "No se pudo actualizar el sistema.";
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM sistemas_agua WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute() ? "Sistema de agua eliminado correctamente." : "No se pudo eliminar el sistema.";
    }
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $conn->prepare("SELECT id, nombre, tipo_sistema, centro_poblado_id, municipalidad_id FROM sistemas_agua WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();
}

$centros = $conn->query("SELECT id, nombre, distrito, provincia FROM centros_poblados ORDER BY nombre");
$municipalidades = $conn->query("SELECT id, nombre FROM municipalidades ORDER BY nombre");

if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $stmt = $conn->prepare("
        SELECT s.id, s.nombre, s.tipo_sistema, c.nombre AS centro_poblado, m.nombre AS municipalidad
        FROM sistemas_agua s
        INNER JOIN centros_poblados c ON s.centro_poblado_id = c.id
        LEFT JOIN municipalidades m ON s.municipalidad_id = m.id
        WHERE s.nombre LIKE ? OR s.tipo_sistema LIKE ? OR c.nombre LIKE ? OR m.nombre LIKE ?
        ORDER BY c.nombre, s.nombre
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $sistemas = $stmt->get_result();
} else {
    $sistemas = $conn->query("
        SELECT s.id, s.nombre, s.tipo_sistema, c.nombre AS centro_poblado, m.nombre AS municipalidad
        FROM sistemas_agua s
        INNER JOIN centros_poblados c ON s.centro_poblado_id = c.id
        LEFT JOIN municipalidades m ON s.municipalidad_id = m.id
        ORDER BY c.nombre, s.nombre
    ");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento de Sistemas de Agua</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.toolbar form { display: flex; gap: 8px; align-items: center; }
.toolbar input { min-width: 280px; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.button.secondary { background: #555; }
.form-grid { display: grid; grid-template-columns: repeat(4, minmax(140px, 1fr)); gap: 12px; margin-bottom: 18px; }
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
@media (max-width: 900px) { .form-grid, .toolbar, .toolbar form { display: flex; flex-direction: column; align-items: stretch; } .toolbar input { min-width: 0; } }
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
            <h2>Sistemas de Agua</h2>
            <form method="get">
                <input type="text" name="buscar" value="<?php echo e($busqueda); ?>" placeholder="Buscar sistema, centro o municipalidad">
                <button type="submit">Buscar</button>
                <?php if ($busqueda !== ''): ?><a class="button light" href="mantenimiento_sistemas_agua.php">Limpiar</a><?php endif; ?>
            </form>
        </div>
        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?php echo e($success); ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="accion" value="<?php echo $editando ? 'actualizar' : 'crear'; ?>">
            <input type="hidden" name="id" value="<?php echo e($editando['id'] ?? ''); ?>">
            <div class="form-grid">
                <label>Nombre del sistema
                    <input type="text" name="nombre" value="<?php echo e($editando['nombre'] ?? ''); ?>" required>
                </label>
                <label>Tipo de sistema
                    <input type="text" name="tipo_sistema" value="<?php echo e($editando['tipo_sistema'] ?? ''); ?>">
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
                <label>Municipalidad
                    <select name="municipalidad_id">
                        <option value="">Sin municipalidad</option>
                        <?php while ($muni = $municipalidades->fetch_assoc()): ?>
                            <option value="<?php echo e($muni['id']); ?>" <?php echo (($editando['municipalidad_id'] ?? '') == $muni['id']) ? 'selected' : ''; ?>>
                                <?php echo e($muni['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit"><?php echo $editando ? 'Actualizar' : 'Registrar'; ?></button>
                <?php if ($editando): ?><a class="button secondary" href="mantenimiento_sistemas_agua.php">Cancelar edición</a><?php endif; ?>
            </div>
        </form>
        <br>
        <table>
            <thead><tr><th>ID</th><th>Sistema</th><th>Tipo</th><th>Centro poblado</th><th>Municipalidad</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if ($sistemas->num_rows === 0): ?><tr><td colspan="6">No hay sistemas de agua registrados.</td></tr><?php endif; ?>
                <?php while ($row = $sistemas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['nombre']); ?></td>
                        <td><?php echo e($row['tipo_sistema']); ?></td>
                        <td><?php echo e($row['centro_poblado']); ?></td>
                        <td><?php echo e($row['municipalidad']); ?></td>
                        <td><div class="actions">
                            <a class="button light" href="mantenimiento_sistemas_agua.php?editar=<?php echo e($row['id']); ?>">Editar</a>
                            <form class="delete-form" method="post" onsubmit="return confirm('¿Eliminar este sistema de agua?');">
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
