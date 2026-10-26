<?php $paginaActual = basename($_SERVER['PHP_SELF']); ?>
<div class="col-md-3 sidebar user-sidebar">
    <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Minimizar menú lateral" aria-expanded="true" title="Minimizar menú lateral">
        <i class="bi bi-three-dots" aria-hidden="true"></i>
    </button>
    <a class="sidebar-nav-link <?= $paginaActual === 'menu.php' ? 'active' : '' ?>" href="menu.php" aria-label="Inicio"><i class="bi bi-house-door-fill me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Inicio</span></a>
    <a class="sidebar-nav-link <?= $paginaActual === 'mi_cuenta.php' ? 'active' : '' ?>" href="mi_cuenta.php" aria-label="Mi cuenta"><i class="bi bi-person-circle me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Mi cuenta</span></a>
    <a class="sidebar-nav-link <?= $paginaActual === 'mis_equipos.php' ? 'active' : '' ?>" href="mis_equipos.php" aria-label="Mis equipos"><i class="bi bi-pc-display me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Mis equipos</span></a>
    <a class="sidebar-nav-link <?= $paginaActual === 'crear_solicitud.php' ? 'active' : '' ?>" href="crear_solicitud.php" aria-label="Crear solicitud"><i class="bi bi-plus-square me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Crear solicitud</span></a>
    <a class="sidebar-nav-link <?= $paginaActual === 'mis_solicitudes.php' ? 'active' : '' ?>" href="mis_solicitudes.php" aria-label="Mis solicitudes"><i class="bi bi-card-list me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Mis solicitudes</span></a>
    <a class="sidebar-nav-link <?= $paginaActual === 'mis_prestaciones.php' ? 'active' : '' ?>" href="mis_prestaciones.php" aria-label="Mis prestaciones"><i class="bi bi-box-seam me-2" aria-hidden="true"></i><span class="sidebar-nav-label">Mis prestaciones</span></a>
    <div class="sidebar-credit">Derechos reservados<br>Copyright © 2026 Creado por Devweb</div>
</div>
