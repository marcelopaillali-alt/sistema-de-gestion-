<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$titulo="Prestaciones";$base="../../";$ok=$_GET['ok']??'';$error=$_GET['error']??'';
$datos=$conexion->query("SELECT p.*, CONCAT(u.nombre,' ',u.apellido) usuario, e.codigo_equipo, e.numero_serie FROM prestaciones p JOIN usuarios u ON u.id=p.usuario_id LEFT JOIN equipos e ON e.id=p.equipo_id ORDER BY p.id DESC");
$usuarios=$conexion->query("SELECT id,nombre,apellido FROM usuarios WHERE estado='activo' ORDER BY nombre,apellido");
$equipos=$conexion->query("SELECT e.id,e.codigo_equipo,e.numero_serie,e.marca,e.modelo,e.tipo FROM equipos e WHERE e.disponible_prestamo=1 AND e.estado='Disponible' AND e.usuario_id IS NULL AND NOT EXISTS (SELECT 1 FROM prestaciones p WHERE p.equipo_id=e.id AND p.estado<>'Devuelto') ORDER BY e.codigo_equipo");
$clasesEstado = [
    'Pendiente' => 'status-pending',
    'Entregado' => 'status-approved',
    'Devuelto' => 'status-resolved',
    'Atrasado' => 'status-cancelled',
];
require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><h2>Prestaciones</h2>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="card table-card p-4 mb-4"><h5>Registrar prestación</h5><form action="prestacion_guardar.php" method="POST"><div class="row">
<div class="col-md-6 mb-3"><label class="form-label" for="usuario_id">Usuario</label><select id="usuario_id" name="usuario_id" class="form-select" required><option value="">Seleccione usuario</option><?php while($u=$usuarios->fetch_assoc()):?><option value="<?=e($u['id'])?>"><?=e($u['nombre'].' '.$u['apellido'])?></option><?php endwhile;?></select></div>
<div class="col-md-6 mb-3"><label class="form-label" for="equipo_id">Equipo disponible</label><select id="equipo_id" name="equipo_id" class="form-select" required><option value="">Seleccione equipo</option><?php while($eq=$equipos->fetch_assoc()):$descripcion=trim($eq['codigo_equipo'].' - '.$eq['marca'].' '.$eq['modelo'].' '.$eq['tipo']);$serie=$eq['numero_serie']?:$eq['codigo_equipo'];?><option value="<?=e($eq['id'])?>" data-serie="<?=e($serie)?>"><?=e($descripcion.($eq['numero_serie']?' (Serie: '.$eq['numero_serie'].')':''))?></option><?php endwhile;?></select></div>
<div class="col-md-6 mb-3"><label class="form-label" for="fecha_pedido">Fecha de préstamo</label><input id="fecha_pedido" type="date" class="form-control" value="<?=e(date('Y-m-d'))?>" readonly aria-readonly="true"></div>
<div class="col-md-6 mb-3"><label class="form-label" for="fecha_devolucion">Fecha de devolución (automática)</label><input id="fecha_devolucion" type="date" class="form-control" value="<?=e(date('Y-m-d', strtotime('+2 days')) )?>" readonly aria-readonly="true"><div class="form-text">.</div></div>
<?php if($equipos->num_rows===0):?><div class="col-12"><div class="form-text mb-3">No hay equipos disponibles para préstamo.</div></div><?php endif;?>
<div class="col-12"><button class="btn btn-primary">Registrar préstamo</button></div>
</div></form></div>
<div class="card table-card p-3"><div class="table-responsive"><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Código prestación</th><th>Usuario</th><th>Equipo</th><th>Fecha de préstamo</th><th>Fecha devolución</th><th>Estado</th><th>Serie equipo</th><th>Acción</th></tr></thead><tbody>
<?php while($r=$datos->fetch_assoc()):$equipoPrestado=trim(($r['codigo_equipo']??'').' '.($r['tipo_equipo']??''));?><tr><td><?=e($r['id'])?></td><td><strong><?=e($r['ticket'])?></strong></td><td><?=e($r['usuario'])?></td><td><?=e($equipoPrestado?:$r['tipo_equipo'])?></td><td><?=e($r['fecha_pedido'])?></td><td><?=e($r['fecha_devolucion']?:'-')?></td><td><span class="badge status-badge <?=e($clasesEstado[$r['estado']] ?? 'status-default')?>"><?=e($r['estado'])?></span></td><td><?=e(($r['numero_serie']??'')?:$r['codigo_prestamo'])?></td><td><form method="POST" action="estado_prestacion.php" class="d-flex gap-1"><input type="hidden" name="id" value="<?=e($r['id'])?>"><select name="estado" class="form-select form-select-sm"><?php foreach(['Pendiente','Entregado','Devuelto','Atrasado'] as $op): ?><option <?= $r['estado']===$op?'selected':'' ?>><?=e($op)?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary">OK</button></form></td></tr><?php endwhile;?>
</tbody></table></div></div></main>
<?php require "../../includes/footer.php"; ?>