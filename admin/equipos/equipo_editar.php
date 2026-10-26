<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$id=(int)($_GET['id']??0);
$stmt=$conexion->prepare("SELECT * FROM equipos WHERE id=?");$stmt->bind_param("i",$id);$stmt->execute();$equipo=$stmt->get_result()->fetch_assoc();
if(!$equipo){header("Location: inventario.php?error=Equipo no encontrado.");exit;}
$usuarios=$conexion->query("SELECT id,nombre,apellido,ubicacion,area FROM usuarios ORDER BY nombre");
$areaActual='Sin asignar';
if(!empty($equipo['usuario_id'])){$stmtA=$conexion->prepare("SELECT area FROM usuarios WHERE id=?");$stmtA->bind_param("i",$equipo['usuario_id']);$stmtA->execute();$filaA=$stmtA->get_result()->fetch_assoc();$areaActual=($filaA['area'] ?? '')!==''?$filaA['area']:'Sin área';}
$tiposEquipo=$conexion->query("SELECT nombre FROM tipos_equipo ORDER BY nombre");
$idEquipoTipos=$id;$paginaTiposEquipo='editar';
$titulo="Editar equipo";$base="../../";require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><h2>Editar equipo</h2>
<?php if(!empty($_GET['ok'])):?><div class="alert alert-success"><?=e($_GET['ok'])?></div><?php endif;?>
<?php if(!empty($_GET['error'])):?><div class="alert alert-danger"><?=e($_GET['error'])?></div><?php endif;?>
<div class="card table-card p-4"><form action="equipo_actualizar.php" method="POST">
<input type="hidden" name="id" value="<?=e($equipo['id'])?>">
<div class="row">
<div class="col-md-4 mb-3"><label class="form-label">Código equipo</label><input class="form-control" value="<?=e($equipo['codigo_equipo'])?>" disabled></div>
<div class="col-md-4 mb-3"><label class="form-label">Código institucional</label><input name="codigo_institucional" class="form-control" maxlength="50" value="<?=e($equipo['codigo_institucional'] ?? '')?>"></div>
<div class="col-md-4 mb-3"><label class="form-label">Marca</label><input name="marca" class="form-control" value="<?=e($equipo['marca'])?>"></div>
<div class="col-md-4 mb-3"><label class="form-label">Modelo</label><input name="modelo" class="form-control" value="<?=e($equipo['modelo'])?>"></div>
<div class="col-md-4 mb-3"><label class="form-label" for="tipo_equipo">Tipo</label><select id="tipo_equipo" name="tipo" class="form-select" data-tipo-equipo required><?php while($tipo=$tiposEquipo->fetch_assoc()):?><option value="<?=e($tipo['nombre'])?>" <?=$equipo['tipo']===$tipo['nombre']?'selected':''?>><?=e($tipo['nombre'])?></option><?php endwhile;?><option value="__nuevo__">Agregar nuevo tipo...</option></select><input name="tipo_nuevo" class="form-control mt-2" data-tipo-equipo-nuevo maxlength="50" placeholder="Escriba el nuevo tipo" hidden disabled></div>
<div class="col-md-4 mb-3"><label class="form-label">Tipo propietario</label><select name="tipo_propietario" class="form-select"><?php foreach(['Propio','Comodato','Arriendo'] as $tp): ?><option value="<?=e($tp)?>" <?= $equipo['tipo_propietario']===$tp?'selected':'' ?>><?=e($tp)?></option><?php endforeach; ?></select></div>
<div class="col-md-4 mb-3"><label class="form-label">Número de serie</label><input name="numero_serie" class="form-control" value="<?=e($equipo['numero_serie'])?>"></div>
<div class="col-md-4 mb-3"><label class="form-label">Lugar</label><input name="lugar" class="form-control" data-lugar-equipo value="<?=e($equipo['lugar'])?>"></div>
<div class="col-md-4 mb-3"><label class="form-label">IP asignada</label><input name="ip_asignada" class="form-control" inputmode="decimal" maxlength="15" placeholder="192.168.1.10" value="<?=e($equipo['ip_asignada'])?>"></div>
<div class="col-md-4 mb-3"><label class="form-label">MAC</label><input name="mac" class="form-control" maxlength="17" placeholder="AA:BB:CC:DD:EE:FF" autocapitalize="characters" spellcheck="false" value="<?=e($equipo['mac'])?>"></div>
<div class="col-md-4 mb-3"><label class="form-label" for="disponible_prestamo">Disponible para préstamo</label><select id="disponible_prestamo" name="disponible_prestamo" class="form-select"><option value="1" <?=!empty($equipo['disponible_prestamo'])?'selected':''?>>Sí</option><option value="0" <?=empty($equipo['disponible_prestamo'])?'selected':''?>>No</option></select></div>
<div class="col-md-4 mb-3"><label class="form-label">Usuario</label><select name="usuario_id" class="form-select" data-usuario-lugar><option value="">Sin asignar</option><?php while($u=$usuarios->fetch_assoc()): ?><option value="<?=e($u['id'])?>" data-ubicacion="<?=e($u['ubicacion'] ?? '')?>" data-area="<?=e($u['area'] ?? '')?>" <?= (int)$equipo['usuario_id']===(int)$u['id']?'selected':'' ?>><?=e($u['nombre'].' '.$u['apellido'])?></option><?php endwhile; ?></select></div>
<div class="col-md-4 mb-3"><label class="form-label" for="area_equipo">Área</label><input id="area_equipo" class="form-control" data-area-equipo value="<?=e($areaActual)?>" readonly><div class="form-text">Se completa según el usuario asignado.</div></div>
<div class="col-md-4 mb-3"><label class="form-label" for="estado_equipo">Estado</label><select id="estado_equipo" name="estado" class="form-select"><?php foreach(['Disponible','Asignado','En reparación','Baja'] as $estado): ?><option <?= $equipo['estado']===$estado?'selected':'' ?>><?=e($estado)?></option><?php endforeach; ?></select><div class="form-text">Si el equipo está asignado a un usuario, puede cambiar a «En reparación» o «Baja» sin quitar la asignación.</div></div>
<div class="col-md-4 mb-3"><label class="form-label">Fecha de adquisición</label><input class="form-control" value="<?=e($equipo['fecha_adquisicion'] ? date('d-m-Y', strtotime($equipo['fecha_adquisicion'])) : '-')?>" disabled></div>
<div class="col-md-4 mb-3"><label class="form-label">Última modificación</label><input class="form-control" value="<?=e($equipo['ultima_modificacion'] ? date('d-m-Y H:i', strtotime($equipo['ultima_modificacion'])) : '-')?>" disabled></div>
<div class="col-md-4 mb-3"><label class="form-label" for="accion_admin">Acción realizada por el administrador</label><textarea id="accion_admin" name="accion_admin" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Ej: Se formateó el equipo"></textarea><div class="form-text">Se registra en el historial.</div></div>
<div class="col-12 mb-3"><?php require __DIR__ . '/../../includes/admin_tipos_equipo.php'; ?></div>
</div>
<button class="btn btn-primary">Actualizar</button> <a class="btn btn-secondary" href="inventario.php">Cancelar</a>
</form></div>
</main>
<?php require "../../includes/footer.php"; ?>
