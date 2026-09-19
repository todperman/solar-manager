<?php
$pageTitle = 'จัดการสินค้า';
include __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../../includes/functions.php';

$categories = db()->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $sku = strtoupper(trim($_POST['sku']));
        $category_id = intval($_POST['category_id'] ?? 0) ?: null;
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price']);
        $cost = floatval($_POST['cost'] ?? 0);
        $stock_qty = intval($_POST['stock_qty'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'ชิ้น');
        $active = isset($_POST['active']) ? 1 : 0;
        $specs = trim($_POST['specs'] ?? '');
        if ($specs === '' || json_decode($specs) === null) {
            $specs = '{}';
        }

        $filename = basename($_POST['existing_image'] ?? '');
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $isImage = $_FILES['image']['error'] === UPLOAD_ERR_OK && @getimagesize($_FILES['image']['tmp_name']) !== false;
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || !$isImage) {
                flash('error', 'อัปโหลดได้เฉพาะไฟล์รูปภาพ (jpg, png, gif, webp)');
                redirect('/admin/products.php');
            }
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0775, true);
            }
            $filename = 'prod_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $filename)) {
                flash('error', 'บันทึกรูปภาพไม่สำเร็จ ตรวจสอบสิทธิ์โฟลเดอร์ public/assets/img/uploads');
                redirect('/admin/products.php');
            }
        }

        $dup = db()->prepare("SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?");
        $dup->execute([$sku, $id]);
        if ($dup->fetchColumn() > 0) {
            flash('error', "รหัสสินค้า (SKU) {$sku} ถูกใช้แล้ว");
            redirect('/admin/products.php');
        }

        if ($id > 0) {
            $sql = "UPDATE products SET name=?, sku=?, category_id=?, description=?, price=?, cost=?, stock_qty=?, unit=?, image=?, specs=?, active=?";
            $params = [$name, $sku, $category_id, $description, $price, $cost, $stock_qty, $unit, $filename, $specs, $active];
            $sql .= " WHERE id=?";
            $params[] = $id;
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = db()->prepare("INSERT INTO products (name,sku,category_id,description,price,cost,stock_qty,unit,image,specs,active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $sku, $category_id, $description, $price, $cost, $stock_qty, $unit, $filename, $specs, $active]);
        }
        flash('success', 'บันทึกสินค้าเรียบร้อยแล้ว');
        redirect('/admin/products.php');
    }
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        db()->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        flash('success', 'ลบสินค้าเรียบร้อยแล้ว');
        redirect('/admin/products.php');
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$search = trim($_GET['q'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$catFilter = intval($_GET['cat'] ?? 0);
if ($catFilter) { $where .= " AND p.category_id = ?"; $params[] = $catFilter; }

$total = db()->prepare("SELECT COUNT(*) as c FROM products p $where");
$total->execute($params);
$totalCount = $total->fetch()['c'];
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="filters-bar">
    <div class="search-box">
        <span class="icon"><?= icon('search', 16) ?></span>
        <input type="text" placeholder="ค้นหาสินค้า... (ชื่อ, SKU)" value="<?= sanitize($search) ?>" id="searchBox" onkeyup="filterTable()">
    </div>
    <select class="form-control" style="width:180px" onchange="location='?cat='+this.value">
        <option value="">ทุกหมวดหมู่</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['icon'].' '.$c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" onclick="openModal('prodModal');document.getElementById('modalTitle').textContent='เพิ่มสินค้าใหม่';resetForm()">+ เพิ่มสินค้า</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table id="dataTable">
            <thead>
                <tr><th>สินค้า</th><th>SKU</th><th>หมวดหมู่</th><th>ราคาขาย</th><th>ทุน</th><th>Stock</th><th>สถานะ</th><th>จัดการ</th></tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">ไม่พบสินค้า</td></tr>
                <?php else: foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px">
                            <div style="width:44px;height:44px;border-radius:10px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
                                <?= $p['image'] ? '<img src="'.url('/assets/img/uploads/'.rawurlencode($p['image'])).'" style="width:100%;height:100%;object-fit:cover;border-radius:10px">' : icon('package', 22) ?>
                            </div>
                            <div>
                                <div style="font-weight:600"><?= sanitize($p['name']) ?></div>
                                <div style="font-size:.78rem;color:var(--gray-500)"><?= sanitize($p['unit']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code style="font-size:.82rem;background:var(--gray-100);padding:3px 8px;border-radius:4px"><?= sanitize($p['sku']) ?></code></td>
                    <td><span class="badge badge-info"><?= sanitize($p['cat_name'] ?? '-') ?></span></td>
                    <td><strong><?= formatCurrency($p['price']) ?></strong></td>
                    <td style="color:var(--gray-500)"><?= formatCurrency($p['cost']) ?></td>
                    <td>
                        <span class="badge <?= $p['stock_qty'] <= 10 ? 'badge-danger' : 'badge-success' ?>">
                            <?= $p['stock_qty'] ?> <?= $p['unit'] ?>
                        </span>
                    </td>
                    <td><span class="badge <?= $p['active'] ? 'badge-success' : 'badge-gray' ?>"><?= $p['active'] ? 'เปิด' : 'ปิด' ?></span></td>
                    <td class="table-actions">
                        <button class="btn btn-sm btn-outline" onclick='editProduct(<?= json_encode($p) ?>)'><?= icon('pencil', 14) ?></button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('ลบสินค้านี้?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-danger"><?= icon('trash', 14) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Modal -->
<div class="modal-overlay" id="prodModal">
    <div class="modal" style="max-width:640px">
        <div class="modal-header">
            <h3 id="modalTitle">เพิ่มสินค้าใหม่</h3>
            <button class="modal-close" onclick="closeModal('prodModal')"><?= icon('x', 16) ?></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="pId" value="0">
                <input type="hidden" name="existing_image" id="pExistingImage" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อสินค้า <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" id="pName" required>
                    </div>
                    <div class="form-group">
                        <label>SKU <span class="required">*</span></label>
                        <input type="text" name="sku" class="form-control" id="pSku" required placeholder="เช่น LP-450W-001">
                    </div>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่</label>
                    <select name="category_id" class="form-control" id="pCategory">
                        <option value="0">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['icon'].' '.$c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea name="description" class="form-control" id="pDesc"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาขาย (฿) <span class="required">*</span></label>
                        <input type="number" name="price" class="form-control" id="pPrice" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>ทุน (฿)</label>
                        <input type="number" name="cost" class="form-control" id="pCost" step="0.01" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>จำนวน Stock</label>
                        <input type="number" name="stock_qty" class="form-control" id="pStock" value="0">
                    </div>
                    <div class="form-group">
                        <label>หน่วย</label>
                        <input type="text" name="unit" class="form-control" id="pUnit" value="ชิ้น">
                    </div>
                </div>
                <div class="form-group">
                    <label>สเปค (JSON)</label>
                    <textarea name="specs" class="form-control" id="pSpecs" placeholder='{"warranty":"25 years"}'></textarea>
                </div>
                <div class="form-group">
                    <label>รูปภาพ</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="active" id="pActive" checked> เปิดใช้งาน</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('prodModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><?= icon('save', 16) ?> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('pId').value = 0;
    document.getElementById('pName').value = '';
    document.getElementById('pSku').value = '';
    document.getElementById('pCategory').value = 0;
    document.getElementById('pDesc').value = '';
    document.getElementById('pPrice').value = '';
    document.getElementById('pCost').value = 0;
    document.getElementById('pStock').value = 0;
    document.getElementById('pUnit').value = 'ชิ้น';
    document.getElementById('pSpecs').value = '';
    document.getElementById('pActive').checked = true;
}
function editProduct(p) {
    document.getElementById('modalTitle').textContent = 'แก้ไขสินค้า';
    document.getElementById('pId').value = p.id;
    document.getElementById('pName').value = p.name;
    document.getElementById('pSku').value = p.sku;
    document.getElementById('pCategory').value = p.category_id || 0;
    document.getElementById('pDesc').value = p.description || '';
    document.getElementById('pPrice').value = p.price;
    document.getElementById('pCost').value = p.cost;
    document.getElementById('pStock').value = p.stock_qty;
    document.getElementById('pUnit').value = p.unit;
    document.getElementById('pSpecs').value = p.specs || '';
    document.getElementById('pExistingImage').value = p.image || '';
    document.getElementById('pActive').checked = p.active == 1;
    openModal('prodModal');
}
function filterTable() {
    const q = document.getElementById('searchBox').value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
