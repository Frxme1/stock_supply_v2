<?php
function device_dashboard_maintenance()
{
    global $wpdb;
    $table_device_wn = 'DevicesWithNames';

    $total_maintenance = $wpdb->get_var("
        SELECT COUNT(*) FROM $table_device_wn
        WHERE Status = 'Maintenance'
    ");

    $total_devices = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn");

    $maintenance_by_category = $wpdb->get_results("
        SELECT Category, COUNT(*) as count
        FROM $table_device_wn
        WHERE Status = 'Maintenance'
        GROUP BY Category
    ");

    $summary_map = [];
    foreach ($maintenance_by_category as $row) {
        $summary_map[$row->Category] = intval($row->count);
    }

    $percent_maintenance = $total_devices > 0 ? round(($total_maintenance / $total_devices) * 100, 0) : 0;

    ob_start();
?>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <div class="next-dashboard">
        <!-- Summary Box for Total Maintenance -->
        <div class="next-grid-2 mt-4" style="grid-template-columns: 1fr;">
            <div class="next-card slide-up" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 20px; box-shadow: 0 12px 30px -5px rgba(15, 23, 42, 0.25), 0 4px 14px rgba(245, 158, 11, 0.12); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -30px; right: -30px; width: 140px; height: 140px; background: rgba(245, 158, 11, 0.12); border-radius: 50%; filter: blur(30px); pointer-events: none;"></div>
                <div class="text-center py-4 position-relative" style="z-index: 2;">
                    <div class="d-inline-flex align-items-center gap-2 mb-2" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; padding: 5px 16px; border-radius: 9999px; font-size: 0.85rem; font-weight: 700;">
                        <i class="fa-solid fa-screwdriver-wrench"></i> Total Devices Under Maintenance
                    </div>
                    <div style="font-size: 3.75rem; font-weight: 800; line-height: 1; margin: 12px 0; color: #ffffff; letter-spacing: -0.03em;"><span class="count-up" data-count="<?= $total_maintenance ?>">0</span></div>
                    <span style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); padding: 6px 18px; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; color: #cbd5e1;"><?= $percent_maintenance ?>% of total device stock</span>
                </div>
            </div>
        </div>

        <!-- Breakdown by Category -->
        <h3 class="next-section-title mt-5 mb-4">Breakdown by Category</h3>
        <div class="next-grid" style="grid-template-columns: repeat(3, 1fr);">
            <?php foreach (['Monitor', 'Laptop', 'Accessories'] as $category):
                $count = $summary_map[$category] ?? 0;
                $percent = $total_maintenance > 0 ? round(($count / $total_maintenance) * 100, 0) : 0;

                $color = '#FDB840';
                $icon = '<i class="fa-solid fa-desktop"></i>';
                if ($category === 'Laptop') {
                    $color = '#15A5DA';
                    $icon = '<i class="fa-solid fa-laptop"></i>';
                } elseif ($category === 'Accessories') {
                    $color = '#6ABF57';
                    $icon = '<i class="fa-solid fa-plug"></i>';
                }
            ?>
                <div class="next-card slide-up">
                    <div class="next-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="next-status-dot" style="background: <?= $color ?>;"></span>
                            <span class="next-card-title"><?= esc_html($category) ?></span>
                        </div>
                        <div class="next-icon-wrapper-sm" style="background: <?= $color ?>15; color: <?= $color ?>;">
                            <?= $icon ?>
                        </div>
                    </div>
                    <div class="next-card-body mt-3">
                        <span class="next-number-md count-up" data-count="<?= $count ?>">0</span>
                        <div class="next-progress-wrap mt-2">
                            <div class="next-progress-bar">
                                <div class="next-progress-fill" style="width: 0%; background: <?= $color ?>;" data-width="<?= $percent ?>%"></div>
                            </div>
                            <span class="next-progress-text"><?= $percent ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        /* maintenance_dashboard — shared styles from dashboard_cards.css */
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
add_shortcode('device_dashboard_maintenance', 'device_dashboard_maintenance');
