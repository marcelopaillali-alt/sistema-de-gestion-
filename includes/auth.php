<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta relativa hacia la raíz del proyecto (ej: "../../" desde admin/usuarios/).
function ruta_raiz(): string {
    $raiz = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
    $dirScript = str_replace('\\', '/', dirname((string)realpath($_SERVER['SCRIPT_FILENAME'])));
    $relativo = trim(substr($dirScript, strlen($raiz)), '/');
    return $relativo === '' ? '' : str_repeat('../', substr_count($relativo, '/') + 1);
}

// Redirige al login si no hay sesión iniciada.
function exigir_login(): void {
    if (!isset($_SESSION['id'])) {
        header("Location: " . ruta_raiz() . "login.php?error=Debe iniciar sesión.");
        exit;
    }
}

// Exige sesión y el rol indicado. 'admin' también admite al técnico de soporte (mismos privilegios).
function exigir_rol(string $rol): void {
    exigir_login();
    $rolSesion = $_SESSION['rol'] ?? '';
    $permitido = $rol === 'admin' ? in_array($rolSesion, ['admin', 'tecnico'], true) : $rolSesion === $rol;
    if (!$permitido) {
        header("Location: " . ruta_raiz() . "index.php");
        exit;
    }
}

// Devuelve la clase CSS del color según la prioridad de la solicitud.
function clase_prioridad($prioridad): string {
    $clases = [
        'Crítica' => 'priority-high',
        'Alta' => 'priority-high',
        'Media' => 'priority-medium',
        'Baja' => 'priority-low',
    ];
    return $clases[(string)$prioridad] ?? 'status-default';
}

// Escapa texto para mostrarlo de forma segura en HTML.
function e($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>