<?php
// ============================================================
//  CASA DENISE - Conexión a la base de datos
//  Coloca este archivo en: tu-proyecto/includes/db.php
// ============================================================

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
