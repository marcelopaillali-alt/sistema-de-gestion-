<?php
require_once "../../conexion.php"; require_once "../../includes/auth.php"; exigir_rol('admin');
$id=(int)($_GET['id']??0);$error=$_GET['error']??'';$stmt=$conexion->prepare("SELECT * FROM usuarios WHERE id=?");$stmt->bind_param("i",$id);$stmt->execute();$u=$stmt->get_result()->fetch_assoc();
if(!$u){header("Location: usuarios.php?error=Usuario no encontrado.");exit;}
$titulo="Editar usuario";$base="../../";require_once "../../includes/lugares.php";require_once "../../includes/areas.php";require_once "../../includes/formulario.php";
if($error!==''){$f=recuperar_formulario('usuario_editar');if(($f['area'] ?? '')==='__nueva__'){$f['area']=trim($f['area_nueva'] ?? '');}foreach(['rut','correo','nombre','apellido','cargo','area','lugar','ubicacion','rol','estado'] as $c){if(isset($f[$c])){$u[$c]=$f[$c];}}}
require "../../includes/header.php"; ?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><h2>Editar usuario</h2><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><div class="card table-card p-4"><form action="usuario_actualizar.php" method="POST" novalidate data-validar-vacios><input type="hidden" name="id" value="<?=e($u['id'])?>"><div class="row">
<div class="col-md-6 mb-3"><label>RUT</label><input name="rut" class="form-control" value="<?=e($u['rut'])?>" required></div><div class="col-md-6 mb-3"><label>Correo institucional</label><input type="email" name="correo" class="form-control" value="<?=e($u['correo'])?>" required></div>
<div class="col-md-6 mb-3"><label>Nombre</label><input name="nombre" class="form-control" value="<?=e($u['nombre'])?>" required></div><div class="col-md-6 mb-3"><label>Apellido</label><input name="apellido" class="form-control" value="<?=e($u['apellido'])?>" required></div>
<div class="col-md-6 mb-3"><label>Cargo</label><input name="cargo" class="form-control" value="<?=e($u['cargo'])?>"></div><div class="col-md-6 mb-3"><label>Área</label><select name="area" id="area_select" class="form-select" onchange="document.getElementById('area_nueva_wrap').style.display = this.value === '__nueva__' ? 'block' : 'none';">
    <option value="">Seleccione...</option>
    <?php foreach ($AREAS_DEPARTAMENTOS as $a): ?>
        <option value="<?=e($a)?>" <?=$u['area']===$a?'selected':''?>><?=e($a)?></option>
    <?php endforeach; ?>
    <?php if ($u['area'] !== '' && $u['area'] !== null && !in_array($u['area'], $AREAS_DEPARTAMENTOS, true)): ?>
        <option value="<?=e($u['area'])?>" selected><?=e($u['area'])?></option>
    <?php endif; ?>
    <option value="__nueva__">+ Agregar nueva área...</option>
</select>
<div id="area_nueva_wrap" style="display:none;" class="mt-2">
    <input type="text" name="area_nueva" class="form-control" placeholder="Escriba el nombre de la nueva área">
</div>
</div>
<div class="col-md-6 mb-3"><label>Lugar o establecimiento</label><select name="lugar" class="form-select">
    <option value="">Seleccione...</option>
    <?php foreach ($LUGARES_ESTABLECIMIENTOS as $l): ?>
        <option value="<?=e($l)?>" <?=$u['lugar']===$l?'selected':''?>><?=e($l)?></option>
    <?php endforeach; ?>
    <?php if ($u['lugar'] !== '' && $u['lugar'] !== null && !in_array($u['lugar'], $LUGARES_ESTABLECIMIENTOS, true)): ?>
        <option value="<?=e($u['lugar'])?>" selected><?=e($u['lugar'])?></option>
    <?php endif; ?>
</select></div>
<div class="col-md-6 mb-3"><label>Ubicación u oficina</label><input name="ubicacion" class="form-control" value="<?=e($u['ubicacion'])?>"></div>
<div class="col-md-6 mb-3"><label>Rol</label><select name="rol" class="form-select"><option value="usuario" <?=$u['rol']==='usuario'?'selected':''?>>Usuario</option><option value="tecnico" <?=$u['rol']==='tecnico'?'selected':''?>>Técnico de soporte</option><option value="admin" <?=$u['rol']==='admin'?'selected':''?>>Administrador</option></select></div>
<div class="col-md-6 mb-3"><label>Estado</label><select name="estado" class="form-select"><option value="activo" <?=$u['estado']==='activo'?'selected':''?>>Activo</option><option value="inactivo" <?=$u['estado']==='inactivo'?'selected':''?>>Inactivo</option><option value="bloqueado" <?=$u['estado']==='bloqueado'?'selected':''?>>Bloqueado</option></select></div>
<div class="col-md-6 mb-3"><label>Nueva contraseña</label><input type="password" name="contrasena" data-opcional minlength="6" class="form-control" placeholder="Dejar vacío para no cambiar"></div>
<div class="col-md-6 mb-3"><label>Repetir contraseña</label><input type="password" name="repetir_contrasena" data-opcional minlength="6" class="form-control" placeholder="Repetir nueva contraseña"></div>
<div class="col-md-6 mb-3"><label for="pin_recuperacion">PIN de recuperación (6 dígitos)</label><input id="pin_recuperacion" type="password" name="pin_recuperacion" data-opcional class="form-control" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="off" placeholder="Dejar vacío para mantener el actual"><div class="form-text">El nuevo PIN reemplazará el anterior. Entrégueselo al usuario por un canal seguro.</div></div>
</div><button class="btn btn-primary">Actualizar</button> <a class="btn btn-secondary" href="usuarios.php">Cancelar</a></form></div></main>
<?php require "../../includes/footer.php"; ?>