<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guarda lo escrito en un formulario (sin contraseñas ni PIN) para volver a mostrarlo si hay un error.
function recordar_formulario(string $clave, array $datos): void {
    $campos = ['contrasena', 'repetir', 'repetir_contrasena', 'pin_recuperacion'];
    foreach ($campos as $campo) {
        unset($datos[$campo]);
    }
    $_SESSION['_formularios'][$clave] = $datos;
}

// Devuelve lo guardado para el formulario y lo elimina de la sesión.
function recuperar_formulario(string $clave): array {
    $datos = $_SESSION['_formularios'][$clave] ?? [];
    unset($_SESSION['_formularios'][$clave]);
    return is_array($datos) ? $datos : [];
}

function olvidar_formulario(string $clave): void {
    unset($_SESSION['_formularios'][$clave]);
}
