<?php
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';
require_once 'D:\xampp\htdocs\smartedu\includes\functions.php';

protegerPagina([3]); // Solo maestros
header('Content-Type: application/json');

$db = new Database();
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (empty($data['nombre']) || empty($data['porcentaje']) || empty($data['grupo_id']) || empty($data['materia_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar que el grupo pertenezca al maestro
    $db->query("SELECT id FROM grupos 
                WHERE id = :grupo_id AND maestro_id = :maestro_id");
    $db->bind(':grupo_id', $data['grupo_id']);
    $db->bind(':maestro_id', $_SESSION['user_id']);
    $grupo = $db->single();
    
    if (!$grupo) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para agregar actividades a este grupo']);
        exit;
    }
    
    // Verificar que la materia esté asignada al maestro
    $db->query("SELECT id FROM maestros_materias 
                WHERE maestro_id = :maestro_id AND materia_id = :materia_id");
    $db->bind(':maestro_id', $_SESSION['user_id']);
    $db->bind(':materia_id', $data['materia_id']);
    $materia = $db->single();
    
    if (!$materia) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para agregar actividades en esta materia']);
        exit;
    }
    
    // Verificar que la suma de porcentajes no exceda 100% en el trimestre
    $db->query("SELECT SUM(porcentaje) as total FROM actividades 
                WHERE grupo_id = :grupo_id AND materia_id = :materia_id AND trimestre = :trimestre");
    $db->bind(':grupo_id', $data['grupo_id']);
    $db->bind(':materia_id', $data['materia_id']);
    $db->bind(':trimestre', $data['trimestre']);
    $result = $db->single();
    $total_porcentaje = $result->total ?? 0;
    
    if (($total_porcentaje + $data['porcentaje']) > 100) {
        echo json_encode(['success' => false, 'message' => 'La suma de porcentajes no puede exceder el 100%']);
        exit;
    }
    
    // Insertar nueva actividad
    $db->query("INSERT INTO actividades (nombre, descripcion, porcentaje, trimestre, materia_id, grupo_id) 
                VALUES (:nombre, :descripcion, :porcentaje, :trimestre, :materia_id, :grupo_id)");
    $db->bind(':nombre', $data['nombre']);
    $db->bind(':descripcion', $data['descripcion'] ?? null);
    $db->bind(':porcentaje', $data['porcentaje']);
    $db->bind(':trimestre', $data['trimestre']);
    $db->bind(':materia_id', $data['materia_id']);
    $db->bind(':grupo_id', $data['grupo_id']);
    
    $db->execute();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}