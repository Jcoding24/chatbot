<?php
// Verificar si se recibió el parámetro 'n' en la URL
if (isset($_GET['n'])) {
    // Obtener el valor del parámetro 'n'
    $numero = $_GET['n'];
    
    // Preparar la respuesta en formato JSON
    $respuesta = array(
        'mensaje' => 'Número recibido correctamente',
        'numero' => $numero
    );
    
    // Devolver la respuesta en formato JSON
    header('Content-Type: application/json');
    echo json_encode($respuesta);
} else {
    // Manejar el caso en el que no se reciba el parámetro 'n'
    $respuesta = array(
        'error' => 'No se recibió el parámetro "n"'
    );
    
    // Devolver la respuesta de error en formato JSON
    header('Content-Type: application/json');
    echo json_encode($respuesta);
}
?>
