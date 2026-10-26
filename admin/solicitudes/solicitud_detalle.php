<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');

$solicitudId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitudId || $solicitudId < 1) {
    http_response_code(404);
    exit("Solicitud no encontrada.");
}

$stmt = $conexion->prepare(
    "SELECT
        s.*,
        u.rut AS usuario_rut,
        u.nombre AS usuario_nombre,
        u.apellido AS usuario_apellido,
        u.correo AS usuario_correo,
        u.cargo AS usuario_cargo,
        u.area AS usuario_area,
        u.lugar AS usuario_lugar,
        u.ubicacion AS usuario_ubicacion,
        u.estado AS usuario_estado,
        e.codigo_equipo,
        e.marca AS equipo_marca,
        e.modelo AS equipo_modelo,
        e.tipo AS equipo_tipo
     FROM solicitudes s
     INNER JOIN usuarios u ON u.id = s.usuario_id
     LEFT JOIN equipos e ON e.id = s.equipo_id
     WHERE s.id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $solicitudId);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
if (!$solicitud) {
    http_response_code(404);
    exit("Solicitud no encontrada.");
}

if (empty($_SESSION['csrf_asignar_tecnico'])) {
    $_SESSION['csrf_asignar_tecnico'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['csrf_respuesta_solicitud'])) {
    $_SESSION['csrf_respuesta_solicitud'] = bin2hex(random_bytes(32));
}
$tecnicos = $conexion->query(
    "SELECT id, nombre, apellido, cargo, area
     FROM usuarios
     WHERE estado = 'activo'
       AND LOWER(cargo) LIKE '%tecnic%'
       AND LOWER(area) LIKE '%inform%'
     ORDER BY nombre, apellido"
)->fetch_all(MYSQLI_ASSOC);
$mensajeOk = isset($_GET['ok']) && is_string($_GET['ok']) ? $_GET['ok'] : '';
$mensajeError = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : '';

$titulo = "Detalle de solicitud";
$base = "../../";
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
require "../../includes/header.php";
?>
<?php require "../../includes/sidebar_admin.php"; ?>
<main class="col-md-9 content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1">Detalle de solicitud</h2>
            <p class="text-secondary mb-0">Ticket <strong><?=e($solicitud['ticket'] ?: 'Sin número')?></strong></p>
        </div>
        <a href="solicitudes.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Volver a solicitudes</a>
    </div>

    <?php if ($mensajeOk !== ''): ?><div class="alert alert-success" role="status"><?=e($mensajeOk)?></div><?php endif; ?>
    <?php if ($mensajeError !== ''): ?><div class="alert alert-danger" role="alert"><?=e($mensajeError)?></div><?php endif; ?>

    <section class="card table-card p-3 p-md-4 mb-4">
        <h3 class="h5 mb-3">Datos del usuario solicitante</h3>
        <div class="row g-3">
            <div class="col-md-6"><div class="small text-secondary">Nombre</div><div><?=e(trim($solicitud['usuario_nombre'] . ' ' . $solicitud['usuario_apellido']))?></div></div>
            <div class="col-md-6"><div class="small text-secondary">RUT</div><div><?=e($solicitud['usuario_rut'])?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Correo</div><div><?=e($solicitud['usuario_correo'])?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Cargo</div><div><?=e($solicitud['usuario_cargo'] ?: 'No indicado')?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Área/departamento</div><div><?=e($solicitud['usuario_area'] ?: 'No definida')?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Estado de la cuenta</div><div><?=e($solicitud['usuario_estado'])?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Lugar o establecimiento</div><div><?=e($solicitud['usuario_lugar'] ?: 'No indicado')?></div></div>
            <div class="col-md-6"><div class="small text-secondary">Ubicación u oficina</div><div><?=e($solicitud['usuario_ubicacion'] ?: 'No indicada')?></div></div>
        </div>
    </section>

    <section class="card table-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1"><?=e($solicitud['asunto'])?></h3>
                <span class="text-secondary"><?=e($solicitud['tipo_solicitud'])?></span>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="badge status-badge <?=e($clasesEstado[$solicitud['estado']] ?? 'status-default')?>"><?=e($solicitud['estado'])?></span>
                <?php
                $tiposRep = ['reportar un problema','problema con impresora','mantenimiento de equipo'];
                $estadosSolicitud = ['Pendiente','Aprobada','Rechazada','Completada'];
                if (in_array(strtolower(trim((string)$solicitud['tipo_solicitud'])), $tiposRep, true)) $estadosSolicitud[] = 'Aprobada reparación';
                if (!in_array($solicitud['estado'], $estadosSolicitud, true)) array_unshift($estadosSolicitud, $solicitud['estado']);
                ?>
                <form method="POST" action="estado_solicitud.php" class="d-flex gap-1" novalidate data-validar-vacios>
                    <input type="hidden" name="id" value="<?=e($solicitud['id'])?>">
                    <input type="hidden" name="volver" value="detalle">
                    <select name="estado" id="estado_solicitud" class="form-select form-select-sm" aria-label="Estado de la solicitud">
                        <?php foreach ($estadosSolicitud as $estadoOpcion): ?>
                            <option <?=$solicitud['estado'] === $estadoOpcion ? 'selected' : ''?>><?=e($estadoOpcion)?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary">OK</button>
                </form>
            </div>
        </div>
        <div class="request-summary-grid">
            <div class="request-summary-item">
                <span class="request-summary-icon"><i class="bi bi-hash" aria-hidden="true"></i></span>
                <div><span class="request-summary-label">Ticket</span><strong><?=e($solicitud['ticket'] ?: 'Sin número')?></strong></div>
            </div>
            <div class="request-summary-item">
                <span class="request-summary-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                <div><span class="request-summary-label">Fecha y hora</span><strong><?=e($solicitud['fecha'])?></strong></div>
            </div>
            <div class="request-summary-item">
                <span class="request-summary-icon"><i class="bi bi-flag" aria-hidden="true"></i></span>
                <div><span class="request-summary-label">Prioridad</span><span class="badge status-badge align-self-start <?=e(clase_prioridad($solicitud['prioridad']))?>"><?=e($solicitud['prioridad'])?></span></div>
            </div>
            <div class="request-summary-item">
                <span class="request-summary-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
                <div><span class="request-summary-label">Técnico asignado</span><strong><?=e($solicitud['tecnico_asignado'])?></strong></div>
            </div>
        </div>
        <div class="row g-3 request-detail-grid">
            <div class="col-md-6">
                <div class="request-info-card">
                    <span class="request-info-icon"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                    <div><span class="request-summary-label">Área solicitante</span><strong><?=e($solicitud['area_solicitante'] ?: 'No definida')?></strong></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="request-info-card">
                    <span class="request-info-icon"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span>
                    <div><span class="request-summary-label">Ubicación del problema</span><strong><?=e($solicitud['lugar'] ?: 'No indicada')?></strong></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="request-info-card">
                    <span class="request-info-icon"><i class="bi bi-pc-display" aria-hidden="true"></i></span>
                    <div>
                        <span class="request-summary-label">Equipo relacionado</span>
                        <strong><?=e($solicitud['codigo_equipo']
                            ? trim($solicitud['codigo_equipo'] . ' - ' . $solicitud['equipo_marca'] . ' ' . $solicitud['equipo_modelo'] . ' (' . $solicitud['equipo_tipo'] . ')')
                            : 'Sin equipo relacionado')?></strong>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="request-info-card request-technician-card">
                    <span class="request-info-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <form action="asignar_tecnico_solicitud.php" method="POST" class="request-technician-form" novalidate data-validar-vacios>
                        <input type="hidden" name="csrf" value="<?=e($_SESSION['csrf_asignar_tecnico'])?>">
                        <input type="hidden" name="solicitud_id" value="<?=e($solicitud['id'])?>">
                        <label for="tecnico_id" class="request-technician-label">Asignar técnico de Informática</label>
                        <div class="d-flex flex-wrap gap-2">
                            <select id="tecnico_id" name="tecnico_id" class="form-select" required onchange="if (this.value !== '') { this.form.requestSubmit(); }">
                                <option value="">Seleccione un técnico...</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <?php $nombreTecnico = trim($tecnico['nombre'] . ' ' . $tecnico['apellido']); ?>
                                    <option value="<?=e($tecnico['id'])?>" <?=$solicitud['tecnico_asignado'] === $nombreTecnico ? 'selected' : ''?>>
                                        <?=e($nombreTecnico . ' — ' . $tecnico['area'])?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (count($tecnicos) === 0): ?>
                            <div class="form-text text-danger">No hay técnicos activos de Informática.</div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
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
            <div class="col-12">
                <section class="request-content-panel">
                    <h4><i class="bi bi-card-text" aria-hidden="true"></i>Descripción</h4>
                    <div class="request-description"><?=e($solicitud['descripcion'])?></div>
                </section>
            </div>
            <div class="col-12">
                <section class="request-answer-panel">
                    <label for="respuesta" class="request-answer-label">
                        <i class="bi bi-chat-circle" aria-hidden="true"></i>
                        <span>Respuesta al usuario <span class="text-secondary">(máximo 50 palabras)</span></span>
                    </label>
                    <form action="guardar_respuesta_solicitud.php" method="POST" novalidate data-validar-vacios>
                        <input type="hidden" name="csrf" value="<?=e($_SESSION['csrf_respuesta_solicitud'])?>">
                        <input type="hidden" name="solicitud_id" value="<?=e($solicitud['id'])?>">
                        <textarea id="respuesta" name="respuesta" class="form-control request-answer-input" rows="3" placeholder="Escriba la respuesta al usuario..." aria-describedby="contador-respuesta"><?=e($solicitud['respuesta'] ?? '')?></textarea>
                        <div id="contador-respuesta" class="form-text request-answer-counter">0 de 50 palabras</div>
                        <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-send me-1"></i>Enviar respuesta</button>
                        <a href="solicitudes.php" class="btn btn-secondary mt-2 ms-1">Cancelar</a>
                    </form>
                </section>
            </div>
            <?php if ($solicitud['razon']): ?>
                <div class="col-12">
                    <div class="small text-secondary mb-1">Observación o razón</div>
                    <div class="border rounded p-3 bg-light" style="white-space: pre-wrap"><?=e($solicitud['razon'])?></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>
<script>
    (function() {
        var campo = document.getElementById('respuesta');
        var contador = document.getElementById('contador-respuesta');
        var formulario = campo.form;

        function actualizarConteo() {
            var palabras = campo.value.trim().split(/\s+/).filter(Boolean).length;
            contador.textContent = palabras + ' de 50 palabras';
            campo.setCustomValidity(palabras > 50 ? 'La respuesta no puede superar las 50 palabras.' : '');
        }

        campo.addEventListener('input', actualizarConteo);
        formulario.addEventListener('submit', function(evento) {
            actualizarConteo();
            if (!formulario.reportValidity()) evento.preventDefault();
        });
        actualizarConteo();
    })();
</script>
<?php require "../../includes/footer.php"; ?>