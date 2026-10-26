<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; require_once "../../includes/equipos_red.php"; require_once "../../includes/usuarios_historial.php"; exigir_rol('admin');
$id=(int)($_GET['id']??0);
if($id<1){header("Location: usuarios.php?error=".rawurlencode("Usuario no válido."));exit;}
if($id===(int)$_SESSION['id']){header("Location: usuarios.php?error=".rawurlencode("No puede eliminar su propia cuenta."));exit;}
$stmt=$conexion->prepare("SELECT rut,nombre,apellido FROM usuarios WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
 $usuario=$stmt->get_result()->fetch_assoc();
if(!$usuario){header("Location: usuarios.php?error=".rawurlencode("No se encontró el usuario."));exit;}
try{
    $conexion->begin_transaction();
    registrar_historial_perfil(
        $conexion,
        $id,
        $usuario['rut'],
        $usuario['nombre'].' '.$usuario['apellido'],
        'Eliminación de perfil',
        'El administrador eliminó el perfil del usuario.'
    );
    $stmt=$conexion->prepare("DELETE FROM usuarios WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    sincronizar_equipos_sin_usuario($conexion);
    $conexion->commit();
}catch(mysqli_sql_exception $e){
    $conexion->rollback();
    error_log("No se pudo eliminar el usuario {$id}: ".$e->getMessage());
    header("Location: usuarios.php?error=".rawurlencode("No se pudo eliminar el usuario ni guardar su historial."));
    exit;
}
header("Location: usuarios.php?ok=".rawurlencode("Usuario y su historial de solicitudes y prestaciones eliminados."));
exit;
?>