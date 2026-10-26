<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Redirige a la solicitud con un mensaje de éxito o error.
function volver_a_solicitud(int $solicitudId, string $tipo, string $mensaje): void
{
    header("Location: solicitud_detalle.php?id=" . $solicitudId . "&" . $tipo . "=" . urlencode($mensaje));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit("Método no permitido.");
}

$solicitudId = filter_var($_POST['solicitud_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$csrf = $_POST['csrf'] ?? '';
$respuesta = $_POST['respuesta'] ?? null;

if ($solicitudId === false || $solicitudId === null) {
    http_response_code(400);
    exit("Solicitud no válida.");
}
if (!is_string($csrf)
    || empty($_SESSION['csrf_respuesta_solicitud'])
    || !hash_equals($_SESSION['csrf_respuesta_solicitud'], $csrf)) {
    http_response_code(403);
    exit("La solicitud expiró. Recargue el detalle e inténtelo nuevamente.");
}
if (!is_string($respuesta)) {
    volver_a_solicitud($solicitudId, 'error', "La respuesta no es válida.");
}

$respuesta = trim($respuesta);
$palabras = preg_split('/\s+/u', $respuesta, -1, PREG_SPLIT_NO_EMPTY);
if ($palabras === false || count($palabras) > 50) {
    volver_a_solicitud($solicitudId, 'error', "La respuesta no puede superar las 50 palabras.");
}

$stmt = $conexion->prepare("SELECT id FROM solicitudes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $solicitudId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    http_response_code(404);
    exit("Solicitud no encontrada.");
}

$stmt = $conexion->prepare("UPDATE solicitudes SET respuesta = NULLIF(?, '') WHERE id = ?");
$stmt->bind_param("si", $respuesta, $solicitudId);
$stmt->execute();

header("Location: solicitudes.php?ok=" . urlencode("Respuesta enviada correctamente."));
exit;
