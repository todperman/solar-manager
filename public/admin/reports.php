<?php
$pageTitle = 'รายงาน';
include __DIR__ . '/../includes/admin_header.php';

$period = $_GET['period'] ?? 'month';
$isDate = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
$dateFrom = $isDate($_GET['from'] ?? null) ? $_GET['from'] : date('Y-m-01');
$dateTo = $isDate($_GET['to'] ?? null) ? $_GET['to'] : date('Y-m-t');

// Sales summary
$sales = db()->prepare("SELECT COUNT(*) as orders, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(discount),0) as discount, COALESCE(SUM(tax),0) as tax, COALESCE(SUM(total),0) as total FROM orders WHERE status IN ('paid','completed') AND DATE(created_at) BETWEEN ? AND ?");
$sales->execute([$dateFrom, $dateTo]);
$salesData = $sales->fetch();

// Profit
$profit = db()->prepare("SELECT COALESCE(SUM(oi.total - (p.cost * oi.qty)),0) as profit FROM order_items oi JOIN orders o ON oi.order_id=o.id LEFT JOIN products p ON oi.product_id=p.id WHERE o.status IN ('paid','completed') AND DATE(o.created_at) BETWEEN ? AND ?");
$profit->execute([$dateFrom, $dateTo]);
$profitData = $profit->fetch();

// Top products
$topProducts = db()->prepare("SELECT p.name, SUM(oi.qty) as sold, SUM(oi.total) as revenue FROM order_items oi JOIN orders o ON oi.order_id=o.id JOIN products p ON oi.product_id=p.id WHERE o.status IN ('paid','completed') AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 10");
$topProducts->execute([$dateFrom, $dateTo]);
$topProducts = $topProducts->fetchAll();

// Sales by type
$salesByType = db()->prepare("SELECT type, COUNT(*) as cnt, SUM(total) as total FROM orders WHERE status IN ('paid','completed') AND DATE(created_at) BETWEEN ? AND ? GROUP BY type");
$salesByType->execute([$dateFrom, $dateTo]);
$salesByType = $salesByType->fetchAll();

// Daily sales
$dailySales = db()->prepare("SELECT DATE(created_at) as day, COUNT(*) as cnt, SUM(total) as total FROM orders WHERE status IN ('paid','completed') AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day");
$dailySales->execute([$dateFrom, $dateTo]);
$dailySales = $dailySales->fetchAll();
?>

<div class="filters-bar">
    <form method="GET" style="display:flex;gap:12px;align-items:center">
        <input type="hidden" name="period" value="custom">
        <label style="font-weight:600;font-size:.9rem">ตั้งแต่</label>
        <input type="date" name="from" class="form-control" value="<?= sanitize($dateFrom) ?>" onchange="this.form.submit()" id="dateFrom">
        <label style="font-weight:600;font-size:.9rem">ถึง</label>
        <input type="date" name="to" class="form-control" value="<?= sanitize($dateTo) ?>" onchange="this.form.submit()" id="dateTo">
    </form>
    <div class="btn-group">
        <a href="?period=today&from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $period==='today'?'btn-primary':'btn-outline' ?>">วันนี้</a>
        <a href="?period=week&from=<?= date('Y-m-d', strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $period==='week'?'btn-primary':'btn-outline' ?>">7 วัน</a>
        <a href="?period=month&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-t') ?>" class="btn btn-sm <?= $period==='month'?'btn-primary':'btn-outline' ?>">เดือนนี้</a>
        <a href="?period=year&from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-t') ?>" class="btn btn-sm <?= $period==='year'?'btn-primary':'btn-outline' ?>">ปีนี้</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><?= icon('wallet', 22) ?></div>
        <div class="stat-info">
            <div class="label">ยอดขายรวม</div>
            <div class="value"><?= formatCurrency($salesData['total']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><?= icon('bar-chart', 22) ?></div>
        <div class="stat-info">
            <div class="label">กำไร (ประมาณ)</div>
            <div class="value"><?= formatCurrency($profitData['profit']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><?= icon('package', 22) ?></div>
        <div class="stat-info">
            <div class="label">จำนวนคำสั่งซื้อ</div>
            <div class="value"><?= number_format($salesData['orders']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><?= icon('tag', 22) ?></div>
        <div class="stat-info">
            <div class="label">ส่วนลดรวม</div>
            <div class="value"><?= formatCurrency($salesData['discount']) ?></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
    <!-- Top Products -->
    <div class="card">
        <div class="card-header"><h3><?= icon('trophy', 18) ?> สินค้าขายดี</h3></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>สินค้า</th><th>ขายได้</th><th>รายได้</th></tr></thead>
                <tbody>
                    <?php foreach ($topProducts as $i => $tp): ?>
                    <tr>
                        <td><span class="badge badge-info"><?= $i+1 ?></span></td>
                        <td><strong><?= sanitize($tp['name']) ?></strong></td>
                        <td><?= $tp['sold'] ?> ชิ้น</td>
                        <td><strong><?= formatCurrency($tp['revenue']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--gray-400)">ไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales by Type -->
    <div class="card">
        <div class="card-header"><h3><?= icon('chart', 18) ?> ยอดขายตามประเภท</h3></div>
        <div class="card-body">
            <?php foreach ($salesByType as $sbt): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--gray-100)">
                <div>
                    <span class="badge badge-info" style="font-size:.85rem"><?= match($sbt['type']) { 'pos'=>'POS', 'quotation'=>'ใบเสนอราคา', 'store'=>'หน้าร้าน', default=>$sbt['type'] } ?></span>
                    <span style="margin-left:10px;color:var(--gray-500)"><?= $sbt['cnt'] ?> รายการ</span>
                </div>
                <strong style="font-size:1.05rem"><?= formatCurrency($sbt['total']) ?></strong>
            </div>
            <?php endforeach; ?>
            <?php if (empty($salesByType)): ?>
                <div style="text-align:center;padding:30px;color:var(--gray-400)">ไม่มีข้อมูล</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Daily Sales -->
<div class="card" style="margin-top:24px">
    <div class="card-header">
        <h3><?= icon('trending-up', 18) ?> ยอดขายรายวัน</h3>
        <a href="<?= url('/admin/export-report.php') ?>?from=<?= $dateFrom ?>&to=<?= $dateTo ?>" class="btn btn-sm btn-outline"><?= icon('download', 14) ?> Export CSV</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>วันที่</th><th>จำนวนคำสั่งซื้อ</th><th>ยอดขาย</th></tr></thead>
            <tbody>
                <?php foreach ($dailySales as $ds): ?>
                <tr>
                    <td><?= date('d/m/Y (D)', strtotime($ds['day'])) ?></td>
                    <td><?= number_format($ds['cnt']) ?> รายการ</td>
                    <td><strong><?= formatCurrency($ds['total']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($dailySales)): ?>
                    <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--gray-400)">ไม่มีข้อมูลในช่วงเวลานี้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
