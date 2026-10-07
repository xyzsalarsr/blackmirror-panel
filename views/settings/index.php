<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="page-title">
            <h2>تنظیمات</h2>
        </div>
    </div>

    <div class="content-area">
        <div class="glass settings-area">
            <form method="POST" action="<?= PUBLIC_URL ?>/index.php?page=settings&action=update">
                <div class="mb-3">
                    <label for="default_subscription" class="form-label">اشتراک پیش‌فرض</label>
                    <textarea class="form-control" id="default_subscription" name="default_subscription"
                              rows="4"><?= htmlspecialchars($settings['default_subscription'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    ذخیره تنظیمات
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
