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
            <div class="next-card slide-up" style="background: linear-gradient(135deg, #FDB840 0%, #f59e0b 100%); color: white; border: none; box-shadow: 0 10px 20px -5px rgba(253, 184, 64, 0.4);">
                <div class="text-center py-4">
                    <p style="color: rgba(255,255,255,0.9); font-weight: 500; font-size: 1.1rem; margin-bottom: 0.5rem;">Total Devices Under Maintenance</p>
                    <div style="font-size: 4rem; font-weight: 800; line-height: 1; margin-bottom: 1rem;"><span class="count-up" data-count="<?= $total_maintenance ?>">0</span></div>
                    <span style="background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;"><?= $percent_maintenance ?>% of all devices</span>
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
        /* Next.js Inspired UI (Shared) */
        .next-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #111827;
            background: transparent !important;
            padding-bottom: 2rem;
        }
        .next-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }
        .next-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; }
        .next-card {
            background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03); transition: all 0.2s ease-in-out;
        }
        .next-card:hover { border-color: #d1d5db; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04); transform: translateY(-2px); }
        .next-card-header { display: flex; justify-content: space-between; align-items: center; }
        .next-card-title { font-size: 0.875rem; font-weight: 500; color: #4b5563; margin: 0; }
        .next-section-title { font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; }
        .next-icon-wrapper-sm {
            width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem;
        }
        .next-number-md { font-size: 1.5rem; font-weight: 700; line-height: 1.2; display: block; }
        .next-status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .next-progress-wrap { display: flex; align-items: center; gap: 0.75rem; }
        .next-progress-bar { flex: 1; height: 6px; background: #f3f4f6; border-radius: 9999px; overflow: hidden; }
        .next-progress-fill { height: 100%; border-radius: 9999px; transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1); }
        .next-progress-text { font-size: 0.875rem; color: #6b7280; font-weight: 500; min-width: 32px; text-align: right; }
        .mt-4 { margin-top: 1.5rem; }
        .mt-5 { margin-top: 2rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; text-align: center; }
        .slide-up { opacity: 0; transform: translateY(15px); animation: nextSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes nextSlideUp { to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .next-grid { grid-template-columns: repeat(2, 1fr) !important; } }
        @media (max-width: 640px) { .next-grid { grid-template-columns: 1fr !important; } }
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
