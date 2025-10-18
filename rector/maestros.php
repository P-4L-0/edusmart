<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([2]); // Solo rector

$db = new Database();

// Lista de maestros
$db->query("SELECT * FROM usuarios WHERE rol_id = 3 ORDER BY nombre_completo");
$maestros = $db->resultSet();

// Niveles activos
$db->query("SELECT * FROM niveles ORDER BY nombre");
$niveles = $db->resultSet();

// Procesar asignación de materia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_materia'])) {
    $maestro_id = intval($_POST['maestro_id'] ?? 0);
    $materia_id = intval($_POST['materia_id'] ?? 0);

    if ($maestro_id <= 0 || $materia_id <= 0) {
        $error = "Faltan datos: maestro o materia no seleccionados.";
    } else {
        $db->query("SELECT id FROM maestros_materias WHERE maestro_id = :maestro_id AND materia_id = :materia_id");
        $db->bind(':maestro_id', $maestro_id);
        $db->bind(':materia_id', $materia_id);
        $existe = $db->single();

        if (!$existe) {
            $db->query("INSERT INTO maestros_materias (maestro_id, materia_id) VALUES (:maestro_id, :materia_id)");
            $db->bind(':maestro_id', $maestro_id);
            $db->bind(':materia_id', $materia_id);

            if ($db->execute()) {
                $_SESSION['success'] = "Materia asignada correctamente";
                header('Location: maestros.php?ver=' . $maestro_id);
                exit;
            } else {
                $error = "Error al guardar la asignación.";
            }
        } else {
            $error = "Esta asignación ya existe.";
        }
    }
}

// Eliminar asignación
if (isset($_GET['eliminar_asignacion'])) {
    $asignacion_id = intval($_GET['eliminar_asignacion']);
    $maestro_id = intval($_GET['ver'] ?? 0);

    $db->query("DELETE FROM maestros_materias WHERE id = :id");
    $db->bind(':id', $asignacion_id);

    if ($db->execute()) {
        $_SESSION['success'] = "Asignación eliminada correctamente";
        header('Location: maestros.php?ver=' . $maestro_id);
        exit;
    } else {
        $error = "Error al eliminar la asignación";
    }
}

// Ver detalles de maestro
$maestro_detalle = null;
$materias_asignadas = [];
if (isset($_GET['ver'])) {
    $maestro_id = intval($_GET['ver']);
    $db->query("SELECT * FROM usuarios WHERE id = :id AND rol_id = 3");
    $db->bind(':id', $maestro_id);
    $maestro_detalle = $db->single();

    if ($maestro_detalle) {
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
    <title>Gestión de Maestros - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <div class="flex min-h-screen">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6 text-gray-700">Gestión de Maestros</h2>

            <!-- Alertas nativas -->
            <?php if (isset($error)): ?>
                <script>alert('Error: <?php echo addslashes($error); ?>');</script>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <script>alert('<?php echo addslashes($_SESSION['success']); ?>');</script>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lista de maestros -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                        <h3 class="text-xl font-semibold mb-4 text-gray-800">Lista de Maestros</h3>
                        <div class="space-y-2">
                            <?php foreach ($maestros as $maestro): ?>
                                <a href="maestros.php?ver=<?php echo $maestro->id; ?>"
                                    class="block px-4 py-2 rounded-lg hover:bg-blue-50 transition <?php echo isset($maestro_detalle) && $maestro_detalle->id == $maestro->id ? 'bg-blue-100 font-semibold' : ''; ?>">
                                    <?php echo htmlspecialchars($maestro->nombre_completo); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Detalles del maestro -->
                <div class="lg:col-span-2">
                    <?php if ($maestro_detalle): ?>
                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-2xl font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($maestro_detalle->nombre_completo); ?>
                                    </h3>
                                    <p class="text-gray-500">Usuario:
                                        <?php echo htmlspecialchars($maestro_detalle->username); ?>
                                    </p>
                                    <p class="text-gray-500">Fecha de Nacimiento:
                                        <?php echo date('d/m/Y', strtotime($maestro_detalle->fecha_nacimiento)); ?>
                                    </p>
                                </div>
                                <span
                                    class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">Maestro</span>
                            </div>

                            <!-- Materias asignadas -->
                            <div class="mb-4">
                                <h4 class="text-lg font-semibold mb-2 text-gray-700">Materias Asignadas</h4>
                                <?php if (count($materias_asignadas) > 0): ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Materia</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Descripción</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($materias_asignadas as $materia): ?>
                                                    <tr>
                                                        <td class="px-6 py-4"><?php echo htmlspecialchars($materia->nombre); ?></td>
                                                        <td class="px-6 py-4"><?php echo htmlspecialchars($materia->descripcion); ?>
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            <button
                                                                onclick="confirmarEliminar(<?php echo $materia->asignacion_id; ?>, <?php echo $maestro_detalle->id; ?>)"
                                                                class="text-red-500 hover:text-red-700 font-medium">Eliminar</button>
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

                            <!-- Botón para asignar materia -->
                            <button onclick="abrirModalAsignar(<?php echo $maestro_detalle->id; ?>)"
                                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Asignar
                                Materia</button>
                        </div>
                    <?php else: ?>
                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 text-center">
                            <p class="text-gray-500">Seleccione un maestro para ver sus detalles.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal asignar materia -->
    <div id="modal-asignar"
        class="hidden fixed inset-0 bg-gray-700 bg-opacity-60 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Asignar Materia</h3>
                    <button onclick="document.getElementById('modal-asignar').classList.add('hidden')"
                        class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>

                <form action="maestros.php" method="POST" id="form-asignar">
                    <input type="hidden" name="maestro_id" id="maestro_id">

                    <div class="mb-4">
                        <label for="nivel_id" class="block text-gray-700 mb-2 font-medium">Nivel</label>
                        <select id="nivel_id"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Seleccionar Nivel</option>
                            <?php foreach ($niveles as $nivel): ?>
                                <option value="<?php echo $nivel->id; ?>"><?php echo htmlspecialchars($nivel->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="materia_id" class="block text-gray-700 mb-2 font-medium">Materia</label>
                        <select id="materia_id" name="materia_id"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Seleccionar Materia</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('modal-asignar').classList.add('hidden')"
                            class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                        <button type="submit" name="asignar_materia"
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Abrir modal
        function abrirModalAsignar(maestroId) {
            document.getElementById('maestro_id').value = maestroId;
            document.getElementById('modal-asignar').classList.remove('hidden');
            document.getElementById('nivel_id').value = '';
            document.getElementById('materia_id').innerHTML = '<option value="">Seleccionar Materia</option>';
        }

        // Filtrar materias por nivel
        document.getElementById('nivel_id').addEventListener('change', function () {
            const nivelId = this.value;
            const materiaSelect = document.getElementById('materia_id');
            materiaSelect.innerHTML = '<option value="">Cargando...</option>';

            if (nivelId) {
                fetch('obtener_materias.php?nivel_id=' + nivelId)
                    .then(res => res.json())
                    .then(data => {
                        materiaSelect.innerHTML = '<option value="">Seleccionar Materia</option>';
                        data.forEach(m => {
                            const option = document.createElement('option');
                            option.value = m.id;
                            option.textContent = m.nombre;
                            materiaSelect.appendChild(option);
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
                    });
            } else {
                materiaSelect.innerHTML = '<option value="">Seleccionar Materia</option>';
            }
        });

        // Confirmar eliminación con confirm()
        function confirmarEliminar(asignacionId, maestroId) {
            if (confirm('¿Eliminar asignación? Esta acción no se puede deshacer.')) {
                window.location.href = `maestros.php?eliminar_asignacion=${asignacionId}&ver=${maestroId}`;
            }
        }
    </script>

</body>

</html>