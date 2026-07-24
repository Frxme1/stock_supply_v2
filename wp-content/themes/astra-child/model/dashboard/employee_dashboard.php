<?php
function employee_dashboard()
{
    global $wpdb;
    $table_owner = 'ViewOwnersWithNames';

    // ดึงจำนวนพนักงานทั้งหมด
    $total_employees = $wpdb->get_var("
    SELECT COUNT(*) FROM $table_owner
    WHERE Position IN ('Full-time', 'Intern')
");

    // สรุปจำนวนตามตำแหน่ง
    $position_summary = $wpdb->get_results("
    SELECT Position, COUNT(*) as count
    FROM $table_owner
    WHERE Position IN ('Full-time', 'Intern')
    GROUP BY Position
");


    // ตั้งค่ารูปแบบแต่ละตำแหน่ง
    $position_config = [
        'Full-time' => [
            'icon' => '<i class="fa-solid fa-user-tie"></i>',
            'color' => '#2196F3'
        ],
        'Intern' => [
            'icon' => '<i class="fa-solid fa-user-graduate"></i>',
            'color' => '#4CAF50'
        ],
    ];

    // สร้าง Map สำหรับจำนวนพนักงานแต่ละตำแหน่ง
    $summary_map = [];
    foreach ($position_summary as $row) {
        $summary_map[$row->Position] = intval($row->count);
    }

    ob_start();
?>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <div class="next-dashboard">
        <div class="next-grid mt-4" style="grid-template-columns: repeat(2, 1fr);">
            <?php foreach ($position_config as $position => $config):
                $count = $summary_map[$position] ?? 0;
                $percent = $total_employees > 0 ? round(($count / $total_employees) * 100, 0) : 0;
            ?>
                <div class="next-card slide-up">
                    <div class="next-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="next-status-dot" style="background: <?= $config['color'] ?>;"></span>
                            <span class="next-card-title"><?= esc_html($position) ?></span>
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
                <h3 class="next-section-title">All Employees</h3>
                <div class="next-donut-container mt-4" style="justify-content: center; gap: 3rem;">
                    <?php
                    $emp_sectors = [
                        ['label' => 'Active Employees', 'pct' => 100, 'color' => '#2196F3'],
                    ];
                    echo render_sectors_donut([
                        'symbol' => 'EMP',
                        'caption' => $total_employees . ' persons',
                        'sectors' => $emp_sectors,
                    ]);
                    ?>
                    <div class="text-center">
                        <div class="next-number" style="font-size: 3.5rem; color: #2196F3;"><span class="count-up" data-count="<?= $total_employees ?>">0</span></div>
                        <div class="next-trend-text mt-2">Total Persons</div>
                    </div>
                </div>
            </div>
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
        .next-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
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
        .next-number { font-weight: 700; line-height: 1.2; letter-spacing: -0.025em; }
        .next-number-md { font-size: 1.5rem; font-weight: 700; line-height: 1.2; display: block; }
        .next-status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .next-progress-wrap { display: flex; align-items: center; gap: 0.75rem; }
        .next-progress-bar { flex: 1; height: 6px; background: #f3f4f6; border-radius: 9999px; overflow: hidden; }
        .next-progress-fill { height: 100%; border-radius: 9999px; transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1); }
        .next-progress-text { font-size: 0.875rem; color: #6b7280; font-weight: 500; min-width: 32px; text-align: right; }
        .next-donut-container { display: flex; align-items: center; }
        .next-donut-wrap { position: relative; width: 140px; height: 140px; }
        .next-donut { width: 100%; height: 100%; transform: scale(1); transition: transform 0.3s ease; }
        .next-donut:hover { transform: scale(1.03); }
        .donut-segment { transition: stroke-dasharray 1.5s cubic-bezier(0.16, 1, 0.3, 1); }
        .next-donut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; display: flex; flex-direction: column; }
        .next-donut-value { font-size: 1.5rem; font-weight: 700; color: #111827; line-height: 1; }
        .next-donut-label { font-size: 0.7rem; color: #6b7280; margin-top: 0.35rem; text-transform: uppercase; font-weight: 500; }
        .next-trend-text { font-size: 0.875rem; color: #6b7280; }
        .mt-4 { margin-top: 1.5rem; }
        .slide-up { opacity: 0; transform: translateY(15px); animation: nextSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes nextSlideUp { to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .next-grid { grid-template-columns: 1fr !important; } }
        @media (max-width: 640px) { .next-donut-container { flex-direction: column; } }
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
add_shortcode('employee_dashboard_summary', 'employee_dashboard');
