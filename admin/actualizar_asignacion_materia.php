<?php
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['maestro_id'], $data['materia_id'], $data['asignar'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$maestro_id = intval($data['maestro_id']);
$materia_id = intval($data['materia_id']);
$asignar = filter_var($data['asignar'], FILTER_VALIDATE_BOOLEAN);

$db = new Database();

try {
    if ($asignar) {
        // Asignar materia
        $db->query("INSERT INTO maestros_materias (maestro_id, materia_id) 
                    VALUES (:maestro_id, :materia_id)
                    ON DUPLICATE KEY UPDATE maestro_id = maestro_id");
        $db->bind(':maestro_id', $maestro_id);
        $db->bind(':materia_id', $materia_id);
        $db->execute();
    } else {
        // Eliminar asignación de materia
        $db->query("DELETE FROM maestros_materias 
                    WHERE maestro_id = :maestro_id AND materia_id = :materia_id");
        $db->bind(':maestro_id', $maestro_id);
        $db->bind(':materia_id', $materia_id);
        $db->execute();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}