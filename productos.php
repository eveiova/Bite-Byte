<?php
require_once 'config.php';

$logueado = isset($_SESSION['id']);

$stmt = $pdo->query("SELECT id, nombre, descripcion, precio, categoria, imagen_url FROM catalogo WHERE disponible = 1 ORDER BY categoria, nombre");
$productos = $stmt->fetchAll();

$por_categoria = [];
foreach ($productos as $p) {
    $por_categoria[$p['categoria'] ?? 'General'][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Casa Denise</title>
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

<main class="container mt-4 mb-5">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:#800020;">Inicio</a></li>
            <li class="breadcrumb-item active">Catálogo de Productos</li>
        </ol>
    </nav>

    <div class="text-center mb-4">
        <h2 class="texto-bordeo display-4">Nuestros Productos</h2>
        <p class="lead">Materiales e insumos premium para laboratorios dentales.</p>
        <hr class="mx-auto" style="width:60px; border:2px solid #800020; opacity:1;">
    </div>

    <div class="row g-4">

        <div class="col-lg-8">
            <?php foreach ($por_categoria as $categoria => $items): ?>
                <h5 class="categoria-titulo"><?= htmlspecialchars($categoria) ?></h5>
                <div class="row g-3">
                    <?php foreach ($items as $p): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="producto-card">
                            <?php if ($p['imagen_url']): ?>
                                <img src="<?= htmlspecialchars($p['imagen_url']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"
                                    class="producto-img"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="producto-img-placeholder" style="display:none;">🦷</div>
                            <?php else: ?>
                                <div class="producto-img-placeholder">🦷</div>
                            <?php endif; ?>

                            <div class="producto-body">
                                <div class="producto-categoria"><?= htmlspecialchars($p['categoria'] ?? '') ?></div>
                                <div class="producto-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                                <div class="producto-desc"><?= htmlspecialchars($p['descripcion'] ?? '') ?></div>
                                <div class="producto-footer">
                                    <div class="producto-precio"><?= number_format($p['precio'], 2) ?> €</div>
                                    <button class="btn-agregar"
                                        onclick="agregarAlCarrito(<?= $p['id'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['precio'] ?>)"
                                        id="btn-<?= $p['id'] ?>">
                                        + Añadir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
            <div class="carrito-box">
                <div class="carrito-titulo">
                    🛒 Carrito
                    <span id="carrito-contador" style="background:#800020; color:#fff; border-radius:50%; width:22px; height:22px; font-size:0.75rem; display:none; align-items:center; justify-content:center;">0</span>
                </div>

                <div id="carrito-contenido">
                    <div class="carrito-vacio">
                        <div style="font-size:2rem; margin-bottom:0.5rem;">🛒</div>
                        Aún no has añadido productos.
                    </div>
                </div>

                <div id="carrito-total-wrapper" style="display:none;">
                    <div class="carrito-total">
                        <span>Total</span>
                        <span class="carrito-total-precio" id="carrito-total">0,00 €</span>
                    </div>
                </div>

                <div id="notas-wrapper" style="display:none; margin-top:1rem;">
                    <label style="font-size:0.75rem; letter-spacing:1px; text-transform:uppercase; color:#a07a8a; margin-bottom:0.4rem; display:block;">
                        Notas para el laboratorio (opcional)
                    </label>
                    <textarea id="notas-pedido" class="notas-textarea" placeholder="Indicaciones especiales, referencias de paciente..."></textarea>
                </div>

                <button class="btn-finalizar" id="btn-finalizar" onclick="finalizarPedido()" disabled>
                    Finalizar Pedido
                </button>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="modalExito" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; overflow:hidden;">
            <div style="background:linear-gradient(135deg,#5b0e1f,#3b0a14); padding:1.5rem; text-align:center; border-bottom:3px solid #f5d98b;">
                <div class="brand-name" style="font-family:'Great Vibes',cursive; color:#f5d98b; font-size:2.5rem;">Casa Denise</div>
            </div>
            <div class="modal-exito-body">
                <div style="font-size:3rem; margin-bottom:0.5rem;">🎉</div>
                <h5 class="texto-bordeo mb-1">¡Pedido realizado!</h5>
                <p class="text-muted mb-1" style="font-size:0.88rem;">Tu número de pedido es:</p>
                <div class="num-pedido-grande" id="modal-num-pedido">—</div>
                <p class="text-muted mt-2" style="font-size:0.82rem;">Guarda este número para consultar el estado de tu pedido.</p>
                <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                    <a id="btn-ver-estado" href="#" class="btn btn-bordeo px-4">Ver estado del pedido</a>
                    <a href="dashboard_cliente.php" class="btn btn-outline-secondary px-4">Mis pedidos</a>
                </div>
            </div>
        </div>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const LOGUEADO = <?= $logueado ? 'true' : 'false' ?>;
let carrito = {};

function agregarAlCarrito(id, nombre, precio) {
    if (carrito[id]) {
        carrito[id].cantidad++;
    } else {
        carrito[id] = { nombre, precio, cantidad: 1 };
    }
    renderCarrito();

    const btn = document.getElementById('btn-' + id);
    btn.textContent = '✓ Añadido';
    btn.classList.add('agregado');
    setTimeout(() => {
        btn.textContent = '+ Añadir';
        btn.classList.remove('agregado');
    }, 1500);
}

function cambiarCantidad(id, delta) {
    if (!carrito[id]) return;
    carrito[id].cantidad += delta;
    if (carrito[id].cantidad <= 0) delete carrito[id];
    renderCarrito();
}

function renderCarrito() {
    const contenido = document.getElementById('carrito-contenido');
    const totalWrap = document.getElementById('carrito-total-wrapper');
    const notasWrap = document.getElementById('notas-wrapper');
    const btnFin = document.getElementById('btn-finalizar');
    const contador = document.getElementById('carrito-contador');
    const totalEl = document.getElementById('carrito-total');
    const ids = Object.keys(carrito);

    if (ids.length === 0) {
        contenido.innerHTML = '<div class="carrito-vacio"><div style="font-size:2rem; margin-bottom:0.5rem;">🛒</div>Aún no has añadido productos.</div>';
        totalWrap.style.display = 'none';
        notasWrap.style.display = 'none';
        btnFin.disabled = true;
        contador.style.display = 'none';
        return;
    }

    let html = '';
    let total = 0;
    let numItems = 0;

    ids.forEach(id => {
        const item = carrito[id];
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        numItems += item.cantidad;
        html += `<div class="carrito-item">
            <div class="carrito-item-nombre">${item.nombre}</div>
            <div class="carrito-item-controles">
                <button class="btn-cantidad" onclick="cambiarCantidad(${id}, -1)">−</button>
                <span class="cantidad-num">${item.cantidad}</span>
                <button class="btn-cantidad" onclick="cambiarCantidad(${id}, +1)">+</button>
            </div>
            <div class="carrito-item-precio">${subtotal.toFixed(2)} €</div>
        </div>`;
    });

    contenido.innerHTML = html;
    totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
    totalWrap.style.display = 'block';
    notasWrap.style.display = 'block';
    btnFin.disabled = false;
    contador.style.display = 'inline-flex';
    contador.textContent = numItems;
}

async function finalizarPedido() {
    if (!LOGUEADO) {
        window.location.href = 'login.php?redirect=productos.php';
        return;
    }

    const btn = document.getElementById('btn-finalizar');
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    const notas = document.getElementById('notas-pedido').value.trim();
    const items = Object.entries(carrito).map(([id, item]) => ({
        catalogo_id: parseInt(id),
        cantidad: item.cantidad,
        precio_unitario: item.precio
    }));

    try {
        const resp = await fetch('procesar_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items, notas })
        });

        const data = await resp.json();

        if (data.ok) {
            document.getElementById('modal-num-pedido').textContent = '#' + data.pedido_id;
            document.getElementById('btn-ver-estado').href = 'index.php?id=' + data.pedido_id;
            new bootstrap.Modal(document.getElementById('modalExito')).show();
            carrito = {};
            renderCarrito();
        } else {
            alert('Error: ' + (data.error || 'No se pudo procesar el pedido.'));
            btn.disabled = false;
            btn.textContent = 'Finalizar Pedido';
        }

    } catch (e) {
        alert('Error de conexión. Inténtalo de nuevo.');
        btn.disabled = false;
        btn.textContent = 'Finalizar Pedido';
    }
}
</script>
</body>
</html>
