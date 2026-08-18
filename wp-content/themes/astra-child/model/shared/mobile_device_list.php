<!-- Mobile Device List -->
<div class="mobile-only-container" style="margin-top: 16px;">
    <?php foreach ($rows as $row): ?>
        <div class="mobile-device-card">
            <div class="mobile-device-header">
                <div class="mobile-device-title-area">
                    <div class="mobile-device-title"><?= htmlspecialchars($row->Brand . ' ' . $row->Model) ?></div>
                    <div class="mobile-device-meta">SN: <?= htmlspecialchars($row->SerialNumber ?: '-') ?></div>
                </div>
                <?php
                $statusClass = 'status-retired';
                if (strcasecmp($row->Status, 'Available') === 0)
                    $statusClass = 'status-available';
                elseif (strcasecmp($row->Status, 'In Use') === 0)
                    $statusClass = 'status-inuse';
                elseif (strcasecmp($row->Status, 'Maintenance') === 0)
                    $statusClass = 'status-maintenance';
                ?>
                <div class="status-badge <?= $statusClass ?>">
                    <span class="status-dot"></span>
                    <?= esc_html($row->Status) ?>
                </div>
            </div>
            <div class="mobile-device-body">
                <div class="mobile-device-owner">
                    <i class="fa-solid fa-user"></i>
                    <?php
                    $owner = trim($row->Owner ?? '');
                    $nickname = trim($row->Nickname ?? '');
                    if ($owner === '' && $nickname === '') {
                        echo '-';
                    } else {
                        if ($nickname !== '')
                            echo htmlspecialchars($nickname) . ' ';
                        if ($owner !== '') {
                            preg_match('/\((.*?)\)$/', $owner, $matches);
                            $nameOnly = trim(preg_replace('/\s*\(.*?\)$/', '', $owner));
                            $nameParts = explode(' ', $nameOnly);
                            if (count($nameParts) > 1) {
                                $lastInitial = strtoupper(mb_substr(end($nameParts), 0, 1)) . '.';
                                echo htmlspecialchars($lastInitial);
                            }
                        }
                        $deptAbbr = stock_supply_get_dept_abbr($row->Department ?? '');
                        if (!empty($deptAbbr)) {
                            echo ' <span class="text-muted small">' . htmlspecialchars($deptAbbr) . '</span>';
                        }
                    }
                    ?>
                </div>
                <div class="mobile-device-id"><strong><?= $row->DeviceID ?></strong></div>
            </div>
            <div class="mobile-device-actions">
                <?php if (strcasecmp($row->Status, 'Maintenance') === 0): ?>
                    <a href="?maintenance=<?= $row->DeviceID ?>" class="mobile-btn-action mobile-btn-secondary"><i
                            class="fa-solid fa-gear"></i> Edit</a>
                <?php else: ?>
                    <a href="?edit=<?= $row->DeviceID ?>" class="mobile-btn-action mobile-btn-secondary"><i
                            class="fa-solid fa-gear"></i> Edit</a>
                <?php endif; ?>
                <a href="?view=<?= esc_attr($row->DeviceID) ?>" class="mobile-btn-action mobile-btn-primary"><i
                        class="fa-solid fa-magnifying-glass"></i> View</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Bottom Sheet for Filters -->
<div class="bottom-sheet-backdrop" id="bottomSheetBackdrop" onclick="closeBottomSheet()"></div>
<div class="bottom-sheet" id="mobileBottomSheet">
    <div class="bottom-sheet-header">
        <h3>Filters</h3>
        <button type="button" class="bottom-sheet-close" onclick="closeBottomSheet()"><i
                class="fa-solid fa-times"></i></button>
    </div>
    <div id="mobile-filter-container"></div>
</div>