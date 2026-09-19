<?php
$pageTitle = 'จัดการคำสั่งซื้อ';
include __DIR__ . '/../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['pending', 'paid', 'completed', 'cancelled'], true)) {
            db()->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
            flash('success', 'อัปเดตสถานะเรียบร้อยแล้ว');
        }
        redirect('/admin/orders.php');
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
$params = [];
if ($status) { $where .= " AND o.status=?"; $params[] = $status; }
if ($type) { $where .= " AND o.type=?"; $params[] = $type; }
if ($search) { $where .= " AND (o.order_no LIKE ? OR c.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = db()->prepare("SELECT COUNT(*) as c FROM orders o LEFT JOIN customers c ON o.customer_id=c.id $where");
$total->execute($params);
$totalPages = ceil($total->fetch()['c'] / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT o.*, c.name as customer_name, u.name as staff_name FROM orders o LEFT JOIN customers c ON o.customer_id=c.id LEFT JOIN users u ON o.user_id=u.id $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="filters-bar">
    <div class="search-box">
        <span class="icon"><?= icon('search', 16) ?></span>
        <input type="text" id="searchBox" placeholder="ค้นหาคำสั่งซื้อ..." value="<?= sanitize($search) ?>" onkeyup="filterTable()">
    </div>
    <select class="form-control" style="width:150px" onchange="location='?status='+this.value+'&type=<?= urlencode($type) ?>'">
        <option value="">ทุกสถานะ</option>
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>รอดำเนินการ</option>
        <option value="paid" <?= $status==='paid'?'selected':'' ?>>ชำระแล้ว</option>
        <option value="completed" <?= $status==='completed'?'selected':'' ?>>เสร็จสิ้น</option>
        <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>ยกเลิก</option>
    </select>
    <select class="form-control" style="width:150px" onchange="location='?status=<?= urlencode($status) ?>&type='+this.value">
        <option value="">ทุกประเภท</option>
        <option value="pos" <?= $type==='pos'?'selected':'' ?>>POS</option>
        <option value="quotation" <?= $type==='quotation'?'selected':'' ?>>ใบเสนอราคา</option>
        <option value="store" <?= $type==='store'?'selected':'' ?>>หน้าร้าน</option>
    </select>
</div>

<div class="card">
    <div class="table-wrapper">
        <table id="dataTable">
            <thead>
                <tr><th>หมายเลข</th><th>ลูกค้า</th><th>ประเภท</th><th>ยอดรวม</th><th>ชำระผ่าน</th><th>สถานะ</th><th>วันที่</th><th>จัดการ</th></tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">ไม่พบคำสั่งซื้อ</td></tr>
                <?php else: foreach ($orders as $o):
                    $statusClass = match($o['status']) { 'completed'=>'success','paid'=>'success','pending'=>'warning','cancelled'=>'danger', default=>'gray' };
                    $statusText = match($o['status']) { 'completed'=>'เสร็จสิ้น','paid'=>'ชำระแล้ว','pending'=>'รอดำเนินการ','cancelled'=>'ยกเลิก', default=>$o['status'] };
                ?>
                <tr>
                    <td><strong><?= sanitize($o['order_no']) ?></strong></td>
                    <td><?= sanitize($o['customer_name'] ?? 'Walk-in') ?></td>
                    <td><span class="badge badge-info"><?= $o['type'] ?></span></td>
                    <td><strong><?= formatCurrency($o['total']) ?></strong></td>
                    <td><?= match($o['payment_method']) { 'cash'=>'เงินสด', 'transfer'=>'โอน', 'credit'=>'บัตร', 'qr'=>'QR', default=>'-' } ?></td>
                    <td><span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span></td>
                    <td style="white-space:nowrap;font-size:.82rem"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td class="table-actions">
                        <?php if ($o['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="status" value="paid"><button class="btn btn-sm btn-success"><?= icon('wallet', 14) ?> รับเงิน</button></form>
                        <?php endif; ?>
                        <?php if (in_array($o['status'], ['pending','paid'])): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-primary"><?= icon('check', 14) ?> เสร็จ</button></form>
                        <?php endif; ?>
                        <a href="<?= url('/admin/order-detail.php') ?>?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline"><?= icon('eye', 14) ?></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&q=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function filterTable() {
    const q = document.getElementById('searchBox')?.value?.toLowerCase() || '';
    document.querySelectorAll('#dataTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
