<?php
// ============================================================
//  CASA DENISE - Página principal + buscador de pedidos
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

$logueado = estaLogueado();
$usuario  = $logueado ? usuarioActual() : null;

// ── Lógica del buscador de pedidos ───────────────────────────
$pedido     = null;
$items      = [];
$buscado    = false;
$num_pedido = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $num_pedido = trim($_POST['num_pedido'] ?? $_GET['id'] ?? '');
    $buscado    = true;

    if ($num_pedido !== '' && ctype_digit($num_pedido)) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.total, p.estado, p.notas,
                   p.created_at, p.updated_at,
                   u.nombre, u.apellido
            FROM pedidos p
            JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$num_pedido]);
        $pedido = $stmt->fetch();

        if ($pedido) {
            $stmt2 = $pdo->prepare("
                SELECT pi.cantidad, pi.precio_unitario,
                       c.nombre, c.categoria
                FROM pedido_items pi
                JOIN catalogo c ON c.id = pi.catalogo_id
                WHERE pi.pedido_id = ?
            ");
            $stmt2->execute([(int)$num_pedido]);
            $items = $stmt2->fetchAll();
        }
    }
}

$estados = [
    0 => ['label' => 'Pendiente',  'desc' => 'Tu pedido ha sido recibido y está pendiente de revisión.',              'icon' => '⏳', 'color' => '#888',    'bg' => '#f5f5f5'],
    1 => ['label' => 'Aprobado',   'desc' => 'Tu pedido ha sido aprobado y pronto comenzará su fabricación.',          'icon' => '✅', 'color' => '#1e40af', 'bg' => '#dbeafe'],
    2 => ['label' => 'En proceso', 'desc' => 'Tu pedido está siendo fabricado en el laboratorio.',                     'icon' => '⚙️', 'color' => '#854d0e', 'bg' => '#fef9c3'],
    3 => ['label' => 'Completado', 'desc' => '¡Tu pedido está listo! Contacta con el laboratorio para la entrega.',   'icon' => '🏁', 'color' => '#166534', 'bg' => '#dcfce7'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casa Denise - Laboratorio Dental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* ── Sección buscador ── */
        .buscar-section {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(128,0,32,0.08);
            padding: 2.5rem 2rem;
            margin-top: 2.5rem;
        }

        .buscar-titulo {
            font-family: "Playfair Display", serif;
            color: #800020;
            font-size: 1.6rem;
            margin-bottom: 0.3rem;
        }

        /* Barra de búsqueda */
        .search-box {
            display: flex;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(128,0,32,0.1);
            max-width: 500px;
        }

        .search-input {
            flex: 1;
            border: 2px solid #d4a5b0;
            border-right: none;
            border-radius: 14px 0 0 14px;
            padding: 0.8rem 1.2rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 1rem;
            color: #5a4b3b;
            background: #fdf8f2;
            letter-spacing: 2px;
            transition: border-color 0.2s;
        }
        .search-input:focus    { outline: none; border-color: #800020; background: #fff; }
        .search-input::placeholder { color: #c0a8b0; letter-spacing: 1px; font-size: 0.9rem; }

        .search-btn {
            background: linear-gradient(135deg, #800020 0%, #5b0e1f 100%);
            color: white;
            border: none;
            padding: 0 1.5rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 0.82rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
            border-radius: 0 14px 14px 0;
            cursor: pointer;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .search-btn:hover { opacity: 0.88; color: #f5d98b; }

        /* Resultado */
        .resultado-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 25px rgba(128,0,32,0.09);
            overflow: hidden;
            margin-top: 1.8rem;
            border: 1px solid #f0e6ec;
        }

        .resultado-header {
            background: linear-gradient(135deg, #5b0e1f 0%, #3b0a14 100%);
            padding: 1.2rem 1.6rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #f5d98b;
        }
        .resultado-header .num-pedido {
            font-family: "Playfair Display", serif;
            color: #f5d98b;
            font-size: 1.4rem;
        }
        .resultado-body { padding: 1.5rem 1.6rem; }

        /* Timeline */
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 1.2rem 0 1.5rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 21px; left: 12%; right: 12%;
            height: 3px;
            background: #ead8df;
            z-index: 0;
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .timeline-dot {
            width: 42px; height: 42px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            border: 3px solid #ead8df;
            background: #f6f0e1;
            transition: all 0.3s;
        }
        .timeline-dot.activo {
            border-color: var(--step-color);
            background: var(--step-bg);
            box-shadow: 0 0 0 5px var(--step-bg);
        }
        .timeline-dot.completado { border-color: #198754; background: #dcfce7; }
        .timeline-label {
            font-size: 0.68rem; letter-spacing: 0.5px;
            text-transform: uppercase; color: #a07a8a; text-align: center;
        }
        .timeline-label.activo    { color: var(--step-color); font-weight: 700; }
        .timeline-label.completado { color: #198754; }

        /* Badge estado */
        .estado-badge-grande {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.6rem 1.2rem; border-radius: 30px;
            font-size: 0.95rem; font-weight: 700;
        }

        /* Tabla items */
        .tabla-items th {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px;
            color: #a07a8a; border-bottom: 2px solid #ead8df;
            padding: 0.4rem 0.6rem; font-weight: 600;
        }
        .tabla-items td {
            font-size: 0.85rem; color: #5a4b3b;
            padding: 0.5rem 0.6rem; border-color: #f0e6ec;
        }

        /* No encontrado */
        .not-found {
            text-align: center; padding: 2rem 1rem;
            color: #a07a8a; margin-top: 1.5rem;
        }
        .not-found .icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="p-3 navbar-vino shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
        </a>
        <ul class="nav d-none d-md-flex">
            <li><a href="index.php"        class="nav-link nav-link-vino">Inicio</a></li>
            <li><a href="trabajos.php"     class="nav-link nav-link-vino">Trabajos</a></li>
            <li><a href="productos.php"    class="nav-link nav-link-vino">Productos</a></li>
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

<!-- JUMBOTRON -->
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
                        <?php $panel = $usuario['rol'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_cliente.php'; ?>
                        <a href="<?= $panel ?>" class="btn btn-bordeo btn-lg px-4">Mi panel</a>
                    <?php else: ?>
                        <a href="login.php"    class="btn btn-bordeo btn-lg px-4">Iniciar sesión</a>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4"
                           style="border-color:#d4a5b0; color:#e8c9d0;">Registrarse</a>
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

<!-- CARDS -->
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

<!-- BUSCADOR DE PEDIDOS -->
<div class="container mb-5">
    <div class="buscar-section">

        <div class="row align-items-start g-4">

            <!-- Lado izquierdo: título + formulario -->
            <div class="col-lg-5">
                <div class="buscar-titulo">🔍 Consultar pedido</div>
                <p class="text-muted mb-3" style="font-size:0.88rem;">
                    Introduce tu número de pedido para ver en qué estado se encuentra.
                </p>
                <form method="POST" action="index.php<?= $buscado && $pedido ? '#resultado' : '' ?>">
                    <div class="search-box">
                        <input
                            type="text"
                            name="num_pedido"
                            class="search-input"
                            placeholder="Ej: 1042"
                            value="<?= htmlspecialchars($num_pedido) ?>"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            autocomplete="off"
                        >
                        <button type="submit" class="search-btn">Buscar</button>
                    </div>
                </form>
            </div>

            <!-- Lado derecho: resultado -->
            <div class="col-lg-7" id="resultado">

                <?php if (!$buscado): ?>
                    <!-- Estado inicial -->
                    <div style="text-align:center; padding:1.5rem; color:#c0a8b0;">
                        <div style="font-size:3rem; margin-bottom:0.5rem;">📦</div>
                        <p style="font-size:0.88rem;">El resultado aparecerá aquí.</p>
                    </div>

                <?php elseif (!$pedido || !ctype_digit($num_pedido)): ?>
                    <!-- No encontrado -->
                    <div class="not-found">
                        <div class="icon">🔎</div>
                        <h6 class="texto-bordeo mb-1">Pedido no encontrado</h6>
                        <p style="font-size:0.85rem;">
                            No existe el pedido <strong>#<?= htmlspecialchars($num_pedido) ?></strong>.<br>
                            Revisa el número e inténtalo de nuevo.
                        </p>
                    </div>

                <?php else:
                    $est = $estados[$pedido['estado']];
                ?>
                    <!-- Resultado -->
                    <div class="resultado-card">
                        <div class="resultado-header">
                            <div>
                                <div class="num-pedido">Pedido #<?= $pedido['id'] ?></div>
                                <div style="color:#d4a5b0; font-size:0.78rem;">
                                    <?= htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']) ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div style="color:#f5d98b; font-size:0.72rem; letter-spacing:1px;">REALIZADO EL</div>
                                <div style="color:#e8c9d0; font-size:0.88rem;">
                                    <?= date('d/m/Y', strtotime($pedido['created_at'])) ?>
                                </div>
                                <div style="color:#d4a5b0; font-size:0.72rem;">
                                    Actualizado: <?= date('d/m/Y H:i', strtotime($pedido['updated_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <div class="resultado-body">
                            <!-- Estado badge -->
                            <div class="text-center mb-2">
                                <div class="estado-badge-grande"
                                     style="background:<?= $est['bg'] ?>; color:<?= $est['color'] ?>;">
                                    <span style="font-size:1.3rem;"><?= $est['icon'] ?></span>
                                    <?= $est['label'] ?>
                                </div>
                                <p class="text-muted mt-1 mb-0" style="font-size:0.82rem;">
                                    <?= $est['desc'] ?>
                                </p>
                            </div>

                            <!-- Timeline -->
                            <div class="timeline">
                                <?php foreach ($estados as $val => $cfg): ?>
                                <?php
                                    $cdot = $val < $pedido['estado'] ? 'completado' : ($val == $pedido['estado'] ? 'activo' : '');
                                    $clbl = $cdot;
                                ?>
                                <div class="timeline-step">
                                    <div class="timeline-dot <?= $cdot ?>"
                                         style="--step-color:<?= $cfg['color'] ?>; --step-bg:<?= $cfg['bg'] ?>;">
                                        <?= $val < $pedido['estado'] ? '✓' : $cfg['icon'] ?>
                                    </div>
                                    <div class="timeline-label <?= $clbl ?>"
                                         style="--step-color:<?= $cfg['color'] ?>;">
                                        <?= $cfg['label'] ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Artículos -->
                            <?php if (!empty($items)): ?>
                            <p style="font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:#a07a8a; margin-bottom:0.3rem;">
                                Detalle del pedido
                            </p>
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
                                        <td style="color:#a07a8a;"><?= htmlspecialchars($item['categoria']) ?></td>
                                        <td class="text-center"><?= $item['cantidad'] ?></td>
                                        <td class="text-end"><?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?> €</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold" style="border-top:2px solid #ead8df;">Total:</td>
                                        <td class="text-end fw-bold" style="border-top:2px solid #ead8df; color:#800020;">
                                            <?= number_format($pedido['total'], 2) ?> €
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php endif; ?>

                            <!-- Nota -->
                            <?php if (!empty($pedido['notas'])): ?>
                            <div style="background:#fdf8f2; border-left:4px solid #d4a5b0; border-radius:8px; padding:0.6rem 0.9rem; font-size:0.82rem; color:#7a6a5a;">
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
        <h3 class="texto-bordeo mb-3" style="font-family: 'Playfair Display', serif;">Encuéntranos</h3>
        <p class="text-muted mb-4" style="font-size:0.95rem;">
            📍 Estamos en: C/Benito Pérez Galdós
        </p>
        
        <div style="width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid #f0e6ec;">
            <iframe 
                width="100%" 
                height="400" 
                frameborder="0" 
                scrolling="no" 
                marginheight="0" 
                marginwidth="0" 
                src="https://maps.google.com/maps?q=40.424488312165046,-3.564736952675174&hl=es&z=17&amp;output=embed">
            </iframe>
        </div>
    </div>
</div>
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
