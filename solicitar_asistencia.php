<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_any_role(['Administrador', 'Municipalidad']);

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } else {
        $centro_poblado_id = (int) ($_POST['centro_poblado_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_solicitud = date('Y-m-d');
        $usuario_id = (int) $_SESSION['id'];

        if ($centro_poblado_id <= 0 || $motivo === '') {
            $error = "Selecciona un centro poblado e ingresa el motivo de la asistencia.";
        } else {
            $stmtCentro = $conn->prepare("SELECT id FROM centros_poblados WHERE id = ?");
            $stmtCentro->bind_param("i", $centro_poblado_id);
            $stmtCentro->execute();
            $centroExiste = $stmtCentro->get_result()->num_rows > 0;

            if (!$centroExiste) {
                $error = "Selecciona un centro poblado válido.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO solicitudes_asistencia
                        (centro_poblado_id, usuario_id, motivo, descripcion, fecha_solicitud, estado)
                    VALUES (?, ?, ?, ?, ?, 'Pendiente')
                ");
                $stmt->bind_param("iisss", $centro_poblado_id, $usuario_id, $motivo, $descripcion, $fecha_solicitud);

                if ($stmt->execute()) {
                    $success = "Solicitud de asistencia técnica registrada correctamente.";
                } else {
                    $error = "No se pudo registrar la solicitud.";
                }
            }
        }
    }
}

$centros = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados ORDER BY nombre");

$usuario_id = (int) $_SESSION['id'];
if (has_role('Administrador')) {
    $solicitudes = $conn->query("
        SELECT s.id, s.fecha_solicitud, s.motivo, s.estado, s.fecha_atencion,
               c.nombre AS centro_poblado, u.nombre AS solicitante
        FROM solicitudes_asistencia s
        INNER JOIN centros_poblados c ON s.centro_poblado_id = c.id
        INNER JOIN usuarios u ON s.usuario_id = u.id
        ORDER BY s.id DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT s.id, s.fecha_solicitud, s.motivo, s.estado, s.fecha_atencion,
               c.nombre AS centro_poblado, u.nombre AS solicitante
        FROM solicitudes_asistencia s
        INNER JOIN centros_poblados c ON s.centro_poblado_id = c.id
        INNER JOIN usuarios u ON s.usuario_id = u.id
        WHERE s.usuario_id = ?
        ORDER BY s.id DESC
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $solicitudes = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Solicitar Asistencia Técnica</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
label { display: flex; flex-direction: column; gap: 6px; color: #333; font-size: 14px; }
select, textarea { padding: 9px; border: 1px solid #ccc; border-radius: 5px; font-family: Arial, sans-serif; }
textarea { min-height: 88px; resize: vertical; }
.full { grid-column: 1 / -1; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 18px; }
th, td { padding: 9px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
th { background: #f5f5f5; }
.status { font-weight: bold; }
@media (max-width: 800px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <?php if (has_any_role(['Administrador', 'Municipalidad'])): ?>
            <li><a href="solicitar_asistencia.php">Solicitar Asistencia</a></li>
        <?php endif; ?>
        <?php if (has_any_role(['Administrador', 'Gobierno Regional'])): ?>
            <li><a href="gestionar_asistencia.php">Gestionar Asistencia</a></li>
        <?php endif; ?>
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
        <div class="page-title">
            <h2>Solicitud de Asistencia Técnica</h2>
            <a class="button" href="dashboard.php">Volver</a>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <div class="form-grid">
                <label class="full">
                    Centro poblado
                    <select name="centro_poblado_id" required>
                        <option value="">Seleccionar centro poblado</option>
                        <?php while ($centro = $centros->fetch_assoc()): ?>
                            <option value="<?php echo e($centro['id']); ?>">
                                <?php echo e($centro['nombre'] . ' - ' . $centro['distrito'] . ', ' . $centro['provincia']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
                <label class="full">
                    Motivo
                    <textarea name="motivo" required placeholder="Describe el motivo principal de la asistencia técnica"></textarea>
                </label>
                <label class="full">
                    Descripción adicional
                    <textarea name="descripcion" placeholder="Agrega detalles, ubicación o información relevante"></textarea>
                </label>
            </div>
            <button type="submit">Enviar solicitud</button>
        </form>

        <h3>Mis solicitudes</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Centro poblado</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Fecha atención</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($solicitudes->num_rows === 0): ?>
                    <tr><td colspan="6">No hay solicitudes registradas.</td></tr>
                <?php endif; ?>
                <?php while ($row = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['fecha_solicitud']); ?></td>
                        <td><?php echo e($row['centro_poblado']); ?></td>
                        <td><?php echo e($row['motivo']); ?></td>
                        <td class="status"><?php echo e($row['estado']); ?></td>
                        <td><?php echo e($row['fecha_atencion'] ?? ''); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
