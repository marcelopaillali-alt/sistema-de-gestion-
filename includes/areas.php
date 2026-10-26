<?php
// Carga las áreas/departamentos disponibles desde la base de datos.
// Las áreas nuevas que se agreguen desde el formulario quedan guardadas aquí automáticamente.
require_once __DIR__ . "/../conexion.php";
$AREAS_DEPARTAMENTOS = [];
$resultadoAreas = $conexion->query("SELECT nombre FROM areas ORDER BY nombre ASC");
if ($resultadoAreas) {
    while ($fila = $resultadoAreas->fetch_assoc()) {
        $AREAS_DEPARTAMENTOS[] = $fila['nombre'];
    }
}
