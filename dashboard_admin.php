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

    if ($pedido_id > 0 && in_array($nuevo_estado, [0, 1, 2, 3])) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $pedido_id]);
    }
    // Redirigir para evitar reenvío del formulario al refrescar
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
        <a href="index.html" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
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
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Artículos</th>
                        <th>Total</th>
                        <th>Notas</th>
                        <th>Estado actual</th>
                        <th>Cambiar estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><strong>#<?= $p['id'] ?></strong></td>
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
                        <td><?= $p['num_items'] ?> art.</td>
                        <td><strong><?= number_format($p['total'], 2) ?> €</strong></td>
                        <td style="font-size:0.82rem; color:#7a6a5a; max-width:180px;">
                            <?= htmlspecialchars($p['notas'] ?? '—') ?>
                        </td>
                        <td>
                            <span class="badge-estado <?= $estados[$p['estado']]['badge'] ?>">
                                <?= $estados[$p['estado']]['icon'] ?>
                                <?= $estados[$p['estado']]['label'] ?>
                            </span>
                        </td>
                        <td>
                            <!-- Formulario inline para cambiar estado -->
                            <form method="POST" action="dashboard_admin.php">
                                <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                <div class="d-flex gap-2 align-items-center">
                                    <select name="nuevo_estado" class="select-estado">
                                        <?php foreach ($estados as $val => $cfg): ?>
                                        <option value="<?= $val ?>"
                                            <?= $p['estado'] == $val ? 'selected' : '' ?>>
                                            <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-bordeo btn-sm px-2 py-1"
                                            style="font-size:0.78rem;">
                                        Guardar
                                    </button>
                                </div>
                            </form>
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
        <a href="index.html" class="footer-brand">Casa Denise</a>
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
