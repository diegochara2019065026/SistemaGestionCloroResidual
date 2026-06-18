<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_login();

$conn->query("
    CREATE TABLE IF NOT EXISTS ficha_adjuntos (
        id INT NOT NULL AUTO_INCREMENT,
        ficha_id INT NOT NULL,
        archivo VARCHAR(255) NOT NULL,
        nombre_original VARCHAR(255) NOT NULL,
        tipo_mime VARCHAR(100) DEFAULT NULL,
        tamano INT DEFAULT 0,
        categoria VARCHAR(80) DEFAULT NULL,
        fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_ficha_adjuntos_ficha (ficha_id),
        CONSTRAINT fk_ficha_adjuntos_ficha
            FOREIGN KEY (ficha_id) REFERENCES ficha_tecnica (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$centro = $stmt->get_result()->fetch_assoc();

if (!$centro) {
    header("Location: mantenimiento_centros.php");
    exit();
}

$stmtJass = $conn->prepare("SELECT id, nombre FROM jass WHERE centro_poblado_id = ? ORDER BY nombre");
$stmtJass->bind_param("i", $id);
$stmtJass->execute();
$jass = $stmtJass->get_result();

$stmtSistemas = $conn->prepare("
    SELECT s.id, s.nombre, s.tipo_sistema, m.nombre AS municipalidad
    FROM sistemas_agua s
    LEFT JOIN municipalidades m ON s.municipalidad_id = m.id
    WHERE s.centro_poblado_id = ?
    ORDER BY s.nombre
");
$stmtSistemas->bind_param("i", $id);
$stmtSistemas->execute();
$sistemas = $stmtSistemas->get_result();

$stmtFichas = $conn->prepare("
    SELECT f.id, f.fecha_registro, f.municipalidad, f.jass, f.cloro_residual_mgL, f.usuario_nombre, f.pdf_archivo,
           (
               SELECT a.archivo
               FROM ficha_adjuntos a
               WHERE a.ficha_id = f.id
               ORDER BY FIELD(a.categoria, 'Formato monitoreo cloro residual', 'Evidencia fotografica') ASC, a.id DESC
               LIMIT 1
           ) AS adjunto_archivo
    FROM ficha_tecnica f
    WHERE f.centro_poblado_id = ? OR f.localidad_anexo = ?
    ORDER BY f.fecha_registro DESC, f.id DESC
");
$stmtFichas->bind_param("is", $id, $centro['nombre']);
$stmtFichas->execute();
$fichas = $stmtFichas->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle Centro Poblado</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.button { padding: 9px 14px; border-radius: 5px; background: #c62828; color: #fff; text-decoration: none; font-size: 14px; display: inline-block; }
.button.light { background: #eee; color: #333; }
.summary { display: grid; grid-template-columns: repeat(4, minmax(120px, 1fr)); gap: 12px; margin-bottom: 20px; }
.summary div { background: #f5f5f5; padding: 12px; border-radius: 5px; }
.summary strong { display: block; color: #555; font-size: 12px; margin-bottom: 5px; }
.section-title { margin-top: 24px; padding: 8px 10px; background: #f5f5f5; border-left: 4px solid #c62828; font-size: 16px; }
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 12px; }
th, td { padding: 9px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
@media (max-width: 900px) { .summary { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="mantenimiento_centros.php">Centros Poblados</a></li>
        <li><a href="mantenimiento_jass.php">JASS</a></li>
        <li><a href="mantenimiento_sistemas_agua.php">Sistemas de Agua</a></li>
        <li><a href="mantenimiento_ficha.php">Ficha Cloración</a></li>
        <li><a href="logout.php">Cerrar sesión</a></li>
    </ul>
</div>
<div class="main">
    <header><div class="user-info"><span><?php echo e($_SESSION['nombre']); ?></span> | <span><?php echo e($_SESSION['rol']); ?></span></div></header>
    <section class="content">
        <div class="page-title">
            <h2><?php echo e($centro['nombre']); ?></h2>
            <div>
                <a class="button" href="cuestionario_centro.php?id=<?php echo e($centro['id']); ?>">Cuestionario</a>
                <a class="button light" href="mantenimiento_centros.php">Volver</a>
            </div>
        </div>
        <div class="summary">
            <div><strong>Distrito</strong><?php echo e($centro['distrito']); ?></div>
            <div><strong>Provincia</strong><?php echo e($centro['provincia']); ?></div>
            <div><strong>Departamento</strong><?php echo e($centro['departamento']); ?></div>
            <div><strong>ID</strong><?php echo e($centro['id']); ?></div>
        </div>

        <h3 class="section-title">JASS</h3>
        <table>
            <thead><tr><th>ID</th><th>Nombre</th></tr></thead>
            <tbody>
                <?php if ($jass->num_rows === 0): ?><tr><td colspan="2">Este centro poblado no tiene JASS registrada.</td></tr><?php endif; ?>
                <?php while ($row = $jass->fetch_assoc()): ?><tr><td><?php echo e($row['id']); ?></td><td><?php echo e($row['nombre']); ?></td></tr><?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="section-title">Sistemas de Agua</h3>
        <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Municipalidad</th></tr></thead>
            <tbody>
                <?php if ($sistemas->num_rows === 0): ?><tr><td colspan="4">Este centro poblado no tiene sistemas de agua registrados.</td></tr><?php endif; ?>
                <?php while ($row = $sistemas->fetch_assoc()): ?>
                    <tr><td><?php echo e($row['id']); ?></td><td><?php echo e($row['nombre']); ?></td><td><?php echo e($row['tipo_sistema']); ?></td><td><?php echo e($row['municipalidad']); ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="section-title">Cloración</h3>
        <table>
            <thead><tr><th>ID</th><th>Fecha</th><th>Municipalidad</th><th>JASS</th><th>Cloro red</th><th>Usuario</th><th>PDF</th></tr></thead>
            <tbody>
                <?php if ($fichas->num_rows === 0): ?><tr><td colspan="7">Este centro poblado no tiene fichas de cloración registradas.</td></tr><?php endif; ?>
                <?php while ($row = $fichas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['fecha_registro']); ?></td>
                        <td><?php echo e($row['municipalidad']); ?></td>
                        <td><?php echo e($row['jass']); ?></td>
                        <td><?php echo e($row['cloro_residual_mgL']); ?></td>
                        <td><?php echo e($row['usuario_nombre']); ?></td>
                        <td>
                            <?php if (!empty($row['adjunto_archivo'])): ?>
                                <a href="uploads/fichas/adjuntos/<?php echo e($row['adjunto_archivo']); ?>" target="_blank">Vista previa</a>
                            <?php elseif (!empty($row['pdf_archivo'])): ?>
                                <a href="uploads/fichas/<?php echo e($row['pdf_archivo']); ?>" target="_blank">Ver PDF</a>
                            <?php else: ?>
                                Sin PDF
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
