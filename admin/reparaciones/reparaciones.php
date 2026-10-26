<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$titulo="Reparaciones";$base="../../";$ok=$_GET['ok']??'';$error=$_GET['error']??'';
$datos=$conexion->query('SELECT r.*, e.codigo_equipo, e.codigo_institucional, s.ticket FROM reparaciones r LEFT JOIN equipos e ON e.id=r.equipo_id LEFT JOIN solicitudes s ON s.id=r.solicitud_id ORDER BY r.id DESC');
require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><h2>Reparaciones</h2>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="card table-card p-4 mb-4"><h5>Ingresar reparación</h5><form action="reparacion_guardar.php" method="POST"><div class="row">
<div class="col-md-4 mb-2"><select name="equipo_id" class="form-select" required><option value="">Equipo...</option><?php $eq=$conexion->query("SELECT e.id,e.codigo_equipo,e.codigo_institucional,e.marca,e.modelo,e.estado,e.lugar,TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,''))) usuario,u.area,u.ubicacion FROM equipos e LEFT JOIN usuarios u ON u.id=e.usuario_id ORDER BY e.codigo_equipo");while($equi=$eq->fetch_assoc()):?><option value="<?=e($equi['id'])?>" data-usuario="<?=e($equi['usuario'] ?: 'Sin asignar')?>" data-area="<?=e($equi['area'] ?: '—')?>" data-ubicacion="<?=e($equi['ubicacion'] ?: ($equi['lugar'] ?: '—'))?>" data-estado="<?=e($equi['estado'])?>"><?=e($equi['codigo_equipo'].($equi['codigo_institucional']?' ('.$equi['codigo_institucional'].')':'').' - '.$equi['marca'].' '.$equi['modelo'])?></option><?php endwhile;?></select></div>
<div class="col-md-8 mb-2"><input name="problema" class="form-control" placeholder="Problema" required></div>
<div class="col-md-3 mb-2"><label class="form-label small">Usuario asignado</label><input id="rep_usuario" class="form-control" value="Automático" disabled></div><div class="col-md-3 mb-2"><label class="form-label small">Área</label><input id="rep_area" class="form-control" value="Automático" disabled></div><div class="col-md-3 mb-2"><label class="form-label small">Ubicación</label><input id="rep_ubicacion" class="form-control" value="Automático" disabled></div><div class="col-md-3 mb-2"><label class="form-label small">Estado del equipo</label><input id="rep_estado" class="form-control" value="Automático" disabled></div>
<div class="col-12 mb-2"><textarea name="diagnostico" class="form-control" placeholder="Diagnóstico"></textarea></div>
<div class="col-12"><button class="btn btn-primary">Registrar reparación</button></div></div></form></div>
<div class="card table-card p-3"><div class="table-responsive"><table class="table table-hover table-sm"><thead><tr><th>Código reparación</th><th>Ticket de solicitud</th><th>Equipo</th><th>Cód. institucional</th><th>Problema</th><th>Diagnóstico</th><th>Solución</th><th>Ingreso</th><th>Estado</th><th>Cambiar estado</th><th>Editar</th></tr></thead><tbody>
<?php $clasesReparacion=['Ingresado'=>'status-pending','Diagnóstico'=>'status-pending','En reparación'=>'status-pending','Reparado'=>'status-approved','Entregado'=>'status-resolved','Reparado y entregado'=>'status-resolved','No reparable'=>'equipment-retired','Bueno'=>'status-approved'];
while($r=$datos->fetch_assoc()):
    $opciones=['En reparación','Reparado y entregado','No reparable','Bueno'];
    if(!in_array($r['estado'],$opciones,true)) array_unshift($opciones,$r['estado']);
?><tr><td><strong>REP-<?=e(str_pad((string)$r['id'], 5, '0', STR_PAD_LEFT))?></strong></td><td><?=e($r['ticket'] ?: '—')?></td><td><?=e($r['codigo_equipo'] ?: 'Sin equipo asociado')?></td><td><?=e($r['codigo_institucional'] ?: '—')?></td><td><?=e($r['problema'])?></td><td><?=e($r['diagnostico'])?></td><td><?=e($r['solucion'])?></td><td><?=e($r['fecha_ingreso'])?></td><td><span class="badge status-badge <?=e($clasesReparacion[$r['estado']] ?? 'status-default')?>"><?=e($r['estado'])?></span></td><td><form method="POST" action="reparacion_estado.php" class="d-flex gap-1"><input type="hidden" name="id" value="<?=e($r['id'])?>"><select name="estado" class="form-select form-select-sm" aria-label="Cambiar estado de la reparación <?=e($r['id'])?>"><?php foreach($opciones as $op): ?><option <?= $r['estado']===$op?'selected':'' ?>><?=e($op)?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary">OK</button></form></td><td><a class="btn btn-sm btn-primary" href="reparacion_editar.php?id=<?=e($r['id'])?>" title="Editar reparación" aria-label="Editar reparación"><i class="bi bi-pencil-square" aria-hidden="true"></i></a></td></tr><?php endwhile;?>
</tbody></table></div></div></main>
<?php require "../../includes/footer.php"; ?>
<script>
(function(){var s=document.querySelector('select[name="equipo_id"]');if(!s)return;
function f(){var o=s.options[s.selectedIndex],v=o&&o.value!=='';
[['usuario','rep_usuario'],['area','rep_area'],['ubicacion','rep_ubicacion'],['estado','rep_estado']].forEach(function(p){document.getElementById(p[1]).value=v?o.dataset[p[0]]:'Automático';});}
s.addEventListener('change',f);f();})();
</script>
