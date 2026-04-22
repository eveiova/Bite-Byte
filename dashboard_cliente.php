<?php
require_once 'config.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['rol'] === 'admin') {
    header('Location: dashboard_admin.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.id, p.total, p.estado, p.notas, p.created_at, p.updated_at, COUNT(pi.id) AS num_items
    FROM pedidos p
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    WHERE p.usuario_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['id']]);
$pedidos = $stmt->fetchAll();

$items_por_pedido = [];
if (!empty($pedidos)) {
    $ids = array_column($pedidos, 'id');
    $pl = implode(',', array_fill(0, count($ids), '?'));
    $stmt2 = $pdo->prepare("SELECT pi.pedido_id, pi.cantidad, pi.precio_unitario, c.nombre, c.categoria, c.descripcion FROM pedido_items pi JOIN catalogo c ON c.id = pi.catalogo_id WHERE pi.pedido_id IN ($pl) ORDER BY pi.id");
    $stmt2->execute($ids);
    foreach ($stmt2->fetchAll() as $item) {
        $items_por_pedido[$item['pedido_id']][] = $item;
    }
}

$estados = [
    0 => ['label' => 'Pendiente',  'badge' => 'badge-pendiente',  'icon' => '⏳'],
    1 => ['label' => 'Aprobado',   'badge' => 'badge-aprobado',   'icon' => '✅'],
    2 => ['label' => 'En proceso', 'badge' => 'badge-proceso',    'icon' => '⚙️'],
    3 => ['label' => 'Completado', 'badge' => 'badge-completado', 'icon' => '🏁'],
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

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li class="nav-item"><a href="dashboard_admin.php" class="nav-link nav-link-vino">Panel Admin</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="dashboard_cliente.php" class="nav-link nav-link-vino">Mi cuenta</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="logout.php" class="nav-link nav-link-vino">Salir (<?= htmlspecialchars($_SESSION['nombre']) ?>)</a></li>
                    <li class="nav-item"><a href="login.php" class="nav-link nav-link-vino">Iniciar sesión</a></li>

            </ul>
        </div>
    </div>
</nav>
</header>

<main class="container mt-5 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="texto-bordeo mb-0">Mis Pedidos</h2>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Pulsa sobre un pedido para ver su contenido</p>
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
                        $est = $estados[$p['estado']];
                        $items = $items_por_pedido[$p['id']] ?? [];
                    ?>
                        <tr class="fila-pedido" id="fila-<?= $p['id'] ?>" onclick="toggleDetalle(<?= $p['id'] ?>)">
                            <td class="text-center"><span class="chevron">▼</span></td>
                            <td><strong style="color:#800020;">#<?= $p['id'] ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                            <td><span style="color:#a07a8a;"><?= $p['num_items'] ?> art.</span></td>
                            <td><strong><?= number_format($p['total'], 2) ?> €</strong></td>
                            <td>
                                <span class="badge-estado <?= $est['badge'] ?>"><?= $est['icon'] ?> <?= $est['label'] ?></span>
                            </td>
                            <td style="font-size:0.8rem; color:#a07a8a;"><?= date('d/m/Y H:i', strtotime($p['updated_at'])) ?></td>
                        </tr>

                        <tr class="fila-detalle" id="detalle-<?= $p['id'] ?>" style="display:none;">
                            <td colspan="7">
                                <div class="detalle-inner">

                                    <div class="mini-timeline">
                                        <?php foreach ($estados as $val => $cfg): ?>
                                            <?php if ($val > 0): ?><span class="mini-arrow">→</span><?php endif; ?>
                                            <span class="mini-step <?= $val < $p['estado'] ? 'hecho' : ($val == $p['estado'] ? 'activo' : '') ?>">
                                                <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

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
                                                        <div style="font-size:0.76rem; color:#a07a8a;"><?= htmlspecialchars($item['descripcion']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="color:#a07a8a;"><?= htmlspecialchars($item['categoria'] ?? '—') ?></td>
                                                <td class="text-center"><?= $item['cantidad'] ?></td>
                                                <td class="text-end"><?= number_format($item['precio_unitario'], 2) ?> €</td>
                                                <td class="text-end" style="color:#800020; font-weight:600;"><?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?> €</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end">Total del pedido:</td>
                                                <td class="text-end" style="color:#800020; font-size:1rem;"><?= number_format($p['total'], 2) ?> €</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <?php else: ?>
                                        <p class="text-muted mt-3" style="font-size:0.85rem;">No hay artículos registrados.</p>
                                    <?php endif; ?>

                                    <?php if (!empty($p['notas'])): ?>
                                    <div class="nota-lab">
                                        <strong>📋 Nota del laboratorio:</strong> <?= htmlspecialchars($p['notas']) ?>
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
