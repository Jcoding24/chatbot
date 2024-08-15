<?php
require 'includes/session.php';
require 'includes/db.php';
require 'includes/config.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

// Verifica si el usuario ha iniciado sesión y si es un cliente
if (!is_logged_in() || is_admin()) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Obtiene los datos del usuario logueado
$sql = "SELECT * FROM clientes_user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Genera el JWT
$payload = array(
    "id_user" => $id_user,
    "exp" => strtotime($user['fecha_periodo'])
);

$jwt = JWT::encode($payload, $key, 'HS256');

$sqlUpdateToken = "UPDATE clientes_user SET token_jwt = ? WHERE id_user = ?";
$stmtUpdateToken = $conn->prepare($sqlUpdateToken);
$stmtUpdateToken->bind_param("si", $jwt, $id_user);
$stmtUpdateToken->execute();

// Obtiene las sucursales del usuario
$sqlSucursales = "SELECT * FROM clientes_direccion WHERE id_user = ? AND tipo = 1";
$stmtSucursales = $conn->prepare($sqlSucursales);
$stmtSucursales->bind_param("i", $id_user);
$stmtSucursales->execute();
$resultSucursales = $stmtSucursales->get_result();
$sucursales = $resultSucursales->fetch_all(MYSQLI_ASSOC);

// Obtiene las direcciones del usuario para el combo
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

// Obtiene los historiales del usuario
$sqlHistoriales = "SELECT id_historial, fecha_historial, hora_inicio FROM clientes_historial WHERE id_user = ?";
$stmtHistoriales = $conn->prepare($sqlHistoriales);
$stmtHistoriales->bind_param("i", $id_user);
$stmtHistoriales->execute();
$resultHistoriales = $stmtHistoriales->get_result();
$historiales = $resultHistoriales->fetch_all(MYSQLI_ASSOC);

// Maneja la actualización de datos del usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $nombre_comercial = $_POST['nombre_comercial']; // Relacionado con 'empresa'
    $ruc = $_POST['ruc'];
    $direccionNueva = $_POST['direccion'];
    $rubro = $_POST['rubro'];
    $correo = $_POST['correo'];
    $contexto = $_POST['contexto'];
    $objetivo_principal = $_POST['objetivo_principal']; // Cambiado de 'personalidad' a 'objetivo_principal'
    $nombre_chatbot = $_POST['nombre_chatbot'];
    $horario = $_POST['horario'];
    $redes = $_POST['redes'];

    // Actualiza los datos del usuario en la base de datos
    $sql = "UPDATE clientes_user SET empresa = ?, RUC = ?, direccion = ?, rubro = ?, correo = ?, contexto = ?, personalidad = ?, nombre_chatbot = ?, horario = ?, redes = ? WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssi", $nombre_comercial, $ruc, $direccionNueva, $rubro, $correo, $contexto, $objetivo_principal, $nombre_chatbot, $horario, $redes, $id_user);
    $stmt->execute();

    // Inserta o actualiza la dirección en la tabla clientes_direccion
    $sqlDireccionCheck = "SELECT * FROM clientes_direccion WHERE id_user = ? AND tipo = 0";
    $stmtDireccionCheck = $conn->prepare($sqlDireccionCheck);
    $stmtDireccionCheck->bind_param("i", $id_user);
    $stmtDireccionCheck->execute();
    $resultDireccionCheck = $stmtDireccionCheck->get_result();

    if ($resultDireccionCheck->num_rows > 0) {
        $sqlDireccionUpdate = "UPDATE clientes_direccion SET direccion = ? WHERE id_user = ? AND tipo = 0";
        $stmtDireccionUpdate = $conn->prepare($sqlDireccionUpdate);
        $stmtDireccionUpdate->bind_param("si", $direccionNueva, $id_user);
        $stmtDireccionUpdate->execute();
    } else {
        $sqlDireccionInsert = "INSERT INTO clientes_direccion (id_user, direccion, tipo) VALUES (?, ?, 0)";
        $stmtDireccionInsert = $conn->prepare($sqlDireccionInsert);
        $stmtDireccionInsert->bind_param("is", $id_user, $direccionNueva);
        $stmtDireccionInsert->execute();
    }

    header("Location: dashboard.php");
    exit();
}

// Maneja la adición de sucursales
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_sucursal'])) {
    $nuevaSucursal = $_POST['nueva_sucursal'];

    $sqlSucursalInsert = "INSERT INTO clientes_direccion (id_user, direccion, tipo) VALUES (?, ?, 1)";
    $stmtSucursalInsert = $conn->prepare($sqlSucursalInsert);
    $stmtSucursalInsert->bind_param("is", $id_user, $nuevaSucursal);
    $stmtSucursalInsert->execute();

    header("Location: dashboard.php");
    exit();
}

// Maneja la edición de sucursales
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_sucursales'])) {
    foreach ($_POST['sucursales'] as $sucursalId => $direccionEditada) {
        $sqlSucursalUpdate = "UPDATE clientes_direccion SET direccion = ? WHERE id_direccion = ? AND id_user = ? AND tipo = 1";
        $stmtSucursalUpdate = $conn->prepare($sqlSucursalUpdate);
        $stmtSucursalUpdate->bind_param("sii", $direccionEditada, $sucursalId, $id_user);
        $stmtSucursalUpdate->execute();
    }

    header("Location: dashboard.php");
    exit();
}

// Maneja la eliminación de sucursales y sus vendedores asociados
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_sucursal'])) {
    $sucursalId = $_POST['sucursal_id'];

    // Elimina los contactos asociados a la sucursal
    $sqlEliminarContactos = "DELETE FROM clientes_telefonos WHERE id_direccion = ? AND id_user = ?";
    $stmtEliminarContactos = $conn->prepare($sqlEliminarContactos);
    $stmtEliminarContactos->bind_param("ii", $sucursalId, $id_user);
    $stmtEliminarContactos->execute();

    // Elimina la sucursal
    $sqlEliminarSucursal = "DELETE FROM clientes_direccion WHERE id_direccion = ? AND id_user = ?";
    $stmtEliminarSucursal = $conn->prepare($sqlEliminarSucursal);
    $stmtEliminarSucursal->bind_param("ii", $sucursalId, $id_user);
    $stmtEliminarSucursal->execute();

    header("Location: dashboard.php");
    exit();
}

// Maneja la adición de contactos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_contacto'])) {
    $vendedor = $_POST['vendedor'];
    $telefono = $_POST['telefono'];
    $direccionId = $_POST['direccion_id'];

    $sqlContactoInsert = "INSERT INTO clientes_telefonos (id_user, id_direccion, vendedor, telefono) VALUES (?, ?, ?, ?)";
    $stmtContactoInsert = $conn->prepare($sqlContactoInsert);
    $stmtContactoInsert->bind_param("iiss", $id_user, $direccionId, $vendedor, $telefono);
    $stmtContactoInsert->execute();

    header("Location: dashboard.php");
    exit();
}

// Maneja la edición de contactos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_contactos'])) {
    foreach ($_POST['contactos'] as $contactoId => $contactoData) {
        $vendedorEditado = $contactoData['vendedor'];
        $telefonoEditado = $contactoData['telefono'];
        $direccionEditadaId = $contactoData['direccion'];

        $sqlContactoUpdate = "UPDATE clientes_telefonos SET vendedor = ?, telefono = ?, id_direccion = ? WHERE id_contacto = ? AND id_user = ?";
        $stmtContactoUpdate = $conn->prepare($sqlContactoUpdate);
        $stmtContactoUpdate->bind_param("ssiii", $vendedorEditado, $telefonoEditado, $direccionEditadaId, $contactoId, $id_user);
        $stmtContactoUpdate->execute();
    }

    header("Location: dashboard.php");
    exit();
}

// Maneja la eliminación de contactos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_contacto'])) {
    $contactoId = $_POST['contacto_id'];

    $sqlContactoDelete = "DELETE FROM clientes_telefonos WHERE id_contacto = ? AND id_user = ?";
    $stmtContactoDelete = $conn->prepare($sqlContactoDelete);
    $stmtContactoDelete->bind_param("ii", $contactoId, $id_user);
    $stmtContactoDelete->execute();

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <script>
        function confirmarEliminacion(event) {
            if (!confirm("¿Estás seguro de que deseas eliminar este contacto?")) {
                event.preventDefault();
            }
        }

        function confirmarEliminacionSucursal(event) {
            if (!confirm("¿Estás seguro de que deseas eliminar esta sucursal y todos los contactos asociados?")) {
                event.preventDefault();
            }
        }

        function generarBot() {
            window.location.href = 'generate_bot.php';
        }

        function probarBot() {
            window.location.href = 'probar_bot.php';
        }

        function actualizarFechaHora() {
            const now = new Date();
            const fechaActual = now.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const horaActual = now.toLocaleTimeString('es-ES');

            document.getElementById('fecha-actual').innerText = fechaActual;
            document.getElementById('hora-actual').innerText = horaActual;
        }

        setInterval(actualizarFechaHora, 1000);

        function cargarHistorial(idHistorial) {
            fetch('obtener_historial.php?id_historial=' + idHistorial)
                .then(response => response.json())
                .then(data => {
                    const historialContainer = document.getElementById('historial-conversacion');
                    historialContainer.innerHTML = ''; // Limpiar el contenido anterior

                    if (data.error) {
                        historialContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    } else {
                        data.historial.forEach(item => {
                            const mensaje = document.createElement('div');
                            mensaje.className = item.tipo === 'usuario' ? 'message user' : 'message bot';
                            mensaje.innerHTML = `<strong>${item.hora} - ${item.tipo === 'usuario' ? 'Usuario' : 'Bot'}:</strong> ${item.mensaje}`;
                            historialContainer.appendChild(mensaje);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error al cargar el historial:', error);
                });
        }
    </script>
    <style>
        .nav-pills .nav-link.active {
            background-color: #007bff;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header-info div {
            margin-right: 10px;
        }
        .header-info .highlight {
            font-weight: bold;
            color: #dc3545;
        }
        .message.user {
            text-align: right;
            background-color: #d1ecf1;
            padding: 10px;
            border-radius: 10px;
            margin: 5px 0;
        }
        .message.bot {
            text-align: left;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body onload="actualizarFechaHora()">
    <div class="container mt-5">
        <div class="header-info">
            <div>
                <span>Fecha Actual: </span><span id="fecha-actual"></span>
            </div>
            <div>
                <span>Hora Actual: </span><span id="hora-actual"></span>
            </div>
            <div>
                <span>Periodo hasta: </span><span class="highlight"><?php echo date('d-m-Y', strtotime($user['fecha_periodo'])); ?></span>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-primary text-center" role="alert">
                    BIENVENIDO <?php echo htmlspecialchars($user['usuario']); ?>
                </div>
            </div>
        </div>
        <h2 class="mb-4 text-center">Gestión de Clientes</h2>
        <button type="button" class="btn btn-success btn-block mb-4" onclick="generarBot()">
            <i class="fas fa-robot"></i> Generar Bot
        </button>
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="gestion-clientes-tab" data-toggle="pill" href="#gestion-clientes" role="tab" aria-controls="gestion-clientes" aria-selected="true">Gestión de Clientes</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="instalacion-tab" data-toggle="pill" href="#instalacion" role="tab" aria-controls="instalacion" aria-selected="false">Instalación</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="historial-tab" data-toggle="pill" href="#historial" role="tab" aria-controls="historial" aria-selected="false">Historial de Conversaciones</a>
            </li>
        </ul>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="gestion-clientes" role="tabpanel" aria-labelledby="gestion-clientes-tab">
                <h2 class="mb-4">Gestión de Clientes</h2>
                <form method="post" action="dashboard.php" class="mb-4">
                    <div class="form-group">
                        <label for="nombre_comercial">Nombre Comercial:</label>
                        <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial" value="<?php echo htmlspecialchars($user['empresa']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="ruc">RUC:</label>
                        <input type="text" class="form-control" id="ruc" name="ruc" value="<?php echo htmlspecialchars($user['RUC']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($user['direccion']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="rubro">Rubro:</label>
                        <input type="text" class="form-control" id="rubro" name="rubro" value="<?php echo htmlspecialchars($user['rubro']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo:</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($user['correo']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contexto">Contexto:</label>
                        <textarea class="form-control" id="contexto" name="contexto" required><?php echo htmlspecialchars($user['contexto']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="objetivo_principal">Objetivo Principal del Chatbot/Personalidad:</label>
                        <input type="text" class="form-control" id="objetivo_principal" name="objetivo_principal" value="<?php echo htmlspecialchars($user['personalidad']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre_chatbot">Nombre del Chatbot:</label>
                        <input type="text" class="form-control" id="nombre_chatbot" name="nombre_chatbot" value="<?php echo htmlspecialchars($user['nombre_chatbot']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="horario">Horario:</label>
                        <input type="text" class="form-control" id="horario" name="horario" value="<?php echo htmlspecialchars($user['horario']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="redes">Redes:</label>
                        <textarea class="form-control" id="redes" name="redes" rows="4" required><?php echo htmlspecialchars($user['redes']); ?></textarea>
                    </div>
                    <button type="submit" name="actualizar" class="btn btn-primary">Guardar</button>
                </form>

                <h2 class="mb-4">Sucursales</h2>
                <form method="post" action="dashboard.php" class="mb-4">
                    <div class="form-group">
                        <label for="nueva_sucursal">Nueva Sucursal:</label>
                        <input type="text" class="form-control" id="nueva_sucursal" name="nueva_sucursal" required>
                    </div>
                    <button type="submit" name="agregar_sucursal" class="btn btn-primary">Agregar Sucursal</button>
                </form>
                <form method="post" action="dashboard.php" class="mb-4">
                    <ul class="list-group">
                        <?php foreach ($sucursales as $sucursal): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <input type="text" class="form-control" name="sucursales[<?php echo $sucursal['id_direccion']; ?>]" value="<?php echo htmlspecialchars($sucursal['direccion']); ?>" required>
                                <form method="post" action="dashboard.php" style="display:inline;">
                                    <input type="hidden" name="sucursal_id" value="<?php echo $sucursal['id_direccion']; ?>">
                                    <button type="submit" name="eliminar_sucursal" class="btn btn-danger ml-2" onclick="confirmarEliminacionSucursal(event)">Eliminar</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="submit" name="actualizar_sucursales" class="btn btn-primary mt-3">Actualizar Sucursales</button>
                </form>

                <h2 class="mb-4">Contactos</h2>
                <form method="post" action="dashboard.php" class="mb-4">
                    <div class="form-group">
                        <label for="vendedor">Nombre del Vendedor:</label>
                        <input type="text" class="form-control" id="vendedor" name="vendedor" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" required>
                    </div>
                    <div class="form-group">
                        <label for="direccion_id">Dirección:</label>
                        <select class="form-control" id="direccion_id" name="direccion_id" required>
                            <?php foreach ($direcciones as $direccion): ?>
                                <option value="<?php echo $direccion['id_direccion']; ?>"><?php echo htmlspecialchars($direccion['direccion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="agregar_contacto" class="btn btn-primary">Agregar Contacto</button>
                </form>

                <h2 class="mb-4">Actualizar Contactos</h2>
                <form method="post" action="dashboard.php" class="mb-4">
                    <ul class="list-group">
                        <?php foreach ($contactos as $contacto): ?>
                            <li class="list-group-item">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="contactos[<?php echo $contacto['id_contacto']; ?>][vendedor]" value="<?php echo htmlspecialchars($contacto['vendedor']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control" name="contactos[<?php echo $contacto['id_contacto']; ?>][telefono]" value="<?php echo htmlspecialchars($contacto['telefono']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <select class="form-control" name="contactos[<?php echo $contacto['id_contacto']; ?>][direccion]" required>
                                        <?php foreach ($direcciones as $direccion): ?>
                                            <option value="<?php echo $direccion['id_direccion']; ?>" <?php if ($direccion['id_direccion'] == $contacto['id_direccion']) echo 'selected'; ?>><?php echo htmlspecialchars($direccion['direccion']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="submit" name="actualizar_contactos" class="btn btn-primary mt-3">Actualizar Contactos</button>
                </form>

                <h2 class="mb-4">Eliminar Contactos</h2>
                <ul class="list-group">
                    <?php foreach ($contactos as $contacto): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($contacto['vendedor']); ?> - <?php echo htmlspecialchars($contacto['telefono']); ?> - 
                            <?php 
                                foreach ($direcciones as $direccion) {
                                    if ($direccion['id_direccion'] == $contacto['id_direccion']) {
                                        echo htmlspecialchars($direccion['direccion']);
                                        break;
                                    }
                                }
                            ?>
                            <form id="eliminar_contacto_form_<?php echo $contacto['id_contacto']; ?>" method="post" action="dashboard.php">
                                <input type="hidden" name="contacto_id" value="<?php echo $contacto['id_contacto']; ?>">
                                <button type="submit" name="eliminar_contacto" class="btn btn-danger" onclick="confirmarEliminacion(event)">Eliminar</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="tab-pane fade" id="instalacion" role="tabpanel" aria-labelledby="instalacion-tab">
                <h2 class="mb-4">Instalación del Chatbot</h2>
                <div class="form-group">
                    <label for="jwt">Tu Token JWT:</label>
                    <textarea class="form-control" id="jwt" rows="3" readonly><?php echo $jwt; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="snippet">Código para insertar el chatbot en tu página web:</label>
                    <textarea class="form-control" id="snippet" rows="5" readonly>&lt;script 
    src="https://lazzarcloud.com/lazzarbot/chatbot.js"
    data-jwt="<?php echo $jwt; ?>"
    data-title="Chat con Soporte"           
    data-primary-color="#275BDB"           
    data-secondary-color="#FFC300"         
    data-background-color="#F0F0F0"        
    data-greeting-message="Hola, ¿necesitas ayuda?"&gt;
&lt;/script&gt;</textarea>
                </div>
                <button type="button" class="btn btn-info btn-block" onclick="probarBot()">
                    <i class="fas fa-play"></i> Probar Bot
                </button>
            </div>
            <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
                <h2 class="mb-4">Historial de Conversaciones</h2>
                <div class="form-group">
                    <label for="historial">Selecciona una conversación:</label>
                    <select class="form-control" id="historial" onchange="cargarHistorial(this.value)">
                        <option value="">Selecciona...</option>
                        <?php foreach ($historiales as $historial): ?>
                            <option value="<?php echo $historial['id_historial']; ?>">
                                <?php echo $historial['fecha_historial'] . ' - ' . $historial['hora_inicio']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="historial-conversacion"></div>
            </div>
        </div>
        <form method="post" action="logout.php" class="mt-4 text-center">
            <button type="submit" class="btn btn-secondary">Cerrar Sesión</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
