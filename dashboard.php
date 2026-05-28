<?php
session_start();
require 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Consultas con nombres de columnas correctos
$centros = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados ORDER BY nombre");
$jass = $conn->query("SELECT id, nombre, centro_poblado_id FROM jass ORDER BY nombre");
$fichas = $conn->query("
    SELECT id, localidad_anexo AS centro_poblado, fecha_registro, cloro_residual_mgL, usuario_nombre 
    FROM ficha_tecnica 
    ORDER BY fecha_registro DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard SGMCR</title>
<link rel="stylesheet" href="dashboard.css">
<style>
/* Tabs y tablas */
.tabs { display: flex; margin-bottom: 20px; }
.tabs button { padding: 10px 20px; margin-right: 5px; cursor: pointer; border: none; background: #eee; border-radius: 5px; }
.tabs button.active { background: #c62828; color: white; }
.tabcontent { display: none; }
.tabcontent table { width: 100%; border-collapse: collapse; }
.tabcontent table th, .tabcontent table td { padding: 8px; border: 1px solid #ccc; text-align: left; }
.tabcontent table th { background: #f5f5f5; }
.search { margin-bottom: 10px; padding: 5px; width: 50%; }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="crear_usuario.php">Crear Usuario</a></li>
     <!-- Mantenimiento con submenú -->
        <li>
            <a href="#" class="submenu-toggle">Mantenimiento &#9662;</a>
            <ul class="submenu">
                <li><a href="mantenimiento_centros.php">Centros Poblados</a></li>
                <li><a href="mantenimiento_jass.php">JASS</a></li>
                <li><a href="mantenimiento_ficha.php">Ficha Cloración</a></li>
            </ul>
        </li>  
        <li><a href="logout.php">Cerrar sesión</a></li>
    </ul>
</div>
<script>
    // Toggle del submenú
    const toggles = document.querySelectorAll('.submenu-toggle');
    toggles.forEach(t => {
        t.addEventListener('click', function(e){
            e.preventDefault();
            this.nextElementSibling.classList.toggle('open');
        });
    });
</script>
    
<div class="main">
    <header>
        <div class="user-info">
            <span><?php echo $_SESSION['nombre']; ?></span> |
            <span><?php echo $_SESSION['rol']; ?></span>
        </div>
    </header>

    <section class="content">
        <h2>Dashboard</h2>

        <div class="tabs">
            <button class="tablinks active" onclick="openTab(event,'modulo1')">Módulo I - Centros Poblados</button>
            <button class="tablinks" onclick="openTab(event,'modulo2')">Módulo II - JASS</button>
            <button class="tablinks" onclick="openTab(event,'modulo3')">Módulo III - Cloración</button>
        </div>

        <!-- Módulo 1: Centros Poblados -->
        <div id="modulo1" class="tabcontent" style="display:block;">
            <input class="search" type="text" id="search1" onkeyup="searchTable('table1','search1')" placeholder="Buscar centro poblado...">
            <table id="table1">
                <tr><th>ID</th><th>Nombre</th><th>Distrito</th><th>Provincia</th><th>Departamento</th></tr>
                <?php while($row = $centros->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['distrito']; ?></td>
                    <td><?php echo $row['provincia']; ?></td>
                    <td><?php echo $row['departamento']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Módulo 2: JASS -->
        <div id="modulo2" class="tabcontent">
            <input class="search" type="text" id="search2" onkeyup="searchTable('table2','search2')" placeholder="Buscar JASS...">
            <table id="table2">
                <tr><th>ID</th><th>Nombre JASS</th><th>Centro Poblado ID</th></tr>
                <?php while($row = $jass->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['centro_poblado_id']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Módulo 3: Ficha Cloración -->
        <div id="modulo3" class="tabcontent">
            <input class="search" type="text" id="search3" onkeyup="searchTable('table3','search3')" placeholder="Buscar ficha de cloración...">
            <table id="table3">
                <tr><th>ID</th><th>Centro Poblado</th><th>Fecha Registro</th><th>Cloro Residual mg/L</th><th>Usuario</th></tr>
                <?php while($row = $fichas->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['centro_poblado']; ?></td>
                    <td><?php echo $row['fecha_registro']; ?></td>
                    <td><?php echo $row['cloro_residual_mgL']; ?></td>
                    <td><?php echo $row['usuario_nombre']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

    </section>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for(i=0;i<tabcontent.length;i++) tabcontent[i].style.display="none";
    tablinks = document.getElementsByClassName("tablinks");
    for(i=0;i<tablinks.length;i++) tablinks[i].className = tablinks[i].className.replace(" active","");
    document.getElementById(tabName).style.display="block";
    evt.currentTarget.className += " active";
}

// Buscador dinámico
function searchTable(tableId, inputId){
    var input, filter, table, tr, td, i, j, txtValue;
    input = document.getElementById(inputId);
    filter = input.value.toUpperCase();
    table = document.getElementById(tableId);
    tr = table.getElementsByTagName("tr");
    for(i=1;i<tr.length;i++){
        tr[i].style.display = "none";
        td = tr[i].getElementsByTagName("td");
        for(j=0;j<td.length;j++){
            if(td[j]){
                txtValue = td[j].textContent || td[j].innerText;
                if(txtValue.toUpperCase().indexOf(filter)>-1){
                    tr[i].style.display = "";
                    break;
                }
            }
        }
    }
}
</script>
</body>
</html>