<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}
$error = $_GET['error'] ?? '';
require_once "includes/formulario.php";
$f = $error !== '' ? recuperar_formulario('registro') : [];
$v = fn($c) => htmlspecialchars((string)($f[$c] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body class="login-body">
<div class="register-card">
    <h2 class="mb-1">Crear cuenta</h2>
    <p class="text-muted">El registro público crea una cuenta con rol Usuario.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="registrar_usuario.php" method="POST" novalidate data-validar-vacios>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">RUT</label>
                <input type="text" name="rut" class="form-control" value="<?= $v('rut') ?>" placeholder="12.345.678-9" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Correo institucional</label>
                <input type="email" name="correo" class="form-control" value="<?= $v('correo') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= $v('nombre') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Apellido</label>
                <input type="text" name="apellido" class="form-control" value="<?= $v('apellido') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Cargo</label>
                <input type="text" name="cargo" class="form-control" value="<?= $v('cargo') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Área</label>
                <input type="text" name="area" class="form-control" value="<?= $v('area') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lugar o establecimiento</label>
                <input type="text" name="lugar" class="form-control" value="<?= $v('lugar') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Ubicación u oficina</label>
                <input type="text" name="ubicacion" class="form-control" value="<?= $v('ubicacion') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="contrasena" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Repetir contraseña</label>
                <input type="password" name="repetir" class="form-control" minlength="6" required>
            </div>
        </div>
        <button class="btn btn-primary">Registrarse</button>
        <a href="login.php" class="btn btn-outline-secondary">Volver al login</a>
    </form>
</div>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/rut.js?v=<?= filemtime(__DIR__ . '/assets/js/rut.js') ?>"></script>
</body>
</html>