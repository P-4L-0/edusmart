<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);
header('Content-Type: application/json');
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_materia'])) {
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($id <= 0) throw new Exception("ID inválido");

        // Eliminar relaciones con maestros
        $db->query("DELETE FROM maestros_materias WHERE materia_id = :id");
        $db->bind(':id', $id);
        $db->execute();

        // Eliminar relaciones con niveles
        $db->query("DELETE FROM materias_niveles WHERE materia_id = :id");
        $db->bind(':id', $id);
        $db->execute();

        // Eliminar notas de actividades de la materia
        $db->query("
            DELETE n FROM notas n
            JOIN actividades a ON n.actividad_id = a.id
            WHERE a.materia_id = :id
        ");
        $db->bind(':id', $id);
        $db->execute();

        // Eliminar actividades asociadas a la materia
        $db->query("DELETE FROM actividades WHERE materia_id = :id");
        $db->bind(':id', $id);
        $db->execute();

        // Finalmente eliminar la materia
        $db->query("DELETE FROM materias WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Materia eliminada correctamente junto con todas sus relaciones.'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// Método no permitido
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
]);
