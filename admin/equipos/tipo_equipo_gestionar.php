<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$csrf = $_POST['csrf'] ?? '';
if (!is_string($csrf) || empty($_SESSION['csrf_tipos_equipo']) || !hash_equals($_SESSION['csrf_tipos_equipo'], $csrf)) {
    header("Location: inventario.php?pestana=agregar&error=" . urlencode("La solicitud expiró. Recargue la página e intente nuevamente."));
    exit;
}

$accion = $_POST['accion'] ?? '';
$nombre = trim((string)($_POST['nombre'] ?? ''));
$nombreNuevo = trim((string)($_POST['nombre_nuevo'] ?? ''));
$volver = ($_POST['volver'] ?? '') === 'editar' ? 'editar' : 'inventario';
$equipoId = max(0, (int)($_POST['equipo_id'] ?? 0));
$destino = $volver === 'editar' && $equipoId > 0
    ? "equipo_editar.php?id={$equipoId}"
    : "inventario.php?pestana=agregar";

$redirigir = static function (string $parametro, string $mensaje) use ($destino): void {
    header("Location: {$destino}&{$parametro}=" . urlencode($mensaje));
    exit;
};

if (!in_array($accion, ['editar', 'eliminar'], true) || $nombre === '') {
    $redirigir('error', 'Acción o tipo de equipo inválido.');
}
if ($accion === 'editar' && ($nombreNuevo === '' || mb_strlen($nombreNuevo) > 50)) {
    $redirigir('error', 'El nuevo nombre debe tener entre 1 y 50 caracteres.');
}

$transaccionIniciada = false;
try {
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmt = $conexion->prepare("SELECT nombre FROM tipos_equipo WHERE nombre=? FOR UPDATE");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $conexion->rollback();
        $redirigir('error', 'El tipo seleccionado ya no existe.');
    }

    if ($accion === 'eliminar') {
        $stmt = $conexion->prepare("SELECT COUNT(*) total FROM equipos WHERE tipo=?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $enUso = (int)$stmt->get_result()->fetch_assoc()['total'];
        if ($enUso > 0) {
            $conexion->rollback();
            $redirigir('error', "No se puede eliminar «{$nombre}» porque lo usan {$enUso} equipo(s).");
        }
        $stmt = $conexion->prepare("DELETE FROM tipos_equipo WHERE nombre=?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $conexion->commit();
        $transaccionIniciada = false;
        $redirigir('ok', "Tipo «{$nombre}» eliminado.");
    }

    $stmt = $conexion->prepare("SELECT nombre FROM tipos_equipo WHERE nombre=? AND nombre<>? LIMIT 1");
    $stmt->bind_param("ss", $nombreNuevo, $nombre);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $conexion->rollback();
        $redirigir('error', "Ya existe el tipo «{$nombreNuevo}».");
    }

    $stmt = $conexion->prepare("INSERT INTO equipos_historial(equipo_id,accion,detalle,realizado_por)
        SELECT id,'Cambio de tipo',CONCAT('Tipo cambiado: \"',tipo,'\" → \"',?,'\"'),?
        FROM equipos WHERE tipo=?");
    $autor = trim((string)($_SESSION['nombre'] ?? '')) ?: 'Administrador';
    $stmt->bind_param("sss", $nombreNuevo, $autor, $nombre);
    $stmt->execute();

    $stmt = $conexion->prepare("UPDATE equipos SET tipo=?,ultima_modificacion=NOW() WHERE tipo=?");
    $stmt->bind_param("ss", $nombreNuevo, $nombre);
    $stmt->execute();

    $stmt = $conexion->prepare("UPDATE tipos_equipo SET nombre=? WHERE nombre=?");
    $stmt->bind_param("ss", $nombreNuevo, $nombre);
    $stmt->execute();

    $conexion->commit();
    $transaccionIniciada = false;
} catch (mysqli_sql_exception $e) {
    if ($transaccionIniciada) {
        $conexion->rollback();
    }
    error_log("No se pudo gestionar el tipo de equipo: " . $e->getMessage());
    $redirigir('error', "No se pudo actualizar el tipo. Compruebe que el nombre no esté repetido.");
}

$redirigir('ok', "Tipo actualizado a «{$nombreNuevo}».");
