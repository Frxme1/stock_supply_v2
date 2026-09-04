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
            <div class="next-card slide-up dept-chart-card" style="animation-delay: 0.7s;">
                <div class="dept-chart-header">
                    <div class="dept-title-group">
                        <div class="dept-title-icon">
                            <i class="fa-solid fa-chart-column"></i>
                        </div>
                        <div>
                            <h3 class="next-section-title">Employees by Department</h3>
                            <span class="dept-subtitle">Headcount distribution across departments</span>
                        </div>
                    </div>
                    <div class="dept-meta-badges">
                        <span class="dept-total-pill">
                            <i class="fa-solid fa-users text-primary me-1"></i> Total <strong><?= intval(array_sum($dept_counts)) ?></strong> Employees
                        </span>
                    </div>
                </div>
                <div id="chart-department" class="mt-2"></div>
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
            margin-top: 0.75rem;
        }

        .next-progress-bar {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        .next-progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
        }

        .next-progress-text {
            font-size: 0.76rem;
            font-weight: 700;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 2px 7px;
            min-width: 40px;
            text-align: center;
            font-family: ui-monospace, SFMono-Regular, monospace;
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

        /* ===== Department Chart Card Styling ===== */
        .dept-chart-card {
            padding: 1.5rem !important;
            border-radius: 16px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.04), 0 2px 6px -1px rgba(0, 0, 0, 0.02) !important;
            background: #ffffff !important;
        }

        .dept-chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0.5rem;
        }

        .dept-title-group {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .dept-title-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(14, 165, 233, 0.08) 100%);
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            border: 1px solid rgba(59, 130, 246, 0.18);
            box-shadow: 0 2px 5px rgba(37, 99, 235, 0.08);
            flex-shrink: 0;
        }

        .dept-rank-badge {
            font-size: 0.74rem;
            font-weight: 700;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 3px 9px;
            border-radius: 9999px;
            letter-spacing: 0.01em;
            display: inline-flex;
            align-items: center;
        }

        .dept-subtitle {
            font-size: 0.82rem;
            color: #64748b;
            display: block;
            margin-top: 3px;
        }

        .dept-meta-badges {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .dept-total-pill {
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 11px;
            display: inline-flex;
            align-items: center;
        }

        .dept-hint-badge {
            font-size: 0.75rem;
            font-weight: 500;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 5px 11px;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .dept-hint-badge:hover {
            color: #2563eb;
            border-color: #93c5fd;
            background: #eff6ff;
        }

        /* ApexCharts Overrides for Dept Chart */
        #chart-department {
            position: relative;
            margin-top: 0.5rem;
        }

        #chart-department .apexcharts-bar-area {
            cursor: pointer !important;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), filter 0.2s ease !important;
        }

        #chart-department .apexcharts-bar-area:hover {
            filter: brightness(1.08) drop-shadow(0 4px 10px rgba(0, 0, 0, 0.15)) !important;
        }

        #chart-department .apexcharts-yaxis-label {
            font-weight: 600 !important;
            fill: #1e293b !important;
            cursor: pointer !important;
            transition: fill 0.15s ease;
        }

        #chart-department .apexcharts-yaxis-label:hover {
            fill: #2563eb !important;
        }

        #chart-department .apexcharts-datalabel {
            pointer-events: none;
        }

        #chart-department .apexcharts-tooltip {
            border-radius: 10px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #e2e8f0 !important;
            font-family: inherit !important;
            overflow: hidden !important;
        }

        @media (max-width: 768px) {
            .dept-chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            .dept-meta-badges {
                width: 100%;
                justify-content: flex-start;
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
            var deptLabels = <?= $js_dept_labels ?>;
            var deptCounts = <?= $js_dept_counts ?>;
            var totalEmployees = <?= intval($total_employees) ?>;
            var maxCount = deptCounts.length > 0 ? Math.max.apply(null, deptCounts) : 10;
            // Pad max count so outside data labels have comfortable breathing room
            var xMax = Math.ceil(maxCount * 1.12);

            // Employees by Department Chart
            var optionsDept = {
                series: [{ name: 'Employees', data: deptCounts }],
                chart: {
                    type: 'bar',
                    height: 330,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 700,
                        animateGradually: {
                            enabled: true,
                            delay: 120
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    },
                    events: {
                        dataPointSelection: function (event, chartContext, config) {
                            if (config.dataPointIndex >= 0 && config.dataPointIndex < deptLabels.length) {
                                var selected = deptLabels[config.dataPointIndex];
                                if (selected) {
                                    window.location.href = '<?= esc_url(home_url('/owner/?filter_department=')) ?>' + encodeURIComponent(selected);
                                }
                            }
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '62%',
                        borderRadius: 6,
                        borderRadiusApplication: 'end',
                        distributed: true,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                colors: [
                    '#3B82F6', // Blue
                    '#0EA5E9', // Sky Blue
                    '#10B981', // Emerald
                    '#F59E0B', // Amber
                    '#8B5CF6', // Violet
                    '#EC4899', // Pink
                    '#06B6D4', // Cyan
                    '#6366F1'  // Indigo
                ],
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    offsetX: 10,
                    style: {
                        fontSize: '12px',
                        fontWeight: 700,
                        colors: ['#334155']
                    },
                    formatter: function (val) {
                        return val;
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                    xaxis: {
                        lines: { show: true }
                    },
                    yaxis: {
                        lines: { show: false }
                    },
                    padding: {
                        top: -10,
                        right: 40,
                        bottom: 0,
                        left: 10
                    }
                },
                xaxis: {
                    categories: deptLabels,
                    max: xMax,
                    labels: {
                        style: {
                            colors: '#94a3b8',
                            fontSize: '11px',
                            fontWeight: 500
                        },
                        formatter: function (val) {
                            return Math.floor(val);
                        }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#1e293b',
                            fontSize: '13px',
                            fontWeight: 600
                        }
                    }
                },
                tooltip: {
                    theme: 'light',
                    custom: function (opts) {
                        var series = opts.series;
                        var seriesIndex = opts.seriesIndex;
                        var dataPointIndex = opts.dataPointIndex;
                        var w = opts.w;
                        var dept = deptLabels[dataPointIndex] || '';
                        var val = series[seriesIndex][dataPointIndex];
                        var pct = totalEmployees > 0 ? ((val / totalEmployees) * 100).toFixed(1) : 0;
                        var color = w.globals.colors[dataPointIndex % w.globals.colors.length];

                        return '<div style="padding: 10px 14px; min-width: 160px;">' +
                            '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">' +
                                '<span style="width: 10px; height: 10px; border-radius: 3px; background: ' + color + '; display: inline-block;"></span>' +
                                '<strong style="color: #0f172a; font-size: 13px;">' + dept + '</strong>' +
                            '</div>' +
                            '<div style="font-size: 12px; color: #475569; display: flex; justify-content: space-between; margin-bottom: 3px;">' +
                                '<span>Employees:</span>' +
                                '<strong style="color: #0f172a;">' + val + '</strong>' +
                            '</div>' +
                            '<div style="font-size: 12px; color: #475569; display: flex; justify-content: space-between; margin-bottom: 6px;">' +
                                '<span>Share:</span>' +
                                '<strong style="color: #2563eb;">' + pct + '%</strong>' +
                            '</div>' +
                            '<div style="border-top: 1px dashed #e2e8f0; padding-top: 5px; font-size: 11px; color: #3b82f6; text-align: center;">' +
                                '<i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-right: 4px;"></i>Click to view employees' +
                            '</div>' +
                        '</div>';
                    }
                },
                legend: { show: false }
            };

            var chartEl = document.querySelector("#chart-department");
            if (chartEl) {
                chartEl.innerHTML = '';
                var chartDept = new ApexCharts(chartEl, optionsDept);
                chartDept.render();

                // Make Y-Axis department labels clickable too
                setTimeout(function() {
                    var yLabels = document.querySelectorAll('#chart-department .apexcharts-yaxis-label');
                    yLabels.forEach(function(lbl, idx) {
                        lbl.style.cursor = 'pointer';
                        lbl.onclick = function() {
                            var deptName = deptLabels[idx];
                            if (deptName) {
                                window.location.href = '<?= esc_url(home_url('/owner/?filter_department=')) ?>' + encodeURIComponent(deptName);
                            }
                        };
                    });
                }, 600);
            }
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
