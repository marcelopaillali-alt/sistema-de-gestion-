<?php
session_start();
require_once "conexion.php";

$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
    header("Location: login.php?error=Complete todos los campos.");
    exit;
}

$stmt = $conexion->prepare("SELECT id, rut, nombre, apellido, correo, contrasena, cargo, area, lugar, ubicacion, rol, estado FROM usuarios WHERE correo = ? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    header("Location: login.php?error=Correo o contraseña incorrectos.");
    exit;
}

$usuario = $resultado->fetch_assoc();

if (!password_verify($contrasena, $usuario['contrasena'])) {
    header("Location: login.php?error=Correo o contraseña incorrectos.");
    exit;
}

if ($usuario['estado'] === 'bloqueado') {
    header("Location: login.php?error=Su cuenta se encuentra bloqueada. Contacte al administrador.");
    exit;
}
if ($usuario['estado'] === 'inactivo') {
    header("Location: login.php?error=Su cuenta se encuentra inactiva. Contacte al administrador.");
    exit;
}

session_regenerate_id(true);
$_SESSION['id'] = (int)$usuario['id'];
$_SESSION['rut'] = $usuario['rut'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['apellido'] = $usuario['apellido'];
$_SESSION['correo'] = $usuario['correo'];
$_SESSION['cargo'] = $usuario['cargo'];
$_SESSION['area'] = $usuario['area'];
$_SESSION['lugar'] = $usuario['lugar'];
$_SESSION['ubicacion'] = $usuario['ubicacion'];
$_SESSION['estado'] = $usuario['estado'];
$_SESSION['rol'] = $usuario['rol'];

// Cookie visible en el navegador con el RUT del usuario que tiene la sesión activa.
setcookie('usuario_activo', $usuario['rut'], time() + 86400, '/', '', false, true);

if (in_array($usuario['rol'], ['admin', 'tecnico'], true)) {
    header("Location: admin/menu.php");
} else {
    header("Location: usuario/menu.php");
}
exit;
?>