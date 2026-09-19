<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
$orderNo = $_GET['order'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ | G2K</title>
    <link rel="icon" href="<?= url('/assets/img/g2k-logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/store_nav.php'; ?>

<section class="page-shell" style="display:flex;align-items:center">
    <div class="container" style="max-width:560px;text-align:center">
        <div style="width:88px;height:88px;border-radius:50%;background:var(--success-50);display:flex;align-items:center;justify-content:center;margin:0 auto 22px;color:var(--success)"><?= icon('check-circle', 40) ?></div>
        <h1 style="font-size:1.85rem;font-weight:700;color:var(--gray-900);margin-bottom:8px">สั่งซื้อสำเร็จ</h1>
        <p style="color:var(--gray-500);font-size:1.02rem;margin-bottom:8px">หมายเลขคำสั่งซื้อ</p>
        <div style="font-size:1.45rem;font-weight:800;color:var(--primary);font-family:var(--font-en);margin-bottom:24px;background:var(--primary-50);padding:12px 24px;border-radius:12px;display:inline-block"><?= sanitize($orderNo) ?></div>
        <p style="color:var(--gray-500);margin-bottom:32px;line-height:1.8">เราจะติดต่อกลับภายใน 24 ชั่วโมง<br>หากมีข้อสงสัยโทร 02-123-4567</p>
        <a href="<?= url('/store/') ?>" class="btn btn-primary btn-lg">เลือกซื้อสินค้าต่อ</a>
    </div>
</section>

<?php include __DIR__ . '/../includes/store_footer.php'; ?>
</body>
</html>
