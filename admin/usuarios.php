<?php
require_once __DIR__ . '/../includes/config.php';
require_once BASE_PATH . 'functions.php';
if (!function_exists('generarUsername') || !function_exists('generarPassword')) {
    die("Error: Funciones esenciales no disponibles");
}
protegerPagina([1]); // Solo admin

$db = new Database();

// Obtener datos necesarios
$db->query("SELECT * FROM roles WHERE id != 1"); // Excluir admin
$roles = $db->resultSet();

$db->query("SELECT id, nombre FROM materias WHERE activa = 1 ORDER BY nombre");
$materias = $db->resultSet();

// Variables para mostrar credenciales
$credencialesMostrar = isset($_SESSION['credenciales_temporales']) ? $_SESSION['credenciales_temporales'] : null;
if ($credencialesMostrar) {
    unset($_SESSION['credenciales_temporales']); // Limpiar después de obtener
}

// Operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_usuario'])) {
        $nombre_completo = trim($_POST['nombre_completo']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $rol_id = intval($_POST['rol_id']);
        $materia_id = ($rol_id == 3 && isset($_POST['materia_id'])) ? intval($_POST['materia_id']) : null;

        // Generar username automático
        $username = generarUsername($nombre_completo, $db);

        // Generar password aleatorio seguro
        $password = generarPassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db->beginTransaction();

            // Crear usuario
            $db->query("INSERT INTO usuarios (nombre_completo, fecha_nacimiento, username, password, rol_id, activo) 
                       VALUES (:nombre, :fecha, :username, :password, :rol_id, 1)");
            $db->bind(':nombre', $nombre_completo);
            $db->bind(':fecha', $fecha_nacimiento);
            $db->bind(':username', $username);
            $db->bind(':password', $hashed_password);
            $db->bind(':rol_id', $rol_id);

            $db->execute();
            $usuario_id = $db->lastInsertId();

            // Si es maestro, asignar materia
// En la sección de creación de usuario, cambiar la asignación de materia por:
            if ($rol_id == 3 && !empty($_POST['materias_id'])) {
                foreach ($_POST['materias_id'] as $materia_id) {
                    $materia_id = intval($materia_id);
                    if ($materia_id > 0) {
                        $db->query("INSERT INTO maestros_materias (maestro_id, materia_id) 
                       VALUES (:maestro_id, :materia_id)");
                        $db->bind(':maestro_id', $usuario_id);
                        $db->bind(':materia_id', $materia_id);
                        $db->execute();
                    }
                }
            }

            $db->commit();

            // Guardar credenciales para mostrar
            $_SESSION['credenciales_temporales'] = [
                'username' => $username,
                'password' => $password,
                'timestamp' => time()
            ];

            $_SESSION['success'] = "Usuario creado correctamente";
            header("Location: usuarios.php");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error al crear usuario: " . $e->getMessage();
            header("Location: usuarios.php");
            exit;
        }
    }
} elseif (isset($_POST['editar_usuario'])) {
    $usuario_id = intval($_POST['id']);
    $nombre_completo = trim($_POST['nombre_completo']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $rol_id = intval($_POST['rol_id']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $materias_id = isset($_POST['materias_id']) ? $_POST['materias_id'] : [];

    try {
        $db->beginTransaction();

        // Actualizar datos básicos del usuario
        $db->query("UPDATE usuarios SET 
                   nombre_completo = :nombre, 
                   fecha_nacimiento = :fecha, 
                   rol_id = :rol_id, 
                   activo = :activo 
                   WHERE id = :id");
        $db->bind(':nombre', $nombre_completo);
        $db->bind(':fecha', $fecha_nacimiento);
        $db->bind(':rol_id', $rol_id);
        $db->bind(':activo', $activo);
        $db->bind(':id', $usuario_id);
        $db->execute();

        // Solo si es maestro, actualizar materias
        if ($rol_id == 3) {
            // Eliminar asignaciones actuales
            $db->query("DELETE FROM maestros_materias WHERE maestro_id = :maestro_id");
            $db->bind(':maestro_id', $usuario_id);
            $db->execute();

            // Agregar nuevas asignaciones
            foreach ($materias_id as $materia_id) {
                $materia_id = intval($materia_id);
                if ($materia_id > 0) {
                    $db->query("INSERT INTO maestros_materias (maestro_id, materia_id) 
                               VALUES (:maestro_id, :materia_id)");
                    $db->bind(':maestro_id', $usuario_id);
                    $db->bind(':materia_id', $materia_id);
                    $db->execute();
                }
            }
        }

        $db->commit();
        $_SESSION['success'] = "Usuario actualizado correctamente";
        header("Location: usuarios.php");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error al actualizar usuario: " . $e->getMessage();
        header("Location: usuarios.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $usuario_id = intval($_POST['eliminar_usuario']);
        echo "Intentando eliminar usuario con ID: $usuario_id<br>";

    try {
        $db->beginTransaction();

        // Eliminar asignaciones de materias si es maestro
        $db->query("DELETE FROM maestros_materias WHERE maestro_id = :maestro_id");
        $db->bind(':maestro_id', $usuario_id);
        $db->execute();
        echo "Asignaciones de materias eliminadas para el usuario con ID: $usuario_id<br>";

        // Eliminar el usuario
        $db->query("DELETE FROM usuarios WHERE id = :id");
        $db->bind(':id', $usuario_id);
        $db->execute();
        echo "Usuario eliminado con ID: $usuario_id<br>";

        $db->commit();
        $_SESSION['success'] = "Usuario eliminado correctamente";
    } catch (Exception $e) {
        $db->rollBack();
        echo "Error al eliminar usuario: " . $e->getMessage() . "<br>";
        $_SESSION['error'] = "Error al eliminar usuario: " . $e->getMessage();
    }

    exit;
}

// Obtener lista de usuarios
$db->query("SELECT u.*, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.activo DESC, u.nombre_completo");
$usuarios = $db->resultSet();

// Funciones auxiliares
function generarUsername($nombre_completo, $db)
{
    $iniciales = '';
    $partes_nombre = explode(' ', $nombre_completo);
    foreach ($partes_nombre as $parte) {
        if (!empty($parte)) {
            $iniciales .= strtolower(substr($parte, 0, 1));
        }
    }
    $base_username = $iniciales . date('y');
    $username = $base_username;
    $contador = 1;

    // Verificar si el username ya existe
    $db->query("SELECT id FROM usuarios WHERE username = :username");
    $db->bind(':username', $username);
    while ($db->single()) {
        $username = $base_username . $contador;
        $contador++;
        $db->bind(':username', $username);
    }

    return $username;
}

function generarPassword($longitud = 10)
{
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    $max = strlen($caracteres) - 1;
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[random_int(0, $max)];
    }
    return $password;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <!-- Notificación de credenciales -->
            <?php if ($credencialesMostrar): ?>
                <div id="credenciales-alert" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6"
                    role="alert">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-bold">Credenciales generadas</p>
                            <p>Usuario: <span
                                    class="font-mono"><?= htmlspecialchars($credencialesMostrar['username']) ?></span></p>
                            <p>Contraseña: <span
                                    class="font-mono"><?= htmlspecialchars($credencialesMostrar['password']) ?></span></p>
                            <p class="text-sm text-blue-600">Estas credenciales se mostrarán por 30 segundos</p>
                        </div>
                        <button onclick="document.getElementById('credenciales-alert').remove()"
                            class="text-blue-700 hover:text-blue-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <script>
                    // Ocultar después de 30 segundos
                    setTimeout(() => {
                        document.getElementById('credenciales-alert').remove();
                    }, 30000);
                </script>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold">Gestión de Usuarios</h2>
                <button onclick="abrirModalCrear()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plus mr-2"></i> Nuevo Usuario
                </button>
            </div>

            <!-- Tabla de usuarios -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">Nombre</th>
                            <th class="px-6 py-3 text-left">Usuario</th>
                            <th class="px-6 py-3 text-left">Rol</th>
                            <th class="px-6 py-3 text-left">Estado</th>
                            <th class="px-6 py-3 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="<?= $usuario->activo ? '' : 'bg-gray-100' ?>">
                                <td class="px-6 py-4"><?= htmlspecialchars($usuario->nombre_completo) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($usuario->username) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($usuario->rol) ?></td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full <?= $usuario->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $usuario->activo ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <button onclick="editarUsuario(<?= $usuario->id ?>)"
                                        class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                    <?php if ($usuario->activo): ?>
                                        <form method="POST" action="usuarios.php" class="inline-block"
                                            onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                            <input type="hidden" name="eliminar_usuario" value="<?= $usuario->id ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-times-circle mr-1"></i> Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="activar_usuario.php?id=<?= $usuario->id ?>"
                                            class="text-green-500 hover:text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i> Activar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para crear usuario -->
    <div id="modal-crear" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Nuevo Usuario</h3>
                    <button onclick="cerrarModal('modal-crear')"
                        class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="crear_usuario">

                    <div class="space-y-4">
                        <div>
                            <label for="nombre_completo" class="block text-gray-700 mb-2">Nombre Completo*</label>
                            <input type="text" id="nombre_completo" name="nombre_completo"
                                class="w-full p-2 border rounded" required>
                        </div>

                        <div>
                            <label for="fecha_nacimiento" class="block text-gray-700 mb-2">Fecha de Nacimiento*</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                class="w-full p-2 border rounded" required>
                        </div>

                        <div>
                            <label for="rol_id" class="block text-gray-700 mb-2">Rol*</label>
                            <select id="rol_id" name="rol_id" class="w-full p-2 border rounded" required
                                onchange="mostrarCampoMateria(this.value)">
                                <option value="">Seleccionar Rol</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol->id ?>"><?= htmlspecialchars($rol->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="materia-container" class="hidden">
                            <label class="block text-gray-700 mb-2">Materias asignadas*</label>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border rounded">
                                <?php foreach ($materias as $materia): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="materia_<?= $materia->id ?>" name="materias_id[]"
                                            value="<?= $materia->id ?>" class="mr-2">
                                        <label
                                            for="materia_<?= $materia->id ?>"><?= htmlspecialchars($materia->nombre) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModal('modal-crear')"
                            class="px-4 py-2 border rounded">Cancelar</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar usuario -->
    <div id="modal-editar"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Editar Usuario</h3>
                    <button onclick="cerrarModal('modal-editar')"
                        class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="editar_usuario">
                    <input type="hidden" name="id" id="edit-id">

                    <div class="space-y-4">
                        <div>
                            <label for="edit-nombre" class="block text-gray-700 mb-2">Nombre Completo*</label>
                            <input type="text" id="edit-nombre" name="nombre_completo" class="w-full p-2 border rounded"
                                required>
                        </div>

                        <div>
                            <label for="edit-fecha" class="block text-gray-700 mb-2">Fecha de Nacimiento*</label>
                            <input type="date" id="edit-fecha" name="fecha_nacimiento" class="w-full p-2 border rounded"
                                required>
                        </div>

                        <div>
                            <label for="edit-rol" class="block text-gray-700 mb-2">Rol*</label>
                            <select id="edit-rol" name="rol_id" class="w-full p-2 border rounded" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol->id ?>"><?= htmlspecialchars($rol->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="edit-materia-container" class="hidden">
                            <label class="block text-gray-700 mb-2">Materias asignadas</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border rounded"
                                id="edit-materias-list">
                                <!-- Las materias se cargarán dinámicamente con JavaScript -->
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="edit-activo" name="activo" class="mr-2">
                            <label for="edit-activo">Usuario activo</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModal('modal-editar')"
                            class="px-4 py-2 border rounded">Cancelar</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para abrir el modal de creación
        // Función para abrir el modal de creación
        function abrirModalCrear() {
            const modalCrear = document.getElementById('modal-crear');
            if (modalCrear) {
                modalCrear.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            } else {
                console.error('No se encontró el modal de creación.');
            }
        }

        // Función para cerrar cualquier modal
        function cerrarModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                console.error(`No se encontró el modal con ID: ${id}`);
            }
        }

        // Función para abrir el modal de edición
        function editarUsuario(id) {
            fetch(`obtener_usuario.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit-id').value = data.id;
                    document.getElementById('edit-nombre').value = data.nombre_completo;
                    document.getElementById('edit-fecha').value = data.fecha_nacimiento;
                    document.getElementById('edit-rol').value = data.rol_id;
                    document.getElementById('edit-activo').checked = data.activo == 1;

                    // Mostrar/ocultar materias según rol
                    mostrarCampoMateria(data.rol_id);

                    // Cargar materias asignadas si es maestro
                    if (data.rol_id == 3) {
                        cargarMateriasMaestro(id);
                    }

                    const modalEditar = document.getElementById('modal-editar');
                    if (modalEditar) {
                        modalEditar.classList.remove('hidden');
                        document.body.classList.add('overflow-hidden');
                    } else {
                        console.error('No se encontró el modal de edición.');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar datos del usuario:', error);
                    alert('Error al cargar datos del usuario');
                });
        }

        function mostrarCampoMateria(rolId) {
            const materiaContainer = document.getElementById('materia-container');
            const editMateriaContainer = document.getElementById('edit-materia-container');

            if (rolId == 3) { // Maestro
                if (materiaContainer) materiaContainer.classList.remove('hidden');
                if (editMateriaContainer) editMateriaContainer.classList.remove('hidden');
            } else {
                if (materiaContainer) materiaContainer.classList.add('hidden');
                if (editMateriaContainer) editMateriaContainer.classList.add('hidden');
            }
        }

        // Modificar la función editarUsuario para cargar materias asignadas
        function editarUsuario(id) {
            fetch(`obtener_usuario.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit-id').value = data.id;
                    document.getElementById('edit-nombre').value = data.nombre_completo;
                    document.getElementById('edit-fecha').value = data.fecha_nacimiento;
                    document.getElementById('edit-rol').value = data.rol_id;
                    document.getElementById('edit-activo').checked = data.activo == 1;

                    // Mostrar/ocultar materias según rol
                    mostrarCampoMateria(data.rol_id);

                    // Cargar materias asignadas si es maestro
                    if (data.rol_id == 3) {
                        cargarMateriasMaestro(id);
                    }

                    document.getElementById('modal-editar').classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar datos del usuario');
                });
        }

        function cargarMateriasMaestro(maestroId) {
            fetch(`obtener_materias_maestro.php?id=${maestroId}`)
                .then(response => response.json())
                .then(materias => {
                    console.log('Materias cargadas:', materias); // Depuración
                    const container = document.getElementById('edit-materias-list');
                    container.innerHTML = ''; // Limpiar el contenedor antes de agregar nuevas materias

                    if (materias.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">No hay materias disponibles.</p>';
                        return;
                    }

                    materias.forEach(materia => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `edit_materia_${materia.id}`;
                        checkbox.name = 'materias_id[]';
                        checkbox.value = materia.id;
                        checkbox.className = 'mr-2';

                        // Evaluar explícitamente si la materia está asignada
                        if (materia.asignada === 1 || materia.asignada === "1" || materia.asignada === true) {
                            checkbox.checked = true;
                        } else {
                            checkbox.checked = false;
                        }

                        // Agregar evento para manejar cambios
                        checkbox.addEventListener('change', () => {
                            actualizarAsignacionMateria(maestroId, materia.id, checkbox.checked);
                        });

                        const label = document.createElement('label');
                        label.htmlFor = `edit_materia_${materia.id}`;
                        label.textContent = materia.nombre;

                        div.appendChild(checkbox);
                        div.appendChild(label);
                        container.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar materias del maestro:', error);
                });
        }

        function actualizarAsignacionMateria(maestroId, materiaId, asignar) {
            fetch('actualizar_asignacion_materia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    maestro_id: maestroId,
                    materia_id: materiaId,
                    asignar: asignar,
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(`Materia ${materiaId} ${asignar ? 'asignada' : 'eliminada'} correctamente.`);
                    } else {
                        console.error('Error al actualizar la asignación:', data.message);
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar la asignación:', error);
                    alert('Ocurrió un error al intentar actualizar la asignación.');
                });
        }
    </script>
</body>

</html>