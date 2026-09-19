<?php
$pageTitle = 'รายละเอียดคำสั่งซื้อ';
include __DIR__ . '/../includes/admin_header.php';

$id = intval($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['pending', 'paid', 'completed', 'cancelled'], true)) {
        db()->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
        flash('success', 'อัปเดตสถานะเรียบร้อยแล้ว');
    }
    redirect('/admin/order-detail.php?id=' . $id);
}

$stmt = db()->prepare("SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, u.name as staff_name
    FROM orders o LEFT JOIN customers c ON o.customer_id=c.id LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'ไม่พบคำสั่งซื้อ');
    redirect('/admin/orders.php');
}

$items = db()->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id");
$items->execute([$id]);
$items = $items->fetchAll();
?>

<div class="filters-bar">
    <a href="<?= url('/admin/' . ($order['type'] === 'quotation' ? 'quotations.php' : 'orders.php')) ?>" class="btn btn-outline">&larr; กลับ</a>
    <a href="<?= url('/admin/quotation-print.php') ?>?id=<?= $order['id'] ?>" class="btn btn-outline" target="_blank"><?= icon('printer', 16) ?> พิมพ์เอกสาร</a>
    <?php if ($order['status'] === 'pending'): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="status" value="paid"><button class="btn btn-success"><?= icon('wallet', 16) ?> รับเงิน</button></form>
    <?php endif; ?>
    <?php if (in_array($order['status'], ['pending', 'paid'], true)): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="status" value="completed"><button class="btn btn-primary"><?= icon('check', 16) ?> เสร็จสิ้น</button></form>
        <form method="POST" style="display:inline" onsubmit="return confirm('ยืนยันการยกเลิกคำสั่งซื้อนี้?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="status" value="cancelled"><button class="btn btn-danger"><?= icon('x', 16) ?> ยกเลิก</button></form>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-bottom:24px">
    <div class="card">
        <div class="card-header"><h3><?= icon('file', 18) ?> <?= sanitize($order['order_no']) ?></h3><span class="badge badge-<?= statusBadge($order['status']) ?>"><?= statusLabel($order['status']) ?></span></div>
        <div class="card-body">
            <table>
                <tr><td style="color:var(--gray-500)">ประเภท</td><td><span class="badge badge-info"><?= orderTypeLabel($order['type']) ?></span></td></tr>
                <tr><td style="color:var(--gray-500)">วันที่</td><td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td></tr>
                <tr><td style="color:var(--gray-500)">ชำระผ่าน</td><td><?= paymentLabel($order['payment_method']) ?></td></tr>
                <tr><td style="color:var(--gray-500)">พนักงาน</td><td><?= sanitize($order['staff_name'] ?? '-') ?></td></tr>
                <?php if ($order['notes']): ?><tr><td style="color:var(--gray-500)">หมายเหตุ</td><td><?= nl2br(sanitize($order['notes'])) ?></td></tr><?php endif; ?>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><?= icon('users', 18) ?> ลูกค้า</h3></div>
        <div class="card-body">
            <?php if ($order['customer_id']): ?>
            <table>
                <tr><td style="color:var(--gray-500)">ชื่อ</td><td><strong><?= sanitize($order['customer_name']) ?></strong></td></tr>
                <tr><td style="color:var(--gray-500)">เบอร์โทร</td><td><?= sanitize($order['customer_phone'] ?? '-') ?></td></tr>
                <tr><td style="color:var(--gray-500)">อีเมล</td><td><?= sanitize($order['customer_email'] ?? '-') ?></td></tr>
                <tr><td style="color:var(--gray-500)">ที่อยู่</td><td><?= nl2br(sanitize($order['customer_address'] ?? '-')) ?></td></tr>
            </table>
            <a href="<?= url('/admin/customer-orders.php') ?>?id=<?= $order['customer_id'] ?>" class="btn btn-sm btn-outline" style="margin-top:12px">ประวัติการสั่งซื้อทั้งหมด</a>
            <?php else: ?>
                <div style="color:var(--gray-500)">Walk-in (ไม่ระบุชื่อ)</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><?= icon('package', 18) ?> รายการสินค้า</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>#</th><th>รายการ</th><th style="text-align:right">จำนวน</th><th style="text-align:right">ราคา/หน่วย</th><th style="text-align:right">รวม</th></tr></thead>
            <tbody>
                <?php foreach ($items as $i => $it): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= sanitize($it['name']) ?> <?= $it['package_id'] ? '<span class="badge badge-info">แพ็กเกจ</span>' : '' ?></td>
                    <td style="text-align:right"><?= number_format($it['qty']) ?></td>
                    <td style="text-align:right"><?= formatCurrency($it['price']) ?></td>
                    <td style="text-align:right"><strong><?= formatCurrency($it['total']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--gray-400)">ไม่มีรายการ</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4" style="text-align:right">รวมก่อนลด</td><td style="text-align:right"><?= formatCurrency($order['subtotal']) ?></td></tr>
                <tr><td colspan="4" style="text-align:right">ส่วนลด</td><td style="text-align:right">-<?= formatCurrency($order['discount']) ?></td></tr>
                <tr><td colspan="4" style="text-align:right">ภาษี VAT <?= rtrim(rtrim(number_format($order['tax_rate'], 2), '0'), '.') ?>%</td><td style="text-align:right"><?= formatCurrency($order['tax']) ?></td></tr>
                <tr><td colspan="4" style="text-align:right;font-weight:800">รวมทั้งหมด</td><td style="text-align:right;font-weight:800;color:var(--primary)"><?= formatCurrency($order['total']) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
