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

    <div class="device-maintenance-dashboard">
        <div class="total-box">
            <p>Total Devices Under Maintenance</p>
            <h1><?= $total_maintenance ?> Unit<?= $total_maintenance > 1 ? 's' : '' ?></h1>
            <span><?= $percent_maintenance ?>% of all devices</span>
        </div>

        <div class="dashboard-cards">
            <?php foreach ($summary_map as $category => $count):
                $percent = $total_maintenance > 0 ? round(($count / $total_maintenance) * 100, 0) : 0;

                // เลือกสี background ตามประเภท
                $gradient = '';
                switch (strtolower($category)) {
                    case 'laptop':
                        $gradient = 'linear-gradient(90deg, #15A5DA, #ffffff)';
                        break;
                    case 'accessories':
                        $gradient = 'linear-gradient(90deg, #6ABF57, #ffffff)';
                        break;
                    default:
                        $gradient = 'linear-gradient(90deg, #888888, #ffffff)'; // default สีเทา
                }
            ?>
                <div class="card-status" style="background: <?= $gradient ?>; color: #fff;">
                    <div class="card-top">
                        <div class="card-title"><?= esc_html($category) ?></div>
                        <div class="card-icon">🛠️</div>
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

    <style>
        .device-maintenance-dashboard {
            border-radius: 12px;
            font-family: 'Segoe UI', sans-serif;
        }

        .dashboard-title {
            text-align: center;
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
        }

        .total-box {
            background: linear-gradient(to right, #FDB840, #ffebc6);
            color: #fff;
            padding: 25px 30px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .total-box p {
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
        }

        .total-box h1 {
            font-size: 2.5rem;
            margin: 10px 0 0;
            color: #fff;
        }

        .total-box span {
            color: #333;
            font-weight: 500;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: -20px;
        }

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
            font-size: 2rem;
            opacity: 0.6;
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

        @media (max-width: 500px) {
            .dashboard-title {
                font-size: 1.5rem;
            }

            .total-box h1 {
                font-size: 2rem;
            }
        }
    </style>

<?php
    return ob_get_clean();
}
add_shortcode('device_dashboard_maintenance', 'device_dashboard_maintenance');
