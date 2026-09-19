<?php
$pageTitle = 'ประวัติการสั่งซื้อของลูกค้า';
include __DIR__ . '/../includes/admin_header.php';

$id = intval($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM customers WHERE id=?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    flash('error', 'ไม่พบข้อมูลลูกค้า');
    redirect('/admin/customers.php');
}

$orders = db()->prepare("SELECT o.*, u.name as staff_name, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) as item_count FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.customer_id=? ORDER BY o.created_at DESC");
$orders->execute([$id]);
$orders = $orders->fetchAll();

$totalSpent = 0;
foreach ($orders as $o) {
    if (in_array($o['status'], ['paid', 'completed'], true)) $totalSpent += $o['total'];
}
?>

<div class="filters-bar">
    <a href="<?= url('/admin/customers.php') ?>" class="btn btn-outline">&larr; กลับไปหน้าลูกค้า</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><?= icon('users', 22) ?></div>
        <div class="stat-info">
            <div class="label"><?= sanitize($customer['phone'] ?? '') ?> <?= $customer['email'] ? '· ' . sanitize($customer['email']) : '' ?></div>
            <div class="value" style="font-size:1.2rem"><?= sanitize($customer['name']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><?= icon('file', 22) ?></div>
        <div class="stat-info">
            <div class="label">จำนวนคำสั่งซื้อ / ใบเสนอราคา</div>
            <div class="value"><?= number_format(count($orders)) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><?= icon('wallet', 22) ?></div>
        <div class="stat-info">
            <div class="label">ยอดใช้จ่าย (ชำระแล้ว)</div>
            <div class="value"><?= formatCurrency($totalSpent) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>หมายเลข</th><th>ประเภท</th><th>รายการ</th><th>ยอดรวม</th><th>สถานะ</th><th>วันที่</th><th>จัดการ</th></tr></thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">ลูกค้ารายนี้ยังไม่มีคำสั่งซื้อ</td></tr>
                <?php else: foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?= sanitize($o['order_no']) ?></strong></td>
                    <td><span class="badge badge-info"><?= orderTypeLabel($o['type']) ?></span></td>
                    <td><?= $o['item_count'] ?> รายการ</td>
                    <td><strong><?= formatCurrency($o['total']) ?></strong></td>
                    <td><span class="badge badge-<?= statusBadge($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
                    <td style="white-space:nowrap;font-size:.82rem"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td class="table-actions">
                        <a href="<?= url('/admin/order-detail.php') ?>?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline"><?= icon('eye', 14) ?></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
