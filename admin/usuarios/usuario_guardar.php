<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; require_once "../../includes/usuarios_historial.php"; require_once "../../includes/formulario.php"; exigir_rol('admin');
recordar_formulario('usuario_nuevo', $_POST);
$rut=trim($_POST['rut']??'');$nombre=trim($_POST['nombre']??'');$apellido=trim($_POST['apellido']??'');$correo=trim($_POST['correo']??'');$cargo=trim($_POST['cargo']??'');$area=trim($_POST['area']??'');$areaNueva=trim($_POST['area_nueva']??'');$lugar=trim($_POST['lugar']??'');$ubicacion=trim($_POST['ubicacion']??'');$pass=$_POST['contrasena']??'';$repetirPass=$_POST['repetir_contrasena']??'';$pinRecuperacion=$_POST['pin_recuperacion']??'';$rol=$_POST['rol']??'usuario';$estado=$_POST['estado']??'activo';
if($area==='__nueva__'){$area=$areaNueva;}
if(!in_array($rol,['usuario','tecnico','admin'],true))$rol='usuario';
if(!in_array($estado,['activo','inactivo','bloqueado'],true))$estado='activo';
if($rut===''||$nombre===''||$apellido===''||$correo===''||strlen($pass)<6||!preg_match('/^[0-9]{6}$/',$pinRecuperacion)){
    header("Location: usuario_nuevo.php?error=".urlencode("Complete los campos, use una contraseña de al menos 6 caracteres y un PIN numérico de 6 dígitos."));
    exit;
}
if($pass!==$repetirPass){
    header("Location: usuario_nuevo.php?error=".urlencode("Las contraseñas no coinciden."));
    exit;
}
if($area!==''){
    $stmtArea=$conexion->prepare("INSERT IGNORE INTO areas(nombre) VALUES(?)");
    $stmtArea->bind_param("s",$area);
    $stmtArea->execute();
}
$hash=password_hash($pass,PASSWORD_DEFAULT);
$pinHash=password_hash($pinRecuperacion,PASSWORD_DEFAULT);
$stmt=$conexion->prepare("INSERT INTO usuarios(rut,nombre,apellido,correo,contrasena,recovery_pin_hash,cargo,area,lugar,ubicacion,rol,estado) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");$stmt->bind_param("ssssssssssss",$rut,$nombre,$apellido,$correo,$hash,$pinHash,$cargo,$area,$lugar,$ubicacion,$rol,$estado);
try{
    $conexion->begin_transaction();
    $stmt->execute();
    $nuevoId = (int)$conexion->insert_id;
    registrar_historial_perfil(
        $conexion,
        $nuevoId,
        $rut,
        $nombre . ' ' . $apellido,
        'Creación de perfil',
        'Perfil creado desde la administración.'
    );
    $conexion->commit();
}catch(mysqli_sql_exception $e){
    $conexion->rollback();
    $msg=$e->getCode()==1062?(stripos($e->getMessage(),'rut')!==false?"El RUT ya está registrado.":"El correo ya está registrado."):"No se pudo crear el usuario.";
    header("Location: usuario_nuevo.php?error=".urlencode($msg));exit;
}
olvidar_formulario('usuario_nuevo');
header("Location: usuarios.php?ok=Usuario creado correctamente.");exit;
?>