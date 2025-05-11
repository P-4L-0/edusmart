<?php
// Configuración básica
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartedu');

// Configuración de la aplicación
define('APP_NAME', 'SmartEdu');
define('APP_URL', 'http://localhost/smartedu');

// Iniciar sesión
session_start();

// Incluir funciones
require_once 'functions.php';

// Conectar a la base de datos
require_once 'db.php';
$db = new Database();

$options = array(
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Esto es importante para transacciones
);