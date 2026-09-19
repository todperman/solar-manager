<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$categories = db()->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();
$catFilter = intval($_GET['cat'] ?? 0);
$where = $catFilter ? "AND p.category_id=$catFilter" : "";
$products = db()->query("SELECT p.*, c.name as cat_name, c.icon as cat_icon FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.active=1 AND p.stock_qty > 0 $where ORDER BY p.created_at DESC")->fetchAll();
$packages = db()->query("SELECT * FROM packages WHERE active=1 ORDER BY sort_order, price")->fetchAll();
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G2K — โซล่าเซลล์ รูฟท็อปและปั๊มน้ำ</title>
    <link rel="icon" href="<?= url('/assets/img/g2k-logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/store_nav.php'; ?>

<section class="hero">
    <div class="hero-inner">
        <div>
            <div class="hero-badge">
                <span class="dot"></span>
                รับประกัน 25 ปี · ติดตั้งฟรี · ผ่อน 0%
            </div>
            <h1>โซล่าเซลล์และปั๊มน้ำ<br><span>พลังงานสะอาด</span> เพื่อองค์กรและบ้านคุณ</h1>
            <p>รับติดตั้งโซล่าเซลล์รูฟท็อปและระบบปั๊มน้ำครบวงจร โดยทีมงานผู้เชี่ยวชาญ คืนทุนเร็ว ค่าไฟลดทันที 30–60%</p>
            <div class="hero-actions">
                <a href="#products" class="btn btn-accent btn-lg">เลือกดูสินค้า</a>
                <a href="packages.php" class="btn btn-ghost btn-lg">ดูแพ็กเกจติดตั้ง</a>
            </div>
            <div class="hero-stats">
                <div>
                    <div class="n">15,000+</div>
                    <div class="l">ลูกค้าไว้วางใจ</div>
                </div>
                <div>
                    <div class="n">98%</div>
                    <div class="l">ความพึงพอใจ</div>
                </div>
                <div>
                    <div class="n">25 ปี</div>
                    <div class="l">รับประกัน</div>
                </div>
            </div>
        </div>
        <div class="hero-mark">
            <div class="logo-plate">
                <img src="<?= url('/assets/img/g2k-logo.png') ?>" alt="G2K Solar & Water Pump Solution" class="hero-logo">
            </div>
        </div>
    </div>
</section>

<section class="section section-white">
    <div class="container">
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon navy"><?= icon('sun', 24) ?></div>
                <h3>แผงคุณภาพสูง</h3>
                <p>มาตรฐานสากล ประสิทธิภาพสูงสุด รับประกัน 25 ปี</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon green"><?= icon('wrench', 24) ?></div>
                <h3>ติดตั้งโดยผู้เชี่ยวชาญ</h3>
                <p>ทีมช่างมืออาชีพ ติดตั้งฟรีทุกแพ็กเกจ</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon gold"><?= icon('droplet', 24) ?></div>
                <h3>โซลูชันปั๊มน้ำ</h3>
                <p>ระบบปั๊มน้ำโซล่าเซลล์ ประหยัดพลังงาน ยั่งยืน</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon cyan"><?= icon('credit-card', 24) ?></div>
                <h3>ผ่อนชำระ 0%</h3>
                <p>รองรับบัตรเครดิต ผ่อนนานสูงสุด 10 เดือน</p>
            </div>
        </div>
    </div>
</section>

<section id="products" class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Products</span>
            <h2>สินค้าของเรา</h2>
            <p>อุปกรณ์โซล่าเซลล์และปั๊มน้ำคุณภาพสูง ได้มาตรฐานสากล</p>
        </div>

        <div class="cat-filters">
            <a href="<?= url('/store/') ?>" class="btn btn-sm <?= !$catFilter ? 'btn-primary' : 'btn-outline' ?>">ทั้งหมด</a>
            <?php foreach ($categories as $c): ?>
                <a href="<?= url('/store/') ?>?cat=<?= $c['id'] ?>" class="btn btn-sm <?= $catFilter == $c['id'] ? 'btn-primary' : 'btn-outline' ?>"><?= icon(categoryIconName($c['icon']), 14) ?> <?= sanitize($c['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card">
                <div class="product-img">
                    <?= $p['image'] ? '<img src="'.url('/assets/img/uploads/'.rawurlencode($p['image'])).'" style="width:100%;height:100%;object-fit:cover" alt="'.sanitize($p['name']).'">' : icon('package', 40) ?>
                </div>
                <div class="product-info">
                    <div class="category"><?= icon(categoryIconName($p['cat_icon']), 14) ?> <?= sanitize($p['cat_name']) ?></div>
                    <h3><?= sanitize($p['name']) ?></h3>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div class="price"><?= formatCurrency($p['price']) ?></div>
                        <div class="stock" style="margin:0">คงเหลือ <?= $p['stock_qty'] ?> <?= $p['unit'] ?></div>
                    </div>
                    <button class="btn btn-primary btn-sm" style="width:100%;justify-content:center;margin-top:14px;padding:12px" onclick="addToCartStore(<?= $p['id'] ?>, '<?= sanitize($p['name']) ?>', <?= $p['price'] ?>)">เพิ่มลงตะกร้า</button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--gray-400)">
                    <p style="font-weight:600">ไม่พบสินค้าในหมวดนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section-white">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow gold">Packages</span>
            <h2>แพ็กเกจติดตั้ง</h2>
            <p>เลือกแพ็กเกจที่เหมาะกับบ้านและการใช้ไฟของคุณ</p>
        </div>
        <div class="packages-grid">
            <?php foreach ($packages as $p):
                $features = json_decode($p['features'], true) ?: [];
            ?>
            <div class="package-card <?= $p['popular'] ? 'popular' : '' ?>">
                <h3><?= sanitize($p['name']) ?></h3>
                <?php if ($p['original_price'] > 0): ?>
                    <div class="original-price">ลดจาก <?= formatCurrency($p['original_price']) ?></div>
                <?php endif; ?>
                <div class="price"><?= formatCurrency($p['price']) ?></div>
                <p style="color:var(--gray-500);font-size:.9rem;margin:8px 0 0"><?= sanitize($p['description']) ?></p>
                <ul class="features">
                    <?php foreach ($features as $f): ?>
                        <li><?= sanitize($f) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= url('/store/cart.php') ?>?add_package=<?= $p['id'] ?>" class="btn <?= $p['popular'] ? 'btn-accent' : 'btn-primary' ?> btn-lg" style="width:100%;justify-content:center;padding:14px">สั่งซื้อแพ็กเกจนี้</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="contact" class="section section-muted">
    <div class="container" style="max-width:900px">
        <div class="section-head">
            <span class="eyebrow">Contact</span>
            <h2>ติดต่อเรา</h2>
            <p>พร้อมให้คำปรึกษาเรื่องโซล่าเซลล์และปั๊มน้ำ</p>
        </div>
        <div class="contact-grid">
            <div class="contact-card">
                <div class="feature-icon navy"><?= icon('phone', 24) ?></div>
                <h3>โทรศัพท์</h3>
                <p class="navy">02-123-4567</p>
            </div>
            <div class="contact-card">
                <div class="feature-icon green"><?= icon('message', 24) ?></div>
                <h3>LINE</h3>
                <p class="green">@g2ksolar</p>
            </div>
            <div class="contact-card">
                <div class="feature-icon gold"><?= icon('mail', 24) ?></div>
                <h3>อีเมล</h3>
                <p class="gold">info@g2k.co.th</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/store_footer.php'; ?>

<a href="<?= url('/store/cart.php') ?>" class="float-cart" aria-label="ตะกร้าสินค้า"><?= icon('cart', 22) ?></a>

<script>
function addToCartStore(id, name, price) {
    fetch('<?= url('/api/cart.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'add', id, name, price, qty: 1})
    }).then(r => r.json()).then(data => {
        document.getElementById('cartCount').textContent = data.count || 0;
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:100px;right:28px;background:var(--gray-900);color:var(--white);padding:14px 24px;border-radius:12px;font-weight:600;font-size:.9rem;z-index:200;box-shadow:0 8px 24px rgba(0,0,0,.2);animation:fadeIn .3s ease';
        toast.textContent = 'เพิ่มลงตะกร้าแล้ว';
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 2000);
    });
}
</script>
</body>
</html>
