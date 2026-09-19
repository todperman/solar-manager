<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        // Name and price always come from the database, never from the browser.
        $type = ($input['type'] ?? 'product') === 'package' ? 'package' : 'product';
        $table = $type === 'package' ? 'packages' : 'products';
        $stmt = db()->prepare("SELECT id, name, price FROM $table WHERE id = ? AND active = 1");
        $stmt->execute([intval($input['id'] ?? 0)]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Item not found', 'count' => count($_SESSION['cart'] ?? [])]);
            break;
        }
        $item = [
            'type' => $type,
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'price' => (float) $row['price'],
            'qty' => min(999, max(1, intval($input['qty'] ?? 1)))
        ];
        // Check if already in cart
        $found = false;
        foreach ($_SESSION['cart'] ?? [] as &$ci) {
            if ($ci['id'] == $item['id'] && $ci['type'] === $item['type']) {
                $ci['qty'] += $item['qty'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
        echo json_encode(['success' => true, 'count' => count($_SESSION['cart'])]);
        break;

    case 'remove':
        $idx = intval($input['index'] ?? -1);
        if (isset($_SESSION['cart'][$idx])) {
            array_splice($_SESSION['cart'], $idx, 1);
        }
        echo json_encode(['success' => true, 'count' => count($_SESSION['cart'] ?? [])]);
        break;

    case 'count':
        echo json_encode(['count' => count($_SESSION['cart'] ?? [])]);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'count' => 0]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
