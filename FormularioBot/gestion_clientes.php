<?php
require 'includes/session.php';
require 'includes/db.php';

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

<h3>Gestión de Clientes</h3>
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

<h3 class="mb-4">Sucursales</h3>
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

<h3 class="mb-4">Contactos</h3>
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

<h3 class="mb-4">Actualizar Contactos</h3>
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

<h3 class="mb-4">Eliminar Contactos</h3>
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
