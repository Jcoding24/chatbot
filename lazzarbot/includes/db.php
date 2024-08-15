<?php
$servername = "127.0.0.1";
$username = "u342201105_admin_chatbot";
$password = "Chatbot123#";
$dbname = "u342201105_chatbotproject";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
