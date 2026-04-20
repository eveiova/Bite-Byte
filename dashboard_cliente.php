<?php
// ============================================================
//  CASA DENISE - Panel del cliente
//  Coloca este archivo en: tu-proyecto/dashboard_cliente.php
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$usuario = usuarioActual();

if ($usuario['rol'] === 'admin') {
    header('Location: dashboard_admin.php');
    exit;
}

// Pedidos del cliente
$stmt = $pdo->prepare("
    SELECT p.id, p.total, p.estado, p.notas, p.created_at, p.updated_at,
           COUNT(pi.id) AS num_items
    FROM pedidos p
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    WHERE p.usuario_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$usuario['id']]);
$pedidos = $stmt->fetchAll();

// Items de todos los pedidos en una sola query
$items_por_pedido = [];
if (!empty($pedidos)) {
    $ids          = array_column($pedidos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt2 = $pdo->prepare("
        SELECT
            pi.pedido_id,
            pi.cantidad,
            pi.precio_unitario,
            c.nombre,
            c.categoria,
            c.descripcion
        FROM pedido_items pi
        JOIN catalogo c ON c.id = pi.catalogo_id
        WHERE pi.pedido_id IN ($placeholders)
        ORDER BY pi.id
    ");
    $stmt2->execute($ids);
    foreach ($stmt2->fetchAll() as $item) {
        $items_por_pedido[$item['pedido_id']][] = $item;
    }
}

$estados = [
    0 => ['label' => 'Pendiente',   'badge' => 'badge-pendiente',  'icon' => '⏳'],
    1 => ['label' => 'Aprobado',    'badge' => 'badge-aprobado',   'icon' => '✅'],
    2 => ['label' => 'En proceso',  'badge' => 'badge-proceso',    'icon' => '⚙️'],
    3 => ['label' => 'Completado',  'badge' => 'badge-completado', 'icon' => '🏁'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .tabla-pedidos {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(128,0,32,0.07);
            overflow: hidden;
        }
        .tabla-pedidos thead th {
            background: linear-gradient(135deg, #5b0e1f 0%, #3b0a14 100%);
            color: #e8c9d0;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: none;
            padding: 1rem 1.2rem;
            font-weight: 400;
        }

        /* Fila principal */
        .fila-pedido {
            cursor: pointer;
            transition: background 0.15s;
            border-bottom: 1px solid #f0e6ec !important;
        }
        .fila-pedido:hover  { background: #fdf8f2 !important; }
        .fila-pedido.abierta { background: #fdf4f6 !important; }
        .fila-pedido td {
            padding: 0.95rem 1.2rem;
            vertical-align: middle;
            border: none;
            font-size: 0.9rem;
        }

        /* Chevron animado */
        .chevron {
            display: inline-block;
            transition: transform 0.25s;
            font-size: 0.72rem;
            color: #a07a8a;
        }
        .abierta .chevron { transform: rotate(180deg); }

        /* Fila de detalle */
        .fila-detalle td {
            padding: 0 !important;
            border: none !important;
            background: #fdf4f6;
        }
        .detalle-inner {
            padding: 1.2rem 1.5rem 1.5rem 2.5rem;
            border-bottom: 2px solid #ead8df;
            animation: slideDown 0.2s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Tabla de items */
        .tabla-items { width: 100%; font-size: 0.85rem; }
        .tabla-items th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a07a8a;
            padding: 0.4rem 0.8rem;
            border-bottom: 2px solid #ead8df;
            font-weight: 600;
        }
        .tabla-items td {
            padding: 0.55rem 0.8rem;
            border-bottom: 1px solid #f0e6ec;
            color: #5a4b3b;
            vertical-align: middle;
        }
        .tabla-items tfoot td {
            border-top: 2px solid #ead8df;
            border-bottom: none;
            font-weight: 700;
            padding-top: 0.7rem;
        }

        /* Badges */
        .badge-estado { padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pendiente  { background: #f0f0f0; color: #555; }
        .badge-aprobado   { background: #dbeafe; color: #1e40af; }
        .badge-proceso    { background: #fef9c3; color: #854d0e; }
        .badge-completado { background: #dcfce7; color: #166534; }

        /* Mini timeline */
        .mini-timeline { display: flex; align-items: center; gap: 0.3rem; margin-top: 0.8rem; flex-wrap: wrap; }
        .mini-step {
            font-size: 0.72rem; padding: 0.2em 0.6em;
            border-radius: 10px; background: #f0e6ec; color: #a07a8a;
        }
        .mini-step.activo  { background: #800020; color: white; font-weight: 700; }
        .mini-step.hecho   { background: #dcfce7; color: #166534; }
        .mini-arrow        { color: #d4a5b0; font-size: 0.7rem; }

        /* Nota */
        .nota-lab {
            background: #fff8f0; border-left: 3px solid #d4a5b0;
            border-radius: 6px; padding: 0.5rem 0.9rem;
            font-size: 0.82rem; color: #7a6a5a; margin-top: 0.8rem;
        }

        /* Vacío */
        .empty-state {
            text-align: center; padding: 3rem 2rem;
            background: #fff; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(128,0,32,0.07);
        }
    </style>
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
        <div class="d-flex align-items-center gap-3">
            <ul class="nav d-none d-md-flex">
                <li><a href="index.php"        class="nav-link nav-link-vino">Inicio</a></li>
                <li><a href="productos.php"     class="nav-link nav-link-vino">Productos</a></li>
                <li><a href="buscar_pedido.php" class="nav-link nav-link-vino">Estado Pedido</a></li>
            </ul>
            <span style="color:#e8c9d0; font-size:0.85rem;">
                Hola, <strong style="color:#f5d98b;"><?= htmlspecialchars($usuario['nombre']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-light"
               style="border-color:#d4a5b0; color:#e8c9d0; font-size:0.8rem;">
                Cerrar sesión
            </a>
        </div>
    </div>
</header>

<main class="container mt-5 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="texto-bordeo mb-0">Mis Pedidos</h2>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Pulsa sobre un pedido para ver su contenido
            </p>
        </div>
        <a href="productos.php" class="btn btn-bordeo btn-sm px-3">+ Nuevo pedido</a>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="empty-state">
            <div style="font-size:3rem; margin-bottom:1rem;">📋</div>
            <p class="text-muted mb-3">Aún no tienes pedidos realizados.</p>
            <a href="productos.php" class="btn btn-bordeo px-4">Ver catálogo</a>
        </div>

    <?php else: ?>
        <div class="tabla-pedidos">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Artículos</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Actualizado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos as $p):
                        $est   = $estados[$p['estado']];
                        $items = $items_por_pedido[$p['id']] ?? [];
                    ?>
                        <!-- Fila principal -->
                        <tr class="fila-pedido"
                            id="fila-<?= $p['id'] ?>"
                            onclick="toggleDetalle(<?= $p['id'] ?>)">
                            <td class="text-center"><span class="chevron">▼</span></td>
                            <td><strong style="color:#800020;">#<?= $p['id'] ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                            <td><span style="color:#a07a8a;"><?= $p['num_items'] ?> art.</span></td>
                            <td><strong><?= number_format($p['total'], 2) ?> €</strong></td>
                            <td>
                                <span class="badge-estado <?= $est['badge'] ?>">
                                    <?= $est['icon'] ?> <?= $est['label'] ?>
                                </span>
                            </td>
                            <td style="font-size:0.8rem; color:#a07a8a;">
                                <?= date('d/m/Y H:i', strtotime($p['updated_at'])) ?>
                            </td>
                        </tr>

                        <!-- Fila detalle (oculta) -->
                        <tr class="fila-detalle" id="detalle-<?= $p['id'] ?>" style="display:none;">
                            <td colspan="7">
                                <div class="detalle-inner">

                                    <!-- Mini timeline -->
                                    <div class="mini-timeline">
                                        <?php foreach ($estados as $val => $cfg): ?>
                                            <?php if ($val > 0): ?><span class="mini-arrow">→</span><?php endif; ?>
                                            <span class="mini-step <?= $val < $p['estado'] ? 'hecho' : ($val == $p['estado'] ? 'activo' : '') ?>">
                                                <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Artículos -->
                                    <?php if (!empty($items)): ?>
                                    <table class="tabla-items mt-3">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Categoría</th>
                                                <th class="text-center">Cant.</th>
                                                <th class="text-end">Precio unit.</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                                    <?php if ($item['descripcion']): ?>
                                                        <div style="font-size:0.76rem; color:#a07a8a; margin-top:0.1rem;">
                                                            <?= htmlspecialchars($item['descripcion']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="color:#a07a8a;"><?= htmlspecialchars($item['categoria'] ?? '—') ?></td>
                                                <td class="text-center"><?= $item['cantidad'] ?></td>
                                                <td class="text-end"><?= number_format($item['precio_unitario'], 2) ?> €</td>
                                                <td class="text-end" style="color:#800020; font-weight:600;">
                                                    <?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?> €
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end">Total del pedido:</td>
                                                <td class="text-end" style="color:#800020; font-size:1rem;">
                                                    <?= number_format($p['total'], 2) ?> €
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <?php else: ?>
                                        <p class="text-muted mt-3" style="font-size:0.85rem;">No hay artículos registrados.</p>
                                    <?php endif; ?>

                                    <!-- Nota del laboratorio -->
                                    <?php if (!empty($p['notas'])): ?>
                                    <div class="nota-lab">
                                        <strong>📋 Nota del laboratorio:</strong>
                                        <?= htmlspecialchars($p['notas']) ?>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
<script>
function toggleDetalle(id) {
    const fila    = document.getElementById('fila-' + id);
    const detalle = document.getElementById('detalle-' + id);
    const abierto = detalle.style.display !== 'none';

    if (abierto) {
        detalle.style.display = 'none';
        fila.classList.remove('abierta');
    } else {
        detalle.style.display = 'table-row';
        fila.classList.add('abierta');
    }
}
</script>
</body>
</html>
