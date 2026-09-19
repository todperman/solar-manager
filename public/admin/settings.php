<?php
$pageTitle = 'ตั้งค่าระบบ';
include __DIR__ . '/../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'company') {
        $fields = ['company_name','company_address','company_phone','company_email','tax_rate','currency'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = trim($_POST[$f]);
                $stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
                $stmt->execute([$f, $val, $val]);
            }
        }
        flash('success', 'บันทึกตั้งค่าบริษัทเรียบร้อยแล้ว');
        redirect('/admin/settings.php');
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $user = currentUser();

        if (!password_verify($current, $user['password'])) {
            flash('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        } elseif (strlen($new) < 6) {
            flash('error', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
        } elseif ($new !== $confirm) {
            flash('error', 'รหัสผ่านใหม่ไม่ตรงกัน');
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $user['id']]);
            flash('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        }
        redirect('/admin/settings.php');
    }
}

$settings = [];
$settingsRows = db()->query("SELECT * FROM settings")->fetchAll();
foreach ($settingsRows as $s) $settings[$s['setting_key']] = $s['setting_value'];
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
    <!-- Company Settings -->
    <div class="card">
        <div class="card-header"><h3><?= icon('building', 18) ?> ข้อมูลบริษัท</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="company">
                <div class="form-group"><label>ชื่อบริษัท</label><input type="text" name="company_name" class="form-control" value="<?= sanitize($settings['company_name'] ?? '') ?>"></div>
                <div class="form-group"><label>ที่อยู่</label><textarea name="company_address" class="form-control"><?= sanitize($settings['company_address'] ?? '') ?></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>เบอร์โทร</label><input type="text" name="company_phone" class="form-control" value="<?= sanitize($settings['company_phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>อีเมล</label><input type="email" name="company_email" class="form-control" value="<?= sanitize($settings['company_email'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>ภาษีมูลค่าเพิ่ม (%)</label><input type="number" name="tax_rate" class="form-control" value="<?= sanitize($settings['tax_rate'] ?? '7') ?>" step="0.01"></div>
                    <div class="form-group"><label>สกุลเงิน</label><input type="text" name="currency" class="form-control" value="<?= sanitize($settings['currency'] ?? '฿') ?>"></div>
                </div>
                <button type="submit" class="btn btn-primary"><?= icon('save', 16) ?> บันทึก</button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-header"><h3><?= icon('lock', 18) ?> เปลี่ยนรหัสผ่าน</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="password">
                <div class="form-group"><label>รหัสผ่านปัจจุบัน</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="form-group"><label>รหัสผ่านใหม่</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
                <div class="form-group"><label>ยืนยันรหัสผ่านใหม่</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary"><?= icon('lock', 16) ?> เปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </div>
</div>

<!-- System Info -->
<div class="card" style="margin-top:24px">
    <div class="card-header"><h3><?= icon('info', 18) ?> ข้อมูลระบบ</h3></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px">
            <div><span style="color:var(--gray-500);font-size:.85rem">เวอร์ชัน</span><div style="font-weight:700">G2K Enterprise v2.0</div></div>
            <div><span style="color:var(--gray-500);font-size:.85rem">PHP</span><div style="font-weight:700"><?= phpversion() ?></div></div>
            <div><span style="color:var(--gray-500);font-size:.85rem">MySQL</span><div style="font-weight:700"><?= db()->query("SELECT VERSION()")->fetchColumn() ?></div></div>
            <div><span style="color:var(--gray-500);font-size:.85rem">Database</span><div style="font-weight:700"><?= DB_NAME ?></div></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
