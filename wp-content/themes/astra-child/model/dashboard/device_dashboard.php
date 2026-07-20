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
    
    <div class="next-dashboard">
        <!-- ===== SECTION 1: Category Summary Cards ===== -->
        <div class="next-grid">
            <?php
            $delay = 0;
            foreach ($category_config as $cat):
                $percent = $total_devices > 0 ? round(($cat['count'] / $total_devices) * 100, 1) : 0;
                $is_total = ($cat['label'] === 'All Devices');
                ?>
                <div class="next-card slide-up" style="animation-delay: <?= $delay ?>s;">
                    <?php $delay += 0.05; ?>
                    <div class="next-card-header">
                        <span class="next-card-title"><?= esc_html($cat['label']) ?></span>
                        <div class="next-icon-wrapper" style="background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>;">
                            <?= $cat['icon'] ?>
                        </div>
                    </div>
                    <div class="next-card-body">
                        <div class="next-number-wrap">
                            <span class="next-number count-up" data-count="<?= $cat['count'] ?>">0</span>
                        </div>
                        <?php if (!$is_total): ?>
                            <div class="next-trend">
                                <span class="next-badge" style="background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>;">
                                    <?= $percent ?>%
                                </span>
                                <span class="next-trend-text text-muted">of total</span>
                            </div>
                        <?php else: ?>
                            <div class="next-trend">
                                <span class="next-trend-text text-muted" style="visibility: hidden;">-</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 2: Status Cards ===== -->
        <div class="next-grid mt-4">
            <?php
            $delay2 = 0;
            foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                ?>
                <div class="next-card slide-up" style="animation-delay: <?= $delay2 ?>s;">
                    <?php $delay2 += 0.05; ?>
                    <div class="next-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="next-status-dot" style="background: <?= $config['color'] ?>;"></span>
                            <span class="next-card-title"><?= esc_html($status) ?></span>
                        </div>
                        <div class="next-icon-wrapper-sm" style="color: <?= $config['color'] ?>;">
                            <?= $config['icon'] ?>
                        </div>
                    </div>
                    <div class="next-card-body mt-3">
                        <span class="next-number-md count-up" data-count="<?= $count ?>">0</span>
                        <div class="next-progress-wrap mt-2">
                            <div class="next-progress-bar">
                                <div class="next-progress-fill" style="width: 0%; background: <?= $config['color'] ?>;" data-width="<?= $percent ?>%"></div>
                            </div>
                            <span class="next-progress-text"><?= $percent ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 3: Charts ===== -->
        <div class="next-grid-2 mt-4">
            <!-- Donut Chart -->
            <div class="next-card slide-up" style="animation-delay: 0.2s;">
                <h3 class="next-section-title">Device Distribution</h3>
                <div class="next-donut-container mt-4">
                    <div class="next-donut-wrap">
                        <?php
                        $p_monitor = $total_devices > 0 ? ($total_monitor / $total_devices) * 100 : 0;
                        $p_laptop = $total_devices > 0 ? ($total_laptop / $total_devices) * 100 : 0;
                        $p_accessories = $total_devices > 0 ? ($total_accessories / $total_devices) * 100 : 0;
                        $circumference = 2 * 3.14159 * 60;
                        $offset_monitor = 0;
                        $offset_laptop = $p_monitor;
                        $offset_accessories = $p_monitor + $p_laptop;
                        ?>
                        <svg class="next-donut" viewBox="0 0 150 150">
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#f3f4f6" stroke-width="12" />
                            <circle class="donut-segment" cx="75" cy="75" r="60" fill="none" stroke="#FDB840" stroke-width="12"
                                data-dash="<?= ($p_monitor / 100) * $circumference ?> <?= $circumference ?>"
                                stroke-dasharray="0 <?= $circumference ?>"
                                stroke-dashoffset="<?= -($offset_monitor / 100) * $circumference ?>"
                                transform="rotate(-90 75 75)" stroke-linecap="round" />
                            <circle class="donut-segment" cx="75" cy="75" r="60" fill="none" stroke="#15A5DA" stroke-width="12"
                                data-dash="<?= ($p_laptop / 100) * $circumference ?> <?= $circumference ?>"
                                stroke-dasharray="0 <?= $circumference ?>"
                                stroke-dashoffset="<?= -($offset_laptop / 100) * $circumference ?>"
                                transform="rotate(-90 75 75)" stroke-linecap="round" />
                            <circle class="donut-segment" cx="75" cy="75" r="60" fill="none" stroke="#6ABF57" stroke-width="12"
                                data-dash="<?= ($p_accessories / 100) * $circumference ?> <?= $circumference ?>"
                                stroke-dasharray="0 <?= $circumference ?>"
                                stroke-dashoffset="<?= -($offset_accessories / 100) * $circumference ?>"
                                transform="rotate(-90 75 75)" stroke-linecap="round" />
                        </svg>
                        <div class="next-donut-center">
                            <span class="next-donut-value count-up" data-count="<?= $total_devices ?>">0</span>
                            <span class="next-donut-label">Total</span>
                        </div>
                    </div>
                    <div class="next-legend-wrap">
                        <div class="next-legend-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="next-legend-dot" style="background: #FDB840;"></span>
                                <span class="next-legend-label">Monitor</span>
                            </div>
                            <span class="next-legend-value count-up" data-count="<?= $total_monitor ?>">0</span>
                        </div>
                        <div class="next-legend-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="next-legend-dot" style="background: #15A5DA;"></span>
                                <span class="next-legend-label">Laptop</span>
                            </div>
                            <span class="next-legend-value count-up" data-count="<?= $total_laptop ?>">0</span>
                        </div>
                        <div class="next-legend-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="next-legend-dot" style="background: #6ABF57;"></span>
                                <span class="next-legend-label">Accessories</span>
                            </div>
                            <span class="next-legend-value count-up" data-count="<?= $total_accessories ?>">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="next-card slide-up" style="animation-delay: 0.25s;">
                <h3 class="next-section-title">Status Overview</h3>
                <div class="next-status-list mt-4">
                    <?php foreach ($status_config as $status => $config):
                        $count = $summary_map[$status] ?? 0;
                        $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                        ?>
                        <div class="next-list-item">
                            <div class="next-list-left">
                                <div class="next-icon-wrapper-sm" style="background: <?= $config['color'] ?>15; color: <?= $config['color'] ?>;">
                                    <?= $config['icon'] ?>
                                </div>
                                <span class="next-list-name"><?= esc_html($status) ?></span>
                            </div>
                            <div class="next-list-right">
                                <div class="next-progress-wrap-sm">
                                    <div class="next-progress-bar">
                                        <div class="next-progress-fill" style="width: 0%; background: <?= $config['color'] ?>;" data-width="<?= $percent ?>%"></div>
                                    </div>
                                </div>
                                <span class="next-list-value count-up" data-count="<?= $count ?>">0</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Next.js / Vercel Inspired UI */
        .next-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #111827;
            background: transparent !important;
            padding-bottom: 2rem;
            padding-top: 0.5rem;
        }

        .next-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }

        .next-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .next-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .next-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            transform: translateY(-2px);
        }

        .next-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .next-card-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            letter-spacing: -0.01em;
        }

        .next-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .next-icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .next-icon-wrapper-sm {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .next-number {
            font-size: 2.25rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.025em;
            color: #111827;
        }

        .next-number-md {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
            display: block;
        }

        .next-card-body {
            margin-top: 1.25rem;
        }

        .next-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .next-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.15rem 0.6rem;
            border-radius: 9999px;
            letter-spacing: -0.01em;
        }

        .next-trend-text {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .next-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .next-progress-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .next-progress-wrap-sm {
            width: 100px;
        }

        .next-progress-bar {
            flex: 1;
            height: 6px;
            background: #f3f4f6;
            border-radius: 9999px;
            overflow: hidden;
        }

        .next-progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .next-progress-text {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
            min-width: 32px;
            text-align: right;
        }

        .next-donut-container {
            display: flex;
            align-items: center;
            gap: 2.5rem;
        }

        .next-donut-wrap {
            position: relative;
            width: 150px;
            height: 150px;
            flex-shrink: 0;
        }

        .next-donut {
            width: 100%;
            height: 100%;
            transform: scale(1);
            transition: transform 0.3s ease;
        }
        
        .next-donut:hover {
            transform: scale(1.03);
        }

        .donut-segment {
            transition: stroke-dasharray 1.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .next-donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            display: flex;
            flex-direction: column;
        }

        .next-donut-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
            letter-spacing: -0.025em;
        }

        .next-donut-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        .next-legend-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .next-legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .next-legend-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .next-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .next-legend-label {
            font-size: 0.875rem;
            color: #4b5563;
            font-weight: 500;
        }

        .next-legend-value {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        .next-status-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .next-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0.5rem;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        
        .next-list-item:hover {
            background: #f9fafb;
            border-color: #f3f4f6;
            transform: translateX(2px);
        }

        .next-list-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .next-list-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }

        .next-list-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .next-list-value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #111827;
            min-width: 24px;
            text-align: right;
        }

        .mt-4 { margin-top: 1.5rem; }

        /* Animations */
        .slide-up {
            opacity: 0;
            transform: translateY(15px);
            animation: nextSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes nextSlideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .next-grid { grid-template-columns: repeat(2, 1fr); }
            .next-grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .next-grid { grid-template-columns: 1fr; }
            .next-donut-container { flex-direction: column; gap: 1.5rem; }
            .next-legend-wrap { width: 100%; }
        }
    </style>

    <script>
        function initNextDashboard() {
            // Smooth Count Up Animation (Spring-like)
            const countElements = document.querySelectorAll('.count-up');
            countElements.forEach(el => {
                const target = parseInt(el.getAttribute('data-count'), 10);
                const duration = 1800; // ms
                
                if (target > 0) {
                    const easeOutQuart = t => 1 - (--t) * t * t * t;
                    let startTime = null;
                    
                    const step = (timestamp) => {
                        if (!startTime) startTime = timestamp;
                        const progress = Math.min((timestamp - startTime) / duration, 1);
                        const easeProgress = easeOutQuart(progress);
                        
                        const current = Math.floor(easeProgress * target);
                        el.innerText = current.toLocaleString();
                        
                        if (progress < 1) {
                            window.requestAnimationFrame(step);
                        } else {
                            el.innerText = target.toLocaleString();
                        }
                    };
                    window.requestAnimationFrame(step);
                } else {
                    el.innerText = "0";
                }
            });

            // SVG Donut Chart Animation (Delayed for effect)
            setTimeout(() => {
                const segments = document.querySelectorAll('.donut-segment');
                segments.forEach(segment => {
                    const targetDash = segment.getAttribute('data-dash');
                    if (targetDash) {
                        segment.setAttribute('stroke-dasharray', targetDash);
                    }
                });
            }, 150);

            // Progress Bar Animation
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.next-progress-fill');
                progressBars.forEach(bar => {
                    const targetWidth = bar.getAttribute('data-width');
                    if (targetWidth) {
                        bar.style.width = targetWidth;
                    }
                });
            }, 250);
        }

        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", initNextDashboard);
        } else {
            initNextDashboard();
        }
    </script>


    <?php
    return ob_get_clean();
}
add_shortcode('device_dashboard', 'device_dashboard');

