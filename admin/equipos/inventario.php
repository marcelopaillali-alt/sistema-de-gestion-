<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
require_once __DIR__ . "/../../includes/equipos_red.php";
sincronizar_equipos_sin_usuario($conexion);
$titulo="Inventario de equipos";$base="../../";$ok=$_GET['ok']??'';$error=$_GET['error']??'';$busqueda=trim($_GET['buscar']??'');
$estadosFiltro=['Disponible','Asignado','En reparación','Baja'];
$estadoFiltro=in_array($_GET['estado']??'',$estadosFiltro,true)?$_GET['estado']:'';
$pestana = ($_GET['pestana'] ?? '') === 'agregar' || $error ? 'agregar' : 'inventario';
$sqlEquipos="SELECT e.*, CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,'')) usuario, u.area area_usuario FROM equipos e LEFT JOIN usuarios u ON u.id=e.usuario_id";
$condiciones=[];$tipos='';$params=[];
if($busqueda!==''){
    $filtro="%".$busqueda."%";
    $condiciones[]="(u.rut LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR e.codigo_equipo LIKE ? OR e.numero_serie LIKE ? OR e.estado LIKE ?)";
    $tipos.='ssssss';array_push($params,$filtro,$filtro,$filtro,$filtro,$filtro,$filtro);
}
if($estadoFiltro!==''){$condiciones[]="e.estado=?";$tipos.='s';$params[]=$estadoFiltro;}
if($condiciones){$sqlEquipos.=" WHERE ".implode(" AND ",$condiciones);}
$stmt=$conexion->prepare($sqlEquipos." ORDER BY e.id DESC");
if($params){$stmt->bind_param($tipos,...$params);}
$stmt->execute();$datos=$stmt->get_result();
$usuarios=$conexion->query("SELECT id,nombre,apellido,ubicacion,area FROM usuarios ORDER BY nombre");
$tiposEquipo=$conexion->query("SELECT nombre FROM tipos_equipo ORDER BY nombre");
$clasesEstado = [
    'Asignado' => 'equipment-assigned',
    'Disponible' => 'equipment-available',
    'En reparación' => 'status-pending',
    'Baja' => 'equipment-retired',
];
require "../../includes/header.php"; ?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0">Inventario de equipos</h2><?php if($pestana==='agregar'): ?><a class="btn btn-outline-primary" href="inventario.php">Volver al inventario</a><?php else: ?><a class="btn btn-primary" href="inventario.php?pestana=agregar">Agregar equipo +</a><?php endif; ?></div>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<?php if($pestana==='agregar'): ?>
<div class="card table-card p-4 mb-4"><h5>Agregar equipo</h5><form action="equipo_guardar.php" method="POST"><div class="row">
<div class="col-md-6 mb-3"><label class="form-label" for="codigo_institucional">Código institucional</label><input id="codigo_institucional" name="codigo_institucional" class="form-control" maxlength="50" placeholder="Código institucional"></div><div class="col-md-6 mb-3"><label class="form-label" for="marca">Marca</label><input id="marca" name="marca" class="form-control" placeholder="Marca"></div>
<div class="col-md-6 mb-3"><label class="form-label" for="modelo">Modelo</label><input id="modelo" name="modelo" class="form-control" placeholder="Modelo"></div><div class="col-md-6 mb-3"><label class="form-label" for="tipo_equipo">Tipo de equipo</label><select id="tipo_equipo" name="tipo" class="form-select" data-tipo-equipo required><?php while($tipo=$tiposEquipo->fetch_assoc()):?><option value="<?=e($tipo['nombre'])?>"><?=e($tipo['nombre'])?></option><?php endwhile;?><option value="__nuevo__">Agregar nuevo tipo...</option></select><input name="tipo_nuevo" class="form-control mt-2" data-tipo-equipo-nuevo maxlength="50" placeholder="Escriba el nuevo tipo" hidden disabled></div>
<div class="col-md-6 mb-3"><label class="form-label" for="numero_serie">Número de serie</label><input id="numero_serie" name="numero_serie" class="form-control" placeholder="Número de serie"></div><div class="col-md-6 mb-3"><label class="form-label" for="tipo_propietario">Tipo de propietario</label><select id="tipo_propietario" name="tipo_propietario" class="form-select"><option value="Propio">Propio</option><option value="Comodato">Comodato</option><option value="Arriendo">Arriendo</option></select></div>
<div class="col-md-6 mb-3"><label class="form-label" for="lugar">Lugar</label><input id="lugar" name="lugar" class="form-control" data-lugar-equipo placeholder="Lugar"></div><div class="col-md-6 mb-3"><label class="form-label" for="ip_asignada">Dirección IP</label><input id="ip_asignada" name="ip_asignada" class="form-control" inputmode="decimal" maxlength="15" placeholder="192.168.1.10"></div>
<div class="col-md-6 mb-3"><label class="form-label" for="mac">Dirección MAC</label><input id="mac" name="mac" class="form-control" maxlength="17" placeholder="AA:BB:CC:DD:EE:FF" autocapitalize="characters" spellcheck="false"></div><div class="col-md-6 mb-3"><label class="form-label" for="usuario_id">Usuario asignado</label><select id="usuario_id" name="usuario_id" class="form-select" data-usuario-lugar><option value="">Sin asignar</option><?php while($u=$usuarios->fetch_assoc()):?><option value="<?=e($u['id'])?>" data-ubicacion="<?=e($u['ubicacion'] ?? '')?>" data-area="<?=e($u['area'] ?? '')?>"><?=e($u['nombre'].' '.$u['apellido'])?></option><?php endwhile;?></select></div><div class="col-md-6 mb-3"><label class="form-label" for="area_equipo">Área</label><input id="area_equipo" class="form-control" data-area-equipo value="Sin asignar" readonly></div>
<div class="col-12 mb-2"><?php $paginaTiposEquipo='inventario'; require __DIR__ . '/../../includes/admin_tipos_equipo.php'; ?></div>
<div class="col-md-6 mb-3"><label class="form-label" for="disponible_prestamo">Disponible para préstamo</label><select id="disponible_prestamo" name="disponible_prestamo" class="form-select"><option value="1" selected>Sí</option><option value="0">No</option></select></div><div class="col-md-6 mb-3 d-flex align-items-end justify-content-end gap-2"><button class="btn btn-primary px-4">Agregar equipo</button><a class="btn btn-secondary px-4" href="inventario.php">Cancelar</a></div>
</div></form></div>
<?php else: ?>
<div class="card table-card p-2 mb-3" style="max-width:640px"><form method="GET" class="d-flex gap-2 align-items-center">
<input type="search" name="buscar" class="form-control form-control-sm" value="<?=e($busqueda)?>" placeholder="Buscar por RUT, usuario, código o serie">
<select name="estado" class="form-select form-select-sm" style="max-width:160px" aria-label="Filtrar por estado"><option value="">Todos los estados</option><?php foreach($estadosFiltro as $est):?><option <?=$estadoFiltro===$est?'selected':''?>><?=e($est)?></option><?php endforeach;?></select>
<button class="btn btn-primary btn-sm">Buscar</button><?php if($busqueda!==''||$estadoFiltro!==''): ?><a class="btn btn-secondary btn-sm" href="inventario.php">Limpiar</a><?php endif; ?>
</form></div>
<div class="card table-card p-3"><div class="table-responsive"><table class="table table-hover table-sm"><thead><tr><th>#</th><th>Código</th><th>Equipo</th><th>Propietario</th><th>Serie</th><th>IP</th><th>Usuario</th><th>Área</th><th>Estado</th><th>Disponible para préstamo</th><th>Lugar</th><th>F. adquisición</th><th>Acción</th></tr></thead><tbody>
<?php $contador=1; while($r=$datos->fetch_assoc()):?><tr><td><?=e($contador++)?></td><td><?=e($r['codigo_equipo'])?></td><td><?=e($r['marca'].' '.$r['modelo'].' ('.$r['tipo'].')')?></td><td><?=e($r['tipo_propietario'])?></td><td><?=e($r['numero_serie'])?></td><td><?=e($r['ip_asignada'] ?: 'Sin asignar')?></td><td><?=e(trim($r['usuario']) ?: 'Sin asignar')?></td><td><?=e($r['area_usuario'] ?: '-')?></td><td><span class="badge status-badge <?=e($clasesEstado[$r['estado']] ?? 'status-default')?>"><?=e($r['estado'])?></span></td><td><?=!empty($r['disponible_prestamo'])?'Sí':'No'?></td><td><?=e($r['lugar'])?></td><td><?=e($r['fecha_adquisicion'] ? date('d-m-Y', strtotime($r['fecha_adquisicion'])) : '-')?></td><td class="text-nowrap"><div class="d-flex gap-2 align-items-center"><a class="btn btn-sm btn-warning btn-icon-action" href="equipo_ver.php?id=<?=e($r['id'])?>" title="Ver historial" aria-label="Ver historial del equipo"><i class="bi bi-eye-fill" aria-hidden="true"></i></a><a class="btn btn-sm btn-primary btn-icon-action" href="equipo_editar.php?id=<?=e($r['id'])?>" title="Editar" aria-label="Editar equipo"><i class="bi bi-pencil-square" aria-hidden="true"></i></a><a data-confirm="¿Estás seguro de que deseas eliminar este equipo del inventario?&#10;&#10;Al eliminar este equipo, se borrará toda la información asociada, incluyendo su historial, asignaciones, préstamos, reparaciones y solicitudes relacionadas, si corresponde.&#10;&#10;Esta acción puede ser irreversible." data-confirm-second="¿Está seguro que desea eliminar este equipo (<?=e($r['codigo_equipo'])?>)?&#10;&#10;Esta es la confirmación final y no se puede deshacer." class="btn btn-sm btn-danger btn-icon-action" href="equipo_eliminar.php?id=<?=e($r['id'])?>" title="Eliminar" aria-label="Eliminar equipo"><i class="bi bi-trash3-fill" aria-hidden="true"></i></a></div></td></tr><?php endwhile;?></tbody></table></div></div>
<?php endif; ?>
</main>
<?php require "../../includes/footer.php"; ?>
