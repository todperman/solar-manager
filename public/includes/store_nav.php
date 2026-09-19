<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$cartCount = $cartCount ?? (isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0);
$storePage = basename($_SERVER['PHP_SELF']);
?>
<nav class="store-nav">
    <a href="<?= url('/store/') ?>" class="logo" aria-label="G2K Solar & Water Pump Solution">
        <span class="logo-plate">
            <img src="<?= url('/assets/img/g2k-logo.png') ?>" alt="G2K" class="brand-logo-img">
        </span>
    </a>
    <button class="nav-toggle" type="button" aria-label="เมนู" onclick="document.getElementById('storeLinks').classList.toggle('open')"><?= icon('menu', 18) ?></button>
    <div class="nav-links" id="storeLinks">
        <a href="<?= url('/store/') ?>" class="<?= $storePage === 'index.php' ? 'active' : '' ?>">หน้าแรก</a>
        <a href="<?= url('/store/#products') ?>">สินค้า</a>
        <a href="<?= url('/store/packages.php') ?>" class="<?= $storePage === 'packages.php' ? 'active' : '' ?>">แพ็กเกจ</a>
        <a href="<?= url('/store/#contact') ?>">ติดต่อเรา</a>
        <a href="<?= url('/store/cart.php') ?>" class="cart-btn"><?= icon('cart', 16) ?> ตะกร้า <span class="count" id="cartCount"><?= $cartCount ?></span></a>
    </div>
</nav>
