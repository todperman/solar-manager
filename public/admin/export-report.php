<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$isDate = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
$dateFrom = $isDate($_GET['from'] ?? null) ? $_GET['from'] : date('Y-m-01');
$dateTo = $isDate($_GET['to'] ?? null) ? $_GET['to'] : date('Y-m-t');

$stmt = db()->prepare("SELECT o.order_no, o.created_at, o.type, o.status, c.name as customer_name, c.phone as customer_phone, o.payment_method, o.subtotal, o.discount, o.tax, o.total, u.name as staff_name
    FROM orders o LEFT JOIN customers c ON o.customer_id=c.id LEFT JOIN users u ON o.user_id=u.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.created_at");
$stmt->execute([$dateFrom, $dateTo]);

while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales-report_' . $dateFrom . '_' . $dateTo . '.csv"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Thai correctly
fputcsv($out, ['หมายเลข', 'วันที่', 'ประเภท', 'สถานะ', 'ลูกค้า', 'เบอร์โทร', 'ชำระผ่าน', 'รวมก่อนลด', 'ส่วนลด', 'VAT', 'ยอดรวม', 'พนักงาน']);

$sum = ['subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0];
while ($r = $stmt->fetch()) {
    fputcsv($out, [
        $r['order_no'],
        date('Y-m-d H:i', strtotime($r['created_at'])),
        orderTypeLabel($r['type']),
        statusLabel($r['status']),
        $r['customer_name'] ?? 'Walk-in',
        $r['customer_phone'] ?? '',
        paymentLabel($r['payment_method']),
        $r['subtotal'], $r['discount'], $r['tax'], $r['total'],
        $r['staff_name'] ?? '',
    ]);
    if (in_array($r['status'], ['paid', 'completed'], true)) {
        foreach ($sum as $k => $v) $sum[$k] += $r[$k];
    }
}
fputcsv($out, []);
fputcsv($out, ['รวมยอดที่ชำระแล้ว', '', '', '', '', '', '', $sum['subtotal'], $sum['discount'], $sum['tax'], $sum['total'], '']);
fclose($out);
exit;
