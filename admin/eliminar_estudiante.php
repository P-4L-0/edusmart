<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);

header('Content-Type: application/json');

$db = new Database();

try {
    $id = intval($_POST['id']);
    $grupo_id = intval($_POST['grupo_id']);
    
    $db->query("DELETE FROM estudiantes WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}