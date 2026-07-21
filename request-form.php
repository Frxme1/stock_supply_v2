<?php
ob_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress core to access the database ($wpdb) and functions
require_once(dirname(__FILE__) . '/wp-load.php');
global $wpdb;

// Fetch active owners
$owners = $wpdb->get_results("
    SELECT o.OwnerID, o.Nickname, d.DepartmentName 
    FROM Owners o
    LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
    WHERE o.StatusID = 1
    ORDER BY o.Nickname ASC
");

// Fetch categories
$categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName ASC");

// Fetch keywords
$keywords = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords ORDER BY KeywordName ASC");
$keywords_json = json_encode($keywords);

// Fetch available devices
$available_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
$available_devices = $wpdb->get_results($wpdb->prepare("
    SELECT d.DeviceID, d.CategoryID, d.KeywordID, b.BrandName, d.Model, d.SerialNumber
    FROM Devices d
    LEFT JOIN Brands b ON d.BrandID = b.BrandID
    WHERE d.StatusID = %d
", $available_status));

// Group devices by category for JS
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
$devices_json = json_encode($devices_by_cat);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow IT Device</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
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
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h2 {
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
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
            color: #9ca3af;
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

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .site-footer {
            text-align: center;
            margin-top: 2rem;
            color: #9ca3af;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>

    <div class="request-form-container">
        <div class="form-header">
            <h2>Borrow an IT Device</h2>
            <p>Select an available device from the inventory below.</p>
        </div>

        <form id="deviceRequestForm" method="POST" action="">
            <input type="hidden" name="submit_request" value="1">

            <div class="form-group">
                <label for="OwnerID">Requester Name</label>
                <select name="OwnerID" id="OwnerID" class="form-control" required>
                    <option value="">-- Select Your Name --</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= esc_attr($owner->OwnerID) ?>">
                            <?= esc_html($owner->Nickname) ?> (<?= esc_html($owner->DepartmentName ?: 'No Dept') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="CategoryID">Device Category</label>
                <select name="CategoryID" id="CategoryID" class="form-control" required onchange="filterDevices()">
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
                <label for="Reason">Reason / Justification</label>
                <textarea name="Reason" id="Reason" rows="3" class="form-control"
                    placeholder="E.g. Needed for a new project, old device is broken..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">Submit Request</button>
        </form>


    </div>

    <script>
        // Data injected from PHP
        const devicesByCat = <?= $devices_json ?>;
        const keywordsData = <?= $keywords_json ?>;

        function filterDevices() {
            const catSelect = document.getElementById('CategoryID');
            const catId = catSelect.value;
            const catName = catSelect.options[catSelect.selectedIndex].text.trim();
            const deviceSelect = document.getElementById('RequestedDeviceID');
            const keywordGroup = document.getElementById('keyword-group');
            const keywordSelect = document.getElementById('KeywordID');

            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>';
            keywordSelect.innerHTML = '<option value="">-- Select Keyword --</option>';

            if (!catId) {
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select a category first --</option>';
                keywordGroup.style.display = 'none';
                return;
            }

            // If Accessories (ID 3 or name matches), show keyword dropdown
            if (catName === 'Accessories' || catId == '3') {
                keywordGroup.style.display = 'block';
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select a keyword first --</option>';

                // Populate Keywords based on available devices in this category
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
            } else {
                // Not accessories, just populate devices normally
                keywordGroup.style.display = 'none';
                populateDevices(catId, null);
            }
        }

        function filterDevicesByKeyword() {
            const catId = document.getElementById('CategoryID').value;
            const keywordId = document.getElementById('KeywordID').value;
            populateDevices(catId, keywordId);
        }

        function populateDevices(catId, keywordId) {
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
        }

        // Handle form submission via AJAX
        document.getElementById('deviceRequestForm').addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent browser from redirecting

            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(this);
            formData.append('ajax_submit', '1');

            fetch('request-submit.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Submitted!',
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
                        submitBtn.textContent = 'Submit Request';
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
                    submitBtn.textContent = 'Submit Request';
                });
        });
    </script>

</body>

</html>