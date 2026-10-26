<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(404);
    exit('Reparación no encontrada.');
}
$stmt = $conexion->prepare("SELECT r.*, s.ticket FROM reparaciones r LEFT JOIN solicitudes s ON s.id=r.solicitud_id WHERE r.id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$reparacion = $stmt->get_result()->fetch_assoc();
if (!$reparacion) {
    http_response_code(404);
    exit('Reparación no encontrada.');
}
if (empty($_SESSION['csrf_editar_reparacion'])) {
    $_SESSION['csrf_editar_reparacion'] = bin2hex(random_bytes(32));
}
$titulo = 'Editar reparación';
$base = '../../';
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : '';
require '../../includes/header.php';
?>
<?php require '../../includes/sidebar_admin.php'; ?>
<main class="col-md-9 content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h2 class="mb-1">Editar reparación REP-<?=e(str_pad((string)$reparacion['id'], 5, '0', STR_PAD_LEFT))?></h2><p class="text-secondary mb-0">Ticket de solicitud: <strong><?=e($reparacion['ticket'] ?: 'Sin solicitud asociada')?></strong></p></div>
        <a href="reparaciones.php" class="btn btn-outline-primary">Volver a reparaciones</a>
    </div>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif; ?>
    <section class="card table-card p-3 p-md-4">
        <form action="reparacion_actualizar.php" method="POST">
            <input type="hidden" name="csrf" value="<?=e($_SESSION['csrf_editar_reparacion'])?>">
            <input type="hidden" name="id" value="<?=e($reparacion['id'])?>">
            <div class="mb-3"><label for="problema" class="form-label">Problema</label><textarea id="problema" name="problema" class="form-control" rows="3" maxlength="5000" required><?=e($reparacion['problema'])?></textarea></div>
            <div class="mb-3"><label for="diagnostico" class="form-label">Diagnóstico</label><textarea id="diagnostico" name="diagnostico" class="form-control" rows="4" maxlength="5000"><?=e($reparacion['diagnostico'])?></textarea></div>
            <div class="mb-3"><label for="solucion" class="form-label">Solución</label><textarea id="solucion" name="solucion" class="form-control" rows="4" maxlength="5000"><?=e($reparacion['solucion'])?></textarea></div>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="reparaciones.php" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </section>
</main>
<?php require '../../includes/footer.php'; ?>
