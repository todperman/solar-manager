<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$user = currentUser();
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> | G2K</title>
    <link rel="icon" href="<?= url('/assets/img/g2k-logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-plate">
                <img src="<?= url('/assets/img/g2k-logo.png') ?>" alt="G2K" class="sidebar-logo">
            </div>
            <span class="sidebar-badge">Enterprise Back Office</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">ภาพรวม</div>
            <a href="<?= url('/admin/') ?>" class="<?= $current === 'index' ? 'active' : '' ?>">
                <?= icon('dashboard') ?> Dashboard
            </a>

            <div class="nav-section">จัดการสินค้า</div>
            <a href="<?= url('/admin/categories.php') ?>" class="<?= $current === 'categories' ? 'active' : '' ?>">
                <?= icon('folder') ?> หมวดหมู่
            </a>
            <a href="<?= url('/admin/products.php') ?>" class="<?= $current === 'products' ? 'active' : '' ?>">
                <?= icon('package') ?> สินค้า
            </a>
            <a href="<?= url('/admin/stock.php') ?>" class="<?= $current === 'stock' ? 'active' : '' ?>">
                <?= icon('clipboard') ?> Stock สินค้า
            </a>

            <div class="nav-section">แพ็กเกจ & โปรโมชั่น</div>
            <a href="<?= url('/admin/packages.php') ?>" class="<?= $current === 'packages' ? 'active' : '' ?>">
                <?= icon('tag') ?> แพ็กเกจ
            </a>
            <a href="<?= url('/admin/promotions.php') ?>" class="<?= $current === 'promotions' ? 'active' : '' ?>">
                <?= icon('percent') ?> โปรโมชั่น
            </a>

            <div class="nav-section">การขาย</div>
            <a href="<?= url('/admin/pos.php') ?>" class="<?= $current === 'pos' ? 'active' : '' ?>">
                <?= icon('cart') ?> POS หน้าร้าน
            </a>
            <a href="<?= url('/admin/orders.php') ?>" class="<?= $current === 'orders' ? 'active' : '' ?>">
                <?= icon('file') ?> คำสั่งซื้อ
            </a>
            <a href="<?= url('/admin/quotations.php') ?>" class="<?= $current === 'quotations' ? 'active' : '' ?>">
                <?= icon('file-pen') ?> ใบเสนอราคา
            </a>

            <div class="nav-section">ลูกค้า & รายงาน</div>
            <a href="<?= url('/admin/customers.php') ?>" class="<?= $current === 'customers' ? 'active' : '' ?>">
                <?= icon('users') ?> ลูกค้า (CRM)
            </a>
            <a href="<?= url('/admin/reports.php') ?>" class="<?= $current === 'reports' ? 'active' : '' ?>">
                <?= icon('chart') ?> รายงาน
            </a>

            <div class="nav-section">ตั้งค่า</div>
            <a href="<?= url('/admin/settings.php') ?>" class="<?= $current === 'settings' ? 'active' : '' ?>">
                <?= icon('settings') ?> ตั้งค่าระบบ
            </a>
            <a href="<?= url('/store/') ?>" target="_blank">
                <?= icon('store') ?> ดูหน้าร้าน
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="avatar"><?= mb_substr($user['name'], 0, 1) ?></div>
            <div class="info">
                <div class="name"><?= sanitize($user['name']) ?></div>
                <div class="role"><?= $user['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน' ?></div>
            </div>
            <a href="<?= url('/logout.php') ?>" class="logout" title="ออกจากระบบ"><?= icon('logout', 18) ?></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title"><?= $pageTitle ?? 'Dashboard' ?></div>
            <div class="actions">
                <span class="topbar-date"><?= date('d M Y') ?></span>
                <button class="btn btn-sm btn-outline" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none" id="menuBtn"><?= icon('menu', 16) ?></button>
            </div>
        </div>
        <div class="page-content">
            <?php
            $success = flash('success');
            $error = flash('error');
            if ($success): ?>
                <div class="alert alert-success"><?= icon('check-circle', 16) ?> <?= sanitize($success) ?></div>
            <?php endif;
            if ($error): ?>
                <div class="alert alert-danger"><?= icon('alert', 16) ?> <?= sanitize($error) ?></div>
            <?php endif; ?>
