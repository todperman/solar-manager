<?php
$pageTitle = 'จัดการลูกค้า (CRM)';
include __DIR__ . '/../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $line_id = trim($_POST['line_id'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $source = trim($_POST['source'] ?? '');

        if ($id > 0) {
            $stmt = db()->prepare("UPDATE customers SET name=?,email=?,phone=?,address=?,province=?,line_id=?,notes=?,source=? WHERE id=?");
            $stmt->execute([$name,$email,$phone,$address,$province,$line_id,$notes,$source,$id]);
        } else {
            $stmt = db()->prepare("INSERT INTO customers (name,email,phone,address,province,line_id,notes,source) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$email,$phone,$address,$province,$line_id,$notes,$source]);
        }
        flash('success', 'บันทึกลูกค้าเรียบร้อยแล้ว');
        redirect('/admin/customers.php');
    }
    if ($action === 'delete') {
        db()->prepare("DELETE FROM customers WHERE id = ?")->execute([intval($_POST['id'])]);
        flash('success', 'ลบลูกค้าเรียบร้อยแล้ว');
        redirect('/admin/customers.php');
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$search = trim($_GET['q'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = db()->prepare("SELECT COUNT(*) as c FROM customers $where");
$total->execute($params);
$totalPages = ceil($total->fetch()['c'] / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT c.*, (SELECT COUNT(*) FROM orders WHERE customer_id=c.id) as order_count, (SELECT COALESCE(SUM(total),0) FROM orders WHERE customer_id=c.id AND status IN ('paid','completed')) as total_spent FROM customers c $where ORDER BY c.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="filters-bar">
    <div class="search-box">
        <span class="icon"><?= icon('search', 16) ?></span>
        <input type="text" placeholder="ค้นหาลูกค้า... (ชื่อ, เบอร์, อีเมล)" value="<?= sanitize($search) ?>" onkeyup="filterTable()">
    </div>
    <button class="btn btn-primary" onclick="openModal('cstmModal');document.getElementById('cstmTitle').textContent='เพิ่มลูกค้าใหม่';resetCstm()">+ เพิ่มลูกค้า</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table id="dataTable">
            <thead><tr><th>ชื่อ</th><th>เบอร์โทร</th><th>อีเมล</th><th>LINE</th><th>จำนวนสั่งซื้อ</th><th>ยอดใช้จ่าย</th><th>วันที่เพิ่ม</th><th>จัดการ</th></tr></thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">ไม่พบลูกค้า</td></tr>
                <?php else: foreach ($customers as $c): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:.85rem;flex-shrink:0"><?= mb_substr($c['name'],0,1) ?></div>
                            <strong><?= sanitize($c['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= sanitize($c['phone'] ?? '-') ?></td>
                    <td style="font-size:.85rem"><?= sanitize($c['email'] ?? '-') ?></td>
                    <td><?= sanitize($c['line_id'] ?? '-') ?></td>
                    <td><span class="badge badge-info"><?= $c['order_count'] ?> รายการ</span></td>
                    <td><strong><?= formatCurrency($c['total_spent']) ?></strong></td>
                    <td style="font-size:.82rem"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                    <td class="table-actions">
                        <button class="btn btn-sm btn-outline" onclick='editCstm(<?= json_encode($c) ?>)'><?= icon('pencil', 14) ?></button>
                        <a href="<?= url('/admin/customer-orders.php') ?>?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline"><?= icon('file', 14) ?></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('ลบลูกค้านี้?')">
                            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button class="btn btn-sm btn-danger"><?= icon('trash', 14) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="cstmModal">
    <div class="modal">
        <div class="modal-header"><h3 id="cstmTitle">เพิ่มลูกค้าใหม่</h3><button class="modal-close" onclick="closeModal('cstmModal')"><?= icon('x', 16) ?></button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="save"><input type="hidden" name="id" id="cId" value="0">
                <div class="form-group"><label>ชื่อ-นามสกุล <span class="required">*</span></label><input type="text" name="name" class="form-control" id="cName" required></div>
                <div class="form-row">
                    <div class="form-group"><label>เบอร์โทร</label><input type="tel" name="phone" class="form-control" id="cPhone"></div>
                    <div class="form-group"><label>อีเมล</label><input type="email" name="email" class="form-control" id="cEmail"></div>
                </div>
                <div class="form-group"><label>ที่อยู่</label><textarea name="address" class="form-control" id="cAddress" rows="2"></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>LINE ID</label><input type="text" name="line_id" class="form-control" id="cLine"></div>
                    <div class="form-group"><label>แหล่งที่มา</label>
                        <select name="source" class="form-control" id="cSource">
                            <option value="">-- เลือก --</option>
                            <option value="website">Website</option>
                            <option value="facebook">Facebook</option>
                            <option value="line">LINE</option>
                            <option value="walk-in">Walk-in</option>
                            <option value="referral">แนะนำ</option>
                            <option value="other">อื่นๆ</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>หมายเหตุ</label><textarea name="notes" class="form-control" id="cNotes" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('cstmModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><?= icon('save', 16) ?> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetCstm() {
    document.getElementById('cId').value = 0;
    ['cName','cPhone','cEmail','cAddress','cLine','cNotes'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('cSource').value = '';
}
function editCstm(c) {
    document.getElementById('cstmTitle').textContent = 'แก้ไขข้อมูลลูกค้า';
    document.getElementById('cId').value = c.id;
    document.getElementById('cName').value = c.name;
    document.getElementById('cPhone').value = c.phone || '';
    document.getElementById('cEmail').value = c.email || '';
    document.getElementById('cAddress').value = c.address || '';
    document.getElementById('cLine').value = c.line_id || '';
    document.getElementById('cSource').value = c.source || '';
    document.getElementById('cNotes').value = c.notes || '';
    openModal('cstmModal');
}
function filterTable() {
    const q = document.querySelector('.filters-bar input').value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
