<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
require_once __DIR__ . "/../../includes/equipos_red.php";
$id=(int)($_POST['id']??0);$codInst=mb_substr(trim($_POST['codigo_institucional']??''),0,50);$marca=trim($_POST['marca']??'');$modelo=trim($_POST['modelo']??'');$tipoSeleccion=trim($_POST['tipo']??'');$tipoNuevo=trim($_POST['tipo_nuevo']??'');$tipo='';$tipoPropietario=$_POST['tipo_propietario']??'Propio';$serie=trim($_POST['numero_serie']??'');$lugar=trim($_POST['lugar']??'');$ip=trim($_POST['ip_asignada']??'');$mac=trim($_POST['mac']??'');$estado=$_POST['estado']??'Disponible';$disponiblePrestamo=filter_var($_POST['disponible_prestamo']??null,FILTER_VALIDATE_INT);
$disponiblePrestamo=$disponiblePrestamo===0?0:($disponiblePrestamo===1?1:null);
if($disponiblePrestamo===null){header("Location: inventario.php?error=".urlencode("Seleccione si el equipo está disponible para préstamo."));exit;}
$permitidos=['Disponible','Asignado','En reparación','Baja'];if(!in_array($estado,$permitidos,true))$estado='Disponible';
$propietariosPermitidos=['Propio','Comodato','Arriendo'];if(!in_array($tipoPropietario,$propietariosPermitidos,true)){header("Location: inventario.php?error=".urlencode("Seleccione un tipo de propietario válido."));exit;}
$ipNormalizada=normalizar_ipv4_equipo($ip);
$macNormalizada=normalizar_mac_equipo($mac);
if($ipNormalizada===null){header("Location: inventario.php?error=".urlencode("Ingrese una dirección IPv4 válida."));exit;}
if($macNormalizada===null){header("Location: inventario.php?error=".urlencode("Ingrese una MAC válida con 6 pares hexadecimales, por ejemplo AA:BB:CC:DD:EE:FF."));exit;}
$ip=$ipNormalizada;
$mac=$macNormalizada;
$duplicado=mensaje_equipo_duplicado($conexion,['codigo_institucional'=>$codInst,'numero_serie'=>$serie,'mac'=>$mac,'ip_asignada'=>$ip],$id);
if($duplicado!==null){header("Location: equipo_editar.php?id=".$id."&error=".urlencode($duplicado));exit;}
$usuarioId=($_POST['usuario_id']??'')!==''?(int)$_POST['usuario_id']:null;
$stmtActual=$conexion->prepare("SELECT usuario_id,estado FROM equipos WHERE id=?");$stmtActual->bind_param("i",$id);$stmtActual->execute();$actual=$stmtActual->get_result()->fetch_assoc();
if(!$actual){header("Location: inventario.php?error=Equipo no encontrado.");exit;}
$stmtAntes=$conexion->prepare("SELECT * FROM equipos WHERE id=?");$stmtAntes->bind_param("i",$id);$stmtAntes->execute();$antes=$stmtAntes->get_result()->fetch_assoc();
[$estado,$lugar]=aplicar_regla_bodega_equipo($usuarioId,$estado,$lugar,ubicacion_usuario_equipo($conexion,$usuarioId));
if($id<=0){header("Location: inventario.php?error=Complete los datos obligatorios del equipo.");exit;}
try{
    $conexion->begin_transaction();
    $tipo=resolver_tipo_equipo($conexion,$tipoSeleccion,$tipoNuevo);
    if($tipo===null){$conexion->rollback();header("Location: inventario.php?error=".urlencode("Seleccione o escriba un tipo válido de máximo 50 caracteres."));exit;}
    if($usuarioId===null){
        $stmt=$conexion->prepare("        UPDATE equipos SET usuario_id=NULL,codigo_institucional=?,marca=?,modelo=?,tipo=?,tipo_propietario=?,numero_serie=?,lugar=?,ip_asignada=?,mac=?,estado=?,disponible_prestamo=? WHERE id=?");
                $stmt->bind_param("ssssssssssii",$codInst,$marca,$modelo,$tipo,$tipoPropietario,$serie,$lugar,$ip,$mac,$estado,$disponiblePrestamo,$id);
    }else{
        $stmt=$conexion->prepare("        UPDATE equipos SET usuario_id=?,codigo_institucional=?,marca=?,modelo=?,tipo=?,tipo_propietario=?,numero_serie=?,lugar=?,ip_asignada=?,mac=?,estado=?,disponible_prestamo=? WHERE id=?");
                $stmt->bind_param("issssssssssii",$usuarioId,$codInst,$marca,$modelo,$tipo,$tipoPropietario,$serie,$lugar,$ip,$mac,$estado,$disponiblePrestamo,$id);
    }
    $stmt->execute();
    $despues=['codigo_institucional'=>$codInst,'marca'=>$marca,'modelo'=>$modelo,'tipo'=>$tipo,'tipo_propietario'=>$tipoPropietario,'numero_serie'=>$serie,'lugar'=>$lugar,'ip_asignada'=>$ip,'mac'=>$mac,'estado'=>$estado,'disponible_prestamo'=>$disponiblePrestamo];
    $etiquetas=['codigo_institucional'=>'Código institucional','marca'=>'Marca','modelo'=>'Modelo','tipo'=>'Tipo','tipo_propietario'=>'Propietario','numero_serie'=>'Serie','lugar'=>'Lugar','ip_asignada'=>'IP','mac'=>'MAC','estado'=>'Estado','disponible_prestamo'=>'Disponible para préstamo'];
    $cambios=[];
    foreach($despues as $campo=>$nuevo){
        $anterior=(string)($antes[$campo]??'');
        if($anterior!==(string)$nuevo){$valorAnterior=$campo==='disponible_prestamo'?((int)$anterior?'Sí':'No'):($anterior!==''?$anterior:'-');$valorNuevo=$campo==='disponible_prestamo'?((int)$nuevo?'Sí':'No'):((string)$nuevo!==''?(string)$nuevo:'-');$cambios[]=$etiquetas[$campo].': "'.$valorAnterior.'" → "'.$valorNuevo.'"';}
    }
    if((int)($antes['usuario_id']??0)!==(int)$usuarioId){
        $cambios[]='Usuario: "'.nombre_usuario_equipo($conexion,$antes['usuario_id']).'" → "'.nombre_usuario_equipo($conexion,$usuarioId).'"';
    }
    if($cambios){registrar_historial_equipo($conexion,$id,'Modificación',implode(' | ',$cambios));}
    $accionAdmin=trim($_POST['accion_admin']??'');
    if($accionAdmin!==''){registrar_historial_equipo($conexion,$id,'Acción del administrador',mb_substr($accionAdmin,0,500));}
    $conexion->commit();
}catch(mysqli_sql_exception $e){
    $conexion->rollback();
    error_log("No se pudo actualizar el equipo: ".$e->getMessage());
    header("Location: inventario.php?error=".urlencode("No se pudo actualizar el equipo."));
    exit;
}
header("Location: inventario.php?ok=Equipo actualizado.");exit;
?>
