<?php
require 'includes/session.php';
require 'includes/db.php';

// Verifica si el usuario ha iniciado sesión y si es administrador
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit();
}

function calcular_fecha_periodo($periodo) {
    $fecha = new DateTime();
    switch ($periodo) {
        case '1dia':
            $fecha->modify('+1 day');
            break;
        case '1semana':
            $fecha->modify('+1 week');
            break;
        case '1mes':
            $fecha->modify('+1 month');
            break;
        case '3meses':
            $fecha->modify('+3 months');
            break;
        case '6meses':
            $fecha->modify('+6 months');
            break;
        case '1año':
            $fecha->modify('+1 year');
            break;
        default:
            $fecha = null;
            break;
    }
    return $fecha ? $fecha->format('Y-m-d') : null;
}

// Maneja la creación de un nuevo usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_usuario'])) {
    $usuario = $_POST['usuario'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $status = $_POST['status'];
    $periodo = $_POST['periodo'];
    $fecha_manual = $_POST['fecha_manual'];

    $fecha_periodo = $fecha_manual ? $fecha_manual : calcular_fecha_periodo($periodo);

    $sql = "INSERT INTO clientes_user (usuario, contraseña, rol, status, fecha_periodo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $usuario, $contraseña, $rol, $status, $fecha_periodo);
    $stmt->execute();
    header("Location: panel.php");
    exit();
}

// Maneja la actualización de un usuario existente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_usuario'])) {
    $id_user = $_POST['id_user'];
    $usuario = $_POST['usuario'];
    $rol = $_POST['rol'];
    $status = $_POST['status'];
    $contraseña = $_POST['contraseña'];
    $periodo = $_POST['periodo'];
    $fecha_manual = $_POST['fecha_manual'];

    $fecha_periodo = $fecha_manual ? $fecha_manual : calcular_fecha_periodo($periodo);

    if (!empty($contraseña)) {
        $contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
        $sql = "UPDATE clientes_user SET usuario = ?, rol = ?, status = ?, contraseña = ?, fecha_periodo = ? WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssi", $usuario, $rol, $status, $contraseña, $fecha_periodo, $id_user);
    } else {
        $sql = "UPDATE clientes_user SET usuario = ?, rol = ?, status = ?, fecha_periodo = ? WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siisi", $usuario, $rol, $status, $fecha_periodo, $id_user);
    }
    $stmt->execute();
    header("Location: panel.php");
    exit();
}

// Maneja la eliminación de un usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_usuario'])) {
    $id_user = $_POST['id_user'];

    // Elimina la información relacionada del usuario en otras tablas
    $sqlEliminarDirecciones = "DELETE FROM clientes_direccion WHERE id_user = ?";
    $stmtEliminarDirecciones = $conn->prepare($sqlEliminarDirecciones);
    $stmtEliminarDirecciones->bind_param("i", $id_user);
    $stmtEliminarDirecciones->execute();

    $sqlEliminarContactos = "DELETE FROM clientes_telefonos WHERE id_user = ?";
    $stmtEliminarContactos = $conn->prepare($sqlEliminarContactos);
    $stmtEliminarContactos->bind_param("i", $id_user);
    $stmtEliminarContactos->execute();

    $sqlEliminarPrompts = "DELETE FROM clientes_prompt WHERE id_user = ?";
    $stmtEliminarPrompts = $conn->prepare($sqlEliminarPrompts);
    $stmtEliminarPrompts->bind_param("i", $id_user);
    $stmtEliminarPrompts->execute();

    $sqlEliminarUsuario = "DELETE FROM clientes_user WHERE id_user = ?";
    $stmtEliminarUsuario = $conn->prepare($sqlEliminarUsuario);
    $stmtEliminarUsuario->bind_param("i", $id_user);
    $stmtEliminarUsuario->execute();

    header("Location: panel.php");
    exit();
}

// Obtiene todos los usuarios para mostrarlos en el panel de administración
$sql = "SELECT id_user, usuario, rol, status, fecha_periodo, token_jwt FROM clientes_user";
$result = $conn->query($sql);
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <script>
        function confirmarEliminacion(event) {
            if (!confirm("¿Estás seguro de que deseas eliminar este usuario y toda su información asociada?")) {
                event.preventDefault();
            }
        }

        function mostrarToken() {
            var id_user = document.getElementById("usuariosSelect").value;
            var token = document.getElementById("token-" + id_user).textContent;
            document.getElementById("tokenDisplay").value = token;
        }
    </script>
    <style>
        /* Estilos para las pestañas */
        .tab-content {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-primary text-center" role="alert">
                    BIENVENIDO ADMINISTRADOR <?php echo htmlspecialchars($_SESSION['id_user']); ?>
                </div>
            </div>
        </div>
        <h2 class="mb-4 text-center">Panel de Administración</h2>

        <!-- Pestañas -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="gestion-usuarios-tab" data-toggle="tab" href="#gestion-usuarios" role="tab" aria-controls="gestion-usuarios" aria-selected="true">Gestión de Usuarios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="claves-token-tab" data-toggle="tab" href="#claves-token" role="tab" aria-controls="claves-token" aria-selected="false">Claves Token</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Pestaña de Gestión de Usuarios -->
            <div class="tab-pane fade show active" id="gestion-usuarios" role="tabpanel" aria-labelledby="gestion-usuarios-tab">
                <h3 class="mb-4 text-center">Crear Nuevo Usuario</h3>
                <form method="post" action="panel.php" class="mb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="usuario">Usuario:</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="contraseña">Contraseña:</label>
                            <input type="password" class="form-control" id="contraseña" name="contraseña" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="rol">Rol:</label>
                            <select class="form-control" id="rol" name="rol" required>
                                <option value="1">Administrador</option>
                                <option value="2">Cliente</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="status">Estatus:</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="periodo">Periodo:</label>
                            <select class="form-control" id="periodo" name="periodo">
                                <option value="1dia">1 día</option>
                                <option value="1semana">1 semana</option>
                                <option value="1mes">1 mes</option>
                                <option value="3meses">3 meses</option>
                                <option value="6meses">6 meses</option>
                                <option value="1año">1 año</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="fecha_manual">Fecha Manual:</label>
                            <input type="date" class="form-control" id="fecha_manual" name="fecha_manual">
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="crear_usuario" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>

                <h3 class="mb-4 text-center">Administrar Usuarios</h3>
                <div class="table-responsive">
                    <div class="admin-user-row" style="display: none;"></div>
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="admin-user-row">
                            <form method="post" action="panel.php">
                                <div class="form-group">
                                    <label>ID:</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['id_user']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Usuario:</label>
                                    <input type="text" class="form-control" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Contraseña:</label>
                                    <input type="password" class="form-control" name="contraseña" placeholder="Dejar en blanco para no cambiar">
                                </div>
                                <div class="form-group">
                                    <label>Rol:</label>
                                    <select class="form-control" name="rol" required>
                                        <option value="1" <?php if ($usuario['rol'] == 1) echo 'selected'; ?>>Administrador</option>
                                        <option value="2" <?php if ($usuario['rol'] == 2) echo 'selected'; ?>>Cliente</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Estatus:</label>
                                    <select class="form-control" name="status" required>
                                        <option value="1" <?php if ($usuario['status'] == 1) echo 'selected'; ?>>Activo</option>
                                        <option value="0" <?php if ($usuario['status'] == 0) echo 'selected'; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fecha Periodo:</label>
                                    <input type="date" class="form-control" name="fecha_manual" value="<?php echo htmlspecialchars($usuario['fecha_periodo']); ?>">
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="id_user" value="<?php echo $usuario['id_user']; ?>">
                                    <button type="submit" name="actualizar_usuario" class="btn btn-primary">Actualizar</button>
                                    <button type="submit" name="eliminar_usuario" class="btn btn-danger" onclick="confirmarEliminacion(event)">Eliminar</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Pestaña de Claves Token -->
            <div class="tab-pane fade" id="claves-token" role="tabpanel" aria-labelledby="claves-token-tab">
                <h3 class="mb-4 text-center">Claves Token</h3>
                <div class="row">
                    <div class="col-md-4 offset-md-4 mb-3">
                        <label for="usuariosSelect">Seleccionar Usuario:</label>
                        <select class="form-control" id="usuariosSelect" onchange="mostrarToken()">
                            <option value="">Seleccione un usuario</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id_user']; ?>"><?php echo htmlspecialchars($usuario['usuario']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 offset-md-2 mb-3">
                        <label for="tokenDisplay">Token JWT:</label>
                        <textarea class="form-control" id="tokenDisplay" rows="5" readonly></textarea>
                        <?php foreach ($usuarios as $usuario): ?>
                            <div id="token-<?php echo $usuario['id_user']; ?>" style="display: none;"><?php echo htmlspecialchars($usuario['token_jwt']); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
