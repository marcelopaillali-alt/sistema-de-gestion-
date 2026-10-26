<?php
require_once "../../includes/auth.php"; exigir_rol('admin'); $titulo="Nuevo usuario";$base="../../";$error=$_GET['error']??'';
require_once "../../includes/lugares.php";
require_once "../../includes/areas.php";
require_once "../../includes/formulario.php";
$f = $error !== '' ? recuperar_formulario('usuario_nuevo') : [];
if(($f['area'] ?? '')==='__nueva__'){$f['area_nueva_val']=$f['area_nueva'] ?? '';}
$v = fn($c) => e($f[$c] ?? '');
$sel = fn($c,$val) => (($f[$c] ?? '')===$val) ? 'selected' : '';
require "../../includes/header.php"; ?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content"><h2>Crear usuario</h2><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="card table-card p-4"><form action="usuario_guardar.php" method="POST" novalidate data-validar-vacios><div class="row">
<div class="col-md-6 mb-3"><label>RUT</label><input name="rut" class="form-control" value="<?= $v('rut') ?>" required></div><div class="col-md-6 mb-3"><label>Correo institucional</label><input type="email" name="correo" class="form-control" value="<?= $v('correo') ?>" required></div>
<div class="col-md-6 mb-3"><label>Nombre</label><input name="nombre" class="form-control" value="<?= $v('nombre') ?>" required></div><div class="col-md-6 mb-3"><label>Apellido</label><input name="apellido" class="form-control" value="<?= $v('apellido') ?>" required></div>
<div class="col-md-6 mb-3"><label>Cargo</label><input name="cargo" class="form-control" value="<?= $v('cargo') ?>"></div><div class="col-md-6 mb-3"><label>Área</label><select name="area" id="area_select" class="form-select" onchange="document.getElementById('area_nueva_wrap').style.display = this.value === '__nueva__' ? 'block' : 'none';">
    <option value="">Seleccione...</option>
    <?php foreach ($AREAS_DEPARTAMENTOS as $a): ?>
        <option value="<?=e($a)?>" <?=$sel('area',$a)?>><?=e($a)?></option>
    <?php endforeach; ?>
    <option value="__nueva__" <?=$sel('area','__nueva__')?>>+ Agregar nueva área...</option>
</select>
<div id="area_nueva_wrap" style="display:<?= ($f['area'] ?? '')==='__nueva__' ? 'block' : 'none' ?>;" class="mt-2">
    <input type="text" name="area_nueva" class="form-control" value="<?= $v('area_nueva') ?>" placeholder="Escriba el nombre de la nueva área">
</div>
</div>
<div class="col-md-6 mb-3"><label>Lugar o establecimiento</label><select name="lugar" class="form-select">
    <option value="">Seleccione...</option>
    <?php foreach ($LUGARES_ESTABLECIMIENTOS as $l): ?>
        <option value="<?=e($l)?>" <?=$sel('lugar',$l)?>><?=e($l)?></option>
    <?php endforeach; ?>
</select></div>
<div class="col-md-6 mb-3"><label>Ubicación u oficina</label><input name="ubicacion" class="form-control" value="<?= $v('ubicacion') ?>"></div>
<div class="col-md-6 mb-3"><label for="contrasena">Contraseña</label><input id="contrasena" type="password" name="contrasena" minlength="6" class="form-control" autocomplete="new-password" required></div>
<div class="col-md-6 mb-3"><label for="repetir_contrasena">Confirmar contraseña</label><input id="repetir_contrasena" type="password" name="repetir_contrasena" minlength="6" class="form-control" autocomplete="new-password" required></div>
<div class="col-md-6 mb-3"><label for="pin_recuperacion">PIN de recuperación (6 dígitos)</label><input id="pin_recuperacion" type="password" name="pin_recuperacion" class="form-control" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="off" required></div>
<div class="col-md-6 mb-3"><label>Rol</label><select name="rol" class="form-select"><option value="usuario" <?=$sel('rol','usuario')?>>Usuario</option><option value="tecnico" <?=$sel('rol','tecnico')?>>Técnico de soporte</option><option value="admin" <?=$sel('rol','admin')?>>Administrador</option></select></div>
<div class="col-md-6 mb-3"><label>Estado</label><select name="estado" class="form-select"><option value="activo" <?=$sel('estado','activo')?>>Activo</option><option value="inactivo" <?=$sel('estado','inactivo')?>>Inactivo</option><option value="bloqueado" <?=$sel('estado','bloqueado')?>>Bloqueado</option></select></div>
</div><button class="btn btn-primary">Guardar</button> <a class="btn btn-secondary" href="usuarios.php">Cancelar</a></form></div></main>
<?php require "../../includes/footer.php"; ?>