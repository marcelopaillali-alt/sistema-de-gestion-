<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$id=(int)($_GET['id']??0);$stmt=$conexion->prepare("DELETE FROM equipos WHERE id=?");$stmt->bind_param("i",$id);$stmt->execute();header("Location: inventario.php?ok=Equipo eliminado.");exit;
?>