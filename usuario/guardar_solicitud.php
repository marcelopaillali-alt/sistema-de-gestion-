<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$usuarioId = (int)$_SESSION['id'];
$tipo = trim($_POST['tipo_solicitud'] ?? '');
$asunto = trim($_POST['asunto'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$lugar = trim($_POST['lugar'] ?? '');
$prioridad = $_POST['prioridad'] ?? '';
$equipoId = null;
$adjunto = null;
$tiposPermitidos = [
    'Reportar un problema',
    'Solicitar un equipo',
    'Solicitar soporte técnico',
    'Problema de red/Internet',
    'Problema con impresora',
    'Solicitar acceso o cuenta',
    'Problema de contraseña',
    'Solicitar software',
    'Instalación o configuración',
    'Mantenimiento de equipo',
    'Reemplazo de equipo',
    'Préstamo de equipo',
    'Devolución de equipo',
    'Traslado de equipo',
    'Otro',
];
$prioridadesPermitidas = ['Baja', 'Media', 'Alta', 'Crítica'];

if (
    !in_array($tipo, $tiposPermitidos, true)
    || $asunto === ''
    || $descripcion === ''
    || $lugar === ''
    || !in_array($prioridad, $prioridadesPermitidas, true)
) {
    header("Location: crear_solicitud.php?error=" . urlencode("Complete los campos obligatorios con valores válidos."));
    exit;
}
$longitudAsunto = preg_match_all('/./us', $asunto, $caracteresAsunto);
$longitudLugar = preg_match_all('/./us', $lugar, $caracteresLugar);
if ($longitudAsunto === false || $longitudLugar === false || $longitudAsunto > 200 || $longitudLugar > 150) {
    header("Location: crear_solicitud.php?error=" . urlencode("El asunto o la ubicación superan el largo permitido."));
    exit;
}

$stmt = $conexion->prepare("SELECT area FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();
if (!$perfil) {
    http_response_code(403);
    exit("No se encontró el perfil del usuario.");
}
$area = $perfil['area'];

$equipoSeleccionado = $_POST['equipo_id'] ?? '';
if (!is_string($equipoSeleccionado)) {
    header("Location: crear_solicitud.php?error=" . urlencode("Seleccione un equipo válido."));
    exit;
}
$equipoSeleccionado = trim($equipoSeleccionado);
if ($equipoSeleccionado !== '') {
    if (!ctype_digit($equipoSeleccionado) || (int)$equipoSeleccionado < 1) {
        header("Location: crear_solicitud.php?error=" . urlencode("Seleccione un equipo válido."));
        exit;
    }
    $equipoId = (int)$equipoSeleccionado;
    $stmt = $conexion->prepare("SELECT id FROM equipos WHERE id = ? AND usuario_id = ? LIMIT 1");
    $stmt->bind_param("ii", $equipoId, $usuarioId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        header("Location: crear_solicitud.php?error=" . urlencode("Seleccione uno de sus equipos asignados."));
        exit;
    }
}

if (isset($_FILES['adjunto'])) {
    $archivo = $_FILES['adjunto'];
    if (
        !is_array($archivo)
        || !isset($archivo['error'], $archivo['size'], $archivo['tmp_name'])
        || !is_int($archivo['error'])
        || !is_int($archivo['size'])
        || !is_string($archivo['tmp_name'])
    ) {
        header("Location: crear_solicitud.php?error=" . urlencode("El archivo adjunto no es válido."));
        exit;
    }
    if ($archivo['error'] !== UPLOAD_ERR_NO_FILE && ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024)) {
        header("Location: crear_solicitud.php?error=" . urlencode("El archivo adjunto no es válido o supera los 5 MB."));
        exit;
    }
    if ($archivo['error'] !== UPLOAD_ERR_NO_FILE) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        $extensionesPermitidas = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'text/plain' => 'txt',
        ];
        if (!isset($extensionesPermitidas[$mime])) {
            header("Location: crear_solicitud.php?error=" . urlencode("El tipo de archivo adjunto no está permitido."));
            exit;
        }

        $directorio = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'solicitudes';
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            header("Location: crear_solicitud.php?error=" . urlencode("No se pudo preparar el almacenamiento del archivo."));
            exit;
        }
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extensionesPermitidas[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], $directorio . DIRECTORY_SEPARATOR . $nombreArchivo)) {
            header("Location: crear_solicitud.php?error=" . urlencode("No se pudo guardar el archivo adjunto."));
            exit;
        }
        $adjunto = 'uploads/solicitudes/' . $nombreArchivo;
    }
}

$tecnico = 'Sin asignar';
$estado = 'Pendiente';
$transaccionIniciada = false;
try {
    $conexion->begin_transaction();
    $transaccionIniciada = true;
    $stmt = $conexion->prepare(
        "INSERT INTO solicitudes
            (usuario_id, tipo_solicitud, asunto, descripcion, area_solicitante, equipo_id, lugar, prioridad, tecnico_asignado, adjunto, estado, fecha)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param(
        "issssisssss",
        $usuarioId,
        $tipo,
        $asunto,
        $descripcion,
        $area,
        $equipoId,
        $lugar,
        $prioridad,
        $tecnico,
        $adjunto,
        $estado
    );
    $stmt->execute();
    $solicitudId = (int)$conexion->insert_id;
    $ticket = 'SOL-' . date('y') . '-' . str_pad((string)$solicitudId, 5, '0', STR_PAD_LEFT);

    $stmt = $conexion->prepare("UPDATE solicitudes SET ticket = ? WHERE id = ?");
    $stmt->bind_param("si", $ticket, $solicitudId);
    $stmt->execute();
    $conexion->commit();
    $transaccionIniciada = false;
} catch (mysqli_sql_exception $exception) {
    if ($transaccionIniciada) {
        $conexion->rollback();
    }
    if ($adjunto !== null) {
        $rutaAdjunto = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $adjunto);
        if (is_file($rutaAdjunto) && !unlink($rutaAdjunto)) {
            error_log("No se pudo eliminar el adjunto de una solicitud fallida: " . $rutaAdjunto);
        }
    }
    error_log("Error al guardar la solicitud del usuario {$usuarioId}: " . $exception->getMessage());
    header("Location: crear_solicitud.php?error=" . urlencode("No se pudo guardar la solicitud. Intente nuevamente."));
    exit;
}

header("Location: ver_solicitud.php?id=" . $solicitudId . "&creada=1");
exit;
?>
