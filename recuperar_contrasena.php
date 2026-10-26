<?php
session_start();

if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

require_once "conexion.php";

if (empty($_SESSION['csrf_recuperacion'])) {
    $_SESSION['csrf_recuperacion'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_recuperacion'], $csrf)) {
        header("Location: recuperar_contrasena.php?error=La solicitud expiró. Intente nuevamente.");
        exit;
    }

    $correo = $_POST['correo'] ?? '';
    $pin = $_POST['pin'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $repetir = $_POST['repetir_contrasena'] ?? '';

    if (!is_string($correo) || !is_string($pin) || !preg_match('/^[0-9]{6}$/', $pin)) {
        header("Location: recuperar_contrasena.php?error=Ingrese un correo válido y un PIN numérico de 6 dígitos.");
        exit;
    }
    $correo = trim($correo);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header("Location: recuperar_contrasena.php?error=Ingrese un correo válido y un PIN numérico de 6 dígitos.");
        exit;
    }
    if (!is_string($contrasena) || !is_string($repetir) || strlen($contrasena) < 6 || $contrasena !== $repetir) {
        header("Location: recuperar_contrasena.php?error=Las contraseñas deben coincidir y tener al menos 6 caracteres.");
        exit;
    }

    $conexion->begin_transaction();
    $stmt = $conexion->prepare(
        "SELECT id, recovery_pin_hash, recovery_pin_attempts, recovery_pin_locked_until > NOW() AS bloqueado
         FROM usuarios
         WHERE correo = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $cuenta = $stmt->get_result()->fetch_assoc();

    if (!$cuenta || (int)$cuenta['bloqueado'] === 1 || empty($cuenta['recovery_pin_hash']) || !password_verify($pin, $cuenta['recovery_pin_hash'])) {
        if ($cuenta && (int)$cuenta['bloqueado'] !== 1) {
            $intentos = (int)$cuenta['recovery_pin_attempts'] + 1;
            $actualizarIntentos = $conexion->prepare(
                "UPDATE usuarios
                 SET recovery_pin_attempts = ?,
                     recovery_pin_locked_until = IF(? >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), recovery_pin_locked_until)
                 WHERE id = ?"
            );
            $actualizarIntentos->bind_param("iii", $intentos, $intentos, $cuenta['id']);
            $actualizarIntentos->execute();
        }
        $conexion->commit();
        header("Location: recuperar_contrasena.php?error=No se pudo validar el correo y el PIN. Revise los datos o contacte al administrador.");
        exit;
    }

    $hashContrasena = password_hash($contrasena, PASSWORD_DEFAULT);
    $actualizar = $conexion->prepare(
        "UPDATE usuarios
         SET contrasena = ?, recovery_pin_attempts = 0, recovery_pin_locked_until = NULL
         WHERE id = ?"
    );
    $actualizar->bind_param("si", $hashContrasena, $cuenta['id']);
    $actualizar->execute();
    $conexion->commit();

    unset($_SESSION['csrf_recuperacion']);
    header("Location: login.php?ok=Contraseña actualizada. Ya puede iniciar sesión.");
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña | Sistema de Soporte</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/estilo.css?v=<?= filemtime(__DIR__ . '/assets/css/estilo.css') ?>">
</head>
<body class="login-body">
<main class="login-card">
    <div class="login-brand">
        <div class="login-logo"><img src="media/logo2.png" alt=""></div>
        <h1>Recuperar contraseña</h1>
        <p class="text-secondary mt-2 mb-0">Ingrese el correo de su cuenta, el PIN de 6 dígitos y una nueva contraseña.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="recuperar_contrasena.php" method="POST">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_recuperacion'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
            <label class="form-label" for="correo">Correo institucional</label>
            <div class="input-group login-input">
                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                <input id="correo" type="email" name="correo" class="form-control" required autocomplete="email">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="pin">PIN de recuperación</label>
            <div class="input-group login-input">
                <span class="input-group-text"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                <input id="pin" type="password" name="pin" class="form-control" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" required autocomplete="one-time-code" aria-describedby="pin-ayuda">
            </div>
            <div id="pin-ayuda" class="form-text">Debe solicitar el PIN al administrador si aún no lo tiene.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="contrasena">Nueva contraseña</label>
            <div class="input-group login-input">
                <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                <input id="contrasena" type="password" name="contrasena" class="form-control" minlength="6" required autocomplete="new-password">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="repetir_contrasena">Repetir nueva contraseña</label>
            <div class="input-group login-input">
                <span class="input-group-text"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>
                <input id="repetir_contrasena" type="password" name="repetir_contrasena" class="form-control" minlength="6" required autocomplete="new-password">
            </div>
        </div>
        <button class="btn btn-primary w-100 login-submit"><i class="bi bi-key me-1" aria-hidden="true"></i>Restablecer contraseña</button>
    </form>

    <div class="login-links text-center mt-3">
        <a href="login.php">Volver al inicio de sesión</a>
    </div>
</main>
</body>
</html>