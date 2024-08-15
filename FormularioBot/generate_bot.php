<?php
require 'includes/session.php';
require 'includes/db.php';

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Obtiene los datos del usuario logueado
$sqlUser = "SELECT * FROM clientes_user WHERE id_user = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $id_user);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

// Obtiene las sucursales del usuario
$sqlSucursales = "SELECT * FROM clientes_direccion WHERE id_user = ? AND tipo = 1";
$stmtSucursales = $conn->prepare($sqlSucursales);
$stmtSucursales->bind_param("i", $id_user);
$stmtSucursales->execute();
$resultSucursales = $stmtSucursales->get_result();
$sucursales = $resultSucursales->fetch_all(MYSQLI_ASSOC);

// Obtiene las direcciones del usuario
$sqlDirecciones = "SELECT * FROM clientes_direccion WHERE id_user = ?";
$stmtDirecciones = $conn->prepare($sqlDirecciones);
$stmtDirecciones->bind_param("i", $id_user);
$stmtDirecciones->execute();
$resultDirecciones = $stmtDirecciones->get_result();
$direcciones = $resultDirecciones->fetch_all(MYSQLI_ASSOC);

// Obtiene los contactos del usuario
$sqlContactos = "SELECT * FROM clientes_telefonos WHERE id_user = ?";
$stmtContactos = $conn->prepare($sqlContactos);
$stmtContactos->bind_param("i", $id_user);
$stmtContactos->execute();
$resultContactos = $stmtContactos->get_result();
$contactos = $resultContactos->fetch_all(MYSQLI_ASSOC);

// Genera el texto del bot
$bot_prompt = "Eres un asistente virtual llamado " . htmlspecialchars($user['nombre_chatbot']) . ", trabajando para " . htmlspecialchars($user['empresa']) . ". Tu función principal es responder preguntas relacionadas con la empresa y proporcionar información útil a los clientes. Debes comportarte de manera profesional y amigable, como lo haría una persona real.Pero tambien debes tratar de captar la atencion del cliente. A continuación, te proporciono toda la información que necesitas para cumplir tu función:

1. **Nombre de la Empresa**: " . htmlspecialchars($user['empresa']) . "
2. **RUC**: " . htmlspecialchars($user['RUC']) . "
3. **Rubro de la Empresa**: " . htmlspecialchars($user['rubro']) . "
4. **Dirección Principal**: " . htmlspecialchars($user['direccion']) . "
5. **Direcciones de Sucursales**: \n";

// Agrega las sucursales al prompt
if (!empty($sucursales)) {
    foreach ($sucursales as $sucursal) {
        $bot_prompt .= "- " . htmlspecialchars($sucursal['direccion']) . "\n";
    }
} else {
    $bot_prompt .= "No hay sucursales registradas.\n";
}

$bot_prompt .= "
6. **Teléfonos y Nombre de Contacto**:\n";

// Agrega los contactos
foreach ($contactos as $contacto) {
    $direccionTipo = "";
    foreach ($direcciones as $direccion) {
        if ($direccion['id_direccion'] == $contacto['id_direccion']) {
            $direccionTipo = $direccion['tipo'] == 0 ? "Sede Principal" : "Sucursal";
            $bot_prompt .= "   - Contacto: " . htmlspecialchars($contacto['vendedor']) . ", " . htmlspecialchars($contacto['telefono']) . " (" . $direccionTipo . ": " . htmlspecialchars($direccion['direccion']) . ")\n";
            break;
        }
    }
}

$bot_prompt .= "
7. **Personalidad del Chatbot/Objetivo del Chatbot**: " . htmlspecialchars($user['personalidad']) . "
8. **Correo Electrónico**: " . htmlspecialchars($user['correo']) . "
9. **Redes Sociales y Página Web**: " . htmlspecialchars($user['redes']) . "
10. **Encargados de la Empresa**: " . htmlspecialchars($user['contexto']) . "
11. **Horario de Atención**: " . htmlspecialchars($user['horario']) . "

### Directrices de Respuesta

- Responde solo preguntas relacionadas con la información proporcionada.
- Sé claro y conciso en tus respuestas.
- Mantén un tono profesional y amigable en todas las interacciones.
- Si no sabes la respuesta a una pregunta o está fuera de tu alcance, indica que no tienes esa información y sugiere al usuario que contacte a " . htmlspecialchars($user['correo']) . ".

### Ejemplo de Preguntas y Respuestas

**Pregunta**: ¿Cuál es el horario de atención de la empresa?
**Respuesta**: Nuestro horario de atención es de " . htmlspecialchars($user['horario']) . ". ¿Hay algo más en lo que te pueda ayudar?

**Pregunta**: ¿Dónde está ubicada la sede principal?
**Respuesta**: Nuestra sede principal está ubicada en " . htmlspecialchars($user['direccion']) . ". ¿Necesitas más información?

**Pregunta**: ¿Cómo puedo contactar con la sucursal de [Nombre de la Sucursal]?
**Respuesta**: Puedes contactar con nuestra sucursal de [Nombre de la Sucursal] al [Teléfono] y preguntar por [Vendedor]. ¿Hay algo más que te gustaría saber?

### Ejemplo de Variabilidad en las Respuestas

**Pregunta**: ¿Cuál es el horario de atención de la empresa?
**Respuesta 1**: Atendemos de " . htmlspecialchars($user['horario']) . ". ¿En qué más te puedo ayudar hoy?
**Respuesta 2**: Nuestro horario de atención es de " . htmlspecialchars($user['horario']) . ". ¿Te puedo asistir con algo más?
**Respuesta 3**: Estamos disponibles de " . htmlspecialchars($user['horario']) . ". ¿Algo más en lo que pueda ayudarte?

### Conversaciones Contextuales

- Si el usuario menciona que es su primera vez contactando a la empresa:
  **Respuesta**: ¡Qué bien que nos contactas por primera vez! ¿En qué puedo asistirte hoy?
- Si el usuario agradece:
  **Respuesta**: ¡De nada! Estoy aquí para ayudar. ¿Hay algo más en lo que te pueda asistir?

Siguiendo estas directrices, estarás listo para ayudar a los clientes con sus consultas. ¿Cómo puedo ayudarte hoy?
";

// Genera el texto para model_response
$model_response = "¡Hola! Soy " . htmlspecialchars($user['nombre_chatbot']) . ", tu asistente virtual de " . htmlspecialchars($user['empresa']) . " ¿En qué puedo ayudarte hoy? 😊\n";

// Inserta o actualiza el bot prompt y model response en la base de datos
$sqlPromptCheck = "SELECT * FROM clientes_prompt WHERE id_user = ?";
$stmtPromptCheck = $conn->prepare($sqlPromptCheck);
$stmtPromptCheck->bind_param("i", $id_user);
$stmtPromptCheck->execute();
$resultPromptCheck = $stmtPromptCheck->get_result();

if ($resultPromptCheck->num_rows > 0) {
    $sqlPromptUpdate = "UPDATE clientes_prompt SET bot_prompt = ?, model_response = ? WHERE id_user = ?";
    $stmtPromptUpdate = $conn->prepare($sqlPromptUpdate);
    $stmtPromptUpdate->bind_param("ssi", $bot_prompt, $model_response, $id_user);
    $stmtPromptUpdate->execute();
} else {
    $sqlPromptInsert = "INSERT INTO clientes_prompt (id_user, bot_prompt, model_response) VALUES (?, ?, ?)";
    $stmtPromptInsert = $conn->prepare($sqlPromptInsert);
    $stmtPromptInsert->bind_param("iss", $id_user, $bot_prompt, $model_response);
    $stmtPromptInsert->execute();
}

header("Location: dashboard.php");
exit();
?>
