<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

header('Content-Type: application/json');
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_materia'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    try {
        $db->query("DELETE FROM materias WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido']);
