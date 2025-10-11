<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

header('Content-Type: application/json');
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_materia'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    try {
        $db->beginTransaction();
        $db->query("DELETE FROM maestros_materias WHERE materia_id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $db->query("DELETE FROM materias WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $db->commit();

        if ($db->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Materia eliminada correctamente junto con su asignación de maestros.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró la materia a eliminar.']);
        }
    } catch (Exception $e) {
        $db->rollBack();

        if (strpos($e->getMessage(), '1451') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'No se puede eliminar la materia porque tiene actividades registradas.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
