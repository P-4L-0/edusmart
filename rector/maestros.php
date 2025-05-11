<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([2]); // Solo rector

$db = new Database();

// Obtener lista de maestros
$db->query("SELECT u.* FROM usuarios u WHERE u.rol_id = 3 ORDER BY u.nombre_completo");
$maestros = $db->resultSet();

// Obtener lista de materias para asignar
$db->query("SELECT * FROM materias WHERE activa = 1 ORDER BY nombre");
$materias = $db->resultSet();

// Procesar asignación de materia
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_materia'])) {
    $maestro_id = intval($_POST['maestro_id']);
    $materia_id = intval($_POST['materia_id']);
    
    // Verificar si ya existe la asignación
    $db->query("SELECT id FROM maestros_materias WHERE maestro_id = :maestro_id AND materia_id = :materia_id");
    $db->bind(':maestro_id', $maestro_id);
    $db->bind(':materia_id', $materia_id);
    $existe = $db->single();
    
    if(!$existe) {
        $db->query("INSERT INTO maestros_materias (maestro_id, materia_id) VALUES (:maestro_id, :materia_id)");
        $db->bind(':maestro_id', $maestro_id);
        $db->bind(':materia_id', $materia_id);
        
        if($db->execute()) {
            $_SESSION['success'] = "Materia asignada correctamente al maestro";
            header('Location: maestros.php');
            exit;
        } else {
            $error = "Error al asignar la materia";
        }
    } else {
        $error = "Este maestro ya tiene asignada esta materia";
    }
}

// Procesar eliminación de asignación
if(isset($_GET['eliminar_asignacion'])) {
    $asignacion_id = intval($_GET['eliminar_asignacion']);
    
    $db->query("DELETE FROM maestros_materias WHERE id = :id");
    $db->bind(':id', $asignacion_id);
    
    if($db->execute()) {
        $_SESSION['success'] = "Asignación eliminada correctamente";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        $error = "Error al eliminar la asignación";
    }
}

// Ver detalles de un maestro específico
$maestro_detalle = null;
$materias_asignadas = [];
if(isset($_GET['ver'])) {
    $maestro_id = intval($_GET['ver']);
    
    // Obtener información del maestro
    $db->query("SELECT * FROM usuarios WHERE id = :id AND rol_id = 3");
    $db->bind(':id', $maestro_id);
    $maestro_detalle = $db->single();
    
    if($maestro_detalle) {
        // Obtener materias asignadas
        $db->query("SELECT mm.id as asignacion_id, m.* 
                   FROM maestros_materias mm 
                   JOIN materias m ON mm.materia_id = m.id 
                   WHERE mm.maestro_id = :maestro_id");
        $db->bind(':maestro_id', $maestro_id);
        $materias_asignadas = $db->resultSet();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maestros - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar (repetir el mismo de dashboard.php) -->
        <?php include './partials/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Gestión de Maestros</h2>
            
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
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lista de maestros -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold mb-4">Lista de Maestros</h3>
                        
                        <div class="space-y-2">
                            <?php foreach($maestros as $maestro): ?>
                            <a href="maestros.php?ver=<?php echo $maestro->id; ?>" class="block px-4 py-2 rounded-lg hover:bg-blue-50 <?php echo isset($maestro_detalle) && $maestro_detalle->id == $maestro->id ? 'bg-blue-100' : ''; ?>">
                                <?php echo htmlspecialchars($maestro->nombre_completo); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Detalles del maestro -->
                <div class="lg:col-span-2">
                    <?php if($maestro_detalle): ?>
                        <div class="bg-white p-6 rounded-lg shadow mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($maestro_detalle->nombre_completo); ?></h3>
                                    <p class="text-gray-500">Usuario: <?php echo htmlspecialchars($maestro_detalle->username); ?></p>
                                </div>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Maestro</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-gray-500">Fecha de Nacimiento</p>
                                    <p><?php echo date('d/m/Y', strtotime($maestro_detalle->fecha_nacimiento)); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Fecha de Registro</p>
                                    <p><?php echo date('d/m/Y', strtotime($maestro_detalle->fecha_creacion)); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Materias asignadas -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-semibold">Materias Asignadas</h3>
                                
                                <!-- Botón para abrir modal de asignación -->
                                <button onclick="document.getElementById('modal-asignar').classList.remove('hidden')" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                    Asignar Materia
                                </button>
                            </div>
                            
                            <?php if(count($materias_asignadas) > 0): ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Materia</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach($materias_asignadas as $materia): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($materia->nombre); ?></td>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars($materia->descripcion); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <a href="maestros.php?eliminar_asignacion=<?php echo $materia->asignacion_id; ?>&ver=<?php echo $maestro_detalle->id; ?>" 
                                                       class="text-red-500 hover:text-red-700" 
                                                       onclick="return confirm('¿Está seguro de eliminar esta asignación?')">
                                                        Eliminar
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500">Este maestro no tiene materias asignadas.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white p-6 rounded-lg shadow text-center">
                            <p class="text-gray-500">Seleccione un maestro para ver sus detalles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para asignar materia -->
    <?php if($maestro_detalle): ?>
    <div id="modal-asignar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Asignar Materia</h3>
                    <button onclick="document.getElementById('modal-asignar').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                        &times;
                    </button>
                </div>
                
                <form action="maestros.php" method="POST">
                    <input type="hidden" name="maestro_id" value="<?php echo $maestro_detalle->id; ?>">
                    
                    <div class="mb-4">
                        <label for="materia_id" class="block text-gray-700 mb-2">Materia</label>
                        <select id="materia_id" name="materia_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Seleccionar Materia</option>
                            <?php foreach($materias as $materia): ?>
                                <option value="<?php echo $materia->id; ?>"><?php echo htmlspecialchars($materia->nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('modal-asignar').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit" name="asignar_materia" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>