<?php
require_once "conexion.php";
header('Content-Type: application/json; charset=utf-8');

$rut = strtoupper(preg_replace('/[^0-9Kk]/', '', (string)($_GET['rut'] ?? '')));
$excluirId = (int)($_GET['excluir'] ?? 0);

if (strlen($rut) < 2) {
    echo json_encode(['existe' => false]);
    exit;
}

$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE UPPER(REPLACE(REPLACE(rut,'.',''),'-',''))=? AND id<>? LIMIT 1");
$stmt->bind_param("si", $rut, $excluirId);
$stmt->execute();
echo json_encode(['existe' => $stmt->get_result()->num_rows > 0]);
