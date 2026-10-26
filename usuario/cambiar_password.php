<?php
require_once "../conexion.php"; require_once "../includes/auth.php"; exigir_rol('usuario');
$actual=$_POST['actual']??''; $nueva=$_POST['nueva']??''; $repetir=$_POST['repetir']??'';
$stmt=$conexion->prepare("SELECT contrasena FROM usuarios WHERE id=?"); $id=(int)$_SESSION['id']; $stmt->bind_param("i",$id); $stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row || !password_verify($actual,$row['contrasena'])) { header("Location: mi_cuenta.php?error=La contraseña actual es incorrecta."); exit; }
if($nueva!==$repetir || strlen($nueva)<6) { header("Location: mi_cuenta.php?error=Las nuevas contraseñas no coinciden o son muy cortas."); exit; }
$hash=password_hash($nueva,PASSWORD_DEFAULT); $stmt=$conexion->prepare("UPDATE usuarios SET contrasena=? WHERE id=?"); $stmt->bind_param("si",$hash,$id); $stmt->execute();
header("Location: mi_cuenta.php?ok=Contraseña cambiada correctamente."); exit;
?>