<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_admin();

$error = '';
$success = '';
$editando = null;
$busqueda = trim($_GET['buscar'] ?? '');
$filtroDistrito = (int) ($_GET['distrito_id'] ?? 0);
$accion = $_POST['accion'] ?? '';

function distrito_por_id($conn, $id) {
    $stmt = $conn->prepare("SELECT id, nombre, provincia, departamento FROM distritos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es valida. Recarga la pagina e intenta nuevamente.";
    } elseif ($accion === 'crear' || $accion === 'actualizar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $distritoId = (int) ($_POST['distrito_id'] ?? 0);
        $distrito = $distritoId > 0 ? distrito_por_id($conn, $distritoId) : null;

        if ($nombre === '') {
            $error = "El nombre del centro poblado es obligatorio.";
        } elseif (!$distrito) {
            $error = "Selecciona un distrito valido.";
        } elseif ($accion === 'crear') {
            $stmt = $conn->prepare("
                INSERT INTO centros_poblados (nombre, distrito_id, distrito, provincia, departamento)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sisss", $nombre, $distrito['id'], $distrito['nombre'], $distrito['provincia'], $distrito['departamento']);

            if ($stmt->execute()) {
                $success = "Centro poblado registrado correctamente.";
            } else {
                $error = "No se pudo registrar el centro poblado.";
            }
        } else {
            if ($id <= 0) {
                $error = "Selecciona un centro poblado valido para actualizar.";
            } else {
                $stmt = $conn->prepare("
                    UPDATE centros_poblados
                    SET nombre = ?, distrito_id = ?, distrito = ?, provincia = ?, departamento = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("sisssi", $nombre, $distrito['id'], $distrito['nombre'], $distrito['provincia'], $distrito['departamento'], $id);

                if ($stmt->execute()) {
                    $success = "Centro poblado actualizado correctamente.";
                } else {
                    $error = "No se pudo actualizar el centro poblado.";
                }
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $error = "Selecciona un centro poblado valido para eliminar.";
        } else {
            $stmt = $conn->prepare("DELETE FROM centros_poblados WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $success = "Centro poblado eliminado correctamente.";
            } else {
                $error = "No se pudo eliminar el centro poblado. Revisa si tiene JASS, sistemas, fichas o solicitudes vinculadas.";
            }
        }
    }
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $conn->prepare("
        SELECT c.id, c.nombre, c.distrito_id, c.distrito, c.provincia, c.departamento
        FROM centros_poblados c
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();

    if (!$editando) {
        $error = "No se encontro el centro poblado seleccionado.";
    }
}

$distritos = $conn->query("
    SELECT d.id, d.nombre, d.provincia, d.departamento, COUNT(c.id) AS total_centros
    FROM distritos d
    LEFT JOIN centros_poblados c ON c.distrito_id = d.id
    GROUP BY d.id, d.nombre, d.provincia, d.departamento
    ORDER BY d.departamento, d.provincia, d.nombre
");

$where = [];
$types = '';
$params = [];

if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $where[] = "(c.nombre LIKE ? OR d.nombre LIKE ? OR d.provincia LIKE ? OR d.departamento LIKE ?)";
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

if ($filtroDistrito > 0) {
    $where[] = "c.distrito_id = ?";
    $params[] = $filtroDistrito;
    $types .= 'i';
}

$sqlCentros = "
    SELECT c.id, c.nombre, c.distrito_id,
           COALESCE(d.nombre, c.distrito) AS distrito,
           COALESCE(d.provincia, c.provincia) AS provincia,
           COALESCE(d.departamento, c.departamento) AS departamento,
           COUNT(*) OVER (PARTITION BY c.distrito_id) AS total_distrito
    FROM centros_poblados c
    LEFT JOIN distritos d ON c.distrito_id = d.id
";

if ($where) {
    $sqlCentros .= " WHERE " . implode(" AND ", $where);
}

$sqlCentros .= " ORDER BY departamento, provincia, distrito, c.nombre";

if ($params) {
    $stmt = $conn->prepare($sqlCentros);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $centros = $stmt->get_result();
} else {
    $centros = $conn->query($sqlCentros);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento de Centros Poblados</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.filters { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(180px, 260px) auto auto; gap: 8px; align-items: center; }
.filters input, .filters select { padding: 9px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.secondary { background: #555; }
.button.light { background: #eee; color: #333; }
.form-grid { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(260px, 1fr); gap: 12px; margin-bottom: 18px; }
.form-grid label { display: flex; flex-direction: column; gap: 5px; font-size: 14px; color: #333; }
.form-grid input, .form-grid select { padding: 9px; border: 1px solid #ccc; border-radius: 5px; }
.form-actions { display: flex; gap: 8px; align-items: end; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
.district-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin: 10px 0 18px; }
.district-chip { border: 1px solid #ddd; border-left: 4px solid #c62828; border-radius: 6px; background: #fff; padding: 10px; }
.district-chip strong { display: block; color: #2f343b; }
.district-chip span { display: block; color: #666; font-size: 12px; margin-top: 3px; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 9px; border: 1px solid #ddd; text-align: left; vertical-align: middle; }
th { background: #f5f5f5; }
.group-row td { background: #eef2f6; color: #2f343b; font-weight: bold; }
.muted { color: #666; font-size: 12px; font-weight: normal; }
.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.delete-form { margin: 0; }
@media (max-width: 900px) {
    .toolbar { align-items: stretch; flex-direction: column; }
    .filters, .form-grid { grid-template-columns: 1fr; }
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
        <li>
            <a href="#" class="submenu-toggle">Mantenimiento &#9662;</a>
            <ul class="submenu open">
                <li><a href="mantenimiento_centros.php">Centros Poblados</a></li>
                <li><a href="mantenimiento_municipalidades.php">Municipalidades</a></li>
                <li><a href="mantenimiento_jass.php">JASS</a></li>
                <li><a href="mantenimiento_sistemas_agua.php">Sistemas de Agua</a></li>
                <li><a href="mantenimiento_ficha.php">Ficha Cloracion</a></li>
            </ul>
        </li>
        <li><a href="logout.php">Cerrar sesion</a></li>
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
            <h2>Centros Poblados</h2>
            <form class="filters" method="get">
                <input type="text" name="buscar" value="<?php echo e($busqueda); ?>" placeholder="Buscar centro poblado, distrito o provincia">
                <select name="distrito_id">
                    <option value="0">Todos los distritos</option>
                    <?php mysqli_data_seek($distritos, 0); ?>
                    <?php while ($d = $distritos->fetch_assoc()): ?>
                        <option value="<?php echo e($d['id']); ?>" <?php echo $filtroDistrito === (int) $d['id'] ? 'selected' : ''; ?>>
                            <?php echo e($d['nombre'] . ' - ' . $d['provincia']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Buscar</button>
                <?php if ($busqueda !== '' || $filtroDistrito > 0): ?>
                    <a class="button light" href="mantenimiento_centros.php">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="district-summary">
            <?php mysqli_data_seek($distritos, 0); ?>
            <?php while ($d = $distritos->fetch_assoc()): ?>
                <div class="district-chip">
                    <strong><?php echo e($d['nombre']); ?></strong>
                    <span><?php echo e($d['provincia'] . ', ' . $d['departamento']); ?></span>
                    <span><?php echo e($d['total_centros']); ?> centro(s) poblado(s)</span>
                </div>
            <?php endwhile; ?>
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
                    Centro poblado
                    <input type="text" name="nombre" value="<?php echo e($editando['nombre'] ?? ''); ?>" required>
                </label>
                <label>
                    Distrito
                    <select name="distrito_id" required>
                        <option value="">Seleccionar distrito</option>
                        <?php mysqli_data_seek($distritos, 0); ?>
                        <?php while ($d = $distritos->fetch_assoc()): ?>
                            <option value="<?php echo e($d['id']); ?>" <?php echo (int) ($editando['distrito_id'] ?? 0) === (int) $d['id'] ? 'selected' : ''; ?>>
                                <?php echo e($d['nombre'] . ' - ' . $d['provincia'] . ', ' . $d['departamento']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit"><?php echo $editando ? 'Actualizar' : 'Registrar'; ?></button>
                <?php if ($editando): ?>
                    <a class="button secondary" href="mantenimiento_centros.php">Cancelar edicion</a>
                <?php endif; ?>
            </div>
        </form>

        <br>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Centro poblado</th>
                    <th>Provincia</th>
                    <th>Departamento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($centros->num_rows === 0): ?>
                    <tr>
                        <td colspan="5">No hay centros poblados registrados.</td>
                    </tr>
                <?php endif; ?>

                <?php $grupoActual = null; ?>
                <?php while ($row = $centros->fetch_assoc()): ?>
                    <?php
                    $grupo = ($row['departamento'] ?? '') . '|' . ($row['provincia'] ?? '') . '|' . ($row['distrito'] ?? '');
                    if ($grupo !== $grupoActual):
                        $grupoActual = $grupo;
                    ?>
                        <tr class="group-row">
                            <td colspan="5">
                                Distrito: <?php echo e($row['distrito']); ?>
                                <span class="muted">
                                    <?php echo e($row['provincia'] . ', ' . $row['departamento']); ?> -
                                    <?php echo e($row['total_distrito']); ?> centro(s)
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['nombre']); ?></td>
                        <td><?php echo e($row['provincia']); ?></td>
                        <td><?php echo e($row['departamento']); ?></td>
                        <td>
                            <div class="actions">
                                <a class="button light" href="detalle_centro.php?id=<?php echo e($row['id']); ?>">Ver detalle</a>
                                <a class="button light" href="cuestionario_centro.php?id=<?php echo e($row['id']); ?>">Cuestionario</a>
                                <a class="button light" href="mantenimiento_centros.php?editar=<?php echo e($row['id']); ?>">Editar</a>
                                <form class="delete-form" method="post" onsubmit="return confirm('Eliminar este centro poblado?');">
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
