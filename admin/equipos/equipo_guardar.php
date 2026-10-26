<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
require_once __DIR__ . "/../../includes/equipos_red.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$marca=trim($_POST['marca']??'');$codInst=mb_substr(trim($_POST['codigo_institucional']??''),0,50);$modelo=trim($_POST['modelo']??'');$tipoSeleccion=trim($_POST['tipo']??'');$tipoNuevo=trim($_POST['tipo_nuevo']??'');$tipo='';$tipoPropietario=$_POST['tipo_propietario']??'Propio';$serie=trim($_POST['numero_serie']??'');$lugar=trim($_POST['lugar']??'');$ip=trim($_POST['ip_asignada']??'');$mac=trim($_POST['mac']??'');$disponiblePrestamo=filter_var($_POST['disponible_prestamo']??null,FILTER_VALIDATE_INT);
$uid=($_POST['usuario_id']??'')!==''?(int)$_POST['usuario_id']:null;

$disponiblePrestamo=$disponiblePrestamo===0?0:($disponiblePrestamo===1?1:null);
if($disponiblePrestamo===null){header("Location: inventario.php?error=".urlencode("Seleccione si el equipo está disponible para préstamo."));exit;}
$propietariosPermitidos=['Propio','Comodato','Arriendo'];
if(!in_array($tipoPropietario,$propietariosPermitidos,true)){header("Location: inventario.php?error=".urlencode("Seleccione un tipo de propietario válido."));exit;}
$ipNormalizada=normalizar_ipv4_equipo($ip);
$macNormalizada=normalizar_mac_equipo($mac);
if($ipNormalizada===null){header("Location: inventario.php?error=".urlencode("Ingrese una dirección IPv4 válida."));exit;}
if($macNormalizada===null){header("Location: inventario.php?error=".urlencode("Ingrese una MAC válida con 6 pares hexadecimales, por ejemplo AA:BB:CC:DD:EE:FF."));exit;}
$ip=$ipNormalizada;
$mac=$macNormalizada;
$duplicado=mensaje_equipo_duplicado($conexion,['codigo_institucional'=>$codInst,'numero_serie'=>$serie,'mac'=>$mac,'ip_asignada'=>$ip]);
if($duplicado!==null){header("Location: inventario.php?pestana=agregar&error=".urlencode($duplicado));exit;}

[$estado,$lugar]=aplicar_regla_bodega_equipo($uid,'Disponible',$lugar,ubicacion_usuario_equipo($conexion,$uid));
$transaccionIniciada=false;
try{
    $conexion->begin_transaction();
    $transaccionIniciada=true;
    $tipo=resolver_tipo_equipo($conexion,$tipoSeleccion,$tipoNuevo);
    if($tipo===null){$conexion->rollback();$transaccionIniciada=false;header("Location: inventario.php?pestana=agregar&error=".urlencode("Seleccione o escriba un tipo válido de máximo 50 caracteres."));exit;}
    $stmt=$conexion->prepare("INSERT INTO equipos(usuario_id,codigo_institucional,marca,modelo,tipo,tipo_propietario,numero_serie,lugar,ip_asignada,mac,estado,disponible_prestamo,fecha_adquisicion) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,CURDATE())");
    $stmt->bind_param("issssssssssi",$uid,$codInst,$marca,$modelo,$tipo,$tipoPropietario,$serie,$lugar,$ip,$mac,$estado,$disponiblePrestamo);
    $stmt->execute();
    $equipoId=(int)$conexion->insert_id;
    $codigo='EQ-'.str_pad((string)$equipoId,5,'0',STR_PAD_LEFT);

    $stmtCodigo=$conexion->prepare("UPDATE equipos SET codigo_equipo=? WHERE id=?");
    $stmtCodigo->bind_param("si",$codigo,$equipoId);
    $stmtCodigo->execute();
    registrar_historial_equipo($conexion,$equipoId,'Registro inicial','Equipo registrado. Estado: '.$estado.'. Usuario: '.nombre_usuario_equipo($conexion,$uid).'. Lugar: '.($lugar!==''?$lugar:'-').'.');
    $conexion->commit();
    $transaccionIniciada=false;
}catch(mysqli_sql_exception $e){
    if($transaccionIniciada){$conexion->rollback();}
    error_log("No se pudo agregar el equipo: ".$e->getMessage());
    header("Location: inventario.php?error=".urlencode("No se pudo agregar el equipo. Intente nuevamente."));
    exit;
}
header("Location: inventario.php?ok=".urlencode("Equipo agregado con código {$codigo}."));exit;
?>