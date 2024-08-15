(function() {
    function getSnippetParams() {
        var script = document.currentScript;
        return {
            title: script.getAttribute('data-title') || 'Chat con Jessi',
            primaryColor: script.getAttribute('data-primary-color') || '#275BDB',
            secondaryColor: script.getAttribute('data-secondary-color') || '#50E3C2',
            backgroundColor: script.getAttribute('data-background-color') || '#F5F7FA',
            greetingMessage: script.getAttribute('data-greeting-message') || 'Hola, ¿necesitas ayuda?',
            jwt: script.getAttribute('data-jwt') || ''
        };
    }

    var params = getSnippetParams();
    var soundEnabled = true;
    var conversationHistory = [];
    var isNewChat = true;

    var linkIcons = document.createElement('link');
    linkIcons.rel = 'stylesheet';
    linkIcons.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
    document.head.appendChild(linkIcons);

    var beepSound = new Audio('https://lazzarcloud.com/FormularioBot/resources/livechat.mp3');
    beepSound.addEventListener('error', function(e) {
        console.error('Error al cargar el sonido:', e);
    });

    var iconContainer = document.createElement('div');
    iconContainer.id = 'chat-icon-container';
    iconContainer.style.position = 'fixed';
    iconContainer.style.bottom = '20px';
    iconContainer.style.right = '20px';
    iconContainer.style.width = '60px';
    iconContainer.style.height = '60px';
    iconContainer.style.borderRadius = '50%';
    iconContainer.style.backgroundColor = 'transparent';
    iconContainer.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
    iconContainer.style.display = 'flex';
    iconContainer.style.alignItems = 'center';
    iconContainer.style.justifyContent = 'center';
    iconContainer.style.cursor = 'pointer';
    iconContainer.style.zIndex = '10000';
    iconContainer.style.animation = 'pulse 2s infinite';

    var iconImage = document.createElement('img');
    iconImage.src = 'https://lazzarcloud.com/FormularioBot/resources/icons-clients/chatbot_icon_default.png';
    iconImage.alt = 'Chatbot';
    iconImage.style.width = '60px';
    iconImage.style.height = '60px';
    iconImage.style.borderRadius = '50%';
    iconImage.style.background = 'transparent';
    iconContainer.appendChild(iconImage);

    var greetingMessage = document.createElement('div');
    greetingMessage.id = 'greeting-message';
    greetingMessage.style.position = 'absolute';
    greetingMessage.style.bottom = '80px';
    greetingMessage.style.right = '80px';
    greetingMessage.style.padding = '10px';
    greetingMessage.style.backgroundColor = params.primaryColor;
    greetingMessage.style.color = '#fff';
    greetingMessage.style.borderRadius = '10px';
    greetingMessage.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
    greetingMessage.style.fontSize = '14px';
    greetingMessage.style.display = 'none';
    greetingMessage.style.zIndex = '10000';
    greetingMessage.innerHTML = params.greetingMessage;
    document.body.appendChild(greetingMessage);

    document.body.appendChild(iconContainer);

    var chatContainer = document.createElement('div');
    chatContainer.id = 'chat-container';
    chatContainer.style.position = 'fixed';
    chatContainer.style.bottom = '90px';
    chatContainer.style.right = '20px';
    chatContainer.style.width = '90%';
    chatContainer.style.maxWidth = '400px';
    chatContainer.style.height = '500px';
    chatContainer.style.maxHeight = '80%';
    chatContainer.style.border = '1px solid #ccc';
    chatContainer.style.borderRadius = '10px';
    chatContainer.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
    chatContainer.style.backgroundColor = '#fff';
    chatContainer.style.zIndex = '10000';
    chatContainer.style.display = 'none';
    chatContainer.style.flexDirection = 'column';
    chatContainer.style.overflow = 'hidden';
    chatContainer.style.fontFamily = "'Roboto', sans-serif";
    chatContainer.style.transition = 'transform 0.3s ease-in-out, opacity 0.3s ease-in-out';
    document.body.appendChild(chatContainer);

    var chatHeader = document.createElement('div');
    chatHeader.style.display = 'flex';
    chatHeader.style.alignItems = 'center';
    chatHeader.style.justifyContent = 'space-between';
    chatHeader.style.padding = '10px';
    chatHeader.style.backgroundColor = params.primaryColor;
    chatHeader.style.color = '#fff';
    chatHeader.style.borderRadius = '10px 10px 0 0';
    chatHeader.innerHTML = `<span style="font-weight: bolder;">${params.title}</span>`;

    var headerButtons = document.createElement('div');

    var soundButton = document.createElement('button');
    soundButton.innerHTML = '<i class="fas fa-volume-up"></i>';
    soundButton.style.background = 'none';
    soundButton.style.border = 'none';
    soundButton.style.color = '#fff';
    soundButton.style.fontSize = '20px';
    soundButton.style.cursor = 'pointer';
    soundButton.style.marginRight = '10px';
    soundButton.addEventListener('click', function() {
        soundEnabled = !soundEnabled;
        soundButton.innerHTML = soundEnabled ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute"></i>';
    });
    headerButtons.appendChild(soundButton);

    var closeButton = document.createElement('button');
    closeButton.innerHTML = '<i class="fas fa-times"></i>';
    closeButton.style.background = 'none';
    closeButton.style.border = 'none';
    closeButton.style.color = '#fff';
    closeButton.style.fontSize = '20px';
    closeButton.style.cursor = 'pointer';
    closeButton.addEventListener('click', function() {
        minimizeChat();
    });
    headerButtons.appendChild(closeButton);

    chatHeader.appendChild(headerButtons);
    chatContainer.appendChild(chatHeader);

    var chatBody = document.createElement('div');
    chatBody.id = 'messages';
    chatBody.style.flexGrow = '1';
    chatBody.style.padding = '10px';
    chatBody.style.overflowY = 'auto';
    chatBody.style.backgroundColor = params.backgroundColor;
    chatContainer.appendChild(chatBody);

    var chatInputContainer = document.createElement('div');
    chatInputContainer.style.display = 'flex';
    chatInputContainer.style.borderTop = '1px solid #ccc';
    var chatInput = document.createElement('input');
    chatInput.type = 'text';
    chatInput.id = 'text';
    chatInput.placeholder = 'Escribe tu mensaje...';
    chatInput.style.flexGrow = '1';
    chatInput.style.padding = '10px';
    chatInput.style.border = 'none';
    chatInput.style.borderRadius = '0 0 0 10px';
    chatInput.style.fontFamily = "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Oxygen-Sans', Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif"; 
    chatInput.style.fontSize = '14px'; 
    chatInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    });
    var sendButton = document.createElement('button');
    sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
    sendButton.style.padding = '10px';
    sendButton.style.backgroundColor = params.primaryColor;
    sendButton.style.color = '#fff';
    sendButton.style.border = 'none';
    sendButton.style.cursor = 'pointer';
    sendButton.style.borderRadius = '0 0 10px 0';
    sendButton.addEventListener('click', sendMessage);
    chatInputContainer.appendChild(chatInput);
    chatInputContainer.appendChild(sendButton);
    chatContainer.appendChild(chatInputContainer);

    var chatFooter = document.createElement('div');
    chatFooter.style.textAlign = 'center';
    chatFooter.style.padding = '5px';
    chatFooter.style.fontSize = '12px';
    chatFooter.style.color = '#ccc';
    chatFooter.innerHTML = 'Desarrollado por <a href="https://www.lzzsol.com" target="_blank" style="color: #50E3C2;">Lazzar Solutions</a>';
    chatContainer.appendChild(chatFooter);

    var typingIndicator = document.createElement('div');
    typingIndicator.id = 'typing-indicator';
    typingIndicator.style.display = 'none';
    typingIndicator.style.position = 'absolute';
    typingIndicator.style.bottom = '70px'; // Ajustar la posición para que esté justo encima del área de ingreso de mensajes
    typingIndicator.style.left = '10px'; // Alineación a la izquierda
    typingIndicator.style.padding = '5px 10px';
    typingIndicator.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
    typingIndicator.style.borderRadius = '10px';
    typingIndicator.style.fontSize = '12px';
    typingIndicator.style.color = '#333';
    typingIndicator.innerHTML = 'Su asistente está escribiendo<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>';
    chatContainer.appendChild(typingIndicator);

    var style = document.createElement('style');
    style.type = 'text/css';
    style.innerHTML = `
        @keyframes blink {
            0% { opacity: 0; }
            50% { opacity: 1; }
            100% { opacity: 0; }
        }
        .dot {
            animation: blink 1.4s infinite;
        }
        .dot:nth-child(1) {
            animation-delay: 0s;
        }
        .dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        .dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        .message {
            opacity: 0;
            animation: fadeIn 0.5s forwards;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Oxygen-Sans', Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
        }
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.5);
            }
            70% {
                box-shadow: 0 0 10px 10px rgba(0, 0, 0, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 0, 0, 0);
            }
        }
        @media screen and (max-width: 420px) {
            #chat-container {
                width: 75%;
                height: 75%;
                bottom: 0;
                right: 0;
                border-radius: 0;
                max-width: none;
                max-height: none;
            }
            #chat-icon-container {
                width: 45px;
                height: 45px;
            }
            #chat-icon-container img {
                width: 45px;
                height: 45px;
            }
            #chat-header, #chat-footer, #chat-input-container {
                font-size: 75%;
            }
            #typing-indicator {
                bottom: 65px; // Ajustar la posición también en pantallas pequeñas
                font-size: 75%;
            }
        }
    `;
    document.getElementsByTagName('head')[0].appendChild(style);

    iconContainer.addEventListener('click', function() {
        if (chatContainer.style.display === 'none' || chatContainer.style.opacity === '0') {
            chatContainer.style.display = 'flex';
            greetingMessage.style.display = 'none';
            setTimeout(function() {
                chatContainer.style.transform = 'scale(1)';
                chatContainer.style.opacity = '1';
                chatContainer.style.bottom = '90px';
            }, 10);
        } else {
            minimizeChat();
        }
    });

    setTimeout(function() {
        if (chatContainer.style.display === 'none' || chatContainer.style.opacity === '0') {
            greetingMessage.style.display = 'block';
        }
    }, 2000);

    function minimizeChat() {
        chatContainer.style.transform = 'scale(0)';
        chatContainer.style.opacity = '0';
        chatContainer.style.bottom = '20px';
        setTimeout(function() {
            chatContainer.style.display = 'none';
            greetingMessage.style.display = 'block'; // Mostrar el mensaje de saludo cuando el chat esté minimizado
        }, 500);
    }

    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function sendMessage() {
        var text = chatInput.value;
        if (text.trim() === "") return;

        var userMessage = document.createElement('div');
        userMessage.className = 'message user';
        userMessage.style.marginBottom = '10px';
        userMessage.style.textAlign = 'right';
        userMessage.innerHTML = `<div style="display: inline-block; background: ${params.primaryColor}; color: #fff; padding: 10px; border-radius: 20px 20px 0 20px; max-width: 70%; word-wrap: break-word; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Oxygen-Sans', Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif; font-size: 14px;">${text}</div>`;
        chatBody.appendChild(userMessage);

        chatInput.value = "";

        conversationHistory.push(`Usuario: ${text}`);

        typingIndicator.style.display = 'block';
        scrollToBottom();

        fetch("https://lazzarcloud.com/lazzarbot/response.php", {
            method: "post",
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + params.jwt
            },
            body: JSON.stringify({
                text: text,
                history: conversationHistory,
                new_chat: isNewChat // Enviar la bandera de nuevo chat
            }),
        })
        .then((res) => res.json())
        .then((res) => {
            typingIndicator.style.display = 'none';

            var botMessage = document.createElement('div');
            botMessage.className = 'message bot';
            botMessage.style.marginBottom = '10px';
            botMessage.style.textAlign = 'left';
            botMessage.innerHTML = `<div style="display: inline-block; background: ${params.secondaryColor}; color: #333; padding: 10px; border-radius: 20px 20px 20px 0; max-width: 70%; word-wrap: break-word; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Oxygen-Sans', Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif; font-size: 14px;">${formatBotResponse(res.response)}</div>`;
            chatBody.appendChild(botMessage);

            conversationHistory.push(`Bot: ${res.response}`);

            if (soundEnabled) {
                beepSound.play().catch((e) => console.error('Error al reproducir el sonido:', e));
            }

            scrollToBottom();
            
            // Marcar que no es un nuevo chat después del primer mensaje
            isNewChat = false;
        })
        .catch((error) => {
            console.error('Error:', error);
            typingIndicator.style.display = 'none';
        });
    }

    // Nueva función para formatear la respuesta del bot
    function formatBotResponse(response) {
        var formattedResponse = response
            .replace(/(?:\r\n|\r|\n)/g, '<br>') // Saltos de línea
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Negritas
            .replace(/\*(.*?)\*/g, '<em>$1</em>') // Cursivas
            .replace(/\_\_(.*?)\_\_/g, '<u>$1</u>') // Subrayado
            .replace(/\~\~(.*?)\~\~/g, '<del>$1</del>') // Tachado
            .replace(/(?:^|\s)- (.*?)(?:\r\n|\r|\n)/g, '<ul><li>$1</li></ul>') // Listas
            .replace(/(?:^|\s)\d+\. (.*?)(?:\r\n|\r|\n)/g, '<ol><li>$1</li></ol>'); // Listas numeradas

        return formattedResponse;
    }

})();
