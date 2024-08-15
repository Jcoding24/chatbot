<?php
require 'includes/db.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

$secret_key = '36b9c111eac179c511fb0ee2fa0e33df6b938cdfef10932e8ffa016d60ed88c8'; // Asegúrate de mantener esta clave secreta segura

try {
    // Obtener todos los usuarios
    $sql = "SELECT id_user, usuario, fecha_periodo FROM clientes_user";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Error al ejecutar la consulta: " . $conn->error);
    }

    echo "<h1>Tokens de los Usuarios</h1>";
    echo "<ul>";

    while ($row = $result->fetch_assoc()) {
        $id_user = $row['id_user'];
        $usuario = $row['usuario'];
        $fecha_periodo = $row['fecha_periodo'];

        // Calcular el tiempo de expiración del token en base a fecha_periodo
        $expiration_time = strtotime($fecha_periodo);

        // Generar el token JWT
        $issuer = "tu_dominio.com";
        $audience = "tu_dominio.com";
        $issuedAt = time();
        $notBefore = $issuedAt;
        $expire = $expiration_time;

        $token = array(
            "iss" => $issuer,
            "aud" => $audience,
            "iat" => $issuedAt,
            "nbf" => $notBefore,
            "exp" => $expire,
            "data" => array(
                "id" => $id_user,
                "username" => $usuario
            )
        );

        $jwt = JWT::encode($token, $secret_key);

        echo "<li>Usuario: $usuario - Token: $jwt</li>";
    }

    echo "</ul>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
