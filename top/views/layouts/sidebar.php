<?php
// views/layouts/sidebar.php
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span>آینه سیاه</span>
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <a href="?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>داشبورد</span>
        </a>
        <a href="?page=users" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>مدیریت کاربران</span>
        </a>
        <a href="?page=accounting" class="nav-item <?= $currentPage === 'accounting' ? 'active' : '' ?>">
            <i class="fa-solid fa-wallet"></i>
            <span>حسابداری</span>
        </a>
        <a href="?page=settings" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
            <i class="fa-solid fa-sliders"></i>
            <span>تنظیمات</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="admin-details">
                <span class="admin-name"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                <span class="admin-role">مدیر سیستم</span>
            </div>
        </div>
        <a href="?page=logout" class="logout-btn" title="خروج">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</aside>