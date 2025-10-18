<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$nivel_id = intval($_GET['nivel_id'] ?? 0);
if ($nivel_id <= 0) { echo json_encode([]); exit; }

$db = new Database();
$db->query("
    SELECT m.id, m.nombre 
    FROM materias m
    INNER JOIN materias_niveles mn ON m.id = mn.materia_id
    WHERE mn.nivel_id = :nivel_id AND m.activa = 1
    ORDER BY m.nombre
");
$db->bind(':nivel_id', $nivel_id);
$materias = $db->resultSet();

echo json_encode($materias);
