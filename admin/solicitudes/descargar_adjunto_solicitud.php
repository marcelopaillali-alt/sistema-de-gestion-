<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');

$solicitudId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitudId || $solicitudId < 1) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$stmt = $conexion->prepare("SELECT adjunto FROM solicitudes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $solicitudId);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
if (!$solicitud || !$solicitud['adjunto']) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$directorio = realpath(__DIR__ . '/../../usuario/uploads/solicitudes');
$rutaArchivo = realpath(
    __DIR__ . '/../../usuario/' . str_replace('/', DIRECTORY_SEPARATOR, $solicitud['adjunto'])
);
if (!$directorio || !$rutaArchivo || dirname($rutaArchivo) !== $directorio || !is_file($rutaArchivo)) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$nombreDescarga = basename($rutaArchivo);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($rutaArchivo));
readfile($rutaArchivo);
exit;
