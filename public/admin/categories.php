<?php
$pageTitle = 'จัดการหมวดหมู่';
include __DIR__ . '/../includes/admin_header.php';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $icon = categoryIconName(trim($_POST['icon'] ?? 'folder'));
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($id > 0) {
            $stmt = db()->prepare("UPDATE categories SET name=?, description=?, icon=?, sort_order=?, active=? WHERE id=?");
            $stmt->execute([$name, $description, $icon, $sort_order, $active, $id]);
        } else {
            $stmt = db()->prepare("INSERT INTO categories (name, description, icon, sort_order, active) VALUES (?,?,?,?,?)");
            $stmt->execute([$name, $description, $icon, $sort_order, $active]);
        }
        flash('success', 'บันทึกหมวดหมู่เรียบร้อยแล้ว');
        redirect('/admin/categories.php');
    }
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        db()->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        flash('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
        redirect('/admin/categories.php');
    }
}

$categories = db()->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY sort_order, c.name")->fetchAll();
?>

<div class="filters-bar">
    <div class="search-box">
        <span class="icon"><?= icon('search', 16) ?></span>
        <input type="text" placeholder="ค้นหาหมวดหมู่..." id="searchBox" onkeyup="filterTable()">
    </div>
    <button class="btn btn-primary" onclick="openModal('catModal')">+ เพิ่มหมวดหมู่</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table id="dataTable">
            <thead>
                <tr><th>ไอคอน</th><th>ชื่อหมวดหมู่</th><th>คำอธิบาย</th><th>สินค้า</th><th>สถานะ</th><th>จัดการ</th></tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= icon(categoryIconName($cat['icon']), 20) ?></td>
                    <td><strong><?= sanitize($cat['name']) ?></strong></td>
                    <td><?= sanitize($cat['description']) ?></td>
                    <td><span class="badge badge-info"><?= $cat['product_count'] ?> รายการ</span></td>
                    <td><span class="badge <?= $cat['active'] ? 'badge-success' : 'badge-gray' ?>"><?= $cat['active'] ? 'เปิดใช้งาน' : 'ปิด' ?></span></td>
                    <td class="table-actions">
                        <button class="btn btn-sm btn-outline" onclick='editCategory(<?= json_encode($cat) ?>)'><?= icon('pencil', 14) ?></button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('ลบหมวดหมู่นี้?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button class="btn btn-sm btn-danger"><?= icon('trash', 14) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">เพิ่มหมวดหมู่ใหม่</h3>
            <button class="modal-close" onclick="closeModal('catModal')"><?= icon('x', 16) ?></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="catId" value="0">
                <div class="form-group">
                    <label>ชื่อหมวดหมู่ <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" id="catName" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ไอคอน</label>
                        <select name="icon" class="form-control" id="catIcon">
                            <option value="folder">โฟลเดอร์</option>
                            <option value="sun">แผงโซลาร์</option>
                            <option value="droplet">ปั๊มน้ำ</option>
                            <option value="wrench">ติดตั้ง</option>
                            <option value="package">สินค้า</option>
                            <option value="tag">แท็ก</option>
                            <option value="settings">อุปกรณ์</option>
                            <option value="cart">ขาย</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ลำดับ</label>
                        <input type="number" name="sort_order" class="form-control" id="catSort" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea name="description" class="form-control" id="catDesc"></textarea>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="active" id="catActive" checked> เปิดใช้งาน</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('catModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><?= icon('save', 16) ?> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('modalTitle').textContent = 'แก้ไขหมวดหมู่';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    const iconKnown = ['folder','sun','droplet','wrench','package','tag','settings','cart'];
    const iconEmoji = {'📁':'folder','📂':'folder','☀️':'sun','🌞':'sun','💧':'droplet','🔧':'wrench','📦':'package'};
    const mapped = iconKnown.includes(cat.icon) ? cat.icon : (iconEmoji[cat.icon] || 'folder');
    document.getElementById('catIcon').value = mapped;
    document.getElementById('catSort').value = cat.sort_order;
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('catActive').checked = cat.active == 1;
    openModal('catModal');
}
function filterTable() {
    const q = document.getElementById('searchBox').value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
