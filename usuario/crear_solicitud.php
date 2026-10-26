<?php
require_once "../conexion.php";
require_once "../includes/auth.php";
exigir_rol('usuario');

$titulo = "Crear solicitud";
$base = "../";
$error = $_GET['error'] ?? '';
$usuarioId = (int)$_SESSION['id'];

$stmt = $conexion->prepare("SELECT area FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();
if (!$perfil) {
    http_response_code(403);
    exit("No se encontró el perfil del usuario.");
}

$stmt = $conexion->prepare(
    "SELECT id, codigo_equipo, marca, modelo, tipo, estado
     FROM equipos
     WHERE usuario_id = ?
     ORDER BY codigo_equipo"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$equipos = $stmt->get_result();
$tiposSolicitud = [
    'Reportar un problema',
    'Solicitar un equipo',
    'Solicitar soporte técnico',
    'Problema de red/Internet',
    'Problema con impresora',
    'Solicitar acceso o cuenta',
    'Problema de contraseña',
    'Solicitar software',
    'Instalación o configuración',
    'Mantenimiento de equipo',
    'Reemplazo de equipo',
    'Préstamo de equipo',
    'Devolución de equipo',
    'Traslado de equipo',
    'Otro',
];

require "../includes/header.php";
?>
<?php require "../includes/sidebar_usuario.php"; ?>
<main class="col-md-9 content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1">Crear solicitud</h2>
            <p class="text-secondary mb-0">Completa los datos para registrar una nueva solicitud de soporte.</p>
        </div>
        <a href="mis_solicitudes.php" class="btn btn-outline-primary"><i class="bi bi-card-list me-1"></i>Mis solicitudes</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif; ?>

    <div class="card table-card p-3 p-md-4">
        <form action="guardar_solicitud.php" method="POST" enctype="multipart/form-data" novalidate data-validar-vacios>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="solicitante" class="form-label">Usuario solicitante</label>
                    <input id="solicitante" class="form-control" value="<?=e(trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')))?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="area" class="form-label">Área/departamento</label>
                    <input id="area" class="form-control" value="<?=e($perfil['area'] ?: 'No definida')?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="tipo_solicitud" class="form-label">Tipo de solicitud <span class="text-danger">*</span></label>
                    <select id="tipo_solicitud" name="tipo_solicitud" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($tiposSolicitud as $tipo): ?>
                            <option value="<?=e($tipo)?>"><?=e($tipo)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="asunto" class="form-label">Asunto <span class="text-danger">*</span></label>
                    <input id="asunto" name="asunto" class="form-control" maxlength="200" required>
                </div>
                <div class="col-md-6">
                    <label for="equipo_id" class="form-label">Equipo relacionado</label>
                    <select id="equipo_id" name="equipo_id" class="form-select" data-opcional>
                        <option value="">Sin equipo relacionado</option>
                        <?php while ($equipo = $equipos->fetch_assoc()): ?>
                            <option value="<?=e($equipo['id'])?>"><?=e(trim($equipo['codigo_equipo'] . ' - ' . $equipo['marca'] . ' ' . $equipo['modelo'] . ' (' . $equipo['tipo'] . ') - ' . $equipo['estado']))?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="lugar" class="form-label">Área o ubicación donde ocurre el problema <span class="text-danger">*</span></label>
                    <input id="lugar" name="lugar" class="form-control" maxlength="150" required>
                </div>
                <div class="col-md-6">
                    <label for="prioridad" class="form-label">Prioridad <span class="text-danger">*</span></label>
                    <select id="prioridad" name="prioridad" class="form-select" required>
                        <option>Baja</option>
                        <option selected>Media</option>
                        <option>Alta</option>
                        <option>Crítica</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="descripcion" class="form-label">Descripción detallada <span class="text-danger">*</span></label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="5" required></textarea>
                </div>
                <div class="col-12">
                    <label for="adjunto" class="form-label">Archivo adjunto (opcional)</label>
                    <input id="adjunto" name="adjunto" type="file" data-opcional class="form-control" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.txt">
                    <div class="form-text">PDF, JPG, PNG, GIF, WEBP o TXT. Tamaño máximo: 5 MB.</div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Crear solicitud</button>
                <a href="mis_solicitudes.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</main>
<?php require "../includes/footer.php"; ?>
