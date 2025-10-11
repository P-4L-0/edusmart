<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

$db = new Database();
header('Content-Type: application/json');

// Obtengo los datos por el metodo get
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->query("SELECT * FROM materias WHERE id = :id");
    $db->bind(':id', $id);
    $materia = $db->single();

    if (!$materia) {
        echo json_encode(['success' => false, 'message' => 'Materia no encontrada']);
        exit;
    }

    echo json_encode(['success' => true, 'materia' => $materia]);
    exit;
}

// metodo para actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_materia'])) {
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $activa = isset($_POST['activa']) ? 1 : 0;

    try {
        if ($id <= 0) throw new Exception("ID de materia inválido.");
        if (empty($nombre)) throw new Exception("El nombre de la materia es obligatorio.");

        $db->query("UPDATE materias SET 
                    nombre = :nombre,
                    descripcion = :descripcion,
                    activa = :activa
                    WHERE id = :id");
        $db->bind(':nombre', $nombre);
        $db->bind(':descripcion', $descripcion);
        $db->bind(':activa', $activa);
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'materia' => ['id'=>$id,'nombre'=>$nombre,'descripcion'=>$descripcion,'activa'=>$activa]]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
