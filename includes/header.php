<?php require_once __DIR__ . "/auth.php"; $raiz = ruta_raiz(); $base = $base ?? $raiz; ?>
<?php
$esAdministrador = in_array($_SESSION['rol'] ?? '', ['admin', 'tecnico'], true);
$idUsuario = (int)($_SESSION['id'] ?? 0);
if ($esAdministrador) {
    $totalNotificaciones = (int)$conexion->query(
        "SELECT COUNT(*) AS total FROM solicitudes WHERE estado = 'Pendiente'"
    )->fetch_assoc()['total'];
    $urlNotificaciones = $base . 'admin/solicitudes/solicitudes.php';
} else {
    $stmtNotificaciones = $conexion->prepare(
        "SELECT COUNT(*) AS total FROM solicitudes WHERE usuario_id = ? AND estado = 'Pendiente'"
    );
    $stmtNotificaciones->bind_param("i", $idUsuario);
    $stmtNotificaciones->execute();
    $totalNotificaciones = (int)$stmtNotificaciones->get_result()->fetch_assoc()['total'];
    $urlNotificaciones = $base . 'usuario/mis_solicitudes.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Sistema de Soporte') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= $raiz ?>assets/css/estilo.css?v=<?= filemtime(__DIR__ . '/../assets/css/estilo.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar <?= $esAdministrador ? 'admin-navbar' : '' ?>">
<div class="container-fluid">
<a class="navbar-brand fw-bold" href="<?= $base ?>">
    <img src="<?= $base ?>media/logo2.png" alt="Logo Sistema de Soporte" height="52" class="me-2">
    Sistema de Soporte
</a>
<div class="d-flex align-items-center gap-3 text-white">
<a
    class="notification-link"
    href="<?= e($urlNotificaciones) ?>"
    aria-label="Solicitudes pendientes: <?= e($totalNotificaciones) ?>"
    title="<?= e($totalNotificaciones) ?> solicitudes pendientes"
>
    <i class="bi bi-bell" aria-hidden="true"></i>
    <?php if ($totalNotificaciones > 0): ?>
        <span class="notification-count" aria-hidden="true"><?= $totalNotificaciones > 99 ? '99+' : e($totalNotificaciones) ?></span>
    <?php endif; ?>
</a>
<span class="d-flex align-items-center gap-2">
    <i class="bi bi-person-circle fs-4" aria-hidden="true"></i>
    <span><?= e($_SESSION['nombre'] ?? '') ?> · <?= e(['admin' => 'administrador', 'tecnico' => 'técnico de soporte'][$_SESSION['rol'] ?? ''] ?? ($_SESSION['rol'] ?? '')) ?></span>
</span>
<a href="<?= $base ?>cerrar_sesion.php" class="btn btn-sm btn-danger text-white">Cerrar sesión</a>
</div>
</div>
</nav>
<div class="container-fluid">
<div class="row">