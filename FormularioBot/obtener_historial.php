<?php
require 'includes/session.php';
require 'includes/db.php';
require 'includes/config.php';

// Verifica si el usuario ha iniciado sesión y si es un cliente
if (!is_logged_in() || is_admin()) {
    echo json_encode(['error' => 'No tienes permiso para ver este contenido.']);
    exit();
}

$id_user = $_SESSION['id_user'];

if (isset($_GET['id_historial'])) {
    $id_historial = $_GET['id_historial'];

    // Obtiene el historial del usuario logueado y con el id_historial proporcionado
    $sqlHistorial = "SELECT historial_info FROM clientes_historial WHERE id_user = ? AND id_historial = ?";
    $stmtHistorial = $conn->prepare($sqlHistorial);
    $stmtHistorial->bind_param("ii", $id_user, $id_historial);
    $stmtHistorial->execute();
    $resultHistorial = $stmtHistorial->get_result();

    if ($resultHistorial->num_rows > 0) {
        $historial = $resultHistorial->fetch_assoc();
        echo json_encode(['historial' => json_decode($historial['historial_info'], true)]);
    } else {
        echo json_encode(['error' => 'No se encontró el historial solicitado.']);
    }
} else {
    echo json_encode(['error' => 'No se proporcionó el ID del historial.']);
}
?>
