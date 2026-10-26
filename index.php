<?php
session_start();

if (isset($_SESSION['id'])) {
    if (in_array($_SESSION['rol'], ['admin', 'tecnico'], true)) {
        header("Location: admin/menu.php");
    } else {
        header("Location: usuario/menu.php");
    }
    exit;
}
header("Location: login.php");
exit;
?>