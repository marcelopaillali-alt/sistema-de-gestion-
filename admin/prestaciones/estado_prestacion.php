<?php
require_once "../../conexion.php";require_once "../../includes/auth.php";exigir_rol('admin');
require_once __DIR__ . "/../../includes/equipos_red.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$id=(int)($_POST['id']??0);$estado=$_POST['estado']??'Pendiente';
$permitidas=['Pendiente','Entregado','Devuelto','Atrasado'];
if($id<=0||!in_array($estado,$permitidas,true)){header("Location: prestaciones.php?error=".urlencode("La prestación o el estado seleccionado no son válidos."));exit;}
$iniciada=false;
try{
    $conexion->begin_transaction();
    $iniciada=true;
    $stmt=$conexion->prepare("SELECT id,equipo_id,usuario_id,estado FROM prestaciones WHERE id=? FOR UPDATE");
    $stmt->bind_param("i",$id);$stmt->execute();$prestacion=$stmt->get_result()->fetch_assoc();
    if(!$prestacion){$conexion->rollback();header("Location: prestaciones.php?error=".urlencode("Prestación no encontrada."));exit;}
    $stmt=$conexion->prepare("UPDATE prestaciones SET estado=? WHERE id=?");
    $stmt->bind_param("si",$estado,$id);$stmt->execute();
    if($prestacion['equipo_id']!==null){
        $equipoId=(int)$prestacion['equipo_id'];
        if($estado==='Devuelto'){
            $stmt=$conexion->prepare("UPDATE equipos SET usuario_id=NULL,estado='Disponible',lugar='Bodega' WHERE id=?");
            $stmt->bind_param("i",$equipoId);$stmt->execute();
            registrar_historial_equipo($conexion,$equipoId,'Préstamo devuelto','El equipo fue devuelto y quedó disponible en Bodega.');
        }else{
            $usuarioId=(int)$prestacion['usuario_id'];
            $lugar=ubicacion_usuario_equipo($conexion,$usuarioId)??'';
            [$estadoEquipo,$lugar]=aplicar_regla_bodega_equipo($usuarioId,'Asignado',$lugar,$lugar);
            $stmt=$conexion->prepare("UPDATE equipos SET usuario_id=?,estado=?,lugar=? WHERE id=?");
            $stmt->bind_param("issi",$usuarioId,$estadoEquipo,$lugar,$equipoId);$stmt->execute();
        }
    }
    $conexion->commit();$iniciada=false;
}catch(mysqli_sql_exception $e){
    if($iniciada){$conexion->rollback();}
    error_log("No se pudo actualizar el estado de la prestación: ".$e->getMessage());
    header("Location: prestaciones.php?error=".urlencode("No se pudo actualizar el estado de la prestación."));
    exit;
}
header("Location: prestaciones.php?ok=Estado de prestación actualizado.");exit;
?>
