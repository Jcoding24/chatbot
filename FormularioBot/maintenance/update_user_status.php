<?php
require 'includes/db.php';

$hoy = date('Y-m-d');

// Selecciona todos los usuarios cuyo periodo haya terminado
$sql = "UPDATE clientes_user SET status = 0 WHERE fecha_periodo IS NOT NULL AND fecha_periodo <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hoy);
$stmt->execute();

echo "Usuarios actualizados correctamente.";
?>
