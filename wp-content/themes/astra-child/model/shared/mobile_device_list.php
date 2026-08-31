<!-- Mobile Device List -->
<div class="mobile-only-container" style="margin-top: 16px;">
    <!-- Mobile Filter Button -->
    <button type="button" class="mobile-filter-btn" onclick="openBottomSheet()">
        <span><i class="fa-solid fa-filter"></i> Filters & Search</span>
        <i class="fa-solid fa-chevron-right text-muted"></i>
    </button>

    <?php foreach ($rows as $row): 
        $is_new_device = (!empty($row->CreatedAt) && strtotime($row->CreatedAt) >= strtotime('-7 days')) ||
                         (!empty($row->ReceiveDate) && strtotime($row->ReceiveDate) >= strtotime('-7 days'));
    ?>
        <div class="mobile-device-card"
            onclick="if(!event.target.closest('a, button')) { window.openDeviceQuickPeek('<?= esc_js($row->DeviceID) ?>'); }"
            style="cursor: pointer;">
            <div class="mobile-device-header">
                <div class="mobile-device-title-area">
                    <div class="mobile-device-title">
                        <?= htmlspecialchars($row->Brand . ' ' . $row->Model) ?>
                        <?php if ($is_new_device): ?>
                            <span class="mobile-new-badge">NEW</span>
                        <?php endif; ?>
                    </div>
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
                    $formattedOwner = stock_supply_format_nickname_with_initial($nickname, '', '', $owner);
                    if ($formattedOwner === '-' || empty($formattedOwner)) {
                        echo '-';
                    } else {
                        echo htmlspecialchars($formattedOwner);
                        $deptAbbr = stock_supply_get_dept_abbr($row->Department ?? '');
                        if (!empty($deptAbbr)) {
                            echo ' <span class="text-muted small">' . htmlspecialchars($deptAbbr) . '</span>';
                        }
                    }
                    ?>
                </div>
                <div class="mobile-device-id"
                    onclick="event.stopPropagation(); window.openDeviceQuickPeek('<?= esc_js($row->DeviceID) ?>')">
                    <strong style="color:#2563eb;"><i class="fa-solid fa-bolt" style="font-size:0.75rem;"></i>
                        <?= $row->DeviceID ?></strong>
                </div>
            </div>
            <div class="mobile-device-actions">
                <button type="button" class="mobile-btn-action mobile-btn-peek"
                    onclick="event.stopPropagation(); window.openDeviceQuickPeek('<?= esc_js($row->DeviceID) ?>')">
                    <i class="fa-solid fa-bolt"></i> Peek
                </button>
                <?php if (strcasecmp($row->Status, 'Maintenance') === 0): ?>
                    <a href="?maintenance=<?= $row->DeviceID ?>" class="mobile-btn-action mobile-btn-secondary"
                        style="flex: 1;"><i class="fa-solid fa-gear"></i> Edit</a>
                <?php else: ?>
                    <a href="?edit=<?= $row->DeviceID ?>" class="mobile-btn-action mobile-btn-secondary" style="flex: 1;"><i
                            class="fa-solid fa-gear"></i> Edit</a>
                <?php endif; ?>
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