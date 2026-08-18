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
    // Prepare Data for ApexCharts
    $js_status_labels = json_encode(array_keys($summary_map));
    $js_status_counts = json_encode(array_values($summary_map));

    $js_cat_labels = json_encode(['Monitor', 'Laptop', 'Accessories']);
    $js_cat_counts = json_encode([(int) $total_monitor, (int) $total_laptop, (int) $total_accessories]);

    $dept_summary = $wpdb->get_results("
        SELECT Department, COUNT(*) as count 
        FROM $table_device_wn 
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

    // Category config (Links to respective category pages for Monitor/Laptop/Accessories, and clears filters on current page for All Devices)
    $category_config = [
        ['label' => 'All Devices', 'count' => $total_devices, 'color' => '#1976D2', 'icon' => '<i class="fa-solid fa-chart-simple"></i>', 'url' => home_url('/home/')],
        ['label' => 'Monitor', 'count' => $total_monitor, 'color' => '#FDB840', 'icon' => '<i class="fa-solid fa-desktop"></i>', 'url' => home_url('/monitor/')],
        ['label' => 'Laptop', 'count' => $total_laptop, 'color' => '#15A5DA', 'icon' => '<i class="fa-solid fa-laptop"></i>', 'url' => home_url('/laptop/')],
        ['label' => 'Accessories', 'count' => $total_accessories, 'color' => '#6ABF57', 'icon' => '<i class="fa-solid fa-plug"></i>', 'url' => home_url('/accessories/')],
    ];

    $status_urls = [
        'Available' => home_url('/home/?filter_status=Available'),
        'In Use' => home_url('/home/?filter_status=In+Use'),
        'Maintenance' => home_url('/maintenance/'),
        'Retired' => home_url('/home/?filter_status=Retired'),
    ];

    // Mobile-first command deck: common field actions in one tap.
    $quick_menu = [
        [
            'label' => 'Scan QR',
            'description' => 'Find a device fast',
            'icon' => 'fa-qrcode',
            'url' => home_url('/?scan=1'),
            'tone' => 'is-primary',
        ],
        [
            'label' => 'Add Device',
            'description' => 'Register new stock',
            'icon' => 'fa-plus',
            'url' => home_url('/add-device/'),
            'tone' => 'is-indigo',
        ],
        [
            'label' => 'Maintenance',
            'description' => 'Review devices',
            'icon' => 'fa-screwdriver-wrench',
            'url' => home_url('/maintenance/'),
            'tone' => 'is-amber',
            'badge' => (int) ($summary_map['Maintenance'] ?? 0),
        ],
        [
            'label' => 'Employees',
            'description' => 'Manage assignments',
            'icon' => 'fa-users',
            'url' => home_url('/owner/'),
            'tone' => 'is-teal',
        ],
        [
            'label' => 'History',
            'description' => 'Track activity',
            'icon' => 'fa-clock-rotate-left',
            'url' => home_url('/history/'),
            'tone' => 'is-slate',
        ],
        [
            'label' => 'All Devices',
            'description' => 'Browse inventory',
            'icon' => 'fa-boxes-stacked',
            'url' => home_url('/home/'),
            'tone' => 'is-blue',
        ],
    ];

    // Query recently added devices (within 7 days)
    $new_devices_days = 7;
    $new_devices = $wpdb->get_results($wpdb->prepare(
        "SELECT DeviceID, Brand, Model, Category, CreatedAt 
         FROM $table_device_wn 
         WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL %d DAY) 
         ORDER BY CreatedAt DESC 
         LIMIT 10",
        $new_devices_days
    ));
    $new_devices_count = count($new_devices);

    ob_start();
    ?>

    <!-- FontAwesome & ApexCharts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="next-dashboard home-dashboard">

        <!-- ===== COMMAND DECK: IMPORTANT MENU ===== -->
        <section class="home-command-deck" aria-labelledby="home-command-title">
            <div class="home-command-heading">
                <div>
                    <p class="home-command-eyebrow">Stock Supply</p>
                    <h1 id="home-command-title">What do you need to do?</h1>
                    <p class="home-command-subtitle">Quick access for work on the floor.</p>
                </div>
                <span class="home-command-context" aria-hidden="true">
                    <i class="fa-solid fa-cube"></i>
                </span>
            </div>

            <nav class="home-command-grid" aria-label="Important actions">
                <?php foreach ($quick_menu as $item): ?>
                    <a class="home-command-card <?= esc_attr($item['tone']) ?>" href="<?= esc_url($item['url']) ?>"
                        aria-label="<?= esc_attr($item['label'] . ': ' . $item['description']) ?>">
                        <span class="home-command-card__icon" aria-hidden="true">
                            <i class="fa-solid <?= esc_attr($item['icon']) ?>"></i>
                        </span>
                        <span class="home-command-card__copy">
                            <span class="home-command-card__label"><?= esc_html($item['label']) ?></span>
                            <span class="home-command-card__description"><?= esc_html($item['description']) ?></span>
                        </span>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="home-command-card__badge" aria-label="<?= esc_attr($item['badge']) ?> devices">
                                <?= esc_html($item['badge'] > 99 ? '99+' : $item['badge']) ?>
                            </span>
                        <?php endif; ?>
                        <i class="fa-solid fa-arrow-up-right-from-square home-command-card__arrow" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </nav>
        </section>

        <?php if ($new_devices_count > 0): ?>
            <!-- ===== NEW DEVICES NOTIFICATION CARD ===== -->
            <div class="new-devices-alert slide-up" id="new-devices-alert">
                <div class="new-devices-alert-header" onclick="toggleNewDevices()">
                    <div class="new-devices-alert-left">
                        <div class="new-devices-alert-icon">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div>
                            <div class="new-devices-alert-title">
                                Recently Added Devices
                                <span class="new-devices-count-badge"><?= $new_devices_count ?></span>
                            </div>
                            <div class="new-devices-alert-subtitle">
                                <?= $new_devices_count ?> new device<?= $new_devices_count > 1 ? 's' : '' ?> added in the last
                                <?= $new_devices_days ?> days
                            </div>
                        </div>
                    </div>
                    <button class="new-devices-toggle-btn" id="new-devices-toggle-btn" type="button">
                        <i class="fa-solid fa-chevron-down" id="new-devices-chevron"></i>
                    </button>
                </div>
                <div class="new-devices-alert-body" id="new-devices-body">
                    <table class="new-devices-table">
                        <thead>
                            <tr>
                                <th>Device ID</th>
                                <th>Device Info</th>
                                <th>Category</th>
                                <th>Added Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($new_devices as $nd): ?>
                                <tr onclick="window.location.href='<?= home_url('/' . strtolower(esc_attr($nd->Category)) . '/?view=' . urlencode($nd->DeviceID)) ?>'"
                                    style="cursor: pointer;" title="Click to view details of <?= esc_attr($nd->DeviceID) ?>">
                                    <td>
                                        <span class="new-device-badge-sm">NEW</span>
                                        <?= esc_html($nd->DeviceID) ?>
                                    </td>
                                    <td><strong><?= esc_html($nd->Brand) ?></strong> <?= esc_html($nd->Model) ?></td>
                                    <td><?= esc_html($nd->Category) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($nd->CreatedAt)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== SECTION 1: Category Summary Cards ===== -->
        <div class="home-dashboard-section-heading">
            <div>
                <p class="home-command-eyebrow">Inventory</p>
                <h2>Browse by category</h2>
            </div>
            <a href="<?= esc_url(home_url('/home/')) ?>">View all <i class="fa-solid fa-arrow-right"
                    aria-hidden="true"></i></a>
        </div>
        <div class="next-grid">
            <?php
            $delay = 0;
            foreach ($category_config as $cat):
                $percent = $total_devices > 0 ? round(($cat['count'] / $total_devices) * 100, 1) : 0;
                $is_total = ($cat['label'] === 'All Devices');
                ?>
                <div class="next-card slide-up clickable-card" onclick="triggerChartFilter('<?= esc_url($cat['url']) ?>')"
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


        </div>

        <!-- ===== SECTION 2: Status Cards ===== -->
        <div class="next-grid mt-4">
            <?php
            $delay2 = 0;
            foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                ?>
                <div class="next-card slide-up clickable-card"
                    onclick="triggerChartFilter('<?= esc_url($status_urls[$status] ?? home_url('/home/')) ?>')"
                    style="animation-delay: <?= $delay2 ?>s; cursor: pointer;" title="View <?= esc_attr($status) ?> devices">
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
                                <div class="next-progress-fill" style="width: 0%; background: <?= $config['color'] ?>;"
                                    data-width="<?= $percent ?>%"></div>
                            </div>
                            <span class="next-progress-text"><?= $percent ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== SECTION 2.5: Stock Monitor — Live ===== -->
        <input type="hidden" id="stock-monitor-ajax-url" value="<?= admin_url('admin-ajax.php') ?>">

        <div class="stock-monitor-section">
            <!-- Live Header -->
            <div class="stock-live-header">
                <div class="stock-live-header-left">
                    <span class="stock-live-title">Stock Monitor</span>
                    <span class="stock-live-badge">
                        <span class="stock-pulse-dot"></span>
                        Live
                    </span>
                    <span class="stock-live-timestamp" id="stock-live-timestamp">Loading...</span>
                </div>
                <div class="stock-live-header-right">
                    <button type="button" class="stock-refresh-btn" id="stock-refresh-btn" title="Refresh now">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Category × Status Cards -->
            <div class="stock-cards-grid">
                <?php
                $sm_categories = [
                    'Monitor' => ['icon' => 'fa-desktop', 'color' => '#FDB840'],
                    'Laptop' => ['icon' => 'fa-laptop', 'color' => '#15A5DA'],
                    'Accessories' => ['icon' => 'fa-plug', 'color' => '#6ABF57'],
                ];
                $sm_statuses = ['Available', 'In Use', 'Maintenance', 'Retired'];
                $sm_status_colors = [
                    'Available' => '#6ABF57',
                    'In Use' => '#F05353',
                    'Maintenance' => '#FDB840',
                    'Retired' => '#919191',
                ];

                foreach ($sm_categories as $cat_name => $cat_cfg):
                    $cat_slug = strtolower(str_replace(' ', '-', $cat_name));
                    ?>
                    <div class="stock-category-card" id="stock-card-<?= $cat_slug ?>"
                        style="--card-accent: <?= $cat_cfg['color'] ?>;">
                        <div class="stock-card-header">
                            <div class="stock-card-category">
                                <div class="stock-card-icon"
                                    style="background: <?= $cat_cfg['color'] ?>15; color: <?= $cat_cfg['color'] ?>;">
                                    <i class="fa-solid <?= $cat_cfg['icon'] ?>"></i>
                                </div>
                                <span class="stock-card-name"><?= esc_html($cat_name) ?></span>
                            </div>
                            <span class="stock-card-total" id="stock-total-<?= $cat_slug ?>">—</span>
                        </div>

                        <div class="stock-bars-wrap">
                            <?php foreach ($sm_statuses as $status):
                                $status_slug = strtolower(str_replace(' ', '-', $status));
                                $bar_color = $sm_status_colors[$status];
                                ?>
                                <div class="stock-bar-row">
                                    <span class="stock-bar-label"><?= esc_html($status) ?></span>
                                    <div class="stock-bar-track">
                                        <div class="stock-bar-fill" id="stock-bar-<?= $cat_slug ?>-<?= $status_slug ?>"
                                            style="width: 0%; background: <?= $bar_color ?>;"></div>
                                    </div>
                                    <span class="stock-bar-count" id="stock-count-<?= $cat_slug ?>-<?= $status_slug ?>">—</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Low-Stock Alert Banner -->
            <div class="stock-low-alert" id="stock-low-alert">
                <div class="stock-low-alert-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="stock-low-alert-content">
                    <div class="stock-low-alert-title">Low Stock Warning</div>
                    <ul class="stock-low-alert-list" id="stock-low-alert-list"></ul>
                </div>
            </div>

            <!-- Total Footer -->
            <div class="stock-total-footer">
                Total devices: <strong id="stock-footer-total">—</strong>
                &nbsp;·&nbsp;
                Available: <strong id="stock-footer-available">—</strong>
            </div>
        </div>

        <!-- ===== SECTION 3: Charts ===== -->
        <div class="next-grid-3 mt-4">

            <!-- Donut Chart -->
            <div class="next-card slide-up" style="animation-delay: 0.4s;">
                <h3 class="next-section-title">Device Distribution</h3>
                <div id="chart-distribution" class="mt-4 flex justify-center items-center"
                    style="padding-top: 65px; padding-left: 30px">
                    <?php
                    $pct_monitor = $total_devices > 0 ? round(($total_monitor / $total_devices) * 100, 1) : 0;
                    $pct_laptop = $total_devices > 0 ? round(($total_laptop / $total_devices) * 100, 1) : 0;
                    $pct_acc = $total_devices > 0 ? round(($total_accessories / $total_devices) * 100, 1) : 0;
                    $device_sectors = [
                        ['label' => 'Monitor', 'pct' => $pct_monitor, 'color' => '#FDB840', 'url' => home_url('/monitor/')],
                        ['label' => 'Laptop', 'pct' => $pct_laptop, 'color' => '#15A5DA', 'url' => home_url('/laptop/')],
                        ['label' => 'Accessories', 'pct' => $pct_acc, 'color' => '#6ABF57', 'url' => home_url('/accessories/')],
                    ];
                    echo render_sectors_donut([
                        'symbol' => 'DEVICES',
                        'caption' => $total_devices . ' total units',
                        'sectors' => $device_sectors,
                    ]);
                    ?>
                </div>
            </div>

            <!-- Status Overview Chart -->
            <div class="next-card slide-up" style="animation-delay: 0.25s;">
                <h3 class="next-section-title">Status Overview</h3>
                <div id="chart-status" class="mt-4"></div>
            </div>

            <!-- Department Chart -->
            <div class="next-card slide-up" style="animation-delay: 0.3s;">
                <h3 class="next-section-title">Devices by Department</h3>
                <div id="chart-department" class="mt-4"></div>
            </div>

        </div>

    </div>

    <style>
        /* ============================================================
                       device_dashboard — page-specific styles only
                       Shared card/grid/animation styles → dashboard_cards.css
                       ============================================================ */

        /* ---- New Devices Notification Card ---- */
        .new-devices-alert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            cursor: pointer;
            user-select: none;
        }

        .new-devices-alert-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .new-devices-alert-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            animation: bellPulse 2.2s ease-in-out infinite;
        }

        @keyframes bellPulse {

            0%,
            100% {
                transform: scale(1) rotate(0deg);
            }

            25% {
                transform: scale(1.08) rotate(-6deg);
            }

            75% {
                transform: scale(1.08) rotate(6deg);
            }
        }

        .new-devices-alert-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .new-devices-count-badge {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            min-width: 20px;
            text-align: center;
        }

        .new-devices-alert-subtitle {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .new-devices-toggle-btn {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #16a34a;
            cursor: pointer;
            transition: all 0.25s ease;
            font-size: 0.8rem;
        }

        .new-devices-toggle-btn:hover {
            background: #dcfce7;
        }

        .new-devices-toggle-btn.expanded i {
            transform: rotate(180deg);
        }

        .new-devices-toggle-btn i {
            transition: transform 0.3s ease;
        }

        .new-devices-alert-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border-top: 0 solid transparent;
        }

        .new-devices-alert-body.expanded {
            max-height: 500px;
            border-top: 1px solid #bbf7d0;
        }

        .new-devices-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .new-devices-table thead {
            background: #f0fdf4;
        }

        .new-devices-table th {
            padding: 0.6rem 1.25rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none;
        }

        .new-devices-table td {
            padding: 0.65rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            border-top: none;
            border-left: none;
            border-right: none;
        }

        .new-devices-table tbody tr:last-child td {
            border-bottom: none;
        }

        .new-devices-table tbody tr:hover {
            background: #f0fdf4;
        }

        .new-device-badge-sm {
            display: inline-block;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 4px;
            letter-spacing: 0.06em;
            margin-right: 6px;
            vertical-align: middle;
            line-height: 1.5;
        }

        /* ---- QR Bar ---- */
        .dash-qr-scan-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
        }

        .dash-qr-scan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        }

        .dash-qr-stop-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dash-qr-stop-btn:hover {
            background: #dc2626;
        }

        .dash-qr-hint {
            color: #94a3b8;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .dash-scan-popup {
            border-radius: 16px !important;
        }
    </style>

    <script>
        // Toggle New Devices Notification Card
        function toggleNewDevices() {
            const body = document.getElementById('new-devices-body');
            const btn = document.getElementById('new-devices-toggle-btn');
            if (body && btn) {
                body.classList.toggle('expanded');
                btn.classList.toggle('expanded');
            }
        }

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

            // Progress Bar Animation (for Section 2)
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
            initApexCharts();
        }

        function triggerChartFilter(targetUrl) {
            try {
                var targetObj = new URL(targetUrl, window.location.origin);
                var isSamePath = (targetObj.pathname.replace(/\/$/, '') === window.location.pathname.replace(/\/$/, ''));
                var params = targetObj.searchParams;

                // Sync all select filter controls with the clicked card's parameters
                var filterFields = ['filter_status', 'filter_department', 'filter_brand', 'filter_position', 'filter_category'];
                filterFields.forEach(function (fieldName) {
                    var selectElem = document.querySelector('select[name="' + fieldName + '"]');
                    if (selectElem) {
                        var val = params.get(fieldName) || '';
                        selectElem.value = val;
                        selectElem.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                var searchInput = document.querySelector('input[name="device_search"]');
                if (searchInput) {
                    var searchVal = params.get('device_search') || '';
                    searchInput.value = searchVal;
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                if (isSamePath && typeof window.loadAjaxContent === 'function' && (document.querySelector('.table-wrapper') || document.querySelector('.table-custom') || document.querySelector('.table-wrapper-employee'))) {
                    window.loadAjaxContent(targetUrl);
                    var tableElem = document.getElementById('bulk-action-form') || document.getElementById('device_table') || document.querySelector('.table-wrapper') || document.querySelector('.table-wrapper-employee');
                    if (tableElem) {
                        tableElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    window.location.href = targetUrl;
                }
            } catch (e) {
                window.location.href = targetUrl;
            }
        }
        window.triggerChartFilter = triggerChartFilter;

        function initApexCharts() {
            // Chart 2: Status Overview
            var optionsStatus = {
                series: [{ name: 'Devices', data: <?= $js_status_counts ?> }],
                chart: {
                    type: 'bar',
                    height: 320,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    events: {
                        dataPointSelection: function (event, chartContext, config) {
                            var categories = <?= $js_status_labels ?>;
                            var selected = categories[config.dataPointIndex];
                            if (selected === 'Maintenance') {
                                window.location.href = '<?= esc_url(home_url('/maintenance/')) ?>';
                            } else if (selected) {
                                var targetUrl = '<?= esc_url(home_url('/home/')) ?>?filter_status=' + encodeURIComponent(selected);
                                triggerChartFilter(targetUrl);
                            }
                        }
                    }
                },
                xaxis: { categories: <?= $js_status_labels ?> },
                colors: ['#6ABF57', '#F05353', '#FDB840', '#919191'],
                plotOptions: {
                    bar: { borderRadius: 6, columnWidth: '45%', distributed: true }
                },
                legend: { show: false },
                dataLabels: { enabled: true, style: { colors: ['#fff'] } }
            };
            var chartStatus = new ApexCharts(document.querySelector("#chart-status"), optionsStatus);
            chartStatus.render();

            // Chart 3: Devices by Department
            var optionsDept = {
                series: [{ name: 'Devices', data: <?= $js_dept_counts ?> }],
                chart: {
                    type: 'bar',
                    height: 320,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    events: {
                        dataPointSelection: function (event, chartContext, config) {
                            var categories = <?= $js_dept_labels ?>;
                            var selected = categories[config.dataPointIndex];
                            if (selected) {
                                var targetUrl = '<?= esc_url(home_url('/home/')) ?>?filter_department=' + encodeURIComponent(selected);
                                triggerChartFilter(targetUrl);
                            }
                        }
                    }
                },
                plotOptions: {
                    bar: { borderRadius: 4, horizontal: true, distributed: true }
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
            document.addEventListener("DOMContentLoaded", initNextDashboard);
        } else {
            initNextDashboard();
        }
    </script>


    <?php
    return ob_get_clean();
}
add_shortcode('device_dashboard', 'device_dashboard');

