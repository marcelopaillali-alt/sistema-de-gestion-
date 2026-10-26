<?php
function registrar_historial_perfil(
    mysqli $conexion,
    ?int $usuarioId,
    string $usuarioRut,
    string $usuarioNombre,
    string $accion,
    string $detalle,
    ?int $administradorId = null,
    ?string $administradorNombre = null
): void {
    if ($administradorId === null && isset($_SESSION['id'])) {
        $administradorId = (int)$_SESSION['id'];
    }

    if ($administradorNombre === null && $administradorId !== null) {
        $stmt = $conexion->prepare(
            "SELECT CONCAT(nombre, ' ', apellido) AS nombre
             FROM usuarios
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $administradorId);
        $stmt->execute();
        $administradorNombre = $stmt->get_result()->fetch_assoc()['nombre'] ?? null;
    }

    $administradorNombre = trim((string)$administradorNombre);
    if ($administradorNombre === '') {
        $administradorNombre = 'Registro público';
    }

    $stmt = $conexion->prepare(
        "INSERT INTO usuarios_historial
            (usuario_id, usuario_rut, usuario_nombre, administrador_id,
             administrador_nombre, accion, detalle)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ississs",
        $usuarioId,
        $usuarioRut,
        $usuarioNombre,
        $administradorId,
        $administradorNombre,
        $accion,
        $detalle
    );
    $stmt->execute();
}
