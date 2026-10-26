<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$titulo="Gestión de usuarios";$base="../../";$ok=$_GET['ok']??'';$error=$_GET['error']??'';
$buscar=trim($_GET['buscar']??'');
if ($buscar !== '') {
    $like = '%' . $buscar . '%';
    $stmt = $conexion->prepare("SELECT id,rut,nombre,apellido,correo,cargo,area,lugar,ubicacion,rol,estado,fecha_registro FROM usuarios WHERE rut LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR ubicacion LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $datos = $stmt->get_result();
} else {
    $datos=$conexion->query("SELECT id,rut,nombre,apellido,correo,cargo,area,lugar,ubicacion,rol,estado,fecha_registro FROM usuarios ORDER BY id DESC");
}
require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><div class="d-flex justify-content-between align-items-center mb-3"><h2>Gestión de usuarios</h2><a class="btn btn-primary" href="usuario_nuevo.php">+ Nuevo usuario</a></div>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-6 col-lg-4">
        <input type="text" name="buscar" class="form-control" placeholder="Buscar por RUT, nombre, correo u oficina" value="<?=e($buscar)?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Buscar</button>
        <?php if($buscar !== ''): ?><a href="usuarios.php" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
    </div>
</form>
<div class="card table-card p-3"><div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>ID</th><th>RUT</th><th>Nombre</th><th>Correo institucional</th><th>Área</th><th>Lugar</th><th>Ubicación</th><th>Rol</th><th>Estado</th><th>Fecha de registro</th><th>Acciones</th></tr></thead><tbody>
<?php $hayFilas = false; while($r=$datos->fetch_assoc()): $hayFilas = true; ?><tr>
<td><?=e($r['id'])?></td><td><?=e($r['rut'])?></td><td><?=e($r['nombre'].' '.$r['apellido'])?></td><td><?=e($r['correo'])?></td><td><?=e($r['area'])?></td><td><?=e($r['lugar'])?></td><td><?=e($r['ubicacion'])?></td>
<td><span class="badge <?= ['admin'=>'text-bg-danger','tecnico'=>'text-bg-warning'][$r['rol']] ?? 'text-bg-primary' ?>"><?=e(['admin'=>'admin','tecnico'=>'técnico de soporte'][$r['rol']] ?? $r['rol'])?></span></td>
<td><span class="badge <?= $r['estado']==='activo'?'text-bg-success':($r['estado']==='inactivo'?'text-bg-secondary':'text-bg-danger') ?>"><?=e(ucfirst($r['estado']))?></span></td>
<td><?=e(date('d-m-Y H:i', strtotime($r['fecha_registro'])))?></td>
<td class="text-nowrap"><div class="d-flex gap-2 align-items-center"><a class="btn btn-sm btn-warning btn-icon-action" href="usuario_ver.php?id=<?=e($r['id'])?>" title="Ver usuario e historial" aria-label="Ver usuario e historial"><i class="bi bi-eye-fill" aria-hidden="true"></i></a><a class="btn btn-sm btn-primary btn-icon-action" href="usuario_editar.php?id=<?=e($r['id'])?>" title="Editar" aria-label="Editar usuario"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
<?php if((int)$r['id'] !== (int)$_SESSION['id']): ?><a data-confirm="¿Eliminar a <?=e($r['nombre'].' '.$r['apellido'])?>? Se borrarán su cuenta y todo su historial de solicitudes y prestaciones. Los equipos asignados quedarán sin usuario y el historial de esos equipos se conservará." data-confirm-second="Confirmación final: ¿Eliminar definitivamente a <?=e($r['nombre'].' '.$r['apellido'])?> y su historial? Esta acción no se puede deshacer." class="btn btn-sm btn-danger btn-icon-action" href="usuario_eliminar.php?id=<?=e($r['id'])?>" title="Eliminar" aria-label="Eliminar usuario"><i class="bi bi-trash3-fill" aria-hidden="true"></i></a><?php endif;?></div></td>
</tr><?php endwhile;?>
<?php if(!$hayFilas): ?><tr><td colspan="11" class="text-center text-muted">No se encontraron usuarios que coincidan con la búsqueda.</td></tr><?php endif; ?>
</tbody></table></div></div></main>
<?php require "../../includes/footer.php"; ?>