<?php
require 'includes/db.php';
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;

$key = "36b9c111eac179c511fb0ee2fa0e33df6b938cdfef10932e8ffa016d60ed88c8";
$headers = apache_request_headers();

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);

    try {
        $decoded = JWT::decode($token, $key, array('HS256'));
        $userId = $decoded->userId;

        // Obtener la información del usuario desde la base de datos
        $stmt = $conn->prepare("SELECT * FROM clientes_user WHERE id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Usuario no encontrado"]);
        }
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["message" => "Token inválido"]);
    }
} else {
    http_response_code(401);
    echo json_encode(["message" => "Autorización requerida"]);
}
?>
