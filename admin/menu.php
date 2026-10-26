<?php
require_once "../conexion.php"; require_once "../includes/auth.php"; exigir_rol('admin');
$titulo="Menú administrador";$base="../";
$usuarios=$conexion->query("SELECT COUNT(*) c FROM usuarios")->fetch_assoc()['c'];
$equipos=$conexion->query("SELECT COUNT(*) c FROM equipos")->fetch_assoc()['c'];
$solicitudes=$conexion->query("SELECT COUNT(*) c FROM solicitudes WHERE estado='Pendiente'")->fetch_assoc()['c'];
$reparaciones=$conexion->query("SELECT COUNT(*) c FROM reparaciones WHERE estado NOT IN ('Entregado','Reparado')")->fetch_assoc()['c'];
require "../includes/header.php";
?>
<?php require "../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content">
<div class="hero"><h2>Menú de Administrador</h2><p class="text-muted">Gestione usuarios, equipos, solicitudes, prestaciones y reparaciones.</p></div>
<div class="row g-3">
<div class="col-md-4"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-people-fill"></i></div><h3><?=e($usuarios)?></h3><p>Usuarios registrados</p></div></div>
<div class="col-md-4"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-laptop"></i></div><h3><?=e($equipos)?></h3><p>Equipos</p></div></div>
<div class="col-md-4"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-card-list"></i></div><h3><?=e($solicitudes)?></h3><p>Solicitudes pendientes</p></div></div>
<div class="col-md-4"><div class="card card-stat p-4"><div class="icon"><i class="bi bi-wrench-adjustable-circle"></i></div><h3><?=e($reparaciones)?></h3><p>Reparaciones activas</p></div></div>
</div>
</main>
<?php require "../includes/footer.php"; ?>
