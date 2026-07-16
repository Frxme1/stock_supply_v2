<?php
function employee_dashboard()
{
    global $wpdb;
    $table_owner = 'ViewOwnersWithNames';

    // ดึงจำนวนพนักงานทั้งหมด (นับทั้ง Admin และ User ที่มีตำแหน่ง Full-time หรือ Intern)
    $total_employees = $wpdb->get_var("
    SELECT COUNT(*) FROM $table_owner
    WHERE Position IN ('Full-time', 'Intern')
");

    // สรุปจำนวนตามตำแหน่ง (นับทั้ง Admin และ User)
    $position_summary = $wpdb->get_results("
    SELECT Position, COUNT(*) as count
    FROM $table_owner
    WHERE Position IN ('Full-time', 'Intern')
    GROUP BY Position
");


    // ตั้งค่ารูปแบบแต่ละตำแหน่ง
    $position_config = [
        'Full-time' => [
            'icon' => '👔',
            'gradient' => 'linear-gradient(to right, #2196F3, #BBDEFB)'
        ],
        'Intern' => [
            'icon' => '🎓',
            'gradient' => 'linear-gradient(to right, #4CAF50, #C8E6C9)'
        ],
    ];

    // สร้าง Map สำหรับจำนวนพนักงานแต่ละตำแหน่ง
    $summary_map = [];
    foreach ($position_summary as $row) {
        $summary_map[$row->Position] = intval($row->count);
    }

    ob_start();
?>

    <div class="employee-dashboard">
        <div class="dashboard-cards">
            <?php foreach ($position_config as $position => $config):
                $count = $summary_map[$position] ?? 0;
                $percent = $total_employees > 0 ? round(($count / $total_employees) * 100) : 0;
            ?>
                <div class="card-status" style="background: <?= $config['gradient'] ?>; position:relative;">
                    <div class="card-top">
                        <div class="card-title"><?= esc_html($position) ?></div>
                        <div class="card-icon"><?= $config['icon'] ?></div>
                    </div>
                    <div class="card-bottom">
                        <div class="card-count"><strong><?= $count ?></strong> person<?= $count > 1 ? 's' : '' ?></div>
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

        <div class="all-employee-summary" style="margin-bottom: -50px;">
            <h4>All Employees</h4>
            <div class="employee-summary-container">
                <svg class="circle-chart" viewBox="0 0 36 36">
                    <circle class="circle-bg" cx="18" cy="18" r="16" />
                    <circle class="circle-fill" cx="18" cy="18" r="16" stroke-dasharray="100, 100" />
                    <text x="18" y="20" class="circle-text">100%</text>
                </svg>
                <div class="employee-info">
                    <h2><?= $total_employees ?></h2>
                    <p>person<?= $total_employees > 1 ? 's' : '' ?> </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .employee-dashboard {
            padding: 20px;
            margin: 20px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
        }

        .all-employee-summary {
            background: #f4faff;
            padding: 30px;
            margin-top: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .employee-summary-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 20px;
        }

        .circle-chart {
            width: 80px;
            height: 80px;
        }

        .circle-bg {
            fill: none;
            stroke: #e0e0e0;
            stroke-width: 3.8;
        }

        .circle-fill {
            fill: none;
            stroke: #2196F3;
            stroke-width: 3.8;
            stroke-linecap: round;
        }

        .circle-text {
            font-size: 0.5rem;
            text-anchor: middle;
            fill: #333;
        }

        .employee-info h2 {
            margin: 0;
            font-size: 2rem;
            color: #2196F3;

        }

        @media (max-width: 768px) {
            .card-status {
                text-align: center;
            }

            .card-top {
                flex-direction: column;
                gap: 10px;
            }

            .employee-summary-container {
                flex-direction: column;
            }
        }
    </style>

<?php
    return ob_get_clean();
}
add_shortcode('employee_dashboard_summary', 'employee_dashboard');
