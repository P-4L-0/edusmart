<?php
require_once __DIR__ . '/../../includes/config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];

$grupo_id = $_GET['grupo_id'] ?? null;
$materia_id = $_GET['materia_id'] ?? null;

// Validar que el maestro tenga acceso al grupo/materia
if ($grupo_id) {
    $db->query("SELECT id FROM grupos WHERE id = :grupo_id AND maestro_id = :maestro_id");
    $db->bind(':grupo_id', $grupo_id);
    $db->bind(':maestro_id', $maestro_id);
    $grupo = $db->single();
    
    if (!$grupo) {
        header('Location: /maestro/dashboard.php');
        exit;
    }
    
    // Obtener estudiantes del grupo
    $db->query("SELECT id, nombre_completo FROM estudiantes WHERE grupo_id = :grupo_id ORDER BY nombre_completo");
    $db->bind(':grupo_id', $grupo_id);
    $estudiantes = $db->resultSet();
    
    // Obtener materias del maestro para este grupo
    $db->query("SELECT m.id, m.nombre 
                FROM materias m
                JOIN maestros_materias mm ON m.id = mm.materia_id
                WHERE mm.maestro_id = :maestro_id
                ORDER BY m.nombre");
    $db->bind(':maestro_id', $maestro_id);
    $materias = $db->resultSet();
    
    $titulo = "Calificaciones del Grupo";
} elseif ($materia_id) {
    $db->query("SELECT id FROM maestros_materias WHERE materia_id = :materia_id AND maestro_id = :maestro_id");
    $db->bind(':materia_id', $materia_id);
    $db->bind(':maestro_id', $maestro_id);
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
    
    $titulo = "Calificaciones de la Materia";
} else {
    header('Location: /maestro/dashboard.php');
    exit;
}

// Obtener actividades si hay grupo y materia seleccionados
if ($grupo_id && $materia_id) {
    $db->query("SELECT a.id, a.nombre, a.porcentaje, a.trimestre 
                FROM actividades a
                WHERE a.grupo_id = :grupo_id AND a.materia_id = :materia_id
                ORDER BY a.trimestre, a.fecha_creacion");
    $db->bind(':grupo_id', $grupo_id);
    $db->bind(':materia_id', $materia_id);
    $actividades = $db->resultSet();
    
    // Obtener calificaciones
    $calificaciones = [];
    if (!empty($estudiantes) && !empty($actividades)) {
        foreach ($estudiantes as $estudiante) {
            foreach ($actividades as $actividad) {
                $db->query("SELECT calificacion FROM notas 
                            WHERE estudiante_id = :estudiante_id AND actividad_id = :actividad_id");
                $db->bind(':estudiante_id', $estudiante->id);
                $db->bind(':actividad_id', $actividad->id);
                $nota = $db->single();
                $calificaciones[$estudiante->id][$actividad->id] = $nota ? $nota->calificacion : null;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - <?= APP_NAME ?></title>
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
                <h2 class="text-3xl font-bold"><?= $titulo ?></h2>
                <a href="../dashboard.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
            
            <!-- Filtros -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php if ($grupo_id): ?>
                        <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Materia</label>
                            <select name="materia_id" class="w-full p-2 border rounded" onchange="this.form.submit()">
                                <option value="">-- Seleccione una materia --</option>
                                <?php foreach ($materias as $materia): ?>
                                    <option value="<?= $materia->id ?>" <?= $materia_id == $materia->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($materia->nombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif ($materia_id): ?>
                        <input type="hidden" name="materia_id" value="<?= $materia_id ?>">
                        <div>
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
                    <?php endif; ?>
                    
                    <?php if ($grupo_id && $materia_id): ?>
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
            
            <?php if ($grupo_id && $materia_id && !empty($actividades) && !empty($estudiantes)): ?>
                <!-- Tabla de calificaciones -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Estudiante</th>
                                    <?php foreach ($actividades as $actividad): ?>
                                        <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">
                                            <?= htmlspecialchars($actividad->nombre) ?><br>
                                            <span class="text-xs"><?= $actividad->porcentaje ?>%</span>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Promedio</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($estudiantes as $estudiante): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= htmlspecialchars($estudiante->nombre_completo) ?>
                                        </td>
                                        <?php 
                                        $total_puntos = 0;
                                        $total_porcentaje = 0;
                                        
                                        foreach ($actividades as $actividad): 
                                            $calificacion = $calificaciones[$estudiante->id][$actividad->id] ?? null;
                                            
                                            if (is_numeric($calificacion)) {
                                                $total_puntos += $calificacion * ($actividad->porcentaje / 100);
                                                $total_porcentaje += $actividad->porcentaje;
                                            }
                                        ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="number" step="0.01" min="0" max="10" 
                                                       class="w-20 p-1 border rounded calificacion-input"
                                                       value="<?= $calificacion ?>"
                                                       data-estudiante="<?= $estudiante->id ?>"
                                                       data-actividad="<?= $actividad->id ?>"
                                                       onchange="guardarCalificacion(this)">
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-center font-semibold">
                                            <?php 
                                            if ($total_porcentaje > 0) {
                                                echo round(($total_puntos / ($total_porcentaje / 100)), 2);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Botón para nueva actividad -->
                <div class="text-right mb-4">
                    <button onclick="mostrarFormularioActividad()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-plus mr-1"></i> Nueva Actividad
                    </button>
                </div>
                
                <!-- Formulario para nueva actividad (oculto inicialmente) -->
                <div id="formulario-actividad" class="bg-white p-6 rounded-lg shadow mb-8 hidden">
                    <h3 class="text-xl font-semibold mb-4">Crear Nueva Actividad</h3>
                    <form id="form-actividad" class="space-y-4">
                        <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
                        <input type="hidden" name="materia_id" value="<?= $materia_id ?>">
                        <input type="hidden" name="trimestre" value="<?= $_GET['trimestre'] ?? 1 ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input type="text" name="nombre" class="w-full p-2 border rounded" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Porcentaje</label>
                                <input type="number" name="porcentaje" step="0.1" min="0" max="100" class="w-full p-2 border rounded" required>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea name="descripcion" class="w-full p-2 border rounded"></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="ocultarFormularioActividad()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                Cancelar
                            </button>
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                Guardar Actividad
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Scripts para manejar calificaciones y actividades -->
                <script>
                function mostrarFormularioActividad() {
                    document.getElementById('formulario-actividad').classList.remove('hidden');
                }
                
                function ocultarFormularioActividad() {
                    document.getElementById('formulario-actividad').classList.add('hidden');
                    document.getElementById('form-actividad').reset();
                }
                
                function guardarCalificacion(input) {
                    const estudianteId = input.dataset.estudiante;
                    const actividadId = input.dataset.actividad;
                    const calificacion = input.value;
                    
                    fetch('guardar_calificacion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            estudiante_id: estudianteId,
                            actividad_id: actividadId,
                            calificacion: calificacion
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert('Error al guardar calificación: ' + data.message);
                            // Recargar para mantener consistencia
                            window.location.reload();
                        }
                    });
                }
                
                // Manejar el formulario de actividad
                document.getElementById('form-actividad').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());
                    
                    fetch('guardar_actividad.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error al guardar actividad: ' + data.message);
                        }
                    });
                });
                </script>
            <?php elseif ($grupo_id || $materia_id): ?>
                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <p class="text-gray-500">Seleccione <?= $grupo_id ? 'una materia' : 'un grupo' ?> para ver las calificaciones.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>