<?php
function device_dashboard_accessories()
{
    global $wpdb;
    $table_device_wn = 'DevicesWithNames';

    $total_accessories = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn WHERE Category = 'Accessories'");
    $total_devices = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn");

    $status_summary = $wpdb->get_results("
        SELECT Status, COUNT(*) as count
        FROM $table_device_wn
        WHERE Category = 'Accessories'
        GROUP BY Status
    ");

    $status_config = [
        'Available'   => ['color' => '#6ABF57', 'icon' => '✅'],
        'In Use'      => ['color' => '#F05353', 'icon' => '🚫'],
        'Maintenance' => ['color' => '#FDB840', 'icon' => '🛠️'],
        'Retired'     => ['color' => '#000000', 'icon' => '🗑️'],
    ];

    $summary_map = [];
    foreach ($status_summary as $row) {
        $summary_map[$row->Status] = intval($row->count);
    }

    ob_start();
?>
    <div class="device-dashboard" style="background-color: #fff;">
        <div class="dashboard-cards">
            <?php foreach ($status_config as $status => $config):
                $count = $summary_map[$status] ?? 0;
                $percent = $total_devices > 0 ? round(($count / $total_devices) * 100, 0) : 0;
                $percent_accessories = $total_devices > 0 ? round(($total_accessories / $total_devices) * 100, 0) : 0;
            ?>
                <div class="card-status" style="background: linear-gradient(90deg, <?= $config['color'] ?>, #ffffff); color: #fff; position: relative;">
                    <div class="card-top">
                        <div class="card-title"><?= esc_html($status) ?></div>
                        <div class="card-icon"><?= $config['icon'] ?></div>
                    </div>
                    <div class="card-bottom">
                        <div class="card-count"><strong><?= $count ?></strong> unit<?= $count > 1 ? 's' : '' ?></div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <div class="percent-text"><?= $percent ?>%</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>



    <!-- Summary Accessories -->
    <div class="all-device-summary" style="background-color: rgba(21, 165, 218, 0.03); border-radius: 10px; text-align: center; margin-top: 25px;" >
        <h4>All Accessories</h4>
        <div class="summary-container">
            <svg class="circle-chart" viewBox="0 0 36 36">
                <circle class="circle-bg" cx="18" cy="18" r="16" />
                <circle class="circle-fill" cx="18" cy="18" r="16"
                    stroke-dasharray="<?= $percent_accessories ?>, 100" />
                <text x="18" y="20" class="circle-text"><?= $percent_accessories ?>%</text>
            </svg>
            <div class="device-info">
                <h1><?= $total_accessories ?></h1>
                <p>Unit</p>
            </div>
        </div>
    </div>



    <style>
        .card-status {
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .card-icon {
            font-size: 3rem;
            opacity: 0.5;
        }

        .card-bottom {
            margin-top: 20px;
        }

        .card-count {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 5px;
        }

        .percent-text {
            font-size: 0.9rem;
            white-space: nowrap;
            color: #000;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
    </style>

<?php
    return ob_get_clean();
}
add_shortcode('device_dashboard_accessories', 'device_dashboard_accessories');
