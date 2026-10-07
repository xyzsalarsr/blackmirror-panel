<?php
// views/auth/login.php
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل</title>
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/fontawesome.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
</head>
<body class="login-body">
<div class="login-bg">
    <div class="login-orb orb-1"></div>
    <div class="login-orb orb-2"></div>
    <div class="login-orb orb-3"></div>
</div>

<div class="login-container">
    <div class="login-card glass">
        <div class="login-header">
            <div class="login-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1>ورود به پنل</h1>
            <p>لطفاً اطلاعات خود را وارد کنید</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label>نام کاربری</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="username" placeholder="نام کاربری" required autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label>رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="رمز عبور" required>
                    <button type="button" class="toggle-password" onclick="togglePassword(this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fa-solid fa-right-to-bracket"></i>
                ورود
            </button>
        </form>
    </div>
</div>

<script>
function togglePassword(btn) {
    const input = btn.closest('.input-wrapper').querySelector('input');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
