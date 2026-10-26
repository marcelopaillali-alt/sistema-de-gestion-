<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
require_once "../../includes/equipos_red.php";
exigir_rol('admin');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
$derivacion = $_POST['derivacion'] ?? '';
$motivo = $_POST['motivo_problema'] ?? '';
$diagnostico = $_POST['diagnostico_reparacion'] ?? '';

if ($solicitudId === false || $solicitudId === null) {
    http_response_code(400);
    exit("Solicitud no válida.");
}
if (!is_string($csrf) || empty($_SESSION['csrf_derivar_solicitud']) || !hash_equals($_SESSION['csrf_derivar_solicitud'], $csrf)) {
    http_response_code(403);
    exit("La solicitud expiró. Recargue el detalle e inténtelo nuevamente.");
}
if (!is_string($derivacion) || !in_array($derivacion, ['derivar', 'no_derivar'], true)) {
    volver_a_solicitud($solicitudId, 'error', "Seleccione si deriva o no a reparaciones.");
}
$motivo = is_string($motivo) ? trim($motivo) : '';
$diagnostico = is_string($diagnostico) ? trim($diagnostico) : '';
if ($motivo === '' || $diagnostico === '') {
    volver_a_solicitud($solicitudId, 'error', "Ingrese el motivo del problema y el diagnóstico.");
}
if (mb_strlen($motivo) > 2000 || mb_strlen($diagnostico) > 2000) {
    volver_a_solicitud($solicitudId, 'error', "El motivo y el diagnóstico admiten como máximo 2000 caracteres cada uno.");
}

try {
    $conexion->begin_transaction();
    $stmt = $conexion->prepare("SELECT id, ticket, equipo_id, lugar FROM solicitudes WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $solicitudId);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();
    if (!$solicitud) {
        $conexion->rollback();
        http_response_code(404);
        exit("Solicitud no encontrada.");
    }

    $stmt = $conexion->prepare("SELECT id FROM reparaciones WHERE solicitud_id = ? ORDER BY id LIMIT 1 FOR UPDATE");
    $stmt->bind_param("i", $solicitudId);
    $stmt->execute();
    $reparacion = $stmt->get_result()->fetch_assoc();
    if ($derivacion === 'no_derivar' && $reparacion) {
        $conexion->rollback();
        volver_a_solicitud($solicitudId, 'error', "Esta solicitud ya fue derivada a la reparación REP-" . str_pad((string)$reparacion['id'], 5, '0', STR_PAD_LEFT) . ".");
    }

    $stmt = $conexion->prepare("UPDATE solicitudes SET motivo_problema = ?, diagnostico_reparacion = ?, derivacion_reparacion = ? WHERE id = ?");
    $stmt->bind_param("sssi", $motivo, $diagnostico, $derivacion, $solicitudId);
    $stmt->execute();

    $ticket = $solicitud['ticket'] ?: '#' . $solicitudId;
    $mensaje = "Motivo y diagnóstico guardados. La solicitud $ticket no fue derivada a reparaciones.";
    if ($derivacion === 'derivar') {
        if ($reparacion) {
            $reparacionId = (int)$reparacion['id'];
            $stmt = $conexion->prepare("UPDATE reparaciones SET problema = ?, diagnostico = ? WHERE id = ?");
            $stmt->bind_param("ssi", $motivo, $diagnostico, $reparacionId);
            $stmt->execute();
        } else {
            $equipoId = $solicitud['equipo_id'] !== null ? (int)$solicitud['equipo_id'] : null;
            $observaciones = 'Derivada desde solicitud ' . $ticket . '.';
            if (trim((string)$solicitud['lugar']) !== '') {
                $observaciones .= ' Ubicación: ' . trim((string)$solicitud['lugar']) . '.';
            }
            $fechaIngreso = date('Y-m-d');
            $estadoReparacion = 'Ingresado';
            $stmt = $conexion->prepare("INSERT INTO reparaciones(equipo_id,solicitud_id,problema,diagnostico,fecha_ingreso,estado,observaciones) VALUES(?,?,?,?,?,?,?)");
            $stmt->bind_param("iisssss", $equipoId, $solicitudId, $motivo, $diagnostico, $fechaIngreso, $estadoReparacion, $observaciones);
            $stmt->execute();
            $reparacionId = (int)$conexion->insert_id;
            if ($equipoId !== null) {
                $stmt = $conexion->prepare("SELECT estado FROM equipos WHERE id = ? FOR UPDATE");
                $stmt->bind_param("i", $equipoId);
                $stmt->execute();
                $equipo = $stmt->get_result()->fetch_assoc();
                if ($equipo && $equipo['estado'] !== 'En reparación') {
                    $stmt = $conexion->prepare("UPDATE equipos SET estado = 'En reparación' WHERE id = ?");
                    $stmt->bind_param("i", $equipoId);
                    $stmt->execute();
                    registrar_historial_equipo($conexion, $equipoId, 'Reparación', 'Solicitud ' . $ticket . ' derivada a reparaciones.');
                }
            }
        }
        $mensaje = "Solicitud $ticket derivada a reparaciones (REP-" . str_pad((string)$reparacionId, 5, '0', STR_PAD_LEFT) . ").";
    }
    $conexion->commit();
} catch (mysqli_sql_exception $e) {
    $conexion->rollback();
    error_log("No se pudo derivar la solicitud {$solicitudId}: " . $e->getMessage());
    volver_a_solicitud($solicitudId, 'error', "No se pudo guardar la derivación. Verifique que la actualización de base de datos esté aplicada.");
}

volver_a_solicitud($solicitudId, 'ok', $mensaje);
