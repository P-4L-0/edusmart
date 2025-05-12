<?php
// Incluir el archivo de configuración y proteger la página para que solo los administradores puedan acceder
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

// Verificar si se ha proporcionado un ID en la URL
if (!isset($_GET['id'])) {
    // Si no se proporciona un ID, establecer un mensaje de error y redirigir a la página de usuarios
    $_SESSION['error'] = 'ID no proporcionado';
    header("Location: usuarios.php");
    exit;
}

// Convertir el ID proporcionado en un entero para evitar inyecciones SQL
$id = intval($_GET['id']);

// Crear una instancia de la base de datos
$db = new Database();

// Preparar la consulta para reactivar al usuario (establecer el campo `activo` en 1)
$db->query("UPDATE usuarios SET activo = 1 WHERE id = :id");
$db->bind(':id', $id);

// Ejecutar la consulta y verificar si fue exitosa
if ($db->execute()) {
    // Si la consulta se ejecuta correctamente, establecer un mensaje de éxito
    $_SESSION['success'] = "Usuario reactivado correctamente";
} else {
    // Si ocurre un error, establecer un mensaje de error
    $_SESSION['error'] = "Error al reactivar usuario";
}

// Redirigir a la página de usuarios después de completar la operación
header("Location: usuarios.php");