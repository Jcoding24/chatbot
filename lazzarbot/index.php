<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Bot</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            background-color: #F5F7FA;
        }
        #messages {
            flex-grow: 1;
            padding: 10px;
            margin: 10px;
            border-radius: 5px;
            background-color: #fff;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 10px;
        }
        .message.user {
            text-align: right;
        }
        .message.bot {
            text-align: left;
        }
        .message.user > div {
            display: inline-block;
            background: #4A90E2;
            color: #fff;
            padding: 10px;
            border-radius: 20px 20px 0 20px;
            max-width: 70%;
            word-wrap: break-word;
        }
        .message.bot > div {
            display: inline-block;
            background: #E3F2FD;
            color: #333;
            padding: 10px;
            border-radius: 20px 20px 20px 0;
            max-width: 70%;
            word-wrap: break-word;
        }
        #chat-input {
            display: flex;
            border-top: 1px solid #ccc;
        }
        input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: none;
            border-radius: 0 0 0 5px;
        }
        button {
            padding: 10px;
            background-color: #4A90E2;
            color: #fff;
            border: none;
            border-radius: 0 0 5px 0;
            cursor: pointer;
        }
        button:hover {
            background-color: #357ABD;
        }
    </style>
</head>
<body>
    <div id="messages"></div>
    <div id="chat-input">
        <input type="text" id="text" placeholder="Escribe tu mensaje..." onkeypress="handleKeyPress(event)">
        <button onclick="sendMessage();"><i class="fas fa-paper-plane"></i></button>
    </div>

    <script>
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function sendMessage() {
            var text = document.getElementById("text").value;
            if (text.trim() === "") return;

            // Añadir mensaje del usuario al chat
            var userMessage = document.createElement("div");
            userMessage.className = "message user";
            userMessage.innerHTML = `<div>${text}</div>`;
            document.getElementById("messages").appendChild(userMessage);

            // Limpiar el campo de entrada de texto
            document.getElementById("text").value = "";

            // Enviar mensaje al servidor
            fetch("response.php", {
                method: "post",
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    text: text
                }),
            })
            .then((res) => res.text())
            .then((res) => {
                // Añadir respuesta del bot al chat
                var botMessage = document.createElement("div");
                botMessage.className = "message bot";
                botMessage.innerHTML = `<div>${res}</div>`;
                document.getElementById("messages").appendChild(botMessage);

                // Desplazar hacia abajo para ver el nuevo mensaje
                document.getElementById("messages").scrollTop = document.getElementById("messages").scrollHeight;
            });
        }
    </script>
    <!-- Cargar la fuente de iconos y Google Fonts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</body>
</html>
