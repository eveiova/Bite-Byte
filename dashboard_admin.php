<?php
// ============================================================
//  CASA DENISE - Panel de Administración
//  Coloca este archivo en: tu-proyecto/dashboard_admin.php
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAdmin(); // Solo admins, redirige si no

$usuario = usuarioActual();

// ── Cambiar estado de un pedido (petición POST) ─────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $pedido_id    = (int) $_POST['pedido_id'];
    $nuevo_estado = (int) $_POST['nuevo_estado'];
    $notas        = trim($_POST['notas_inline'] ?? '');

    if ($pedido_id > 0 && in_array($nuevo_estado, [0, 1, 2, 3])) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ?, notas = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $notas ?: null, $pedido_id]);
    }
    header('Location: dashboard_admin.php?ok=1');
    exit;
}

// ── Filtro por estado (GET) ──────────────────────────────────
$filtro_estado = $_GET['estado'] ?? 'todos';

$where = '';
$params = [];
if ($filtro_estado !== 'todos' && in_array($filtro_estado, ['0','1','2','3'])) {
    $where  = 'WHERE p.estado = ?';
    $params = [(int)$filtro_estado];
}

// ── Obtener pedidos con datos del cliente ────────────────────
$sql = "
    SELECT
        p.id,
        p.total,
        p.estado,
        p.notas,
        p.created_at,
        p.updated_at,
        u.nombre,
        u.apellido,
        u.email,
        COUNT(pi.id) AS num_items
    FROM pedidos p
    JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// ── Items de todos los pedidos visibles ──────────────────────
$items_por_pedido = [];
if (!empty($pedidos)) {
    $ids          = array_column($pedidos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt_items   = $pdo->prepare("
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
    $stmt_items->execute($ids);
    foreach ($stmt_items->fetchAll() as $item) {
        $items_por_pedido[$item['pedido_id']][] = $item;
    }
}

// ── Totales por estado (para las tarjetas resumen) ───────────
$stmt_totales = $pdo->query("
    SELECT estado, COUNT(*) as total
    FROM pedidos
    GROUP BY estado
");
$totales = array_fill(0, 4, 0);
foreach ($stmt_totales->fetchAll() as $fila) {
    $totales[$fila['estado']] = $fila['total'];
}

// ── Configuración de estados ─────────────────────────────────
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
    <title>Panel Admin - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* ── Tarjetas de resumen ── */
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-left: 5px solid;
            transition: transform 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .stat-card:hover { transform: translateY(-3px); color: inherit; }
        .stat-card.todos   { border-color: #800020; }
        .stat-card.s0      { border-color: #888; }
        .stat-card.s1      { border-color: #0d6efd; }
        .stat-card.s2      { border-color: #ffc107; }
        .stat-card.s3      { border-color: #198754; }

        .stat-numero {
            font-family: "Playfair Display", serif;
            font-size: 2.2rem;
            color: #800020;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #a07a8a;
            margin-top: 0.2rem;
        }

        /* ── Filtros ── */
        .filtros-bar {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.4rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .filtro-btn {
            border: 1.5px solid #d4a5b0;
            border-radius: 20px;
            padding: 0.3rem 1rem;
            font-size: 0.8rem;
            font-family: "Josefin Sans", sans-serif;
            letter-spacing: 0.5px;
            background: none;
            color: #5a4b3b;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .filtro-btn:hover, .filtro-btn.active {
            background: #800020;
            border-color: #800020;
            color: #fff;
        }

        /* ── Tabla de pedidos ── */
        .tabla-pedidos {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
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
        .tabla-pedidos tbody td {
            padding: 0.95rem 1.2rem;
            vertical-align: middle;
            border-color: #f0e6ec;
            font-size: 0.9rem;
        }
        .tabla-pedidos tbody tr:hover { background: #fdf8f2; }

        /* ── Badges de estado ── */
        .badge-estado {
            padding: 0.35em 0.75em;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-pendiente  { background: #f0f0f0;  color: #555; }
        .badge-aprobado   { background: #dbeafe;  color: #1e40af; }
        .badge-proceso    { background: #fef9c3;  color: #854d0e; }
        .badge-completado { background: #dcfce7;  color: #166534; }

        /* ── Select de estado ── */
        .select-estado {
            border: 1.5px solid #d4a5b0;
            border-radius: 8px;
            padding: 0.3rem 0.6rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 0.82rem;
            color: #5a4b3b;
            background: #fdf8f2;
            cursor: pointer;
        }
        .select-estado:focus {
            border-color: #800020;
            outline: none;
            box-shadow: 0 0 0 2px rgba(128,0,32,0.1);
        }

        /* ── Toast de confirmación ── */
        .toast-ok {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #166534;
            color: white;
            padding: 0.8rem 1.4rem;
            border-radius: 10px;
            font-size: 0.88rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: fadeInOut 3s ease forwards;
        }
        @keyframes fadeInOut {
            0%   { opacity: 0; transform: translateY(10px); }
            15%  { opacity: 1; transform: translateY(0); }
            75%  { opacity: 1; }
            100% { opacity: 0; }
        }

        /* ── Filas expandibles ── */
        .fila-pedido {
            cursor: pointer;
            transition: background 0.15s;
        }
        .fila-pedido:hover   { background: #fdf8f2 !important; }
        .fila-pedido.abierta { background: #fdf4f6 !important; }

        .chevron {
            display: inline-block;
            transition: transform 0.25s;
            font-size: 0.72rem;
            color: #a07a8a;
        }
        .abierta .chevron { transform: rotate(180deg); }

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

        /* Tabla de items dentro del detalle */
        .tabla-items { width: 100%; font-size: 0.85rem; margin-top: 0.8rem; }
        .tabla-items th {
            font-size: 0.72rem; text-transform: uppercase;
            letter-spacing: 1px; color: #a07a8a;
            padding: 0.4rem 0.8rem;
            border-bottom: 2px solid #ead8df; font-weight: 600;
        }
        .tabla-items td {
            padding: 0.55rem 0.8rem;
            border-bottom: 1px solid #f0e6ec;
            color: #5a4b3b; vertical-align: middle;
        }
        .tabla-items tfoot td {
            border-top: 2px solid #ead8df; border-bottom: none;
            font-weight: 700; padding-top: 0.7rem;
        }

        /* Formulario de estado dentro del detalle */
        .estado-form-inline {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #ead8df;
        }
        .estado-form-label {
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #a07a8a;
        }

        /* ── Admin badge en navbar ── */
        .admin-badge {
            background: #f5d98b;
            color: #5b0e1f;
            font-size: 0.65rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 0.2em 0.6em;
            border-radius: 4px;
        }

        /* ── Vacío ── */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #a07a8a;
        }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<header class="p-3 navbar-vino shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="admin-badge">Admin</span>
            <span style="color:#e8c9d0; font-size:0.85rem;">
                <strong style="color:#f5d98b;"><?= htmlspecialchars($usuario['nombre']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-light"
               style="border-color:#d4a5b0; color:#e8c9d0; font-size:0.8rem;">
                Cerrar sesión
            </a>
        </div>
    </div>
</header>

<main class="container mt-4 mb-5">

    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="texto-bordeo mb-0">Panel de Gestión</h2>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Gestiona los pedidos de todos los clientes
            </p>
        </div>
    </div>

    <!-- Tarjetas resumen -->
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

    <!-- Filtros -->
    <div class="filtros-bar mb-4">
        <span style="font-size:0.8rem; color:#a07a8a; letter-spacing:1px; text-transform:uppercase;">
            Filtrar:
        </span>
        <a href="dashboard_admin.php"
           class="filtro-btn <?= $filtro_estado === 'todos' ? 'active' : '' ?>">
            Todos
        </a>
        <?php foreach ($estados as $val => $cfg): ?>
        <a href="dashboard_admin.php?estado=<?= $val ?>"
           class="filtro-btn <?= $filtro_estado == $val ? 'active' : '' ?>">
            <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabla de pedidos -->
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
                        $est   = $estados[$p['estado']];
                        $items = $items_por_pedido[$p['id']] ?? [];
                    ?>

                    <!-- Fila principal -->
                    <tr class="fila-pedido"
                        id="fila-<?= $p['id'] ?>"
                        onclick="toggleDetalle(<?= $p['id'] ?>)">
                        <td class="text-center"><span class="chevron">▼</span></td>
                        <td><strong style="color:#800020;">#<?= $p['id'] ?></strong></td>
                        <td>
                            <div style="font-weight:600; font-size:0.9rem;">
                                <?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?>
                            </div>
                            <div style="font-size:0.78rem; color:#a07a8a;">
                                <?= htmlspecialchars($p['email']) ?>
                            </div>
                        </td>
                        <td style="font-size:0.85rem;">
                            <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                            <div style="font-size:0.75rem; color:#a07a8a;">
                                <?= date('H:i', strtotime($p['created_at'])) ?>
                            </div>
                        </td>
                        <td><span style="color:#a07a8a;"><?= $p['num_items'] ?> art.</span></td>
                        <td><strong><?= number_format($p['total'], 2) ?> €</strong></td>
                        <td>
                            <span class="badge-estado <?= $est['badge'] ?>">
                                <?= $est['icon'] ?> <?= $est['label'] ?>
                            </span>
                        </td>
                    </tr>

                    <!-- Fila detalle (oculta) -->
                    <tr class="fila-detalle" id="detalle-<?= $p['id'] ?>" style="display:none;">
                        <td colspan="7">
                            <div class="detalle-inner">

                                <!-- Tabla de artículos -->
                                <?php if (!empty($items)): ?>
                                <p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:#a07a8a; margin-bottom:0;">
                                    Contenido del pedido
                                </p>
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
                                            <td colspan="4" class="text-end">Total:</td>
                                            <td class="text-end" style="color:#800020; font-size:1rem;">
                                                <?= number_format($p['total'], 2) ?> €
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php else: ?>
                                    <p class="text-muted" style="font-size:0.85rem;">Sin artículos registrados.</p>
                                <?php endif; ?>

                                <!-- Formulario de cambio de estado -->
                                <form method="POST" action="dashboard_admin.php"
                                      onclick="event.stopPropagation()">
                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                    <div class="estado-form-inline">
                                        <span class="estado-form-label">Cambiar estado:</span>
                                        <select name="nuevo_estado" class="select-estado">
                                            <?php foreach ($estados as $val => $cfg): ?>
                                            <option value="<?= $val ?>"
                                                <?= $p['estado'] == $val ? 'selected' : '' ?>>
                                                <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <div style="flex:1; min-width:180px;">
                                            <input
                                                type="text"
                                                name="notas_inline"
                                                class="select-estado"
                                                style="width:100%;"
                                                placeholder="Nota para el cliente (opcional)"
                                                value="<?= htmlspecialchars($p['notas'] ?? '') ?>"
                                            >
                                        </div>

                                        <button type="submit"
                                                class="btn btn-bordeo btn-sm px-3"
                                                style="font-size:0.78rem; white-space:nowrap;">
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

<!-- Toast de confirmación -->
<?php if (isset($_GET['ok'])): ?>
<div class="toast-ok">✅ Estado actualizado correctamente</div>
<?php endif; ?>

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
