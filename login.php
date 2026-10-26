<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}
$error = $_GET['error'] ?? '';
$ok = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | Sistema de Soporte</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/estilo.css?v=<?= filemtime(__DIR__ . '/assets/css/estilo.css') ?>">
</head>
<body class="login-body">
<main class="login-card">
    <div class="login-brand">
        <div class="login-logo"><img src="media/logo2.png" alt=""></div>
        <h1>Sistema de Soporte</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
        <div class="alert alert-success py-2" role="status"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form action="validar_login.php" method="POST">
        <div class="mb-3">
            <label class="form-label" for="correo">Correo institucional</label>
            <div class="input-group login-input">
                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                <input id="correo" type="email" name="correo" class="form-control" required autocomplete="email" autofocus>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="contrasena">Contraseña</label>
            <div class="input-group login-input">
                <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                <input id="contrasena" type="password" name="contrasena" class="form-control" required autocomplete="current-password">
            </div>
        </div>
        <button class="btn btn-primary w-100 login-submit"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Ingresar</button>
    </form>

    <div class="login-links text-center mt-3">
        <a href="recuperar_contrasena.php" class="d-block mb-2">¿Olvidaste tu contraseña?</a>
       <!-- <a href="registro.php">Crear una cuenta de usuario</a> -->
    </div>
</main>
</body>
</html>