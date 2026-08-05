<?php
function employee_dashboard()
{
    global $wpdb;
    $table_owner = 'ViewOwnersWithNames';

    $total_employees = $wpdb->get_var("SELECT COUNT(*) FROM $table_owner");
    $total_fulltime = $wpdb->get_var("SELECT COUNT(*) FROM $table_owner WHERE Position = 'Full-time'");
    $total_intern = $wpdb->get_var("SELECT COUNT(*) FROM $table_owner WHERE Position = 'Intern'");

    $status_summary = $wpdb->get_results("
        SELECT Status, COUNT(*) as count
        FROM $table_owner
        WHERE Status IS NOT NULL AND Status != '' AND Status IN ('Active', 'Resigned')
        GROUP BY Status
    ");

    $status_config = [
        'Active' => ['color' => '#6ABF57', 'bg' => 'rgba(106, 191, 87, 0.08)', 'icon' => '<i class="fa-solid fa-user-check"></i>'],
        'Resigned' => ['color' => '#F05353', 'bg' => 'rgba(240, 83, 83, 0.08)', 'icon' => '<i class="fa-solid fa-user-xmark"></i>'],
    ];

    // Build summary map
    $summary_map = [];
    foreach ($status_summary as $row) {
        $summary_map[$row->Status] = intval($row->count);
    }

    $dept_summary = $wpdb->get_results("
        SELECT Department, COUNT(*) as count 
        FROM $table_owner 
        WHERE Department IS NOT NULL AND Department != '' AND Department != '-'
        GROUP BY Department 
        ORDER BY count DESC 
        LIMIT 7
    ");
    $dept_labels = [];
    $dept_counts = [];
    foreach ($dept_summary as $row) {
        $dept_labels[] = $row->Department;
        $dept_counts[] = intval($row->count);
    }
    $js_dept_labels = json_encode($dept_labels);
    $js_dept_counts = json_encode($dept_counts);

    $category_config = [
        ['label' => 'All Employees', 'count' => $total_employees, 'color' => '#1976D2', 'icon' => '<i class="fa-solid fa-users"></i>', 'url' => home_url('/owner/')],
        ['label' => 'Full-time', 'count' => $total_fulltime, 'color' => '#15A5DA', 'icon' => '<i class="fa-solid fa-user-tie"></i>', 'url' => home_url('/owner/?filter_position=Full-time')],
        ['label' => 'Intern', 'count' => $total_intern, 'color' => '#6ABF57', 'icon' => '<i class="fa-solid fa-user-graduate"></i>', 'url' => home_url('/owner/?filter_position=Intern')],
    ];

    $status_urls = [
        'Active' => home_url('/owner/?filter_status=Active'),
        'Resigned' => home_url('/owner/?filter_status=Resigned'),
    ];

    ob_start();
    ?>

    <!-- FontAwesome & ApexCharts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="next-dashboard">

        <!-- ===== SECTION 1 & 2: Summary & Status Cards ===== -->
        <div class="next-grid" style="grid-template-columns: repeat(5, 1fr);">
            <?php
            $delay = 0;
            foreach ($category_config as $cat):
                $percent = $total_employees > 0 ? round(($cat['count'] / $total_employees) * 100, 1) : 0;
                $is_total = ($cat['label'] === 'All Employees');
                ?>
                <div class="next-card slide-up clickable-card" onclick="window.location.href='<?= esc_url($cat['url']) ?>'"
                    style="animation-delay: <?= $delay ?>s; cursor: pointer;" title="View <?= esc_attr($cat['label']) ?>">
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

            <?php
            foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_employees > 0 ? round(($count / $total_employees) * 100, 0) : 0;
                ?>
                <div class="next-card slide-up clickable-card"
                    onclick="window.location.href='<?= esc_url($status_urls[$status] ?? home_url('/owner/')) ?>'"
                    style="animation-delay: <?= $delay ?>s; cursor: pointer;" title="View <?= esc_attr($status) ?> employees">
                    <?php $delay += 0.05; ?>
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
                                <div class="next-progress-fill" style="width: 0%; background: <?= $config['color'] ?>;"
                                    data-width="<?= $percent ?>%"></div>
                            </div>
                            <span class="next-progress-text"><?= $percent ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 3: Employees by Department Chart ===== -->
        <div class="mt-4 bottom-chart-wrapper">
            <div class="next-card slide-up" style="animation-delay: 0.7s;">
                <h3 class="next-section-title">Employees by Department</h3>
                <div id="chart-department" class="mt-3"></div>
            </div>
        </div>

    </div>

    <style>
        .next-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #111827;
            background: transparent !important;
            padding-bottom: 1rem;
            padding-top: 0.5rem;
        }

        .next-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.25rem;
        }

        .next-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
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
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
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
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.025em;
            color: #111827;
        }

        .next-number-md {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
            display: block;
        }

        .next-card-body {
            margin-top: 1rem;
        }

        .next-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .next-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.15rem 0.6rem;
            border-radius: 9999px;
            letter-spacing: -0.01em;
        }

        .next-trend-text {
            font-size: 0.825rem;
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
            font-size: 0.825rem;
            color: #6b7280;
            font-weight: 500;
            min-width: 32px;
            text-align: right;
        }

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

        .clickable-card {
            cursor: pointer !important;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s ease !important;
        }

        .clickable-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08), 0 4px 10px rgba(0, 0, 0, 0.04) !important;
            border-color: #cbd5e1 !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .next-grid, .bottom-chart-wrapper {
                margin-left: -45px;
            }
            .next-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 0.75rem !important;
            }
        }

        @media (max-width: 640px) {
            .next-grid, .bottom-chart-wrapper {
                margin-left: -45px;
            }
            .next-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.5rem !important;
            }
        }

        @media (max-width: 400px) {
            .next-grid, .bottom-chart-wrapper {
                margin-left: -45px;
            }
            .next-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script>
        function initEmpDashboard() {
            // Count up animation
            const countElements = document.querySelectorAll('.count-up');
            countElements.forEach(el => {
                const target = parseInt(el.getAttribute('data-count'), 10);
                const duration = 1800;

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

            // ApexCharts Initialization
            if (typeof ApexCharts !== 'undefined') {
                initEmpApexCharts();
            }
        }

        function initEmpApexCharts() {
            // Employees by Department Chart
            var optionsDept = {
                series: [{ name: 'Employees', data: <?= $js_dept_counts ?> }],
                chart: {
                    type: 'bar',
                    height: 280,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    events: {
                        dataPointSelection: function (event, chartContext, config) {
                            var categories = <?= $js_dept_labels ?>;
                            var selected = categories[config.dataPointIndex];
                            if (selected) {
                                window.location.href = '<?= esc_url(home_url('/owner/?filter_department=')) ?>' + encodeURIComponent(selected);
                            }
                        }
                    }
                },
                plotOptions: {
                    bar: { borderRadius: 5, horizontal: true, distributed: true, barHeight: '55%' }
                },
                colors: ['#6ABF57', '#15A5DA', '#FDB840', '#F05353', '#8B5CF6', '#EC4899', '#14B8A6'],
                dataLabels: { enabled: true },
                xaxis: { categories: <?= $js_dept_labels ?> },
                legend: { show: false }
            };
            var chartDept = new ApexCharts(document.querySelector("#chart-department"), optionsDept);
            chartDept.render();
        }

        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", initEmpDashboard);
        } else {
            initEmpDashboard();
        }
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('employee_dashboard_summary', 'employee_dashboard');
