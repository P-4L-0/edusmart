<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo admin
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 min-h-screen p-4">
            <h1 class="text-2xl font-bold mb-6"><?php echo APP_NAME; ?></h1>
            <p class="text-blue-200 mb-6">Bienvenido, <?php echo $_SESSION['nombre']; ?></p>
            
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="block px-4 py-2 rounded-lg bg-blue-700">Dashboard</a>
                    </li>
                    <li>
                        <a href="usuarios.php" class="block px-4 py-2 rounded-lg hover:bg-blue-700">Usuarios</a>
                    </li>
                    <li>
                        <a href="materias.php" class="block px-4 py-2 rounded-lg hover:bg-blue-700">Materias</a>
                    </li>
                    <li>
                        <a href="grupos.php" class="block px-4 py-2 rounded-lg hover:bg-blue-700">Grupos</a>
                    </li>
                    <li>
                        <a href="D:\xampp\htdocs\smartedu\logout.php" class="block px-4 py-2 rounded-lg hover:bg-red-700">Cerrar Sesión</a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Panel de Administración</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-2">Total Usuarios</h3>
                    <?php
                    $db = new Database();
                    $db->query("SELECT COUNT(*) as total FROM usuarios");
                    $result = $db->single();
                    ?>
                    <p class="text-3xl font-bold"><?php echo $result->total; ?></p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-2">Total Maestros</h3>
                    <?php
                    $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 3");
                    $result = $db->single();
                    ?>
                    <p class="text-3xl font-bold"><?php echo $result->total; ?></p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-2">Total Rectores</h3>
                    <?php
                    $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2");
                    $result = $db->single();
                    ?>
                    <p class="text-3xl font-bold"><?php echo $result->total; ?></p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-4">Últimos usuarios registrados</h3>
                <?php
                $db->query("SELECT u.*, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.fecha_creacion DESC LIMIT 5");
                $usuarios = $db->resultSet();
                ?>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($usuarios as $usuario): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($usuario->nombre_completo); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($usuario->username); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($usuario->rol); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($usuario->fecha_creacion)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>