<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$id=(int)($_GET['id']??0);
$stmt=$conexion->prepare("SELECT id,rut,nombre,apellido FROM usuarios WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$usuario=$stmt->get_result()->fetch_assoc();
if(!$usuario){header("Location: usuarios.php?error=".rawurlencode("No se encontró el usuario."));exit;}

// Cambios realizados al perfil
$stmt=$conexion->prepare("SELECT fecha_registro,accion,detalle,administrador_nombre FROM usuarios_historial WHERE usuario_id=? ORDER BY fecha_registro DESC,id DESC");
$stmt->bind_param("i",$id);
$stmt->execute();
$perfil=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cambios en los equipos asignados al usuario
$stmt=$conexion->prepare("SELECT h.fecha,h.accion,h.detalle,h.realizado_por,e.codigo_equipo FROM equipos_historial h INNER JOIN equipos e ON e.id=h.equipo_id WHERE e.usuario_id=? ORDER BY h.fecha DESC,h.id DESC");
$stmt->bind_param("i",$id);
$stmt->execute();
$equipos=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Historial de <?=e($usuario['nombre'].' '.$usuario['apellido'])?> <small class="text-muted fs-6"><?=e($usuario['rut'])?></small></h2>
    <a class="btn btn-secondary" href="usuarios.php">Volver a usuarios</a>
</div>

<div class="card mb-4"><div class="card-body">
<h5>Cambios del perfil (<?=count($perfil)?>)</h5>
<div class="table-responsive"><table class="table table-striped align-middle">
<thead><tr><th>Fecha</th><th>Acción</th><th>Cambios realizados</th><th>Realizado por</th></tr></thead><tbody>
<?php foreach($perfil as $f):?>
<tr><td><?=e($f['fecha_registro'])?></td><td><?=e($f['accion'])?></td><td><?=nl2br(e($f['detalle']))?></td><td><?=e($f['administrador_nombre'])?></td></tr>
<?php endforeach;?>
<?php if(!$perfil):?><tr><td colspan="4" class="text-center text-muted">Sin cambios registrados.</td></tr><?php endif;?>
</tbody></table></div>
</div></div>

<div class="card mb-4"><div class="card-body">
<h5>Cambios de equipos (<?=count($equipos)?>)</h5>
<div class="table-responsive"><table class="table table-striped align-middle">
<thead><tr><th>Fecha</th><th>Equipo</th><th>Acción</th><th>Detalle</th><th>Realizado por</th></tr></thead><tbody>
<?php foreach($equipos as $f):?>
<tr><td><?=e($f['fecha'])?></td><td><?=e($f['codigo_equipo'])?></td><td><?=e($f['accion'])?></td><td><?=nl2br(e($f['detalle']))?></td><td><?=e($f['realizado_por'])?></td></tr>
<?php endforeach;?>
<?php if(!$equipos):?><tr><td colspan="5" class="text-center text-muted">Sin cambios registrados.</td></tr><?php endif;?>
</tbody></table></div>
</div></div>
</main>
<?php require "../../includes/footer.php"; ?>
