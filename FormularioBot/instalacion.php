<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/session.php';
require 'includes/db.php';
require 'includes/config.php';
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;

try {
    // Verifica si el usuario ha iniciado sesión
    if (!is_logged_in() || is_admin()) {
        header("Location: login.php");
        exit();
    }

    $id_user = $_SESSION['id_user'];

    // Obtiene los datos del usuario logueado
    $sql = "SELECT fecha_periodo FROM clientes_user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $id_user);
    if (!$stmt->execute()) {
        throw new Exception("Error en la ejecución de la consulta: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("Usuario no encontrado.");
    }

    $fecha_periodo = new DateTime($user['fecha_periodo']);
    $exp = $fecha_periodo->getTimestamp();

    $payload = array(
        "id_user" => $id_user,
        "exp" => $exp
    );

    // Incluye el algoritmo de firma como tercer parámetro
    $jwt = JWT::encode($payload, $key, 'HS256');
} catch (Exception $e) {
    // Manejo de errores
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalación</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Token de Instalación</h2>
        <p>Utiliza el siguiente token para la instalación:</p>
        <pre><?php echo htmlspecialchars($jwt); ?></pre>
        
        <h2>Snippet de Instalación</h2>
        <p>Copia este código en tu página web para cargar los servicios del chatbot:</p>
        <pre>
&lt;div id="chatbot"&gt;&lt;/div&gt;
&lt;script 
    src="https://lazzarcloud.com/lazzarbot/chatbot.js"
    data-title="Chat con Soporte"           
    data-primary-color="#275BDB"           
    data-secondary-color="#FFC300"         
    data-background-color="#F0F0F0"        
    data-greeting-message="Hola, ¿necesitas ayuda?"
    data-jwt="<?php echo htmlspecialchars($jwt); ?>"
&gt;&lt;/script&gt;
        </pre>

        <form method="post" action="logout.php" class="mt-4">
            <button type="submit" class="btn btn-secondary">Cerrar Sesión</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
