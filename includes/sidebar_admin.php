<?php
$paginaActual = basename($_SERVER['PHP_SELF']);
$seccionActual = in_array($paginaActual, ['usuario_nuevo.php', 'usuario_editar.php'], true)
    ? 'usuarios.php'
    : $paginaActual;
?>
<div class="col-md-3 sidebar admin-sidebar">
    <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Minimizar menú lateral" aria-expanded="true" title="Minimizar menú lateral">
        <i class="bi bi-three-dots" aria-hidden="true"></i>
    </button>
    <a class="sidebar-nav-link <?= $seccionActual === 'menu.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/menu.php" aria-label="Inicio"><i class="bi bi-house-door-fill me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Inicio</span></a>
    <a class="sidebar-nav-link <?= $seccionActual === 'usuarios.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/usuarios/usuarios.php" aria-label="Gestión de usuarios"><i class="bi bi-people-fill me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Gestión de usuarios</span></a>
    <a class="sidebar-nav-link <?= $seccionActual === 'inventario.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/equipos/inventario.php" aria-label="Inventario de equipos"><i class="bi bi-laptop me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Inventario de equipos</span></a>
    <a class="sidebar-nav-link <?= $seccionActual === 'reportes.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/reportes/reportes.php" aria-label="Reportes y estadísticas"><i class="bi bi-bar-chart-fill me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Reportes y estadísticas</span></a>
    <a class="sidebar-nav-link <?= $seccionActual === 'solicitudes.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/solicitudes/solicitudes.php" aria-label="Solicitudes de equipos"><i class="bi bi-card-list me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Solicitudes de equipos</span></a>
    <a class="sidebar-nav-link <?= $seccionActual === 'prestaciones.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/prestaciones/prestaciones.php" aria-label="Prestaciones"><i class="bi bi-box-seam me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Prestaciones</span></a>
    <a class="sidebar-nav-link <?= $seccionActual === 'reparaciones.php' ? 'active' : '' ?>" href="<?= ruta_raiz() ?>admin/reparaciones/reparaciones.php" aria-label="Reparaciones"><i class="bi bi-wrench-adjustable-circle me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Reparaciones</span></a>
    <div class="sidebar-credit">Derechos reservados<br>Copyright © 2026 Creado por Devweb</div>
</div>
