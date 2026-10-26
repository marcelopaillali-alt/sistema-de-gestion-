<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$solicitudId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitudId || $solicitudId < 1) {
    http_response_code(404);
    exit("Solicitud no encontrada.");
}

$usuarioId = (int)$_SESSION['id'];
$stmt = $conexion->prepare(
    "SELECT s.*, e.codigo_equipo, e.marca, e.modelo, e.tipo
     FROM solicitudes s
     LEFT JOIN equipos e ON e.id = s.equipo_id
     WHERE s.id = ? AND s.usuario_id = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $solicitudId, $usuarioId);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
if (!$solicitud) {
    http_response_code(404);
    exit("Solicitud no encontrada.");
}
$solicitud['estado'] = trim((string)($solicitud['estado'] ?? ''));
if ($solicitud['estado'] === '') {
    $solicitud['estado'] = 'Pendiente';
}

$titulo = "Detalle de solicitud";
$base = "../";
$creada = ($_GET['creada'] ?? '') === '1';
$clasesEstado = [
    'Pendiente' => 'status-pending',
    'En proceso' => 'status-pending',
    'Aprobada' => 'status-approved',
    'Aprobada reparación' => 'status-approved',
    'Completada' => 'status-resolved',
    'Resuelta' => 'status-resolved',
    'Rechazada' => 'status-cancelled',
    'Cancelada' => 'status-cancelled',
];
require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1">Detalle de solicitud</h2>
            <p class="text-secondary mb-0">Ticket <strong><?=e($solicitud['ticket'])?></strong></p>
        </div>
        <a href="mis_solicitudes.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Mis solicitudes</a>
    </div>

    <?php if ($creada): ?>
        <div class="alert alert-success" role="status">
            <i class="bi bi-check-circle-fill me-1"></i>
            Solicitud creada correctamente. Tu número de ticket es <strong><?=e($solicitud['ticket'])?></strong>.
        </div>
    <?php endif; ?>

    <div class="card table-card p-3 p-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1"><?=e($solicitud['asunto'])?></h3>
                <span class="text-secondary"><?=e($solicitud['tipo_solicitud'])?></span>
            </div>
            <span class="badge status-badge <?=e($clasesEstado[$solicitud['estado']] ?? 'status-default')?>"><?=e($solicitud['estado'])?></span>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="small text-secondary">Fecha y hora de creación</div><div><?=e(date('d/m/Y H:i', strtotime($solicitud['fecha'])))?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Usuario solicitante</div><div><?=e(trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')))?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Área/departamento</div><div><?=e($solicitud['area_solicitante'] ?: 'No definida')?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Prioridad</div><div><span class="badge status-badge <?=e(clase_prioridad($solicitud['prioridad']))?>"><?=e($solicitud['prioridad'])?></span></div></div>
            <div class="col-md-6"><div class="small text-secondary">Equipo relacionado</div><div><?=e($solicitud['codigo_equipo'] ? trim($solicitud['codigo_equipo'] . ' - ' . $solicitud['marca'] . ' ' . $solicitud['modelo'] . ' (' . $solicitud['tipo'] . ')') : 'Sin equipo relacionado')?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Área o ubicación</div><div><?=e($solicitud['lugar'])?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Técnico asignado</div><div><?=e($solicitud['tecnico_asignado'])?></div></div>
            <div class="col-12">
                <div class="small text-secondary mb-1">Descripción detallada</div>
                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap"><?=e($solicitud['descripcion'])?></div>
            </div>
            <?php if (!empty($solicitud['respuesta'])): ?>
                <div class="col-12">
                    <div class="small text-secondary mb-1">Respuesta de soporte</div>
                    <div class="border rounded p-3 bg-light" style="white-space: pre-wrap"><?=e($solicitud['respuesta'])?></div>
                </div>
            <?php endif; ?>
            <?php if ($solicitud['adjunto']): ?>
                <div class="col-12">
                    <div class="small text-secondary mb-1">Archivo adjunto</div>
                    <?php $extAdjunto = strtolower(pathinfo((string)$solicitud['adjunto'], PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($extAdjunto, ['jpg','jpeg','png','gif','webp'], true)): ?>
                        <a href="ver_imagen_solicitud.php?id=<?=e($solicitud['id'])?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-image me-1"></i>Ver imagen
                        </a>
                    <?php else: ?>
                        <a href="descargar_adjunto_solicitud.php?id=<?=e($solicitud['id'])?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-paperclip me-1"></i>Descargar archivo
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require "../includes/footer.php"; ?>
