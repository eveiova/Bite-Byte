<?php
require_once 'config.php';

$logueado = isset($_SESSION['id']);

$pedido = null;
$items = [];
$buscado = false;
$num = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $num = trim($_POST['num_pedido'] ?? $_GET['id'] ?? '');
    $buscado = true;

    if ($num !== '' && ctype_digit($num)) {
        $stmt = $pdo->prepare("SELECT p.*, u.nombre, u.apellido FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ? LIMIT 1");
        $stmt->execute([(int)$num]);
        $pedido = $stmt->fetch();

        if ($pedido) {
            $stmt2 = $pdo->prepare("SELECT pi.cantidad, pi.precio_unitario, c.nombre, c.categoria FROM pedido_items pi JOIN catalogo c ON c.id = pi.catalogo_id WHERE pi.pedido_id = ?");
            $stmt2->execute([(int)$num]);
            $items = $stmt2->fetchAll();
        }
    }
}

// Definir los estados posibles de un pedido con sus configuraciones
$estadoPedidos = [
    0 => ['label' => 'Pendiente',  'desc' => 'Tu pedido ha sido recibido y está pendiente de revisión.',            'icon' => '⏳', 'color' => '#888',    'bg' => '#f5f5f5'],
    1 => ['label' => 'Aprobado',   'desc' => 'Tu pedido ha sido aprobado y pronto comenzará su fabricación.',        'icon' => '✅', 'color' => '#1e40af', 'bg' => '#dbeafe'],
    2 => ['label' => 'En proceso', 'desc' => 'Tu pedido está siendo fabricado en el laboratorio.',                   'icon' => '⚙️', 'color' => '#854d0e', 'bg' => '#fef9c3'],
    3 => ['label' => 'Completado', 'desc' => '¡Tu pedido está listo! Contacta con el laboratorio para la entrega.', 'icon' => '🏁', 'color' => '#166534', 'bg' => '#dcfce7'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>Casa Denise - Laboratorio Dental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header>
<nav class="navbar navbar-expand-md p-3 navbar-color-vino shadow-sm">
    <div class="container">
        <a href="index.php" class="navbar-brand text-decoration-none">
            <div class="brand-name">Casa Denise</div>
            <div class="brand-sub">Laboratorio Dental</div>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#menu"
                style="border-color:#d4a5b0;">
            <span class="text-light-pink" style="font-size:1.3rem;">&#9776;</span>
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

<div class="container">
    <div class="jumbo-container">
        <div class="row align-items-center">
            <div class="col-lg-4 mb-4 mb-lg-0 text-center text-lg-start">
                <div class="jumbo-header">
                    <h1 class="texto-bordeo display-5 fw-bold">Excelencia Dental</h1>
                    <p class="lead">Precisión artesanal y tecnología de vanguardia en cada pieza.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start mt-3">
                    <?php if ($logueado): ?>
                        <?php $panel = $_SESSION['rol'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_cliente.php'; ?>
                        <a href="<?= $panel ?>" class="btn btn-bordeo btn-lg px-4">Mi panel</a>
                    <?php else: ?>
                        <a href="login.php"    class="btn btn-bordeo btn-lg px-4">Iniciar sesión</a>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4" style="border-color:#d4a5b0; color:#e8c9d0;">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-8">
                <div id="jumboCarousel" class="carousel slide carousel-jumbo" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="img/ZIRCONIO/zirconio1.png" class="d-block w-100" alt="Trabajo 1">
                        </div>
                        <div class="carousel-item">
                            <img src="img/CERÁMICA ESTRATIFICADA/ceramica1.png" class="d-block w-100" alt="Trabajo 2">
                        </div>
                        <div class="carousel-item">
                            <img src="img/CERÁMICA ESTRATIFICADA/ceramica3.png" class="d-block w-100" alt="Trabajo 3">
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#jumboCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#jumboCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <div class="row g-4 justify-content-center">
        <div class="col-md-5">
            <a href="trabajos.php" class="text-decoration-none">
                <div class="card card-custom p-4 h-100">
                    <img src="img/carusel2.png" alt="Trabajos" class="mb-3 rounded">
                    <h3 class="texto-bordeo">Nuestros Trabajos</h3>
                    <p class="text-muted">Galería detallada de nuestras prótesis y terminaciones.</p>
                    <span class="btn btn-outline-danger">Explorar Galería</span>
                </div>
            </a>
        </div>
        <div class="col-md-5">
            <a href="productos.php" class="text-decoration-none">
                <div class="card card-custom p-4 h-100">
                    <img src="img/porcelana.png" alt="Productos" class="mb-3 rounded">
                    <h3 class="texto-bordeo">Productos</h3>
                    <p class="text-muted">Materiales e insumos de alta calidad para laboratorios.</p>
                    <span class="btn btn-outline-danger">Ir a la Tienda</span>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Sección para consultar el estado de un pedido -->
<div class="container mb-5">
    <div class="buscar-section">
        <div class="row align-items-start g-4">

            <div class="col-lg-5">
                <div class="buscar-titulo">&#128269; Consultar pedido</div>
                <p class="text-muted mb-3" style="font-size:0.88rem;">
                    Introduce tu número de pedido para ver en qué estado se encuentra.
                </p>
                <form method="POST" action="index.php#resultado">
                    <div class="search-box">
                        <input type="text" name="num_pedido" class="search-input"
                            placeholder="Ej: 1042"
                            value="<?= htmlspecialchars($num) ?>"
                            inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                        <button type="submit" class="search-btn">Buscar</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-7" id="resultado">

                <?php if (!$buscado): ?>
                    <div style="text-align:center; padding:1.5rem; color:#c0a8b0;">
                        <div style="font-size:3rem; margin-bottom:0.5rem;">📦</div>
                        <p style="font-size:0.88rem;">El resultado aparecerá aquí.</p>
                    </div>

                <?php elseif (!$pedido || !ctype_digit($num)): ?>
                    <div class="not-found">
                        <div class="icon">🔎</div>
                        <h6 class="texto-bordeo mb-1">Pedido no encontrado</h6>
                        <p style="font-size:0.85rem;">
                            No existe el pedido <strong>#<?= htmlspecialchars($num) ?></strong>.<br>
                            Revisa el número e inténtalo de nuevo.
                        </p>
                    </div>

                <?php else:
                    $est = $estadoPedidos[$pedido['estado']];
                ?>
                    <div class="resultado-card">
                        <div class="resultado-header">
                            <div>
                                <div class="num-pedido">Pedido #<?= $pedido['id'] ?></div>
                                <div style="color:#d4a5b0; font-size:0.78rem;">
                                    <?= htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']) ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="header-pedido">REALIZADO EL</div>
                                <div class="cuerpo-pedido"><?= date('d/m/Y', strtotime($pedido['created_at'])) ?></div>
                                <div class="footer-pedido">Actualizado: <?= date('d/m/Y H:i', strtotime($pedido['updated_at'])) ?></div>
                            </div>
                        </div>

                        <div class="resultado-body">
                            <div class="text-center mb-2">
                                <div class="estado-badge-grande" style="background:<?= $est['bg'] ?>; color:<?= $est['color'] ?>;">
                                    <span style="font-size:1.3rem;"><?= $est['icon'] ?></span>
                                    <?= $est['label'] ?>
                                </div>
                                <p class="text-muted mt-1 mb-0" style="font-size:0.82rem;"><?= $est['desc'] ?></p>
                            </div>

                            <div class="timeline">
                                <?php foreach ($estadoPedidos as $val => $cfg): ?>
                                <?php $dot = $val < $pedido['estado'] ? 'completado' : ($val == $pedido['estado'] ? 'activo' : ''); ?>
                                <div class="timeline-step">
                                    <div class="timeline-dot <?= $dot ?>" style="--step-color:<?= $cfg['color'] ?>; --step-bg:<?= $cfg['bg'] ?>;">
                                        <?= $val < $pedido['estado'] ? '✓' : $cfg['icon'] ?>
                                    </div>
                                    <div class="timeline-label <?= $dot ?>" style="--step-color:<?= $cfg['color'] ?>;">
                                        <?= $cfg['label'] ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($items)): ?>
                            <p class="detalle-pedido-title">Detalle del pedido</p>
                            <table class="table tabla-items mb-2">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['nombre']) ?></td>
                                        <td class="categoria"><?= htmlspecialchars($item['categoria']) ?></td>
                                        <td class="text-center"><?= $item['cantidad'] ?></td>
                                        <td class="text-end"><?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?> €</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold total" >Total:</td>
                                        <td class="text-end fw-bold total-number"><?= number_format($pedido['total'], 2) ?> €</td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php endif; ?>

                            <?php if (!empty($pedido['notas'])): ?>
                            <div class="nota-lab">
                                <strong>Nota del laboratorio:</strong> <?= htmlspecialchars($pedido['notas']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="buscar-section text-center" style="padding: 2rem;">
        <h3 class="texto-bordeo mb-3">Encuéntranos</h3>
        <p class="text-muted mb-4">📍 C/Benito Pérez Galdós 11</p>
        <div class="map-container">
            <iframe width="100%" height="400" frameborder="0" scrolling="no"
                src="https://maps.google.com/maps?q=40.424488312165046,-3.564736952675174&hl=es&z=17&output=embed">
            </iframe>
        </div>
    </div>
</div>

<footer class="footer-vino shadow-sm">
    <div class="container footer-container">
        <a href="index.php" class="footer-brand">Casa Denise</a>
        <p class="footer-text">© 2026 Laboratorio Dental - Todos los derechos reservados</p>
        <div class="footer-text">
            <span>C/Benito Pérez Galdós 11</span>
            <span class="ms-3">&#128222; +34 1234-5678</span>
        </div>
    </div>
</footer>
<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
