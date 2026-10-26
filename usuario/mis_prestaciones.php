<?php
require_once "../conexion.php"; require_once "../includes/auth.php"; exigir_rol('usuario');
$titulo="Mis prestaciones"; $base="../"; $ok=$_GET['ok']??'';
$id=(int)$_SESSION['id'];
$stmt=$conexion->prepare('SELECT p.id,p.ticket,p.tipo_equipo,p.fecha_pedido,p.fecha_devolucion,p.estado,p.codigo_prestamo,e.codigo_equipo,e.numero_serie FROM prestaciones p LEFT JOIN equipos e ON e.id=p.equipo_id WHERE p.usuario_id=? ORDER BY p.id DESC'); $stmt->bind_param("i",$id); $stmt->execute(); $datos=$stmt->get_result();
$clasesEstado = [
    'Pendiente' => 'status-pending',
    'Entregado' => 'status-approved',
    'Devuelto' => 'status-resolved',
    'Atrasado' => 'status-cancelled',
];
require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content"><h2>Mis prestaciones</h2>
<?php if($ok): ?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?>
<div class="card table-card p-3"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>ID</th><th>Código prestación</th><th>Equipo</th><th>Fecha de préstamo</th><th>Fecha devolución</th><th>Estado</th><th>Serie equipo</th></tr></thead><tbody>
<?php while($r=$datos->fetch_assoc()): ?><tr><td><?=e($r['id'])?></td><td><strong><?=e($r['ticket'])?></strong></td><td><?=e(trim(($r['codigo_equipo']??'').' '.($r['tipo_equipo']??''))?:$r['tipo_equipo'])?></td><td><?=e($r['fecha_pedido'])?></td><td><?=e($r['fecha_devolucion']?:'-')?></td><td><span class="badge status-badge <?=e($clasesEstado[$r['estado']] ?? 'status-default')?>"><?=e($r['estado'])?></span></td><td><?=e(($r['numero_serie']??'')?:$r['codigo_prestamo'])?></td></tr><?php endwhile; ?>
</tbody></table></div></div></main>
<?php require "../includes/footer.php"; ?>