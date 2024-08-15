<?php
require 'includes/session.php';
require 'includes/db.php';

// Verifica si el usuario ha iniciado sesión y si es un cliente
if (!is_logged_in() || is_admin()) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Obtiene el token JWT del usuario logueado
$sql = "SELECT token_jwt FROM clientes_user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$jwt = $user['token_jwt'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Probar Bot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #F5F7FA;
        }
        #chatbot-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <div id="chatbot-container">
        <div id="chatbot"></div>
    </div>

    <script 
        src="https://lazzarcloud.com/lazzarbot/chatbot.js"
        data-title="Chat con Soporte"           
        data-primary-color="#275BDB"           
        data-secondary-color="#FFC300"         
        data-background-color="#F0F0F0"        
        data-greeting-message="Hola, ¿necesitas ayuda?"
        data-jwt="<?php echo $jwt; ?>">
    </script>
</body>
</html>
