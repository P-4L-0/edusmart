<?php
// Incluir el archivo de configuración y funciones para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
require_once BASE_PATH . 'functions.php';

if (!function_exists('generarUsername') || !function_exists('generarPassword')) {
    die("Error: Funciones esenciales no disponibles");
}
$adminID = $_SESSION['user_id'];
protegerPagina([1]); // Solo admin

// Crear una instancia de la base de datos
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

// Helper: validar que fecha_nacimiento >= 18 años
function validarMayorDeEdad($fecha_nacimiento_str) {
    if (!$fecha_nacimiento_str) return false;
    $fn = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento_str);
    if (!$fn) return false;
    $hoy = new DateTime('now', new DateTimeZone('America/El_Salvador'));
    $edad = $fn->diff($hoy)->y;
    return $edad >= 18;
}

// Operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_usuario'])) {
        // Crear un nuevo usuario
        $nombre_completo   = trim($_POST['nombre_completo']);
        $fecha_nacimiento  = trim($_POST['fecha_nacimiento']);
        $rol_id            = intval($_POST['rol_id']);
        $materias_id       = ($rol_id == 3 && isset($_POST['materias_id'])) ? $_POST['materias_id'] : [];

        // Validación backend: mayor de 18 años
        if (!validarMayorDeEdad($fecha_nacimiento)) {
            $_SESSION['error'] = "El usuario debe ser mayor de 18 años. Verifica la fecha de nacimiento.";
            header("Location: usuarios.php");
            exit;
        }

        // Generar username automático
        $username = generarUsername($nombre_completo, $db);

        // Generar password aleatorio seguro
        $password = generarPassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db->beginTransaction();

            // Insertar usuario en la base de datos
            $db->query("INSERT INTO usuarios (nombre_completo, fecha_nacimiento, username, password, rol_id, activo) 
                        VALUES (:nombre, :fecha, :username, :password, :rol_id, 1)");
            $db->bind(':nombre',   $nombre_completo);
            $db->bind(':fecha',    $fecha_nacimiento);
            $db->bind(':username', $username);
            $db->bind(':password', $hashed_password);
            $db->bind(':rol_id',   $rol_id);
            $db->execute();

            $usuario_id = $db->lastInsertId();

            // Si es maestro, asignar materias
            if ($rol_id == 3 && !empty($materias_id)) {
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

            // Guardar credenciales para mostrar
            $_SESSION['credenciales_temporales'] = [
                'username'  => $username,
                'password'  => $password,
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
    } elseif (isset($_POST['editar_usuario'])) {
        // Editar un usuario existente
        $usuario_id       = intval($_POST['id']);
        $nombre_completo  = trim($_POST['nombre_completo']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $rol_id           = intval($_POST['rol_id']);
        $activo           = isset($_POST['activo']) ? 1 : 0;
        $materias_id      = isset($_POST['materias_id']) ? $_POST['materias_id'] : [];

        // Validación backend: mayor de 18 años
        if (!validarMayorDeEdad($fecha_nacimiento)) {
            $_SESSION['error'] = "El usuario debe ser mayor de 18 años. Verifica la fecha de nacimiento.";
            header("Location: usuarios.php");
            exit;
        }

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
            $db->bind(':fecha',  $fecha_nacimiento);
            $db->bind(':rol_id', $rol_id);
            $db->bind(':activo', $activo);
            $db->bind(':id',     $usuario_id);
            $db->execute();

            // Si es maestro, actualizar materias
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
    } elseif (isset($_POST['eliminar_usuario'])) {
        $usuario_id = intval($_POST['eliminar_usuario']);

        try {
            $db->beginTransaction();

            // Obtener rol del usuario
            $db->query("SELECT rol_id FROM usuarios WHERE id = :id");
            $db->bind(':id', $usuario_id);
            $rol = $db->single(); // devuelve un objeto

            if ($rol) {
                $rol_id = $rol->rol_id;

                // Si es maestro (rol_id = 3)
                if ($rol_id == 3) {
                    // Eliminar asignaciones en materias
                    $db->query("DELETE FROM maestros_materias WHERE maestro_id = :maestro_id");
                    $db->bind(':maestro_id', $usuario_id);
                    $db->execute();

                    //  Desasignar de grupos donde era maestro
                    $db->query("UPDATE grupos SET maestro_id = NULL WHERE maestro_id = :maestro_id");
                    $db->bind(':maestro_id', $usuario_id);
                    $db->execute();
                }
            }
            $db->query("DELETE FROM usuarios WHERE id = :id");
            $db->bind(':id', $usuario_id);
            $db->execute();

            $db->commit();
            $_SESSION['success'] = "Usuario eliminado correctamente.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error al eliminar usuario: " . $e->getMessage();
        }

        if (isset($_SESSION['error'])) {
            echo $_SESSION['error'];
            exit;
        }

        header("Location: usuarios.php");
        exit;
    }
}

// Obtener lista de usuarios
$db->query("SELECT u.*, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.id != $adminID ORDER BY u.activo DESC, u.nombre_completo ");
$usuarios = $db->resultSet();
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
    <div id="credenciales-alert" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 shadow-lg rounded-lg p-4 mb-6 animate-fade-in" role="alert">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <div class="flex items-center mb-3">
                    <div class="bg-blue-100 p-2 rounded-full mr-3">
                        <i class="fas fa-key text-blue-600 text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Credenciales Generadas</h3>
                </div>
                
                <div class="bg-white rounded-lg p-4 mb-3 shadow-sm border border-blue-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Usuario:</p>
                            <div class="bg-gray-50 px-3 py-2 rounded border border-gray-200">
                                <code class="font-mono text-blue-700 font-bold text-lg"><?= htmlspecialchars($credencialesMostrar['username']) ?></code>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Contraseña:</p>
                            <div class="bg-gray-50 px-3 py-2 rounded border border-gray-200">
                                <code class="font-mono text-green-700 font-bold text-lg"><?= htmlspecialchars($credencialesMostrar['password']) ?></code>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center text-blue-600 text-sm">
                    <i class="fas fa-clock mr-2"></i>
                    <p>Estas credenciales se mostrarán por <span class="font-semibold">un minuto</span></p>
                </div>
            </div>
            
            <button onclick="document.getElementById('credenciales-alert').remove()" 
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition duration-200 ml-2">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Barra de progreso -->
        <div class="mt-3 bg-blue-200 rounded-full h-1.5">
            <div id="progress-bar" class="bg-blue-600 h-1.5 rounded-full transition-all duration-1000 ease-linear" style="width: 100%"></div>
        </div>
    </div>

    <script>
        // Ocultar después de 1 minuto
        setTimeout(() => {
            const el = document.getElementById('credenciales-alert');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-10px)';
                setTimeout(() => el.remove(), 300);
            }
        }, 60000);
        
        // Animación de la barra de progreso
        let progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            let width = 100;
            let interval = setInterval(() => {
                width -= (100 / 60); // Disminuir proporcionalmente en 60 segundos
                progressBar.style.width = width + '%';
                
                if (width <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        }
        
        // Agregar estilos de animación si no existen
        if (!document.querySelector('#alert-animations')) {
            let style = document.createElement('style');
            style.id = 'alert-animations';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .animate-fade-in {
                    animation: fadeIn 0.5s ease-out forwards;
                }
            `;
            document.head.appendChild(style);
        }
    </script>
<?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Gestión de Usuarios</h2>
            <button onclick="abrirModalCrear()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
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
                            <span class="px-2 py-1 text-xs rounded-full <?= $usuario->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $usuario->activo ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <button onclick="editarUsuario(<?= $usuario->id ?>)" class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                            <?php if ($usuario->activo): ?>
                             <!-- En la tabla de usuarios (reemplazar el form actual) -->
<button onclick="mostrarConfirmacionEliminar(<?= $usuario->id ?>, '<?= htmlspecialchars($usuario->nombre_completo) ?>')" 
        class="text-red-500 hover:text-red-700 transition duration-200">
    <i class="fas fa-times-circle mr-1"></i> Eliminar
</button>

<!-- Modal de confirmación de eliminación -->
<div id="modal-eliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
        <div class="p-6">
            <!-- Icono de advertencia -->
            <div class="flex justify-center mb-4">
                <div class="bg-red-100 p-4 rounded-full">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
            
            <!-- Contenido -->
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-2">¿Estás seguro de que deseas eliminar al usuario?</p>
                <p class="font-semibold text-gray-800" id="nombre-usuario-eliminar"></p>
                <p class="text-sm text-red-600 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Esta acción no se puede deshacer
                </p>
            </div>
            
            <!-- Botones -->
            <div class="flex justify-center space-x-4">
                <button type="button" onclick="cerrarModalEliminar()" 
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200 font-medium">
                    Cancelar
                </button>
                <form method="POST" action="usuarios.php" id="form-eliminar">
                    <input type="hidden" name="eliminar_usuario" id="usuario-id-eliminar">
                    <button type="submit" 
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 font-medium">
                        <i class="fas fa-trash mr-2"></i> Sí, Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Función para mostrar la confirmación
function mostrarConfirmacionEliminar(usuarioId, nombreUsuario) {
    const modal = document.getElementById('modal-eliminar');
    const nombreElement = document.getElementById('nombre-usuario-eliminar');
    const idElement = document.getElementById('usuario-id-eliminar');
    
    if (modal && nombreElement && idElement) {
        nombreElement.textContent = nombreUsuario;
        idElement.value = usuarioId;
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.transform').classList.remove('scale-95');
            modal.querySelector('.transform').classList.add('scale-100');
        }, 10);
        
        document.body.classList.add('overflow-hidden');
    }
}

// Función para cerrar el modal
function cerrarModalEliminar() {
    const modal = document.getElementById('modal-eliminar');
    if (modal) {
        modal.querySelector('.transform').classList.remove('scale-100');
        modal.querySelector('.transform').classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 200);
    }
}

// Cerrar modal al hacer clic fuera del contenido
document.getElementById('modal-eliminar')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalEliminar();
    }
});

// Cerrar con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalEliminar();
    }
});
</script>
                            <?php else: ?>
                                <a href="activar_usuario.php?id=<?= $usuario->id ?>" class="text-green-500 hover:text-green-700">
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
                <button onclick="cerrarModal('modal-crear')" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="crear_usuario">

                <div class="space-y-4">
                    <!-- Nombre Completo -->
                    <div>
                        <label for="nombre_completo" class="block text-gray-700 mb-2">Nombre Completo*</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 hover:border-blue-400" required>
                    </div>

                    <!-- Fecha de Nacimiento con ícono y validación -->
                    <div>
                        <label for="fecha_nacimiento" class="block text-gray-700 mb-2">Fecha de Nacimiento*</label>
                        <div class="relative">
                            <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 hover:border-blue-400 bg-white text-gray-800"
                                   required>
                        </div>
                        <p id="error-edad" class="text-red-600 text-sm mt-1 hidden">⚠️ Debes tener al menos 18 años para registrarte.</p>
                    </div>

                    <!-- Rol -->
                    <div>
                        <label for="rol_id" class="block text-gray-700 mb-2">Rol*</label>
                        <select id="rol_id" name="rol_id" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 hover:border-blue-400" required onchange="mostrarCampoMateria(this.value)">
                            <option value="">Seleccionar Rol</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol->id ?>"><?= htmlspecialchars($rol->nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Materias -->
                    <div id="materia-container" class="hidden">
                        <label class="block text-gray-700 mb-2">Materias asignadas*</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border rounded">
                            <?php foreach ($materias as $materia): ?>
                                <div class="flex items-center">
                                    <input type="checkbox" id="materia_<?= $materia->id ?>" name="materias_id[]" value="<?= $materia->id ?>" class="mr-2">
                                    <label for="materia_<?= $materia->id ?>"><?= htmlspecialchars($materia->nombre) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="cerrarModal('modal-crear')" class="px-4 py-2 border rounded">Cancelar</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div id="modal-editar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Editar Usuario</h3>
                <button onclick="cerrarModal('modal-editar')" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="editar_usuario">
                <input type="hidden" name="id" id="edit-id">

                <div class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label for="edit-nombre" class="block text-gray-700 mb-2">Nombre Completo*</label>
                        <input type="text" id="edit-nombre" name="nombre_completo" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 hover:border-blue-400" required>
                    </div>

                    <!-- Fecha de Nacimiento con ícono y validación -->
                    <div>
                        <label for="edit-fecha" class="block text-gray-700 mb-2">Fecha de Nacimiento*</label>
                        <div class="relative">
                            <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="date" id="edit-fecha" name="fecha_nacimiento"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 hover:border-blue-400 bg-white text-gray-800"
                                   required>
                        </div>
                        <p id="error-edad-edit" class="text-red-600 text-sm mt-1 hidden">⚠️ Debe ser mayor de 18 años.</p>
                    </div>

                    <!-- Rol -->
                    <div>
                        <label for="edit-rol" class="block text-gray-700 mb-2">Rol*</label>
                        <select id="edit-rol" name="rol_id" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 hover:border-blue-400" required>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol->id ?>"><?= htmlspecialchars($rol->nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Materias -->
                    <div id="edit-materia-container" class="hidden">
                        <label class="block text-gray-700 mb-2">Materias asignadas</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border rounded" id="edit-materias-list">
                            <!-- Se llena por JS -->
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="edit-activo" name="activo" class="mr-2">
                        <label for="edit-activo">Usuario activo</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="cerrarModal('modal-editar')" class="px-4 py-2 border rounded">Cancelar</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- Utils: Validación 18+ (aplica max date y verifica cambio) ---
    function prepararValidacionEdad(inputId, errorId) {
        const input = document.getElementById(inputId);
        const error = document.getElementById(errorId);
        if (!input) return;

        const hoy = new Date();
        const fechaMinima = new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate());
        const maxDate = fechaMinima.toISOString().split('T')[0];
        input.max = maxDate;

        input.addEventListener('change', function () {
            const seleccionada = new Date(this.value);
            if (this.value && seleccionada > fechaMinima) {
                error && error.classList.remove('hidden');
                this.classList.add('border-red-500', 'focus:ring-red-500');
                this.classList.remove('focus:ring-blue-500');
                this.value = '';
                this.focus();
            } else {
                error && error.classList.add('hidden');
                this.classList.remove('border-red-500', 'focus:ring-red-500');
                this.classList.add('focus:ring-blue-500');
            }
        });
    }

    prepararValidacionEdad('fecha_nacimiento', 'error-edad');
    prepararValidacionEdad('edit-fecha', 'error-edad-edit');

    // --- Mostrar/ocultar materias por rol ---
    function mostrarCampoMateria(rolId) {
        const materiaContainer = document.getElementById('materia-container');
        const editMateriaContainer = document.getElementById('edit-materia-container');

        if (parseInt(rolId, 10) === 3) { // Maestro
            if (materiaContainer) materiaContainer.classList.remove('hidden');
            if (editMateriaContainer) editMateriaContainer.classList.remove('hidden');
        } else {
            if (materiaContainer) materiaContainer.classList.add('hidden');
            if (editMateriaContainer) editMateriaContainer.classList.add('hidden');
        }
    }

    // --- Abrir / cerrar modales ---
    function abrirModalCrear() {
        const modalCrear = document.getElementById('modal-crear');
        if (modalCrear) {
            modalCrear.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            console.error('No se encontró el modal de creación.');
        }
    }
    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        } else {
            console.error(`No se encontró el modal con ID: ${id}`);
        }
    }

    // --- Cargar datos para editar ---
    function editarUsuario(id) {
        fetch(`obtener_usuario.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit-id').value = data.id;
                document.getElementById('edit-nombre').value = data.nombre_completo;
                document.getElementById('edit-fecha').value = data.fecha_nacimiento;
                document.getElementById('edit-rol').value = data.rol_id;
                document.getElementById('edit-activo').checked = parseInt(data.activo, 10) === 1;

                // Mostrar/ocultar materias según rol
                mostrarCampoMateria(data.rol_id);

                // Cargar materias asignadas si es maestro
                if (parseInt(data.rol_id, 10) === 3) {
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

    function cargarMateriasMaestro(maestroId) {
        fetch(`obtener_materias_maestro.php?id=${maestroId}`)
            .then(response => response.json())
            .then(materias => {
                const container = document.getElementById('edit-materias-list');
                container.innerHTML = '';

                if (!Array.isArray(materias) || materias.length === 0) {
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

                    if (materia.asignada === 1 || materia.asignada === "1" || materia.asignada === true) {
                        checkbox.checked = true;
                    }

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
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ maestro_id: maestroId, materia_id: materiaId, asignar: asignar }),
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
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
