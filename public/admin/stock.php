<?php
$pageTitle = 'จัดการ Stock สินค้า';
include __DIR__ . '/../includes/admin_header.php';

$products = db()->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.active=1 ORDER BY p.name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $type = $_POST['type']; // in or out
    $qty = intval($_POST['qty']);
    $reference = trim($_POST['reference'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($qty > 0 && in_array($type, ['in', 'out'])) {
        // Update stock
        if ($type === 'in') {
            db()->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?")->execute([$qty, $product_id]);
        } else {
            db()->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?")->execute([$qty, $product_id]);
        }
        // Log history
        db()->prepare("INSERT INTO stock_history (product_id, type, qty, reference, note, user_id) VALUES (?,?,?,?,?,?)")
            ->execute([$product_id, $type, $qty, $reference, $note, $_SESSION['user_id']]);
        flash('success', ($type === 'in' ? 'รับเข้า' : 'เบิกออก') . ' สินค้าเรียบร้อยแล้ว');
        redirect('/admin/stock.php');
    }
}

// Recent history
$history = db()->query("SELECT sh.*, p.name as product_name, u.name as user_name FROM stock_history sh LEFT JOIN products p ON sh.product_id = p.id LEFT JOIN users u ON sh.user_id = u.id ORDER BY sh.created_at DESC LIMIT 20")->fetchAll();
?>

<div class="filters-bar">
    <button class="btn btn-success" onclick="openModal('stockInModal')"><?= icon('download', 16) ?> รับเข้า Stock</button>
    <button class="btn btn-danger" onclick="openModal('stockOutModal')"><?= icon('upload', 16) ?> เบิกออก</button>
</div>

<!-- Current Stock -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3><?= icon('package', 18) ?> สถานะ Stock ปัจจุบัน</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>สินค้า</th><th>SKU</th><th>หมวดหมู่</th><th>Stock</th><th>สถานะ</th></tr></thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><strong><?= sanitize($p['name']) ?></strong></td>
                    <td><code style="font-size:.82rem;background:var(--gray-100);padding:3px 8px;border-radius:4px"><?= sanitize($p['sku']) ?></code></td>
                    <td><?= sanitize($p['cat_name'] ?? '-') ?></td>
                    <td><strong style="font-size:1.1rem"><?= $p['stock_qty'] ?></strong> <?= $p['unit'] ?></td>
                    <td>
                        <?php if ($p['stock_qty'] <= 0): ?>
                            <span class="badge badge-danger">หมด</span>
                        <?php elseif ($p['stock_qty'] <= 10): ?>
                            <span class="badge badge-warning">ใกล้หมด</span>
                        <?php else: ?>
                            <span class="badge badge-success">ปกติ</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- History -->
<div class="card">
    <div class="card-header"><h3><?= icon('clipboard', 18) ?> ประวัติการเคลื่อนไหว Stock</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>วันที่</th><th>สินค้า</th><th>ประเภท</th><th>จำนวน</th><th>อ้างอิง</th><th>หมายเหตุ</th><th>ดำเนินการโดย</th></tr></thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray-400)">ยังไม่มีประวัติ</td></tr>
                <?php else: foreach ($history as $h): ?>
                <tr>
                    <td style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                    <td><?= sanitize($h['product_name'] ?? '-') ?></td>
                    <td><span class="badge <?= $h['type'] === 'in' ? 'badge-success' : 'badge-danger' ?>"><?= $h['type'] === 'in' ? 'รับเข้า' : 'เบิกออก' ?></span></td>
                    <td><strong><?= $h['qty'] ?></strong></td>
                    <td><?= sanitize($h['reference'] ?? '-') ?></td>
                    <td style="max-width:200px"><?= sanitize($h['note'] ?? '-') ?></td>
                    <td><?= sanitize($h['user_name'] ?? '-') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal-overlay" id="stockInModal">
    <div class="modal">
        <div class="modal-header"><h3><?= icon('download', 18) ?> รับเข้า Stock</h3><button class="modal-close" onclick="closeModal('stockInModal')"><?= icon('x', 16) ?></button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="type" value="in">
                <div class="form-group">
                    <label>สินค้า <span class="required">*</span></label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- เลือกสินค้า --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= sanitize($p['name'].' (Stock: '.$p['stock_qty'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>จำนวน <span class="required">*</span></label>
                        <input type="number" name="qty" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>อ้างอิง (เลขที่ใบส่ง)</label>
                        <input type="text" name="reference" class="form-control" placeholder="PO-001">
                    </div>
                </div>
                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('stockInModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-success"><?= icon('download', 16) ?> บันทึกรับเข้า</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Out Modal -->
<div class="modal-overlay" id="stockOutModal">
    <div class="modal">
        <div class="modal-header"><h3><?= icon('upload', 18) ?> เบิกออก</h3><button class="modal-close" onclick="closeModal('stockOutModal')"><?= icon('x', 16) ?></button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="type" value="out">
                <div class="form-group">
                    <label>สินค้า <span class="required">*</span></label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- เลือกสินค้า --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= sanitize($p['name'].' (Stock: '.$p['stock_qty'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>จำนวน <span class="required">*</span></label>
                        <input type="number" name="qty" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>อ้างอิง</label>
                        <input type="text" name="reference" class="form-control" placeholder="ORD-001">
                    </div>
                </div>
                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('stockOutModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-danger"><?= icon('upload', 16) ?> บันทึกเบิกออก</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
