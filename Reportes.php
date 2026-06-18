<?php
include("conexion.php");

$sql = "SELECT 
            id,
            centro_poblado,
            nivel_cloro,
            fecha_medicion,
            observacion
        FROM mediciones_cloro
        ORDER BY fecha_medicion DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Mediciones de Cloro</title>
    <link rel="stylesheet" href="reportes.css">
</head>
<body>

<div class="contenedor">
    <h1>Reporte de Mediciones de Cloro Residual</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Centro Poblado</th>
                <th>Nivel de Cloro</th>
                <th>Fecha</th>
                <th>Observación</th>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($fila = mysqli_fetch_assoc($resultado)) { ?>
            <tr>
                <td><?php echo $fila['id']; ?></td>
                <td><?php echo $fila['centro_poblado']; ?></td>
                <td><?php echo $fila['nivel_cloro']; ?> mg/L</td>
                <td><?php echo $fila['fecha_medicion']; ?></td>
                <td><?php echo $fila['observacion']; ?></td>
                <td>
                    <?php
                    if ($fila['nivel_cloro'] < 0.5) {
                        echo "<span class='bajo'>Bajo</span>";
                    } elseif ($fila['nivel_cloro'] <= 1.0) {
                        echo "<span class='normal'>Normal</span>";
                    } else {
                        echo "<span class='alto'>Alto</span>";
                    }
                    ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <button onclick="window.print()">Imprimir Reporte</button>
</div>

</body>
</html>
