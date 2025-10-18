<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);
header('Content-Type: application/json');
$db = new Database();

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['eliminar_materia'])){
    $id = intval($_POST['id'] ?? 0);
    try{
        if($id<=0) throw new Exception("ID inválido");

        $db->query("DELETE FROM materias_niveles WHERE materia_id=:id");
        $db->bind(':id',$id);
        $db->execute();

        $db->query("DELETE FROM materias WHERE id=:id");
        $db->bind(':id',$id);
        $db->execute();

        echo json_encode(['success'=>true]);
    }catch(Exception $e){
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}
echo json_encode(['success'=>false,'message'=>'Método no permitido']);
