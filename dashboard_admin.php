<?php
require_once 'config.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $pid = (int) $_POST['pedido_id'];
    $nuevo = (int) $_POST['nuevo_estado'];
    $notas = trim($_POST['notas_inline'] ?? '');

    if ($pid > 0 && in_array($nuevo, [0, 1, 2, 3])) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ?, notas = ? WHERE id = ?");
        $stmt->execute([$nuevo, $notas ?: null, $pid]);
    }
    header('Location: dashboard_admin.php?ok=1');
    exit;
}

$filtro = $_GET['estado'] ?? 'todos';
$where = '';
$params = [];

if ($filtro !== 'todos' && in_array($filtro, ['0','1','2','3'])) {
    $where = 'WHERE p.estado = ?';
    $params = [(int)$filtro];
}

$stmt = $pdo->prepare("
    SELECT p.id, p.total, p.estado, p.notas, p.created_at, p.updated_at,
           u.nombre, u.apellido, u.email, COUNT(pi.id) AS num_items
    FROM pedidos p
    JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
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

$stmt3 = $pdo->query("SELECT estado, COUNT(*) as total FROM pedidos GROUP BY estado");
$totales = array_fill(0, 4, 0);
foreach ($stmt3->fetchAll() as $fila) {
    $totales[$fila['estado']] = $fila['total'];
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
    <title>Panel Admin - Casa Denise</title>
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

<main class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="texto-bordeo mb-0">Panel de Gestión</h2>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Gestiona los pedidos de todos los clientes</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="dashboard_admin.php" class="stat-card todos">
                <div class="stat-numero"><?= array_sum($totales) ?></div>
                <div class="stat-label">Total pedidos</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="dashboard_admin.php?estado=0" class="stat-card s0">
                <div class="stat-numero"><?= $totales[0] ?></div>
                <div class="stat-label">⏳ Pendientes</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="dashboard_admin.php?estado=2" class="stat-card s2">
                <div class="stat-numero"><?= $totales[2] ?></div>
                <div class="stat-label">⚙️ En proceso</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="dashboard_admin.php?estado=3" class="stat-card s3">
                <div class="stat-numero"><?= $totales[3] ?></div>
                <div class="stat-label">🏁 Completados</div>
            </a>
        </div>
    </div>

    <div class="filtros-bar mb-4">
        <span style="font-size:0.8rem; color:#a07a8a; letter-spacing:1px; text-transform:uppercase;">Filtrar:</span>
        <a href="dashboard_admin.php" class="filtro-btn <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
        <?php foreach ($estados as $val => $cfg): ?>
        <a href="dashboard_admin.php?estado=<?= $val ?>" class="filtro-btn <?= $filtro == $val ? 'active' : '' ?>">
            <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="tabla-pedidos">
        <?php if (empty($pedidos)): ?>
            <div class="empty-state">
                <p style="font-size:2rem;">📭</p>
                <p>No hay pedidos con este filtro.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla-pedidos mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Artículos</th>
                        <th>Total</th>
                        <th>Estado</th>
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
                        <td>
                            <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></div>
                            <div style="font-size:0.78rem; color:#a07a8a;"><?= htmlspecialchars($p['email']) ?></div>
                        </td>
                        <td style="font-size:0.85rem;">
                            <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                            <div style="font-size:0.75rem; color:#a07a8a;"><?= date('H:i', strtotime($p['created_at'])) ?></div>
                        </td>
                        <td><span style="color:#a07a8a;"><?= $p['num_items'] ?> art.</span></td>
                        <td><strong><?= number_format($p['total'], 2) ?> €</strong></td>
                        <td>
                            <span class="badge-estado <?= $est['badge'] ?>"><?= $est['icon'] ?> <?= $est['label'] ?></span>
                        </td>
                    </tr>

                    <tr class="fila-detalle" id="detalle-<?= $p['id'] ?>" style="display:none;">
                        <td colspan="7">
                            <div class="detalle-inner">

                                <?php if (!empty($items)): ?>
                                <p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:#a07a8a; margin-bottom:0;">Contenido del pedido</p>
                                <table class="tabla-items">
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
                                            <td colspan="4" class="text-end">Total:</td>
                                            <td class="text-end" style="color:#800020; font-size:1rem;"><?= number_format($p['total'], 2) ?> €</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php else: ?>
                                    <p class="text-muted" style="font-size:0.85rem;">Sin artículos registrados.</p>
                                <?php endif; ?>

                                <form method="POST" action="dashboard_admin.php" onclick="event.stopPropagation()">
                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                    <div class="estado-form-inline">
                                        <span class="estado-form-label">Cambiar estado:</span>
                                        <select name="nuevo_estado" class="select-estado">
                                            <?php foreach ($estados as $val => $cfg): ?>
                                            <option value="<?= $val ?>" <?= $p['estado'] == $val ? 'selected' : '' ?>>
                                                <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div style="flex:1; min-width:180px;">
                                            <input type="text" name="notas_inline" class="select-estado" style="width:100%;"
                                                placeholder="Nota para el cliente (opcional)"
                                                value="<?= htmlspecialchars($p['notas'] ?? '') ?>">
                                        </div>
                                        <button type="submit" class="btn btn-bordeo btn-sm px-3" style="font-size:0.78rem; white-space:nowrap;">
                                            💾 Guardar
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</main>

<?php if (isset($_GET['ok'])): ?>
<div class="toast-ok">✅ Estado actualizado correctamente</div>
<?php endif; ?>

<footer class="footer-vino shadow-sm">
    <div class="container footer-container">
        <a href="index.php" class="footer-brand">Casa Denise</a>
        <p class="footer-text">© 2026 Laboratorio Dental - Todos los derechos reservados</p>
        <div class="footer-text">
            <span>📍 C/Benito Pérez Galdós 11</span>
            <span class="ms-3">📞 +34 123 456 789</span>
        </div>
    </div>
</footer>
<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
