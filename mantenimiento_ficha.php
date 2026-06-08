<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_login();

$error = '';
$success = '';
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fichas';
$uploadUrl = 'uploads/fichas/';
$maxPdfSize = 10 * 1024 * 1024;

function post_value($name) {
    $value = trim($_POST[$name] ?? '');
    return $value === '' ? null : $value;
}

function bind_all($stmt, $types, &$params) {
    $refs = [];
    foreach ($params as $key => &$value) {
        $refs[$key] = &$value;
    }

    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

function validate_pdf_upload($file, $maxPdfSize) {
    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, "No se pudo cargar el archivo PDF."];
    }

    if ($file['size'] > $maxPdfSize) {
        return [false, "El PDF no debe superar los 10 MB."];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return [false, "Solo se permite subir archivos PDF."];
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
            return [false, "El archivo seleccionado no parece ser un PDF válido."];
        }
    }

    return [true, null];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } else {
        [$pdfValido, $pdfError] = validate_pdf_upload($_FILES['ficha_pdf'] ?? null, $maxPdfSize);

        if (!$pdfValido) {
            $error = $pdfError;
        }
    }

    if (!$error) {
        $localidad_anexo = post_value('localidad_anexo');
        $centro_poblado_id = ($_POST['centro_poblado_id'] ?? '') === '' ? null : (int) $_POST['centro_poblado_id'];
        $distrito = post_value('distrito');
        $provincia = post_value('provincia');
        $departamento = post_value('departamento');
        $establecimiento_salud = post_value('establecimiento_salud');
        $fecha_registro = post_value('fecha_registro');
        $municipalidad = post_value('municipalidad');
        $jass = post_value('jass');
        $tipo_sistema_agua = post_value('tipo_sistema_agua');
        $tipo_bombeo = post_value('tipo_bombeo');
        $nombre_fuente_captacion = post_value('nombre_fuente_captacion');
        $reservorio_1_fecha = post_value('reservorio_1_fecha');
        $reservorio_1_hora = post_value('reservorio_1_hora');
        $reservorio_1_valor = post_value('reservorio_1_valor');
        $reservorio_2_fecha = post_value('reservorio_2_fecha');
        $reservorio_2_hora = post_value('reservorio_2_hora');
        $reservorio_2_valor = post_value('reservorio_2_valor');
        $observacion_1 = post_value('observacion_1');
        $observacion_2 = post_value('observacion_2');
        $representante_oc = post_value('representante_oc');
        $responsable_area_tecnica = post_value('responsable_area_tecnica');
        $representante_drvcs_grvcs = post_value('representante_drvcs_grvcs');

        $red_ubicacion = $_POST['red_ubicacion'] ?? [];
        $red_punto = $_POST['red_punto'] ?? [];
        $red_fecha = $_POST['red_fecha'] ?? [];
        $red_hora = $_POST['red_hora'] ?? [];
        $red_cloro = $_POST['red_cloro'] ?? [];
        $red_usuario = $_POST['red_usuario'] ?? [];
        $red_dni = $_POST['red_dni'] ?? [];
        $red_firma = $_POST['red_firma'] ?? [];

        if (!$localidad_anexo || !$centro_poblado_id || !$fecha_registro || !$municipalidad) {
            $error = "Completa centro poblado, localidad/anexo, fecha y municipalidad.";
        } else {
            $conn->begin_transaction();

            try {
                $ubicacion_punto = trim($red_ubicacion[0] ?? '') ?: null;
                $punto_toma = trim($red_punto[0] ?? '') ?: null;
                $fecha_muestreo = trim($red_fecha[0] ?? '') ?: null;
                $hora_muestreo = trim($red_hora[0] ?? '') ?: null;
                $cloro_residual_mgL = trim($red_cloro[0] ?? '') ?: null;
                $usuario_nombre = trim($red_usuario[0] ?? '') ?: null;
                $usuario_dni = trim($red_dni[0] ?? '') ?: null;
                $usuario_firma = isset($red_firma[0]) ? 1 : 0;

                $pdfArchivo = null;
                $pdfNombreOriginal = null;
                $hayPdf = isset($_FILES['ficha_pdf']) && $_FILES['ficha_pdf']['error'] !== UPLOAD_ERR_NO_FILE;

                $sql = "
                    INSERT INTO ficha_tecnica (
                        centro_poblado_id, localidad_anexo, distrito, provincia, departamento, establecimiento_salud,
                        fecha_registro, municipalidad, jass, tipo_sistema_agua, tipo_bombeo,
                        nombre_fuente_captacion, reservorio_1_fecha, reservorio_1_hora, reservorio_1_valor,
                        reservorio_2_fecha, reservorio_2_hora, reservorio_2_valor,
                        ubicacion_punto, punto_toma, fecha_muestreo, hora_muestreo, cloro_residual_mgL,
                        usuario_nombre, usuario_dni, usuario_firma, observacion_1, observacion_2,
                        representante_oc, responsable_area_tecnica, representante_drvcs_grvcs,
                        pdf_archivo, pdf_nombre_original
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $conn->prepare($sql);
                $params = [
                    $centro_poblado_id, $localidad_anexo, $distrito, $provincia, $departamento, $establecimiento_salud,
                    $fecha_registro, $municipalidad, $jass, $tipo_sistema_agua, $tipo_bombeo,
                    $nombre_fuente_captacion, $reservorio_1_fecha, $reservorio_1_hora, $reservorio_1_valor,
                    $reservorio_2_fecha, $reservorio_2_hora, $reservorio_2_valor,
                    $ubicacion_punto, $punto_toma, $fecha_muestreo, $hora_muestreo, $cloro_residual_mgL,
                    $usuario_nombre, $usuario_dni, $usuario_firma, $observacion_1, $observacion_2,
                    $representante_oc, $responsable_area_tecnica, $representante_drvcs_grvcs,
                    $pdfArchivo, $pdfNombreOriginal,
                ];
                bind_all($stmt, str_repeat('s', count($params)), $params);
                $stmt->execute();
                $ficha_id = $conn->insert_id;

                if ($hayPdf) {
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    $pdfNombreOriginal = basename($_FILES['ficha_pdf']['name']);
                    $pdfArchivo = 'ficha_' . $ficha_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
                    $destino = $uploadDir . DIRECTORY_SEPARATOR . $pdfArchivo;

                    if (!move_uploaded_file($_FILES['ficha_pdf']['tmp_name'], $destino)) {
                        throw new RuntimeException('No se pudo mover el PDF cargado.');
                    }

                    $stmtPdf = $conn->prepare("UPDATE ficha_tecnica SET pdf_archivo = ?, pdf_nombre_original = ? WHERE id = ?");
                    $stmtPdf->bind_param("ssi", $pdfArchivo, $pdfNombreOriginal, $ficha_id);
                    $stmtPdf->execute();
                }

                $stmtRed = $conn->prepare("
                    INSERT INTO ficha_red_distribucion (
                        ficha_id, numero, ubicacion_punto, punto_toma, fecha_muestreo,
                        hora_muestreo, cloro_residual_mgL, usuario_nombre, usuario_dni, usuario_firma
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                for ($i = 0; $i < 3; $i++) {
                    $numero = $i + 1;
                    $detalleUbicacion = trim($red_ubicacion[$i] ?? '');
                    $detallePunto = trim($red_punto[$i] ?? '');
                    $detalleFecha = trim($red_fecha[$i] ?? '');
                    $detalleHora = trim($red_hora[$i] ?? '');
                    $detalleCloro = trim($red_cloro[$i] ?? '');
                    $detalleUsuario = trim($red_usuario[$i] ?? '');
                    $detalleDni = trim($red_dni[$i] ?? '');
                    $detalleFirma = isset($red_firma[$i]) ? 1 : 0;

                    if ($detalleUbicacion === '' && $detallePunto === '' && $detalleFecha === '' && $detalleHora === '' && $detalleCloro === '' && $detalleUsuario === '' && $detalleDni === '') {
                        continue;
                    }

                    $detalleUbicacion = $detalleUbicacion ?: null;
                    $detallePunto = $detallePunto ?: null;
                    $detalleFecha = $detalleFecha ?: null;
                    $detalleHora = $detalleHora ?: null;
                    $detalleCloro = $detalleCloro ?: null;
                    $detalleUsuario = $detalleUsuario ?: null;
                    $detalleDni = $detalleDni ?: null;

                    $stmtRed->bind_param(
                        "iisssssssi",
                        $ficha_id,
                        $numero,
                        $detalleUbicacion,
                        $detallePunto,
                        $detalleFecha,
                        $detalleHora,
                        $detalleCloro,
                        $detalleUsuario,
                        $detalleDni,
                        $detalleFirma
                    );
                    $stmtRed->execute();
                }

                $conn->commit();
                $success = "Ficha técnica registrada correctamente.";
            } catch (Throwable $e) {
                $conn->rollback();
                $error = "No se pudo guardar la ficha técnica.";
            }
        }
    }
}

$fichas = $conn->query("
    SELECT f.id, f.localidad_anexo, f.distrito, f.provincia, f.departamento, f.fecha_registro,
           f.municipalidad, f.jass, f.cloro_residual_mgL, f.usuario_nombre, f.pdf_archivo,
           c.nombre AS centro_poblado
    FROM ficha_tecnica f
    LEFT JOIN centros_poblados c ON f.centro_poblado_id = c.id
    ORDER BY f.fecha_registro DESC, f.id DESC
");
$centros = $conn->query("SELECT id, nombre, distrito, provincia FROM centros_poblados ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento de Ficha Técnica</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
.section-title { margin-top: 24px; padding: 8px 10px; background: #f5f5f5; border-left: 4px solid #c62828; font-size: 16px; }
.form-grid { display: grid; grid-template-columns: repeat(4, minmax(130px, 1fr)); gap: 12px; margin-bottom: 14px; }
label { display: flex; flex-direction: column; gap: 6px; color: #333; font-size: 14px; }
input, select, textarea { padding: 9px; border: 1px solid #ccc; border-radius: 5px; font-family: Arial, sans-serif; }
input[type="file"] { background: #fff; }
textarea { min-height: 70px; resize: vertical; }
.full { grid-column: 1 / -1; }
.span-2 { grid-column: span 2; }
.hint { color: #666; font-size: 12px; }
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 14px; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
th { background: #f5f5f5; }
.mini-input { min-width: 105px; width: 100%; box-sizing: border-box; }
.firma-cell { text-align: center; }
@media (max-width: 1000px) {
    .form-grid { grid-template-columns: 1fr; }
    .span-2 { grid-column: auto; }
}
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
        <div class="page-title">
            <h2>Formato de Reporte de Control de Cloro</h2>
            <a class="button light" href="dashboard.php">Volver</a>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <h3 class="section-title">I. Ubicación</h3>
            <div class="form-grid">
                <label class="span-2">Centro poblado
                    <select name="centro_poblado_id" required>
                        <option value="">Seleccionar centro poblado</option>
                        <?php while ($centro = $centros->fetch_assoc()): ?>
                            <option value="<?php echo e($centro['id']); ?>">
                                <?php echo e($centro['nombre'] . ' - ' . $centro['distrito'] . ', ' . $centro['provincia']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
                <label class="span-2">Localidad / Anexo
                    <input type="text" name="localidad_anexo" required>
                </label>
                <label>Fecha
                    <input type="date" name="fecha_registro" required>
                </label>
                <label>Distrito
                    <input type="text" name="distrito">
                </label>
                <label>Provincia
                    <input type="text" name="provincia">
                </label>
                <label>Departamento
                    <input type="text" name="departamento">
                </label>
                <label class="span-2">Establecimiento de salud
                    <input type="text" name="establecimiento_salud">
                </label>
            </div>

            <h3 class="section-title">II. Sistema de abastecimiento de agua para consumo humano</h3>
            <div class="form-grid">
                <label class="span-2">Administrador del sistema / Municipalidad
                    <input type="text" name="municipalidad" required>
                </label>
                <label class="span-2">JASS
                    <input type="text" name="jass">
                </label>
                <label class="span-2">Tipo de abastecimiento de agua
                    <select name="tipo_sistema_agua">
                        <option value="">Seleccionar</option>
                        <option value="Gravedad simple">Gravedad simple</option>
                        <option value="Gravedad con tratamiento">Gravedad con tratamiento</option>
                        <option value="Bombeo sin tratamiento">Bombeo sin tratamiento</option>
                        <option value="Bombeo con tratamiento">Bombeo con tratamiento</option>
                    </select>
                </label>
                <label>Tipo de bombeo
                    <select name="tipo_bombeo">
                        <option value="">Seleccionar</option>
                        <option value="Red">Red</option>
                        <option value="Reservorio">Reservorio</option>
                        <option value="Pozo">Pozo</option>
                        <option value="Otro">Otro</option>
                    </select>
                </label>
                <label>Fuente principal / captación
                    <input type="text" name="nombre_fuente_captacion">
                </label>
            </div>

            <h3 class="section-title">III. Medición del cloro residual</h3>
            <h4>3.1 Planta de tratamiento / Reservorio</h4>
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Punto de toma de muestra</th>
                        <th>Fecha muestreo</th>
                        <th>Hora</th>
                        <th>Cloro residual (mg/L)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Reservorio - 1</td>
                        <td><input class="mini-input" type="date" name="reservorio_1_fecha"></td>
                        <td><input class="mini-input" type="time" name="reservorio_1_hora"></td>
                        <td><input class="mini-input" type="number" step="0.01" min="0" name="reservorio_1_valor"></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Reservorio - 2</td>
                        <td><input class="mini-input" type="date" name="reservorio_2_fecha"></td>
                        <td><input class="mini-input" type="time" name="reservorio_2_hora"></td>
                        <td><input class="mini-input" type="number" step="0.01" min="0" name="reservorio_2_valor"></td>
                    </tr>
                </tbody>
            </table>

            <h4>3.2 Red de distribución</h4>
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Ubicación del punto</th>
                        <th>Punto de toma</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Cloro residual</th>
                        <th>Usuario</th>
                        <th>DNI</th>
                        <th>Firma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <tr>
                            <td><?php echo e($i + 1); ?></td>
                            <td><input class="mini-input" type="text" name="red_ubicacion[]" placeholder="1er nivel, intermedia, última"></td>
                            <td><input class="mini-input" type="text" name="red_punto[]" placeholder="Red"></td>
                            <td><input class="mini-input" type="date" name="red_fecha[]"></td>
                            <td><input class="mini-input" type="time" name="red_hora[]"></td>
                            <td><input class="mini-input" type="number" step="0.01" min="0" name="red_cloro[]"></td>
                            <td><input class="mini-input" type="text" name="red_usuario[]"></td>
                            <td><input class="mini-input" type="text" name="red_dni[]"></td>
                            <td class="firma-cell"><input type="checkbox" name="red_firma[<?php echo e($i); ?>]" value="1"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <h3 class="section-title">IV. Observaciones y responsables</h3>
            <div class="form-grid">
                <label class="full">Observación 1
                    <textarea name="observacion_1"></textarea>
                </label>
                <label class="full">Observación 2
                    <textarea name="observacion_2"></textarea>
                </label>
                <label>Representante de OC
                    <input type="text" name="representante_oc">
                </label>
                <label>Responsable del área técnica municipal
                    <input type="text" name="responsable_area_tecnica">
                </label>
                <label>Representante DRVCS/GRVCS
                    <input type="text" name="representante_drvcs_grvcs">
                </label>
            </div>

            <h3 class="section-title">Archivo PDF</h3>
            <div class="form-grid">
                <label class="full">Ficha escaneada en PDF
                    <input type="file" name="ficha_pdf" accept="application/pdf,.pdf">
                    <span class="hint">Opcional. Tamaño máximo: 10 MB.</span>
                </label>
            </div>

            <button type="submit">Guardar ficha técnica</button>
        </form>

        <h3 class="section-title">Registros cargados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Localidad</th>
                    <th>Centro poblado</th>
                    <th>Distrito</th>
                    <th>Municipalidad</th>
                    <th>JASS</th>
                    <th>Cloro red</th>
                    <th>Usuario</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($fichas->num_rows === 0): ?>
                    <tr><td colspan="10">No hay fichas técnicas registradas.</td></tr>
                <?php endif; ?>
                <?php while ($row = $fichas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['id']); ?></td>
                        <td><?php echo e($row['fecha_registro']); ?></td>
                        <td><?php echo e($row['localidad_anexo']); ?></td>
                        <td><?php echo e($row['centro_poblado']); ?></td>
                        <td><?php echo e($row['distrito']); ?></td>
                        <td><?php echo e($row['municipalidad']); ?></td>
                        <td><?php echo e($row['jass']); ?></td>
                        <td><?php echo e($row['cloro_residual_mgL']); ?></td>
                        <td><?php echo e($row['usuario_nombre']); ?></td>
                        <td>
                            <?php if (!empty($row['pdf_archivo'])): ?>
                                <a href="<?php echo e($uploadUrl . $row['pdf_archivo']); ?>" target="_blank">Ver PDF</a>
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
