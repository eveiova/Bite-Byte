<?php
// ============================================================
//  CASA DENISE - Logout
//  Coloca este archivo en: tu-proyecto/logout.php
// ============================================================

require_once 'includes/auth.php';

// Destruir todos los datos de sesión
$_SESSION = [];

// Borrar la cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
