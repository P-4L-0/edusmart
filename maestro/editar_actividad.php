<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $porcentaje = floatval($_POST['porcentaje']);
    $materia_id = $_POST['materia_id'];
    $trimestre = $_POST['trimestre'];

    // Verificar si ya existe la actividad
    $stmt = $conn->prepare("SELECT porcentaje FROM actividades WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($porcentaje_original);
    if (!$stmt->fetch()) {
        $stmt->close();
        header("Location: materias.php?error=Actividad no encontrada");
        exit();
    }
    $stmt->close();

    // Obtener la suma de los porcentajes del trimestre excluyendo esta actividad
    $stmt = $conn->prepare("SELECT SUM(porcentaje) FROM actividades WHERE materia_id = ? AND trimestre = ? AND id != ?");
    $stmt->bind_param("iii", $materia_id, $trimestre, $id);
    $stmt->execute();
    $stmt->bind_result($suma);
    $stmt->fetch();
    $stmt->close();

    $suma = $suma ?? 0;
    $total = $suma + $porcentaje;

    if ($total > 100) {
        header("Location: materias.php?error=El porcentaje total excede el 100%");
        exit();
    }

    // Actualizar la actividad
    $stmt = $conn->prepare("UPDATE actividades SET nombre = ?, descripcion = ?, porcentaje = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $nombre, $descripcion, $porcentaje, $id);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: materias.php?success=Actividad actualizada correctamente");
        exit();
    } else {
        $stmt->close();
        header("Location: materias.php?error=Error al actualizar la actividad");
        exit();
    }
}
?>
