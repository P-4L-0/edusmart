<?php
// Este archivo genera el menú lateral (sidebar) para la interfaz de administración.
?>

<div class="bg-blue-800 text-white w-64 min-h-screen p-4">
    <!-- Título del sidebar con el nombre de la aplicación -->
    <h1 class="text-2xl font-bold mb-6"><?php echo APP_NAME; ?></h1>
    
    <!-- Mensaje de bienvenida con el nombre del usuario autenticado -->
    <p class="text-blue-200 mb-6">Bienvenido, <?php echo $_SESSION['nombre']; ?></p>
    
    <!-- Navegación principal del sidebar -->
    <nav>
        <ul class="space-y-2">
            <!-- Enlace al Dashboard -->
            <li>
                <a href="dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-blue-700">
                    Dashboard
                </a>
            </li>
            
            <!-- Enlace a la gestión de usuarios -->
            <li>
                <a href="usuarios.php" class="block px-4 py-2 rounded-lg bg-blue-700">
                    Usuarios
                </a>
            </li>
            
            <!-- Enlace a la gestión de materias -->
            <li>
                <a href="materias.php" class="block px-4 py-2 rounded-lg hover:bg-blue-700">
                    Materias
                </a>
            </li>
            
            <!-- Enlace a la gestión de grupos -->
            <li>
                <a href="grupos.php" class="block px-4 py-2 rounded-lg hover:bg-blue-700">
                    Grupos
                </a>
            </li>
            
            <!-- Enlace para cerrar sesión -->
            <li>
                <a href="<?php echo APP_URL; ?>/logout.php" class="block px-4 py-2 rounded-lg hover:bg-red-700">
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>
</div>