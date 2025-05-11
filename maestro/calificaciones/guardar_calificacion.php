<?php
require_once __DIR__ . '/../../includes/config.php';
require_once BASE_PATH . 'functions.php';

protegerPagina([3]); // Solo maestros
header('Content-Type: application/json');

$db = new Database();
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (empty($data['estudiante_id']) || empty($data['actividad_id']) || !isset($data['calificacion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar que el maestro tenga permiso para esta actividad
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
    
    // Verificar que el estudiante pertenezca al grupo de la actividad
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
    
    // Verificar si ya existe una calificación
    $db->query("SELECT id FROM notas 
                WHERE estudiante_id = :estudiante_id AND actividad_id = :actividad_id");
    $db->bind(':estudiante_id', $data['estudiante_id']);
    $db->bind(':actividad_id', $data['actividad_id']);
    $nota = $db->single();
    
    if ($nota) {
        // Actualizar calificación existente
        $db->query("UPDATE notas SET calificacion = :calificacion 
                    WHERE id = :id");
        $db->bind(':calificacion', $data['calificacion']);
        $db->bind(':id', $nota->id);
    } else {
        // Insertar nueva calificación
        $db->query("INSERT INTO notas (estudiante_id, actividad_id, calificacion) 
                    VALUES (:estudiante_id, :actividad_id, :calificacion)");
        $db->bind(':estudiante_id', $data['estudiante_id']);
        $db->bind(':actividad_id', $data['actividad_id']);
        $db->bind(':calificacion', $data['calificacion']);
    }
    
    $db->execute();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}