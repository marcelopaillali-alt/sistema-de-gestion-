<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$id=(int)($_GET['id']??0);
$stmt=$conexion->prepare("SELECT id,codigo_equipo,codigo_institucional,marca,modelo,tipo FROM equipos WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$equipo=$stmt->get_result()->fetch_assoc();
if(!$equipo){header("Location: inventario.php?error=".rawurlencode("No se encontró el equipo."));exit;}

$stmt=$conexion->prepare("SELECT fecha,accion,detalle,realizado_por FROM equipos_historial WHERE equipo_id=? ORDER BY fecha DESC,id DESC");
$stmt->bind_param("i",$id);
$stmt->execute();
$historial=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Historial del equipo <?=e($equipo['codigo_equipo'])?> <small class="text-muted fs-6"><?=e($equipo['marca'].' '.$equipo['modelo'].' ('.$equipo['tipo'].')')?></small></h2>
    <a class="btn btn-secondary" href="inventario.php">Volver al inventario</a>
</div>
<div class="card mb-4"><div class="card-body">
<h5>Cambios realizados (<?=count($historial)?>)</h5>
<div class="table-responsive"><table class="table table-striped align-middle">
<thead><tr><th>Fecha</th><th>Acción</th><th>Detalle</th><th>Realizado por</th></tr></thead><tbody>
<?php foreach($historial as $f):?>
<tr><td><?=e($f['fecha'])?></td><td><?=e($f['accion'])?></td><td><?=nl2br(e($f['detalle']))?></td><td><?=e($f['realizado_por'])?></td></tr>
<?php endforeach;?>
<?php if(!$historial):?><tr><td colspan="4" class="text-center text-muted">Sin cambios registrados.</td></tr><?php endif;?>
</tbody></table></div>
</div></div>
</main>
<?php require "../../includes/footer.php"; ?>
