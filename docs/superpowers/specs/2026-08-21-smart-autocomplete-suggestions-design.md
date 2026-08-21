# Smart Auto-Complete & SQL-Driven Suggestions System Design

**Date:** 2026-08-21  
**Project:** Stock Supply  
**Type:** Architectural Specification  
**Status:** Approved by User  

---

## 1. Overview & Objective

The objective of this project is to implement a comprehensive, global **Smart Auto-Complete & Intelligent Auto-Fill** system across all major forms in the Stock Supply management portal. 

All suggestions, frequencies, and auto-filled data profiles will be driven **100% dynamically from real database records (MySQL/MariaDB via `$wpdb`)**, ensuring that the system reflects company operational data accurately without hardcoded static lists.

---

## 2. Target Forms & Field Mapping

| Form Area | Target Input Fields | SQL Data Source (`$wpdb`) | Behavior on Select |
| :--- | :--- | :--- | :--- |
| **Add / Edit Device** (`device_form_add.php`, `edit-device.php`) | **Brand** | `Brands` (`SELECT BrandID, BrandName ...`) | Filters Model & Keyword choices |
| | **Model** | `Devices` (`SELECT DISTINCT Model, COUNT(*) ... GROUP BY Model ORDER BY COUNT(*) DESC`) | **Instant Auto-Fill:** Auto-populates associated Keyword, Brand, and previous parameters with visual glow feedback |
| | **Serial Number** | `Devices` | Real-time live duplicate check with inline warning |
| **Receive / Issue Device** (`receive-device.php`) | **Employee / Owner Lookup** | `ViewOwnersWithNames` / `Owners` | **Instant Auto-Fill:** Fills Department, Position, Phone, Email |
| | **Device ID / Serial Lookup** | `DevicesWithNames` | **Instant Auto-Fill:** Fills Model, Brand, Category, Current Status, Owner |
| **Maintenance & Repair** (`formMaintenance.php`, `maintenance.php`) | **Device ID** | `DevicesWithNames` | **Instant Auto-Fill:** Fills device specs and current owner |
| | **Issue / Symptom Description** | `Maintenance` (`SELECT DISTINCT Issue, COUNT(*) ... GROUP BY Issue ORDER BY COUNT(*) DESC`) | Displays frequent past repair issues (e.g. "เปิดไม่ติด", "จอฟ้า", "แบตเตอรี่เสื่อม") |
| **Employee Form** (`form_add_employee.php`, `form_edit_employee.php`) | **Department** | `ViewOwnersWithNames` (`SELECT DISTINCT Department ...`) | Suggests existing department names |
| | **Position** | `ViewOwnersWithNames` (`SELECT DISTINCT Position ...`) | Suggests existing position titles |

---

## 3. Frontend Architecture

### 3.1. Shared JS & CSS Components
- **`js/smart_autocomplete.js`**: Reusable Vanilla JavaScript class / module supporting:
  - Floating suggestions panel anchored to any text/search input.
  - Keyboard navigation (`ArrowUp`, `ArrowDown`, `Enter`, `Escape`).
  - 250ms debounced AJAX calls with abort controller for fast typing.
  - Frequency / Badge indicators (`บ่อยที่สุด`, `ประวัติเดิม`).
  - Customizable item click handlers and auto-fill dispatchers.
  - Toggle configuration allowing switching between *Instant Auto-fill (Option 1)* and *Field-only suggestions (Option 3)*.
- **`css/smart_autocomplete.css`**:
  - Glassmorphism dropdown container adhering to Material 3 Design.
  - Highlighted matching substrings.
  - Green/Cyan subtle glow pulse animation on fields when auto-filled.
  - Dark mode and mobile viewport touch optimization.

---

## 4. Backend Architecture & AJAX Endpoints

### 4.1. Suggestions Endpoint: `wp_ajax_stock_get_smart_suggestions`
- **Request Parameters:**
  - `field_type`: String (`model`, `brand`, `department`, `position`, `maintenance_issue`, `employee_lookup`, `device_lookup`)
  - `term`: String (current input search term)
  - `context`: JSON string (optional filters like `{ category_id: 1, brand_id: 2 }`)
- **Query Optimization:**
  - Prepared statements with `$wpdb->prepare`
  - Grouping and ordering by usage frequency (`ORDER BY COUNT(*) DESC`)
  - Output standard JSON `{ success: true, data: [ { label: '...', value: '...', meta: '...' } ] }`

### 4.2. Model Profile / Auto-Fill Endpoint: `wp_ajax_stock_get_model_profile`
- **Request Parameters:**
  - `model_name`: String
  - `brand_id`: Integer (optional)
- **Response:**
  - JSON payload containing the most recent or frequent attributes associated with that model (`CategoryID`, `BrandID`, `KeywordID`, etc.) for seamless one-click form completion.

---

## 5. Non-Functional Requirements & Security
- **Security:** Nonce verification via `wp_create_nonce('stock_smart_suggest_nonce')` and strict sanitization with `sanitize_text_field()`.
- **Performance:** Caching of heavy distinct queries using WordPress Transients API (5-minute TTL) with cache invalidation on save.
- **Graceful Fallback:** Native HTML inputs remain fully functional if JavaScript is disabled or network fails.
