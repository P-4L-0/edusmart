<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y la base de datos
require_once __DIR__ . '/../includes/config.php';

// Leer los datos enviados en formato JSON desde el cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Verificar si los datos necesarios están presentes en la solicitud
if (!isset($data['maestro_id'], $data['materia_id'], $data['asignar'])) {
    // Si faltan datos, devolver una respuesta JSON indicando el error
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Sanitizar y validar los datos recibidos
$maestro_id = intval($data['maestro_id']); // Convertir el ID del maestro a un entero
$materia_id = intval($data['materia_id']); // Convertir el ID de la materia a un entero
$asignar = filter_var($data['asignar'], FILTER_VALIDATE_BOOLEAN); // Validar el valor booleano de "asignar"

// Crear una instancia de la base de datos
$db = new Database();

try {
    if ($asignar) {
        // Asignar la materia al maestro
        $db->query("INSERT INTO maestros_materias (maestro_id, materia_id) 
                    VALUES (:maestro_id, :materia_id)
                    ON DUPLICATE KEY UPDATE maestro_id = maestro_id");
        $db->bind(':maestro_id', $maestro_id); // Asociar el ID del maestro
        $db->bind(':materia_id', $materia_id); // Asociar el ID de la materia
        $db->execute(); // Ejecutar la consulta
    } else {
        // Eliminar la asignación de la materia al maestro
        $db->query("DELETE FROM maestros_materias 
                    WHERE maestro_id = :maestro_id AND materia_id = :materia_id");
        $db->bind(':maestro_id', $maestro_id); // Asociar el ID del maestro
        $db->bind(':materia_id', $materia_id); // Asociar el ID de la materia
        $db->execute(); // Ejecutar la consulta
    }

    // Devolver una respuesta JSON indicando éxito
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Si ocurre un error, devolver una respuesta JSON con el mensaje de error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}