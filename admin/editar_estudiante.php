<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);

header('Content-Type: application/json');

$db = new Database();

try {
    $id = intval($_POST['id']);
    $nie = trim($_POST['nie']);
    $nombre = trim($_POST['nombre_completo']);
    $grupo_id = intval($_POST['grupo_id']);
    
    $db->query("UPDATE estudiantes SET 
               nie = :nie, 
               nombre_completo = :nombre,
               grupo_id = :grupo_id
               WHERE id = :id");
    $db->bind(':nie', $nie);
    $db->bind(':nombre', $nombre);
    $db->bind(':grupo_id', $grupo_id);
    $db->bind(':id', $id);
    $db->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}