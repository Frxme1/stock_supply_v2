<?php
function device_dashboard_monitor()
{
    global $wpdb;
    $table_device_wn = 'DevicesWithNames';

    $total_monitor = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn WHERE Category = 'Monitor'");
    $total_devices = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn");

    $status_summary = $wpdb->get_results("
        SELECT Status, COUNT(*) as count
        FROM $table_device_wn
        WHERE Category = 'Monitor'
        GROUP BY Status
    ");

    $status_config = [
        'Available'   => ['color' => '#6ABF57', 'icon' => '<i class="fa-solid fa-circle-check"></i>'],
        'In Use'      => ['color' => '#F05353', 'icon' => '<i class="fa-solid fa-ban"></i>'],
        'Maintenance' => ['color' => '#FDB840', 'icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>'],
        'Retired'     => ['color' => '#919191', 'icon' => '<i class="fa-solid fa-trash-can"></i>'],
        'Lost'        => ['color' => '#EF4444', 'icon' => '<i class="fa-solid fa-triangle-exclamation"></i>'],
    ];

    $status_urls = [
        'Available'   => home_url('/monitor/?filter_status=Available'),
        'In Use'      => home_url('/monitor/?filter_status=In+Use'),
        'Maintenance' => home_url('/maintenance/'),
        'Retired'     => home_url('/monitor/?filter_status=Retired'),
        'Lost'        => home_url('/monitor/?filter_status=Lost'),
    ];

    // Map count per status
    $summary_map = [];
    foreach ($status_summary as $row) {
        $summary_map[$row->Status] = intval($row->count);
    }

    // Query Brand breakdown for Monitor
    $brand_summary = $wpdb->get_results("
        SELECT Brand, COUNT(*) as count
        FROM $table_device_wn
        WHERE Category = 'Monitor' AND Brand IS NOT NULL AND Brand != '' AND Brand != '-'
        GROUP BY Brand
        ORDER BY count DESC
    ");

    $current_brand = isset($_GET['filter_brand']) ? trim($_GET['filter_brand']) : '';
    $in_use_count = $summary_map['In Use'] ?? 0;
    $available_count = $summary_map['Available'] ?? 0;
    $active_total = $in_use_count + $available_count;
    $in_use_rate = $active_total > 0 ? round(($in_use_count / $active_total) * 100, 1) : 0;
    $available_rate = $active_total > 0 ? round(100 - $in_use_rate, 1) : 0;

    ob_start();
?>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <div class="next-dashboard">
        <div class="next-grid monitor-status-grid mt-4">
            <?php foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_monitor > 0 ? round(($count / $total_monitor) * 100, 0) : 0;
                $target_url = $status_urls[$status] ?? home_url('/monitor/');
            ?>
                <div class="next-card slide-up clickable-card" onclick="if(window.triggerChartFilter){window.triggerChartFilter('<?= esc_url($target_url) ?>');}else{window.location.href='<?= esc_url($target_url) ?>';}" style="cursor: pointer;" title="Filter by <?= esc_attr($status) ?>">
                    <div class="next-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="next-status-dot" style="background: <?= $config['color'] ?>;"></span>
                            <span class="next-card-title"><?= esc_html($status) ?></span>
                        </div>
                        <div class="next-icon-wrapper-sm" style="background: <?= $config['color'] ?>15; color: <?= $config['color'] ?>;">
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

        <!-- ===== SECTION 2: Modern Overview Bar ===== -->
        <div class="mt-4">
            <div class="next-card slide-up monitor-overview-bar" style="animation-delay: 0.2s;">
                <!-- Left: Identity & Total Units -->
                <div class="monitor-brand-group">
                    <div class="monitor-icon-box">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="next-section-title mb-0">All Monitors</h3>
                            <span class="monitor-total-badge">
                                <span class="count-up" data-count="<?= intval($total_monitor) ?>">0</span> Total
                            </span>
                        </div>
                        <span class="monitor-meta-desc">Monitor inventory & brand breakdown</span>
                    </div>
                </div>

                <!-- Center: Interactive Brand Pills -->
                <div class="monitor-pills-wrap">
                    <?php foreach ($brand_summary as $br):
                        $b_name = $br->Brand;
                        $b_cnt = intval($br->count);
                        $is_active = ($current_brand === $b_name);
                        $b_url = home_url('/monitor/?filter_brand=' . urlencode($b_name));
                    ?>
                        <a href="<?= esc_url($b_url) ?>" 
                           class="monitor-type-pill <?= $is_active ? 'is-active' : '' ?>"
                           onclick="if(window.triggerChartFilter){window.triggerChartFilter('<?= esc_url($b_url) ?>'); return false;}else{window.location.href='<?= esc_url($b_url) ?>'; return false;}"
                           title="Filter by <?= esc_attr($b_name) ?>">
                            <i class="fa-solid fa-tag monitor-pill-icon"></i>
                            <span class="monitor-pill-name"><?= esc_html($b_name) ?></span>
                            <span class="monitor-pill-count count-up" data-count="<?= $b_cnt ?>">0</span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!empty($current_brand)): ?>
                        <a href="<?= esc_url(home_url('/monitor/')) ?>" 
                           class="monitor-type-pill monitor-pill-reset"
                           onclick="if(window.triggerChartFilter){window.triggerChartFilter('<?= esc_url(home_url('/monitor/')) ?>'); return false;}else{window.location.href='<?= esc_url(home_url('/monitor/')) ?>'; return false;}"
                           title="Clear brand filter">
                            <i class="fa-solid fa-xmark"></i>
                            <span>Clear</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Right: Utilization Rate -->
                <div class="monitor-utilization-wrap">
                    <div class="monitor-util-header">
                        <span class="monitor-util-title">Allocation Status</span>
                        <span class="monitor-util-pct"><?= $in_use_rate ?>% In Use</span>
                    </div>
                    <div class="monitor-util-track" title="In Use: <?= $in_use_count ?> (<?= $in_use_rate ?>%) · Available: <?= $available_count ?> (<?= $available_rate ?>%)">
                        <div class="monitor-util-fill in-use" style="width: <?= $in_use_rate ?>%;"></div>
                        <div class="monitor-util-fill available" style="width: <?= $available_rate ?>%;"></div>
                    </div>
                    <div class="monitor-util-legend">
                        <span class="monitor-legend-item"><span class="monitor-dot in-use"></span> In Use: <strong><?= $in_use_count ?></strong></span>
                        <span class="monitor-legend-item"><span class="monitor-dot available"></span> Available: <strong><?= $available_count ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modern Overview Bar Styling */
        .monitor-overview-bar {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            padding: 1.15rem 1.5rem !important;
            box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.03), 0 2px 6px -1px rgba(0, 0, 0, 0.02) !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .monitor-brand-group {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 220px;
        }

        .monitor-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(253, 184, 64, 0.15) 0%, rgba(245, 158, 11, 0.08) 100%);
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            border: 1px solid rgba(253, 184, 64, 0.25);
            box-shadow: 0 2px 5px rgba(217, 119, 6, 0.08);
            flex-shrink: 0;
        }

        .monitor-total-badge {
            font-size: 0.74rem;
            font-weight: 700;
            color: #b45309;
            background: #fefce8;
            border: 1px solid #fde68a;
            padding: 3px 9px;
            border-radius: 9999px;
            letter-spacing: 0.01em;
            display: inline-flex;
            align-items: center;
        }

        .monitor-meta-desc {
            font-size: 0.8rem;
            color: #64748b;
            display: block;
            margin-top: 2px;
        }

        /* Center Pills */
        .monitor-pills-wrap {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
            justify-content: center;
            flex: 1;
        }

        .monitor-type-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 6px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #334155;
            text-decoration: none !important;
            font-size: 0.82rem;
            font-weight: 500;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .monitor-type-pill:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #d97706;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .monitor-type-pill.is-active {
            background: #fefce8;
            border-color: #fcd34d;
            color: #b45309;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
            font-weight: 600;
        }

        .monitor-pill-icon {
            font-size: 0.85rem;
            color: #64748b;
            transition: color 0.2s ease;
        }

        .monitor-type-pill:hover .monitor-pill-icon,
        .monitor-type-pill.is-active .monitor-pill-icon {
            color: #d97706;
        }

        .monitor-pill-count {
            background: #e2e8f0;
            color: #0f172a;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 6px;
            min-width: 22px;
            text-align: center;
        }

        .monitor-type-pill:hover .monitor-pill-count {
            background: #fef3c7;
            color: #92400e;
        }

        .monitor-type-pill.is-active .monitor-pill-count {
            background: #f59e0b;
            color: #ffffff;
        }

        .monitor-pill-reset {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
            font-size: 0.78rem;
        }

        .monitor-pill-reset:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        /* Right Side: Utilization Bar */
        .monitor-utilization-wrap {
            min-width: 210px;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .monitor-util-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
        }

        .monitor-util-title {
            font-weight: 500;
            color: #64748b;
        }

        .monitor-util-pct {
            font-weight: 700;
            color: #0f172a;
        }

        .monitor-util-track {
            display: flex;
            height: 7px;
            background: #f1f5f9;
            border-radius: 9999px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .monitor-util-fill.in-use {
            background: #f05353;
            transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .monitor-util-fill.available {
            background: #6abf57;
            transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .monitor-util-legend {
            display: flex;
            justify-content: space-between;
            font-size: 0.74rem;
            color: #64748b;
        }

        .monitor-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .monitor-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }

        .monitor-dot.in-use { background: #f05353; }
        .monitor-dot.available { background: #6abf57; }

        @media (max-width: 992px) {
            .monitor-overview-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .monitor-pills-wrap {
                justify-content: flex-start;
            }
            .monitor-utilization-wrap {
                width: 100%;
            }
        }

        /* 5-column layout for Status Cards */
        .monitor-status-grid {
            grid-template-columns: repeat(5, 1fr) !important;
            gap: 1.25rem !important;
        }

        @media (max-width: 1200px) {
            .monitor-status-grid {
                grid-template-columns: repeat(5, 1fr) !important;
                gap: 0.75rem !important;
            }
        }

        @media (max-width: 1024px) {
            .monitor-status-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 0.75rem !important;
            }
        }

        @media (max-width: 640px) {
            .monitor-status-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.5rem !important;
            }
        }

        @media (max-width: 400px) {
            .monitor-status-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    
    <script>
        if (typeof initNextDashboardShared !== 'function') {
            window.initNextDashboardShared = function() {
                document.querySelectorAll('.count-up:not(.initialized)').forEach(el => {
                    el.classList.add('initialized');
                    const target = parseInt(el.getAttribute('data-count'), 10) || 0;
                    if (target > 0) {
                        const duration = 1800;
                        const easeOutQuart = t => 1 - (--t) * t * t * t;
                        let startTime = null;
                        const step = (timestamp) => {
                            if (!startTime) startTime = timestamp;
                            const progress = Math.min((timestamp - startTime) / duration, 1);
                            el.innerText = Math.floor(easeOutQuart(progress) * target).toLocaleString();
                            if (progress < 1) window.requestAnimationFrame(step);
                            else el.innerText = target.toLocaleString();
                        };
                        window.requestAnimationFrame(step);
                    } else el.innerText = "0";
                });

                setTimeout(() => {
                    document.querySelectorAll('.donut-segment:not(.initialized)').forEach(segment => {
                        segment.classList.add('initialized');
                        const targetDash = segment.getAttribute('data-dash');
                        if (targetDash) segment.setAttribute('stroke-dasharray', targetDash);
                    });
                }, 150);

                setTimeout(() => {
                    document.querySelectorAll('.next-progress-fill:not(.initialized)').forEach(bar => {
                        bar.classList.add('initialized');
                        const targetWidth = bar.getAttribute('data-width');
                        if (targetWidth) bar.style.width = targetWidth;
                    });
                }, 250);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", window.initNextDashboardShared);
        } else {
            window.initNextDashboardShared();
        }
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('device_dashboard_monitor', 'device_dashboard_monitor');
