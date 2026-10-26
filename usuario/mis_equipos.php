<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$titulo = "Mis equipos";
$base = "../";
$usuarioId = (int)$_SESSION['id'];
$stmt = $conexion->prepare(
    "SELECT codigo_equipo, marca, modelo, tipo, numero_serie, estado, lugar, ip_asignada, mac,
            (SELECT r.estado FROM reparaciones r WHERE r.equipo_id = equipos.id ORDER BY r.id DESC LIMIT 1) AS estado_reparacion
     FROM equipos
     WHERE usuario_id = ?
     ORDER BY codigo_equipo"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$equipos = $stmt->get_result();
$clasesEstado = [
    'Asignado' => 'equipment-assigned',
    'Disponible' => 'equipment-available',
    'En reparación' => 'status-pending',
    'Baja' => 'equipment-retired',
];

require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content">
    <div class="mb-4">
        <h2 class="mb-1">Mis equipos</h2>
        <p class="text-secondary mb-0">Equipos que están asignados a tu usuario.</p>
    </div>

    <div class="card table-card p-3 p-md-4">
        <?php if ($equipos->num_rows === 0): ?>
            <div class="text-center py-4">
                <i class="bi bi-pc-display text-secondary" style="font-size: 2.5rem"></i>
                <p class="mt-3 mb-0">No tienes equipos asignados actualmente.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Tipo de equipo</th>
                            <th>Número de serie</th>
                            <th>Estado</th>
                            <th>Estado reparación</th>
                            <th>Lugar</th>
                            <th>IP asignada</th>
                            <th>MAC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($equipo = $equipos->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?=e($equipo['codigo_equipo'])?></strong></td>
                                <td><?=e($equipo['marca'] ?: '—')?></td>
                                <td><?=e($equipo['modelo'] ?: '—')?></td>
                                <td><?=e($equipo['tipo'] ?: '—')?></td>
                                <td><?=e($equipo['numero_serie'] ?: '—')?></td>
                                <td><span class="badge status-badge <?=e($clasesEstado[$equipo['estado']] ?? 'status-default')?>"><?=e($equipo['estado'])?></span></td>
                                <td><?php if(!empty($equipo['estado_reparacion'])): ?><span class="badge status-badge <?=e(['Reparado y entregado'=>'status-resolved','Entregado'=>'status-resolved','Reparado'=>'status-approved','Bueno'=>'status-approved','No reparable'=>'equipment-retired'][$equipo['estado_reparacion']] ?? 'status-pending')?>"><?=e($equipo['estado_reparacion'])?></span><?php else: ?>—<?php endif; ?></td>
                                <td><?=e($equipo['lugar'] ?: '—')?></td>
                                <td><?=e($equipo['ip_asignada'] ?: '—')?></td>
                                <td><?=e($equipo['mac'] ?: '—')?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require "../includes/footer.php"; ?>
