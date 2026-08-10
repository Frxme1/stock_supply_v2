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
    ];

    // Map count per status
    $summary_map = [];
    foreach ($status_summary as $row) {
        $summary_map[$row->Status] = intval($row->count);
    }

    ob_start();
?>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <div class="next-dashboard">
        <div class="next-grid mt-4">
            <?php foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
            ?>
                <div class="next-card slide-up">
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

        <div class="next-grid-2 mt-4" style="grid-template-columns: 1fr;">
            <div class="next-card slide-up" style="animation-delay: 0.2s;">
                <h3 class="next-section-title">All Monitor</h3>
                <div class="next-donut-container mt-4" style="justify-content: center; gap: 3rem;">
                    <?php
                    $percent_monitor = $total_devices > 0 ? round(($total_monitor / $total_devices) * 100, 1) : 0;
                    $monitor_sectors = [
                        ['label' => 'Monitor', 'pct' => $percent_monitor, 'color' => '#FDB840'],
                        ['label' => 'Other Devices', 'pct' => max(0, round(100 - $percent_monitor, 1)), 'color' => '#e5e7eb'],
                    ];
                    echo render_sectors_donut([
                        'symbol' => 'MONITOR',
                        'caption' => $total_monitor . ' units',
                        'sectors' => $monitor_sectors,
                    ]);
                    ?>
                    <div class="text-center">
                        <div class="next-number" style="font-size: 3.5rem; color: #FDB840;"><span class="count-up" data-count="<?= $total_monitor ?>">0</span></div>
                        <div class="next-trend-text mt-2">Units Registered</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* monitor_dashboard — shared styles from dashboard_cards.css */
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
