<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$solicitudId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitudId || $solicitudId < 1) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

$usuarioId = (int)$_SESSION['id'];
$stmt = $conexion->prepare("SELECT adjunto FROM solicitudes WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->bind_param("ii", $solicitudId, $usuarioId);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
if (!$solicitud || !$solicitud['adjunto']) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

$directorio = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'solicitudes');
$rutaArchivo = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $solicitud['adjunto']));
if (!$directorio || !$rutaArchivo || dirname($rutaArchivo) !== $directorio || !is_file($rutaArchivo)) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($rutaArchivo);
if (!is_string($mime) || !in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
    http_response_code(415);
    exit("El archivo adjunto no es una imagen compatible.");
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($rutaArchivo) . '"');
header('Content-Length: ' . filesize($rutaArchivo));
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; sandbox");
readfile($rutaArchivo);
exit;
