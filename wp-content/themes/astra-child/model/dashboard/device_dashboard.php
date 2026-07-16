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
        'Available' => ['color' => '#6ABF57', 'bg' => 'rgba(106, 191, 87, 0.08)', 'icon' => '✅'],
        'In Use' => ['color' => '#F05353', 'bg' => 'rgba(240, 83, 83, 0.08)', 'icon' => '🚫'],
        'Maintenance' => ['color' => '#FDB840', 'bg' => 'rgba(253, 184, 64, 0.08)', 'icon' => '🛠️'],
        'Retired' => ['color' => '#919191', 'bg' => 'rgba(145, 145, 145, 0.08)', 'icon' => '🗑️'],
    ];

    // Build summary map
    $summary_map = [];
    foreach ($status_summary as $row) {
        $summary_map[$row->Status] = intval($row->count);
    }

    // Category config
    $category_config = [
        ['label' => 'All Devices', 'count' => $total_devices, 'color' => '#1976D2', 'icon' => '📊'],
        ['label' => 'Monitor', 'count' => $total_monitor, 'color' => '#FDB840', 'icon' => '🖥️'],
        ['label' => 'Laptop', 'count' => $total_laptop, 'color' => '#15A5DA', 'icon' => '💻'],
        ['label' => 'Accessories', 'count' => $total_accessories, 'color' => '#6ABF57', 'icon' => '🔌'],
    ];

    ob_start();
    ?>

    <!-- ===== SECTION 1: Category Summary Cards ===== -->
    <div class="md-dashboard">
        <div class="md-summary-row">
            <?php foreach ($category_config as $cat):
                $percent = $total_devices > 0 ? round(($cat['count'] / $total_devices) * 100, 1) : 0;
                $is_total = ($cat['label'] === 'All Devices');
                ?>
                <div class="md-summary-card">
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
                    <div class="md-summary-number" style="color: <?= $cat['color'] ?>;">
                        <?= number_format($cat['count']) ?>
                    </div>
                    <span class="md-summary-unit">unit<?= $cat['count'] > 1 ? 's' : '' ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 2: Status Cards ===== -->
        <div class="md-status-row">
            <?php foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                ?>
                <div class="md-status-card">
                    <div class="md-status-indicator" style="background: <?= $config['color'] ?>;"></div>
                    <div class="md-status-content">
                        <div class="md-status-top">
                            <span class="md-status-label"><?= esc_html($status) ?></span>
                            <span class="md-status-emoji"><?= $config['icon'] ?></span>
                        </div>
                        <div class="md-status-number"><?= $count ?></div>
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
                <div class="md-chart-content">
                    <div class="md-donut-wrap">
                        <?php
                        $p_monitor = $total_devices > 0 ? ($total_monitor / $total_devices) * 100 : 0;
                        $p_laptop = $total_devices > 0 ? ($total_laptop / $total_devices) * 100 : 0;
                        $p_accessories = $total_devices > 0 ? ($total_accessories / $total_devices) * 100 : 0;
                        $circumference = 2 * 3.14159 * 60;
                        $offset_monitor = 0;
                        $offset_laptop = $p_monitor;
                        $offset_accessories = $p_monitor + $p_laptop;
                        ?>
                        <svg class="md-donut" viewBox="0 0 150 150">
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#E0E0E0" stroke-width="18" />
                            <!-- Monitor -->
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#FDB840" stroke-width="18"
                                stroke-dasharray="<?= ($p_monitor / 100) * $circumference ?> <?= $circumference ?>"
                                stroke-dashoffset="<?= -($offset_monitor / 100) * $circumference ?>"
                                transform="rotate(-90 75 75)" stroke-linecap="round" />
                            <!-- Laptop -->
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#15A5DA" stroke-width="18"
                                stroke-dasharray="<?= ($p_laptop / 100) * $circumference ?> <?= $circumference ?>"
                                stroke-dashoffset="<?= -($offset_laptop / 100) * $circumference ?>"
                                transform="rotate(-90 75 75)" stroke-linecap="round" />
                            <!-- Accessories -->
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#6ABF57" stroke-width="18"
                                stroke-dasharray="<?= ($p_accessories / 100) * $circumference ?> <?= $circumference ?>"
                                stroke-dashoffset="<?= -($offset_accessories / 100) * $circumference ?>"
                                transform="rotate(-90 75 75)" stroke-linecap="round" />
                        </svg>
                        <div class="md-donut-center">
                            <span class="md-donut-label">Total</span>
                            <span class="md-donut-value"><?= number_format($total_devices) ?></span>
                        </div>
                    </div>
                    <div class="md-chart-legend">
                        <div class="md-legend-item">
                            <span class="md-legend-dot" style="background: #FDB840;"></span>
                            <span class="md-legend-text">Monitor</span>
                            <span class="md-legend-count"><?= $total_monitor ?></span>
                        </div>
                        <div class="md-legend-item">
                            <span class="md-legend-dot" style="background: #15A5DA;"></span>
                            <span class="md-legend-text">Laptop</span>
                            <span class="md-legend-count"><?= $total_laptop ?></span>
                        </div>
                        <div class="md-legend-item">
                            <span class="md-legend-dot" style="background: #6ABF57;"></span>
                            <span class="md-legend-text">Accessories</span>
                            <span class="md-legend-count"><?= $total_accessories ?></span>
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
            background: transparent;
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
            background: transparent;
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
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .md-chart-card {
            background: transparent;
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

            .md-chart-row {
                grid-template-columns: 1fr;
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
    </style>

    <?php
    return ob_get_clean();
}
add_shortcode('device_dashboard', 'device_dashboard');

