<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

// Establecer el tipo de contenido de la respuesta como JSON
header('Content-Type: application/json');

// Verificar si se proporcionó el ID del grupo en la solicitud
if (!isset($_GET['id'])) {
    // Si no se proporciona el ID, devolver un código de respuesta 400 (Bad Request) y un mensaje de error
    http_response_code(400);
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

// Sanitizar y convertir el ID a un entero
$id = intval($_GET['id']);

// Crear una instancia de la base de datos
$db = new Database();

// Preparar la consulta para obtener los datos del grupo
$db->query("SELECT * FROM grupos WHERE id = :id");
$db->bind(':id', $id);

// Ejecutar la consulta y obtener el grupo
$grupo = $db->single();

// Verificar si se encontró el grupo
if ($grupo) {
    // Si se encuentra el grupo, devolver los datos en formato JSON
    echo json_encode($grupo);
} else {
    // Si no se encuentra el grupo, devolver un código de respuesta 404 (Not Found) y un mensaje de error
    http_response_code(404);
    echo json_encode(['error' => 'Grupo no encontrado']);
}