<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID no proporcionado';
    header("Location: usuarios.php");
    exit;
}

$id = intval($_GET['id']);
$db = new Database();
$db->query("UPDATE usuarios SET activo = 1 WHERE id = :id");
$db->bind(':id', $id);

if ($db->execute()) {
    $_SESSION['success'] = "Usuario reactivado correctamente";
} else {
    $_SESSION['error'] = "Error al reactivar usuario";
}

header("Location: usuarios.php");