<?php
// ============================================================
//  CASA DENISE - Guard de autenticación
//  Coloca este archivo en: tu-proyecto/includes/auth.php
//
//  USO en cualquier página protegida:
//      require_once 'includes/auth.php';           // cualquier usuario logueado
//      require_once 'includes/auth.php'; requireAdmin(); // solo admins
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve true si hay sesión activa.
 */
function estaLogueado(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Redirige a login si no hay sesión. Guarda la URL de origen para volver.
 */
function requireLogin(): void {
    if (!estaLogueado()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }
}

/**
 * Redirige si el usuario no es admin.
 */
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['usuario_rol'] !== 'admin') {
        header('Location: /dashboard_cliente.php');
        exit;
    }
}

/**
 * Datos del usuario en sesión (acceso rápido en las vistas).
 */
function usuarioActual(): array {
    return [
        'id'     => $_SESSION['usuario_id']     ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? '',
        'email'  => $_SESSION['usuario_email']  ?? '',
        'rol'    => $_SESSION['usuario_rol']    ?? '',
    ];
}
