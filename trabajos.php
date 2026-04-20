<?php
// ============================================================
//  CASA DENISE - Trabajos realizados
//  Renombra trabajos.php a trabajos.php (o bórralo y usa este)
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

$logueado = estaLogueado();
$usuario  = $logueado ? usuarioActual() : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabajos - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="p-3 navbar-vino shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
        </a>
        <ul class="nav d-none d-md-flex">
            <li><a href="index.php"         class="nav-link nav-link-vino">Inicio</a></li>
            <li><a href="trabajos.php"       class="nav-link nav-link-vino fw-bold" style="color:#f5d98b !important;">Trabajos</a></li>
            <li><a href="productos.php"      class="nav-link nav-link-vino">Productos</a></li>
            <?php if ($logueado): ?>
                <?php if ($usuario['rol'] === 'admin'): ?>
                    <li><a href="dashboard_admin.php"   class="nav-link nav-link-vino">Panel Admin</a></li>
                <?php else: ?>
                    <li><a href="dashboard_cliente.php" class="nav-link nav-link-vino">Mi cuenta</a></li>
                <?php endif; ?>
                <li>
                    <a href="logout.php" class="nav-link nav-link-vino">
                        Salir (<?= htmlspecialchars($usuario['nombre']) ?>)
                    </a>
                </li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link nav-link-vino">Iniciar sesión</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<main class="container mt-5 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php" class="text-decoration-none" style="color:#800020;">Inicio</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Trabajos Realizados</li>
        </ol>
    </nav>

    <div class="text-center mb-5">
        <h2 class="texto-bordeo display-4">Nuestra Galería</h2>
        <p class="lead">Calidad y detalle en cada prótesis dental.</p>
        <hr class="mx-auto" style="width:60px; border:2px solid #800020; opacity:1;">
    </div>

    <!-- Aquí irá el contenido de la galería -->

</main>

<footer class="footer-vino shadow-sm">
    <div class="container footer-container">
        <a href="index.php" class="footer-brand">Casa Denise</a>
        <p class="footer-text">© 2024 Laboratorio Dental - Todos los derechos reservados</p>
        <div class="footer-text">
            <span>📍 Calle Dental 123</span>
            <span class="ms-3">📞 +54 11 1234-5678</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
