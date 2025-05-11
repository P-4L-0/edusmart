<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $grupo_id = intval($_POST['grupo_id']);
    $archivo = $_FILES['archivo'];
    
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if ($extension !== 'xlsx' && $extension !== 'xls') {
        $_SESSION['error'] = "Solo se permiten archivos Excel (.xlsx, .xls)";
        header("Location: grupos.php");
        exit;
    }
    
    try {
        require 'D:\xampp\htdocs\smartedu\vendor\autoload.php';
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        
        $data = $sheet->toArray();
        
        $startRow = 0;
        foreach ($data as $index => $row) {
            if (isset($row[0]) && trim($row[0]) === 'No.') {
                $startRow = $index + 1;
                break;
            }
        }
        
        if ($startRow === 0) {
            throw new Exception("No se encontró el inicio de la lista de estudiantes");
        }
        
        $db->beginTransaction();
        $insertados = 0;
        
        for ($i = $startRow; $i < count($data); $i++) {
            $row = $data[$i];
            
            if (empty($row[1]) || empty($row[2])) {
                continue;
            }
            
            $nie = trim($row[1]);
            $nombre = trim($row[2]);
            
            $db->query("INSERT INTO estudiantes (nie, nombre_completo, grupo_id) 
                       VALUES (:nie, :nombre, :grupo_id)");
            $db->bind(':nie', $nie);
            $db->bind(':nombre', $nombre);
            $db->bind(':grupo_id', $grupo_id);
            
            if ($db->execute()) {
                $insertados++;
            }
        }
        
        $db->commit();
        $_SESSION['success'] = "Se importaron $insertados estudiantes correctamente";
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error al importar: " . $e->getMessage();
    }
    
    header("Location: grupos.php");
    exit;
}