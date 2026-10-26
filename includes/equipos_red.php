<?php
// Regla de bodega: equipo sin usuario queda "Disponible" en "Bodega" (salvo reparación/baja).
// Devuelve [estado, lugar] de un equipo según tenga o no usuario.
function aplicar_regla_bodega_equipo(
    ?int $usuarioId,
    string $estado,
    string $lugar,
    ?string $ubicacionUsuario = null
): array {
    if ($usuarioId === null) {
        if (!in_array($estado, ['En reparación', 'Baja'], true)) {
            $estado = 'Disponible';
            $lugar = 'Bodega';
        }
    } else {
        if ($estado === 'Disponible') {
            $estado = 'Asignado';
        }

        $lugarVacioOBodega = trim($lugar) === '' || strcasecmp(trim($lugar), 'Bodega') === 0;
        $ubicacion = trim((string)$ubicacionUsuario);

        if ($lugarVacioOBodega && $ubicacion !== '') {
            $lugar = $ubicacion;
        }
    }

    return [$estado, $lugar];
}

// Obtiene la ubicación del usuario (null si no hay usuario).
function ubicacion_usuario_equipo(mysqli $conexion, ?int $usuarioId): ?string
{
    if ($usuarioId === null) {
        return null;
    }

    $stmt = $conexion->prepare("SELECT ubicacion FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    return $fila['ubicacion'] ?? null;
}

// Guarda una entrada en el historial del equipo y actualiza su última modificación.
function registrar_historial_equipo(
    mysqli $conexion,
    int $equipoId,
    string $accion,
    string $detalle,
    ?string $autor = null
): void {
    if ($autor === null) {
        $autor = trim((string)($_SESSION['nombre'] ?? '')) ?: 'Sistema';
    }

    $stmt = $conexion->prepare(
        "INSERT INTO equipos_historial(equipo_id, accion, detalle, realizado_por)
         VALUES(?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $equipoId, $accion, $detalle, $autor);
    $stmt->execute();

    $stmt = $conexion->prepare("UPDATE equipos SET ultima_modificacion = NOW() WHERE id = ?");
    $stmt->bind_param("i", $equipoId);
    $stmt->execute();
}

// Devuelve "Nombre Apellido" del usuario, o "Sin asignar" si no existe.
function nombre_usuario_equipo(mysqli $conexion, $usuarioId): string {
    if (empty($usuarioId)) {
        return 'Sin asignar';
    }
    $id = (int)$usuarioId;
    $stmt = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) AS n FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    return trim((string)($fila['n'] ?? '')) ?: 'Sin asignar';
}

// Valida el tipo de equipo (creándolo si es nuevo); devuelve null si no es válido.
function resolver_tipo_equipo(mysqli $conexion, string $seleccion, string $tipoNuevo): ?string {
    if ($seleccion === '__nuevo__') {
        $tipoNuevo = trim($tipoNuevo);
        if ($tipoNuevo === '' || mb_strlen($tipoNuevo) > 50) {
            return null;
        }
        $stmt = $conexion->prepare("INSERT IGNORE INTO tipos_equipo(nombre) VALUES(?)");
        $stmt->bind_param("s", $tipoNuevo);
        $stmt->execute();
        $seleccion = $tipoNuevo;
    }

    $stmt = $conexion->prepare("SELECT nombre FROM tipos_equipo WHERE nombre=? LIMIT 1");
    $stmt->bind_param("s", $seleccion);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    return $fila['nombre'] ?? null;
}

// Manda a bodega los equipos sin usuario y marca como asignados los que tienen usuario.
function sincronizar_equipos_sin_usuario(mysqli $conexion): void {
    $pendientes = $conexion->query(
        "SELECT id, estado, lugar
         FROM equipos
         WHERE usuario_id IS NULL
           AND estado NOT IN ('En reparación','Baja')
           AND (estado<>'Disponible' OR lugar IS NULL OR lugar<>'Bodega')"
    );
    while ($p = $pendientes->fetch_assoc()) {
        $detalle = 'Sin usuario asignado: estado "' . $p['estado'] . '" → "Disponible", '
            . 'lugar "' . $p['lugar'] . '" → "Bodega".';

        registrar_historial_equipo(
            $conexion,
            (int)$p['id'],
            'Pasó a bodega',
            $detalle,
            'Sistema'
        );
    }
    $conexion->query(
        "UPDATE equipos
         SET estado='Disponible', lugar='Bodega'
         WHERE usuario_id IS NULL
           AND estado NOT IN ('En reparación','Baja')"
    );
    $conexion->query(
        "UPDATE equipos
         SET estado='Asignado'
         WHERE usuario_id IS NOT NULL
           AND estado='Disponible'"
    );
}

// Valida y limpia una IPv4; devuelve '' si está vacía y null si es inválida.
function normalizar_ipv4_equipo(string $ip): ?string
{
    if ($ip === '') {
        return '';
    }

    $octetos = explode('.', $ip);
    if (count($octetos) !== 4) {
        return null;
    }

    foreach ($octetos as $octeto) {
        if (!preg_match('/\A\d{1,3}\z/', $octeto) || (int)$octeto > 255) {
            return null;
        }
    }

    $ipNormalizada = implode('.', array_map(function ($octeto) {
        return (string)(int)$octeto;
    }, $octetos));

    return filter_var($ipNormalizada, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
        ? $ipNormalizada
        : null;
}

// Valida una MAC y la devuelve como AA:BB:CC:DD:EE:FF; '' si está vacía, null si es inválida.
function normalizar_mac_equipo(string $mac): ?string
{
    if ($mac === '') {
        return '';
    }

    $compacta = preg_replace('/[:-]/', '', $mac);
    if (!is_string($compacta) || !preg_match('/\A[0-9A-Fa-f]{12}\z/', $compacta)) {
        return null;
    }

    return strtoupper(implode(':', str_split($compacta, 2)));
}

// Devuelve un mensaje si algún identificador ya pertenece a otro equipo; null si no hay duplicados.
function mensaje_equipo_duplicado(mysqli $conexion, array $valores, int $excluirId = 0): ?string
{
    $etiquetas = [
        'codigo_institucional' => 'El código institucional',
        'numero_serie' => 'El número de serie',
        'mac' => 'La dirección MAC',
        'ip_asignada' => 'La dirección IP',
    ];
    foreach ($etiquetas as $campo => $etiqueta) {
        $valor = trim((string)($valores[$campo] ?? ''));
        if ($valor === '') {
            continue;
        }
        $stmt = $conexion->prepare("SELECT codigo_equipo FROM equipos WHERE $campo = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $valor, $excluirId);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_assoc();
        if ($existente) {
            return $etiqueta . ' "' . $valor . '" ya está en uso en el equipo ' . $existente['codigo_equipo'] . '.';
        }
    }
    return null;
}
