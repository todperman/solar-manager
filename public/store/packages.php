<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$packages = db()->query("SELECT * FROM packages WHERE active=1 ORDER BY sort_order, price")->fetchAll();
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แพ็กเกจติดตั้ง | G2K</title>
    <link rel="icon" href="<?= url('/assets/img/g2k-logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/store_nav.php'; ?>

<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow gold">Installation Packages</span>
            <h2>แพ็กเกจติดตั้งโซล่าเซลล์</h2>
            <p>เลือกแพ็กเกจที่เหมาะกับขนาดบ้านและการใช้ไฟของคุณ ทุกแพ็กเกจรวมติดตั้งฟรี</p>
        </div>
        <div class="packages-grid">
            <?php foreach ($packages as $p):
                $features = json_decode($p['features'], true) ?: [];
            ?>
            <div class="package-card <?= $p['popular'] ? 'popular' : '' ?>">
                <h3><?= sanitize($p['name']) ?></h3>
                <?php if ($p['original_price'] > 0): ?>
                    <div class="original-price"><?= formatCurrency($p['original_price']) ?></div>
                <?php endif; ?>
                <div class="price"><?= formatCurrency($p['price']) ?></div>
                <p style="color:var(--gray-500);font-size:.9rem;margin:12px 0 0"><?= sanitize($p['description']) ?></p>
                <ul class="features">
                    <?php foreach ($features as $f): ?>
                        <li><?= sanitize($f) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= url('/store/cart.php') ?>?add_package=<?= $p['id'] ?>" class="btn <?= $p['popular'] ? 'btn-accent' : 'btn-primary' ?> btn-lg" style="width:100%;justify-content:center">สั่งซื้อแพ็กเกจนี้</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/store_footer.php'; ?>
</body>
</html>
