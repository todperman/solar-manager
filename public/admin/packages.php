<?php
$pageTitle = 'จัดการแพ็กเกจ';
include __DIR__ . '/../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price']);
        $original_price = floatval($_POST['original_price'] ?? 0);
        $features = trim($_POST['features'] ?? '');
        $popular = isset($_POST['popular']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;

        // Convert features textarea to JSON array
        $featuresArr = array_filter(array_map('trim', explode("\n", $features)));
        $featuresJson = json_encode($featuresArr, JSON_UNESCAPED_UNICODE);

        if ($id > 0) {
            $stmt = db()->prepare("UPDATE packages SET name=?, description=?, price=?, original_price=?, features=?, popular=?, active=? WHERE id=?");
            $stmt->execute([$name, $description, $price, $original_price, $featuresJson, $popular, $active, $id]);
        } else {
            $stmt = db()->prepare("INSERT INTO packages (name,description,price,original_price,features,popular,active) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$name, $description, $price, $original_price, $featuresJson, $popular, $active]);
        }
        flash('success', 'บันทึกแพ็กเกจเรียบร้อยแล้ว');
        redirect('/admin/packages.php');
    }
    if ($action === 'delete') {
        db()->prepare("DELETE FROM packages WHERE id = ?")->execute([intval($_POST['id'])]);
        flash('success', 'ลบแพ็กเกจเรียบร้อยแล้ว');
        redirect('/admin/packages.php');
    }
}

$packages = db()->query("SELECT * FROM packages ORDER BY sort_order, price")->fetchAll();
?>

<div class="filters-bar">
    <button class="btn btn-primary" onclick="openModal('pkgModal');document.getElementById('pkgTitle').textContent='เพิ่มแพ็กเกจใหมe';resetPkg()">+ เพิ่มแพ็กเกจ</button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:24px;padding-top:20px">
    <?php foreach ($packages as $p): ?>
    <?php $features = json_decode($p['features'], true) ?: []; ?>
    <div class="card<?= $p['popular'] ? ' popular' : '' ?>">
        <div class="card-body">
            <?php if ($p['popular']): ?>
                <div class="popular-badge"><?= icon('star', 12) ?> ยอดนิยม</div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px">
                <div>
                    <h3 style="font-size:1.2rem;font-weight:800;color:var(--gray-900)"><?= sanitize($p['name']) ?></h3>
                    <?php if ($p['original_price'] > 0): ?>
                        <span style="text-decoration:line-through;color:var(--gray-400);font-size:.9rem"><?= formatCurrency($p['original_price']) ?></span>
                    <?php endif; ?>
                    <div style="font-size:1.6rem;font-weight:800;color:var(--primary);font-family:var(--font-en)"><?= formatCurrency($p['price']) ?></div>
                </div>
                <span class="badge <?= $p['active'] ? 'badge-success' : 'badge-gray' ?>"><?= $p['active'] ? 'เปิด' : 'ปิด' ?></span>
            </div>
            <p style="color:var(--gray-500);font-size:.9rem;margin-bottom:16px"><?= sanitize($p['description']) ?></p>
            <ul style="margin-bottom:20px">
                <?php foreach ($features as $f): ?>
                    <li style="padding:6px 0;font-size:.9rem;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--gray-100)">
                        <span style="color:var(--success);display:inline-flex"><?= icon('check', 14) ?></span> <?= sanitize($f) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline" onclick='editPkg(<?= json_encode($p) ?>)'><?= icon('pencil', 14) ?> แก้ไข</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('ลบแพ็กเกจนี้?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="btn btn-sm btn-danger"><?= icon('trash', 14) ?> ลบ</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="pkgModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h3 id="pkgTitle">เพิ่มแพ็กเกจใหม่</h3>
            <button class="modal-close" onclick="closeModal('pkgModal')"><?= icon('x', 16) ?></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="pkgId" value="0">
                <div class="form-group">
                    <label>ชื่อแพ็กเกจ <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" id="pkgName" required>
                </div>
                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea name="description" class="form-control" id="pkgDesc"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาแพ็กเกจ (฿) <span class="required">*</span></label>
                        <input type="number" name="price" class="form-control" id="pkgPrice" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>ราคาก่อนลด (฿)</label>
                        <input type="number" name="original_price" class="form-control" id="pkgOrigPrice" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label>สิ่งที่รวมในแพ็กเกจ (แยกบรรทัด)</label>
                    <textarea name="features" class="form-control" id="pkgFeatures" rows="5" placeholder="แผง 5kW (11 แผง)&#10;อินเวอร์เตอร์ 5kW&#10;ติดตั้งฟรี"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><input type="checkbox" name="popular" id="pkgPopular"> แสดงเป็นแพ็กเกจยอดนิยม</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="active" id="pkgActive" checked> เปิดใช้งาน</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('pkgModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><?= icon('save', 16) ?> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetPkg() {
    document.getElementById('pkgId').value = 0;
    document.getElementById('pkgName').value = '';
    document.getElementById('pkgDesc').value = '';
    document.getElementById('pkgPrice').value = '';
    document.getElementById('pkgOrigPrice').value = '';
    document.getElementById('pkgFeatures').value = '';
    document.getElementById('pkgPopular').checked = false;
    document.getElementById('pkgActive').checked = true;
}
function editPkg(p) {
    document.getElementById('pkgTitle').textContent = 'แก้ไขแพ็กเกจ';
    document.getElementById('pkgId').value = p.id;
    document.getElementById('pkgName').value = p.name;
    document.getElementById('pkgDesc').value = p.description || '';
    document.getElementById('pkgPrice').value = p.price;
    document.getElementById('pkgOrigPrice').value = p.original_price || '';
    const features = JSON.parse(p.features || '[]');
    document.getElementById('pkgFeatures').value = features.join('\n');
    document.getElementById('pkgPopular').checked = p.popular == 1;
    document.getElementById('pkgActive').checked = p.active == 1;
    openModal('pkgModal');
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
