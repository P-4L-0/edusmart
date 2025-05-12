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
    $nie = trim($_POST['nie']); // Número de Identificación del Estudiante
    $nombre = trim($_POST['nombre_completo']); // Nombre completo del estudiante
    $grupo_id = intval($_POST['grupo_id']); // ID del grupo al que pertenece el estudiante
    
    // Preparar la consulta para insertar un nuevo estudiante en la base de datos
    $db->query("INSERT INTO estudiantes (nie, nombre_completo, grupo_id) 
               VALUES (:nie, :nombre, :grupo_id)");
    $db->bind(':nie', $nie); // Asociar el NIE del estudiante
    $db->bind(':nombre', $nombre); // Asociar el nombre del estudiante
    $db->bind(':grupo_id', $grupo_id); // Asociar el ID del grupo
    $db->execute(); // Ejecutar la consulta
    
    // Devolver una respuesta JSON indicando éxito
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Si ocurre un error, devolver una respuesta JSON con el mensaje de error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}