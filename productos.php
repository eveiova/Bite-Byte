<?php
// ============================================================
//  CASA DENISE - Catálogo de productos
//  Reemplaza productos.php
//  Coloca en: tu-proyecto/productos.php
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Página pública — no requiere login para ver productos
// El login solo se exige al finalizar el pedido
$logueado = estaLogueado();
$usuario  = $logueado ? usuarioActual() : null;

// Cargar todos los productos disponibles del catálogo
$stmt = $pdo->query("
    SELECT id, nombre, descripcion, precio, categoria, imagen_url
    FROM catalogo
    WHERE disponible = 1
    ORDER BY categoria, nombre
");
$productos = $stmt->fetchAll();

// Agrupar por categoría para mostrarlos en secciones
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
    <style>
        /* ── Tarjeta de producto ── */
        .producto-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(128,0,32,0.07);
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .producto-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(128,0,32,0.13);
        }

        .producto-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f6f0e1;
        }

        .producto-img-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #f6f0e1, #ead8df);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .producto-body {
            padding: 1.1rem 1.2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .producto-categoria {
            font-size: 0.7rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #a07a8a;
            margin-bottom: 0.3rem;
        }

        .producto-nombre {
            font-family: "Playfair Display", serif;
            color: #800020;
            font-size: 1rem;
            margin-bottom: 0.4rem;
            line-height: 1.3;
        }

        .producto-desc {
            font-size: 0.82rem;
            color: #7a6a5a;
            flex: 1;
            margin-bottom: 0.8rem;
            line-height: 1.5;
        }

        .producto-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #f0e6ec;
            padding-top: 0.8rem;
            margin-top: auto;
        }

        .producto-precio {
            font-family: "Playfair Display", serif;
            font-size: 1.25rem;
            color: #800020;
            font-weight: 700;
        }

        .btn-agregar {
            background: #800020;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.4rem 0.9rem;
            font-size: 0.78rem;
            font-family: "Josefin Sans", sans-serif;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-agregar:hover  { background: #5b0e1f; color: #f5d98b; }
        .btn-agregar:active { transform: scale(0.96); }
        .btn-agregar.agregado {
            background: #198754;
            pointer-events: none;
        }

        /* ── Encabezado de categoría ── */
        .categoria-titulo {
            font-family: "Playfair Display", serif;
            color: #800020;
            font-size: 1.3rem;
            border-left: 4px solid #800020;
            padding-left: 0.8rem;
            margin: 2rem 0 1.2rem;
        }

        /* ── Carrito ── */
        .carrito-box {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(128,0,32,0.09);
            padding: 1.8rem 2rem;
            position: sticky;
            top: 1rem;
        }

        .carrito-titulo {
            font-family: "Playfair Display", serif;
            color: #800020;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0e6ec;
        }

        .carrito-vacio {
            text-align: center;
            color: #a07a8a;
            padding: 1.5rem 0;
            font-size: 0.9rem;
        }

        .carrito-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0e6ec;
            font-size: 0.85rem;
            gap: 0.5rem;
        }

        .carrito-item-nombre {
            flex: 1;
            color: #5a4b3b;
            font-size: 0.82rem;
        }

        .carrito-item-controles {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-cantidad {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1.5px solid #d4a5b0;
            background: none;
            color: #800020;
            font-size: 0.9rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            font-weight: 700;
        }
        .btn-cantidad:hover { background: #800020; color: #fff; border-color: #800020; }

        .cantidad-num {
            font-weight: 700;
            color: #5a4b3b;
            min-width: 18px;
            text-align: center;
            font-size: 0.88rem;
        }

        .carrito-item-precio {
            color: #800020;
            font-weight: 700;
            font-size: 0.88rem;
            min-width: 60px;
            text-align: right;
        }

        .carrito-total {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0 0;
            margin-top: 0.5rem;
            font-weight: 700;
            font-size: 1rem;
            border-top: 2px solid #ead8df;
        }

        .carrito-total-precio { color: #800020; font-family: "Playfair Display", serif; font-size: 1.2rem; }

        .btn-finalizar {
            background: linear-gradient(135deg, #800020 0%, #5b0e1f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            width: 100%;
            font-family: "Josefin Sans", sans-serif;
            font-size: 0.82rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
            margin-top: 1rem;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-finalizar:hover  { opacity: 0.9; color: #f5d98b; }
        .btn-finalizar:active { transform: scale(0.98); }
        .btn-finalizar:disabled { opacity: 0.5; cursor: not-allowed; }

        /* ── Modal de éxito ── */
        .modal-exito-body { text-align: center; padding: 2rem 1.5rem; }
        .num-pedido-grande {
            font-family: "Playfair Display", serif;
            font-size: 3rem;
            color: #800020;
            background: #fdf8f2;
            border: 3px dashed #d4a5b0;
            border-radius: 16px;
            padding: 1rem 2rem;
            display: inline-block;
            margin: 1rem 0;
            letter-spacing: 4px;
        }

        /* ── Notas del pedido ── */
        .notas-textarea {
            border: 1.5px solid #d4a5b0;
            border-radius: 10px;
            padding: 0.6rem 1rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 0.85rem;
            color: #5a4b3b;
            background: #fdf8f2;
            width: 100%;
            resize: vertical;
            min-height: 70px;
        }
        .notas-textarea:focus {
            outline: none;
            border-color: #800020;
            box-shadow: 0 0 0 3px rgba(128,0,32,0.08);
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
            <li><a href="index.php"    class="nav-link nav-link-vino">Inicio</a></li>
            <li><a href="trabajos.php" class="nav-link nav-link-vino">Trabajos</a></li>
            <li><a href="productos.php" class="nav-link nav-link-vino fw-bold" style="color:#f5d98b !important;">Productos</a></li>
            <?php if ($logueado): ?>
                <li><a href="dashboard_cliente.php" class="nav-link nav-link-vino">Mi cuenta</a></li>
                <li><a href="logout.php" class="nav-link nav-link-vino">Salir</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link nav-link-vino">Iniciar sesión</a></li>
            <?php endif; ?>
        </ul>
        <?php if ($logueado): ?>
        <div class="d-flex align-items-center gap-2 d-md-none">
            <span style="color:#f5d98b; font-size:0.8rem;">
                <?= htmlspecialchars($usuario['nombre']) ?>
            </span>
            <a href="logout.php" style="color:#d4a5b0; font-size:0.8rem;">Salir</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<main class="container mt-4 mb-5">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php" class="text-decoration-none" style="color:#800020;">Inicio</a>
            </li>
            <li class="breadcrumb-item active">Catálogo de Productos</li>
        </ol>
    </nav>

    <div class="text-center mb-4">
        <h2 class="texto-bordeo display-4">Nuestros Productos</h2>
        <p class="lead">Materiales e insumos premium para laboratorios dentales.</p>
        <hr class="mx-auto" style="width:60px; border:2px solid #800020; opacity:1;">
    </div>

    <div class="row g-4">

        <!-- Columna izquierda: catálogo -->
        <div class="col-lg-8">
            <?php foreach ($por_categoria as $categoria => $items): ?>
                <h5 class="categoria-titulo"><?= htmlspecialchars($categoria) ?></h5>
                <div class="row g-3">
                    <?php foreach ($items as $p): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="producto-card">
                            <?php if ($p['imagen_url']): ?>
                                <img
                                    src="<?= htmlspecialchars($p['imagen_url']) ?>"
                                    alt="<?= htmlspecialchars($p['nombre']) ?>"
                                    class="producto-img"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                                <div class="producto-img-placeholder" style="display:none;"></div>
                            <?php else: ?>
                                <div class="producto-img-placeholder"></div>
                            <?php endif; ?>

                            <div class="producto-body">
                                <div class="producto-categoria"><?= htmlspecialchars($p['categoria'] ?? '') ?></div>
                                <div class="producto-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                                <div class="producto-desc"><?= htmlspecialchars($p['descripcion'] ?? '') ?></div>
                                <div class="producto-footer">
                                    <div class="producto-precio"><?= number_format($p['precio'], 2) ?> €</div>
                                    <button
                                        class="btn-agregar"
                                        onclick="agregarAlCarrito(<?= $p['id'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['precio'] ?>)"
                                        id="btn-<?= $p['id'] ?>"
                                    >
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

        <!-- Columna derecha: carrito sticky -->
        <div class="col-lg-4">
            <div class="carrito-box">
                <div class="carrito-titulo">
                    🛒 Carrito
                    <span id="carrito-contador"
                          style="background:#800020; color:#fff; border-radius:50%; width:22px; height:22px; font-size:0.75rem; display:inline-flex; align-items:center; justify-content:center; display:none;">
                        0
                    </span>
                </div>

                <div id="carrito-contenido">
                    <div class="carrito-vacio" id="carrito-vacio">
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

                <!-- Notas opcionales -->
                <div id="notas-wrapper" style="display:none; margin-top:1rem;">
                    <label style="font-size:0.75rem; letter-spacing:1px; text-transform:uppercase; color:#a07a8a; margin-bottom:0.4rem; display:block;">
                        Notas para el laboratorio (opcional)
                    </label>
                    <textarea
                        id="notas-pedido"
                        class="notas-textarea"
                        placeholder="Indicaciones especiales, referencias de paciente..."
                    ></textarea>
                </div>

                <button
                    class="btn-finalizar"
                    id="btn-finalizar"
                    onclick="finalizarPedido()"
                    disabled
                >
                    Finalizar Pedido
                </button>
            </div>
        </div>

    </div>
</main>

<!-- Modal de éxito -->
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
                <p class="text-muted mt-2" style="font-size:0.82rem;">
                    Guarda este número para consultar el estado de tu pedido en cualquier momento.
                </p>
                <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                    <a id="btn-ver-estado" href="#" class="btn btn-bordeo px-4">
                        Ver estado del pedido
                    </a>
                    <a href="dashboard_cliente.php" class="btn btn-outline-secondary px-4">
                        Mis pedidos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer-vino shadow-sm">
    <div class="container footer-container">
        <a href="index.php" class="footer-brand">Casa Denise</a>
        <p class="footer-text">© 2024 Laboratorio Dental - Todos los derechos reservados</p>
        <div class="footer-text">
            <span>📍 C/Benito Pérez Galdós 11</span>
            <span class="ms-3">📞 +34 1234-5678</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
//  Carrito — lógica cliente
// ============================================================

// Estado de sesión inyectado desde PHP
const LOGUEADO = <?= $logueado ? 'true' : 'false' ?>;

let carrito = {}; // { id: { nombre, precio, cantidad } }

function agregarAlCarrito(id, nombre, precio) {
    if (carrito[id]) {
        carrito[id].cantidad++;
    } else {
        carrito[id] = { nombre, precio, cantidad: 1 };
    }
    renderCarrito();

    // Feedback visual en el botón
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
    const contenido  = document.getElementById('carrito-contenido');
    const vacio      = document.getElementById('carrito-vacio');
    const totalWrap  = document.getElementById('carrito-total-wrapper');
    const notasWrap  = document.getElementById('notas-wrapper');
    const btnFin     = document.getElementById('btn-finalizar');
    const contador   = document.getElementById('carrito-contador');
    const totalEl    = document.getElementById('carrito-total');

    const ids = Object.keys(carrito);

    if (ids.length === 0) {
        contenido.innerHTML = `
            <div class="carrito-vacio" id="carrito-vacio">
                <div style="font-size:2rem; margin-bottom:0.5rem;">🛒</div>
                Aún no has añadido productos.
            </div>`;
        totalWrap.style.display  = 'none';
        notasWrap.style.display  = 'none';
        btnFin.disabled          = true;
        contador.style.display   = 'none';
        return;
    }

    let html  = '';
    let total = 0;
    let numItems = 0;

    ids.forEach(id => {
        const item     = carrito[id];
        const subtotal = item.precio * item.cantidad;
        total         += subtotal;
        numItems      += item.cantidad;

        html += `
        <div class="carrito-item">
            <div class="carrito-item-nombre">${item.nombre}</div>
            <div class="carrito-item-controles">
                <button class="btn-cantidad" onclick="cambiarCantidad(${id}, -1)">−</button>
                <span class="cantidad-num">${item.cantidad}</span>
                <button class="btn-cantidad" onclick="cambiarCantidad(${id}, +1)">+</button>
            </div>
            <div class="carrito-item-precio">${subtotal.toFixed(2)} €</div>
        </div>`;
    });

    contenido.innerHTML          = html;
    totalEl.textContent          = total.toFixed(2).replace('.', ',') + ' €';
    totalWrap.style.display      = 'block';
    notasWrap.style.display      = 'block';
    btnFin.disabled              = false;
    contador.style.display       = 'inline-flex';
    contador.textContent         = numItems;
}

async function finalizarPedido() {

    // Si no está logueado, redirigir al login guardando la URL actual
    if (!LOGUEADO) {
        window.location.href = 'login.php?redirect=productos.php';
        return;
    }

    const btn = document.getElementById('btn-finalizar');
    btn.disabled     = true;
    btn.textContent  = 'Procesando...';

    const notas = document.getElementById('notas-pedido').value.trim();

    const items = Object.entries(carrito).map(([id, item]) => ({
        catalogo_id:    parseInt(id),
        cantidad:       item.cantidad,
        precio_unitario: item.precio
    }));

    try {
        const resp = await fetch('procesar_pedido.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ items, notas })
        });

        const data = await resp.json();

        if (data.ok) {
            // Mostrar modal de éxito con número de pedido
            document.getElementById('modal-num-pedido').textContent = '#' + data.pedido_id;
            document.getElementById('btn-ver-estado').href =
                'buscar_pedido.php?id=' + data.pedido_id;

            const modal = new bootstrap.Modal(document.getElementById('modalExito'));
            modal.show();

            // Vaciar carrito
            carrito = {};
            renderCarrito();
        } else {
            alert('Error: ' + (data.error || 'No se pudo procesar el pedido.'));
            btn.disabled    = false;
            btn.textContent = 'Finalizar Pedido';
        }

    } catch (e) {
        alert('Error de conexión. Inténtalo de nuevo.');
        btn.disabled    = false;
        btn.textContent = 'Finalizar Pedido';
    }
}
</script>
</body>
</html>
