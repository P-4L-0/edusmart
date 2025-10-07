<?php
// script para generar la contraseña del admin
// run : consola > php hash.php
$password = 'p4l0123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;