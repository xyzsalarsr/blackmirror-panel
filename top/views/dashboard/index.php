<?php
// views/dashboard/index.php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$labels   = [];
$revenues = [];
$signups  = [];

// build chart arrays
$chartByDate = [];
foreach ($chart_data as $row) {
    $chartByDate[$row['date']] = $row['revenue'];
}

$userModel2 = new User();
$allUsers   = $userModel2->getAll();
$signupByDate = [];
foreach ($allUsers as $u) {
    $d = date('Y-m-d', strtotime($u['created_at']));
    $signupByDate[$d] = ($signupByDate[$d] ?? 0) + 1;
}

for ($i = 6; $i >= 0; $i--) {
    $date       = date('Y-m-d', strtotime("-{$i} days"));
    $labels[]   = date('m/d', strtotime($date));
    $revenues[] = $chartByDate[$date] ?? 0;
    $signups[]  = $signupByDate[$date] ?? 0;
}
?>

<div class="main-content">
    <div class="topbar">
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="page-title">
            <h2>داشبورد</h2>
            <span>خلاصه وضعیت سیستم</span>
        </div>
    </div>

    <div class="content-area">
        <div class="stats-grid">
            <div class="stat-card glass animate-in" style="--delay:0.1s">
                <div class="stat-icon icon-blue">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">درآمد روزانه</span>
                    <span class="stat-value"><?= number_format($daily_revenue) ?> <small>تومان</small></span>
                </div>
                <div class="stat-glow glow-blue"></div>
            </div>

            <div class="stat-card glass animate-in" style="--delay:0.2s">
                <div class="stat-icon icon-purple">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">درآمد ماهانه</span>
                    <span class="stat-value"><?= number_format($monthly_revenue) ?> <small>تومان</small></span>
                </div>
                <div class="stat-glow glow-purple"></div>
            </div>

            <div class="stat-card glass animate-in" style="--delay:0.3s">
                <div class="stat-icon icon-green">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">ثبت‌نام روزانه</span>
                    <span class="stat-value"><?= $daily_signups ?> <small>کاربر</small></span>
                </div>
                <div class="stat-glow glow-green"></div>
            </div>

            <div class="stat-card glass animate-in" style="--delay:0.4s">
                <div class="stat-icon icon-orange">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">ثبت‌نام ماهانه</span>
                    <span class="stat-value"><?= $monthly_signups ?> <small>کاربر</small></span>
                </div>
                <div class="stat-glow glow-orange"></div>
            </div>
        </div>

        <div class="chart-card glass animate-in" style="--delay:0.5s">
            <div class="chart-header">
                <h3>نمودار ۷ روز اخیر</h3>
                <div class="chart-legend">
                    <span class="legend-item"><span class="dot dot-blue"></span>درآمد</span>
                    <span class="legend-item"><span class="dot dot-green"></span>ثبت‌نام</span>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="mainChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
window.chartLabels   = <?= json_encode($labels) ?>;
window.chartRevenues = <?= json_encode($revenues) ?>;
window.chartSignups  = <?= json_encode($signups) ?>;
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
