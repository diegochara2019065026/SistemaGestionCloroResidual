<?php
// Rango permitido de cloro en ppm (partes por millón)
$min_cloro = 0.2;
$max_cloro = 1.5;

// Simulación de una medición de cloro obtenida (puedes cambiar este valor para probar)
$medicion_actual = 1.8; 

// Validar si la medición está fuera de los límites
if ($medicion_actual < $min_cloro || $medicion_actual > $max_cloro) {
    // Se dispara la alerta usando JavaScript
    echo "<script>
            alert('¡ALERTA! El nivel de cloro ($medicion_actual ppm) está fuera del rango permitido.');
          </script>";
} else {
    echo "<script>
            console.log('Nivel de cloro normal: $medicion_actual ppm');
          </script>";
}
?>
