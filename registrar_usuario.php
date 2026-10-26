<?php
require_once "conexion.php";
require_once "includes/usuarios_historial.php";
require_once "includes/formulario.php";
recordar_formulario('registro', $_POST);

$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$cargo = trim($_POST['cargo'] ?? '');
$area = trim($_POST['area'] ?? '');
$lugar = trim($_POST['lugar'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$repetir = $_POST['repetir'] ?? '';

if ($rut === '' || $nombre === '' || $apellido === '' || $correo === '' || $contrasena === '') {
    header("Location: registro.php?error=Complete los campos obligatorios.");
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: registro.php?error=El correo no es válido.");
    exit;
}
if ($contrasena !== $repetir) {
    header("Location: registro.php?error=Las contraseñas no coinciden.");
    exit;
}
if (strlen($contrasena) < 6) {
    header("Location: registro.php?error=La contraseña debe tener al menos 6 caracteres.");
    exit;
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$rol = "usuario";

$stmt = $conexion->prepare("INSERT INTO usuarios (rut,nombre,apellido,correo,contrasena,cargo,area,lugar,ubicacion,rol) VALUES (?,?,?,?,?,?,?,?,?,?)");
$stmt->bind_param("ssssssssss", $rut, $nombre, $apellido, $correo, $hash, $cargo, $area, $lugar, $ubicacion, $rol);

try {
    $conexion->begin_transaction();
    $stmt->execute();
    $nuevoId = (int)$conexion->insert_id;
    registrar_historial_perfil(
        $conexion,
        $nuevoId,
        $rut,
        $nombre . ' ' . $apellido,
        'Creación de perfil',
        'Perfil creado mediante el registro público.',
        null,
        'Registro público'
    );
    $conexion->commit();
    olvidar_formulario('registro');
    header("Location: login.php?ok=Cuenta creada correctamente. Ya puede iniciar sesión.");
    exit;
} catch (mysqli_sql_exception $e) {
    $conexion->rollback();
    if ((int)$e->getCode() === 1062) {
        $msg = stripos($e->getMessage(), 'rut') !== false ? "El RUT ya está registrado." : "El correo ya está registrado.";
        header("Location: registro.php?error=" . urlencode($msg));
    } else {
        header("Location: registro.php?error=No se pudo registrar el usuario.");
    }
    exit;
}
?>