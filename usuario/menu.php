<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$titulo = "Panel de usuario";
$base = "../";
$idUsuario = (int)$_SESSION['id'];
$estadisticasSolicitudes = $conexion->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(estado IN ('Pendiente', 'Aprobada', 'Aprobada reparación')), 0) AS en_proceso,
        COALESCE(SUM(estado = 'Completada'), 0) AS resueltas,
        COALESCE(SUM(estado = 'Rechazada'), 0) AS canceladas
     FROM solicitudes
     WHERE usuario_id = $idUsuario"
)->fetch_assoc();
$totalPrestaciones = $conexion->query("SELECT COUNT(*) c FROM prestaciones WHERE usuario_id=".(int)$_SESSION['id'])->fetch_assoc()['c'];
require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Mis solicitudes</h2>
        <a href="crear_solicitud.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card card-stat user-request-stat user-request-total p-4"><div class="icon"><i class="bi bi-file-earmark-text"></i></div><h3><?= e($estadisticasSolicitudes['total']) ?></h3><p class="mb-0">Mis solicitudes</p></div></div>
        <div class="col-6 col-lg-3"><div class="card card-stat user-request-stat user-request-progress p-4"><div class="icon"><i class="bi bi-clock"></i></div><h3><?= e($estadisticasSolicitudes['en_proceso']) ?></h3><p class="mb-0">En proceso</p></div></div>
        <div class="col-6 col-lg-3"><div class="card card-stat user-request-stat user-request-resolved p-4"><div class="icon"><i class="bi bi-check-circle"></i></div><h3><?= e($estadisticasSolicitudes['resueltas']) ?></h3><p class="mb-0">Resueltas</p></div></div>
        <div class="col-6 col-lg-3"><div class="card card-stat user-request-stat user-request-cancelled p-4"><div class="icon"><i class="bi bi-x-circle"></i></div><h3><?= e($estadisticasSolicitudes['canceladas']) ?></h3><p class="mb-0">Canceladas</p></div></div>
    </div>
    <div class="row g-3">
        <div class="col-md-6"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-box-seam"></i></div><h3><?= e($totalPrestaciones) ?></h3><p class="mb-0">Mis prestaciones</p></div></div>
    </div>
</main>
<?php require "../includes/footer.php"; ?>
