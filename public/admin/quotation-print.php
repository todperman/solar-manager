<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, u.name as staff_name
    FROM orders o LEFT JOIN customers c ON o.customer_id=c.id LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    exit('ไม่พบเอกสาร');
}

$items = db()->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id");
$items->execute([$id]);
$items = $items->fetchAll();

$isQuote = $order['type'] === 'quotation';
$docTitle = $isQuote ? 'ใบเสนอราคา' : 'ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ';
$docTitleEn = $isQuote ? 'QUOTATION' : 'RECEIPT';
$company = [
    'name' => getSetting('company_name', APP_NAME),
    'address' => getSetting('company_address'),
    'phone' => getSetting('company_phone'),
    'email' => getSetting('company_email'),
];
$created = strtotime($order['created_at']);
$taxRate = rtrim(rtrim(number_format($order['tax_rate'], 2), '0'), '.');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $docTitleEn ?> <?= sanitize($order['order_no']) ?></title>
    <link rel="icon" href="<?= url('/assets/img/g2k-logo.png') ?>" type="image/png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap');
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', 'Tahoma', sans-serif; color: #1f2937; background: #f3f4f6; margin: 0; font-size: 14px; }
        .page { width: 210mm; min-height: 297mm; margin: 24px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0f4c81; padding-bottom: 16px; }
        .head img { height: 64px; }
        .company { font-size: 13px; line-height: 1.6; margin-top: 6px; }
        .company strong { font-size: 16px; }
        .doc { text-align: right; }
        .doc h1 { margin: 0; font-size: 22px; color: #0f4c81; }
        .doc .en { letter-spacing: 3px; color: #6b7280; font-size: 12px; }
        .doc table { margin-left: auto; margin-top: 10px; font-size: 13px; }
        .doc td { padding: 2px 0 2px 12px; }
        .bill { margin: 20px 0; padding: 14px 16px; background: #f9fafb; border-radius: 8px; line-height: 1.7; }
        .bill .label { color: #6b7280; font-size: 12px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: #0f4c81; color: #fff; padding: 9px 8px; font-weight: 600; font-size: 13px; }
        table.items td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .r { text-align: right; } .c { text-align: center; }
        .totals { width: 320px; margin-left: auto; margin-top: 12px; border-collapse: collapse; }
        .totals td { padding: 6px 8px; }
        .totals .grand td { border-top: 2px solid #0f4c81; font-weight: 700; font-size: 16px; color: #0f4c81; }
        .notes { margin-top: 20px; font-size: 13px; color: #4b5563; }
        .sign { display: flex; justify-content: space-between; margin-top: 60px; text-align: center; font-size: 13px; }
        .sign div { width: 40%; }
        .sign .line { border-top: 1px dotted #6b7280; margin-bottom: 6px; padding-top: 40px; }
        .toolbar { text-align: center; margin: 16px; }
        .toolbar button { font-family: inherit; font-size: 15px; padding: 10px 28px; border: 0; border-radius: 8px; background: #0f4c81; color: #fff; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; width: auto; min-height: 0; padding: 0; }
            .toolbar { display: none; }
            @page { size: A4; margin: 14mm; }
        }
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">พิมพ์ / บันทึกเป็น PDF</button></div>
<div class="page">
    <div class="head">
        <div>
            <img src="<?= url('/assets/img/g2k-logo.png') ?>" alt="<?= sanitize($company['name']) ?>">
            <div class="company">
                <strong><?= sanitize($company['name']) ?></strong><br>
                <?= nl2br(sanitize($company['address'])) ?><br>
                โทร <?= sanitize($company['phone']) ?> · <?= sanitize($company['email']) ?>
            </div>
        </div>
        <div class="doc">
            <h1><?= $docTitle ?></h1>
            <div class="en"><?= $docTitleEn ?></div>
            <table>
                <tr><td>เลขที่</td><td><strong><?= sanitize($order['order_no']) ?></strong></td></tr>
                <tr><td>วันที่</td><td><?= date('d/m/', $created) . (date('Y', $created) + 543) ?></td></tr>
                <?php if ($isQuote): ?><tr><td>ยืนราคาถึง</td><td><?= date('d/m/', $created + 30 * 86400) . (date('Y', $created + 30 * 86400) + 543) ?></td></tr><?php endif; ?>
                <tr><td>ผู้ออกเอกสาร</td><td><?= sanitize($order['staff_name'] ?? '-') ?></td></tr>
            </table>
        </div>
    </div>

    <div class="bill">
        <div class="label">ลูกค้า / Customer</div>
        <?php if ($order['customer_id']): ?>
            <strong><?= sanitize($order['customer_name']) ?></strong><br>
            <?= $order['customer_address'] ? nl2br(sanitize($order['customer_address'])) . '<br>' : '' ?>
            <?= $order['customer_phone'] ? 'โทร ' . sanitize($order['customer_phone']) : '' ?>
            <?= $order['customer_email'] ? ' · ' . sanitize($order['customer_email']) : '' ?>
        <?php else: ?>
            <strong>ลูกค้าทั่วไป (Walk-in)</strong>
        <?php endif; ?>
    </div>

    <table class="items">
        <thead><tr><th class="c" style="width:40px">#</th><th style="text-align:left">รายการ</th><th class="r" style="width:70px">จำนวน</th><th class="r" style="width:120px">ราคา/หน่วย</th><th class="r" style="width:120px">จำนวนเงิน</th></tr></thead>
        <tbody>
            <?php foreach ($items as $i => $it): ?>
            <tr>
                <td class="c"><?= $i + 1 ?></td>
                <td><?= sanitize($it['name']) ?></td>
                <td class="r"><?= number_format($it['qty']) ?></td>
                <td class="r"><?= number_format($it['price'], 2) ?></td>
                <td class="r"><?= number_format($it['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>รวมเป็นเงิน</td><td class="r"><?= number_format($order['subtotal'], 2) ?></td></tr>
        <?php if ($order['discount'] > 0): ?><tr><td>ส่วนลด</td><td class="r">-<?= number_format($order['discount'], 2) ?></td></tr><?php endif; ?>
        <tr><td>ภาษีมูลค่าเพิ่ม <?= $taxRate ?>%</td><td class="r"><?= number_format($order['tax'], 2) ?></td></tr>
        <tr class="grand"><td>จำนวนเงินรวมทั้งสิ้น</td><td class="r"><?= formatCurrency($order['total']) ?></td></tr>
    </table>

    <?php if ($order['notes']): ?>
        <div class="notes"><strong>หมายเหตุ:</strong> <?= nl2br(sanitize($order['notes'])) ?></div>
    <?php endif; ?>
    <?php if ($isQuote): ?>
        <div class="notes">ราคานี้มีผลภายใน 30 วันนับจากวันที่ออกเอกสาร</div>
    <?php endif; ?>

    <div class="sign">
        <div><div class="line"></div><?= $isQuote ? 'ผู้อนุมัติ / ลูกค้า' : 'ผู้รับเงิน' ?></div>
        <div><div class="line"></div>ผู้มีอำนาจลงนาม · <?= sanitize($company['name']) ?></div>
    </div>
</div>
</body>
</html>
