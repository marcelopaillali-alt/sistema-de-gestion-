<?php
require_once "../conexion.php"; require_once "../includes/auth.php"; exigir_rol('usuario');
$titulo="Mis solicitudes"; $base="../"; $ok=$_GET['ok']??'';
$id=(int)$_SESSION['id'];
$stmt=$conexion->prepare('SELECT s.id,s.ticket,s.tipo_solicitud,s.asunto,s.descripcion,s.estado,s.fecha,(SELECT r.estado FROM reparaciones r WHERE r.solicitud_id=s.id ORDER BY r.id DESC LIMIT 1) AS estado_reparacion FROM solicitudes s WHERE s.usuario_id=? ORDER BY s.id DESC'); $stmt->bind_param("i",$id); $stmt->execute(); $datos=$stmt->get_result();
$clasesEstado = [
    'Pendiente' => 'status-pending',
    'En proceso' => 'status-pending',
    'Aprobada' => 'status-approved',
    'Aprobada reparación' => 'status-approved',
    'Completada' => 'status-resolved',
    'Resuelta' => 'status-resolved',
    'Rechazada' => 'status-cancelled',
    'Cancelada' => 'status-cancelled',
];
require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content"><h2>Mis solicitudes</h2>
<?php if($ok): ?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?>
<div class="card table-card p-3"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Ticket</th><th>Asunto</th><th>Tipo</th><th>Estado</th><th>Reparación</th><th>Fecha</th><th>Acción</th></tr></thead><tbody>
<?php while($r=$datos->fetch_assoc()): $estadoActual=trim((string)($r['estado'] ?? '')); if($estadoActual==='') $estadoActual='Pendiente'; ?><tr><td><strong><?=e($r['ticket'])?></strong></td><td><?=e($r['asunto'])?></td><td><?=e($r['tipo_solicitud'])?></td><td><span class="badge status-badge <?=e($clasesEstado[$estadoActual] ?? 'status-default')?>"><?=e($estadoActual)?></span></td><td><?php if(!empty($r['estado_reparacion'])): ?><span class="badge status-badge <?=e(['Reparado y entregado'=>'status-resolved','Entregado'=>'status-resolved','Reparado'=>'status-approved','No reparable'=>'equipment-retired'][$r['estado_reparacion']] ?? 'status-pending')?>"><?=e($r['estado_reparacion'])?></span><?php else: ?>—<?php endif; ?></td><td><?=e($r['fecha'])?></td><td><a class="btn btn-sm btn-warning" href="ver_solicitud.php?id=<?=e($r['id'])?>" title="Ver detalle" aria-label="Ver detalle"><i class="bi bi-eye-fill"></i></a></td></tr><?php endwhile; ?>
</tbody></table></div></div></main>
<?php require "../includes/footer.php"; ?>