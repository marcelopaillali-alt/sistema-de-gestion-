<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$titulo = "Panel de usuario";
$base = "../";
$totalSolicitudes = $conexion->query("SELECT COUNT(*) c FROM solicitudes WHERE usuario_id=".(int)$_SESSION['id'])->fetch_assoc()['c'];
$totalPrestaciones = $conexion->query("SELECT COUNT(*) c FROM prestaciones WHERE usuario_id=".(int)$_SESSION['id'])->fetch_assoc()['c'];
require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content">

    <div class="row g-3">
        <div class="col-md-6"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-card-list"></i></div><h3><?= e($totalSolicitudes) ?></h3><p class="mb-0">Mis solicitudes</p></div></div>
        <div class="col-md-6"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-box-seam"></i></div><h3><?= e($totalPrestaciones) ?></h3><p class="mb-0">Mis prestaciones</p></div></div>
    </div>
</main>
<?php require "../includes/footer.php"; ?>
