<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_login();

$stats = [
    'centros' => (int) $conn->query("SELECT COUNT(*) AS total FROM centros_poblados")->fetch_assoc()['total'],
    'municipalidades' => (int) $conn->query("SELECT COUNT(*) AS total FROM municipalidades")->fetch_assoc()['total'],
    'jass' => (int) $conn->query("SELECT COUNT(*) AS total FROM jass")->fetch_assoc()['total'],
    'sistemas' => (int) $conn->query("SELECT COUNT(*) AS total FROM sistemas_agua")->fetch_assoc()['total'],
    'fichas' => (int) $conn->query("SELECT COUNT(*) AS total FROM ficha_tecnica")->fetch_assoc()['total'],
];

$centros = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados ORDER BY nombre");
$jass = $conn->query("
    SELECT j.id, j.nombre, c.nombre AS centro_poblado
    FROM jass j
    INNER JOIN centros_poblados c ON j.centro_poblado_id = c.id
    ORDER BY j.nombre
");
$fichas = $conn->query("
    SELECT f.id, COALESCE(c.nombre, f.localidad_anexo) AS centro_poblado, f.fecha_registro,
           f.cloro_residual_mgL, f.usuario_nombre
    FROM ficha_tecnica f
    LEFT JOIN centros_poblados c ON f.centro_poblado_id = c.id
    ORDER BY f.fecha_registro DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard SGMCR</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.dashboard-title { margin-bottom: 18px; }
.dashboard-title h2 { margin: 0 0 6px; font-size: 26px; color: #2f343b; }
.dashboard-title p { margin: 0; color: #777; }
.stats-grid { display: grid; grid-template-columns: repeat(5, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card { background: #fff; border: 1px solid #e3e6ea; border-radius: 8px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.stat-card strong { display: block; color: #c62828; font-size: 26px; line-height: 1; }
.stat-card span { display: block; color: #666; margin-top: 7px; font-size: 13px; font-weight: bold; }
.tabs { display: flex; margin-bottom: 18px; gap: 8px; flex-wrap: wrap; border-bottom: 1px solid #eee; }
.tabs button { padding: 11px 18px; cursor: pointer; border: none; background: transparent; border-bottom: 3px solid transparent; color: #666; font-weight: bold; }
.tabs button.active { color: #c62828; border-bottom-color: #c62828; }
.tabcontent { display: none; }
.tabcontent table { width: 100%; border-collapse: collapse; }
.tabcontent table th, .tabcontent table td { padding: 11px; border-bottom: 1px solid #e7e7e7; text-align: left; }
.tabcontent table th { background: #f7f8fa; color: #666; font-size: 13px; text-transform: uppercase; }
.tabcontent table tr:hover td { background: #fafafa; }
.search { margin-bottom: 14px; padding: 10px 12px; width: 50%; min-width: 240px; border: 1px solid #ccc; border-radius: 6px; }
.action-link { display: inline-block; padding: 7px 10px; border-radius: 5px; background: #c62828; color: #fff; text-decoration: none; font-size: 13px; }
.empty-row { color: #777; text-align: center; }
@media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, minmax(130px, 1fr)); } }
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
                <ul class="submenu">
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
    <header class="datass-header">
        <div class="datass-brand">
            <div class="brand-mark">PERÚ</div>
            <div class="brand-ministry">Ministerio de Vivienda, Construcción y Saneamiento</div>
            <strong>DATASS</strong>
        </div>
        <div class="datass-clock">
            <span class="clock-icon"></span>
            <div>
                <strong id="currentDateTime"></strong>
                <span>Tacna, Tacna, Peru (PE)</span>
            </div>
        </div>
        <div class="datass-user">
            <span class="bell-icon">!</span>
            <div class="user-avatar"><?php echo e(strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1))); ?></div>
            <div>
                <strong><?php echo e($_SESSION['nombre']); ?></strong>
                <span><?php echo e($_SESSION['rol']); ?></span>
            </div>
        </div>
    </header>
    <section class="content">
        <div class="dashboard-title">
            <h2>Panel principal</h2>
            <p>Resumen operativo del sistema de agua, saneamiento y cloración.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><strong><?php echo e($stats['centros']); ?></strong><span>Centros poblados</span></div>
            <div class="stat-card"><strong><?php echo e($stats['municipalidades']); ?></strong><span>Municipalidades</span></div>
            <div class="stat-card"><strong><?php echo e($stats['jass']); ?></strong><span>JASS</span></div>
            <div class="stat-card"><strong><?php echo e($stats['sistemas']); ?></strong><span>Sistemas de agua</span></div>
            <div class="stat-card"><strong><?php echo e($stats['fichas']); ?></strong><span>Fichas de cloración</span></div>
        </div>

        <div class="tabs">
            <button class="tablinks active" onclick="openTab(event,'modulo1')">Módulo I - Centros Poblados</button>
            <button class="tablinks" onclick="openTab(event,'modulo2')">Módulo II - JASS</button>
            <button class="tablinks" onclick="openTab(event,'modulo3')">Módulo III - Cloración</button>
        </div>

        <div id="modulo1" class="tabcontent" style="display:block;">
            <input class="search" type="text" id="search1" onkeyup="searchTable('table1','search1')" placeholder="Buscar centro poblado...">
            <table id="table1">
                <tr><th>ID</th><th>Nombre</th><th>Distrito</th><th>Provincia</th><th>Departamento</th><th>Acciones</th></tr>
                <?php if ($centros->num_rows === 0): ?>
                    <tr><td class="empty-row" colspan="6">No hay centros poblados registrados.</td></tr>
                <?php endif; ?>
                <?php while ($row = $centros->fetch_assoc()): ?>
                <tr>
                    <td><?php echo e($row['id']); ?></td>
                    <td><?php echo e($row['nombre']); ?></td>
                    <td><?php echo e($row['distrito']); ?></td>
                    <td><?php echo e($row['provincia']); ?></td>
                    <td><?php echo e($row['departamento']); ?></td>
                    <td><a class="action-link" href="detalle_centro.php?id=<?php echo e($row['id']); ?>">Ver detalle</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div id="modulo2" class="tabcontent">
            <input class="search" type="text" id="search2" onkeyup="searchTable('table2','search2')" placeholder="Buscar JASS...">
            <table id="table2">
                <tr><th>ID</th><th>Nombre JASS</th><th>Centro Poblado</th></tr>
                <?php if ($jass->num_rows === 0): ?>
                    <tr><td class="empty-row" colspan="3">No hay JASS registradas.</td></tr>
                <?php endif; ?>
                <?php while ($row = $jass->fetch_assoc()): ?>
                <tr>
                    <td><?php echo e($row['id']); ?></td>
                    <td><?php echo e($row['nombre']); ?></td>
                    <td><?php echo e($row['centro_poblado']); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div id="modulo3" class="tabcontent">
            <input class="search" type="text" id="search3" onkeyup="searchTable('table3','search3')" placeholder="Buscar ficha de cloración...">
            <table id="table3">
                <tr><th>ID</th><th>Centro Poblado</th><th>Fecha Registro</th><th>Cloro Residual mg/L</th><th>Usuario</th></tr>
                <?php if ($fichas->num_rows === 0): ?>
                    <tr><td class="empty-row" colspan="5">No hay fichas de cloración registradas.</td></tr>
                <?php endif; ?>
                <?php while ($row = $fichas->fetch_assoc()): ?>
                <tr>
                    <td><?php echo e($row['id']); ?></td>
                    <td><?php echo e($row['centro_poblado']); ?></td>
                    <td><?php echo e($row['fecha_registro']); ?></td>
                    <td><?php echo e($row['cloro_residual_mgL']); ?></td>
                    <td><?php echo e($row['usuario_nombre']); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
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

function openTab(evt, tabName) {
    let i;
    const tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";

    const tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");

    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function searchTable(tableId, inputId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        const td = tr[i].getElementsByTagName("td");

        for (let j = 0; j < td.length; j++) {
            const txtValue = td[j].textContent || td[j].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
                break;
            }
        }
    }
}

function updateDateTime() {
    const target = document.getElementById('currentDateTime');
    const now = new Date();
    const date = now.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    const time = now.toLocaleTimeString('es-PE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    target.textContent = `${date}, ${time}`;
}

updateDateTime();
setInterval(updateDateTime, 1000);
</script>
</body>
</html>
