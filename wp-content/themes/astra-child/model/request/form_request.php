<?php
function device_request_form() {
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

    // Fetch available devices
    $available_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
    $available_devices = $wpdb->get_results($wpdb->prepare("
        SELECT d.DeviceID, d.CategoryID, b.BrandName, d.Model, d.SerialNumber
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
                'label' => $d->DeviceID . ' - ' . ($d->BrandName ?? '') . ' ' . ($d->Model ?? '') . ' (SN: ' . ($d->SerialNumber ?? 'N/A') . ')'
            ];
        }
    }
    $devices_json = json_encode($devices_by_cat);

    ob_start();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
        $owner_id = intval($_POST['OwnerID']);
        $category_id = intval($_POST['CategoryID']);
        $requested_device_id = sanitize_text_field($_POST['RequestedDeviceID']);
        $reason = sanitize_textarea_field($_POST['Reason']);
        
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        
        if ($owner_id && $category_id && !empty($requested_device_id) && !empty($reason)) {
            $inserted = $wpdb->insert(
                'Device_Requests',
                [
                    'OwnerID' => $owner_id,
                    'CategoryID' => $category_id,
                    'Reason' => $reason,
                    'Status' => 'Pending',
                    'AssignedDeviceID' => $requested_device_id, // Store requested device here
                    'RequestDate' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $request_id = $wpdb->insert_id;
                
                // Send email notification to employee
                if (function_exists('stock_supply_send_email')) {
                    stock_supply_send_email('RequestSubmitted', $request_id, $owner_id, "Device: $requested_device_id - $reason");
                }

                echo "<script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Submitted!',
                            text: 'Your request for device " . esc_js($requested_device_id) . " has been submitted successfully.',
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.href = window.location.pathname;
                        });
                    }, 100);
                </script>";
            } else {
                $err = $wpdb->last_error;
                echo "<script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Database Error',
                            text: " . json_encode($err) . ",
                            confirmButtonColor: '#ef4444'
                        });
                    }, 100);
                </script>";
            }
        } else {
            echo "<script>
                setTimeout(function() {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in all fields and select a specific device. If no devices are available, you cannot submit.',
                        confirmButtonColor: '#f59e0b'
                    });
                }, 100);
            </script>";
        }
    }
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .request-form-container {
            max-width: 650px;
            margin: 40px auto;
            background: #ffffff;
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid #f3f4f6;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            font-family: 'Inter', sans-serif;
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
            appearance: none;
        }
        select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
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
        .alert-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .error-message { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .warning-message { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
    </style>

    <div class="request-form-container">
        <div class="form-header">
            <h2>Borrow an IT Device</h2>
            <p>Select an available device from the inventory below.</p>
        </div>
        <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
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

            <div class="form-group">
                <label for="RequestedDeviceID">Select Available Device</label>
                <select name="RequestedDeviceID" id="RequestedDeviceID" class="form-control" required disabled>
                    <option value="">-- Please select a category first --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Reason">Reason / Justification</label>
                <textarea name="Reason" id="Reason" rows="3" class="form-control" placeholder="E.g. Old laptop is broken, needed for new project..." required></textarea>
            </div>

            <button type="submit" name="submit_request" class="btn-submit">Submit Request</button>
        </form>
    </div>

    <script>
        const devicesByCat = <?= $devices_json ?>;

        function filterDevices() {
            const catId = document.getElementById('CategoryID').value;
            const deviceSelect = document.getElementById('RequestedDeviceID');
            
            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>';
            
            if (!catId) {
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select a category first --</option>';
                return;
            }

            if (devicesByCat[catId] && devicesByCat[catId].length > 0) {
                deviceSelect.disabled = false;
                devicesByCat[catId].forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.label;
                    deviceSelect.appendChild(opt);
                });
            } else {
                deviceSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "❌ No available devices in this category";
                deviceSelect.appendChild(opt);
            }
        }
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('device_request_form', 'device_request_form');
