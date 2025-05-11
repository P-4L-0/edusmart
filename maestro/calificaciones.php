<?php
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];
$grupo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$materia_id = isset($_GET['materia_id']) ? intval($_GET['materia_id']) : 0;
$trimestre = isset($_GET['trimestre']) ? intval($_GET['trimestre']) : 1;

// Verificar permisos del maestro sobre el grupo
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

// Obtener materias del maestro
$db->query("SELECT m.id, m.nombre 
           FROM materias m
           JOIN maestros_materias mm ON mm.materia_id = m.id
           WHERE mm.maestro_id = :maestro_id
           ORDER BY m.nombre");
$db->bind(':maestro_id', $maestro_id);
$materias = $db->resultSet();

// Si hay materia seleccionada, obtener actividades y calificaciones
if ($materia_id) {
    // Verificar que el maestro imparte esta materia
    $db->query("SELECT id FROM maestros_materias WHERE materia_id = :materia_id AND maestro_id = :maestro_id");
    $db->bind(':materia_id', $materia_id);
    $db->bind(':maestro_id', $maestro_id);
    $materia_valida = $db->single();

    if (!$materia_valida) {
        $_SESSION['error'] = "No tienes permiso para acceder a esta materia";
        header("Location: calificaciones.php?id=$grupo_id");
        exit;
    }

    // Obtener actividades del trimestre con fecha de entrega
    $db->query("SELECT a.id, a.nombre, a.porcentaje, a.fecha_entrega 
               FROM actividades a
               WHERE a.grupo_id = :grupo_id AND a.materia_id = :materia_id AND a.trimestre = :trimestre
               ORDER BY a.fecha_entrega");
    $db->bind(':grupo_id', $grupo_id);
    $db->bind(':materia_id', $materia_id);
    $db->bind(':trimestre', $trimestre);
    $actividades = $db->resultSet();

    // Obtener calificaciones
    $calificaciones = [];
    $promedios = [];

    if (!empty($estudiantes) && !empty($actividades)) {
        foreach ($estudiantes as $estudiante) {
            $total_puntos = 0;
            $total_porcentaje = 0;

            foreach ($actividades as $actividad) {
                $db->query("SELECT id, calificacion FROM notas 
                           WHERE estudiante_id = :estudiante_id AND actividad_id = :actividad_id");
                $db->bind(':estudiante_id', $estudiante->id);
                $db->bind(':actividad_id', $actividad->id);
                $nota = $db->single();

                $calificaciones[$estudiante->id][$actividad->id] = [
                    'id' => $nota ? $nota->id : null,
                    'calificacion' => $nota ? $nota->calificacion : null
                ];

                // Calcular contribución al promedio
                if ($nota && $nota->calificacion !== null) {
                    $total_puntos += $nota->calificacion * ($actividad->porcentaje / 100);
                    $total_porcentaje += $actividad->porcentaje;
                }
            }

            // Calcular promedio del trimestre
            $promedios[$estudiante->id] = $total_porcentaje > 0 ? round($total_puntos / ($total_porcentaje / 100), 2) : null;
        }
    }

    // Obtener información de la materia
    $db->query("SELECT nombre FROM materias WHERE id = :materia_id");
    $db->bind(':materia_id', $materia_id);
    $materia_actual = $db->single();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones - <?= htmlspecialchars($grupo->nombre) ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function guardarCalificacion(input) {
            const notaId = input.dataset.notaId;
            const estudianteId = input.dataset.estudiante;
            const actividadId = input.dataset.actividad;
            const calificacion = input.value;

            try {
                const response = await fetch('guardar_calificacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nota_id: notaId,
                        estudiante_id: estudianteId,
                        actividad_id: actividadId,
                        calificacion: calificacion
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar promedio
                    await actualizarPromedio(estudianteId);
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'La calificación se ha guardado correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Error al guardar');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                // Recargar para mantener consistencia
                setTimeout(() => window.location.reload(), 2000);
            }
        }

        async function actualizarPromedio(estudianteId) {
            const fila = document.querySelector(`tr[data-estudiante="${estudianteId}"]`);
            const inputs = fila.querySelectorAll('.calificacion-input');
            let totalPuntos = 0;
            let totalPorcentaje = 0;

            inputs.forEach(input => {
                const porcentaje = parseFloat(input.dataset.porcentaje);
                const valor = parseFloat(input.value);

                if (!isNaN(valor)) {
                    totalPuntos += valor * (porcentaje / 100);
                    totalPorcentaje += porcentaje;
                }
            });

            if (totalPorcentaje > 0) {
                const promedio = (totalPuntos / (totalPorcentaje / 100)).toFixed(2);
                fila.querySelector('.promedio').textContent = promedio;
            } else {
                fila.querySelector('.promedio').textContent = 'N/A';
            }
        }
    </script>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <!-- Encabezado y pestañas (mantener igual que antes) -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-3xl font-bold">Calificaciones - <?= htmlspecialchars($grupo->nombre) ?></h2>
                    <div class="flex items-center mt-2 text-gray-600">
                        <span class="mr-4"><i
                                class="fas fa-graduation-cap mr-2"></i><?= htmlspecialchars($grupo->grado) ?></span>
                        <span><i
                                class="fas fa-calendar-alt mr-2"></i><?= htmlspecialchars($grupo->ciclo_escolar) ?></span>
                    </div>
                </div>
                <a href="ver_grupo.php?id=<?= $grupo_id ?>" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al grupo
                </a>
            </div>

            <!-- Pestañas -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px">
                    <li class="mr-2">
                        <a href="ver_grupo.php?id=<?= $grupo_id ?>"
                            class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-users mr-2"></i> Estudiantes
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="calificaciones.php?id=<?= $grupo_id ?>"
                            class="inline-block p-4 border-b-2 border-blue-500 text-blue-600">
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
            <!-- Filtros -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="id" value="<?= $grupo_id ?>">

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

                    <?php if ($materia_id): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
                            <select name="trimestre" class="w-full p-2 border rounded" onchange="this.form.submit()">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <option value="<?= $i ?>" <?= $trimestre == $i ? 'selected' : '' ?>>
                                        Trimestre <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($materia_id): ?>
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($materia_actual->nombre) ?> - Trimestre
                        <?= $trimestre ?></h3>

                    <?php if (empty($actividades)): ?>
                        <div class="bg-white p-6 rounded-lg shadow text-center">
                            <p class="text-gray-500">No hay actividades registradas para este trimestre.</p>
                            <a href="actividades.php?id=<?= $grupo_id ?>&materia_id=<?= $materia_id ?>"
                                class="mt-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                <i class="fas fa-plus mr-1"></i> Crear Actividad
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Tabla de calificaciones mejorada -->
                        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">
                                                Estudiante</th>
                                            <?php foreach ($actividades as $actividad): ?>
                                                <th
                                                    class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">
                                                    <div class="flex flex-col items-center">
                                                        <span><?= htmlspecialchars($actividad->nombre) ?></span>
                                                        <span class="text-xs text-gray-500"><?= $actividad->porcentaje ?>%</span>
                                                        <span
                                                            class="text-xs text-blue-600"><?= date('d/m/Y', strtotime($actividad->fecha_entrega)) ?></span>
                                                    </div>
                                                </th>
                                            <?php endforeach; ?>
                                            <th
                                                class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">
                                                Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($estudiantes as $estudiante): ?>
                                            <tr data-estudiante="<?= $estudiante->id ?>">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?= htmlspecialchars($estudiante->nombre_completo) ?>
                                                    <?php if ($estudiante->nie): ?>
                                                        <div class="text-sm text-gray-500">NIE: <?= $estudiante->nie ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <?php foreach ($actividades as $actividad):
                                                    $nota = $calificaciones[$estudiante->id][$actividad->id] ?? null;
                                                    ?>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <input type="number" step="0.01" min="0" max="10"
                                                            class="w-20 p-1 border rounded calificacion-input text-center"
                                                            value="<?= $nota['calificacion'] ?? '' ?>"
                                                            data-nota-id="<?= $nota['id'] ?? '' ?>"
                                                            data-estudiante="<?= $estudiante->id ?>"
                                                            data-actividad="<?= $actividad->id ?>"
                                                            data-porcentaje="<?= $actividad->porcentaje ?>"
                                                            onchange="guardarCalificacion(this)" placeholder="0.00">
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-center font-semibold promedio">
                                                    <?= $promedios[$estudiante->id] ?? 'N/A' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Resumen estadístico -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
                                <h4 class="font-medium mb-2">Promedio del grupo</h4>
                                <p class="text-2xl font-bold">
                                    <?php
                                    $suma_promedios = 0;
                                    $contador = 0;
                                    foreach ($promedios as $promedio) {
                                        if ($promedio !== null) {
                                            $suma_promedios += $promedio;
                                            $contador++;
                                        }
                                    }
                                    echo $contador > 0 ? round($suma_promedios / $contador, 2) : 'N/A';
                                    ?>
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
                                <h4 class="font-medium mb-2">Mejor promedio</h4>
                                <p class="text-2xl font-bold">
                                    <?= count($promedios) > 0 ? max(array_filter($promedios)) : 'N/A' ?>
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
                                <h4 class="font-medium mb-2">Peor promedio</h4>
                                <p class="text-2xl font-bold">
                                    <?= count($promedios) > 0 ? min(array_filter($promedios)) : 'N/A' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between">
                            <a href="actividades.php?id=<?= $grupo_id ?>&materia_id=<?= $materia_id ?>"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 inline-block">
                                <i class="fas fa-tasks mr-1"></i> Gestionar Actividades
                            </a>
                            <button onclick="exportarPDF()"
                                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                <i class="fas fa-file-pdf mr-1"></i> Exportar a PDF
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <p class="text-gray-500">Seleccione una materia para ver las calificaciones.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportarPDF() {
            Swal.fire({
                title: 'Exportar a PDF',
                text: '¿Desea exportar las calificaciones actuales a PDF?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Exportar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí puedes implementar la exportación a PDF
                    // Por ahora solo mostramos un mensaje
                    Swal.fire(
                        'Exportado',
                        'El PDF se generará en breve...',
                        'success'
                    );
                }
            });
        }
    </script>
</body>

</html>