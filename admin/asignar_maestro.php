<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo administradores

// Crear una instancia de la base de datos
$db = new Database();

// Leer y sanitizar los datos enviados desde el formulario
$grupo_id = intval($_POST['grupo_id']); // ID del grupo al que se asignará el maestro
$maestro_id = !empty($_POST['maestro_id']) ? intval($_POST['maestro_id']) : null; // ID del maestro (puede ser null)

// Preparar la consulta para actualizar el maestro asignado al grupo
$db->query("UPDATE grupos SET maestro_id = :maestro_id WHERE id = :grupo_id");
$db->bind(':maestro_id', $maestro_id); // Asociar el ID del maestro
$db->bind(':grupo_id', $grupo_id); // Asociar el ID del grupo

// Ejecutar la consulta y verificar si fue exitosa
if ($db->execute()) {
    // Si la consulta se ejecuta correctamente, establecer un mensaje de éxito
    $_SESSION['success'] = "Maestro asignado correctamente";
} else {
    // Si ocurre un error, establecer un mensaje de error
    $_SESSION['error'] = "Error al asignar maestro";
}

// Redirigir a la página de detalles del grupo después de completar la operación
header("Location: ver_grupo.php?id=$grupo_id");