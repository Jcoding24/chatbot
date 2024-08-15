<?php
// Genera una clave secreta aleatoria
$secret_key = bin2hex(random_bytes(32)); // Esto genera una clave de 64 caracteres hexadecimales
echo $secret_key;
?>