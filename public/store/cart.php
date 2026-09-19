<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Add package to cart
if (isset($_GET['add_package'])) {
    $pkgId = intval($_GET['add_package']);
    $pkg = db()->prepare("SELECT * FROM packages WHERE id=? AND active=1");
    $pkg->execute([$pkgId]);
    $pkg = $pkg->fetch();
    if ($pkg) {
        $_SESSION['cart'][] = ['type'=>'package', 'id'=>$pkg['id'], 'name'=>$pkg['name'], 'price'=>floatval($pkg['price']), 'qty'=>1];
    }
    redirect('/store/cart.php');
    exit;
}

// Remove item from cart
if (isset($_GET['remove'])) {
    $idx = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$idx])) {
        array_splice($_SESSION['cart'], $idx, 1);
    }
    redirect('/store/cart.php');
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = 'กรุณากรอกชื่อและเบอร์โทรศัพท์';
    } elseif (empty($_SESSION['cart'])) {
        $error = 'ตะกร้าสินค้าว่างเปล่า';
    } else {
        // Create or find customer
        $custStmt = db()->prepare("SELECT id FROM customers WHERE phone=?");
        $custStmt->execute([$phone]);
        $cust = $custStmt->fetch();
        if ($cust) {
            $customerId = $cust['id'];
            db()->prepare("UPDATE customers SET name=?,email=?,address=? WHERE id=?")->execute([$name,$email,$address,$customerId]);
        } else {
            db()->prepare("INSERT INTO customers (name,phone,email,address,source) VALUES (?,?,?,?,?)")->execute([$name,$phone,$email,$address,'store']);
            $customerId = db()->lastInsertId();
        }

        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        $tax = $subtotal * TAX_RATE / 100;
        $total = $subtotal + $tax;
        $order_no = generateOrderNo('ORD');

        db()->beginTransaction();
        try {
            $stmt = db()->prepare("INSERT INTO orders (order_no, customer_id, type, status, subtotal, tax_rate, tax, total, notes) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$order_no, $customerId, 'store', 'pending', $subtotal, TAX_RATE, $tax, $total, $notes]);
            $orderId = db()->lastInsertId();

            foreach ($_SESSION['cart'] as $item) {
                db()->prepare("INSERT INTO order_items (order_id, product_id, package_id, name, qty, price, total) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$orderId, $item['type']==='product' ? $item['id'] : null, $item['type']==='package' ? $item['id'] : null, $item['name'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
            }

            db()->commit();
            $_SESSION['cart'] = [];
            redirect('/store/order-success.php?order=' . urlencode($order_no));
            exit;
        } catch (Exception $e) {
            db()->rollBack();
            $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) $subtotal += $item['price'] * $item['qty'];
$tax = $subtotal * TAX_RATE / 100;
$total = $subtotal + $tax;
$cartCount = count($cart);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | G2K</title>
    <link rel="icon" href="<?= url('/assets/img/g2k-logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/store_nav.php'; ?>

<section class="page-shell">
    <div class="container" style="max-width:1000px">
        <h1 style="font-size:1.7rem;font-weight:700;margin-bottom:28px;color:var(--gray-900)">ตะกร้าสินค้า</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= icon('alert', 16) ?> <?= sanitize($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center;padding:60px">
                    <div style="margin-bottom:16px;color:var(--gray-400)"><?= icon('cart', 40) ?></div>
                    <h3 style="color:var(--gray-700);margin-bottom:8px">ตะกร้าสินค้าว่างเปล่า</h3>
                    <p style="color:var(--gray-500);margin-bottom:24px">ยังไม่มีสินค้าในตะกร้า เลือกซื้อสินค้าเลย!</p>
                    <a href="<?= url('/store/') ?>" class="btn btn-primary"><?= icon('shopping-bag', 16) ?> เลือกซื้อสินค้า</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="card">
                    <div class="card-header"><h3>สินค้าในตะกร้า (<?= $cartCount ?> รายการ)</h3></div>
                    <div class="card-body">
                        <?php foreach ($cart as $i => $item): ?>
                        <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid var(--gray-100)">
                            <div style="width:56px;height:56px;border-radius:12px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">
                                <?= $item['type'] === 'package' ? icon('package', 22) : icon('wrench', 22) ?>
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600"><?= sanitize($item['name']) ?></div>
                                <div style="color:var(--primary);font-weight:700;font-family:var(--font-en)"><?= formatCurrency($item['price']) ?></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="color:var(--gray-500)">x<?= (int) $item['qty'] ?></span>
                                <strong><?= formatCurrency($item['price'] * $item['qty']) ?></strong>
                            </div>
                            <a href="<?= url('/store/cart.php') ?>?remove=<?= $i ?>" style="color:var(--danger)" onclick="return confirm('ลบสินค้านี้?')"><?= icon('x', 16) ?></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="card" style="position:sticky;top:80px">
                    <div class="card-header"><h3>สรุปคำสั่งซื้อ</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row" style="display:flex;justify-content:space-between;padding:8px 0;font-size:.9rem"><span>รวมก่อนภาษี</span><span><?= formatCurrency($subtotal) ?></span></div>
                            <div class="row" style="display:flex;justify-content:space-between;padding:8px 0;font-size:.9rem"><span>ภาษี VAT <?= TAX_RATE ?>%</span><span><?= formatCurrency($tax) ?></span></div>
                            <div class="row" style="display:flex;justify-content:space-between;padding:12px 0;font-size:1.1rem;font-weight:800;border-top:2px solid var(--gray-200);margin-top:8px"><span>รวมทั้งหมด</span><span style="color:var(--primary)"><?= formatCurrency($total) ?></span></div>

                            <div style="margin-top:24px">
                                <div class="form-group"><label>ชื่อ-นามสกุล <span style="color:var(--danger)">*</span></label><input type="text" name="name" class="form-control" required></div>
                                <div class="form-group"><label>เบอร์โทรศัพท์ <span style="color:var(--danger)">*</span></label><input type="tel" name="phone" class="form-control" required></div>
                                <div class="form-group"><label>อีเมล</label><input type="email" name="email" class="form-control"></div>
                                <div class="form-group"><label>ที่อยู่จัดส่ง</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                                <div class="form-group"><label>หมายเหตุ</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg" style="width:100%;justify-content:center"><?= icon('check', 16) ?> ยืนยันคำสั่งซื้อ</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/store_footer.php'; ?>
</body>
</html>
