<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin

// Procesar formulario de creación de materia
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']); // Nombre de la materia
    $descripcion = trim($_POST['descripcion']); // Descripción de la materia

    // Crear una instancia de la base de datos
    $db = new Database();

    // Preparar la consulta para insertar una nueva materia
    $db->query("INSERT INTO materias (nombre, descripcion) VALUES (:nombre, :descripcion)");
    $db->bind(':nombre', $nombre);
    $db->bind(':descripcion', $descripcion);

    // Ejecutar la consulta y verificar si fue exitosa
    if ($db->execute()) {
        $_SESSION['success'] = "Materia creada exitosamente";
        header('Location: materias.php');
        exit;
    } else {
        $error = "Error al crear la materia";
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_materia'])) {
    $materia_id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $activa = isset($_POST['activa']) ? 1 : 0;

    try {
        if (empty($nombre)) {
            throw new Exception("El nombre de la materia es obligatorio.");
        }

        $db->query("UPDATE materias SET 
                    nombre = :nombre, 
                    descripcion = :descripcion, 
                    activa = :activa 
                    WHERE id = :id");
        $db->bind(':nombre', $nombre);
        $db->bind(':descripcion', $descripcion);
        $db->bind(':activa', $activa);
        $db->bind(':id', $materia_id);
        $db->execute();

        $_SESSION['success'] = "Materia actualizada correctamente.";
        header("Location: materias.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al actualizar materia: " . $e->getMessage();
        header("Location: materias.php");
        exit;
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
        <!-- Sidebar (reutilizado de dashboard.php) -->
        <?php include './partials/sidebar.php'; ?>

        <!-- Contenido principal -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Gestión de Materias</h2>

            <!-- Mostrar mensajes de error o éxito -->
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

            <!-- Formulario de creación de materia -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h3 class="text-xl font-semibold mb-4">Agregar Nueva Materia</h3>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="nombre" class="block text-gray-700 mb-2">Nombre de la Materia</label>
                            <input type="text" id="nombre" name="nombre"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>

                        <div>
                            <label for="descripcion" class="block text-gray-700 mb-2">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                            class="bg-blue-500 text-white py-2 px-6 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
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
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($materias as $materia): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($materia->nombre); ?>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($materia->descripcion); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full <?php echo $materia->activa ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $materia->activa ? 'Activa' : 'Inactiva'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="javascript:void(0);" class="text-blue-500 hover:text-blue-700 mr-2"
                                            onclick="abrirModalMateria(<?= $materia->id ?>)">
                                            Editar
                                        </a>

                                        <a href="javascript:void(0);"
                                            onclick="abrirModalEliminar(<?= $materia->id ?>, '<?= htmlspecialchars($materia->nombre) ?>')"
                                            class="text-red-500 hover:text-red-700">Eliminar</a>

                                    </td>
                                </tr>
                            <?php endforeach; ?>


                            <!-- Modal para editar materia -->
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

                                            <div class="space-y-4">
                                                <div>
                                                    <label for="edit-materia-nombre"
                                                        class="block text-gray-700 mb-2">Nombre*</label>
                                                    <input type="text" id="edit-materia-nombre" name="nombre"
                                                        class="w-full p-2 border rounded" required>
                                                </div>

                                                <div>
                                                    <label for="edit-materia-descripcion"
                                                        class="block text-gray-700 mb-2">Descripción</label>
                                                    <textarea id="edit-materia-descripcion" name="descripcion" rows="3"
                                                        class="w-full p-2 border rounded"></textarea>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" id="edit-materia-activa" name="activa"
                                                        class="mr-2">
                                                    <label for="edit-materia-activa">Materia activa</label>
                                                </div>
                                            </div>

                                            <div class="mt-6 flex justify-end space-x-3">
                                                <button type="button" onclick="cerrarModal('modal-editar-materia')"
                                                    class="px-4 py-2 border rounded">Cancelar</button>
                                                <button type="submit"
                                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Guardar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal para eliminar materia -->
                            <div id="modal-eliminar-materia"
                                class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                                <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                                    <div class="p-6">
                                        <div class="flex justify-between items-center mb-4">
                                            <h3 class="text-xl font-semibold">Eliminar Materia</h3>
                                            <button onclick="cerrarModal('modal-eliminar-materia')"
                                                class="text-gray-500 hover:text-gray-700">&times;</button>
                                        </div>

                                        <p>¿Deseas eliminar la materia <strong id="nombre-materia-eliminar"></strong>?
                                        </p>

                                        <form id="form-eliminar-materia" class="mt-4">
                                            <input type="hidden" name="eliminar_materia">
                                            <input type="hidden" name="id" id="materia-id-eliminar">

                                            <div class="flex justify-end space-x-3">
                                                <button type="button" onclick="cerrarModal('modal-eliminar-materia')"
                                                    class="px-4 py-2 border rounded">Cancelar</button>
                                                <button type="submit"
                                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Eliminar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <script>
                                //EDITAR MATERIA
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
                                                document.getElementById('modal-editar-materia').classList.remove('hidden');
                                            } else {
                                                alert(data.message);
                                            }
                                        });
                                }

                                function cerrarModal(idModal) {
                                    document.getElementById(idModal).classList.add('hidden');
                                }
                                document.getElementById('form-editar-materia').addEventListener('submit', function (e) {
                                    e.preventDefault();
                                    const formData = new FormData(this);

                                    fetch('editar_materia.php', { method: 'POST', body: formData })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success) {
                                                alert('Materia actualizada correctamente');
                                                cerrarModal('modal-editar-materia');
                                                window.location.reload(); // O actualizar solo la fila
                                            } else {
                                                alert('Error: ' + data.message);
                                            }
                                        });
                                });
                                //ELIMINAR MATERIA
                                function abrirModalEliminar(id, nombre) {
                                    document.getElementById('materia-id-eliminar').value = id;
                                    document.getElementById('nombre-materia-eliminar').textContent = nombre;
                                    document.getElementById('modal-eliminar-materia').classList.remove('hidden');
                                }

                                function cerrarModal(idModal) {
                                    document.getElementById(idModal).classList.add('hidden');
                                }

                                document.getElementById('form-eliminar-materia').addEventListener('submit', function (e) {
                                    e.preventDefault();
                                    const formData = new FormData(this);

                                    fetch('eliminar_materia.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success) {
                                                cerrarModal('modal-eliminar-materia');
                                                window.location.reload();
                                            } else {
                                                alert('Error: ' + data.message);
                                            }
                                        });
                                });
                          </script>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>