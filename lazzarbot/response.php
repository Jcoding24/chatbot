<?php

// Habilitar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require 'vendor/autoload.php';
require 'includes/config.php';

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Configuración de la base de datos
$servername = "82.197.82.7";
$username = "u342201105_admin_chatbot";
$password = "Chatbot123#";
$dbname = "u342201105_chatbotproject";

try {
    // Crear conexión a la base de datos
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Configurar el modo de errores de PDO a excepción
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage();
    exit;
}

// Obtener el JWT de la cabecera de la solicitud
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(['error' => 'No se proporcionó el token de autorización']);
    exit;
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);

try {
    // Decodificar el JWT
    $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    $id_user = $decoded->id_user;
} catch (Exception $e) {
    echo json_encode(['error' => 'Token inválido: ' . $e->getMessage()]);
    exit;
}

try {
    // Obtener la información inicial de la tabla clientes_prompt
    $stmt = $conn->prepare("SELECT bot_prompt FROM clientes_prompt WHERE id_user = ?");
    $stmt->execute([$id_user]);

    // Obtener el resultado
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        throw new Exception("No se encontró información para el usuario con id_user: $id_user");
    }
    $initial_info = $result['bot_prompt'];
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Clave API para la API de Gemini
$api_key = 'AIzaSyDpqTDzA6x_pOGFtbf9SyIf9-L6_-kD4u4';
$client = new Client($api_key);

// Obtiene los datos enviados en la solicitud POST
$data = json_decode(file_get_contents("php://input"));

// Recuperar el historial de la conversación
$conversation_history = $data->history;
$short_term_memory_limit = 5;

// Mantener solo los últimos N mensajes relevantes
$short_term_memory = array_slice($conversation_history, -$short_term_memory_limit);

// Resumir los mensajes más antiguos para la memoria a largo plazo
$long_term_memory = summarizeConversation(array_slice($conversation_history, 0, -$short_term_memory_limit));

// Concatenar la información inicial con la memoria a largo plazo y la memoria a corto plazo
$prompt = $initial_info . "\n" . $long_term_memory . "\n" . implode("\n", $short_term_memory) . "\nUsuario: " . $data->text . "\nBot:";

// Registrar la solicitud en un archivo de texto
file_put_contents('conversation_log.txt', "\n\n[SOLICITUD ENVIADA A LA API]\n" . $prompt, FILE_APPEND);

try {
    $response = $client->geminiPro()->generateContent(
        new TextPart($prompt)
    );
    $bot_response = $response->text();
    // Registrar la respuesta en un archivo de texto
    file_put_contents('conversation_log.txt', "\n[RESPUESTA RECIBIDA DE LA API]\n" . $bot_response, FILE_APPEND);
    echo json_encode(['response' => $bot_response]);

    // Guardar el historial de la conversación en la base de datos
    $fecha_historial = date('Y-m-d');
    $hora_inicio = date('H:i:s');

    // Verificar si es un nuevo chat
    if (isset($data->new_chat) && $data->new_chat) {
        // Crear una nueva entrada en la tabla de historiales
        $sqlHistorial = "INSERT INTO clientes_historial (id_user, fecha_historial, hora_inicio, historial_info) VALUES (?, ?, ?, ?)";
        $stmtHistorial = $conn->prepare($sqlHistorial);
        $historial_info = [
            ["hora" => $hora_inicio, "mensaje" => $data->text, "tipo" => "usuario"],
            ["hora" => $hora_inicio, "mensaje" => $bot_response, "tipo" => "bot"]
        ];
        $historial_json = json_encode($historial_info);
        $stmtHistorial->execute([$id_user, $fecha_historial, $hora_inicio, $historial_json]);
    } else {
        // Obtener el historial actual de la conversación desde la base de datos
        $stmtHistorial = $conn->prepare("SELECT id_historial, historial_info FROM clientes_historial WHERE id_user = ? ORDER BY id_historial DESC LIMIT 1");
        $stmtHistorial->execute([$id_user]);
        $historial_result = $stmtHistorial->fetch(PDO::FETCH_ASSOC);
        
        if ($historial_result) {
            $historial_info = json_decode($historial_result['historial_info'], true);

            // Agregar los nuevos mensajes al historial
            $hora_actual = date('H:i:s');
            $historial_info[] = ["hora" => $hora_actual, "mensaje" => $data->text, "tipo" => "usuario"];
            $historial_info[] = ["hora" => $hora_actual, "mensaje" => $bot_response, "tipo" => "bot"];

            $historial_json = json_encode($historial_info);

            // Actualizar el historial en la base de datos
            $sqlHistorial = "UPDATE clientes_historial SET historial_info = ? WHERE id_user = ? AND id_historial = ?";
            $stmtHistorial = $conn->prepare($sqlHistorial);
            $stmtHistorial->execute([$historial_json, $id_user, $historial_result['id_historial']]);
        }
    }

} catch (Exception $e) {
    $error_message = 'Error al generar la respuesta: ' . $e->getMessage();
    file_put_contents('conversation_log.txt', "\n[ERROR]\n" . $error_message, FILE_APPEND);
    echo json_encode(['error' => $error_message]);
}

function summarizeConversation($conversation) {
    // Aquí puedes implementar una lógica de resumen personalizada
    // Por ahora, simplemente concatenamos los mensajes como un ejemplo
    return implode(" ", $conversation);
}
?>
