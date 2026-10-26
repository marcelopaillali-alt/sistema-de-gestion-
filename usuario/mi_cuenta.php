<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');
$titulo="Mi cuenta"; $base="../";
$mensaje = $_GET['ok'] ?? ''; $error = $_GET['error'] ?? '';
require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content">
<h2>Mi cuenta</h2>
<?php if($mensaje): ?><div class="alert alert-success"><?=e($mensaje)?></div><?php endif;?>
<?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="card table-card p-4">
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">RUT</label><input class="form-control" value="<?=e($_SESSION['rut'])?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Correo institucional</label><input class="form-control" type="email" value="<?=e($_SESSION['correo'])?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Nombre</label><input class="form-control" value="<?=e($_SESSION['nombre'])?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Apellido</label><input class="form-control" value="<?=e($_SESSION['apellido'])?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Cargo</label><input class="form-control" value="<?=e($_SESSION['cargo'])?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Área</label><input class="form-control" value="<?=e($_SESSION['area'])?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Lugar o establecimiento</label><input class="form-control" value="<?=e($_SESSION['lugar'] ?? '')?>" disabled></div>
<div class="col-md-6 mb-3"><label class="form-label">Ubicación u oficina</label><input class="form-control" value="<?=e($_SESSION['ubicacion'] ?? '')?>" disabled></div>
</div>
<p class="text-muted small mb-0">Estos datos son administrados por el equipo de soporte. Si necesita corregir alguno, contacte a un administrador.</p>
<hr>
<h5>Cambiar contraseña</h5>
<form action="cambiar_password.php" method="POST">
<div class="row"><div class="col-md-4 mb-3"><input type="password" name="actual" class="form-control" placeholder="Contraseña actual" required></div>
<div class="col-md-4 mb-3"><input type="password" name="nueva" class="form-control" placeholder="Nueva contraseña" minlength="6" required></div>
<div class="col-md-4 mb-3"><input type="password" name="repetir" class="form-control" placeholder="Repetir contraseña" minlength="6" required></div></div>
<button class="btn btn-outline-primary">Cambiar contraseña</button>
</form>
</div>
</main>
<?php require "../includes/footer.php"; ?>