<?php
require_once __DIR__ . '/../includes/config.php';

// obtenemos los datos
if (isset($_GET['id']) && isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');

    $id = intval($_GET['id']);
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    // lo traemos segun el id al estudiante
    $db->query("
        SELECT e.id, e.nombre_completo, e.fecha_nacimiento, e.grupo_id,
               g.nombre AS grupo, g.grado, g.ciclo_escolar
        FROM estudiantes e
        LEFT JOIN grupos g ON e.grupo_id = g.id
        WHERE e.id = :id
        LIMIT 1
    ");
    $db->bind(':id', $id);
    $estudiante = $db->single();

    if (!$estudiante) {
        echo json_encode(['error' => 'Estudiante no encontrado']);
        exit;
    }

    $db->query("
        SELECT a.nombre AS actividad, a.trimestre, n.calificacion
        FROM actividades a
        LEFT JOIN notas n ON n.actividad_id = a.id AND n.estudiante_id = :id
        WHERE a.grupo_id = :grupo_id
        ORDER BY a.trimestre ASC
    ");
    $db->bind(':id', $id);
    $db->bind(':grupo_id', $estudiante->grupo_id ?? 0);
    $actividades = $db->resultSet();

    echo json_encode([
        'estudiante' => $estudiante,
        'actividades' => $actividades
    ]);
    exit;
}

// Lista de estudiantes
$db->query("SELECT * FROM estudiantes ORDER BY nombre_completo ASC");
$estudiantes = $db->resultSet();