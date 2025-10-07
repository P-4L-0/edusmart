<?php
$password = 'p4l0123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;