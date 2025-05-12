<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales
require_once __DIR__ . '/../includes/config.php';

// Verificar si se proporcionó el ID del maestro en la solicitud
if (!isset($_GET['id'])) {
    // Si no se proporciona el ID, devolver un array vacío en formato JSON
    echo json_encode([]);
    exit;
}

// Sanitizar y convertir el ID del maestro a un entero
$maestro_id = intval($_GET['id']);

// Crear una instancia de la base de datos
$db = new Database();

// Preparar la consulta para obtener las materias y verificar si están asignadas al maestro
$db->query("SELECT m.id, m.nombre, 
                   CASE WHEN mm.maestro_id IS NOT NULL THEN 1 ELSE 0 END AS asignada
            FROM materias m
            LEFT JOIN maestros_materias mm ON m.id = mm.materia_id AND mm.maestro_id = :maestro_id
            WHERE m.activa = 1
            ORDER BY m.nombre");
$db->bind(':maestro_id', $maestro_id);

// Ejecutar la consulta y obtener los resultados
$materias = $db->resultSet();

// Devolver las materias en formato JSON
echo json_encode($materias);