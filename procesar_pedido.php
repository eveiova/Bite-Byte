<?php
// ============================================================
//  CASA DENISE - Procesar pedido (API endpoint)
//  Coloca en: tu-proyecto/procesar_pedido.php
//  Solo acepta POST con JSON. Requiere sesión activa.
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// Solo usuarios logueados
if (!estaLogueado()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para hacer un pedido.']);
    exit;
}

// Leer JSON del body
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || empty($body['items']) || !is_array($body['items'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El carrito está vacío.']);
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$notas      = trim($body['notas'] ?? '');
$items      = $body['items'];

// ── Validar y recalcular precios desde la BD ─────────────────
// Nunca fiarse del precio que manda el cliente — siempre
// verificar contra la BD para evitar manipulaciones.

$ids_catalogo = array_map(fn($i) => (int)$i['catalogo_id'], $items);
$placeholders = implode(',', array_fill(0, count($ids_catalogo), '?'));

$stmt = $pdo->prepare("
    SELECT id, nombre, precio, disponible
    FROM catalogo
    WHERE id IN ($placeholders)
");
$stmt->execute($ids_catalogo);
$productos_bd = [];
foreach ($stmt->fetchAll() as $p) {
    $productos_bd[$p['id']] = $p;
}

// Verificar que todos los productos existen y están disponibles
$items_validados = [];
$total           = 0.00;

foreach ($items as $item) {
    $cat_id   = (int)($item['catalogo_id'] ?? 0);
    $cantidad = (int)($item['cantidad']    ?? 0);

    if ($cat_id <= 0 || $cantidad <= 0) continue;

    if (!isset($productos_bd[$cat_id])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "Producto #$cat_id no encontrado."]);
        exit;
    }

    if (!$productos_bd[$cat_id]['disponible']) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "El producto \"{$productos_bd[$cat_id]['nombre']}\" no está disponible."]);
        exit;
    }

    $precio_real = (float) $productos_bd[$cat_id]['precio'];
    $subtotal    = $precio_real * $cantidad;
    $total      += $subtotal;

    $items_validados[] = [
        'catalogo_id'    => $cat_id,
        'cantidad'       => $cantidad,
        'precio_unitario'=> $precio_real,
    ];
}

if (empty($items_validados)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No hay artículos válidos en el carrito.']);
    exit;
}

// ── Insertar pedido y sus líneas en una transacción ──────────
try {
    $pdo->beginTransaction();

    // 1. Crear cabecera del pedido
    $stmt = $pdo->prepare("
        INSERT INTO pedidos (usuario_id, total, estado, notas)
        VALUES (?, ?, 0, ?)
    ");
    $stmt->execute([$usuario_id, $total, $notas ?: null]);
    $pedido_id = (int) $pdo->lastInsertId();

    // 2. Insertar cada línea
    $stmt_item = $pdo->prepare("
        INSERT INTO pedido_items (pedido_id, catalogo_id, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($items_validados as $item) {
        $stmt_item->execute([
            $pedido_id,
            $item['catalogo_id'],
            $item['cantidad'],
            $item['precio_unitario'],
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok'        => true,
        'pedido_id' => $pedido_id,
        'total'     => number_format($total, 2),
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al crear pedido: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno al guardar el pedido.']);
}
