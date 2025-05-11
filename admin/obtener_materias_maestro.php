<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$maestro_id = intval($_GET['id']);
$db = new Database();

$db->query("SELECT m.id, m.nombre, 
                   CASE WHEN mm.maestro_id IS NOT NULL THEN 1 ELSE 0 END AS asignada
            FROM materias m
            LEFT JOIN maestros_materias mm ON m.id = mm.materia_id AND mm.maestro_id = :maestro_id
            WHERE m.activa = 1
            ORDER BY m.nombre");
$db->bind(':maestro_id', $maestro_id);

$materias = $db->resultSet();
echo json_encode($materias);