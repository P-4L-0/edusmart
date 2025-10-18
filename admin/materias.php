<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

$db = new Database();
$error = '';

// nueva materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar_materia']) && !isset($_POST['eliminar_materia'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $niveles = $_POST['niveles'] ?? [];

    try {
        if (empty($nombre))
            throw new Exception("El nombre de la materia es obligatorio.");
        if (empty($niveles))
            throw new Exception("Debes seleccionar al menos un nivel.");

        $db->query("INSERT INTO materias (nombre, descripcion, activa) VALUES (:nombre, :descripcion, 1)");
        $db->bind(':nombre', $nombre);
        $db->bind(':descripcion', $descripcion);
        $db->execute();
        $materia_id = $db->lastInsertId();

        foreach ($niveles as $nivel_id) {
            $db->query("INSERT INTO materias_niveles (materia_id, nivel_id) VALUES (:materia_id, :nivel_id)");
            $db->bind(':materia_id', $materia_id);
            $db->bind(':nivel_id', $nivel_id);
            $db->execute();
        }

        $_SESSION['success'] = "Materia creada exitosamente";
        header('Location: materias.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

//materias con niveles
$db->query("
    SELECT m.*, GROUP_CONCAT(n.nombre SEPARATOR ', ') AS niveles
    FROM materias m
    LEFT JOIN materias_niveles mn ON m.id = mn.materia_id
    LEFT JOIN niveles n ON mn.nivel_id = n.id
    GROUP BY m.id
    ORDER BY m.nombre
");
$materias = $db->resultSet();

//niveles
$db->query("SELECT * FROM niveles ORDER BY nombre");
$niveles = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Gestión de Materias</h2>

            <!-- Mensajes -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="flex flex-col gap-6  max-w-90 mx-auto">

                <div class="bg-white p-8 rounded-2xl shadow-lg max-w-8xl mx-auto">
                    <h3 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Agregar Nueva Materia</h3>
                    <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <div class="flex flex-col">
                            <label class="block text-gray-700 mb-2 font-medium">Nombre*</label>
                            <input type="text" name="nombre" placeholder="Nombre de la materia" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>


                        <div class="flex flex-col">
                            <label class="block text-gray-700 mb-2 font-medium">Descripción</label>
                            <textarea name="descripcion" placeholder="Descripción opcional"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-32 resize-none transition duration-200">
                          </textarea>
                        </div>

                        <!-- Niveles -->
                        <div class="flex flex-col">
                            <label class="block text-gray-700 mb-2 font-medium">Niveles*</label>
                            <div
                                class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-lg max-h-36 overflow-y-auto bg-gray-50">
                                <?php foreach ($niveles as $nivel): ?>
                                    <label
                                        class="flex items-center gap-2 cursor-pointer px-2 py-1 rounded hover:bg-blue-50 transition duration-150">
                                        <input type="checkbox" name="niveles[]" value="<?= $nivel->id ?>"
                                            class="form-checkbox h-4 w-4 text-blue-500">
                                        <span class="text-gray-700 text-sm"><?= htmlspecialchars($nivel->nombre) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                       
                        <div class="md:col-span-3 flex justify-end mt-4">
                            <button type="submit"
                                class="bg-blue-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-blue-600 shadow-md hover:shadow-lg transition duration-200">
                                Guardar Materia
                            </button>
                        </div>
                    </form>
                </div>


                <!-- filtro -->
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl max-w-6xl mx-auto w-full overflow-x-auto">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                        <h3 class="text-2xl font-semibold text-gray-800 border-b pb-2 flex-1">Lista de Materias</h3>

                        <!-- No funciona -->
                        <div class="flex items-center gap-3">
                            <label for="filtro-nivel" class="text-gray-700 font-medium">Filtrar por nivel:</label>
                            <select id="filtro-nivel" class="p-2 border border-gray-300 rounded">
                                <option value="">Todos</option>
                                <?php foreach ($niveles as $nivel): ?>
                                    <option value="<?= $nivel->id ?>"><?= htmlspecialchars($nivel->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Niveles</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($materias as $materia): ?>
                                <tr data-niveles="<?= $materia->niveles_ids ?? '' ?>"
                                    class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-2"><?= htmlspecialchars($materia->nombre) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($materia->descripcion) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($materia->niveles ?: '-') ?></td>
                                    <td class="px-4 py-2">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full <?= $materia->activa ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $materia->activa ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 flex gap-2">
                                        <button onclick="abrirModalMateria(<?= $materia->id ?>)"
                                            class="text-blue-500 hover:text-blue-700">Editar</button>
                                        <button
                                            onclick="abrirModalEliminar(<?= $materia->id ?>,'<?= htmlspecialchars($materia->nombre) ?>')"
                                            class="text-red-500 hover:text-red-700">Eliminar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>




            <!-- MODAL EDITAR -->
            <div id="modal-editar-materia"
                class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Editar Materia</h3>
                            <button onclick="cerrarModal('modal-editar-materia')"
                                class="text-gray-500 hover:text-gray-700">&times;</button>
                        </div>

                        <form id="form-editar-materia">
                            <input type="hidden" name="editar_materia" value="1">
                            <input type="hidden" name="id" id="edit-materia-id">

                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Nombre*</label>
                                <input type="text" name="nombre" id="edit-materia-nombre"
                                    class="w-full p-2 border rounded" required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Descripción</label>
                                <textarea name="descripcion" id="edit-materia-descripcion" rows="3"
                                    class="w-full p-2 border rounded"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Niveles</label>
                                <div id="niveles-checks" class="flex flex-wrap gap-4">
                                    <?php foreach ($niveles as $nivel): ?>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="niveles[]" value="<?= $nivel->id ?>"
                                                class="form-checkbox h-4 w-4 nivel-checkbox">
                                            <?= htmlspecialchars($nivel->nombre) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="activa" id="edit-materia-activa"
                                        class="form-checkbox h-4 w-4">
                                    Materia activa
                                </label>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" onclick="cerrarModal('modal-editar-materia')"
                                    class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="submit"
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL ELIMINAR -->
            <div id="modal-eliminar-materia"
                class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Eliminar Materia</h3>
                            <button onclick="cerrarModal('modal-eliminar-materia')"
                                class="text-gray-500 hover:text-gray-700">&times;</button>
                        </div>
                        <p>¿Deseas eliminar la materia <strong id="nombre-materia-eliminar"></strong>?</p>
                        <form id="form-eliminar-materia" class="mt-4">
                            <input type="hidden" name="eliminar_materia">
                            <input type="hidden" name="id" id="materia-id-eliminar">

                            <div class="flex justify-end gap-3">
                                <button type="button" onclick="cerrarModal('modal-eliminar-materia')"
                                    class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="submit"
                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Eliminar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function abrirModalMateria(id) {
            fetch('editar_materia.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const m = data.materia;
                        document.getElementById('edit-materia-id').value = m.id;
                        document.getElementById('edit-materia-nombre').value = m.nombre;
                        document.getElementById('edit-materia-descripcion').value = m.descripcion;
                        document.getElementById('edit-materia-activa').checked = m.activa == 1;

                        // Desmarcar todos los checkboxes
                        document.querySelectorAll('#niveles-checks input.nivel-checkbox').forEach(cb => cb.checked = false);
                        // Marcar los niveles asociados
                        if (m.niveles_ids) {
                            m.niveles_ids.forEach(nid => {
                                const cb = document.querySelector('#niveles-checks input.nivel-checkbox[value="' + nid + '"]');
                                if (cb) cb.checked = true;
                            });
                        }
                        document.getElementById('modal-editar-materia').classList.remove('hidden');
                    } else alert(data.message);
                });
        }

        function abrirModalEliminar(id, nombre) {
            document.getElementById('materia-id-eliminar').value = id;
            document.getElementById('nombre-materia-eliminar').textContent = nombre;
            document.getElementById('modal-eliminar-materia').classList.remove('hidden');
        }

        function cerrarModal(idModal) {
            document.getElementById(idModal).classList.add('hidden');
        }

        // EDITAR AJAX
        document.getElementById('form-editar-materia').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('editar_materia.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { alert('Materia actualizada correctamente'); window.location.reload(); }
                    else alert('Error: ' + data.message);
                });
        });

        // ELIMINAR AJAX
        document.getElementById('form-eliminar-materia').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('eliminar_materia.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { cerrarModal('modal-eliminar-materia'); window.location.reload(); }
                    else alert('Error: ' + data.message);
                });
        });


        // FILTRO SENCILLO POR NIVELES
        const filtroNivel = document.getElementById('filtro-nivel');
        filtroNivel.addEventListener('change', () => {
            const nivelId = filtroNivel.value;
            document.querySelectorAll('tbody tr').forEach(row => {
                const niveles = row.getAttribute('data-niveles').split(',');
                if (!nivelId || niveles.includes(nivelId)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

    </script>

</body>

</html>