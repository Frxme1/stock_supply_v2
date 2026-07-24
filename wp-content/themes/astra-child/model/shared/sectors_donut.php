<?php
/**
 * Reusable SectorsDonut PHP Component & Shortcode
 * Adapts Framer Motion / Shadcn SectorsDonut to PHP/Vanilla JS
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a Sectors Donut component
 * 
 * @param array $args Component options
 * @return string HTML output
 */
function render_sectors_donut($args = []) {
    $default_colors = ["#4790E4", "#7FB4EF", "#2E5FA3", "#2AA173", "#B98634", "#94a3b8"];
    $default_sectors = [
        ['label' => 'Technology', 'pct' => 31.2],
        ['label' => 'Financials', 'pct' => 13.9],
        ['label' => 'Health care', 'pct' => 12.4],
        ['label' => 'Consumer disc.', 'pct' => 10.1],
        ['label' => 'Industrials', 'pct' => 8.6],
        ['label' => 'Other', 'pct' => 23.8],
    ];

    $symbol = isset($args['symbol']) ? $args['symbol'] : 'SPY';
    $caption = isset($args['caption']) ? $args['caption'] : 'of 503 holdings';
    $sectors = !empty($args['sectors']) ? $args['sectors'] : $default_sectors;
    $colors = !empty($args['colors']) ? $args['colors'] : $default_colors;
    $class = isset($args['class']) ? $args['class'] : '';
    $id = isset($args['id']) ? 'id="' . esc_attr($args['id']) . '"' : '';

    $R = 52;
    $STROKE = 13;
    $C = 2 * M_PI * $R; // ~326.7256

    // Ensure assets are loaded
    wp_enqueue_style('sectors-donut-css', get_stylesheet_directory_uri() . '/css/sectors-donut.css', [], CHILD_THEME_ASTRA_CHILD_VERSION);
    wp_enqueue_script('sectors-donut-js', get_stylesheet_directory_uri() . '/js/sectors-donut.js', [], CHILD_THEME_ASTRA_CHILD_VERSION, true);

    $arcs = [];
    $acc = 0;
    foreach ($sectors as $i => $s) {
        $pct = floatval($s['pct']);
        $start = $acc;
        $acc += $pct;
        $color = isset($s['color']) ? $s['color'] : $colors[$i % count($colors)];
        
        // Arc dash length with 2px separation gap
        $dash_len = max(0, ($pct / 100) * $C - 2);
        $dash_offset = -(($start / 100) * $C);

        $arcs[] = [
            'label' => $s['label'],
            'pct' => $pct,
            'color' => $color,
            'start' => $start,
            'dash_array' => sprintf('%.4f %.4f', $dash_len, $C),
            'dash_offset' => sprintf('%.4f', $dash_offset)
        ];
    }

    ob_start();
    ?>
    <!-- Fallback stylesheet link if called via shortcode outside standard head -->
    <link rel="stylesheet" href="<?= esc_url(get_stylesheet_directory_uri() . '/css/sectors-donut.css') ?>?v=<?= CHILD_THEME_ASTRA_CHILD_VERSION ?>">

    <div <?= $id ?> class="sectors-donut-container <?= esc_attr($class) ?>">
        <div class="sectors-donut-wrap">
            <svg width="132" height="132" viewBox="0 0 132 132" class="sectors-donut-svg">
                <?php foreach ($arcs as $i => $a): ?>
                    <circle class="sectors-donut-arc"
                            cx="66" cy="66" r="52"
                            fill="none"
                            stroke="<?= esc_attr($a['color']) ?>"
                            stroke-width="13"
                            stroke-dasharray="0 <?= sprintf('%.4f', $C) ?>"
                            data-target-dash="<?= esc_attr($a['dash_array']) ?>"
                            stroke-dashoffset="<?= esc_attr($a['dash_offset']) ?>"
                            data-index="<?= $i ?>" />
                <?php endforeach; ?>
            </svg>
            <div class="sectors-donut-center">
                <span class="sectors-donut-symbol"><?= esc_html($symbol) ?></span>
                <span class="sectors-donut-caption"><?= esc_html($caption) ?></span>
            </div>
        </div>

        <div class="sectors-donut-legend">
            <?php foreach ($arcs as $i => $a): ?>
                <button type="button" class="sectors-donut-legend-item" data-index="<?= $i ?>" title="<?= esc_attr($a['label']) ?> (<?= sprintf('%.1f', $a['pct']) ?>%)">
                    <span class="sectors-donut-badge" style="background-color: <?= esc_attr($a['color']) ?>;"></span>
                    <span class="sectors-donut-label"><?= esc_html($a['label']) ?></span>
                    <span class="sectors-donut-value"><?= sprintf('%.1f', $a['pct']) ?>%</span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="<?= esc_url(get_stylesheet_directory_uri() . '/js/sectors-donut.js') ?>?v=<?= CHILD_THEME_ASTRA_CHILD_VERSION ?>"></script>
    <script>
        if (typeof window.initSectorsDonut === 'function') {
            window.initSectorsDonut();
        }
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode for [sectors_donut]
 */
function sectors_donut_shortcode($atts) {
    $atts = shortcode_atts([
        'symbol' => 'SPY',
        'caption' => 'of 503 holdings',
    ], $atts, 'sectors_donut');

    return render_sectors_donut([
        'symbol' => $atts['symbol'],
        'caption' => $atts['caption'],
    ]);
}
add_shortcode('sectors_donut', 'sectors_donut_shortcode');
