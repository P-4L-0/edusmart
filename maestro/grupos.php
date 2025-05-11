<?php
require_once 'D:\xampp\htdocs\smartedu\includes\config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];

// Obtener grupos asignados
$db->query("SELECT g.id, g.nombre, g.grado, g.ciclo_escolar, 
                   COUNT(e.id) as num_estudiantes
            FROM grupos g
            LEFT JOIN estudiantes e ON g.id = e.grupo_id
            WHERE g.maestro_id = :maestro_id
            GROUP BY g.id
            ORDER BY g.nombre");
$db->bind(':maestro_id', $maestro_id);
$grupos = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Grupos - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include './partials/sidebar.php'; ?>

        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold">Mis Grupos</h2>
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al dashboard
                </a>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">Listado de Grupos Asignados</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">Grupo</th>
                                <th class="px-6 py-3 text-left">Grado</th>
                                <th class="px-6 py-3 text-left">Ciclo Escolar</th>
                                <th class="px-6 py-3 text-left">Estudiantes</th>
                                <th class="px-6 py-3 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($grupos) > 0): ?>
                                <?php foreach ($grupos as $grupo): ?>
                                    <tr>
                                        <td class="px-6 py-4"><?= htmlspecialchars($grupo->nombre) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($grupo->grado) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($grupo->ciclo_escolar) ?></td>
                                        <td class="px-6 py-4"><?= $grupo->num_estudiantes ?></td>
                                        <td class="px-6 py-4 space-x-2">
                                            <a href="ver_grupo.php?id=<?= $grupo->id ?>"
                                                class="text-blue-500 hover:text-blue-700">
                                                <i class="fas fa-users mr-1"></i> Ver estudiantes
                                            </a>
                                            <a href="calificaciones/listar.php?grupo_id=<?= $grupo->id ?>"
                                                class="text-green-500 hover:text-green-700">
                                                <i class="fas fa-graduation-cap mr-1"></i> Calificaciones
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No tienes grupos asignados
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>