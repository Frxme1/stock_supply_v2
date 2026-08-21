# Smart Auto-Complete & SQL-Driven Suggestions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build and integrate a global, high-performance Smart Auto-Complete and Auto-Fill system across all major forms in Stock Supply, dynamically pulling real historical values and frequencies from SQL database tables.

**Architecture:** A unified modular architecture consisting of backend WordPress AJAX endpoints (`controller/smart_autocomplete_controller.php`) providing SQL-mined suggestions and model profiles, coupled with a lightweight, accessible Vanilla JavaScript UI component (`js/smart_autocomplete.js` + `css/smart_autocomplete.css`) with Material 3 styling and auto-fill visual pulse cues.

**Tech Stack:** PHP (WordPress `$wpdb`), Vanilla JavaScript (ES6+), Vanilla CSS (Glassmorphism & Material 3), SweetAlert2.

**Spec:** `docs/superpowers/specs/2026-08-21-smart-autocomplete-suggestions-design.md`

## Global Constraints
- All data suggestions MUST be dynamically queried from real SQL records in the database (`Devices`, `Brands`, `Categories`, `Keywords`, `Owners`, `ViewOwnersWithNames`, `Maintenance`), without hardcoded static lists.
- Queries must use `$wpdb->prepare` to prevent SQL injection.
- UI must follow Material 3 / Glassmorphism styles and be 100% responsive on both mobile and desktop.
- Instant Auto-Fill must provide clear visual feedback (green/cyan subtle glow pulse) on affected inputs while allowing immediate user edits.

---

### Task 1: Backend Controller & AJAX Suggestion Endpoints

**Files:**
- Create: `wp-content/themes/astra-child/controller/smart_autocomplete_controller.php`
- Modify: `wp-content/themes/astra-child/functions.php`

**Interfaces:**
- Produces: 
  - `wp_ajax_stock_get_smart_suggestions`: JSON response `{ success: true, data: [ { value: string, label: string, badge: string, count: number } ] }`
  - `wp_ajax_stock_get_model_profile`: JSON response `{ success: true, data: { CategoryID: int, BrandID: int, BrandName: string, KeywordID: int } }`
  - `wp_ajax_stock_check_serial_duplicate`: JSON response `{ success: true, is_duplicate: bool, existing_device_id: string }`

- [ ] **Step 1: Create `smart_autocomplete_controller.php` with query handlers**

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class StockSmartAutocompleteController {
    public static function init() {
        add_action('wp_ajax_stock_get_smart_suggestions', [__CLASS__, 'handle_suggestions']);
        add_action('wp_ajax_stock_get_model_profile', [__CLASS__, 'handle_model_profile']);
        add_action('wp_ajax_stock_check_serial_duplicate', [__CLASS__, 'handle_serial_check']);
    }

    public static function handle_suggestions() {
        global $wpdb;
        $field_type = sanitize_text_field($_REQUEST['field_type'] ?? '');
        $term = trim(sanitize_text_field($_REQUEST['term'] ?? ''));
        $context = isset($_REQUEST['context']) ? json_decode(stripslashes($_REQUEST['context']), true) : [];

        $results = [];

        switch ($field_type) {
            case 'model':
                $where = ["Model IS NOT NULL", "Model != ''"];
                $params = [];
                if (!empty($term)) {
                    $where[] = "Model LIKE %s";
                    $params[] = '%' . $wpdb->esc_like($term) . '%';
                }
                if (!empty($context['brand_id'])) {
                    $where[] = "BrandID = %d";
                    $params[] = intval($context['brand_id']);
                }
                if (!empty($context['category_id'])) {
                    $where[] = "CategoryID = %d";
                    $params[] = intval($context['category_id']);
                }
                $where_sql = implode(' AND ', $where);
                $query = "SELECT Model AS value, Model AS label, COUNT(*) AS cnt FROM Devices WHERE $where_sql GROUP BY Model ORDER BY cnt DESC, Model ASC LIMIT 10";
                $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);
                foreach ($rows as $r) {
                    $results[] = [
                        'value' => $r->value,
                        'label' => $r->label,
                        'badge' => $r->cnt > 1 ? "{$r->cnt} ชิ้นในระบบ" : 'เคยใช้',
                        'count' => intval($r->cnt)
                    ];
                }
                break;

            case 'brand':
                $where = ["BrandName IS NOT NULL", "BrandName != ''", "LOWER(BrandName) != 'other'"];
                $params = [];
                if (!empty($term)) {
                    $where[] = "BrandName LIKE %s";
                    $params[] = '%' . $wpdb->esc_like($term) . '%';
                }
                $where_sql = implode(' AND ', $where);
                $query = "SELECT BrandID AS id, BrandName AS value, BrandName AS label FROM Brands WHERE $where_sql ORDER BY BrandName ASC LIMIT 15";
                $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);
                foreach ($rows as $r) {
                    $results[] = [
                        'id' => $r->id,
                        'value' => $r->value,
                        'label' => $r->label,
                        'badge' => 'ยี่ห้อ'
                    ];
                }
                break;

            case 'department':
                $where = ["Department IS NOT NULL", "Department != ''", "Department != '-'"];
                $params = [];
                if (!empty($term)) {
                    $where[] = "Department LIKE %s";
                    $params[] = '%' . $wpdb->esc_like($term) . '%';
                }
                $where_sql = implode(' AND ', $where);
                $query = "SELECT Department AS value, Department AS label, COUNT(*) as cnt FROM ViewOwnersWithNames WHERE $where_sql GROUP BY Department ORDER BY cnt DESC LIMIT 10";
                $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);
                foreach ($rows as $r) {
                    $results[] = [
                        'value' => $r->value,
                        'label' => $r->label,
                        'badge' => "{$r->cnt} คน",
                        'count' => intval($r->cnt)
                    ];
                }
                break;

            case 'position':
                $where = ["Position IS NOT NULL", "Position != ''", "Position != '-'"];
                $params = [];
                if (!empty($term)) {
                    $where[] = "Position LIKE %s";
                    $params[] = '%' . $wpdb->esc_like($term) . '%';
                }
                $where_sql = implode(' AND ', $where);
                $query = "SELECT Position AS value, Position AS label, COUNT(*) as cnt FROM ViewOwnersWithNames WHERE $where_sql GROUP BY Position ORDER BY cnt DESC LIMIT 10";
                $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);
                foreach ($rows as $r) {
                    $results[] = [
                        'value' => $r->value,
                        'label' => $r->label,
                        'badge' => "{$r->cnt} คน",
                        'count' => intval($r->cnt)
                    ];
                }
                break;

            case 'maintenance_issue':
                $where = ["Issue IS NOT NULL", "Issue != ''"];
                $params = [];
                if (!empty($term)) {
                    $where[] = "Issue LIKE %s";
                    $params[] = '%' . $wpdb->esc_like($term) . '%';
                }
                $where_sql = implode(' AND ', $where);
                $query = "SELECT Issue AS value, Issue AS label, COUNT(*) as cnt FROM Maintenance WHERE $where_sql GROUP BY Issue ORDER BY cnt DESC LIMIT 10";
                $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);
                foreach ($rows as $r) {
                    $results[] = [
                        'value' => $r->value,
                        'label' => $r->label,
                        'badge' => "{$r->cnt} ครั้ง",
                        'count' => intval($r->cnt)
                    ];
                }
                break;

            case 'employee_lookup':
                $where = ["(Fullname LIKE %s OR Nickname LIKE %s OR OwnerID LIKE %s OR Email LIKE %s)"];
                $like = '%' . $wpdb->esc_like($term) . '%';
                $query = $wpdb->prepare(
                    "SELECT OwnerID, Fullname, Nickname, Department, Position, Email, Phone 
                     FROM ViewOwnersWithNames 
                     WHERE (Fullname LIKE %s OR Nickname LIKE %s OR OwnerID LIKE %s OR Email LIKE %s)
                     ORDER BY Fullname ASC LIMIT 10",
                    $like, $like, $like, $like
                );
                $rows = $wpdb->get_results($query);
                foreach ($rows as $r) {
                    $results[] = [
                        'id' => $r->OwnerID,
                        'value' => $r->Nickname ? "{$r->Fullname} ({$r->Nickname})" : $r->Fullname,
                        'label' => $r->Fullname . ($r->Nickname ? " ({$r->Nickname})" : "") . " — " . ($r->Department ?? 'N/A'),
                        'badge' => $r->Position ?? 'Employee',
                        'data' => $r
                    ];
                }
                break;
        }

        wp_send_json_success($results);
    }

    public static function handle_model_profile() {
        global $wpdb;
        $model = trim(sanitize_text_field($_REQUEST['model_name'] ?? ''));
        if (empty($model)) {
            wp_send_json_error('Model name required');
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT d.CategoryID, d.BrandID, b.BrandName, d.KeywordID, d.Model
             FROM Devices d
             LEFT JOIN Brands b ON d.BrandID = b.BrandID
             WHERE d.Model = %s
             ORDER BY d.CreatedAt DESC
             LIMIT 1",
            $model
        ));

        if ($row) {
            wp_send_json_success($row);
        } else {
            wp_send_json_error('No profile found');
        }
    }

    public static function handle_serial_check() {
        global $wpdb;
        $serial = trim(sanitize_text_field($_REQUEST['serial'] ?? ''));
        $current_device_id = trim(sanitize_text_field($_REQUEST['current_device_id'] ?? ''));

        if (empty($serial)) {
            wp_send_json_success(['is_duplicate' => false]);
        }

        $query = "SELECT DeviceID FROM Devices WHERE SerialNumber = %s";
        $params = [$serial];
        if (!empty($current_device_id)) {
            $query .= " AND DeviceID != %s";
            $params[] = $current_device_id;
        }

        $existing = $wpdb->get_var($wpdb->prepare($query, ...$params));
        wp_send_json_success([
            'is_duplicate' => !empty($existing),
            'existing_device_id' => $existing ?? ''
        ]);
    }
}

StockSmartAutocompleteController::init();
```

- [ ] **Step 2: Include controller and enqueue assets in `functions.php`**
- [ ] **Step 3: Test AJAX endpoints using curl/PowerShell or browser subagent**

---

### Task 2: Frontend Smart Autocomplete Component (JS & CSS)

**Files:**
- Create: `wp-content/themes/astra-child/js/smart_autocomplete.js`
- Create: `wp-content/themes/astra-child/css/smart_autocomplete.css`
- Modify: `wp-content/themes/astra-child/functions.php` (enqueue script/style)

**Interfaces:**
- Produces: `window.initSmartAutocomplete(inputElement, options)`
  - Options: `{ fieldType, contextGetter, onSelect, enableAutoFillGlow: true }`
  - Helpers: `window.flashAutoFillGlow(element)`

- [ ] **Step 1: Create `smart_autocomplete.css` with Material 3 styling and subtle glow keyframe**
- [ ] **Step 2: Create `smart_autocomplete.js` with debounce, keyboard navigation, popover positioning, and selection dispatching**
- [ ] **Step 3: Register and enqueue scripts in WordPress `functions.php`**

---

### Task 3: Integration with Add/Edit Device Form

**Files:**
- Modify: `wp-content/themes/astra-child/model/device/device_form_add.php`
- Modify: `wp-content/themes/astra-child/model/device/edit-device.php`

- [ ] **Step 1: Attach smart autocomplete to Model input in `device_form_add.php` with Brand & Category context**
- [ ] **Step 2: Implement instant Auto-Fill logic when a Model is selected (auto-fills Brand and Keyword with visual glow pulse)**
- [ ] **Step 3: Attach live duplicate check on Serial Number input with inline warning badge**
- [ ] **Step 4: Mirror changes to `edit-device.php`**

---

### Task 4: Integration with Receive/Issue Device Form

**Files:**
- Modify: `wp-content/themes/astra-child/model/device/receive-device.php`

- [ ] **Step 1: Attach smart autocomplete to Employee / Receiver search field**
- [ ] **Step 2: Implement auto-population of Department, Position, Email, and Phone upon Employee selection**
- [ ] **Step 3: Test device assignment flow with smart autocomplete**

---

### Task 5: Integration with Maintenance & Employee Forms

**Files:**
- Modify: `wp-content/themes/astra-child/view/formMaintenance.php`
- Modify: `wp-content/themes/astra-child/model/employee/form_add_employee.php`
- Modify: `wp-content/themes/astra-child/model/employee/form_edit_employee.php`

- [ ] **Step 1: Attach smart suggestions to Issue / Symptom Description in `formMaintenance.php`**
- [ ] **Step 2: Attach Department and Position smart suggestions in `form_add_employee.php` and `form_edit_employee.php`**

---

### Task 6: End-to-End Verification

- [ ] **Step 1: Verify all AJAX endpoints respond with status 200 and expected SQL-mined JSON data**
- [ ] **Step 2: Verify keyboard navigation (Up, Down, Enter, Esc) works seamlessly on all attached inputs**
- [ ] **Step 3: Verify visual auto-fill pulse effect triggers correctly and allows manual edits**
- [ ] **Step 4: Verify mobile responsiveness and touch interactions on mobile viewport**
