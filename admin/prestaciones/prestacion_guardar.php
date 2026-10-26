<?php
require_once "../../conexion.php";require_once "../../includes/auth.php";exigir_rol('admin');
require_once __DIR__ . "/../../includes/equipos_red.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$uid=filter_var($_POST['usuario_id']??null,FILTER_VALIDATE_INT);
$equipoId=filter_var($_POST['equipo_id']??null,FILTER_VALIDATE_INT);
$hoy=date('Y-m-d');
$dev=date('Y-m-d',strtotime('+2 days'));
if(!$uid||!$equipoId){header("Location: prestaciones.php?error=".urlencode("Seleccione un usuario y un equipo disponible."));exit;}
$iniciada=false;
try{
    $conexion->begin_transaction();
    $iniciada=true;
    $stmt=$conexion->prepare("SELECT id FROM usuarios WHERE id=? AND estado='activo' FOR UPDATE");
    $stmt->bind_param("i",$uid);
    $stmt->execute();
    if(!$stmt->get_result()->fetch_assoc()){$conexion->rollback();header("Location: prestaciones.php?error=".urlencode("Seleccione un usuario activo."));exit;}
    $stmt=$conexion->prepare("SELECT id,codigo_equipo,numero_serie,marca,modelo,tipo FROM equipos WHERE id=? AND disponible_prestamo=1 AND estado='Disponible' AND usuario_id IS NULL FOR UPDATE");
    $stmt->bind_param("i",$equipoId);
    $stmt->execute();
    $equipo=$stmt->get_result()->fetch_assoc();
    if(!$equipo){$conexion->rollback();header("Location: prestaciones.php?error=".urlencode("El equipo seleccionado ya no está disponible para préstamo."));exit;}
    $stmt=$conexion->prepare("SELECT id FROM prestaciones WHERE equipo_id=? AND estado<>'Devuelto' LIMIT 1");
    $stmt->bind_param("i",$equipoId);
    $stmt->execute();
    if($stmt->get_result()->fetch_assoc()){$conexion->rollback();header("Location: prestaciones.php?error=".urlencode("El equipo ya tiene una prestación activa."));exit;}

    $tipo=trim(implode(' ',array_filter([$equipo['marca'],$equipo['modelo'],$equipo['tipo']],static fn($parte)=>trim((string)$parte)!=='')));
    $tipo=mb_substr($tipo!==''?$tipo:$equipo['codigo_equipo'],0,100);
    $codigo=trim((string)($equipo['numero_serie']?:$equipo['codigo_equipo']));
    $stmt=$conexion->prepare("INSERT INTO prestaciones(equipo_id,usuario_id,tipo_equipo,fecha_pedido,fecha_devolucion,estado,codigo_prestamo) VALUES(?,?,?,?,?,'Entregado',?)");
    $stmt->bind_param("iissss",$equipoId,$uid,$tipo,$hoy,$dev,$codigo);
    $stmt->execute();
    $prestacionId=(int)$conexion->insert_id;
    $ticket=sprintf('PRE-%s-%04d',date('Y'),$prestacionId);
    $stmt=$conexion->prepare("UPDATE prestaciones SET ticket=? WHERE id=?");
    $stmt->bind_param("si",$ticket,$prestacionId);
    $stmt->execute();

    $lugar=ubicacion_usuario_equipo($conexion,$uid)??'';
    [$estadoEquipo,$lugar]=aplicar_regla_bodega_equipo($uid,'Asignado',$lugar,$lugar);
    $stmt=$conexion->prepare("UPDATE equipos SET usuario_id=?,estado=?,lugar=? WHERE id=?");
    $stmt->bind_param("issi",$uid,$estadoEquipo,$lugar,$equipoId);
    $stmt->execute();
    registrar_historial_equipo($conexion,$equipoId,'Préstamo registrado','Prestado a '.nombre_usuario_equipo($conexion,$uid).'. Prestación: '.$ticket.'.');
    $conexion->commit();
    $iniciada=false;
}catch(mysqli_sql_exception $e){
    if($iniciada){$conexion->rollback();}
    error_log("No se pudo registrar la prestación: ".$e->getMessage());
    header("Location: prestaciones.php?error=".urlencode("No se pudo registrar el préstamo. Intente nuevamente."));
    exit;
}
header("Location: prestaciones.php?ok=".urlencode("Préstamo registrado. Código: ".$ticket));exit;
?>