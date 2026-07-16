<?php
function device_dashboard()
{
    global $wpdb;
    $table_device_wn = 'DevicesWithNames';

    $total_devices = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn");
    $total_monitor = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn WHERE Category = 'Monitor'");
    $total_laptop = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn WHERE Category = 'Laptop'");
    $total_accessories = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn WHERE Category = 'Accessories'");


    $status_summary = $wpdb->get_results("
    SELECT Status, COUNT(*) as count
    FROM $table_device_wn
    GROUP BY Status
");

    $status_config = [
        'Available' => ['color' => '#6ABF57', 'bg' => 'rgba(106, 191, 87, 0.08)', 'icon' => '<i class="fa-solid fa-circle-check"></i>'],
        'In Use' => ['color' => '#F05353', 'bg' => 'rgba(240, 83, 83, 0.08)', 'icon' => '<i class="fa-solid fa-ban"></i>'],
        'Maintenance' => ['color' => '#FDB840', 'bg' => 'rgba(253, 184, 64, 0.08)', 'icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>'],
        'Retired' => ['color' => '#919191', 'bg' => 'rgba(145, 145, 145, 0.08)', 'icon' => '<i class="fa-solid fa-trash-can"></i>'],
    ];

    // Build summary map
    $summary_map = [];
    foreach ($status_summary as $row) {
        $summary_map[$row->Status] = intval($row->count);
    }

    // Category config
    $category_config = [
        ['label' => 'All Devices', 'count' => $total_devices, 'color' => '#1976D2', 'icon' => '<i class="fa-solid fa-chart-simple"></i>'],
        ['label' => 'Monitor', 'count' => $total_monitor, 'color' => '#FDB840', 'icon' => '<i class="fa-solid fa-desktop"></i>'],
        ['label' => 'Laptop', 'count' => $total_laptop, 'color' => '#15A5DA', 'icon' => '<i class="fa-solid fa-laptop"></i>'],
        ['label' => 'Accessories', 'count' => $total_accessories, 'color' => '#6ABF57', 'icon' => '<i class="fa-solid fa-plug"></i>'],
    ];

    ob_start();
    ?>

    <!-- FontAwesome & Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ===== SECTION 1: Category Summary Cards ===== -->
    <div class="md-dashboard">
        <div class="md-summary-row">
            <?php
            $delay = 0;
            foreach ($category_config as $cat):
                $percent = $total_devices > 0 ? round(($cat['count'] / $total_devices) * 100, 1) : 0;
                $is_total = ($cat['label'] === 'All Devices');
                ?>
                <div class="md-summary-card"
                    style="animation: slideUpFade 0.5s ease backwards; animation-delay: <?= $delay ?>s;">
                    <?php $delay += 0.1; ?>
                    <div class="md-summary-header">
                        <span class="md-summary-title"><?= esc_html($cat['label']) ?></span>
                        <span class="md-summary-icon"
                            style="background: <?= $cat['color'] ?>1A; color: <?= $cat['color'] ?>;"><?= $cat['icon'] ?></span>
                    </div>
                    <div class="md-summary-body">
                        <?php if (!$is_total): ?>
                            <div class="md-summary-trend" style="color: <?= $cat['color'] ?>;">
                                <span class="md-trend-badge"
                                    style="background: <?= $cat['color'] ?>1A; color: <?= $cat['color'] ?>;">
                                    <?= $percent ?>%
                                </span>
                                <svg class="md-sparkline" viewBox="0 0 60 20" style="stroke: <?= $cat['color'] ?>;">
                                    <polyline points="0,18 10,14 20,16 30,8 40,12 50,6 60,10" fill="none" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="md-summary-number count-up" data-count="<?= $cat['count'] ?>"
                        style="color: <?= $cat['color'] ?>;">
                        0
                    </div>
                    <span class="md-summary-unit">unit<?= $cat['count'] > 1 ? 's' : '' ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 2: Status Cards ===== -->
        <div class="md-status-row">
            <?php
            $delay2 = 0;
            foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                ?>
                <div class="md-status-card"
                    style="animation: slideUpFade 0.5s ease backwards; animation-delay: <?= $delay2 ?>s;">
                    <?php $delay2 += 0.1; ?>
                    <div class="md-status-indicator" style="background: <?= $config['color'] ?>;"></div>
                    <div class="md-status-content">
                        <div class="md-status-top">
                            <span class="md-status-label"><?= esc_html($status) ?></span>
                            <span class="md-status-emoji"><?= $config['icon'] ?></span>
                        </div>
                        <div class="md-status-number count-up" data-count="<?= $count ?>">0</div>
                        <div class="md-status-bar-wrap">
                            <div class="md-status-bar">
                                <div class="md-status-bar-fill"
                                    style="width: <?= $percent ?>%; background: <?= $config['color'] ?>;"></div>
                            </div>
                            <span class="md-status-percent"><?= $percent ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 3: Donut Chart Overview ===== -->
        <div class="md-chart-row">
            <div class="md-chart-card">
                <h4 class="md-chart-title">Device Distribution</h4>
                <div class="md-donut-wrap"
                    style="position: relative; height: 180px; width: 100%; display: flex; align-items: center; justify-content: center;">
                    <?php
                    $p_monitor = $total_devices > 0 ? ($total_monitor / $total_devices) * 100 : 0;
                    $p_laptop = $total_devices > 0 ? ($total_laptop / $total_devices) * 100 : 0;
                    $p_accessories = $total_devices > 0 ? ($total_accessories / $total_devices) * 100 : 0;
                    $circumference = 2 * 3.14159 * 60;
                    $offset_monitor = 0;
                    $offset_laptop = $p_monitor;
                    $offset_accessories = $p_monitor + $p_laptop;
                    ?>
                    <svg class="md-donut" viewBox="0 0 150 150" style="width: 150px; height: 150px;">
                        <circle cx="75" cy="75" r="60" fill="none" stroke="#E0E0E0" stroke-width="18" />
                        <!-- Monitor -->
                        <circle class="donut-segment" cx="75" cy="75" r="60" fill="none" stroke="#FDB840" stroke-width="18"
                            data-dash="<?= ($p_monitor / 100) * $circumference ?> <?= $circumference ?>"
                            stroke-dasharray="0 <?= $circumference ?>"
                            stroke-dashoffset="<?= -($offset_monitor / 100) * $circumference ?>"
                            transform="rotate(-90 75 75)" stroke-linecap="round"
                            style="transition: stroke-dasharray 1.5s ease-out;" />
                        <!-- Laptop -->
                        <circle class="donut-segment" cx="75" cy="75" r="60" fill="none" stroke="#15A5DA" stroke-width="18"
                            data-dash="<?= ($p_laptop / 100) * $circumference ?> <?= $circumference ?>"
                            stroke-dasharray="0 <?= $circumference ?>"
                            stroke-dashoffset="<?= -($offset_laptop / 100) * $circumference ?>"
                            transform="rotate(-90 75 75)" stroke-linecap="round"
                            style="transition: stroke-dasharray 1.5s ease-out;" />
                        <!-- Accessories -->
                        <circle class="donut-segment" cx="75" cy="75" r="60" fill="none" stroke="#6ABF57" stroke-width="18"
                            data-dash="<?= ($p_accessories / 100) * $circumference ?> <?= $circumference ?>"
                            stroke-dasharray="0 <?= $circumference ?>"
                            stroke-dashoffset="<?= -($offset_accessories / 100) * $circumference ?>"
                            transform="rotate(-90 75 75)" stroke-linecap="round"
                            style="transition: stroke-dasharray 1.5s ease-out;" />
                    </svg>
                    <div class="md-donut-center"
                        style="position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                        <span class="md-donut-label">Total</span>
                        <span class="md-donut-value count-up" data-count="<?php echo $total_devices; ?>">0</span>
                    </div>
                </div>
                <div class="md-chart-legend">
                    <div class="md-legend-item">
                        <span class="md-legend-dot" style="background: #FDB840;"></span>
                        <span class="md-legend-text">Monitor</span>
                        <span class="md-legend-count count-up" data-count="<?php echo $total_monitor; ?>">0</span>
                    </div>
                    <div class="md-legend-item">
                        <span class="md-legend-dot" style="background: #15A5DA;"></span>
                        <span class="md-legend-text">Laptop</span>
                        <span class="md-legend-count count-up" data-count="<?php echo $total_laptop; ?>">0</span>
                    </div>
                    <div class="md-legend-item">
                        <span class="md-legend-dot" style="background: #6ABF57;"></span>
                        <span class="md-legend-text">Accessories</span>
                        <span class="md-legend-count count-up" data-count="<?php echo $total_accessories; ?>">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="md-chart-card">
            <h4 class="md-chart-title">Status Overview</h4>
            <div class="md-status-list">
                <?php foreach ($status_config as $status => $config):
                    $count = $summary_map[$status] ?? 0;
                    $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                    ?>
                    <div class="md-status-list-item">
                        <div class="md-status-list-left">
                            <span class="md-legend-dot" style="background: <?= $config['color'] ?>;"></span>
                            <span class="md-status-list-name"><?= esc_html($status) ?></span>
                        </div>
                        <div class="md-status-list-right">
                            <div class="md-status-list-bar">
                                <div class="md-status-list-bar-fill"
                                    style="width: <?= $percent ?>%; background: <?= $config['color'] ?>;"></div>
                            </div>
                            <span class="md-status-list-count"><?= $count ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    </div>

    <style>
        /* ===== MD DASHBOARD LAYOUT ===== */
        .md-dashboard {
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background: transparent !important;
        }

        /* ===== SUMMARY CARDS ROW ===== */
        .md-summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .md-summary-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.3s ease, transform 0.25s ease;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .md-summary-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }

        .md-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .md-summary-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(0, 0, 0, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .md-summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .md-summary-body {
            margin-bottom: 8px;
            min-height: 28px;
        }

        .md-summary-trend {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .md-trend-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .md-sparkline {
            width: 60px;
            height: 20px;
        }

        .md-summary-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .md-summary-unit {
            font-size: 0.8rem;
            color: rgba(0, 0, 0, 0.45);
            font-weight: 400;
            margin-top: 2px;
        }

        /* ===== STATUS CARDS ROW ===== */
        .md-status-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .md-status-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.06);
            display: flex;
            overflow: hidden;
            transition: box-shadow 0.3s ease, transform 0.25s ease;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .md-status-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }

        .md-status-indicator {
            width: 5px;
            min-height: 100%;
            flex-shrink: 0;
        }

        .md-status-content {
            padding: 18px 20px;
            flex: 1;
        }

        .md-status-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .md-status-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(0, 0, 0, 0.6);
        }

        .md-status-emoji {
            font-size: 1.3rem;
            opacity: 0.7;
        }

        .md-status-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.87);
            margin-bottom: 12px;
        }

        .md-status-bar-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .md-status-bar {
            flex: 1;
            height: 6px;
            background: #F0F0F0;
            border-radius: 3px;
            overflow: hidden;
        }

        .md-status-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .md-status-percent {
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(0, 0, 0, 0.45);
            min-width: 36px;
            text-align: right;
        }

        /* ===== CHART ROW ===== */
        .md-chart-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .md-chart-card {
            flex: 1;
            min-width: 0;
            /* Prevents overflow */
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.3s ease, transform 0.25s ease;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .md-chart-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
        }

        .md-chart-title {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.87);
            margin: 0 0 24px 0;
            letter-spacing: -0.01em;
        }

        .md-chart-content {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        /* ===== DONUT CHART ===== */
        .md-donut-wrap {
            position: relative;
            width: 160px;
            height: 160px;
            flex-shrink: 0;
        }

        .md-donut {
            width: 100%;
            height: 100%;
        }

        .md-donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .md-donut-label {
            display: block;
            font-size: 0.75rem;
            color: rgba(0, 0, 0, 0.45);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .md-donut-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.87);
            line-height: 1.2;
        }

        /* ===== LEGEND ===== */
        .md-chart-legend {
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex: 1;
        }

        .md-legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .md-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .md-legend-text {
            font-size: 0.875rem;
            color: rgba(0, 0, 0, 0.6);
            flex: 1;
        }

        .md-legend-count {
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.87);
        }

        /* ===== STATUS LIST (Right Panel) ===== */
        .md-status-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .md-status-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .md-status-list-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 120px;
        }

        .md-status-list-name {
            font-size: 0.875rem;
            color: rgba(0, 0, 0, 0.7);
            font-weight: 400;
        }

        .md-status-list-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .md-status-list-bar {
            flex: 1;
            height: 8px;
            background: #F0F0F0;
            border-radius: 4px;
            overflow: hidden;
        }

        .md-status-list-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .md-status-list-count {
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.87);
            min-width: 30px;
            text-align: right;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .md-summary-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .md-status-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 800px) {
            .md-chart-row {
                flex-direction: column;
            }
        }

        @media (max-width: 600px) {
            .md-summary-row {
                grid-template-columns: 1fr;
            }

            .md-status-row {
                grid-template-columns: 1fr;
            }

            .md-chart-content {
                flex-direction: column;
                gap: 24px;
            }
        }

        /* ===== ANIMATIONS ===== */
        .md-summary-card,
        .md-status-card,
        .md-chart-card {
            animation: mdSlideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .md-summary-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .md-summary-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .md-summary-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .md-summary-card:nth-child(4) {
            animation-delay: 0.2s;
        }

        .md-status-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .md-status-card:nth-child(2) {
            animation-delay: 0.15s;
        }

        .md-status-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        .md-status-card:nth-child(4) {
            animation-delay: 0.25s;
        }

        @keyframes mdSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUpFade {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        function initReactLikeDashboard() {
            // --- 1. Number Count Up Animation ---
            const countElements = document.querySelectorAll('.count-up');
            countElements.forEach(el => {
                const target = parseInt(el.getAttribute('data-count'), 10);
                const duration = 1500; // ms
                let current = 0;

                if (target > 0) {
                    const timer = setInterval(() => {
                        current += Math.ceil(target / 50);
                        if (current >= target) {
                            el.innerText = target.toLocaleString();
                            clearInterval(timer);
                        } else {
                            el.innerText = current.toLocaleString();
                        }
                    }, 30);
                } else {
                    el.innerText = "0";
                }
            });

            // --- 2. SVG Donut Chart Animation ---
            setTimeout(() => {
                const segments = document.querySelectorAll('.donut-segment');
                segments.forEach(segment => {
                    const targetDash = segment.getAttribute('data-dash');
                    if (targetDash) {
                        segment.setAttribute('stroke-dasharray', targetDash);
                    }
                });
            }, 100);
        }

        // Run immediately if DOM is already ready, otherwise wait for DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", initReactLikeDashboard);
        } else {
            initReactLikeDashboard();
        }
    </script>


    <?php
    return ob_get_clean();
}
add_shortcode('device_dashboard', 'device_dashboard');

