<?php
// ============================================================
//  CASA DENISE - Panel del cliente
//  Coloca este archivo en: tu-proyecto/dashboard_cliente.php
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();   // Redirige a login si no hay sesión

$usuario = usuarioActual();

// Redirigir admins a su propio panel
if ($usuario['rol'] === 'admin') {
    header('Location: dashboard_admin.php');
    exit;
}

// Obtener pedidos del cliente con sus estados
$stmt = $pdo->prepare("
    SELECT p.id, p.total, p.estado, p.notas, p.created_at,
           COUNT(pi.id) AS num_items
    FROM pedidos p
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    WHERE p.usuario_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$usuario['id']]);
$pedidos = $stmt->fetchAll();

// Etiquetas y colores de estado
$estados = [
    0 => ['label' => 'Pendiente',   'class' => 'bg-secondary'],
    1 => ['label' => 'Aprobado',    'class' => 'bg-primary'],
    2 => ['label' => 'En proceso',  'class' => 'bg-warning text-dark'],
    3 => ['label' => 'Completado',  'class' => 'bg-success'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="p-3 navbar-vino shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.html" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span style="color:#e8c9d0; font-size:0.85rem;">
                Hola, <strong style="color:#f5d98b;"><?= htmlspecialchars($usuario['nombre']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-light" style="border-color:#d4a5b0; color:#e8c9d0; font-size:0.8rem;">
                Cerrar sesión
            </a>
        </div>
    </div>
</header>

<main class="container mt-5 mb-5">
    <h2 class="texto-bordeo mb-1">Mis Pedidos</h2>
    <p class="text-muted mb-4">Aquí puedes ver el estado de todos tus pedidos.</p>

    <?php if (empty($pedidos)): ?>
        <div class="card card-custom p-4 text-center">
            <p class="text-muted mb-3">Aún no tienes pedidos realizados.</p>
            <a href="productos.html" class="btn btn-bordeo">Ver catálogo</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead style="background:#f6f0e1;">
                    <tr>
                        <th>Pedido #</th>
                        <th>Fecha</th>
                        <th>Artículos</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><strong>#<?= $p['id'] ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td><?= $p['num_items'] ?> artículo<?= $p['num_items'] != 1 ? 's' : '' ?></td>
                        <td><?= number_format($p['total'], 2) ?> €</td>
                        <td>
                            <span class="badge <?= $estados[$p['estado']]['class'] ?>">
                                <?= $estados[$p['estado']]['label'] ?>
                            </span>
                        </td>
                        <td class="text-muted" style="font-size:0.85rem;">
                            <?= htmlspecialchars($p['notas'] ?? '—') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

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
