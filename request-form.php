<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in to Employee Portal
if (!isset($_SESSION['portal_owner_id']) || empty($_SESSION['portal_owner_id'])) {
    header('Location: portal-login.php');
    exit;
}

$logged_owner_id = intval($_SESSION['portal_owner_id']);
$logged_owner_name = $_SESSION['portal_owner_name'] ?? 'Employee';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress core for database ($wpdb) access
require_once(dirname(__FILE__) . '/wp-load.php');
global $wpdb;

// 1. Fetch active owners
$owners = $wpdb->get_results("
    SELECT o.OwnerID, o.Nickname, d.DepartmentName 
    FROM Owners o
    LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
    WHERE o.StatusID = 1
    ORDER BY o.Nickname ASC
");

// 2. Data for Borrow Request Form
$categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName ASC");
$keywords = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords ORDER BY KeywordName ASC");
$keywords_json = json_encode($keywords);

$available_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
$available_devices = $wpdb->get_results($wpdb->prepare("
    SELECT d.DeviceID, d.CategoryID, d.KeywordID, b.BrandName, d.Model, d.SerialNumber
    FROM Devices d
    LEFT JOIN Brands b ON d.BrandID = b.BrandID
    WHERE d.StatusID = %d
", $available_status));

$devices_by_cat = [];
if ($available_devices) {
    foreach ($available_devices as $d) {
        $devices_by_cat[$d->CategoryID][] = [
            'id' => $d->DeviceID,
            'keyword_id' => $d->KeywordID,
            'label' => $d->DeviceID . ' - ' . ($d->BrandName ?? '') . ' ' . ($d->Model ?? '') . ' (SN: ' . ($d->SerialNumber ?? 'N/A') . ')'
        ];
    }
}
$borrow_devices_json = json_encode($devices_by_cat);

// 3. Data for Repair Request Form
$in_use_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");
if (!$in_use_status) $in_use_status = 2; // Default fallback

$in_use_devices = $wpdb->get_results($wpdb->prepare("
    SELECT d.DeviceID, d.OwnerID, c.CategoryName, b.BrandName, d.Model, d.SerialNumber
    FROM Devices d
    LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
    LEFT JOIN Brands b ON d.BrandID = b.BrandID
    WHERE d.StatusID = %d AND d.OwnerID IS NOT NULL
", $in_use_status));

$devices_by_owner = [];
if ($in_use_devices) {
    foreach ($in_use_devices as $d) {
        $devices_by_owner[$d->OwnerID][] = [
            'id' => $d->DeviceID,
            'label' => $d->DeviceID . ' - ' . ($d->CategoryName ?? '') . ' ' . ($d->BrandName ?? '') . ' ' . ($d->Model ?? '') . ' (SN: ' . ($d->SerialNumber ?? 'N/A') . ')'
        ];
    }
}
$repair_devices_json = json_encode($devices_by_owner);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service Requests Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Select2 Custom Modern Styling */
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            height: 48px;
            padding: 0.5rem 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #4f46e5;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1f2937;
            line-height: normal;
            padding-left: 0;
            padding-right: 2rem;
            width: 100%;
        }

        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #e5e7eb;
            color: #4b5563;
            cursor: not-allowed;
            border-color: #e5e7eb;
        }

        body {
            background-color: #f0f2f5;
            margin: 0;
            padding: 40px 15px;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .request-form-container {
            width: 100%;
            max-width: 600px;
            background: #ffffff;
            padding: 2.5rem 3rem 3rem 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* User Header Bar */
        .user-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .user-badge {
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .logout-link {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s ease;
        }

        .logout-link:hover {
            opacity: 0.8;
        }

        /* Tab Switcher Styling */
        .tab-switcher {
            display: flex;
            background-color: #f3f4f6;
            padding: 4px;
            border-radius: 14px;
            margin-bottom: 2rem;
            gap: 4px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            background: transparent;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: 'Inter', sans-serif;
        }

        .tab-btn.active {
            background: #ffffff;
            color: #111827;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
        }

        .form-header p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            color: #1f2937;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.2em 1.2em;
            padding-right: 2.5rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .form-control:disabled {
            background-color: #e5e7eb;
            color: #4b5563;
            cursor: not-allowed;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
            margin-top: 1rem;
        }

        .btn-submit.btn-repair {
            background: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%);
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>

    <div class="request-form-container">

        <!-- Logged-in User Bar -->
        <div class="user-bar">
            <span class="user-badge">👤 Employee: <strong><?= esc_html($logged_owner_name) ?></strong></span>
            <a href="portal-logout.php" class="logout-link">Log out 🚪</a>
        </div>

        <!-- Tab Navigation Switcher -->
        <div class="tab-switcher">
            <button type="button" class="tab-btn active" id="tabBorrowBtn" onclick="switchTab('borrow')">📦 Borrow Device</button>
            <button type="button" class="tab-btn" id="tabRepairBtn" onclick="switchTab('repair')">🛠️ Request Repair</button>
        </div>

        <!-- ================= BORROW DEVICE TAB ================= -->
        <div class="tab-content active" id="borrowFormTab">
            <div class="form-header">
                <h2>Borrow an IT Device</h2>
                <p>Select an available device from the inventory below.</p>
            </div>

            <form id="borrowRequestForm" method="POST">
                <input type="hidden" name="submit_request" value="1">
                <input type="hidden" name="OwnerID" value="<?= esc_attr($logged_owner_id) ?>">

                <div class="form-group">
                    <label for="BorrowOwnerID">Requester Name (Locked)</label>
                    <select id="BorrowOwnerID" class="form-control" disabled>
                        <?php foreach ($owners as $owner): ?>
                            <?php if ($owner->OwnerID == $logged_owner_id): ?>
                                <option value="<?= esc_attr($owner->OwnerID) ?>" selected>
                                    <?= esc_html($owner->Nickname) ?> (<?= esc_html($owner->DepartmentName ?: 'No Dept') ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="CategoryID">Device Category</label>
                    <select name="CategoryID" id="CategoryID" class="form-control" required onchange="filterBorrowDevices()">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= esc_attr($cat->CategoryID) ?>">
                                <?= esc_html($cat->CategoryName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="keyword-group" style="display: none;">
                    <label for="KeywordID">Keyword (For Accessories)</label>
                    <select name="KeywordID" id="KeywordID" class="form-control" onchange="filterDevicesByKeyword()">
                        <option value="">-- Select Keyword --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="RequestedDeviceID">Select Available Device</label>
                    <select name="RequestedDeviceID" id="RequestedDeviceID" class="form-control" required disabled>
                        <option value="">-- Please select a category first --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="BorrowReason">Reason / Justification</label>
                    <textarea name="Reason" id="BorrowReason" rows="3" class="form-control"
                        placeholder="E.g. Needed for a new project, old device is broken..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">Submit Borrow Request</button>
            </form>
        </div>

        <!-- ================= REPORT REPAIR TAB ================= -->
        <div class="tab-content" id="repairFormTab">
            <div class="form-header">
                <h2>Report a Device Repair</h2>
                <p>Select your assigned device and describe the issue.</p>
            </div>

            <form id="repairRequestForm" method="POST">
                <input type="hidden" name="ajax_submit" value="1">
                <input type="hidden" name="OwnerID" value="<?= esc_attr($logged_owner_id) ?>">

                <div class="form-group">
                    <label for="RepairOwnerID">Requester Name (Locked)</label>
                    <select id="RepairOwnerID" class="form-control" disabled>
                        <?php foreach ($owners as $owner): ?>
                            <?php if ($owner->OwnerID == $logged_owner_id): ?>
                                <option value="<?= esc_attr($owner->OwnerID) ?>" selected>
                                    <?= esc_html($owner->Nickname) ?> (<?= esc_html($owner->DepartmentName ?: 'No Dept') ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="RepairDeviceID">Select Device to Repair</label>
                    <select name="DeviceID" id="RepairDeviceID" class="form-control" required disabled>
                        <option value="">-- Loading your assigned devices... --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="RepairReason">Issue Details / Reason for repair</label>
                    <textarea name="Reason" id="RepairReason" rows="3" class="form-control"
                        placeholder="E.g. Screen is flickering, keyboard not working..." required></textarea>
                </div>

                <button type="submit" class="btn-submit btn-repair">Submit Repair Request</button>
            </form>
        </div>
    </div>

    <script>
        const loggedOwnerId = <?= json_encode($logged_owner_id) ?>;
        const devicesByCat = <?= $borrow_devices_json ?>;
        const keywordsData = <?= $keywords_json ?>;
        const devicesByOwner = <?= $repair_devices_json ?>;

        function syncSelect2(id) {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#' + id).trigger('change.select2');
            }
        }

        $(document).ready(function () {
            $('#BorrowOwnerID, #CategoryID, #KeywordID, #RequestedDeviceID, #RepairOwnerID, #RepairDeviceID').select2({
                width: '100%'
            });

            $('#CategoryID').on('change', filterBorrowDevices);
            $('#KeywordID').on('change', filterDevicesByKeyword);
            
            // Populate repair devices on load
            filterRepairDevices();
        });

        // 1. Tab Switching Function
        function switchTab(type) {
            const borrowBtn = document.getElementById('tabBorrowBtn');
            const repairBtn = document.getElementById('tabRepairBtn');
            const borrowTab = document.getElementById('borrowFormTab');
            const repairTab = document.getElementById('repairFormTab');

            if (type === 'repair') {
                borrowBtn.classList.remove('active');
                repairBtn.classList.add('active');
                borrowTab.classList.remove('active');
                repairTab.classList.add('active');
            } else {
                repairBtn.classList.remove('active');
                borrowBtn.classList.add('active');
                repairTab.classList.remove('active');
                borrowTab.classList.add('active');
            }

            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#BorrowOwnerID, #CategoryID, #KeywordID, #RequestedDeviceID, #RepairOwnerID, #RepairDeviceID').select2({
                    width: '100%'
                });
            }
        }

        // Auto-select tab based on URL query parameter ?type=repair
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('type') === 'repair') {
            switchTab('repair');
        }

        // 2. Borrow Form Logic
        function filterBorrowDevices() {
            const catSelect = document.getElementById('CategoryID');
            const catId = catSelect.value;
            const catName = catSelect.options[catSelect.selectedIndex] ? catSelect.options[catSelect.selectedIndex].text.trim() : '';
            const deviceSelect = document.getElementById('RequestedDeviceID');
            const keywordGroup = document.getElementById('keyword-group');
            const keywordSelect = document.getElementById('KeywordID');

            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>';
            keywordSelect.innerHTML = '<option value="">-- Select Keyword --</option>';

            if (!catId) {
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select a category first --</option>';
                keywordGroup.style.display = 'none';
                syncSelect2('RequestedDeviceID');
                syncSelect2('KeywordID');
                return;
            }

            if (catName === 'Accessories' || catId == '3') {
                keywordGroup.style.display = 'block';
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select a keyword first --</option>';

                const availableKeywords = new Set();
                if (devicesByCat[catId]) {
                    devicesByCat[catId].forEach(d => {
                        if (d.keyword_id) availableKeywords.add(d.keyword_id.toString());
                    });
                }

                let hasKeywords = false;
                keywordsData.forEach(k => {
                    if (availableKeywords.has(k.KeywordID.toString())) {
                        const opt = document.createElement('option');
                        opt.value = k.KeywordID;
                        opt.textContent = k.KeywordName;
                        keywordSelect.appendChild(opt);
                        hasKeywords = true;
                    }
                });

                if (!hasKeywords) {
                    keywordSelect.innerHTML = '<option value="">❌ No accessories available</option>';
                    keywordSelect.disabled = true;
                } else {
                    keywordSelect.disabled = false;
                }
                syncSelect2('KeywordID');
                syncSelect2('RequestedDeviceID');
            } else {
                keywordGroup.style.display = 'none';
                populateBorrowDevices(catId, null);
            }
        }

        function filterDevicesByKeyword() {
            const catId = document.getElementById('CategoryID').value;
            const keywordId = document.getElementById('KeywordID').value;
            populateBorrowDevices(catId, keywordId);
        }

        function populateBorrowDevices(catId, keywordId) {
            const deviceSelect = document.getElementById('RequestedDeviceID');
            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>';

            let filteredDevices = devicesByCat[catId] || [];

            if (keywordId) {
                filteredDevices = filteredDevices.filter(d => d.keyword_id == keywordId);
            }

            if (filteredDevices.length > 0) {
                deviceSelect.disabled = false;
                filteredDevices.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.label;
                    deviceSelect.appendChild(opt);
                });
            } else {
                deviceSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "❌ No available devices in this category/keyword";
                deviceSelect.appendChild(opt);
            }
            syncSelect2('RequestedDeviceID');
        }

        // 3. Repair Form Logic (Uses loggedOwnerId)
        function filterRepairDevices() {
            const deviceSelect = document.getElementById('RepairDeviceID');
            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>';

            const userDevices = devicesByOwner[loggedOwnerId] || [];

            if (userDevices.length > 0) {
                deviceSelect.disabled = false;
                userDevices.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.label;
                    deviceSelect.appendChild(opt);
                });
            } else {
                deviceSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "❌ You currently have no assigned devices";
                deviceSelect.appendChild(opt);
            }
            syncSelect2('RepairDeviceID');
        }

        // 4. AJAX Submission for Borrow Form
        document.getElementById('borrowRequestForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(this);
            formData.append('ajax_submit', '1');

            const requestSubmitUrl = '<?= site_url('/request-submit.php') ?>';
            fetch(requestSubmitUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Borrow Request Submitted!',
                            text: data.message,
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#ef4444'
                        });
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Borrow Request';
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Error Details: ' + error.message,
                        confirmButtonColor: '#ef4444'
                    });
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Borrow Request';
                });
        });

        // 5. AJAX Submission for Repair Form
        document.getElementById('repairRequestForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(this);
            formData.append('ajax_submit', '1');

            const repairSubmitUrl = '<?= site_url('/repair-submit.php') ?>';
            fetch(repairSubmitUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Repair Request Submitted!',
                            text: data.message,
                            confirmButtonColor: '#ef4444'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#ef4444'
                        });
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Repair Request';
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Error Details: ' + error.message,
                        confirmButtonColor: '#ef4444'
                    });
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Repair Request';
                });
        });
    </script>

</body>

</html>