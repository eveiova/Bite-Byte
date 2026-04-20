<?php
// ============================================================
//  CASA DENISE - Buscador de pedidos
//  Coloca este archivo en: tu-proyecto/buscar_pedido.php
//  Acceso público — no requiere login
// ============================================================

require_once 'includes/db.php';

$pedido      = null;
$items       = [];
$buscado     = false;
$num_pedido  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {

    $num_pedido = trim($_POST['num_pedido'] ?? $_GET['id'] ?? '');
    $buscado    = true;

    if ($num_pedido !== '' && ctype_digit($num_pedido)) {

        // Buscar pedido con datos del cliente
        $stmt = $pdo->prepare("
            SELECT
                p.id,
                p.total,
                p.estado,
                p.notas,
                p.created_at,
                p.updated_at,
                u.nombre,
                u.apellido
            FROM pedidos p
            JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$num_pedido]);
        $pedido = $stmt->fetch();

        // Si existe, cargar sus artículos
        if ($pedido) {
            $stmt2 = $pdo->prepare("
                SELECT
                    pi.cantidad,
                    pi.precio_unitario,
                    c.nombre,
                    c.categoria
                FROM pedido_items pi
                JOIN catalogo c ON c.id = pi.catalogo_id
                WHERE pi.pedido_id = ?
            ");
            $stmt2->execute([(int)$num_pedido]);
            $items = $stmt2->fetchAll();
        }
    }
}

// Configuración de estados
$estados = [
    0 => [
        'label'   => 'Pendiente',
        'desc'    => 'Tu pedido ha sido recibido y está pendiente de revisión.',
        'icon'    => '⏳',
        'color'   => '#888',
        'bg'      => '#f5f5f5',
    ],
    1 => [
        'label'   => 'Aprobado',
        'desc'    => 'Tu pedido ha sido aprobado y pronto comenzará su fabricación.',
        'icon'    => '✅',
        'color'   => '#1e40af',
        'bg'      => '#dbeafe',
    ],
    2 => [
        'label'   => 'En proceso',
        'desc'    => 'Tu pedido está siendo fabricado en el laboratorio.',
        'icon'    => '⚙️',
        'color'   => '#854d0e',
        'bg'      => '#fef9c3',
    ],
    3 => [
        'label'   => 'Completado',
        'desc'    => '¡Tu pedido está listo! Contacta con el laboratorio para la entrega.',
        'icon'    => '🏁',
        'color'   => '#166534',
        'bg'      => '#dcfce7',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Pedido - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* ── Barra de búsqueda ── */
        .search-wrapper {
            max-width: 560px;
            margin: 0 auto;
        }

        .search-box {
            display: flex;
            gap: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(128,0,32,0.12);
        }

        .search-input {
            flex: 1;
            border: 2px solid #d4a5b0;
            border-right: none;
            border-radius: 14px 0 0 14px;
            padding: 0.9rem 1.4rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 1.1rem;
            color: #5a4b3b;
            background: #fff;
            letter-spacing: 2px;
            transition: border-color 0.2s;
        }
        .search-input:focus {
            outline: none;
            border-color: #800020;
        }
        .search-input::placeholder {
            color: #c0a8b0;
            letter-spacing: 1px;
            font-size: 0.95rem;
        }

        .search-btn {
            background: linear-gradient(135deg, #800020 0%, #5b0e1f 100%);
            color: white;
            border: none;
            padding: 0 1.8rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 0.85rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
            border-radius: 0 14px 14px 0;
            cursor: pointer;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .search-btn:hover { opacity: 0.88; color: #f5d98b; }

        /* ── Resultado ── */
        .resultado-card {
            max-width: 620px;
            margin: 2.5rem auto 0;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(128,0,32,0.09);
            overflow: hidden;
        }

        .resultado-header {
            background: linear-gradient(135deg, #5b0e1f 0%, #3b0a14 100%);
            padding: 1.4rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #f5d98b;
        }

        .resultado-header .num-pedido {
            font-family: "Playfair Display", serif;
            color: #f5d98b;
            font-size: 1.5rem;
        }

        .resultado-header .fecha {
            color: #d4a5b0;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .resultado-body { padding: 1.8rem 2rem; }

        /* ── Timeline de estados ── */
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 1.5rem 0 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 12%;
            right: 12%;
            height: 3px;
            background: #ead8df;
            z-index: 0;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .timeline-dot {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border: 3px solid #ead8df;
            background: #f6f0e1;
            transition: all 0.3s;
        }

        .timeline-dot.activo {
            border-color: var(--step-color);
            background: var(--step-bg);
            box-shadow: 0 0 0 5px var(--step-bg);
        }

        .timeline-dot.completado {
            border-color: #198754;
            background: #dcfce7;
        }

        .timeline-label {
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #a07a8a;
            text-align: center;
        }

        .timeline-label.activo { color: var(--step-color); font-weight: 700; }
        .timeline-label.completado { color: #198754; }

        /* ── Estado grande ── */
        .estado-badge-grande {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.7rem 1.4rem;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* ── Tabla de artículos ── */
        .tabla-items th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a07a8a;
            border-bottom: 2px solid #ead8df;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
        }
        .tabla-items td {
            font-size: 0.88rem;
            color: #5a4b3b;
            padding: 0.6rem 0.75rem;
            border-color: #f0e6ec;
        }

        /* ── No encontrado ── */
        .not-found {
            max-width: 480px;
            margin: 2.5rem auto 0;
            text-align: center;
            background: #fff;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 10px 40px rgba(128,0,32,0.08);
        }
        .not-found .icon { font-size: 3rem; margin-bottom: 1rem; }

        /* ── Sección intro ── */
        .intro-box {
            max-width: 480px;
            margin: 0 auto 2.5rem;
            text-align: center;
        }
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
            <li><a href="index.php"     class="nav-link nav-link-vino">Inicio</a></li>
            <li><a href="trabajos.php"  class="nav-link nav-link-vino">Trabajos</a></li>
            <li><a href="productos.php" class="nav-link nav-link-vino">Productos</a></li>
            <li><a href="login.php"      class="nav-link nav-link-vino">Mi cuenta</a></li>
        </ul>
    </div>
</header>

<main class="container mt-5 mb-5">

    <!-- Título -->
    <div class="intro-box">
        <h2 class="texto-bordeo display-5 mb-2">Consultar Pedido</h2>
        <p class="text-muted">
            Introduce tu número de pedido para ver en qué estado se encuentra tu trabajo.
        </p>
        <hr style="width:60px; border:2px solid #800020; opacity:1; margin:1.2rem auto;">
    </div>

    <!-- Barra de búsqueda -->
    <div class="search-wrapper">
        <form method="POST" action="buscar_pedido.php">
            <div class="search-box">
                <input
                    type="text"
                    name="num_pedido"
                    class="search-input"
                    placeholder="Ej: 1042"
                    value="<?= htmlspecialchars($num_pedido) ?>"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autofocus
                    autocomplete="off"
                >
                <button type="submit" class="search-btn">
                    🔍 Buscar
                </button>
            </div>
        </form>
    </div>

    <?php if ($buscado): ?>

        <?php if (!$pedido || !ctype_digit($num_pedido)): ?>
        <!-- ── No encontrado ── -->
        <div class="not-found">
            <div class="icon">🔎</div>
            <h5 class="texto-bordeo mb-2">Pedido no encontrado</h5>
            <p class="text-muted" style="font-size:0.9rem;">
                No existe ningún pedido con el número
                <strong>#<?= htmlspecialchars($num_pedido) ?></strong>.<br>
                Revisa el número y vuelve a intentarlo.
            </p>
        </div>

        <?php else:
            $estado_cfg = $estados[$pedido['estado']];
        ?>
        <!-- ── Resultado ── -->
        <div class="resultado-card">

            <!-- Cabecera del resultado -->
            <div class="resultado-header">
                <div>
                    <div class="num-pedido">Pedido #<?= $pedido['id'] ?></div>
                    <div class="fecha">
                        <?= htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']) ?>
                    </div>
                </div>
                <div class="text-end">
                    <div style="color:#f5d98b; font-size:0.75rem; letter-spacing:1px;">REALIZADO EL</div>
                    <div style="color:#e8c9d0; font-size:0.9rem;">
                        <?= date('d/m/Y', strtotime($pedido['created_at'])) ?>
                    </div>
                    <div style="color:#d4a5b0; font-size:0.75rem;">
                        Actualizado: <?= date('d/m/Y H:i', strtotime($pedido['updated_at'])) ?>
                    </div>
                </div>
            </div>

            <div class="resultado-body">

                <!-- Estado actual grande -->
                <div class="text-center mb-3">
                    <div
                        class="estado-badge-grande"
                        style="background:<?= $estado_cfg['bg'] ?>; color:<?= $estado_cfg['color'] ?>;"
                    >
                        <span style="font-size:1.4rem;"><?= $estado_cfg['icon'] ?></span>
                        <?= $estado_cfg['label'] ?>
                    </div>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.88rem;">
                        <?= $estado_cfg['desc'] ?>
                    </p>
                </div>

                <!-- Timeline -->
                <div class="timeline">
                    <?php foreach ($estados as $val => $cfg): ?>
                    <?php
                        $clase_dot   = '';
                        $clase_label = '';
                        if ($val < $pedido['estado']) {
                            $clase_dot   = 'completado';
                            $clase_label = 'completado';
                        } elseif ($val == $pedido['estado']) {
                            $clase_dot   = 'activo';
                            $clase_label = 'activo';
                        }
                    ?>
                    <div class="timeline-step">
                        <div
                            class="timeline-dot <?= $clase_dot ?>"
                            style="
                                --step-color: <?= $cfg['color'] ?>;
                                --step-bg:    <?= $cfg['bg'] ?>;
                            "
                        >
                            <?= $val < $pedido['estado'] ? '✓' : $cfg['icon'] ?>
                        </div>
                        <div class="timeline-label <?= $clase_label ?>"
                             style="--step-color: <?= $cfg['color'] ?>">
                            <?= $cfg['label'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Artículos del pedido -->
                <?php if (!empty($items)): ?>
                <div class="mt-2">
                    <p class="mb-2" style="font-size:0.78rem; letter-spacing:1px; text-transform:uppercase; color:#a07a8a;">
                        Detalle del pedido
                    </p>
                    <table class="table tabla-items mb-2">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nombre']) ?></td>
                                <td style="color:#a07a8a;"><?= htmlspecialchars($item['categoria']) ?></td>
                                <td class="text-center"><?= $item['cantidad'] ?></td>
                                <td class="text-end">
                                    <?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?> €
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold" style="border-top:2px solid #ead8df;">
                                    Total:
                                </td>
                                <td class="text-end fw-bold" style="border-top:2px solid #ead8df; color:#800020;">
                                    <?= number_format($pedido['total'], 2) ?> €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Notas si las hay -->
                <?php if (!empty($pedido['notas'])): ?>
                <div style="background:#fdf8f2; border-left:4px solid #d4a5b0; border-radius:8px; padding:0.8rem 1rem; font-size:0.85rem; color:#7a6a5a;">
                    <strong>Nota del laboratorio:</strong><br>
                    <?= htmlspecialchars($pedido['notas']) ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>

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
</body>
</html>
