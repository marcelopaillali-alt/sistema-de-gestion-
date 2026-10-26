<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$titulo="Solicitudes";$base="../../";$ok=$_GET['ok']??'';$error=$_GET['error']??'';
$buscar=isset($_GET['buscar']) && is_string($_GET['buscar']) ? trim($_GET['buscar']) : '';
$sqlSolicitudes="SELECT s.id, s.ticket, s.area_solicitante, s.tipo_solicitud, s.asunto, s.lugar, s.prioridad, s.tecnico_asignado, COALESCE(NULLIF(TRIM(s.estado),''),'Pendiente') estado, s.fecha, s.adjunto, CONCAT(u.nombre,' ',u.apellido) usuario FROM solicitudes s JOIN usuarios u ON u.id=s.usuario_id";
if($buscar!==''){
    $filtro='%'.$buscar.'%';
    $stmt=$conexion->prepare($sqlSolicitudes." WHERE u.rut LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR CONCAT(u.nombre,' ',u.apellido) LIKE ? OR s.ticket LIKE ? OR s.asunto LIKE ? OR s.tipo_solicitud LIKE ? ORDER BY s.id DESC");
    $stmt->bind_param("sssssss",$filtro,$filtro,$filtro,$filtro,$filtro,$filtro,$filtro);
    $stmt->execute();
    $datos=$stmt->get_result();
}else{
    $datos=$conexion->query($sqlSolicitudes." ORDER BY s.id DESC");
}
$clasesEstado = [
    'Pendiente' => 'status-pending',
    'Aprobada' => 'status-approved',
    'Aprobada reparación' => 'status-approved',
    'Completada' => 'status-resolved',
    'Rechazada' => 'status-cancelled',
];
$tiposReparacion = [
    'Reportar un problema',
    'Problema con impresora',
    'Mantenimiento de equipo',
];
require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><h2>Solicitudes</h2>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-8 col-lg-6">
        <input type="search" name="buscar" class="form-control" placeholder="Buscar por RUT, nombre, ticket, asunto o tipo de solicitud" value="<?=e($buscar)?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Buscar</button>
        <?php if($buscar!==''): ?><a href="solicitudes.php" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
    </div>
</form>

<div class="card table-card p-3"><div class="table-responsive"><table class="table table-hover table-sm align-middle"><thead><tr><th>Ticket</th><th>Usuario</th><th>Area</th><th>Tipo</th><th>Asunto</th><th>Ubicación</th><th>Prioridad</th><th>Técnico</th><th>Estado</th><th>Fecha</th><th>Imagen</th><th>Acción</th></tr></thead><tbody>
<?php $hayFilas=false; while($r=$datos->fetch_assoc()): $hayFilas=true; $extensionAdjunto=strtolower(pathinfo((string)$r['adjunto'], PATHINFO_EXTENSION)); $esImagenAdjunta=in_array($extensionAdjunto, ['jpg','jpeg','png','gif','webp'], true); $estadosSolicitud=['Pendiente','Aprobada','Rechazada','Completada']; if(in_array($r['tipo_solicitud'], $tiposReparacion, true)) $estadosSolicitud[]='Aprobada reparación'; if(!in_array($r['estado'], $estadosSolicitud, true)) array_unshift($estadosSolicitud, $r['estado']); ?><tr><td><strong><?=e($r['ticket'])?></strong></td><td><?=e($r['usuario'])?></td><td><?=e($r['area_solicitante'] ?: 'No definida')?></td><td><?=e($r['tipo_solicitud'])?></td><td><?=e($r['asunto'])?></td><td><?=e($r['lugar'])?></td><td><span class="badge status-badge <?=e(clase_prioridad($r['prioridad']))?>"><?=e($r['prioridad'])?></span></td><td><?=e($r['tecnico_asignado'])?></td><td><span class="badge status-badge <?=e($clasesEstado[$r['estado']] ?? 'status-default')?>"><?=e($r['estado'])?></span></td><td><?=e($r['fecha'])?></td><td><?php if($esImagenAdjunta): ?><a class="btn btn-sm btn-outline-secondary" href="ver_imagen_solicitud.php?id=<?=e($r['id'])?>" target="_blank" rel="noopener"><i class="bi bi-image me-1"></i>Ver imagen</a><?php else: ?>—<?php endif; ?></td><td><div class="d-flex flex-column gap-2"><a class="btn btn-sm btn-warning" href="solicitud_detalle.php?id=<?=e($r['id'])?>"><i class="bi bi-eye me-1"></i>Detalle</a><form method="POST" action="estado_solicitud.php" class="d-flex gap-1" novalidate data-validar-vacios><input type="hidden" name="id" value="<?=e($r['id'])?>"><select name="estado" class="form-select form-select-sm" aria-label="Estado de <?=e($r['ticket'])?>"><?php foreach($estadosSolicitud as $estadoOpcion): ?><option <?= $r['estado']===$estadoOpcion?'selected':'' ?>><?=e($estadoOpcion)?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary">OK</button></form></div></td></tr><?php endwhile; ?>
<?php if(!$hayFilas): ?><tr><td colspan="12" class="text-center text-muted py-4"><?= $buscar!=='' ? 'No se encontraron solicitudes con esos datos.' : 'No hay solicitudes registradas.' ?></td></tr><?php endif; ?>
</tbody></table></div></div></main>
<?php require "../../includes/footer.php"; ?>