<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];

// Obtener materias asignadas al maestro
$db->query("SELECT m.id, m.nombre, m.descripcion 
           FROM materias m
           JOIN maestros_materias mm ON mm.materia_id = m.id
           WHERE mm.maestro_id = :maestro_id AND m.activa = 1
           ORDER BY m.nombre");
$db->bind(':maestro_id', $maestro_id);
$materias = $db->resultSet();

// Obtener grupos asignados al maestro
$db->query("SELECT g.id, g.nombre, g.grado, g.ciclo_escolar 
           FROM grupos g
           WHERE g.maestro_id = :maestro_id
           ORDER BY g.grado, g.nombre");
$db->bind(':maestro_id', $maestro_id);
$grupos = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Materias - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Mis Materias</h2>

            <!-- Tarjetas de Materias -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($materias as $materia): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($materia->nombre) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($materia->descripcion) ?></p>
                        <div class="flex justify-between items-center">
                            <a href="actividades.php?materia_id=<?= $materia->id ?>" 
                               class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-tasks mr-1"></i> Actividades
                            </a>
                            <button onclick="mostrarGruposParaReporte(<?= $materia->id ?>)"
                                    class="text-green-500 hover:text-green-700">
                                <i class="fas fa-file-pdf mr-1"></i> Generar Reporte
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Modal para seleccionar grupo para reporte -->
            <div id="modal-reporte" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-4">Generar Reporte de Calificaciones</h3>
                        <form id="form-reporte" method="post" action="generar_reporte.php" target="_blank">
                            <input type="hidden" name="materia_id" id="materia-reporte">
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Seleccione Grupo:</label>
                                <select name="grupo_id" class="w-full p-2 border rounded" required>
                                    <option value="">-- Seleccione un grupo --</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?= $grupo->id ?>">
                                            <?= htmlspecialchars($grupo->grado) ?> - <?= htmlspecialchars($grupo->nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Trimestre:</label>
                                <select name="trimestre" class="w-full p-2 border rounded" required>
                                    <option value="1">Primer Trimestre</option>
                                    <option value="2">Segundo Trimestre</option>
                                    <option value="3">Tercer Trimestre</option>
                                    <option value="0">Promedio Final</option>
                                </select>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="cerrarModal()"
                                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                    Cancelar
                                </button>
                                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    <i class="fas fa-file-pdf mr-1"></i> Generar PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarGruposParaReporte(materiaId) {
            document.getElementById('materia-reporte').value = materiaId;
            document.getElementById('modal-reporte').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modal-reporte').classList.add('hidden');
        }
    </script>
</body>
</html>