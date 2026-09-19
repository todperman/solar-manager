<?php
$pageTitle = 'ใบเสนอราคา';
include __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../../includes/functions.php';

$customers = db()->query("SELECT id, name, phone, email, address FROM customers ORDER BY name")->fetchAll();
$products = db()->query("SELECT id, name, sku, price FROM products WHERE active=1 ORDER BY name")->fetchAll();
$packages = db()->query("SELECT id, name, price, features FROM packages WHERE active=1 ORDER BY price")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $customer_id = intval($_POST['customer_id']);
        $notes = trim($_POST['notes'] ?? '');
        $discount = floatval($_POST['discount'] ?? 0);
        $package_id = intval($_POST['package_id'] ?? 0);
        $items = json_decode($_POST['items_json'] ?? '[]', true);

        $subtotal = 0;
        if ($package_id > 0) {
            $pkgData = db()->prepare("SELECT * FROM packages WHERE id=?");
            $pkgData->execute([$package_id]);
            $pkg = $pkgData->fetch();
            if ($pkg) $subtotal += $pkg['price'];
        }
        foreach ($items as $item) {
            $subtotal += floatval($item['price']) * intval($item['qty']);
        }
        $tax = ($subtotal - $discount) * TAX_RATE / 100;
        $total = $subtotal - $discount + $tax;
        $order_no = generateOrderNo('QT');

        $stmt = db()->prepare("INSERT INTO orders (order_no, customer_id, user_id, type, status, subtotal, discount, tax_rate, tax, total, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$order_no, $customer_id ?: null, $_SESSION['user_id'], 'quotation', 'pending', $subtotal, $discount, TAX_RATE, $tax, $total, $notes]);
        $orderId = db()->lastInsertId();

        if ($package_id > 0) {
            $pkgData2 = db()->prepare("SELECT name, price FROM packages WHERE id=?");
            $pkgData2->execute([$package_id]);
            $pkg = $pkgData2->fetch();
            db()->prepare("INSERT INTO order_items (order_id, package_id, name, qty, price, total) VALUES (?,?,?,?,?,?)")
                ->execute([$orderId, $package_id, $pkg['name'], 1, $pkg['price'], $pkg['price']]);
        }
        foreach ($items as $item) {
            db()->prepare("INSERT INTO order_items (order_id, product_id, name, qty, price, total) VALUES (?,?,?,?,?,?)")
                ->execute([$orderId, $item['id'], $item['name'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
        }

        flash('success', "สร้างใบเสนอราคา {$order_no} เรียบร้อย");
        redirect('/admin/quotations.php');
    }
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['pending', 'completed', 'cancelled'], true)) {
            db()->prepare("UPDATE orders SET status=? WHERE id=? AND type='quotation'")->execute([$status, intval($_POST['id'])]);
            flash('success', 'อัปเดตสถานะใบเสนอราคาเรียบร้อยแล้ว');
        }
        redirect('/admin/quotations.php');
    }
}

$quotations = db()->query("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id=c.id WHERE o.type='quotation' ORDER BY o.created_at DESC LIMIT 50")->fetchAll();
?>

<div class="filters-bar">
    <button class="btn btn-primary" onclick="openModal('qtModal')">+ สร้างใบเสนอราคาใหม่</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>หมายเลข</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th><th>วันที่</th><th>จัดการ</th></tr></thead>
            <tbody>
                <?php if (empty($quotations)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">ยังไม่มีใบเสนอราคา</td></tr>
                <?php else: foreach ($quotations as $q):
                    $sc = statusBadge($q['status']);
                    $st = match($q['status']) { 'completed'=>'อนุมัติ','paid'=>'ชำระแล้ว','pending'=>'รอพิจารณา','cancelled'=>'ยกเลิก', default=>$q['status'] };
                ?>
                <tr>
                    <td><strong><?= sanitize($q['order_no']) ?></strong></td>
                    <td><?= sanitize($q['customer_name'] ?? '-') ?></td>
                    <td><strong><?= formatCurrency($q['total']) ?></strong></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= $st ?></span></td>
                    <td style="font-size:.82rem"><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                    <td class="table-actions">
                        <a href="<?= url('/admin/quotation-print.php') ?>?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline" target="_blank"><?= icon('printer', 14) ?> พิมพ์</a>
                        <?php if ($q['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $q['id'] ?>"><input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-success"><?= icon('check', 14) ?> อนุมัติ</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="qtModal">
    <div class="modal" style="max-width:700px">
        <div class="modal-header"><h3><?= icon('file-pen', 18) ?> สร้างใบเสนอราคาใหม่</h3><button class="modal-close" onclick="closeModal('qtModal')"><?= icon('x', 16) ?></button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="items_json" id="qtItemsJson" value="[]">
                <div class="form-group">
                    <label>ลูกค้า <span class="required">*</span></label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- เลือกลูกค้า --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['name'].' '.$c['phone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>แพ็กเกจ (ถ้ามี)</label>
                    <select name="package_id" class="form-control" id="qtPackage" onchange="qtCalcTotal()">
                        <option value="0">-- ไม่เลือกแพ็กเกจ --</option>
                        <?php foreach ($packages as $p): ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= sanitize($p['name'].'  '.formatCurrency($p['price'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>สินค้าเพิ่มเติม</label>
                    <div id="qtItems">
                        <div class="qt-item-row" style="display:flex;gap:8px;margin-bottom:8px">
                            <select class="form-control" style="flex:2" onchange="qtCalcTotal()">
                                <option value="">เลือกสินค้า...</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-name="<?= sanitize($p['name']) ?>"><?= sanitize($p['name'].' ('.formatCurrency($p['price']).')') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" class="form-control" style="flex:1" value="1" min="1" onchange="qtCalcTotal()">
                            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove();qtCalcTotal()"><?= icon('x', 14) ?></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addQtItem()">+ เพิ่มสินค้า</button>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>ส่วนลด (฿)</label><input type="number" name="discount" class="form-control" value="0" step="0.01" onchange="qtCalcTotal()"></div>
                    <div class="form-group"><label>รวมทั้งหมด</label><div id="qtTotal" style="font-size:1.3rem;font-weight:800;color:var(--primary);padding:10px 0">฿0.00</div></div>
                </div>
                <div class="form-group"><label>หมายเหตุ</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('qtModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><?= icon('file', 16) ?> สร้างใบเสนอราคา</button>
            </div>
        </form>
    </div>
</div>

<script>
function addQtItem() {
    const src = document.querySelector('.qt-item-row').cloneNode(true);
    src.querySelector('select').value = '';
    src.querySelector('input').value = 1;
    document.getElementById('qtItems').appendChild(src);
}
function qtCalcTotal() {
    let total = 0;
    const pkg = document.getElementById('qtPackage');
    if (pkg.value > 0) total += parseFloat(pkg.options[pkg.selectedIndex].dataset.price || 0);
    document.querySelectorAll('.qt-item-row').forEach(row => {
        const sel = row.querySelector('select');
        const qty = parseInt(row.querySelector('input').value) || 0;
        if (sel.value > 0) total += parseFloat(sel.options[sel.selectedIndex].dataset.price || 0) * qty;
    });
    const discount = parseFloat(document.querySelector('[name="discount"]')?.value || 0);
    const tax = (total - discount) * <?= TAX_RATE ?> / 100;
    const grand = total - discount + tax;
    document.getElementById('qtTotal').textContent = '฿' + grand.toLocaleString('th', {minimumFractionDigits:2});

    // Build items JSON
    const items = [];
    document.querySelectorAll('.qt-item-row').forEach(row => {
        const sel = row.querySelector('select');
        const qty = parseInt(row.querySelector('input').value) || 0;
        if (sel.value > 0 && qty > 0) {
            items.push({id: sel.value, name: sel.options[sel.selectedIndex].dataset.name, price: parseFloat(sel.options[sel.selectedIndex].dataset.price), qty: qty});
        }
    });
    document.getElementById('qtItemsJson').value = JSON.stringify(items);
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
