<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect('/admin/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        redirect('/admin/');
    } else {
        $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | G2K</title>
    <link rel="icon" href="assets/img/g2k-logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-brand">
        <div class="login-brand-inner">
            <div class="logo-plate">
                <img src="assets/img/g2k-logo.png" alt="G2K" class="login-brand-logo">
            </div>
            <h2>Solar &amp; Water Pump Solution</h2>
            <p>ระบบบริหารจัดการติดตั้งโซล่าเซลล์รูฟท็อปและปั๊มน้ำ สำหรับทีมงานองค์กร</p>
            <div class="pills">
                <span class="pill">พลังงานสะอาด</span>
                <span class="pill">ประหยัด</span>
                <span class="pill">ยั่งยืน</span>
            </div>
        </div>
    </div>
    <div class="login-panel">
        <div class="login-box">
            <div class="login-logo">
                <h1>เข้าสู่ระบบ</h1>
                <p>G2K Back Office — สำหรับผู้ดูแลและพนักงาน</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@g2k.co.th" required value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:8px">
                    เข้าสู่ระบบ
                </button>
            </form>
            <p style="text-align:center;margin-top:20px;font-size:.82rem;color:var(--gray-500)">
                Demo: admin@solarmgr.com / password
            </p>
        </div>
    </div>
</div>
</body>
</html>
