<?php
require_once "../../conexion.php";require_once "../../includes/auth.php";exigir_rol('admin');
$id=(int)($_POST['id']??0);$estado=$_POST['estado']??'';
$destino=(($_POST['volver']??'')==='detalle' && $id>0) ? "solicitud_detalle.php?id=$id&" : "solicitudes.php?";
$permitidas=['Pendiente','Aprobada','Rechazada','Completada','Aprobada reparación'];
if($id<1 || !is_string($estado) || !in_array($estado,$permitidas,true)){
    header("Location: ".$destino."error=".rawurlencode("Estado o solicitud no válidos."));exit;
}
require_once __DIR__ . "/../../includes/equipos_red.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion->begin_transaction();

    $stmt=$conexion->prepare("SELECT tipo_solicitud, ticket, asunto, descripcion, equipo_id, lugar FROM solicitudes WHERE id=? FOR UPDATE");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $solicitud=$stmt->get_result()->fetch_assoc();
    if(!$solicitud){
        $conexion->rollback();
        header("Location: ".$destino."error=".rawurlencode("No se encontró la solicitud."));
        exit;
    }

    $tiposReparacion=['reportar un problema','problema con impresora','mantenimiento de equipo'];
    $tipoNormalizado=strtolower(trim((string)$solicitud['tipo_solicitud']));
    if($estado==='Aprobada reparación' && !in_array($tipoNormalizado,$tiposReparacion,true)){
        $conexion->rollback();
        header("Location: ".$destino."error=".rawurlencode("Este estado solo se permite para solicitudes de reparación."));
        exit;
    }

    if($estado==='Aprobada reparación'){
        $stmt=$conexion->prepare("SELECT id FROM reparaciones WHERE solicitud_id=? LIMIT 1");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $reparacionExistente=$stmt->get_result()->fetch_assoc();

        if(!$reparacionExistente){
            $equipoId=$solicitud['equipo_id']!==null?(int)$solicitud['equipo_id']:null;
            $problema=trim((string)$solicitud['descripcion']);
            if($problema==='') $problema=trim((string)$solicitud['asunto']);
            $observaciones='Creada automáticamente desde solicitud '.($solicitud['ticket'] ?: '#'.$id).'.';
            if(trim((string)$solicitud['lugar'])!=='') $observaciones.=' Ubicación: '.trim((string)$solicitud['lugar']).'.';
            $fechaIngreso=date('Y-m-d');
            $estadoReparacion='Ingresado';
            $stmt=$conexion->prepare("INSERT INTO reparaciones(equipo_id,solicitud_id,problema,fecha_ingreso,estado,observaciones) VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("iissss",$equipoId,$id,$problema,$fechaIngreso,$estadoReparacion,$observaciones);
            $stmt->execute();
            $reparacionId=(int)$conexion->insert_id;

            if($equipoId!==null){
                $stmt=$conexion->prepare("SELECT estado FROM equipos WHERE id=? FOR UPDATE");
                $stmt->bind_param("i",$equipoId);
                $stmt->execute();
                $equipoActual=$stmt->get_result()->fetch_assoc();
                if($equipoActual && $equipoActual['estado']!=='En reparación'){
                    $stmt=$conexion->prepare("UPDATE equipos SET estado='En reparación' WHERE id=?");
                    $stmt->bind_param("i",$equipoId);
                    $stmt->execute();
                    registrar_historial_equipo($conexion,$equipoId,'Reparación','Solicitud '.($solicitud['ticket'] ?: '#'.$id).' aprobada para reparación.');
                }
            }
        } else {
            $reparacionId=(int)$reparacionExistente['id'];
        }
    }

    $stmt=$conexion->prepare("UPDATE solicitudes SET estado=? WHERE id=?");
    $stmt->bind_param("si",$estado,$id);
    $stmt->execute();
    $conexion->commit();
} catch (mysqli_sql_exception $exception) {
    $conexion->rollback();
    error_log("No se pudo actualizar el estado de la solicitud {$id}: ".$exception->getMessage());
    header("Location: ".$destino."error=".rawurlencode("No se pudo guardar la solicitud o crear su reparación. Revisa que la migración de reparaciones esté aplicada."));
    exit;
}

$mensaje=$estado==='Aprobada reparación'
    ? "Solicitud aprobada y reparación registrada en Reparaciones."
    : "Estado de solicitud actualizado.";
if ($estado === 'Aprobada reparación') {
    header("Location: ../reparaciones/reparaciones.php?ok=" . rawurlencode($mensaje . " Ticket " . ($solicitud['ticket'] ?: '#'.$id) . " | REP-" . str_pad((string)$reparacionId, 5, '0', STR_PAD_LEFT)));
    exit;
}
header("Location: ".$destino."ok=".rawurlencode($mensaje));exit;
?>
