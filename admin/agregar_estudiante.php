<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo administradores

header('Content-Type: application/json');

$db = new Database();

try {
    // Leer y sanitizar los datos enviados desde el formulario
    $id = trim($_POST['id']);
    $nombre = trim($_POST['nombre_completo']);
    $grupo_id = intval($_POST['grupo_id']);

    // Validaciones básicas
    if (empty($id) || empty($nombre) || $grupo_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    // Verificar si el NIE ya existe
    $db->query("SELECT COUNT(*) AS count FROM estudiantes WHERE id = :id");
    $db->bind(':id', $id);
    $result = $db->single(); // Retorna un objeto con la propiedad 'count'

    if ($result && $result->count > 0) {
        echo json_encode(['success' => false, 'message' => 'El NIE ya se encuentra registrado.']);
        exit;
    }

    // Insertar el estudiante
    $db->query("INSERT INTO estudiantes (id, nombre_completo, grupo_id) 
               VALUES (:id, :nombre, :grupo_id)");
    $db->bind(':id', $id);
    $db->bind(':nombre', $nombre);
    $db->bind(':grupo_id', $grupo_id);
    $db->execute();

    echo json_encode(['success' => true, 'message' => 'Estudiante agregado correctamente.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al agregar el estudiante: ' . $e->getMessage()]);
}
