<?php
require_once __DIR__ . '/../includes/config.php';
require_once BASE_PATH . 'functions.php';

protegerPagina([1]); // Solo admin

if (!isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit;
}

$db = new Database();
$id = intval($_GET['id']);

$db->query("SELECT * FROM usuarios WHERE id = :id");
$db->bind(':id', $id);
$usuario = $db->single();

if (!$usuario) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

header('Content-Type: application/json');
echo json_encode($usuario);
?>