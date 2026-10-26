<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');

$titulo = "Reportes y estadísticas";
$base = "../../";

$usuarios = $conexion->query("SELECT COUNT(*) AS total FROM usuarios")->fetch_assoc()['total'];
$equipos = $conexion->query("SELECT COUNT(*) AS total FROM equipos")->fetch_assoc()['total'];
$solicitudesPendientes = $conexion->query("SELECT COUNT(*) AS total FROM solicitudes WHERE estado='Pendiente'")->fetch_assoc()['total'];
$solicitudesAprobadas = $conexion->query("SELECT COUNT(*) AS total FROM solicitudes WHERE estado='Aprobada'")->fetch_assoc()['total'];
$reparacionesActivas = $conexion->query("SELECT COUNT(*) AS total FROM reparaciones WHERE estado NOT IN ('Entregado','Reparado')")->fetch_assoc()['total'];
$prestacionesActivas = $conexion->query("SELECT COUNT(*) AS total FROM prestaciones WHERE estado IN ('Solicitado','En proceso')")->fetch_assoc()['total'];
$equiposAsignados = $conexion->query("SELECT COUNT(*) AS total FROM equipos WHERE usuario_id IS NOT NULL AND usuario_id != ''")->fetch_assoc()['total'];
$equiposSinAsignar = $conexion->query("SELECT COUNT(*) AS total FROM equipos WHERE usuario_id IS NULL OR usuario_id = ''")->fetch_assoc()['total'];

require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content">
    <div class="hero">
        <h2>Reportes y estadísticas</h2>
    </div>

    <section class="card table-card p-3 p-md-4 mb-4" aria-labelledby="referencia-estadisticas">
        <h5 id="referencia-estadisticas">Vista gráfica</h5>
        <img
            src="../../media/reporte-estadisticas-referencia.png"
            alt="Panel de referencia con gráficos de solicitudes, equipos y reparaciones"
            class="img-fluid d-block mx-auto"
            style="width: 100%; height: auto;"
        >
    </section>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-stat stat-card-usuarios p-4">
                <div class="icon"><i class="bi bi-people-fill"></i></div>
                <h3><?= e($usuarios) ?></h3>
                <p>Usuarios registrados</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat stat-card-equipos p-4">
                <div class="icon"><i class="bi bi-laptop"></i></div>
                <h3><?= e($equipos) ?></h3>
                <p>Equipos registrados</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat stat-card-solicitudes p-4">
                <div class="icon"><i class="bi bi-card-list"></i></div>
                <h3><?= e($solicitudesPendientes) ?></h3>
                <p>Solicitudes pendientes</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat stat-card-solicitudes p-4">
                <div class="icon"><i class="bi bi-check-circle"></i></div>
                <h3><?= e($solicitudesAprobadas) ?></h3>
                <p>Solicitudes aprobadas</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat stat-card-reparaciones p-4">
                <div class="icon"><i class="bi bi-wrench-adjustable-circle"></i></div>
                <h3><?= e($reparacionesActivas) ?></h3>
                <p>Reparaciones activas</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat stat-card-solicitudes p-4">
                <div class="icon"><i class="bi bi-box-seam"></i></div>
                <h3><?= e($prestacionesActivas) ?></h3>
                <p>Prestaciones en proceso</p>
            </div>
        </div>
    </div>

    <div class="card table-card p-4 mt-4">
        <h5>Detalle de inventario</h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Equipos asignados</td>
                        <td><?= e($equiposAsignados) ?></td>
                    </tr>
                    <tr>
                        <td>Equipos sin asignar</td>
                        <td><?= e($equiposSinAsignar) ?></td>
                    </tr>
                    <tr>
                        <td>Usuarios registrados</td>
                        <td><?= e($usuarios) ?></td>
                    </tr>
                    <tr>
                        <td>Equipos registrados</td>
                        <td><?= e($equipos) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require "../../includes/footer.php"; ?>
