<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_any_role(['Administrador', 'Gobierno Regional']);

$error = '';
$success = '';
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fichas';
$uploadUrl = 'uploads/fichas/';
$uploadAdjuntosDir = $uploadDir . DIRECTORY_SEPARATOR . 'adjuntos';
$uploadAdjuntosUrl = $uploadUrl . 'adjuntos/';
$maxPdfSize = 10 * 1024 * 1024;

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

function collect_upload_items($file, $categoria) {
    $items = [];

    if (!isset($file['name'])) {
        return $items;
    }

    if (is_array($file['name'])) {
        foreach ($file['name'] as $index => $name) {
            if (($file['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $items[] = [
                'name' => $name,
                'type' => $file['type'][$index] ?? '',
                'tmp_name' => $file['tmp_name'][$index] ?? '',
                'error' => $file['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file['size'][$index] ?? 0,
                'categoria' => $categoria,
            ];
        }

        return $items;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file['categoria'] = $categoria;
        $items[] = $file;
    }

    return $items;
}

function validate_attachment_uploads($items, $maxSize) {
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowedMimes = ['application/pdf', 'application/x-pdf', 'image/jpeg', 'image/png'];

    foreach ($items as $item) {
        if (($item['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [false, "No se pudo cargar uno de los archivos adjuntos."];
        }

        if (($item['size'] ?? 0) > $maxSize) {
            return [false, "Cada archivo adjunto debe pesar como maximo 10 MB."];
        }

        $extension = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            return [false, "Solo se permiten adjuntos PDF, JPG, JPEG o PNG."];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $item['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes, true)) {
                return [false, "Uno de los adjuntos no tiene un formato valido."];
            }
        }
    }

    return [true, null];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $adjuntosSubidos = array_merge(
        collect_upload_items($_FILES['fotos_evidencia'] ?? null, 'Evidencia fotografica'),
        collect_upload_items($_FILES['ficha_formato'] ?? null, 'Formato monitoreo cloro residual')
    );

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } else {
        [$adjuntosValidos, $adjuntosError] = validate_attachment_uploads($adjuntosSubidos, $maxPdfSize);

        if (!$adjuntosValidos) {
            $error = $adjuntosError;
        }
    }

    if (!$error) {
        $centro_poblado_id = ($_POST['centro_poblado_id'] ?? '') === '' ? null : (int) $_POST['centro_poblado_id'];
        $jass_id = ($_POST['jass_id'] ?? '') === '' ? null : (int) $_POST['jass_id'];
        $distrito = null;
        $provincia = null;
        $departamento = null;
        $establecimiento_salud = post_value('establecimiento_salud');
        $fecha_registro = post_value('fecha_registro');
        $municipalidad = post_value('municipalidad');
        $jass = null;
        $tipo_sistema_agua = post_value('tipo_sistema_agua');
        $tipo_bombeo = post_value('tipo_bombeo');
        $nombre_fuente_captacion = post_value('nombre_fuente_captacion');
        $reservorio_1_fecha = null;
        $reservorio_1_hora = null;
        $reservorio_1_valor = null;
        $reservorio_2_fecha = null;
        $reservorio_2_hora = null;
        $reservorio_2_valor = null;
        $observacion_1 = post_value('observacion_1');
        $observacion_2 = post_value('observacion_2');
        $representante_oc = post_value('representante_oc');
        $responsable_area_tecnica = post_value('responsable_area_tecnica');
        $representante_drvcs_grvcs = post_value('representante_drvcs_grvcs');

        $punto_tipo = $_POST['punto_tipo'] ?? [];
        $punto_fecha = $_POST['punto_fecha'] ?? [];
        $punto_hora = $_POST['punto_hora'] ?? [];
        $punto_cloro = $_POST['punto_cloro'] ?? [];
        $punto_dni = $_POST['punto_dni'] ?? [];
        $punto_usuario = $_POST['punto_usuario'] ?? [];

        if (!$centro_poblado_id || !$fecha_registro || !$municipalidad) {
            $error = "Completa centro poblado, fecha y municipalidad.";
        } else {
            $stmtCentro = $conn->prepare("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados WHERE id = ?");
            $stmtCentro->bind_param("i", $centro_poblado_id);
            $stmtCentro->execute();
            $centroSeleccionado = $stmtCentro->get_result()->fetch_assoc();

            if (!$centroSeleccionado) {
                $error = "Selecciona un centro poblado valido.";
            }
        }

        if (!$error && $jass_id) {
            $stmtJass = $conn->prepare("SELECT nombre FROM jass WHERE id = ? AND centro_poblado_id = ?");
            $stmtJass->bind_param("ii", $jass_id, $centro_poblado_id);
            $stmtJass->execute();
            $jassSeleccionada = $stmtJass->get_result()->fetch_assoc();

            if (!$jassSeleccionada) {
                $error = "Selecciona una JASS registrada para el centro poblado elegido.";
            } else {
                $jass = $jassSeleccionada['nombre'];
            }
        }

        if (!$error) {
            $localidad_anexo = $centroSeleccionado['nombre'] ?? null;
            $distrito = $centroSeleccionado['distrito'];
            $provincia = $centroSeleccionado['provincia'];
            $departamento = $centroSeleccionado['departamento'];

            $conn->begin_transaction();

            try {
                $archivosGuardados = [];
                $puntosBase = ['Reservorio', 'Primera Vivienda', 'Vivienda Intermedia', 'Ultima Vivienda'];
                $ubicacion_punto = trim($punto_tipo[0] ?? $puntosBase[0]) ?: $puntosBase[0];
                $punto_toma = $ubicacion_punto;
                $fecha_muestreo = trim($punto_fecha[0] ?? '') ?: null;
                $hora_muestreo = trim($punto_hora[0] ?? '') ?: null;
                $cloro_residual_mgL = trim($punto_cloro[0] ?? '') ?: null;
                $usuario_nombre = $_SESSION['nombre'] ?? null;
                $usuario_dni = null;
                $usuario_firma = 0;
                $reservorio_1_fecha = $fecha_muestreo;
                $reservorio_1_hora = $hora_muestreo;
                $reservorio_1_valor = $cloro_residual_mgL;

                $pdfArchivo = null;
                $pdfNombreOriginal = null;

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

                $stmtRed = $conn->prepare("
                    INSERT INTO ficha_red_distribucion (
                        ficha_id, numero, ubicacion_punto, punto_toma, fecha_muestreo,
                        hora_muestreo, cloro_residual_mgL, usuario_nombre, usuario_dni, usuario_firma
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                for ($i = 0; $i < 4; $i++) {
                    $numero = $i + 1;
                    $detallePunto = trim($punto_tipo[$i] ?? $puntosBase[$i]) ?: $puntosBase[$i];
                    $detalleFecha = trim($punto_fecha[$i] ?? '');
                    $detalleHora = trim($punto_hora[$i] ?? '');
                    $detalleCloro = trim($punto_cloro[$i] ?? '');
                    $detalleUsuario = trim($punto_usuario[$i] ?? '');
                    $detalleDni = trim($punto_dni[$i] ?? '');
                    $detalleFirma = 0;

                    if ($detalleFecha === '' && $detalleHora === '' && $detalleCloro === '' && $detalleUsuario === '' && $detalleDni === '') {
                        continue;
                    }

                    $detalleFecha = $detalleFecha ?: null;
                    $detalleHora = $detalleHora ?: null;
                    $detalleCloro = $detalleCloro ?: null;
                    $detalleUsuario = $detalleUsuario ?: null;
                    $detalleDni = $detalleDni ?: null;

                    $stmtRed->bind_param(
                        "iisssssssi",
                        $ficha_id,
                        $numero,
                        $detallePunto,
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

                if ($adjuntosSubidos) {
                    if (!is_dir($uploadAdjuntosDir)) {
                        mkdir($uploadAdjuntosDir, 0775, true);
                    }

                    $stmtAdjunto = $conn->prepare("
                        INSERT INTO ficha_adjuntos
                            (ficha_id, archivo, nombre_original, tipo_mime, tamano, categoria)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    foreach ($adjuntosSubidos as $item) {
                        $extension = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
                        $nombreArchivo = 'adjunto_' . $ficha_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $destino = $uploadAdjuntosDir . DIRECTORY_SEPARATOR . $nombreArchivo;

                        if (!move_uploaded_file($item['tmp_name'], $destino)) {
                            throw new RuntimeException('No se pudo mover un archivo adjunto.');
                        }

                        $archivosGuardados[] = $destino;
                        $nombreOriginal = basename($item['name']);
                        $tipoMime = $item['type'] ?? null;
                        $tamano = (int) ($item['size'] ?? 0);
                        $categoria = $item['categoria'] ?? 'Adjunto';

                        $stmtAdjunto->bind_param(
                            "isssis",
                            $ficha_id,
                            $nombreArchivo,
                            $nombreOriginal,
                            $tipoMime,
                            $tamano,
                            $categoria
                        );
                        $stmtAdjunto->execute();

                        if ($pdfArchivo === null && $categoria === 'Formato monitoreo cloro residual') {
                            $pdfArchivo = $nombreArchivo;
                            $pdfNombreOriginal = $nombreOriginal;
                        }
                    }
                }

                if ($pdfArchivo !== null) {
                    $stmtPdf = $conn->prepare("UPDATE ficha_tecnica SET pdf_archivo = ?, pdf_nombre_original = ? WHERE id = ?");
                    $stmtPdf->bind_param("ssi", $pdfArchivo, $pdfNombreOriginal, $ficha_id);
                    $stmtPdf->execute();
                }

                $conn->commit();
                $success = "Ficha técnica registrada correctamente.";
            } catch (Throwable $e) {
                $conn->rollback();
                foreach ($archivosGuardados ?? [] as $archivoGuardado) {
                    if (is_file($archivoGuardado)) {
                        unlink($archivoGuardado);
                    }
                }
                $error = "No se pudo guardar la ficha técnica.";
            }
        }
    }
}

$fichas = $conn->query("
    SELECT f.id, f.localidad_anexo, f.distrito, f.provincia, f.departamento, f.fecha_registro,
           f.municipalidad, f.jass, f.cloro_residual_mgL, f.usuario_nombre, f.pdf_archivo,
           c.nombre AS centro_poblado,
           COUNT(a.id) AS total_adjuntos,
           GROUP_CONCAT(CONCAT(a.archivo, '|||', a.nombre_original, '|||', COALESCE(a.categoria, 'Adjunto')) SEPARATOR '###') AS adjuntos_lista
    FROM ficha_tecnica f
    LEFT JOIN centros_poblados c ON f.centro_poblado_id = c.id
    LEFT JOIN ficha_adjuntos a ON a.ficha_id = f.id
    GROUP BY f.id, f.localidad_anexo, f.distrito, f.provincia, f.departamento, f.fecha_registro,
             f.municipalidad, f.jass, f.cloro_residual_mgL, f.usuario_nombre, f.pdf_archivo,
             c.nombre
    ORDER BY f.fecha_registro DESC, f.id DESC
");
$centros = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados ORDER BY nombre");
$jassRegistradas = $conn->query("
    SELECT id, nombre, centro_poblado_id
    FROM jass
    ORDER BY nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento de Ficha Técnica</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.cloro-shell { background: #eef3f8; margin: -20px -20px 22px; padding: 28px 24px 34px; border-bottom: 1px solid #dfe5ec; }
.cloro-title { margin: 0 0 18px; color: #303236; font-size: 34px; font-weight: 500; letter-spacing: 0; }
.cloro-subtitle { border-top: 1px solid #d8dde4; padding-top: 14px; color: #666; font-size: 16px; font-weight: 700; }
.cloro-meta { display: grid; grid-template-columns: repeat(4, minmax(160px, 1fr)); gap: 22px; margin-top: 30px; }
.cloro-meta span { display: block; color: #858585; font-size: 14px; margin-bottom: 8px; }
.cloro-meta strong { display: block; color: #666; font-size: 18px; line-height: 1.25; }
.form-card { background: #fff; border: 1px solid #dfe5ec; border-radius: 16px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 18px; }
.button, button { padding: 9px 14px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
.section-title { margin: 0 0 18px; font-size: 20px; color: #2f343b; }
.form-grid { display: grid; grid-template-columns: repeat(4, minmax(130px, 1fr)); gap: 12px; margin-bottom: 14px; }
label { display: flex; flex-direction: column; gap: 6px; color: #333; font-size: 14px; }
input, select, textarea { padding: 9px; border: 1px solid #ccc; border-radius: 5px; font-family: Arial, sans-serif; }
input[type="file"] { background: #fff; }
textarea { min-height: 70px; resize: vertical; }
.full { grid-column: 1 / -1; }
.span-2 { grid-column: span 2; }
.hint { color: #666; font-size: 12px; }
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 14px; }
th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
th { color: #607889; font-size: 13px; text-transform: uppercase; }
.sample-table input { min-width: 0; }
.attachment-list { margin: 10px 0 0; padding-left: 18px; }
.attachment-list li { margin-bottom: 5px; }
.attachment-list small { color: #777; display: block; }
.mini-input { min-width: 105px; width: 100%; box-sizing: border-box; }
.firma-cell { text-align: center; }
@media (max-width: 1000px) {
    .form-grid { grid-template-columns: 1fr; }
    .span-2 { grid-column: auto; }
    .cloro-meta { grid-template-columns: 1fr; }
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

            <div class="cloro-shell">
                <h1 class="cloro-title">CLORO RESIDUAL</h1>
                <div class="cloro-subtitle">SGMCR / Nueva ficha de cloracion</div>
                <div class="cloro-meta">
                    <div><span>Departamento</span><strong id="headerDepartamento">-</strong></div>
                    <div><span>Provincia</span><strong id="headerProvincia">-</strong></div>
                    <div><span>Distrito</span><strong id="headerDistrito">-</strong></div>
                    <div><span>Centro Poblado</span><strong id="headerCentro">Seleccione un centro poblado</strong></div>
                </div>
            </div>

            <h3 class="section-title">I. Ubicación</h3>
            <div class="form-grid">
                <label class="span-2">Centro poblado
                    <select name="centro_poblado_id" id="centro_poblado_id" required>
                        <option value="">Seleccionar centro poblado</option>
                        <?php while ($centro = $centros->fetch_assoc()): ?>
                            <option
                                value="<?php echo e($centro['id']); ?>"
                                data-nombre="<?php echo e($centro['nombre']); ?>"
                                data-distrito="<?php echo e($centro['distrito']); ?>"
                                data-provincia="<?php echo e($centro['provincia']); ?>"
                                data-departamento="<?php echo e($centro['departamento']); ?>"
                            >
                                <?php echo e($centro['nombre'] . ' - ' . $centro['distrito'] . ', ' . $centro['provincia']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
                <label>Fecha
                    <input type="date" name="fecha_registro" required>
                </label>
                <label>Distrito
                    <input type="text" name="distrito" id="distrito" readonly>
                </label>
                <label>Provincia
                    <input type="text" name="provincia" id="provincia" readonly>
                </label>
                <label>Departamento
                    <input type="text" name="departamento" id="departamento" readonly>
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
                    <select name="jass_id" id="jass_id">
                        <option value="">Sin JASS vinculada</option>
                        <?php while ($jass = $jassRegistradas->fetch_assoc()): ?>
                            <option value="<?php echo e($jass['id']); ?>" data-centro="<?php echo e($jass['centro_poblado_id']); ?>">
                                <?php echo e($jass['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
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
            <div class="form-card">
                <h3 class="section-title">3. Reporte de control de cloro residual de los sistemas de abastecimiento visados por el sector salud</h3>
                <table class="sample-table">
                    <thead>
                        <tr>
                            <th>Punto de toma de la muestra</th>
                            <th>Fecha y hora del muestreo</th>
                            <th>Cloro residual (mg/L)</th>
                            <th>DNI del titular de la vivienda (opcional)</th>
                            <th>Nombres del titular de la vivienda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $puntos = ['Reservorio', 'Primera Vivienda', 'Vivienda Intermedia', 'Ultima Vivienda'];
                        foreach ($puntos as $punto):
                            $esReservorio = $punto === 'Reservorio';
                        ?>
                            <tr>
                                <td>
                                    <?php echo e($punto); ?>
                                    <input type="hidden" name="punto_tipo[]" value="<?php echo e($punto); ?>">
                                </td>
                                <td>
                                    <input class="mini-input" type="date" name="punto_fecha[]">
                                    <input class="mini-input" type="time" name="punto_hora[]">
                                </td>
                                <td><input class="mini-input" type="number" step="0.01" min="0" name="punto_cloro[]"></td>
                                <?php if ($esReservorio): ?>
                                    <td><input type="hidden" name="punto_dni[]" value=""></td>
                                    <td><input type="hidden" name="punto_usuario[]" value=""></td>
                                <?php else: ?>
                                    <td><input class="mini-input" type="text" name="punto_dni[]"></td>
                                    <td><input class="mini-input" type="text" name="punto_usuario[]"></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:none">
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

            </div>

            <div class="form-card">
                <h3 class="section-title">4. Evidencias fotograficas</h3>
                <div class="form-grid">
                    <label class="full">Fotografias de evidencia
                        <input type="file" name="fotos_evidencia[]" accept="image/jpeg,image/png,.jpg,.jpeg,.png" multiple>
                        <span class="hint">Puedes adjuntar varias fotos JPG, JPEG o PNG. Tamano maximo por archivo: 10 MB.</span>
                    </label>
                    <label class="full">Ficha de cloracion o formato escaneado
                        <input type="file" name="ficha_formato" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png">
                        <span class="hint">Acepta PDF o imagen. Se listara junto con las evidencias.</span>
                    </label>
                </div>
            </div>

            <div class="form-card">
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

            </div>

            <div style="display:none">
            <h3 class="section-title">Archivo PDF</h3>
            <div class="form-grid">
                <label class="full">Ficha escaneada en PDF
                    <input type="file" name="ficha_pdf" accept="application/pdf,.pdf">
                    <span class="hint">Opcional. Tamaño máximo: 10 MB.</span>
                </label>
            </div>
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
                    <th>Adjuntos</th>
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
                            <?php if ((int) $row['total_adjuntos'] > 0): ?>
                                <strong><?php echo e($row['total_adjuntos']); ?> archivo(s)</strong>
                                <ul class="attachment-list">
                                    <?php foreach (explode('###', $row['adjuntos_lista'] ?? '') as $adjunto): ?>
                                        <?php
                                        if ($adjunto === '') {
                                            continue;
                                        }
                                        [$archivoAdjunto, $nombreAdjunto, $categoriaAdjunto] = array_pad(explode('|||', $adjunto), 3, '');
                                        ?>
                                        <li>
                                            <a href="<?php echo e($uploadAdjuntosUrl . $archivoAdjunto); ?>" target="_blank"><?php echo e($nombreAdjunto); ?></a>
                                            <small><?php echo e($categoriaAdjunto); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                Sin adjuntos
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

const centroSelect = document.getElementById('centro_poblado_id');
const jassSelect = document.getElementById('jass_id');
const ubicacionInputs = {
    distrito: document.getElementById('distrito'),
    provincia: document.getElementById('provincia'),
    departamento: document.getElementById('departamento')
};
const headerInputs = {
    distrito: document.getElementById('headerDistrito'),
    provincia: document.getElementById('headerProvincia'),
    departamento: document.getElementById('headerDepartamento'),
    centro: document.getElementById('headerCentro')
};

function actualizarUbicacionYJass() {
    const selectedOption = centroSelect.options[centroSelect.selectedIndex];
    const centroId = centroSelect.value;

    ubicacionInputs.distrito.value = selectedOption?.dataset.distrito || '';
    ubicacionInputs.provincia.value = selectedOption?.dataset.provincia || '';
    ubicacionInputs.departamento.value = selectedOption?.dataset.departamento || '';
    headerInputs.distrito.textContent = selectedOption?.dataset.distrito || '-';
    headerInputs.provincia.textContent = selectedOption?.dataset.provincia || '-';
    headerInputs.departamento.textContent = selectedOption?.dataset.departamento || '-';
    headerInputs.centro.textContent = selectedOption?.dataset.nombre || 'Seleccione un centro poblado';

    Array.from(jassSelect.options).forEach(option => {
        if (option.value === '') {
            option.hidden = false;
            option.disabled = false;
            return;
        }

        const coincideCentro = option.dataset.centro === centroId;
        option.hidden = !coincideCentro;
        option.disabled = !coincideCentro;
    });

    const seleccionActual = jassSelect.options[jassSelect.selectedIndex];
    if (seleccionActual && seleccionActual.disabled) {
        jassSelect.value = '';
    }
}

centroSelect.addEventListener('change', actualizarUbicacionYJass);
actualizarUbicacionYJass();
</script>
</body>
</html>
