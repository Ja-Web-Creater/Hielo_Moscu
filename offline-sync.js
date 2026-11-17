// Interceptor seguro para el modo sin conexión (Offline-Sync)
function gestionarEnvioFormulario(event, formElement, urlDestino) {
    if (!navigator.onLine) {
        event.preventDefault(); // Evita que la página falle al no haber servidor
        
        // Capturar los datos del formulario de manera segura
        const formData = new FormData(formElement);
        const datosObjeto = {};
        formData.forEach((valor, clave) => { datosObjeto[clave] = valor; });
        
        // Obtener pendientes anteriores validando que el JSON sea correcto
        let pendientes = [];
        try {
            let almacenado = localStorage.getItem('hielo_pendientes_sync');
            if (almacenado) {
                pendientes = JSON.parse(almacenado);
                if (!Array.isArray(pendientes)) pendientes = [];
            }
        } catch (e) {
            pendientes = []; // Si hubo un error previo, reiniciamos la lista de forma limpia
        }

        pendientes.push({
            url: urlDestino,
            datos: datosObjeto,
            fecha: new Date().toISOString()
        });
        
        // Guardar de forma segura en la memoria local del dispositivo
        localStorage.setItem('hielo_pendientes_sync', JSON.stringify(pendientes));
        
        // Mostrar alerta visual al operador
        mostrarAlertaOffline(pendientes.length);
        
        // Simular éxito visual sin romper la interfaz
        formElement.reset();
        return false;
    }
    return true; // Si hay internet, el formulario se envía normal de forma tradicional
}

// Mostrar notificación flotante de registros pendientes
function mostrarAlertaOffline(cantidad) {
    let alerta = document.getElementById('alerta-offline-banner');
    if (!alerta) {
        alerta = document.createElement('div');
        alerta.id = 'alerta-offline-banner';
        alerta.style.cssText = "position:fixed; bottom:20px; right:20px; background:#ff9800; color:white; padding:12px 20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.3); z-index:9999; font-family:sans-serif; font-weight:bold;";
        document.body.appendChild(alerta);
    }
    alerta.innerHTML = `⚠️ Sin conexión: ${cantidad} registro(s) en el limbo pendientes de sincronizar.`;
}

// Sincronizar automáticamente al recuperar la señal de internet
window.addEventListener('online', async function() {
    let pendientes = [];
    try {
        pendientes = JSON.parse(localStorage.getItem('hielo_pendientes_sync')) || [];
    } catch (e) {
        pendientes = [];
    }

    if (pendientes.length === 0) return;

    let alerta = document.getElementById('alerta-offline-banner');
    if (alerta) {
        alerta.style.background = "#2196F3"; // Azul: sincronizando
        alerta.innerHTML = `🔄 Conexión restablecida. Sincronizando registros...`;
    }

    let sincronizadosExitosamente = [];

    for (let i = 0; i < pendientes.length; i++) {
        let item = pendientes[i];
        try {
            // Convertir los datos a URLSearchParams para que PHP los reciba perfectamente por POST tradicional
            let urlEncodedData = new URLSearchParams(item.datos).toString();

            let respuesta = await fetch(item.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: urlEncodedData
            });
            if (respuesta.ok) {
                sincronizadosExitosamente.push(i);
            }
        } catch (error) {
            console.error("Fallo al sincronizar un elemento:", error);
        }
    }

    // Limpiar los elementos que ya subieron con éxito de la memoria local
    if (sincronizadosExitosamente.length > 0) {
        let nuevosPendientes = pendientes.filter((_, index) => !sincronizadosExitosamente.includes(index));
        localStorage.setItem('hielo_pendientes_sync', JSON.stringify(nuevosPendientes));
        
        if (nuevosPendientes.length === 0) {
            if (alerta) {
                alerta.style.background = "#4CAF50"; // Verde: todo OK
                alerta.innerHTML = `✅ ¡Sincronización completada con éxito!`;
                setTimeout(() => alerta.remove(), 4000);
            }
            location.reload(); // Recargar para ver los datos reflejados en la tabla
        }
    }
});