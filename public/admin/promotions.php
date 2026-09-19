<?php
$pageTitle = 'จัดการโปรโมชั่น';
include __DIR__ . '/../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'];
        $value = floatval($_POST['value']);
        $min_amount = floatval($_POST['min_amount'] ?? 0);
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;
        $active = isset($_POST['active']) ? 1 : 0;

        if ($id > 0) {
            $stmt = db()->prepare("UPDATE promotions SET name=?,code=?,type=?,value=?,min_amount=?,start_date=?,end_date=?,active=? WHERE id=?");
            $stmt->execute([$name,$code,$type,$value,$min_amount,$start_date,$end_date,$active,$id]);
        } else {
            $stmt = db()->prepare("INSERT INTO promotions (name,code,type,value,min_amount,start_date,end_date,active) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$code,$type,$value,$min_amount,$start_date,$end_date,$active]);
        }
        flash('success', 'บันทึกโปรโมชั่นเรียบร้อยแล้ว');
        redirect('/admin/promotions.php');
    }
    if ($action === 'delete') {
        db()->prepare("DELETE FROM promotions WHERE id = ?")->execute([intval($_POST['id'])]);
        flash('success', 'ลบโปรโมชั่นเรียบร้อยแล้ว');
        redirect('/admin/promotions.php');
    }
}

$promotions = db()->query("SELECT * FROM promotions ORDER BY created_at DESC")->fetchAll();
?>

<div class="filters-bar">
    <button class="btn btn-primary" onclick="openModal('promoModal');document.getElementById('promoTitle').textContent='เพิ่มโปรโมชั่น';resetPromo()">+ เพิ่มโปรโมชั่น</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>ชื่อโปรโมชั่น</th><th>รหัส</th><th>ประเภท</th><th>มูลค่า</th><th>ขั้นต่ำ</th><th>ระยะเวลา</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
            <tbody>
                <?php if (empty($promotions)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">ยังไม่มีโปรโมชั่น</td></tr>
                <?php else: foreach ($promotions as $p): ?>
                <tr>
                    <td><strong><?= sanitize($p['name']) ?></strong></td>
                    <td><code style="background:var(--accent);color:var(--white);padding:3px 10px;border-radius:6px;font-size:.82rem;font-weight:600"><?= sanitize($p['code'] ?? '-') ?></code></td>
                    <td><span class="badge badge-info"><?= $p['type'] === 'percent' ? 'เปอร์เซ็นต์' : 'จำนวนเงิน' ?></span></td>
                    <td><strong style="color:var(--primary)"><?= $p['type'] === 'percent' ? $p->value.'%' : formatCurrency($p['value']) ?></strong></td>
                    <td><?= $p['min_amount'] > 0 ? formatCurrency($p['min_amount']) : '-' ?></td>
                    <td style="font-size:.82rem">
                        <?php if ($p['start_date']): ?>
                            <?= date('d/m/Y', strtotime($p['start_date'])) ?> - <?= $p['end_date'] ? date('d/m/Y', strtotime($p['end_date'])) : 'ไม่กำหนด' ?>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><span class="badge <?= $p['active'] ? 'badge-success' : 'badge-gray' ?>"><?= $p['active'] ? 'เปิด' : 'ปิด' ?></span></td>
                    <td class="table-actions">
                        <button class="btn btn-sm btn-outline" onclick='editPromo(<?= json_encode($p) ?>)'><?= icon('pencil', 14) ?></button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('ลบโปรโมชั่นนี้?')">
                            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
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
<div class="modal-overlay" id="promoModal">
    <div class="modal">
        <div class="modal-header"><h3 id="promoTitle">เพิ่มโปรโมชั่น</h3><button class="modal-close" onclick="closeModal('promoModal')"><?= icon('x', 16) ?></button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="save"><input type="hidden" name="id" id="promoId" value="0">
                <div class="form-group"><label>ชื่อโปรโมชั่น <span class="required">*</span></label><input type="text" name="name" class="form-control" id="promoName" required></div>
                <div class="form-row">
                    <div class="form-group"><label>รหัสโปรโมชั่น</label><input type="text" name="code" class="form-control" id="promoCode" placeholder="เช่น SOLAR10"></div>
                    <div class="form-group"><label>ประเภท <span class="required">*</span></label>
                        <select name="type" class="form-control" id="promoType"><option value="percent">เปอร์เซ็นต์ (%)</option><option value="fixed">จำนวนเงิน (฿)</option></select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>มูลค่า <span class="required">*</span></label><input type="number" name="value" class="form-control" id="promoValue" step="0.01" required></div>
                    <div class="form-group"><label>ยอดซื้อขั้นต่ำ (฿)</label><input type="number" name="min_amount" class="form-control" id="promoMin" step="0.01" value="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>วันเริ่ม</label><input type="date" name="start_date" class="form-control" id="promoStart"></div>
                    <div class="form-group"><label>วันสิ้นสุด</label><input type="date" name="end_date" class="form-control" id="promoEnd"></div>
                </div>
                <div class="form-group"><label><input type="checkbox" name="active" id="promoActive" checked> เปิดใช้งาน</label></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('promoModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><?= icon('save', 16) ?> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetPromo() {
    ['promoId','promoName','promoCode','promoValue','promoMin'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('promoId').value = 0;
    document.getElementById('promoType').value = 'percent';
    document.getElementById('promoStart').value = '';
    document.getElementById('promoEnd').value = '';
    document.getElementById('promoActive').checked = true;
}
function editPromo(p) {
    document.getElementById('promoTitle').textContent = 'แก้ไขโปรโมชั่น';
    document.getElementById('promoId').value = p.id;
    document.getElementById('promoName').value = p.name;
    document.getElementById('promoCode').value = p.code || '';
    document.getElementById('promoType').value = p.type;
    document.getElementById('promoValue').value = p.value;
    document.getElementById('promoMin').value = p.min_amount || 0;
    document.getElementById('promoStart').value = p.start_date || '';
    document.getElementById('promoEnd').value = p.end_date || '';
    document.getElementById('promoActive').checked = p.active == 1;
    openModal('promoModal');
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
