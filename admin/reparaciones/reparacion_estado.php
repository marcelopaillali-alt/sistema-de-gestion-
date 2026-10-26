<?php
require_once "../../conexion.php";require_once "../../includes/auth.php";require_once "../../includes/equipos_red.php";exigir_rol('admin');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id=(int)($_POST['id']??0);$estado=$_POST['estado']??'';
$permitidos=['En reparación','Reparado','Entregado','Reparado y entregado','No reparable','Bueno'];
if($id<1 || !is_string($estado) || !in_array($estado,$permitidos,true)){
    header("Location: reparaciones.php?error=".rawurlencode("Estado de reparación no válido."));exit;
}

try{
    $conexion->begin_transaction();
    $stmt=$conexion->prepare("SELECT equipo_id, estado FROM reparaciones WHERE id=? FOR UPDATE");
    $stmt->bind_param("i",$id);$stmt->execute();
    $reparacion=$stmt->get_result()->fetch_assoc();
    if(!$reparacion){
        $conexion->rollback();
        header("Location: reparaciones.php?error=".rawurlencode("No se encontró la reparación."));exit;
    }

    $fechaSalida=in_array($estado,['Entregado','Reparado y entregado','No reparable'],true)?date('Y-m-d'):null;
    $stmt=$conexion->prepare("UPDATE reparaciones SET estado=?, fecha_salida=? WHERE id=?");
    $stmt->bind_param("ssi",$estado,$fechaSalida,$id);$stmt->execute();

    $equipoId=$reparacion['equipo_id']!==null?(int)$reparacion['equipo_id']:null;
    if($equipoId!==null){
        $stmt=$conexion->prepare("SELECT usuario_id, estado FROM equipos WHERE id=? FOR UPDATE");
        $stmt->bind_param("i",$equipoId);$stmt->execute();
        $equipo=$stmt->get_result()->fetch_assoc();
        if($equipo){
            if(in_array($estado,['Entregado','Reparado y entregado'],true)){
                $estadoEquipo=$equipo['usuario_id']!==null?'Asignado':'Disponible';
            }elseif($estado==='No reparable'){
                $estadoEquipo='Baja';
            }else{
                $estadoEquipo='En reparación';
            }
            if($equipo['estado']!==$estadoEquipo){
                $stmt=$conexion->prepare("UPDATE equipos SET estado=? WHERE id=?");
                $stmt->bind_param("si",$estadoEquipo,$equipoId);$stmt->execute();
            }
            registrar_historial_equipo($conexion,$equipoId,'Reparación','Reparación #'.$id.': "'.$reparacion['estado'].'" → "'.$estado.'". Estado del equipo: "'.$estadoEquipo.'".');
        }
    }
    $conexion->commit();
}catch(mysqli_sql_exception $e){
    $conexion->rollback();
    error_log("No se pudo actualizar la reparación {$id}: ".$e->getMessage());
    header("Location: reparaciones.php?error=".rawurlencode("No se pudo actualizar el estado de la reparación."));exit;
}
header("Location: reparaciones.php?ok=".rawurlencode("Estado de reparación actualizado."));exit;
?>
