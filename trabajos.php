<?php
require_once 'config.php';

$logueado = isset($_SESSION['id']);
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
    
<header>
<nav class="navbar navbar-expand-md p-3 navbar-vino shadow-sm">
    <div class="container">
        <a href="index.php" class="navbar-brand text-decoration-none">
            <div class="brand-name">Casa Denise</div>
            <div class="brand-sub">Laboratorio Dental</div>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#menu"
                style="border-color:#d4a5b0;">
            <span style="color:#e8c9d0; font-size:1.3rem;">☰</span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a href="index.php"     class="nav-link nav-link-vino">Inicio</a></li>
                <li class="nav-item"><a href="trabajos.php"  class="nav-link nav-link-vino">Trabajos</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link nav-link-vino">Productos</a></li>
                <?php if ($logueado): ?>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li class="nav-item"><a href="dashboard_admin.php" class="nav-link nav-link-vino">Panel Admin</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="dashboard_cliente.php" class="nav-link nav-link-vino">Mi cuenta</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="logout.php" class="nav-link nav-link-vino">Salir (<?= htmlspecialchars($_SESSION['nombre']) ?>)</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link nav-link-vino">Iniciar sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
</header>

<main class="container mt-5 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:#800020;">Inicio</a></li>
            <li class="breadcrumb-item active">Trabajos Realizados</li>
        </ol>
    </nav>

    <div class="text-center mb-5">
        <h2 class="texto-bordeo display-4">Nuestra Galería</h2>
        <p class="lead">Calidad y detalle en cada prótesis dental.</p>
        <hr class="mx-auto" style="width:60px; border:2px solid #800020; opacity:1;">
    </div>

</main>

<footer class="footer-vino shadow-sm">
    <div class="container footer-container">
        <a href="index.php" class="footer-brand">Casa Denise</a>
        <p class="footer-text">© 2026 Laboratorio Dental - Todos los derechos reservados</p>
        <div class="footer-text">
            <span>📍 C/Benito Pérez Galdós 11</span>
            <span class="ms-3">📞 +34 1234-5678</span>
        </div>
    </div>
</footer>
<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
