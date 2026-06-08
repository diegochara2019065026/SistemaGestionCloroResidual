<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_login();

$id = (int) ($_GET['id'] ?? $_POST['centro_poblado_id'] ?? 0);
$error = '';
$success = '';

$stmtCentro = $conn->prepare("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados WHERE id = ?");
$stmtCentro->bind_param("i", $id);
$stmtCentro->execute();
$centro = $stmtCentro->get_result()->fetch_assoc();

if (!$centro) {
    header("Location: mantenimiento_centros.php");
    exit();
}

function field_value($name) {
    $value = trim($_POST[$name] ?? '');
    return $value === '' ? null : $value;
}

function int_value($name) {
    $value = trim($_POST[$name] ?? '');
    return $value === '' ? null : (int) $value;
}

function decimal_value($name) {
    $value = trim($_POST[$name] ?? '');
    return $value === '' ? null : (float) $value;
}

function json_value($value) {
    return json_encode($value, JSON_UNESCAPED_UNICODE);
}

function old($data, $key, $default = '') {
    return e($data[$key] ?? $default);
}

function checked_value($data, $name, $value) {
    return (($data[$name] ?? '') === $value) ? 'checked' : '';
}

function selected_value($data, $name, $value) {
    return (($data[$name] ?? '') === $value) ? 'selected' : '';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es válida. Recarga la página e intenta nuevamente.";
    } else {
        $entrevistados = [];
        $nombres = $_POST['entrevistado_nombre'] ?? [];
        $dniTiene = $_POST['entrevistado_tiene_dni'] ?? [];
        $dnis = $_POST['entrevistado_dni'] ?? [];
        $cargos = $_POST['entrevistado_cargo'] ?? [];
        $telefonos = $_POST['entrevistado_telefono'] ?? [];

        for ($i = 0; $i < 3; $i++) {
            if (trim($nombres[$i] ?? '') === '' && trim($dnis[$i] ?? '') === '') {
                continue;
            }
            $entrevistados[] = [
                'nombre' => trim($nombres[$i] ?? ''),
                'tiene_dni' => $dniTiene[$i] ?? '',
                'dni' => trim($dnis[$i] ?? ''),
                'cargo' => trim($cargos[$i] ?? ''),
                'telefono' => trim($telefonos[$i] ?? ''),
            ];
        }

        $vias = [];
        $viaCentros = $_POST['via_centro'] ?? [];
        $viaDistancias = $_POST['via_distancia'] ?? [];
        $viaAccesos = $_POST['via_acceso'] ?? [];
        $viaTransportes = $_POST['via_transporte'] ?? [];
        $viaTiempos = $_POST['via_tiempo'] ?? [];
        $viaUnidades = $_POST['via_unidad'] ?? [];

        for ($i = 0; $i < 2; $i++) {
            if (trim($viaCentros[$i] ?? '') === '' && trim($viaDistancias[$i] ?? '') === '') {
                continue;
            }
            $vias[] = [
                'centro' => trim($viaCentros[$i] ?? ''),
                'distancia' => trim($viaDistancias[$i] ?? ''),
                'acceso' => trim($viaAccesos[$i] ?? ''),
                'transporte' => trim($viaTransportes[$i] ?? ''),
                'tiempo' => trim($viaTiempos[$i] ?? ''),
                'unidad' => $viaUnidades[$i] ?? '',
            ];
        }

        $excretas = [];
        $excretasItems = ['alcantarillado_ptar', 'alcantarillado_sin_ptar', 'ubs_tanque_septico', 'ubs_tanque_mejorado', 'ubs_compostera', 'ubs_compostaje', 'ubs_hoyo_seco', 'otro'];
        foreach ($excretasItems as $item) {
            $excretas[$item] = [
                'usa' => isset($_POST['excreta_usa'][$item]) ? 1 : 0,
                'viviendas' => trim($_POST['excreta_viviendas'][$item] ?? ''),
                'calificacion' => $_POST['excreta_calificacion'][$item] ?? '',
                'otro' => trim($_POST['excreta_otro'] ?? ''),
            ];
        }

        $percepciones = [];
        for ($i = 1; $i <= 5; $i++) {
            $percepciones[] = [
                'vivienda' => $i,
                'uso_agua' => $_POST['percepcion_agua'][$i] ?? '',
                'uso_excretas' => $_POST['percepcion_excretas'][$i] ?? '',
                'residuos' => $_POST['percepcion_residuos'][$i] ?? '',
                'higiene' => $_POST['percepcion_higiene'][$i] ?? '',
            ];
        }
        $percepciones[] = [
            'vivienda' => 'Personal del EE.SS.',
            'uso_agua' => $_POST['percepcion_agua_salud'] ?? '',
            'uso_excretas' => $_POST['percepcion_excretas_salud'] ?? '',
            'residuos' => $_POST['percepcion_residuos_salud'] ?? '',
            'higiene' => $_POST['percepcion_higiene_salud'] ?? '',
        ];

        $sql = "
            INSERT INTO centro_cuestionario (
                centro_poblado_id, nombre_conocido, patron_ccpp, ubigeo_dd, ubigeo_pp, ubigeo_ddi, ubigeo_ccpp,
                zona_utm, coordenada_este, coordenada_norte, altitud_msnm, entrevistados_json, condicion_centro,
                viviendas_total, viviendas_habitadas, poblacion_total, lengua_1, lengua_1_otro, lengua_2, lengua_2_otro,
                servicio_energia, servicio_internet, servicio_celular, servicio_telecable, servicio_telefono,
                municipalidad_en_cp, vias_acceso_json, cuenta_sistema_agua, abastecimiento_agua, abastecimiento_agua_otro,
                cuenta_ubs, excretas_json, familias_pagan, obra_anio_tipo, obra_anio, obra_costo_tipo, obra_costo,
                constructor, constructor_otro, intervencion_tipo, intervencion_anio, percepcion_json, prestador_asistencia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nombre_conocido = VALUES(nombre_conocido),
                patron_ccpp = VALUES(patron_ccpp),
                ubigeo_dd = VALUES(ubigeo_dd),
                ubigeo_pp = VALUES(ubigeo_pp),
                ubigeo_ddi = VALUES(ubigeo_ddi),
                ubigeo_ccpp = VALUES(ubigeo_ccpp),
                zona_utm = VALUES(zona_utm),
                coordenada_este = VALUES(coordenada_este),
                coordenada_norte = VALUES(coordenada_norte),
                altitud_msnm = VALUES(altitud_msnm),
                entrevistados_json = VALUES(entrevistados_json),
                condicion_centro = VALUES(condicion_centro),
                viviendas_total = VALUES(viviendas_total),
                viviendas_habitadas = VALUES(viviendas_habitadas),
                poblacion_total = VALUES(poblacion_total),
                lengua_1 = VALUES(lengua_1),
                lengua_1_otro = VALUES(lengua_1_otro),
                lengua_2 = VALUES(lengua_2),
                lengua_2_otro = VALUES(lengua_2_otro),
                servicio_energia = VALUES(servicio_energia),
                servicio_internet = VALUES(servicio_internet),
                servicio_celular = VALUES(servicio_celular),
                servicio_telecable = VALUES(servicio_telecable),
                servicio_telefono = VALUES(servicio_telefono),
                municipalidad_en_cp = VALUES(municipalidad_en_cp),
                vias_acceso_json = VALUES(vias_acceso_json),
                cuenta_sistema_agua = VALUES(cuenta_sistema_agua),
                abastecimiento_agua = VALUES(abastecimiento_agua),
                abastecimiento_agua_otro = VALUES(abastecimiento_agua_otro),
                cuenta_ubs = VALUES(cuenta_ubs),
                excretas_json = VALUES(excretas_json),
                familias_pagan = VALUES(familias_pagan),
                obra_anio_tipo = VALUES(obra_anio_tipo),
                obra_anio = VALUES(obra_anio),
                obra_costo_tipo = VALUES(obra_costo_tipo),
                obra_costo = VALUES(obra_costo),
                constructor = VALUES(constructor),
                constructor_otro = VALUES(constructor_otro),
                intervencion_tipo = VALUES(intervencion_tipo),
                intervencion_anio = VALUES(intervencion_anio),
                percepcion_json = VALUES(percepcion_json),
                prestador_asistencia = VALUES(prestador_asistencia)
        ";

        $params = [
            $id,
            field_value('nombre_conocido'),
            field_value('patron_ccpp'),
            field_value('ubigeo_dd'),
            field_value('ubigeo_pp'),
            field_value('ubigeo_ddi'),
            field_value('ubigeo_ccpp'),
            field_value('zona_utm'),
            field_value('coordenada_este'),
            field_value('coordenada_norte'),
            field_value('altitud_msnm'),
            json_value($entrevistados),
            field_value('condicion_centro'),
            int_value('viviendas_total'),
            int_value('viviendas_habitadas'),
            int_value('poblacion_total'),
            field_value('lengua_1'),
            field_value('lengua_1_otro'),
            field_value('lengua_2'),
            field_value('lengua_2_otro'),
            field_value('servicio_energia'),
            field_value('servicio_internet'),
            field_value('servicio_celular'),
            field_value('servicio_telecable'),
            field_value('servicio_telefono'),
            field_value('municipalidad_en_cp'),
            json_value($vias),
            field_value('cuenta_sistema_agua'),
            field_value('abastecimiento_agua'),
            field_value('abastecimiento_agua_otro'),
            field_value('cuenta_ubs'),
            json_value($excretas),
            field_value('familias_pagan'),
            field_value('obra_anio_tipo'),
            field_value('obra_anio'),
            field_value('obra_costo_tipo'),
            decimal_value('obra_costo'),
            field_value('constructor'),
            field_value('constructor_otro'),
            field_value('intervencion_tipo'),
            field_value('intervencion_anio'),
            json_value($percepciones),
            field_value('prestador_asistencia'),
        ];

        $stmt = $conn->prepare($sql);
        $types = 'i' . str_repeat('s', count($params) - 1);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $success = "Cuestionario guardado correctamente.";
        } else {
            $error = "No se pudo guardar el cuestionario.";
        }
    }
}

$stmtCuestionario = $conn->prepare("SELECT * FROM centro_cuestionario WHERE centro_poblado_id = ?");
$stmtCuestionario->bind_param("i", $id);
$stmtCuestionario->execute();
$data = $stmtCuestionario->get_result()->fetch_assoc() ?: [];

$entrevistados = json_decode($data['entrevistados_json'] ?? '[]', true) ?: [];
$vias = json_decode($data['vias_acceso_json'] ?? '[]', true) ?: [];
$excretas = json_decode($data['excretas_json'] ?? '[]', true) ?: [];
$percepciones = json_decode($data['percepcion_json'] ?? '[]', true) ?: [];

$lenguas = ['Castellano', 'Quechua', 'Shipibo conibo', 'Aymara', 'Awajun', 'Ashaninka', 'Otro'];
$servicios = [
    'servicio_energia' => 'Energía eléctrica',
    'servicio_internet' => 'Internet',
    'servicio_celular' => 'Servicio de telefonía celular',
    'servicio_telecable' => 'Servicio de telecable',
    'servicio_telefono' => 'Teléfono fijo y/o comunitario',
];
$abastecimientos = ['Centro poblado vecino', 'Manantial', 'Pozo', 'Camión, cisterna o similar', 'Río, Acequia, Quebrada, Canal', 'Lago / laguna', 'Agua de lluvia', 'Otro'];
$excretaLabels = [
    'alcantarillado_ptar' => 'Sistema de alcantarillado con PTAR',
    'alcantarillado_sin_ptar' => 'Sistema de alcantarillado sin PTAR',
    'ubs_tanque_septico' => 'UBS-Tanque séptico',
    'ubs_tanque_mejorado' => 'UBS-Tanque séptico mejorado',
    'ubs_compostera' => 'UBS - Compostera de doble cámara',
    'ubs_compostaje' => 'UBS - Compostaje continuo',
    'ubs_hoyo_seco' => 'UBS - Hoyo seco ventilado',
    'otro' => 'Otro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cuestionario Centro Poblado</title>
<link rel="stylesheet" href="dashboard.css">
<style>
body { background: #eef3f8; height: auto; min-height: 100vh; }
.main { overflow: auto; }
.page-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
.card { background: #fff; border: 1px solid #dfe5ec; border-radius: 8px; padding: 22px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.card h3 { margin-top: 0; }
.grid { display: grid; grid-template-columns: repeat(4, minmax(140px, 1fr)); gap: 14px; align-items: end; }
.grid-2 { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 14px; }
.full { grid-column: 1 / -1; }
label { display: flex; flex-direction: column; gap: 6px; color: #333; font-size: 14px; }
input, select { padding: 9px; border: 1px solid #888; border-radius: 5px; font-family: Arial, sans-serif; }
.help { color: #2563eb; font-style: italic; font-size: 13px; }
.button, button { padding: 10px 15px; border: none; border-radius: 5px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light { background: #eee; color: #333; }
.alert { padding: 10px 12px; border-radius: 5px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
.radio-row { display: grid; grid-template-columns: 1fr 70px 70px; gap: 10px; align-items: center; margin-bottom: 12px; }
.option-list { display: grid; gap: 10px; }
.inline-radio { display: flex; align-items: center; gap: 8px; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
th { color: #777; font-size: 12px; text-transform: uppercase; }
.mini { width: 100%; min-width: 80px; box-sizing: border-box; }
.note { border-top: 1px solid #eee; margin-top: 16px; padding-top: 12px; color: #333; }
@media (max-width: 1000px) { .grid, .grid-2 { grid-template-columns: 1fr; } .full { grid-column: auto; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="mantenimiento_centros.php">Centros Poblados</a></li>
        <li><a href="detalle_centro.php?id=<?php echo e($id); ?>">Detalle del Centro</a></li>
        <li><a href="logout.php">Cerrar sesión</a></li>
    </ul>
</div>
<div class="main">
    <header><div class="user-info"><span><?php echo e($_SESSION['nombre']); ?></span> | <span><?php echo e($_SESSION['rol']); ?></span></div></header>
    <section class="content">
        <div class="page-title">
            <h2>Cuestionario: <?php echo e($centro['nombre']); ?></h2>
            <a class="button light" href="mantenimiento_centros.php">Volver</a>
        </div>
        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?php echo e($success); ?></div><?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="centro_poblado_id" value="<?php echo e($id); ?>">

            <div class="card">
                <h3>A Ubicación Geográfica</h3>
                <div class="grid">
                    <label>Departamento<input type="text" value="<?php echo e($centro['departamento']); ?>" readonly></label>
                    <label>Provincia<input type="text" value="<?php echo e($centro['provincia']); ?>" readonly></label>
                    <label>Distrito<input type="text" value="<?php echo e($centro['distrito']); ?>" readonly></label>
                    <label>Centro poblado<input type="text" value="<?php echo e($centro['id'] . ' - ' . $centro['nombre']); ?>" readonly></label>
                    <label class="full">Nombre conocido
                        <input type="text" name="nombre_conocido" value="<?php echo old($data, 'nombre_conocido'); ?>">
                        <span class="help">Nombre comúnmente conocido por la población del centro poblado (opcional)</span>
                    </label>
                    <label>Patrón CCPP
                        <select name="patron_ccpp">
                            <option value="">Seleccionar</option>
                            <option value="Disperso" <?php echo selected_value($data, 'patron_ccpp', 'Disperso'); ?>>Disperso</option>
                            <option value="Concentrado" <?php echo selected_value($data, 'patron_ccpp', 'Concentrado'); ?>>Concentrado</option>
                        </select>
                    </label>
                    <label>DD<input type="text" maxlength="2" name="ubigeo_dd" value="<?php echo old($data, 'ubigeo_dd'); ?>"></label>
                    <label>PP<input type="text" maxlength="2" name="ubigeo_pp" value="<?php echo old($data, 'ubigeo_pp'); ?>"></label>
                    <label>dd<input type="text" maxlength="2" name="ubigeo_ddi" value="<?php echo old($data, 'ubigeo_ddi'); ?>"></label>
                    <label>CCPP<input type="text" maxlength="4" name="ubigeo_ccpp" value="<?php echo old($data, 'ubigeo_ccpp'); ?>"></label>
                </div>
            </div>

            <div class="card">
                <h3>B Georeferenciación del centro poblado. <em>Zona UTM en WGS84</em></h3>
                <div class="grid">
                    <label>Zona UTM en WGS84<input type="text" name="zona_utm" value="<?php echo old($data, 'zona_utm'); ?>"></label>
                    <label>Coordenada este<input type="text" name="coordenada_este" value="<?php echo old($data, 'coordenada_este'); ?>"></label>
                    <label>Coordenada norte<input type="text" name="coordenada_norte" value="<?php echo old($data, 'coordenada_norte'); ?>"></label>
                    <label>Altitud (MSNM)<input type="text" name="altitud_msnm" value="<?php echo old($data, 'altitud_msnm'); ?>"></label>
                </div>
                <p class="note"><strong>NOTA:</strong> La georeferenciación del centro poblado de preferencia en formato KML o KMZ.</p>
            </div>

            <div class="card">
                <h3>D Información de las personas entrevistadas</h3>
                <table>
                    <thead><tr><th>Nombres y apellidos</th><th>¿Tiene DNI?</th><th>DNI</th><th>Cargo</th><th>Teléfono</th></tr></thead>
                    <tbody>
                        <?php for ($i = 0; $i < 3; $i++): $row = $entrevistados[$i] ?? []; ?>
                            <tr>
                                <td><input class="mini" type="text" name="entrevistado_nombre[]" value="<?php echo e($row['nombre'] ?? ''); ?>"></td>
                                <td>
                                    <label class="inline-radio"><input type="radio" name="entrevistado_tiene_dni[<?php echo e($i); ?>]" value="SI" <?php echo (($row['tiene_dni'] ?? '') === 'SI') ? 'checked' : ''; ?>> SI</label>
                                    <label class="inline-radio"><input type="radio" name="entrevistado_tiene_dni[<?php echo e($i); ?>]" value="NO" <?php echo (($row['tiene_dni'] ?? '') === 'NO') ? 'checked' : ''; ?>> NO</label>
                                </td>
                                <td><input class="mini" type="text" name="entrevistado_dni[]" value="<?php echo e($row['dni'] ?? ''); ?>"></td>
                                <td><input class="mini" type="text" name="entrevistado_cargo[]" value="<?php echo e($row['cargo'] ?? ''); ?>"></td>
                                <td><input class="mini" type="text" name="entrevistado_telefono[]" value="<?php echo e($row['telefono'] ?? ''); ?>"></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>E Información de centros poblados sin vivienda o no ubicados</h3>
                <div class="option-list">
                    <?php
                    $condiciones = [
                        'E1' => 'E.1 El Centro Poblado no cuenta con viviendas particulares o población',
                        'E2' => 'E.2 No es posible determinar la ubicación del Centro Poblado o pertenece a otro distrito',
                        'E3' => 'E.3 Centro Poblado donde el servicio de agua y/o saneamiento es administrado por EPS',
                        'E4' => 'E.4 Centro Poblado con viviendas particulares y población ubicado',
                    ];
                    foreach ($condiciones as $key => $label):
                    ?>
                        <label class="inline-radio"><input type="radio" name="condicion_centro" value="<?php echo e($key); ?>" <?php echo checked_value($data, 'condicion_centro', $key); ?>> <?php echo e($label); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>100 En este centro poblado</h3>
                <div class="grid">
                    <label>¿Cuántas viviendas en total existen?<input type="number" min="0" name="viviendas_total" value="<?php echo old($data, 'viviendas_total'); ?>"></label>
                    <label>¿Cuántas viviendas habitadas existen?<input type="number" min="0" name="viviendas_habitadas" value="<?php echo old($data, 'viviendas_habitadas'); ?>"></label>
                    <label>¿Cuál es la población total?<input type="number" min="0" name="poblacion_total" value="<?php echo old($data, 'poblacion_total'); ?>"></label>
                </div>
            </div>

            <div class="card">
                <h3>101 ¿Cuál es la lengua que predomina en el centro poblado (1°) y cuál es la segunda lengua (2°)?</h3>
                <div class="grid-2">
                    <div>
                        <strong>1° lengua</strong>
                        <?php foreach ($lenguas as $lengua): ?>
                            <label class="inline-radio"><input type="radio" name="lengua_1" value="<?php echo e($lengua); ?>" <?php echo checked_value($data, 'lengua_1', $lengua); ?>> <?php echo e($lengua); ?></label>
                        <?php endforeach; ?>
                        <input type="text" name="lengua_1_otro" placeholder="Otro" value="<?php echo old($data, 'lengua_1_otro'); ?>">
                    </div>
                    <div>
                        <strong>2° lengua</strong>
                        <?php foreach ($lenguas as $lengua): ?>
                            <label class="inline-radio"><input type="radio" name="lengua_2" value="<?php echo e($lengua); ?>" <?php echo checked_value($data, 'lengua_2', $lengua); ?>> <?php echo e($lengua); ?></label>
                        <?php endforeach; ?>
                        <input type="text" name="lengua_2_otro" placeholder="Otro" value="<?php echo old($data, 'lengua_2_otro'); ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>102 ¿Cuál de los siguientes servicios tienen en el centro poblado?</h3>
                <?php foreach ($servicios as $name => $label): ?>
                    <div class="radio-row">
                        <span><?php echo e($label); ?></span>
                        <label class="inline-radio"><input type="radio" name="<?php echo e($name); ?>" value="SI" <?php echo checked_value($data, $name, 'SI'); ?>> SI</label>
                        <label class="inline-radio"><input type="radio" name="<?php echo e($name); ?>" value="NO" <?php echo checked_value($data, $name, 'NO'); ?>> NO</label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h3>104 ¿En este centro poblado se encuentra la municipalidad provincial/distrital?</h3>
                <label class="inline-radio"><input type="radio" name="municipalidad_en_cp" value="SI" <?php echo checked_value($data, 'municipalidad_en_cp', 'SI'); ?>> SI</label>
                <label class="inline-radio"><input type="radio" name="municipalidad_en_cp" value="NO" <?php echo checked_value($data, 'municipalidad_en_cp', 'NO'); ?>> NO</label>
            </div>

            <div class="card">
                <h3>104a Vía de acceso del centro poblado a la capital del distrito</h3>
                <table>
                    <thead><tr><th>Centro donde se encuentra la municipalidad</th><th>Distancia (KM)</th><th>Vía de acceso</th><th>Medio de transporte</th><th>Tiempo</th><th>Unidad</th></tr></thead>
                    <tbody>
                        <?php for ($i = 0; $i < 2; $i++): $row = $vias[$i] ?? []; ?>
                            <tr>
                                <td><input class="mini" type="text" name="via_centro[]" value="<?php echo e($row['centro'] ?? ''); ?>"></td>
                                <td><input class="mini" type="number" step="0.01" name="via_distancia[]" value="<?php echo e($row['distancia'] ?? ''); ?>"></td>
                                <td><input class="mini" type="text" name="via_acceso[]" value="<?php echo e($row['acceso'] ?? ''); ?>"></td>
                                <td><input class="mini" type="text" name="via_transporte[]" value="<?php echo e($row['transporte'] ?? ''); ?>"></td>
                                <td><input class="mini" type="number" step="0.01" name="via_tiempo[]" value="<?php echo e($row['tiempo'] ?? ''); ?>"></td>
                                <td>
                                    <select class="mini" name="via_unidad[]">
                                        <option value="">Seleccionar</option>
                                        <option value="Hora" <?php echo (($row['unidad'] ?? '') === 'Hora') ? 'selected' : ''; ?>>Hora</option>
                                        <option value="Min" <?php echo (($row['unidad'] ?? '') === 'Min') ? 'selected' : ''; ?>>Min</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>105 ¿El centro poblado cuenta con sistema(s) de agua?</h3>
                <label class="inline-radio"><input type="radio" name="cuenta_sistema_agua" value="SI" <?php echo checked_value($data, 'cuenta_sistema_agua', 'SI'); ?>> SI</label>
                <label class="inline-radio"><input type="radio" name="cuenta_sistema_agua" value="NO" <?php echo checked_value($data, 'cuenta_sistema_agua', 'NO'); ?>> NO</label>
            </div>

            <div class="card">
                <h3>106 ¿Cómo se abastecen de agua en el centro poblado?</h3>
                <?php foreach ($abastecimientos as $opcion): ?>
                    <label class="inline-radio"><input type="radio" name="abastecimiento_agua" value="<?php echo e($opcion); ?>" <?php echo checked_value($data, 'abastecimiento_agua', $opcion); ?>> <?php echo e($opcion); ?></label>
                <?php endforeach; ?>
                <input type="text" name="abastecimiento_agua_otro" placeholder="Otro" value="<?php echo old($data, 'abastecimiento_agua_otro'); ?>">
            </div>

            <div class="card">
                <h3>107 ¿El centro poblado cuenta con sistema de disposición sanitaria de excretas y/o UBS?</h3>
                <label class="inline-radio"><input type="radio" name="cuenta_ubs" value="SI" <?php echo checked_value($data, 'cuenta_ubs', 'SI'); ?>> SI</label>
                <label class="inline-radio"><input type="radio" name="cuenta_ubs" value="NO" <?php echo checked_value($data, 'cuenta_ubs', 'NO'); ?>> NO</label>
            </div>

            <div class="card">
                <h3>108 ¿Qué tipo de sistema de eliminación de excretas utilizan las familias?</h3>
                <table>
                    <thead><tr><th>Sistema</th><th>Usa</th><th>N° viviendas</th><th>Poco/Nada</th><th>Algo</th><th>Mucho</th></tr></thead>
                    <tbody>
                        <?php foreach ($excretaLabels as $key => $label): $row = $excretas[$key] ?? []; ?>
                            <tr>
                                <td><?php echo e($label); ?><?php if ($key === 'otro'): ?><input class="mini" type="text" name="excreta_otro" value="<?php echo e($row['otro'] ?? ''); ?>"><?php endif; ?></td>
                                <td><input type="checkbox" name="excreta_usa[<?php echo e($key); ?>]" value="1" <?php echo !empty($row['usa']) ? 'checked' : ''; ?>></td>
                                <td><input class="mini" type="number" min="0" name="excreta_viviendas[<?php echo e($key); ?>]" value="<?php echo e($row['viviendas'] ?? ''); ?>"></td>
                                <td><input type="radio" name="excreta_calificacion[<?php echo e($key); ?>]" value="Poco/Nada" <?php echo (($row['calificacion'] ?? '') === 'Poco/Nada') ? 'checked' : ''; ?>></td>
                                <td><input type="radio" name="excreta_calificacion[<?php echo e($key); ?>]" value="Algo" <?php echo (($row['calificacion'] ?? '') === 'Algo') ? 'checked' : ''; ?>></td>
                                <td><input type="radio" name="excreta_calificacion[<?php echo e($key); ?>]" value="Mucho" <?php echo (($row['calificacion'] ?? '') === 'Mucho') ? 'checked' : ''; ?>></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>110 ¿Las familias pagan por el sistema de disposición sanitaria de excretas?</h3>
                <label class="inline-radio"><input type="radio" name="familias_pagan" value="SI" <?php echo checked_value($data, 'familias_pagan', 'SI'); ?>> SI</label>
                <label class="inline-radio"><input type="radio" name="familias_pagan" value="NO" <?php echo checked_value($data, 'familias_pagan', 'NO'); ?>> NO</label>
            </div>

            <div class="card">
                <h3>112 ¿En qué año se construyó la obra de infraestructura?</h3>
                <label class="inline-radio"><input type="radio" name="obra_anio_tipo" value="Anio" <?php echo checked_value($data, 'obra_anio_tipo', 'Anio'); ?>> Año</label>
                <input type="text" name="obra_anio" maxlength="4" value="<?php echo old($data, 'obra_anio'); ?>">
                <label class="inline-radio"><input type="radio" name="obra_anio_tipo" value="No sabe" <?php echo checked_value($data, 'obra_anio_tipo', 'No sabe'); ?>> No sabe/no recuerda</label>
            </div>

            <div class="card">
                <h3>112a ¿Cuánto costó aproximadamente la obra?</h3>
                <label class="inline-radio"><input type="radio" name="obra_costo_tipo" value="Monto" <?php echo checked_value($data, 'obra_costo_tipo', 'Monto'); ?>> Monto S/.</label>
                <input type="number" step="0.01" name="obra_costo" value="<?php echo old($data, 'obra_costo'); ?>">
                <label class="inline-radio"><input type="radio" name="obra_costo_tipo" value="No sabe" <?php echo checked_value($data, 'obra_costo_tipo', 'No sabe'); ?>> No sabe</label>
            </div>

            <div class="card">
                <h3>113 ¿Quién construyó la obra de infraestructura?</h3>
                <?php foreach (['Gobierno Regional', 'Mun. Provincial', 'Mun. Distrital', 'FONCODES', 'ONG', 'MVCS (PNSR, PROCOES)', 'No sabe', 'Otro'] as $constructor): ?>
                    <label class="inline-radio"><input type="radio" name="constructor" value="<?php echo e($constructor); ?>" <?php echo checked_value($data, 'constructor', $constructor); ?>> <?php echo e($constructor); ?></label>
                <?php endforeach; ?>
                <input type="text" name="constructor_otro" placeholder="Otro" value="<?php echo old($data, 'constructor_otro'); ?>">
            </div>

            <div class="card">
                <h3>114 Última intervención en mejoramiento, ampliación y/o rehabilitación</h3>
                <label class="inline-radio"><input type="radio" name="intervencion_tipo" value="Anio" <?php echo checked_value($data, 'intervencion_tipo', 'Anio'); ?>> Año</label>
                <input type="text" maxlength="4" name="intervencion_anio" value="<?php echo old($data, 'intervencion_anio'); ?>">
                <label class="inline-radio"><input type="radio" name="intervencion_tipo" value="No sabe" <?php echo checked_value($data, 'intervencion_tipo', 'No sabe'); ?>> No sabe</label>
                <label class="inline-radio"><input type="radio" name="intervencion_tipo" value="Ninguna" <?php echo checked_value($data, 'intervencion_tipo', 'Ninguna'); ?>> Ninguna</label>
            </div>

            <div class="card">
                <h3>114b Percepción de las conductas sanitarias en las viviendas</h3>
                <table>
                    <thead><tr><th>N° vivienda</th><th>Uso de agua</th><th>Uso de excretas</th><th>Eliminación de residuos</th><th>Higiene corporal</th></tr></thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 5; $i++): $row = $percepciones[$i - 1] ?? []; ?>
                            <tr>
                                <td><?php echo e($i); ?></td>
                                <?php foreach (['agua' => 'uso_agua', 'excretas' => 'uso_excretas', 'residuos' => 'residuos', 'higiene' => 'higiene'] as $field => $jsonKey): ?>
                                    <td><select class="mini" name="percepcion_<?php echo e($field); ?>[<?php echo e($i); ?>]">
                                        <?php foreach (['', 'Adecuada', 'En proceso', 'Inadecuada', 'No aplica'] as $option): ?>
                                            <option value="<?php echo e($option); ?>" <?php echo (($row[$jsonKey] ?? '') === $option) ? 'selected' : ''; ?>><?php echo e($option ?: 'Seleccionar'); ?></option>
                                        <?php endforeach; ?>
                                    </select></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>115 ¿El prestador de AyS brinda asistencia técnica a las familias?</h3>
                <?php foreach (['SI', 'NO', 'No hay de prestador de SS'] as $opcion): ?>
                    <label class="inline-radio"><input type="radio" name="prestador_asistencia" value="<?php echo e($opcion); ?>" <?php echo checked_value($data, 'prestador_asistencia', $opcion); ?>> <?php echo e($opcion); ?></label>
                <?php endforeach; ?>
            </div>

            <button type="submit">Guardar cuestionario</button>
        </form>
    </section>
</div>
</body>
</html>
