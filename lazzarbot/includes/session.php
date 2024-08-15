<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['id_user']);
}

function is_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] == 1;
}

// Configurar duración de la sesión
$session_duration = 15 * 60; // 15 minutos en segundos

// Comprobar si la sesión está activa y la última actividad
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_duration)) {
    // Si la sesión ha expirado, destruirla y redirigir al login
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Actualizar la última actividad
$_SESSION['LAST_ACTIVITY'] = time();
?>
