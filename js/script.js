    /* =========================================================
   EQUILIBRIO MENTAL - SCRIPT GENERAL
   ========================================================= */

/* CONFIGURACIÓN */
var RUTA_PHP = "../php/";

/* =========================================================
   PESTAÑAS DE INICIO DE SESIÓN Y REGISTRO
   ========================================================= */

function mostrarPestana(pestana) {
    var loginForm = document.getElementById("loginForm");
    var registroForm = document.getElementById("registroForm");
    var loginTab = document.getElementById("tab-login");
    var registroTab = document.getElementById("tab-registro");

    // Esta función se carga en varias páginas del sitio.
    // En las páginas que no son de acceso, no hace nada.
    if (!loginForm || !registroForm || !loginTab || !registroTab) {
        return;
    }

    var mostrarRegistro = pestana === "registro";

    loginForm.style.display = mostrarRegistro ? "none" : "flex";
    registroForm.style.display = mostrarRegistro ? "flex" : "none";

    loginTab.classList.toggle("active", !mostrarRegistro);
    registroTab.classList.toggle("active", mostrarRegistro);
}

/* =========================================================
   ALMACENAMIENTO
   ========================================================= */

function _setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);

    document.cookie =
        name + "=" + encodeURIComponent(value) +
        ";expires=" + d.toUTCString() +
        ";path=/;SameSite=Lax";
}

function _getCookie(name) {
    var prefijo = name + "=";
    var partes = document.cookie.split(";");

    for (var i = 0; i < partes.length; i++) {
        var c = partes[i].trim();

        if (c.indexOf(prefijo) === 0) {
            return decodeURIComponent(c.substring(prefijo.length));
        }
    }

    return null;
}

function storeSet(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (e) {
        console.warn("No se pudo usar localStorage:", e);
    }

    _setCookie(key, value, 365);
}

function storeGet(key) {
    try {
        var value = localStorage.getItem(key);
        if (value) return value;
    } catch (e) {
        console.warn("No se pudo leer localStorage:", e);
    }

    return _getCookie(key);
}

function storeRemove(key) {
    try {
        localStorage.removeItem(key);
    } catch (e) {}

    _setCookie(key, "", -1);
}

/* =========================================================
   USUARIO
   ========================================================= */

function getUser() {
    try {
        var datos = storeGet("usuario");
        return datos ? JSON.parse(datos) : null;
    } catch (e) {
        console.error("Error leyendo los datos del usuario:", e);
        return null;
    }
}

function setUser(usuario) {
    if (!usuario) return;

    storeSet("usuario", JSON.stringify(usuario));
}

function removeUser() {
    storeRemove("usuario");
}

/* =========================================================
   COMPROBAR SESIÓN PHP
   ========================================================= */

async function comprobarSesion() {
    try {
        var respuesta = await fetch(RUTA_PHP + "sesion.php", {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store",
            headers: {
                "Accept": "application/json"
            }
        });

            if (!respuesta.ok) {
        var textoError = await respuesta.text();
        var mensajeUsuario;

        if (respuesta.status === 503) {
            mensajeUsuario = "La IA está ocupada en este momento. Inténtalo de nuevo en unos minutos.";
        } else if (respuesta.status === 429) {
            // La cuota diaria agotada no se soluciona reintentando: mostrar aviso claro.
            mensajeUsuario = "Se alcanzó el límite gratuito de consultas de la IA. Inténtalo de nuevo cuando se renueve la cuota.";
        } else if ([400, 401, 403].includes(respuesta.status)) {
            try {
                localStorage.removeItem("gemini_api_key");
            } catch (e) {}
            mensajeUsuario = "No se pudo validar la API Key. Revisa la clave e inténtalo de nuevo.";
        } else {
            mensajeUsuario = "No se pudo contactar con la IA. Inténtalo de nuevo más tarde.";
        }

        // El detalle técnico solo queda en la consola, nunca en el mensaje del chat.
        console.error("Error Gemini:", respuesta.status, textoError);
        throw new Error(mensajeUsuario);
    }

    var data = await respuesta.json();

    if (
        data.candidates &&
        data.candidates[0] &&
        data.candidates[0].content &&
        data.candidates[0].content.parts &&
        data.candidates[0].content.parts[0]
    ) {
        return data.candidates[0].content.parts[0].text;
    }

    return "Lo siento, no pude generar una respuesta.";
}

/* =========================================================
   INICIALIZACIÓN
   ========================================================= */

document.addEventListener("DOMContentLoaded", async function() {
    updateCartUI();

    await iniciarAuthGuard();

    actualizarDatosUsuario();
    crearBotonLogout();
});

/* =========================================================
   PRODUCTOS
   ========================================================= */

var products = [
    {
        id: 1,
        name: "Pulseras con Propósito",
        description: "Pulseras con mensajes que inspiran calma, fuerza, confianza y gratitud.",
        price: 120000,
        currency: "COP",
        images: [
            { src: "../img/manilla-calma.png", label: "Calma" },
            { src: "../img/manilla-fuerza.png", label: "Fuerza" },
            { src: "../img/manilla-confianza.png", label: "Confianza" },
            { src: "../img/manilla-amor-propio.png", label: "Amor propio" },
            { src: "../img/manilla-gratitud.png", label: "Gratitud" },
            { src: "../img/manilla-suenos.png", label: "Sueños" }
        ]
    },
    {
        id: 2,
        name: "Velas de Calma",
        description: "Elige el aroma que acompañará tus momentos de tranquilidad.",
        price: 90000,
        currency: "COP",
        images: [
            { src: "../img/vela-calma.png", label: "Calma" },
            { src: "../img/vela-amor-propio.png", label: "Amor propio" },
            { src: "../img/vela-energia.png", label: "Energía" },
            { src: "../img/vela-tranquilidad.png", label: "Tranquilidad" },
            { src: "../img/vela-enfoque.png", label: "Enfoque" },
            { src: "../img/vela-suenos.png", label: "Sueños" }
        ]
    },
    {
        id: 3,
        name: "Smartwatch Bienestar",
        description: "Un reloj inteligente que te acompaña en tu rutina diaria.",
        price: 100000,
        currency: "COP",
        images: [
            { src: "../img/reloj-pantalla-bienestar.png", label: "Pantalla bienestar" },
            { src: "../img/reloj-respiracion.png", label: "Respiración" },
            { src: "../img/reloj-hidratacion.png", label: "Hidratación" },
            { src: "../img/reloj-sensor.png", label: "Sensor" },
            { src: "../img/relojes-colores.png", label: "Colores disponibles" }
        ],
        colors: [
            { name: "Rosa", hex: "#e9a9b9" },
            { name: "Verde", hex: "#a8b39a" },
            { name: "Negro", hex: "#29272a" }
        ]
    },
    {
        id: 4,
        name: "Diario de Emociones",
        description: "Un espacio personal para expresar tus sentimientos.",
        price: 90000,
        currency: "COP",
        images: [
            { src: "../img/diario.jpg", label: "Diario" }
        ]
    },
    {
        id: 5,
        name: "Kit de Autocuidado",
        description: "Todo lo necesario para regalarte un momento especial.",
        price: 90000,
        currency: "COP",
        images: [
            { src: "../img/kit-auto-cuidado.jpg", label: "Kit completo" },
            { src: "../img/kit-caja.jpg", label: "Caja" },
            { src: "../img/kit-diario.jpg", label: "Diario" },
            { src: "../img/kit-bolsa.jpg", label: "Bolsa" },
            { src: "../img/kit-vela.jpg", label: "Vela" },
            { src: "../img/kit-tarjetas.jpg", label: "Tarjetas" },
            { src: "../img/kit-boligrafo.jpg", label: "Bolígrafo" },
            { src: "../img/kit-stickers.jpg", label: "Stickers" },
            { src: "../img/kit-reto-habitos.jpg", label: "Reto de hábitos" }
        ]
    }
];
