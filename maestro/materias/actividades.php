<?php
require_once __DIR__ . '/../../includes/config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];
$materia_id = $_GET['materia_id'] ?? null;

// Validar que el maestro tenga esta materia asignada
$db->query("SELECT m.id, m.nombre 
            FROM materias m
            JOIN maestros_materias mm ON m.id = mm.materia_id
            WHERE mm.maestro_id = :maestro_id AND m.id = :materia_id");
$db->bind(':maestro_id', $maestro_id);
$db->bind(':materia_id', $materia_id);
$materia = $db->single();

if (!$materia) {
    header('Location: /maestro/dashboard.php');
    exit;
}

// Obtener grupos donde el maestro imparte esta materia
$db->query("SELECT g.id, g.nombre, g.grado 
            FROM grupos g
            JOIN actividades a ON g.id = a.grupo_id
            WHERE a.materia_id = :materia_id AND g.maestro_id = :maestro_id
            GROUP BY g.id
            ORDER BY g.nombre");
$db->bind(':materia_id', $materia_id);
$db->bind(':maestro_id', $maestro_id);
$grupos = $db->resultSet();

// Obtener actividades si hay un grupo seleccionado
$grupo_id = $_GET['grupo_id'] ?? null;
if ($grupo_id) {
    // Verificar que el grupo pertenezca al maestro
    $db->query("SELECT id FROM grupos WHERE id = :grupo_id AND maestro_id = :maestro_id");
    $db->bind(':grupo_id', $grupo_id);
    $db->bind(':maestro_id', $maestro_id);
    $grupo = $db->single();
    
    if (!$grupo) {
        header('Location: /maestro/dashboard.php');
        exit;
    }
    
    // Obtener actividades
    $db->query("SELECT a.id, a.nombre, a.descripcion, a.porcentaje, a.trimestre, a.fecha_creacion, g.nombre as grupo_nombre
                FROM actividades a
                JOIN grupos g ON a.grupo_id = g.id
                WHERE a.materia_id = :materia_id AND a.grupo_id = :grupo_id
                ORDER BY a.trimestre, a.fecha_creacion DESC");
    $db->bind(':materia_id', $materia_id);
    $db->bind(':grupo_id', $grupo_id);
    $actividades = $db->resultSet();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades - <?= $materia->nombre ?> | <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../partials/sidebar.php';?>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold">Actividades de <?= htmlspecialchars($materia->nombre) ?></h2>
                <a href="   ../dashboard.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
            
            <!-- Filtro de grupo -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <form method="get" class="flex items-end space-x-4">
                    <input type="hidden" name="materia_id" value="<?= $materia_id ?>">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                        <select name="grupo_id" class="w-full p-2 border rounded" onchange="this.form.submit()">
                            <option value="">-- Seleccione un grupo --</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?= $grupo->id ?>" <?= $grupo_id == $grupo->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($grupo->nombre) ?> - <?= htmlspecialchars($grupo->grado) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($grupo_id): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
                            <select name="trimestre" class="w-full p-2 border rounded" onchange="this.form.submit()">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_GET['trimestre'] ?? 1) == $i ? 'selected' : '' ?>>
                                        Trimestre <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($grupo_id): ?>
                <!-- Lista de actividades -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actividad</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Porcentaje</th>
                                    <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Trimestre</th>
                                    <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($actividades)): ?>
                                    <?php foreach ($actividades as $actividad): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?= htmlspecialchars($actividad->nombre) ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?= htmlspecialchars($actividad->descripcion) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?= $actividad->porcentaje ?>%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?= $actividad->trimestre ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?= date('d/m/Y', strtotime($actividad->fecha_creacion)) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <a href="../calificaciones/listar.php?grupo_id=<?= $grupo_id ?>&materia_id=<?= $materia_id ?>&trimestre=<?= $actividad->trimestre ?>" 
                                                   class="text-blue-500 hover:text-blue-700 mr-2">
                                                    <i class="fas fa-graduation-cap mr-1"></i> Calificaciones
                                                </a>
                                                <a href="#" onclick="editarActividad(<?= $actividad->id ?>)" class="text-green-500 hover:text-green-700 mr-2">
                                                    <i class="fas fa-edit mr-1"></i> Editar
                                                </a>
                                                <a href="#" onclick="eliminarActividad(<?= $actividad->id ?>)" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash mr-1"></i> Eliminar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No hay actividades registradas para este grupo y trimestre.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Botón para nueva actividad -->
                <div class="text-right">
                    <a href="../calificaciones/listar.php?grupo_id=<?= $grupo_id ?>&materia_id=<?= $materia_id ?>" 
                       class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 inline-block">
                        <i class="fas fa-plus mr-1"></i> Nueva Actividad
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <p class="text-gray-500">Seleccione un grupo para ver las actividades.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function editarActividad(id) {
        // Implementar lógica para editar actividad
        alert('Editar actividad ' + id);
    }
    
    function eliminarActividad(id) {
        if (confirm('¿Está seguro de eliminar esta actividad? Esto también eliminará todas las calificaciones asociadas.')) {
            fetch('eliminar_actividad.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    actividad_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al eliminar actividad: ' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>