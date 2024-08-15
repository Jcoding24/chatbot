// Función para guardar la información inicial en el servidor
function setInitialInfo() {
    var initialInfo = document.getElementById("initialInfo").value;

    fetch("response.php", {
        method: "post",
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            'initial_info': initialInfo
        })
    })
    .then((res) => res.text())
    .then((res) => {
        alert(res); // Muestra una alerta indicando que la información inicial se ha guardado correctamente
    });
}

// Función para generar una respuesta basándose en la información inicial almacenada
function generateResponse() {
    var text = document.getElementById("text").value;
    var response = document.getElementById("response");

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
        response.innerHTML = res;
    });
}
