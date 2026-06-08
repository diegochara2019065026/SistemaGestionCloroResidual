<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_any_role(['Administrador', 'Gobierno Regional']);

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } else {
        $solicitud_id = (int) ($_POST['solicitud_id'] ?? 0);
        $fecha_programada = $_POST['fecha_programada'] ?? '';
        $hora_programada = $_POST['hora_programada'] ?? '';
        $zona_localizacion = trim($_POST['zona_localizacion'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $tecnico_asignado = (int) $_SESSION['id'];

        if ($solicitud_id <= 0 || $fecha_programada === '' || $hora_programada === '') {
            $error = "Selecciona una solicitud e ingresa fecha y hora de programación.";
        } else {
            $stmtSolicitud = $conn->prepare("SELECT id FROM solicitudes_asistencia WHERE id = ?");
            $stmtSolicitud->bind_param("i", $solicitud_id);
            $stmtSolicitud->execute();
            $solicitudExiste = $stmtSolicitud->get_result()->num_rows > 0;

            if (!$solicitudExiste) {
                $error = "La solicitud seleccionada no existe.";
            } else {
                $conn->begin_transaction();

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO programacion_asistencia
                            (solicitud_id, fecha_programada, hora_programada, tecnico_asignado, zona_localizacion, estado, observaciones, fecha_confirmacion)
                        VALUES (?, ?, ?, ?, ?, 'Programada', ?, NOW())
                    ");
                    $stmt->bind_param("ississ", $solicitud_id, $fecha_programada, $hora_programada, $tecnico_asignado, $zona_localizacion, $observaciones);
                    $stmt->execute();

                    $stmtUpdate = $conn->prepare("
                        UPDATE solicitudes_asistencia
                        SET estado = 'En Proceso', tecnico_asignado = ?, fecha_atencion = ?, observaciones = ?
                        WHERE id = ?
                    ");
                    $stmtUpdate->bind_param("issi", $tecnico_asignado, $fecha_programada, $observaciones, $solicitud_id);
                    $stmtUpdate->execute();

                    $conn->commit();
                    $success = "Asistencia técnica aceptada y programada correctamente.";
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = "No se pudo programar la asistencia técnica.";
                }
            }
        }
    }
}

$solicitudes = $conn->query("
    SELECT s.id, s.fecha_solicitud, s.motivo, s.descripcion, s.estado, s.fecha_atencion,
           c.nombre AS centro_poblado, c.distrito, c.provincia,
           u.nombre AS solicitante, u.correo
    FROM solicitudes_asistencia s
    INNER JOIN centros_poblados c ON s.centro_poblado_id = c.id
    INNER JOIN usuarios u ON s.usuario_id = u.id
    ORDER BY FIELD(s.estado, 'Pendiente', 'En Proceso', 'Atendida'), s.id DESC
");

$programaciones = $conn->query("
    SELECT p.id, p.solicitud_id, p.fecha_programada, p.hora_programada, p.estado,
           p.zona_localizacion, p.observaciones, u.nombre AS tecnico
    FROM programacion_asistencia p
    INNER JOIN usuarios u ON p.tecnico_asignado = u.id
    ORDER BY p.fecha_programada DESC, p.hora_programada DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Asistencia Técnica</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 16px; }
th, td { padding: 9px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
th { background: #f5f5f5; }
.schedule-form { display: grid; grid-template-columns: 135px 115px 1fr; gap: 6px; min-width: 430px; }
.schedule-form input, .schedule-form textarea { padding: 7px; border: 1px solid #ccc; border-radius: 5px; font-family: Arial, sans-serif; }
.schedule-form textarea { grid-column: 1 / -1; min-height: 54px; resize: vertical; }
.schedule-form button { grid-column: 1 / -1; }
.status { font-weight: bold; }
@media (max-width: 1000px) { .schedule-form { min-width: 0; grid-template-columns: 1fr; } .schedule-form textarea, .schedule-form button { grid-column: auto; } }
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
            <h2>Gestión de Asistencia Técnica</h2>
            <a class="button light" href="dashboard.php">Volver</a>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <h3>Solicitudes recibidas</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Solicitante</th>
                    <th>Centro poblado</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Programar</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($solicitudes->num_rows === 0): ?>
                    <tr><td colspan="6">No hay solicitudes registradas.</td></tr>
                <?php endif; ?>
                <?php while ($row = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['solicitante']); ?><br><small><?php echo e($row['correo']); ?></small></td>
                        <td><?php echo e($row['centro_poblado']); ?><br><small><?php echo e($row['distrito'] . ', ' . $row['provincia']); ?></small></td>
                        <td><?php echo e($row['motivo']); ?><br><small><?php echo e($row['descripcion']); ?></small></td>
                        <td class="status"><?php echo e($row['estado']); ?></td>
                        <td>
                            <?php if ($row['estado'] !== 'Atendida'): ?>
                                <form class="schedule-form" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="solicitud_id" value="<?php echo e($row['id']); ?>">
                                    <input type="date" name="fecha_programada" value="<?php echo e($row['fecha_atencion'] ?? ''); ?>" required>
                                    <input type="time" name="hora_programada" required>
                                    <input type="text" name="zona_localizacion" placeholder="Zona/localización">
                                    <textarea name="observaciones" placeholder="Observaciones para la atención"></textarea>
                                    <button type="submit">Aceptar y agendar</button>
                                </form>
                            <?php else: ?>
                                Atendida
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Programaciones</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Solicitud</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Técnico</th>
                    <th>Estado</th>
                    <th>Zona</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($programaciones->num_rows === 0): ?>
                    <tr><td colspan="7">No hay programaciones registradas.</td></tr>
                <?php endif; ?>
                <?php while ($row = $programaciones->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['solicitud_id']); ?></td>
                        <td><?php echo e($row['fecha_programada']); ?></td>
                        <td><?php echo e($row['hora_programada']); ?></td>
                        <td><?php echo e($row['tecnico']); ?></td>
                        <td><?php echo e($row['estado']); ?></td>
                        <td><?php echo e($row['zona_localizacion']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
