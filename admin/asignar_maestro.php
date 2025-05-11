<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);

$db = new Database();

$grupo_id = intval($_POST['grupo_id']);
$maestro_id = !empty($_POST['maestro_id']) ? intval($_POST['maestro_id']) : null;

$db->query("UPDATE grupos SET maestro_id = :maestro_id WHERE id = :grupo_id");
$db->bind(':maestro_id', $maestro_id);
$db->bind(':grupo_id', $grupo_id);

if ($db->execute()) {
    $_SESSION['success'] = "Maestro asignado correctamente";
} else {
    $_SESSION['error'] = "Error al asignar maestro";
}

header("Location: ver_grupo.php?id=$grupo_id");