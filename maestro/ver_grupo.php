<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];
$grupo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar que el grupo pertenece al maestro
$db->query("SELECT g.* FROM grupos g WHERE g.id = :grupo_id AND g.maestro_id = :maestro_id");
$db->bind(':grupo_id', $grupo_id);
$db->bind(':maestro_id', $maestro_id);
$grupo = $db->single();

if (!$grupo) {
    $_SESSION['error'] = "Grupo no encontrado o no tienes permisos";
    header("Location: grupos.php");
    exit;
}

// Obtener estudiantes del grupo
$db->query("SELECT * FROM estudiantes WHERE grupo_id = :grupo_id ORDER BY nombre_completo");
$db->bind(':grupo_id', $grupo_id);
$estudiantes = $db->resultSet();

// Obtener materias que imparte el maestro en este grupo
$db->query("SELECT m.id, m.nombre 
           FROM materias m
           JOIN maestros_materias mm ON mm.materia_id = m.id
           WHERE mm.maestro_id = :maestro_id
           ORDER BY m.nombre");
$db->bind(':maestro_id', $maestro_id);
$materias = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($grupo->nombre) ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-3xl font-bold"><?= htmlspecialchars($grupo->nombre) ?></h2>
                    <div class="flex items-center mt-2 text-gray-600">
                        <span class="mr-4"><i
                                class="fas fa-graduation-cap mr-2"></i><?= htmlspecialchars($grupo->grado) ?></span>
                        <span><i
                                class="fas fa-calendar-alt mr-2"></i><?= htmlspecialchars($grupo->ciclo_escolar) ?></span>
                    </div>
                </div>
                <a href="grupos.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i> Volver a grupos
                </a>
            </div>

            <!-- Pestañas -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px">
                    <li class="mr-2">
                        <a href="ver_grupo.php?id=<?= $grupo_id ?>"
                            class="inline-block p-4 border-b-2 border-blue-500 text-blue-600">
                            <i class="fas fa-users mr-2"></i> Estudiantes
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="calificaciones.php?id=<?= $grupo_id ?>"
                            class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-graduation-cap mr-2"></i> Calificaciones
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="actividades.php?id=<?= $grupo_id ?>"
                            class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-tasks mr-2"></i> Actividades
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contenido de la pestaña Estudiantes -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-semibold">Lista de Estudiantes</h3>
                    <span class="text-sm text-gray-500">
                        Total: <?= count($estudiantes) ?> estudiantes
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">No.</th>
                                <th class="px-6 py-3 text-left">NIE</th>
                                <th class="px-6 py-3 text-left">Nombre Completo</th>
                                <th class="px-6 py-3 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($estudiantes as $index => $estudiante): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= $index + 1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($estudiante->nie) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($estudiante->nombre_completo) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="javascript:void(0);" onclick="abrirModal('modalVer<?= $estudiante->id ?>')"
                                            class="text-blue-500 hover:text-blue-700 mr-3">
                                            <i class="fas fa-eye mr-1"></i> Ver
                                        </a>
                                        <a href="javascript:void(0);"
                                            onclick="abrirModal('modalCalif<?= $estudiante->id ?>')"
                                            class="text-green-500 hover:text-green-700">
                                            <i class="fas fa-graduation-cap mr-1"></i> Calificaciones
                                        </a>

                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen rápido por materia -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">Resumen por Materia</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                    <?php foreach ($materias as $materia): ?>
                        <div class="border rounded-lg p-4 hover:shadow-md">
                            <h4 class="font-medium text-lg mb-2"><?= htmlspecialchars($materia->nombre) ?></h4>
                            <a href="calificaciones.php?grupo_id=<?= $grupo_id ?>&materia_id=<?= $materia->id ?>"
                                class="text-blue-500 hover:text-blue-700 text-sm">
                                <i class="fas fa-chart-bar mr-1"></i> Ver progreso
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php foreach ($estudiantes as $estudiante): ?>
                <!-- Modal VER ESTUDIANTE -->
                <div id="modalVer<?= $estudiante->id ?>"
                    class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
                    <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-lg relative">
                        <h3 class="text-xl font-bold mb-4">Detalles del Estudiante</h3>
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($estudiante->nombre_completo) ?></p>
                        <p><strong>NIE:</strong> <?= htmlspecialchars($estudiante->nie) ?></p>
                        <p><strong>Fecha de nacimiento:</strong> <?= htmlspecialchars($estudiante->fecha_nacimiento) ?></p>
                        <div class="mt-4 text-right">
                            <button onclick="cerrarModal('modalVer<?= $estudiante->id ?>')"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal CALIFICACIONES -->
                <div id="modalCalif<?= $estudiante->id ?>"
                    class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
                    <div class="bg-white p-6 rounded-lg w-full max-w-2xl shadow-lg overflow-y-auto max-h-[90vh] relative">
                        <h3 class="text-xl font-bold mb-4">Calificaciones -
                            <?= htmlspecialchars($estudiante->nombre_completo) ?>
                        </h3>

                        <?php
                        // Obtener las calificaciones del estudiante
                        $db->query("SELECT a.nombre AS actividad, a.trimestre, n.calificacion, m.nombre AS materia
                        FROM notas n
                        JOIN actividades a ON a.id = n.actividad_id
                        JOIN materias m ON m.id = a.materia_id
                        WHERE n.estudiante_id = :est_id AND a.grupo_id = :grupo_id
                        ORDER BY a.trimestre, m.nombre, a.nombre");
                        $db->bind(':est_id', $estudiante->id);
                        $db->bind(':grupo_id', $grupo_id);
                        $calificaciones = $db->resultSet();
                        ?>

                        <?php if ($calificaciones): ?>
                            <table class="min-w-full divide-y divide-gray-200 mt-2">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Trimestre</th>
                                        <th class="px-4 py-2 text-left">Materia</th>
                                        <th class="px-4 py-2 text-left">Actividad</th>
                                        <th class="px-4 py-2 text-left">Calificación</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($calificaciones as $nota): ?>
                                        <tr>
                                            <td class="px-4 py-2"><?= htmlspecialchars($nota->trimestre) ?></td>
                                            <td class="px-4 py-2"><?= htmlspecialchars($nota->materia) ?></td>
                                            <td class="px-4 py-2"><?= htmlspecialchars($nota->actividad) ?></td>
                                            <td class="px-4 py-2"><?= htmlspecialchars($nota->calificacion) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-gray-500">Este estudiante aún no tiene calificaciones registradas.</p>
                        <?php endif; ?>

                        <div class="mt-4 text-right">
                            <button onclick="cerrarModal('modalCalif<?= $estudiante->id ?>')"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>


        </div>
    </div>
</body>
<script>
    function abrirModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }
</script>


</html>