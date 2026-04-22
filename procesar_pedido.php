<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para hacer un pedido.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body || empty($body['items'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El carrito está vacío.']);
    exit;
}

$notas = trim($body['notas'] ?? '');
$items = $body['items'];

$ids = array_map(fn($i) => (int)$i['catalogo_id'], $items);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare("SELECT id, nombre, precio, disponible FROM catalogo WHERE id IN ($placeholders)");
$stmt->execute($ids);

$productos = [];
foreach ($stmt->fetchAll() as $p) {
    $productos[$p['id']] = $p;
}

$items_ok = [];
$total = 0;

foreach ($items as $item) {
    $id       = (int)($item['catalogo_id'] ?? 0);
    $cantidad = (int)($item['cantidad'] ?? 0);

    if ($id <= 0 || $cantidad <= 0) continue;

    if (!isset($productos[$id])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "Producto #$id no encontrado."]);
        exit;
    }

    if (!$productos[$id]['disponible']) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "El producto '{$productos[$id]['nombre']}' no está disponible."]);
        exit;
    }

    $precio   = (float) $productos[$id]['precio'];
    $total   += $precio * $cantidad;
    $items_ok[] = ['catalogo_id' => $id, 'cantidad' => $cantidad, 'precio' => $precio];
}

if (empty($items_ok)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No hay artículos válidos en el carrito.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, estado, notas) VALUES (?, ?, 0, ?)");
    $stmt->execute([$_SESSION['id'], $total, $notas ?: null]);
    $pedido_id = (int) $pdo->lastInsertId();

    $stmt2 = $pdo->prepare("INSERT INTO pedido_items (pedido_id, catalogo_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    foreach ($items_ok as $item) {
        $stmt2->execute([$pedido_id, $item['catalogo_id'], $item['cantidad'], $item['precio']]);
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'pedido_id' => $pedido_id, 'total' => number_format($total, 2)]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno al guardar el pedido.']);
}
