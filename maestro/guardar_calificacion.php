<?php
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';
require_once 'D:\xampp\htdocs\smartedu\includes\functions.php';

protegerPagina([3]); // Solo maestros
header('Content-Type: application/json');

$db = new Database();
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (!isset($data['estudiante_id']) || !isset($data['actividad_id']) || !isset($data['calificacion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Convertir calificación a float (puede ser null para eliminar)
$calificacion = !empty($data['calificacion']) ? floatval($data['calificacion']) : null;

try {
    // Verificar que el maestro tiene permiso para esta actividad
    $db->query("SELECT a.id 
                FROM actividades a
                JOIN grupos g ON a.grupo_id = g.id
                JOIN maestros_materias mm ON a.materia_id = mm.materia_id
                WHERE a.id = :actividad_id 
                AND g.maestro_id = :maestro_id
                AND mm.maestro_id = :maestro_id");
    $db->bind(':actividad_id', $data['actividad_id']);
    $db->bind(':maestro_id', $_SESSION['user_id']);
    $actividad = $db->single();
    
    if (!$actividad) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para modificar esta actividad']);
        exit;
    }
    
    // Verificar que el estudiante pertenece al grupo de la actividad
    $db->query("SELECT e.id 
                FROM estudiantes e
                JOIN actividades a ON e.grupo_id = a.grupo_id
                WHERE e.id = :estudiante_id AND a.id = :actividad_id");
    $db->bind(':estudiante_id', $data['estudiante_id']);
    $db->bind(':actividad_id', $data['actividad_id']);
    $estudiante = $db->single();
    
    if (!$estudiante) {
        echo json_encode(['success' => false, 'message' => 'Estudiante no pertenece al grupo de esta actividad']);
        exit;
    }
    
    // Si hay un ID de nota, es una actualización
    if (!empty($data['nota_id'])) {
        if ($calificacion === null) {
            // Eliminar la calificación si se envía vacía
            $db->query("DELETE FROM notas WHERE id = :id");
            $db->bind(':id', $data['nota_id']);
        } else {
            // Actualizar calificación existente
            $db->query("UPDATE notas SET calificacion = :calificacion WHERE id = :id");
            $db->bind(':calificacion', $calificacion);
            $db->bind(':id', $data['nota_id']);
        }
    } else {
        if ($calificacion !== null) {
            // Insertar nueva calificación solo si hay valor
            $db->query("INSERT INTO notas (estudiante_id, actividad_id, calificacion) 
                        VALUES (:estudiante_id, :actividad_id, :calificacion)");
            $db->bind(':estudiante_id', $data['estudiante_id']);
            $db->bind(':actividad_id', $data['actividad_id']);
            $db->bind(':calificacion', $calificacion);
        }
    }
    
    $db->execute();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}