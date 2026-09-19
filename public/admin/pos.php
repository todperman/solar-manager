<?php
$pageTitle = 'POS หน้าร้าน';
include __DIR__ . '/../includes/admin_header.php';

$products = db()->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.active=1 AND p.stock_qty > 0 ORDER BY p.name")->fetchAll();
$customers = db()->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'complete') {
        $customer_id = intval($_POST['customer_id'] ?? 0) ?: null;
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $discount = floatval($_POST['discount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $items = json_decode($_POST['items_json'] ?? '[]', true);

        if (empty($items)) {
            flash('error', 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ');
            redirect('/admin/pos.php');
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        $tax = ($subtotal - $discount) * TAX_RATE / 100;
        $total = $subtotal - $discount + $tax;
        $order_no = generateOrderNo('ORD');

        db()->beginTransaction();
        try {
            $stmt = db()->prepare("INSERT INTO orders (order_no, customer_id, user_id, type, status, subtotal, discount, tax_rate, tax, total, payment_method, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$order_no, $customer_id, $_SESSION['user_id'], 'pos', 'paid', $subtotal, $discount, TAX_RATE, $tax, $total, $payment_method, $notes]);
            $orderId = db()->lastInsertId();

            foreach ($items as $item) {
                db()->prepare("INSERT INTO order_items (order_id, product_id, name, qty, price, total) VALUES (?,?,?,?,?,?)")
                    ->execute([$orderId, $item['id'], $item['name'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
                db()->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?")->execute([$item['qty'], $item['id']]);
            }

            db()->commit();
            flash('success', "บันทึกคำสั่งซื้อ {$order_no} เรียบร้อย ยอดรวม " . formatCurrency($total));
            redirect('/admin/orders.php');
        } catch (Exception $e) {
            db()->rollBack();
            flash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
            redirect('/admin/pos.php');
        }
    }
}
?>

<style>
.pos-layout { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.pos-item {
    background: var(--white); border: 2px solid var(--gray-200); border-radius: var(--radius);
    padding: 16px; text-align: center; cursor: pointer; transition: var(--transition);
}
.pos-item:hover { border-color: var(--primary); box-shadow: var(--shadow-md); }
.pos-item .icon { display:flex; align-items:center; justify-content:center; margin-bottom: 8px; color: var(--estate); }
.pos-item .icon .ico { width: 28px; height: 28px; }
.pos-item .name { font-size: .85rem; font-weight: 600; margin-bottom: 4px; }
.pos-item .price { font-size: .95rem; color: var(--primary); font-weight: 700; font-family: var(--font-en); }
.pos-item .stock { font-size: .75rem; color: var(--gray-500); }
.cart-panel { background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200); position: sticky; top: 80px; max-height: calc(100vh - 100px); overflow-y: auto; }
.cart-panel .header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
.cart-panel .header > span:first-child { display: inline-flex; align-items: center; gap: 8px; }
.cart-panel .items { padding: 12px 20px; }
.cart-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
.cart-item .info { flex: 1; }
.cart-item .info .name { font-size: .85rem; font-weight: 600; }
.cart-item .info .price { font-size: .82rem; color: var(--gray-500); }
.cart-item .qty { display: flex; align-items: center; gap: 8px; }
.cart-item .qty button { width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--gray-300); background: var(--white); cursor: pointer; font-weight: 700; }
.cart-item .qty span { font-weight: 600; min-width: 24px; text-align: center; }
.cart-item .remove { background: none; border: none; color: var(--danger); cursor: pointer; display: inline-flex; align-items: center; }
.cart-totals { padding: 16px 20px; border-top: 2px solid var(--gray-200); }
.cart-totals .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: .9rem; }
.cart-totals .row.total { font-size: 1.1rem; font-weight: 800; color: var(--gray-900); border-top: 2px solid var(--gray-200); padding-top: 12px; margin-top: 8px; }
.cart-checkout { padding: 16px 20px; }
.pos-search { margin-bottom: 16px; }
</style>

<div class="pos-layout">
    <!-- Products -->
    <div>
        <div class="pos-search">
            <div class="search-box">
                <span class="icon"><?= icon('search', 16) ?></span>
                <input type="text" placeholder="ค้นหาสินค้า..." id="posSearch" onkeyup="filterPOS()" style="font-size:1rem;padding:14px 16px 14px 42px">
            </div>
        </div>
        <div class="product-grid" id="posGrid">
            <?php foreach ($products as $p): ?>
            <div class="pos-item" onclick='addToCart(<?= json_encode(["id"=>$p["id"],"name"=>$p["name"],"price"=>floatval($p["price"]),"stock"=>$p["stock_qty"],"unit"=>$p["unit"]]) ?>)' data-name="<?= strtolower(sanitize($p['name'])) ?>">
                <div class="icon"><?= icon('package', 28) ?></div>
                <div class="name"><?= sanitize($p['name']) ?></div>
                <div class="price"><?= formatCurrency($p['price']) ?></div>
                <div class="stock">คงเหลือ <?= $p['stock_qty'] ?> <?= $p['unit'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart -->
    <div class="cart-panel">
        <div class="header">
            <span><?= icon('cart', 18) ?> ตะกร้าสินค้า</span>
            <span id="cartCount">0 รายการ</span>
        </div>
        <div class="items" id="cartItems">
            <div style="text-align:center;padding:40px 20px;color:var(--gray-400)">เลือกสินค้าจากรายการด้านซ้าย</div>
        </div>
        <div class="cart-totals">
            <div class="row"><span>รวมก่อนลด</span><span id="cartSubtotal">฿0.00</span></div>
            <div class="row" style="align-items:center"><span>ส่วนลด</span><input type="number" id="cartDiscount" value="0" min="0" step="0.01" style="width:100px;text-align:right;padding:4px 8px;border:1px solid var(--gray-300);border-radius:6px" onchange="updateCart()"></div>
            <div class="row"><span>ภาษี VAT <?= TAX_RATE ?>%</span><span id="cartTax">฿0.00</span></div>
            <div class="row total"><span>รวมทั้งหมด</span><span id="cartTotal" style="color:var(--primary)">฿0.00</span></div>
        </div>
        <div class="cart-checkout">
            <form method="POST" id="posForm">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="items_json" id="itemsJson" value="[]">
                <input type="hidden" name="discount" id="discountInput" value="0">
                <div class="form-group">
                    <label>ลูกค้า</label>
                    <select name="customer_id" class="form-control">
                        <option value="0">Walk-in (ไม่ระบุชื่อ)</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['name'].' '.$c['phone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ช่องทางชำระเงิน</label>
                    <select name="payment_method" class="form-control">
                        <option value="cash">เงินสด</option>
                        <option value="transfer">โอนเงิน</option>
                        <option value="credit">บัตรเครดิต</option>
                        <option value="qr">QR Code</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-lg" style="width:100%;justify-content:center" onclick="return confirmCheckout()"><?= icon('check', 16) ?> ชำระเงิน</button>
            </form>
        </div>
    </div>
</div>

<script>
const xIcon = <?= json_encode(icon('x', 14)) ?>;
let cart = [];
function addToCart(item) {
    const existing = cart.find(c => c.id === item.id);
    if (existing) {
        if (existing.qty >= item.stock) { alert('สินค้าไม่พอในสต็อก'); return; }
        existing.qty++;
    } else {
        cart.push({...item, qty: 1});
    }
    renderCart();
}
function removeFromCart(idx) { cart.splice(idx, 1); renderCart(); }
function changeQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) cart.splice(idx, 1);
    renderCart();
}
function renderCart() {
    const el = document.getElementById('cartItems');
    if (cart.length === 0) {
        el.innerHTML = '<div style="text-align:center;padding:40px 20px;color:var(--gray-400)">เลือกสินค้าจากรายการด้านซ้าย</div>';
    } else {
        el.innerHTML = cart.map((c, i) => `
            <div class="cart-item">
                <div class="info"><div class="name">${c.name}</div><div class="price">฿${(c.price*c.qty).toLocaleString()}</div></div>
                <div class="qty">
                    <button onclick="changeQty(${i},-1)">-</button>
                    <span>${c.qty}</span>
                    <button onclick="changeQty(${i},1)">+</button>
                </div>
                <button class="remove" onclick="removeFromCart(${i})">${xIcon}</button>
            </div>
        `).join('');
    }
    updateCart();
}
function updateCart() {
    const subtotal = cart.reduce((s, c) => s + c.price * c.qty, 0);
    const discount = parseFloat(document.getElementById('cartDiscount').value) || 0;
    const taxable = Math.max(0, subtotal - discount);
    const tax = taxable * <?= TAX_RATE ?> / 100;
    const total = taxable + tax;
    document.getElementById('cartCount').textContent = cart.length + ' รายการ';
    document.getElementById('cartSubtotal').textContent = '฿' + subtotal.toLocaleString('th', {minimumFractionDigits:2});
    document.getElementById('cartTax').textContent = '฿' + tax.toLocaleString('th', {minimumFractionDigits:2});
    document.getElementById('cartTotal').textContent = '฿' + total.toLocaleString('th', {minimumFractionDigits:2});
    document.getElementById('itemsJson').value = JSON.stringify(cart);
    document.getElementById('discountInput').value = discount;
}
function confirmCheckout() {
    if (cart.length === 0) { alert('กรุณาเลือกสินค้า'); return false; }
    return confirm('ยืนยันการชำระเงิน?');
}
function filterPOS() {
    const q = document.getElementById('posSearch').value.toLowerCase();
    document.querySelectorAll('#posGrid .pos-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
