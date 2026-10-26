<?php
if (empty($_SESSION['csrf_tipos_equipo'])) {
    $_SESSION['csrf_tipos_equipo'] = bin2hex(random_bytes(32));
}
$tiposAdministrables = $conexion->query("SELECT nombre FROM tipos_equipo ORDER BY nombre");
?>
<details class="mt-2" data-tipos-equipo-admin data-csrf="<?=e($_SESSION['csrf_tipos_equipo'])?>" data-volver="<?=e($paginaTiposEquipo ?? 'inventario')?>" data-equipo-id="<?=e($idEquipoTipos ?? 0)?>">
<summary class="form-text">Administrar tipos guardados</summary>
<div class="list-group list-group-flush mt-2">
<?php while($tipoAdmin=$tiposAdministrables->fetch_assoc()): ?>
<div class="list-group-item d-flex justify-content-between align-items-center gap-2 px-2 py-2">
<span><?=e($tipoAdmin['nombre'])?></span>
<div class="d-flex gap-2">
<button type="button" class="btn btn-sm btn-primary btn-icon-action" data-accion-tipo="editar" data-nombre-tipo="<?=e($tipoAdmin['nombre'])?>" title="Editar tipo" aria-label="Editar tipo <?=e($tipoAdmin['nombre'])?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
<button type="button" class="btn btn-sm btn-danger btn-icon-action" data-accion-tipo="eliminar" data-nombre-tipo="<?=e($tipoAdmin['nombre'])?>" title="Eliminar tipo" aria-label="Eliminar tipo <?=e($tipoAdmin['nombre'])?>"><i class="bi bi-trash3-fill" aria-hidden="true"></i></button>
</div>
</div>
<?php endwhile; ?>
</div>
</details>
