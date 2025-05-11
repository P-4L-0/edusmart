<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

$id = intval($_GET['id']);
$db = new Database();
$db->query("SELECT * FROM grupos WHERE id = :id");
$db->bind(':id', $id);
$grupo = $db->single();

if ($grupo) {
    echo json_encode($grupo);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Grupo no encontrado']);
}