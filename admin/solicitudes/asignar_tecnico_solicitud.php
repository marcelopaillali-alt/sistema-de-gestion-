<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Redirige al detalle de la solicitud con un mensaje de éxito o error.
function volver_al_detalle(int $solicitudId, string $tipo, string $mensaje): void
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
$tecnicoId = filter_var($_POST['tecnico_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($solicitudId === false || $solicitudId === null) {
    http_response_code(400);
    exit("Solicitud no válida.");
}
if (!is_string($csrf) || empty($_SESSION['csrf_asignar_tecnico']) || !hash_equals($_SESSION['csrf_asignar_tecnico'], $csrf)) {
    http_response_code(403);
    exit("La solicitud expiró. Recargue el detalle e inténtelo nuevamente.");
}
if ($tecnicoId === false || $tecnicoId === null) {
    volver_al_detalle($solicitudId, 'error', "Seleccione un técnico válido.");
}

$stmt = $conexion->prepare("SELECT id FROM solicitudes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $solicitudId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    http_response_code(404);
    exit("Solicitud no encontrada.");
}

$stmt = $conexion->prepare(
    "SELECT nombre, apellido
     FROM usuarios
     WHERE id = ?
       AND estado = 'activo'
       AND LOWER(cargo) LIKE '%tecnic%'
       AND LOWER(area) LIKE '%inform%'
     LIMIT 1"
);
$stmt->bind_param("i", $tecnicoId);
$stmt->execute();
$tecnico = $stmt->get_result()->fetch_assoc();
if (!$tecnico) {
    volver_al_detalle($solicitudId, 'error', "El usuario seleccionado no es un técnico activo de Informática.");
}

$nombreTecnico = trim($tecnico['nombre'] . ' ' . $tecnico['apellido']);
$stmt = $conexion->prepare("UPDATE solicitudes SET tecnico_asignado = ? WHERE id = ?");
$stmt->bind_param("si", $nombreTecnico, $solicitudId);
$stmt->execute();

volver_al_detalle($solicitudId, 'ok', "Técnico asignado correctamente.");
