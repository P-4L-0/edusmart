<?php 
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';
protegerPagina([1]); // Solo admin

// Procesar formulario de creación de materia
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    
    $db = new Database();
    $db->query("INSERT INTO materias (nombre, descripcion) VALUES (:nombre, :descripcion)");
    $db->bind(':nombre', $nombre);
    $db->bind(':descripcion', $descripcion);
    
    if($db->execute()) {
        $_SESSION['success'] = "Materia creada exitosamente";
        header('Location: materias.php');
        exit;
    } else {
        $error = "Error al crear la materia";
    }
}

// Obtener lista de materias
$db = new Database();
$db->query("SELECT * FROM materias ORDER BY nombre");
$materias = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar (repetir el mismo de dashboard.php) -->
        <?php include './partials/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Gestión de Materias</h2>
            
            <?php if(isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Formulario de creación de materia -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h3 class="text-xl font-semibold mb-4">Agregar Nueva Materia</h3>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="nombre" class="block text-gray-700 mb-2">Nombre de la Materia</label>
                            <input type="text" id="nombre" name="nombre" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="descripcion" class="block text-gray-700 mb-2">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="bg-blue-500 text-white py-2 px-6 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            Guardar Materia
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de materias -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-4">Lista de Materias</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($materias as $materia): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($materia->nombre); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($materia->descripcion); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $materia->activa ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $materia->activa ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="editar_materia.php?id=<?php echo $materia->id; ?>" class="text-blue-500 hover:text-blue-700 mr-2">Editar</a>
                                    <?php if($materia->activa): ?>
                                        <a href="desactivar_materia.php?id=<?php echo $materia->id; ?>" class="text-yellow-500 hover:text-yellow-700">Desactivar</a>
                                    <?php else: ?>
                                        <a href="activar_materia.php?id=<?php echo $materia->id; ?>" class="text-green-500 hover:text-green-700">Activar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>