<?php
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';
protegerPagina([1]);

header('Content-Type: application/json');

$db = new Database();

try {
    $nie = trim($_POST['nie']);
    $nombre = trim($_POST['nombre_completo']);
    $grupo_id = intval($_POST['grupo_id']);
    
    $db->query("INSERT INTO estudiantes (nie, nombre_completo, grupo_id) 
               VALUES (:nie, :nombre, :grupo_id)");
    $db->bind(':nie', $nie);
    $db->bind(':nombre', $nombre);
    $db->bind(':grupo_id', $grupo_id);
    $db->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}