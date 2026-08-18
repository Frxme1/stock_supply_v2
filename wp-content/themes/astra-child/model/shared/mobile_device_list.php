<?php
/**
 * Mobile Device List & Filter Component
 * Matches high-end modern task/inventory app reference mockup
 */

// Compute dynamic category / page title
$display_page_title = 'รายการสินค้า';
if (!empty($qr_category_filter)) {
    $display_page_title = $qr_category_filter;
} elseif (function_exists('is_page')) {
    if (is_page('monitor')) {
        $display_page_title = 'Monitor';
    } elseif (is_page('laptop')) {
        $display_page_title = 'Laptop';
    } elseif (is_page('accessories')) {
        $display_page_title = 'Accessories';
    } elseif (is_page('home') || is_page('all-devices')) {
        $display_page_title = 'Stock Supply';
    } else {
        $display_page_title = get_the_title() ?: 'Stock Supply';
    }
}

$curr_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$curr_search = isset($_GET['device_search']) ? trim($_GET['device_search']) : '';
$curr_sort   = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'newest';

// Count active filter conditions
$active_filter_count = 0;
if (!empty($_GET['filter_brand'])) $active_filter_count++;
if (!empty($_GET['filter_department'])) $active_filter_count++;
if (!empty($_GET['device_search'])) $active_filter_count++;
if (!empty($_GET['filter_status'])) $active_filter_count++;

// Status list for pills
$status_pills = [
    ['key' => '',            'label' => 'All task'],
    ['key' => 'Available',   'label' => 'Available'],
    ['key' => 'In Use',      'label' => 'In progress'],
    ['key' => 'Maintenance', 'label' => 'Maintenance'],
    ['key' => 'Retired',     'label' => 'Retired'],
];

// Helper to construct filter URLs preserving other params
if (!function_exists('stock_supply_mob_filter_url')) {
    function stock_supply_mob_filter_url($status_val) {
        $params = $_GET;
        if ($status_val === '') {
            unset($params['filter_status']);
        } else {
            $params['filter_status'] = $status_val;
        }
        unset($params['paged']);
        return '?' . http_build_query($params);
    }
}
?>

<!-- ========================================================
     MOBILE APP HEADER & FILTER BAR (Matches Reference Mockup)
     ======================================================== -->
<div class="mob-view-container mobile-only-container">

    <!-- Top Header -->
    <div class="mob-header">
        <h1 class="mob-header-title"><?= esc_html($display_page_title) ?></h1>
        <div class="mob-header-actions">
            <!-- Search Button -->
            <button type="button" class="mob-circle-btn <?= !empty($curr_search) ? 'active' : '' ?>" id="mobSearchToggleBtn" onclick="toggleMobSearch()" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <!-- Notification Button -->
            <a href="<?= esc_url(home_url('/history/')) ?>" class="mob-circle-btn" aria-label="History & Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="mob-purple-dot"></span>
            </a>
        </div>
    </div>

    <!-- Collapsible Quick Search Box -->
    <div class="mob-quick-search-wrap" id="mobQuickSearchWrap" style="<?= !empty($curr_search) ? 'display: block;' : 'display: none;' ?>">
        <form method="GET" action="" class="mob-quick-search-form">
            <?php
            foreach ($_GET as $k => $v) {
                if (!in_array($k, ['device_search', 'paged'])) {
                    if (is_array($v)) {
                        foreach ($v as $sub_v) {
                            echo '<input type="hidden" name="' . esc_attr($k) . '[]" value="' . esc_attr($sub_v) . '">';
                        }
                    } else {
                        echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
                    }
                }
            }
            ?>
            <div class="mob-search-input-box">
                <i class="fa-solid fa-magnifying-glass mob-search-box-icon"></i>
                <input type="text" name="device_search" value="<?= esc_attr($curr_search) ?>" placeholder="Search devices, SN, owner..." class="mob-search-input" id="mobSearchInput">
                <?php if (!empty($curr_search)): ?>
                    <a href="<?= esc_url(remove_query_arg(['device_search', 'paged'])) ?>" class="mob-search-clear" title="Clear search"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
                <button type="submit" class="mob-search-submit" aria-label="Submit Search"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </form>
    </div>

    <!-- Status Filter Pills (Horizontal Scroll) -->
    <div class="mob-pills-row">
        <?php foreach ($status_pills as $pill): 
            $is_active = ($curr_status === $pill['key']);
            $pill_url = esc_url(stock_supply_mob_filter_url($pill['key']));
        ?>
            <a href="<?= $pill_url ?>" class="mob-filter-pill <?= $is_active ? 'active' : '' ?>">
                <?= esc_html($pill['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Sub-Filter Row (Filters & Sort By) -->
    <div class="mob-subfilter-bar">
        <button type="button" class="mob-sub-btn" onclick="openBottomSheet()">
            <i class="fa-solid fa-filter"></i>
            <span>Filters</span>
            <?php if ($active_filter_count > 0): ?>
                <span class="mob-badge-count"><?= $active_filter_count ?></span>
            <?php endif; ?>
        </button>

        <button type="button" class="mob-sub-btn" onclick="toggleMobSortModal()">
            <i class="fa-solid fa-arrow-down-short-wide"></i>
            <span>Sort by</span>
        </button>
    </div>

    <!-- Mobile Device Cards (Soft Bento Style matching mockup) -->
    <div class="mob-card-list">
        <?php if (empty($rows)): ?>
            <div class="mob-empty-card">
                <div class="mob-empty-icon"><i class="fa-solid fa-box-open"></i></div>
                <h4 class="mob-empty-title">No devices found</h4>
                <p class="mob-empty-desc">Try resetting your filters or search keywords.</p>
                <a href="<?= esc_url(remove_query_arg(['device_search', 'filter_status', 'filter_brand', 'filter_department', 'paged'])) ?>" class="mob-reset-btn">Reset All Filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($rows as $row): 
                $status = $row->Status;
                $statusPillClass = 'mob-pill-retired';
                if (strcasecmp($status, 'Available') === 0)       $statusPillClass = 'mob-pill-available';
                elseif (strcasecmp($status, 'In Use') === 0)      $statusPillClass = 'mob-pill-inuse';
                elseif (strcasecmp($status, 'Maintenance') === 0)  $statusPillClass = 'mob-pill-maintenance';

                // Format dates
                $date_display = '';
                if (!empty($row->UpdatedAt)) {
                    $date_display = date('M j, Y', strtotime($row->UpdatedAt));
                } elseif (!empty($row->ReceiveDate)) {
                    $date_display = date('M j, Y', strtotime($row->ReceiveDate));
                } elseif (!empty($row->CreatedAt)) {
                    $date_display = date('M j, Y', strtotime($row->CreatedAt));
                }

                $dept_text = '';
                if (!empty($row->Department)) {
                    $dept_text = function_exists('stock_supply_get_dept_abbr') ? stock_supply_get_dept_abbr($row->Department) : $row->Department;
                }
            ?>
                <div class="mob-task-card">
                    <!-- Top Status Badge & Device ID -->
                    <div class="mob-card-top-row">
                        <span class="mob-status-tag <?= $statusPillClass ?>">
                            <?= esc_html($status) ?>
                        </span>
                        <span class="mob-card-id">
                            <?= esc_html($row->DeviceID) ?>
                        </span>
                    </div>

                    <!-- Title & Subtitle -->
                    <h3 class="mob-card-title"><?= esc_html($row->Brand . ' ' . $row->Model) ?></h3>
                    <div class="mob-card-subtitle">
                        <?= esc_html($row->Category ?? '') ?>
                        <?php if (!empty($dept_text)): ?>
                            · <?= esc_html($dept_text) ?>
                        <?php endif; ?>
                    </div>

                    <!-- Meta info row (Date, User / Comments / SN) -->
                    <div class="mob-card-meta-row">
                        <?php if (!empty($date_display)): ?>
                            <div class="mob-meta-item">
                                <i class="fa-regular fa-calendar"></i>
                                <span><?= esc_html($date_display) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $owner_text = trim($row->Nickname ?: ($row->Owner ?: ''));
                        if (!empty($owner_text)): ?>
                            <div class="mob-meta-item">
                                <i class="fa-regular fa-user"></i>
                                <span><?= esc_html($owner_text) ?></span>
                            </div>
                        <?php elseif (!empty($row->SerialNumber)): ?>
                            <div class="mob-meta-item">
                                <i class="fa-solid fa-barcode"></i>
                                <span>SN: <?= esc_html($row->SerialNumber) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Striped Progress Bar Track matching mockup -->
                    <div class="mob-progress-track">
                        <div class="mob-progress-fill <?= $statusPillClass ?>"></div>
                    </div>

                    <!-- Actions row -->
                    <div class="mob-card-actions">
                        <?php if (strcasecmp($row->Status, 'Maintenance') === 0): ?>
                            <a href="?maintenance=<?= esc_attr($row->DeviceID) ?>" class="mob-btn-sec"><i class="fa-solid fa-gear"></i> Edit</a>
                        <?php else: ?>
                            <a href="?edit=<?= esc_attr($row->DeviceID) ?>" class="mob-btn-sec"><i class="fa-solid fa-gear"></i> Edit</a>
                        <?php endif; ?>
                        <a href="?view=<?= esc_attr($row->DeviceID) ?>" class="mob-btn-pri"><i class="fa-solid fa-magnifying-glass"></i> View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Bottom Sheet for Filters -->
<div class="bottom-sheet-backdrop" id="bottomSheetBackdrop" onclick="closeBottomSheet()"></div>
<div class="bottom-sheet" id="mobileBottomSheet">
    <div class="bottom-sheet-header">
        <h3>Filters</h3>
        <button type="button" class="bottom-sheet-close" onclick="closeBottomSheet()"><i class="fa-solid fa-times"></i></button>
    </div>
    <div id="mobile-filter-container"></div>
</div>

<!-- Sort Modal Bottom Sheet -->
<div class="bottom-sheet-backdrop" id="sortSheetBackdrop" onclick="closeMobSortModal()"></div>
<div class="bottom-sheet" id="mobSortBottomSheet">
    <div class="bottom-sheet-header">
        <h3>Sort by</h3>
        <button type="button" class="bottom-sheet-close" onclick="closeMobSortModal()"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="mob-sort-options">
        <a href="<?= esc_url(add_query_arg(['sort_by' => 'newest', 'paged' => 1])) ?>" class="mob-sort-item <?= ($curr_sort === 'newest' || empty($curr_sort)) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-clock-rotate-left"></i> Newest updated</span>
            <?= ($curr_sort === 'newest' || empty($curr_sort)) ? '<i class="fa-solid fa-check"></i>' : '' ?>
        </a>
        <a href="<?= esc_url(add_query_arg(['sort_by' => 'oldest', 'paged' => 1])) ?>" class="mob-sort-item <?= $curr_sort === 'oldest' ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrow-up-1-9"></i> Oldest updated</span>
            <?= $curr_sort === 'oldest' ? '<i class="fa-solid fa-check"></i>' : '' ?>
        </a>
        <a href="<?= esc_url(add_query_arg(['sort_by' => 'brand_asc', 'paged' => 1])) ?>" class="mob-sort-item <?= $curr_sort === 'brand_asc' ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrow-down-a-z"></i> Brand / Model (A-Z)</span>
            <?= $curr_sort === 'brand_asc' ? '<i class="fa-solid fa-check"></i>' : '' ?>
        </a>
        <a href="<?= esc_url(add_query_arg(['sort_by' => 'id_asc', 'paged' => 1])) ?>" class="mob-sort-item <?= $curr_sort === 'id_asc' ? 'active' : '' ?>">
            <span><i class="fa-solid fa-tag"></i> Device ID</span>
            <?= $curr_sort === 'id_asc' ? '<i class="fa-solid fa-check"></i>' : '' ?>
        </a>
    </div>
</div>

<script>
function toggleMobSearch() {
    var wrap = document.getElementById('mobQuickSearchWrap');
    var btn = document.getElementById('mobSearchToggleBtn');
    var input = document.getElementById('mobSearchInput');
    if (!wrap) return;
    if (wrap.style.display === 'none' || wrap.style.display === '') {
        wrap.style.display = 'block';
        if (btn) btn.classList.add('active');
        if (input) setTimeout(function() { input.focus(); }, 50);
    } else {
        wrap.style.display = 'none';
        if (btn) btn.classList.remove('active');
    }
}

function toggleMobSortModal() {
    var sheet = document.getElementById('mobSortBottomSheet');
    var backdrop = document.getElementById('sortSheetBackdrop');
    if (sheet) {
        if (sheet.parentElement !== document.body) document.body.appendChild(sheet);
        sheet.classList.add('open');
    }
    if (backdrop) {
        if (backdrop.parentElement !== document.body) document.body.appendChild(backdrop);
        backdrop.classList.add('open');
    }
}

function closeMobSortModal() {
    var sheet = document.getElementById('mobSortBottomSheet');
    var backdrop = document.getElementById('sortSheetBackdrop');
    if (sheet) sheet.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
}

function closeBottomSheet() {
    var sheet = document.getElementById('mobileBottomSheet');
    var backdrop = document.getElementById('bottomSheetBackdrop');
    if (sheet) sheet.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
}

function openBottomSheet() {
    var sheet = document.getElementById('mobileBottomSheet');
    var backdrop = document.getElementById('bottomSheetBackdrop');
    if (sheet) {
        if (sheet.parentElement !== document.body) document.body.appendChild(sheet);
        sheet.classList.add('open');
    }
    if (backdrop) {
        if (backdrop.parentElement !== document.body) document.body.appendChild(backdrop);
        backdrop.classList.add('open');
    }
    var filterForm = document.getElementById('advanced-filter-form');
    var mobileContainer = document.getElementById('mobile-filter-container');
    if (filterForm && mobileContainer && filterForm.parentElement !== mobileContainer) {
        mobileContainer.appendChild(filterForm);
        filterForm.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var sheet = document.getElementById('mobileBottomSheet');
    var backdrop = document.getElementById('bottomSheetBackdrop');
    var sortSheet = document.getElementById('mobSortBottomSheet');
    var sortBackdrop = document.getElementById('sortSheetBackdrop');
    if (sheet && sheet.parentElement !== document.body) document.body.appendChild(sheet);
    if (backdrop && backdrop.parentElement !== document.body) document.body.appendChild(backdrop);
    if (sortSheet && sortSheet.parentElement !== document.body) document.body.appendChild(sortSheet);
    if (sortBackdrop && sortBackdrop.parentElement !== document.body) document.body.appendChild(sortBackdrop);

    if (window.innerWidth <= 768) {
        var filterForm = document.getElementById('advanced-filter-form');
        var mobileContainer = document.getElementById('mobile-filter-container');
        if (filterForm && mobileContainer) {
            mobileContainer.appendChild(filterForm);
            filterForm.style.display = 'block';
        }
    }
});
</script>
