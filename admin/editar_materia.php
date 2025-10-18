<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]);
$db = new Database();
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $db->query("SELECT * FROM materias WHERE id=:id");
    $db->bind(':id',$id);
    $materia = $db->single();
    if(!$materia) echo json_encode(['success'=>false,'message'=>'Materia no encontrada']),exit;

    // Niveles asociados
    $db->query("SELECT nivel_id FROM materias_niveles WHERE materia_id=:id");
    $db->bind(':id',$id);
    $niveles_assoc = $db->resultSet();
    $niveles_ids = array_map(fn($n)=>$n->nivel_id,$niveles_assoc);

    $materia->niveles_ids = $niveles_ids;
    echo json_encode(['success'=>true,'materia'=>$materia]);
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['editar_materia'])){
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $activa = isset($_POST['activa']) ? 1 : 0;
    $niveles = $_POST['niveles'] ?? [];

    try{
        if($id<=0) throw new Exception("ID inválido");
        if(empty($nombre)) throw new Exception("El nombre es obligatorio");

        $db->query("UPDATE materias SET nombre=:nombre, descripcion=:descripcion, activa=:activa WHERE id=:id");
        $db->bind(':nombre',$nombre);
        $db->bind(':descripcion',$descripcion);
        $db->bind(':activa',$activa);
        $db->bind(':id',$id);
        $db->execute();

        // Actualizar niveles
        $db->query("DELETE FROM materias_niveles WHERE materia_id=:id");
        $db->bind(':id',$id);
        $db->execute();

        foreach($niveles as $nivel_id){
            $db->query("INSERT INTO materias_niveles(materia_id,nivel_id) VALUES(:id,:nivel_id)");
            $db->bind(':id',$id);
            $db->bind(':nivel_id',$nivel_id);
            $db->execute();
        }

        echo json_encode(['success'=>true]);
    } catch(Exception $e){
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Método no permitido']);
