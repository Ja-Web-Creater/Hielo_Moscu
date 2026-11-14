<?php
// =========================================================================
// MÓDULO CENTRAL DE SEGURIDAD Y PROTOCOLOS HTTP - HIELO MOSCÚ
// =========================================================================

// 1. Ocultar versión de PHP en cabeceras HTTP
@ini_set('expose_php', 'Off');

// 2. Configuración estricta de sesiones (Protección en Wi-Fi y Redes Móviles)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1); // Evita robo de sesión vía JavaScript (XSS)
    ini_set('session.cookie_samesite', 'Lax'); // Protege contra peticiones cruzadas

    // Activar cookie segura automáticamente si el sitio corre bajo HTTPS en producción
    $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    if ($es_https) {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

// 3. Cabeceras de Seguridad HTTP (Defensa a nivel navegador)
header("X-Frame-Options: SAMEORIGIN"); // Evita Clickjacking (que incrusten tu web en iframes falsos)
header("X-Content-Type-Options: nosniff"); // Evita ataques de MIME-sniffing
header("X-XSS-Protection: 1; mode=block"); // Filtro XSS adicional para navegadores móviles
header("Referrer-Policy: strict-origin-when-cross-origin"); // Protege datos en URLs al salir a Google Maps
header("Permissions-Policy: geolocation=(self), microphone=(), camera=()"); // Solo permite geolocalización en tu propio dominio

// 4. Generación y Validación de Tokens CSRF (Protección de Formularios)
function generar_token_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function campo_csrf() {
    $token = generar_token_csrf();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function validar_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token_recibido = $_POST['csrf_token'] ?? '';
        $token_sesion = $_SESSION['csrf_token'] ?? '';
        if (empty($token_recibido) || empty($token_sesion) || !hash_equals($token_sesion, $token_recibido)) {
            http_response_code(403);
            die("⛔ Error de seguridad: Solicitud no autorizada o sesión expirada. Recarga la página e intenta de nuevo.");
        }
    }
}

// 5. Función auxiliar para escapar salidas HTML de forma segura (Anti-XSS)
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}