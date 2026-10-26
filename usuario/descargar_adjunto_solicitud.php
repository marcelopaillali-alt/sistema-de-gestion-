<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$solicitudId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitudId || $solicitudId < 1) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$usuarioId = (int)$_SESSION['id'];
$stmt = $conexion->prepare("SELECT adjunto FROM solicitudes WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->bind_param("ii", $solicitudId, $usuarioId);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
if (!$solicitud || !$solicitud['adjunto']) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$directorio = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'solicitudes');
$rutaArchivo = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $solicitud['adjunto']));
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
?>
