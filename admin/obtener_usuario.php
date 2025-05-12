<?php
// Incluir el archivo de configuración y funciones para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
require_once BASE_PATH . 'functions.php';

protegerPagina([1]); // Solo admin

// Verificar si se proporcionó el ID del usuario en la solicitud
if (!isset($_GET['id'])) {
    // Si no se proporciona el ID, devolver un código de respuesta 400 (Bad Request)
    header("HTTP/1.1 400 Bad Request");
    exit;
}

// Crear una instancia de la base de datos
$db = new Database();
$id = intval($_GET['id']); // Sanitizar y convertir el ID a un entero

// Preparar la consulta para obtener los datos del usuario
$db->query("SELECT * FROM usuarios WHERE id = :id");
$db->bind(':id', $id);
$usuario = $db->single(); // Obtener un único resultado

// Verificar si se encontró el usuario
if (!$usuario) {
    // Si no se encuentra el usuario, devolver un código de respuesta 404 (Not Found)
    header("HTTP/1.1 404 Not Found");
    exit;
}

// Establecer el tipo de contenido de la respuesta como JSON
header('Content-Type: application/json');

// Devolver los datos del usuario en formato JSON
echo json_encode($usuario);
?>