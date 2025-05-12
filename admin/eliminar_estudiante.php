<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo administradores

// Establecer el tipo de contenido de la respuesta como JSON
header('Content-Type: application/json');

// Crear una instancia de la base de datos
$db = new Database();

try {
    // Leer y sanitizar los datos enviados desde el formulario
    $id = intval($_POST['id']); // ID del estudiante a eliminar
    $grupo_id = intval($_POST['grupo_id']); // ID del grupo al que pertenece el estudiante (si aplica)
    
    // Preparar la consulta para eliminar al estudiante
    $db->query("DELETE FROM estudiantes WHERE id = :id");
    $db->bind(':id', $id); // Asociar el ID del estudiante
    $db->execute(); // Ejecutar la consulta
    
    // Devolver una respuesta JSON indicando éxito
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Si ocurre un error, devolver una respuesta JSON con el mensaje de error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}