<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([2]); // Solo rector

$db = new Database();

// Obtener lista de grupos
$db->query("SELECT g.*, u.nombre_completo as maestro FROM grupos g 
           LEFT JOIN usuarios u ON g.maestro_id = u.id 
           ORDER BY g.grado, g.nombre");
$grupos = $db->resultSet();

// Obtener lista de maestros para asignar
$db->query("SELECT * FROM usuarios WHERE rol_id = 3 ORDER BY nombre_completo");
$maestros = $db->resultSet();

// Procesar asignación de maestro a grupo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_maestro'])) {
    $grupo_id = intval($_POST['grupo_id']);
    $maestro_id = intval($_POST['maestro_id']);

    $db->query("UPDATE grupos SET maestro_id = :maestro_id WHERE id = :grupo_id");
    $db->bind(':maestro_id', $maestro_id);
    $db->bind(':grupo_id', $grupo_id);

    if ($db->execute()) {
        $_SESSION['success'] = "Maestro asignado correctamente al grupo";
        header('Location: grupos.php');
        exit;
    } else {
        $error = "Error al asignar el maestro";
    }
}

// Ver detalles de un grupo específico
$grupo_detalle = null;
$estudiantes = [];
if (isset($_GET['ver'])) {
    $grupo_id = intval($_GET['ver']);

    // Obtener información del grupo
    $db->query("SELECT g.*, u.nombre_completo as maestro FROM grupos g 
               LEFT JOIN usuarios u ON g.maestro_id = u.id 
               WHERE g.id = :grupo_id");
    $db->bind(':grupo_id', $grupo_id);
    $grupo_detalle = $db->single();

    if ($grupo_detalle) {
        // Obtener estudiantes del grupo
        $db->query("SELECT * FROM estudiantes WHERE grupo_id = :grupo_id AND activo = 1 ORDER BY nombre_completo");
        $db->bind(':grupo_id', $grupo_id);
        $estudiantes = $db->resultSet();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar (repetir el mismo de dashboard.php) -->
        <?php include './partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Gestión de Grupos</h2>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lista de grupos -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold mb-4">Lista de Grupos</h3>

                        <div class="space-y-2">
                            <?php foreach ($grupos as $grupo): ?>
                                <a href="grupos.php?ver=<?php echo $grupo->id; ?>"
                                    class="block px-4 py-2 rounded-lg hover:bg-blue-50 <?php echo isset($grupo_detalle) && $grupo_detalle->id == $grupo->id ? 'bg-blue-100' : ''; ?>">
                                    <p class="font-medium"><?php echo htmlspecialchars($grupo->nombre); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($grupo->grado); ?> -
                                        <?php echo htmlspecialchars($grupo->ciclo_escolar); ?>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Detalles del grupo -->
                <div class="lg:col-span-2">
                    <?php if ($grupo_detalle): ?>
                        <div class="bg-white p-6 rounded-lg shadow mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-semibold">
                                        <?php echo htmlspecialchars($grupo_detalle->nombre); ?>
                                    </h3>
                                    <p class="text-gray-500"><?php echo htmlspecialchars($grupo_detalle->grado); ?> -
                                        <?php echo htmlspecialchars($grupo_detalle->ciclo_escolar); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h4 class="font-medium mb-2">Maestro Asignado</h4>
                                <?php if ($grupo_detalle->maestro): ?>
                                    <p><?php echo htmlspecialchars($grupo_detalle->maestro); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-500">No hay maestro asignado</p>
                                <?php endif; ?>

                                <!-- Formulario para asignar maestro -->
                                <form action="grupos.php" method="POST" class="mt-4">
                                    <input type="hidden" name="grupo_id" value="<?php echo $grupo_detalle->id; ?>">

                                    <div class="flex items-end gap-4">
                                        <div class="flex-1">
                                            <label for="maestro_id" class="block text-gray-700 mb-2">Asignar Maestro</label>
                                            <select id="maestro_id" name="maestro_id" required
                                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Seleccionar Maestro</option>
                                                <?php foreach ($maestros as $maestro): ?>
                                                    <option value="<?php echo $maestro->id; ?>" <?php echo ($grupo_detalle->maestro_id == $maestro->id) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($maestro->nombre_completo); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="asignar_maestro"
                                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                            Asignar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Modal estudiante -->
                        <div id="modalEstudiante"
                            class="hidden fixed inset-0 bg-gray-700 bg-opacity-60 flex items-center justify-center p-4 z-50">
                            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-xl font-semibold text-gray-800" id="nombre">Nombre del estudiante
                                        </h3>
                                        <button id="closeModal"
                                            class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                                    </div>

                                    <div id="detalles" class="space-y-2 text-gray-700">
                                        <p><b>Grupo:</b> <span id="grupo"></span></p>
                                        <p><b>Grado:</b> <span id="grado"></span></p>
                                        <p><b>Ciclo escolar:</b> <span id="ciclo"></span></p>

                                        <hr class="my-3 border-gray-300">

                                        <h4 class="text-lg font-semibold text-gray-800">Actividades y Notas</h4>
                                        <div id="actividades"
                                            class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm max-h-60 overflow-y-auto">
                                        </div>
                                    </div>

                                    <div class="flex justify-end mt-4 space-x-3">
                                        <button id="cerrarBtn"
                                            class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal eliminar estudiante -->
                        <div id="modalEliminar"
                            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white rounded-xl shadow-xl w-11/12 max-w-md p-6">
                                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirmar eliminación</h3>
                                <p class="text-gray-600 mb-6">¿Seguro que deseas eliminar este estudiante y todas sus
                                    relaciones? Esta acción no se puede deshacer.</p>
                                <div class="flex justify-end space-x-3">
                                    <button id="cancelEliminar"
                                        class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                                    <button id="confirmEliminar"
                                        class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Eliminar</button>
                                </div>
                            </div>
                        </div>
                        <!-- Tabla de estudiantes -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-xl font-semibold mb-4">Estudiantes</h3>
                            <?php if (count($estudiantes) > 0): ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Nombre</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Fecha Nac.</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($estudiantes as $est): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php echo htmlspecialchars($est->nombre_completo); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php echo date('d/m/Y', strtotime($est->fecha_nacimiento)); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <a href="#" class="text-blue-500 hover:text-blue-700 ver-estudiante"
                                                            data-id="<?php echo $est->id; ?>">Ver</a>
                                                        <button class="btn-eliminar-estudiante text-red-500 hover:text-red-700"
                                                            data-id="<?php echo $est->id; ?>">Eliminar</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500">No hay estudiantes registrados.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white p-6 rounded-lg shadow text-center">
                            <p class="text-gray-500">Seleccione un grupo para ver sus detalles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        // modal con datos del estudiante
        document.querySelectorAll('.ver-estudiante').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id;

                fetch(`estudiantes.php?id=${id}&ajax=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) { alert(data.error); return; }
                        const est = data.estudiante;
                        const actividades = data.actividades;

                        document.getElementById('nombre').textContent = est.nombre_completo;
                        document.getElementById('grupo').textContent = est.grupo ?? '-';
                        document.getElementById('grado').textContent = est.grado ?? '-';
                        document.getElementById('ciclo').textContent = est.ciclo_escolar ?? '-';

                        const actividadesDiv = document.getElementById('actividades');
                        actividadesDiv.innerHTML = '';
                        if (actividades.length > 0) {
                            actividades.forEach(a => {
                                actividadesDiv.innerHTML += `
                    <div class="border-b py-2">
                        <p><b>${a.actividad}</b> (Trimestre ${a.trimestre})</p>
                        <p>Calificación: <span class="font-semibold text-blue-700">${a.calificacion ?? 'Sin calificación'}</span></p>
                    </div>`;
                            });
                        } else {
                            actividadesDiv.innerHTML = '<p class="text-gray-500 italic">No tiene actividades registradas.</p>';
                        }

                        document.getElementById('modalEstudiante').classList.remove('hidden');
                    })
                    .catch(err => {
                        alert('Error al obtener información del estudiante.');
                        console.error(err);
                    });
            });
        });

        // Cerrar modal
        function cerrarModal() {
            document.getElementById('modalEstudiante').classList.add('hidden');
        }
        document.getElementById('closeModal').onclick = cerrarModal;
        document.getElementById('cerrarBtn').onclick = cerrarModal;
        window.onclick = e => { if (e.target === document.getElementById('modalEstudiante')) cerrarModal(); };

        let estudianteAEliminar = null;

        document.querySelectorAll('.btn-eliminar-estudiante').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                estudianteAEliminar = this.dataset.id;
                document.getElementById('modalEliminar').classList.remove('hidden');
            });
        });

        // Cancelar
        document.getElementById('cancelEliminar').addEventListener('click', () => {
            estudianteAEliminar = null;
            document.getElementById('modalEliminar').classList.add('hidden');
        });

        // Confirmar eliminación
        document.getElementById('confirmEliminar').addEventListener('click', () => {
            if (!estudianteAEliminar) return;

            fetch('eliminar_estudiante.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `estudiante_id=${estudianteAEliminar}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        const fila = document.querySelector(`tr[data-id="${estudianteAEliminar}"]`);
                        if (fila) fila.remove();

                        alert(data.success);
                    }
                    estudianteAEliminar = null;
                    document.getElementById('modalEliminar').classList.add('hidden');
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al eliminar estudiante.');
                    estudianteAEliminar = null;
                    document.getElementById('modalEliminar').classList.add('hidden');
                });
        });
    </script>

</body>

</html>