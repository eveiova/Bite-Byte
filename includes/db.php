<?php
// ============================================================
//  CASA DENISE - Conexión a la base de datos
//  Coloca este archivo en: tu-proyecto/includes/db.php
// ============================================================

// ── Configuración de sesión persistente ─────────────────────
// La sesión dura 8 horas de inactividad
$session_lifetime = 60 * 60 * 8;
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);
ini_set('session.cookie_httponly', 1);   // La cookie no es accesible por JS
ini_set('session.use_strict_mode', 1);   // Rechaza IDs de sesión no iniciados

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Renovar la cookie en cada visita para que el contador se reinicie
// solo si han pasado más de 10 minutos desde la última renovación
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > 600) {
        session_regenerate_id(false);
        setcookie(session_name(), session_id(), time() + $session_lifetime, '/');
    }
}
$_SESSION['last_activity'] = time();
// ─────────────────────────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_NAME', 'casadenise');
define('DB_USER', 'root');       // Usuario por defecto en XAMPP
define('DB_PASS', '');           // Contraseña vacía por defecto en XAMPP
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
} catch (PDOException $e) {
    // En producción nunca mostrar el error real
    error_log("Error de conexión: " . $e->getMessage());
    die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
}
