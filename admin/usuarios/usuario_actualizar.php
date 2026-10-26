<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; require_once "../../includes/usuarios_historial.php"; require_once "../../includes/formulario.php"; exigir_rol('admin');
recordar_formulario('usuario_editar', $_POST);
$id=(int)($_POST['id']??0);$rut=trim($_POST['rut']??'');$nombre=trim($_POST['nombre']??'');$apellido=trim($_POST['apellido']??'');$correo=trim($_POST['correo']??'');$cargo=trim($_POST['cargo']??'');$area=trim($_POST['area']??'');$areaNueva=trim($_POST['area_nueva']??'');$lugar=trim($_POST['lugar']??'');$ubicacion=trim($_POST['ubicacion']??'');$rol=$_POST['rol']??'usuario';$estado=$_POST['estado']??'activo';$nuevaPass=$_POST['contrasena'] ?? '';$repetirPass=$_POST['repetir_contrasena'] ?? '';$pinRecuperacion=$_POST['pin_recuperacion']??'';
if($area==='__nueva__'){$area=$areaNueva;}
if(!in_array($rol,['usuario','tecnico','admin'],true))$rol='usuario';
if(!in_array($estado,['activo','inactivo','bloqueado'],true))$estado='activo';
if($id===(int)$_SESSION['id'] && $estado!=='activo'){header("Location: usuarios.php?error=No puede cambiar el estado de su propia cuenta.");exit;}
if($rut===''||$nombre===''||$apellido===''||$correo===''){header("Location: usuarios.php?error=Complete los datos obligatorios del usuario.");exit;}
if($id<1){header("Location: usuarios.php?error=".urlencode("Usuario no válido."));exit;}
$stmtAntes=$conexion->prepare("SELECT rut,nombre,apellido,correo,cargo,area,lugar,ubicacion,rol,estado FROM usuarios WHERE id=? LIMIT 1");
$stmtAntes->bind_param("i",$id);
$stmtAntes->execute();
$usuarioAntes=$stmtAntes->get_result()->fetch_assoc();
if(!$usuarioAntes){header("Location: usuarios.php?error=".urlencode("Usuario no encontrado."));exit;}
if($pinRecuperacion!==''&&!preg_match('/^[0-9]{6}$/',$pinRecuperacion)){header("Location: usuario_editar.php?id=".$id."&error=El PIN de recuperación debe tener exactamente 6 dígitos.");exit;}
if($nuevaPass!==''||$repetirPass!==''){
    if($nuevaPass!==$repetirPass){
        header("Location: usuario_editar.php?id=".$id."&error=".urlencode("Las contraseñas no coinciden."));
        exit;
    }
    if(strlen($nuevaPass)<6){
        header("Location: usuario_editar.php?id=".$id."&error=".urlencode("La contraseña debe tener al menos 6 caracteres."));
        exit;
    }
}
if($area!==''){
    $stmtArea=$conexion->prepare("INSERT IGNORE INTO areas(nombre) VALUES(?)");
    $stmtArea->bind_param("s",$area);
    $stmtArea->execute();
}
if($nuevaPass !== '' || $repetirPass !== ''){
    $hash=password_hash($nuevaPass, PASSWORD_DEFAULT);
    $pinHash=$pinRecuperacion!==''?password_hash($pinRecuperacion,PASSWORD_DEFAULT):'';
    $stmt=$conexion->prepare("UPDATE usuarios SET rut=?,nombre=?,apellido=?,correo=?,cargo=?,area=?,lugar=?,ubicacion=?,rol=?,estado=?,contrasena=?,recovery_pin_hash=COALESCE(NULLIF(?,''),recovery_pin_hash),recovery_pin_attempts=IF(?<>'',0,recovery_pin_attempts),recovery_pin_locked_until=IF(?<>'',NULL,recovery_pin_locked_until) WHERE id=?");
    $stmt->bind_param("ssssssssssssssi",$rut,$nombre,$apellido,$correo,$cargo,$area,$lugar,$ubicacion,$rol,$estado,$hash,$pinHash,$pinHash,$pinHash,$id);
} else {
    $pinHash=$pinRecuperacion!==''?password_hash($pinRecuperacion,PASSWORD_DEFAULT):'';
    $stmt=$conexion->prepare("UPDATE usuarios SET rut=?,nombre=?,apellido=?,correo=?,cargo=?,area=?,lugar=?,ubicacion=?,rol=?,estado=?,recovery_pin_hash=COALESCE(NULLIF(?,''),recovery_pin_hash),recovery_pin_attempts=IF(?<>'',0,recovery_pin_attempts),recovery_pin_locked_until=IF(?<>'',NULL,recovery_pin_locked_until) WHERE id=?");
    $stmt->bind_param("sssssssssssss"."i",$rut,$nombre,$apellido,$correo,$cargo,$area,$lugar,$ubicacion,$rol,$estado,$pinHash,$pinHash,$pinHash,$id);
}
try{
    $conexion->begin_transaction();
    $stmt->execute();

    $camposAntes=[
        'rut'=>$usuarioAntes['rut'],
        'nombre'=>$usuarioAntes['nombre'],
        'apellido'=>$usuarioAntes['apellido'],
        'correo'=>$usuarioAntes['correo'],
        'cargo'=>$usuarioAntes['cargo'],
        'area'=>$usuarioAntes['area'],
        'lugar'=>$usuarioAntes['lugar'],
        'ubicacion'=>$usuarioAntes['ubicacion'],
        'rol'=>$usuarioAntes['rol'],
        'estado'=>$usuarioAntes['estado']
    ];
    $camposDespues=[
        'rut'=>$rut,
        'nombre'=>$nombre,
        'apellido'=>$apellido,
        'correo'=>$correo,
        'cargo'=>$cargo,
        'area'=>$area,
        'lugar'=>$lugar,
        'ubicacion'=>$ubicacion,
        'rol'=>$rol,
        'estado'=>$estado
    ];
    $cambios=[];
    foreach($camposDespues as $campo=>$valorNuevo){
        $valorAnterior=(string)($camposAntes[$campo]??'');
        if($valorAnterior!==(string)$valorNuevo){
            $cambios[]=$campo.': "'.($valorAnterior!==''?$valorAnterior:'—').'" → "'.((string)$valorNuevo!==''?$valorNuevo:'—').'"';
        }
    }
    if($nuevaPass!==''){$cambios[]='Contraseña: actualizada';}
    if($pinRecuperacion!==''){$cambios[]='PIN de recuperación: actualizado';}

    if($cambios){
        $accion='Modificación de perfil';
        if($usuarioAntes['rol']!==$rol){$accion='Cambio de rol';}
        elseif($usuarioAntes['estado']!==$estado){$accion='Cambio de estado';}

        registrar_historial_perfil(
            $conexion,
            $id,
            $rut,
            $nombre . ' ' . $apellido,
            $accion,
            implode(' | ', $cambios)
        );
    }
    $conexion->commit();
}catch(mysqli_sql_exception $e){
    $conexion->rollback();
    $msg=$e->getCode()==1062?(stripos($e->getMessage(),'rut')!==false?"El RUT ya está registrado.":"El correo ya está registrado."):"No se pudo actualizar el usuario.";
    header("Location: usuario_editar.php?id=".$id."&error=".urlencode($msg));exit;
}
if($id===(int)$_SESSION['id'])$_SESSION['rol']=$rol;
olvidar_formulario('usuario_editar');
header("Location: usuarios.php?ok=Usuario actualizado.");exit;
?>