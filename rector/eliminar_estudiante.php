<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['estudiante_id'])) {
    $estudiante_id = intval($_POST['estudiante_id']);

    if ($estudiante_id <= 0) {
        echo json_encode(['error' => 'ID de estudiante inválido.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Eliminar notas relacionadas
        $db->query("DELETE FROM notas WHERE estudiante_id = :id");
        $db->bind(':id', $estudiante_id);
        $db->execute();

        // Eliminar al estudiante
        $db->query("DELETE FROM estudiantes WHERE id = :id");
        $db->bind(':id', $estudiante_id);
        $db->execute();

        $db->commit();

        echo json_encode(['success' => 'Estudiante eliminado correctamente.']);
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['error' => 'Error al eliminar estudiante: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Solicitud inválida.']);
}
