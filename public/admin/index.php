<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/admin_header.php';

$todaySales = db()->query("SELECT COALESCE(SUM(total),0) as val FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetch()['val'];
$monthSales = db()->query("SELECT COALESCE(SUM(total),0) as val FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'")->fetch()['val'];
$totalProducts = db()->query("SELECT COUNT(*) as val FROM products WHERE active = 1")->fetch()['val'];
$lowStock = db()->query("SELECT COUNT(*) as val FROM products WHERE stock_qty <= 10 AND active = 1")->fetch()['val'];
$pendingOrders = db()->query("SELECT COUNT(*) as val FROM orders WHERE status = 'pending'")->fetch()['val'];
$totalCustomers = db()->query("SELECT COUNT(*) as val FROM customers")->fetch()['val'];
$totalOrders = db()->query("SELECT COUNT(*) as val FROM orders WHERE status IN ('paid','completed')")->fetch()['val'];

$recentOrders = db()->query("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();
$lowStockProducts = db()->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_qty <= 10 AND p.active = 1 ORDER BY p.stock_qty ASC LIMIT 5")->fetchAll();
?>

<div class="welcome-banner">
    <div style="position:relative;z-index:1">
        <h2 style="font-size:1.35rem;font-weight:700;margin-bottom:4px">สวัสดี, <?= sanitize(currentUser()['name']) ?></h2>
        <p style="opacity:.8;font-size:.95rem">ยินดีต้อนรับสู่ระบบจัดการ G2K Solar &amp; Water Pump</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><?= icon('wallet', 22) ?></div>
        <div class="stat-info">
            <div class="label">ยอดขายวันนี้</div>
            <div class="value"><?= formatCurrency($todaySales) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><?= icon('chart', 22) ?></div>
        <div class="stat-info">
            <div class="label">ยอดขายเดือนนี้</div>
            <div class="value"><?= formatCurrency($monthSales) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><?= icon('package', 22) ?></div>
        <div class="stat-info">
            <div class="label">สินค้าทั้งหมด</div>
            <div class="value"><?= number_format($totalProducts) ?></div>
            <div class="change <?= $lowStock > 0 ? 'down' : 'up' ?>"><?= $lowStock > 0 ? icon('alert', 14).' '.$lowStock.' รายการใกล้หมด' : icon('check', 14).' สต็อกปกติ' ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><?= icon('cart', 22) ?></div>
        <div class="stat-info">
            <div class="label">คำสั่งซื้อทั้งหมด</div>
            <div class="value"><?= number_format($totalOrders) ?></div>
            <div class="change"><?= $pendingOrders > 0 ? icon('clock', 14).' '.$pendingOrders.' รอดำเนินการ' : icon('check', 14).' ไม่มีค้าง' ?></div>
        </div>
    </div>
</div>

<div class="dash-split">
    <div class="card">
        <div class="card-header">
            <h3><?= icon('file') ?> คำสั่งซื้อล่าสุด</h3>
            <a href="<?= url('/admin/orders.php') ?>" class="btn btn-sm btn-outline">ดูทั้งหมด <?= icon('chevron-right', 14) ?></a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>หมายเลข</th><th>ลูกค้า</th><th>ประเภท</th><th>ยอดรวม</th><th>สถานะ</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px">ยังไม่มีคำสั่งซื้อ</td></tr>
                    <?php else: foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><strong><?= sanitize($o['order_no']) ?></strong></td>
                            <td><?= sanitize($o['customer_name'] ?? 'Walk-in') ?></td>
                            <td><span class="badge badge-info"><?= $o['type'] ?></span></td>
                            <td><strong><?= formatCurrency($o['total']) ?></strong></td>
                            <td>
                                <?php
                                $statusClass = match($o['status']) {
                                    'completed' => 'success', 'paid' => 'success',
                                    'pending' => 'warning', 'cancelled' => 'danger',
                                    default => 'gray'
                                };
                                $statusText = match($o['status']) {
                                    'completed' => 'เสร็จสิ้น', 'paid' => 'ชำระแล้ว',
                                    'pending' => 'รอดำเนินการ', 'cancelled' => 'ยกเลิก',
                                    default => $o['status']
                                };
                                ?>
                                <span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><?= icon('alert') ?> สินค้าใกล้หมด</h3>
        </div>
        <div class="card-body">
            <?php if (empty($lowStockProducts)): ?>
                <div class="empty-state" style="padding:28px;color:var(--success)">
                    <?= icon('check-circle', 32) ?>
                    <p style="font-weight:600;margin-top:10px">สต็อกสินค้าปกติ</p>
                </div>
            <?php else: foreach ($lowStockProducts as $p): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--gray-100)">
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:var(--gray-900)"><?= sanitize($p['name']) ?></div>
                        <div style="font-size:.76rem;color:var(--gray-500)"><?= sanitize($p['cat_name'] ?? '') ?></div>
                    </div>
                    <span class="badge badge-danger">เหลือ <?= $p['stock_qty'] ?> <?= $p['unit'] ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
