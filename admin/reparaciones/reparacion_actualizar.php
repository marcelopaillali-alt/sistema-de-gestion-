<?php
require_once "../../conexion.php";
require_once "../../includes/auth.php";
exigir_rol('admin');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$csrf = $_POST['csrf'] ?? '';
if (!$id || !is_string($csrf) || empty($_SESSION['csrf_editar_reparacion']) || !hash_equals($_SESSION['csrf_editar_reparacion'], $csrf)) {
    http_response_code(403);
    exit('La sesión expiró. Recargue la página e inténtelo nuevamente.');
}
$problema = trim((string)($_POST['problema'] ?? ''));
$diagnostico = trim((string)($_POST['diagnostico'] ?? ''));
$solucion = trim((string)($_POST['solucion'] ?? ''));
if ($problema === '' || mb_strlen($problema) > 5000 || mb_strlen($diagnostico) > 5000 || mb_strlen($solucion) > 5000) {
    header('Location: reparacion_editar.php?id=' . $id . '&error=' . rawurlencode('Revise los campos. El problema es obligatorio y cada campo admite hasta 5000 caracteres.'));
    exit;
}
$stmt = $conexion->prepare("UPDATE reparaciones SET problema=?, diagnostico=?, solucion=? WHERE id=?");
$stmt->bind_param('sssi', $problema, $diagnostico, $solucion, $id);
$stmt->execute();
header('Location: reparaciones.php?ok=' . rawurlencode('Problema, diagnóstico y solución actualizados.'));
exit;
