<?php
if (!function_exists('generarUsername')) {
    function generarUsername($nombre_completo, $db) {
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
        
        $db->query("SELECT id FROM usuarios WHERE username = :username");
        $db->bind(':username', $username);
        while ($db->single()) {
            $username = $base_username . $contador;
            $contador++;
            $db->bind(':username', $username);
        }
        
        return $username;
    }
}

if (!function_exists('generarPassword')) {
    function generarPassword($longitud = 10) {
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $password = '';
        $max = strlen($caracteres) - 1;
        for ($i = 0; $i < $longitud; $i++) {
            $password .= $caracteres[random_int(0, $max)];
        }
        return $password;
    }
}


function verificarLogin($username, $password) {
    $db = new Database();
    $db->query("SELECT * FROM usuarios WHERE username = :username AND activo = 1");
    $db->bind(':username', $username);
    $user = $db->single();
    
    if($user && password_verify($password, $user->password)) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['rol'] = $user->rol_id;
        $_SESSION['nombre'] = $user->nombre_completo;
        return true;
    }
    return false;
}

function redirigirSegunRol() {
    if(isset($_SESSION['rol'])) {
        switch($_SESSION['rol']) {
            case 1:
                header('Location: ' . APP_URL . '/admin/dashboard.php');
                break;
            case 2:
                header('Location: ' . APP_URL . '/rector/dashboard.php');
                break;
            case 3:
                header('Location: ' . APP_URL . '/maestro/dashboard.php');
                break;
            default:
                header('Location: ' . APP_URL . '/login.php');
        }
        exit;
    }
}

function protegerPagina($roles_permitidos = array()) {
    if(!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    
    if(!empty($roles_permitidos) && !in_array($_SESSION['rol'], $roles_permitidos)) {
        header('Location: ' . APP_URL . '/acceso-denegado.php');
        exit;
    }
}