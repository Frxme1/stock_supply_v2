<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(dirname(__FILE__) . '/wp-load.php');
global $wpdb;

// If already logged in, redirect to portal
if (isset($_SESSION['portal_owner_id']) && !empty($_SESSION['portal_owner_id'])) {
    header('Location: request-form.php');
    exit;
}

$error_msg = '';

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = intval($_POST['owner_id'] ?? 0);
    $login_input = trim(sanitize_text_field($_POST['login_input'] ?? ''));

    $owner = null;

    if ($owner_id > 0) {
        $owner = $wpdb->get_row($wpdb->prepare("SELECT OwnerID, Nickname, FirstName, LastName, Email FROM Owners WHERE OwnerID = %d AND StatusID = 1", $owner_id));
    } elseif (!empty($login_input)) {
        $owner = $wpdb->get_row($wpdb->prepare("
            SELECT OwnerID, Nickname, FirstName, LastName, Email 
            FROM Owners 
            WHERE (Email = %s OR Nickname = %s OR FirstName = %s) AND StatusID = 1
        ", $login_input, $login_input, $login_input));
    }

    if ($owner) {
        $_SESSION['portal_owner_id'] = $owner->OwnerID;
        $_SESSION['portal_owner_name'] = !empty($owner->FirstName) ? $owner->FirstName . ' ' . $owner->LastName : $owner->Nickname;
        $_SESSION['portal_owner_email'] = $owner->Email ?? '';
        
        header('Location: request-form.php');
        exit;
    } else {
        $error_msg = 'Invalid employee account. Please select your name or enter a valid email.';
    }
}

// Fetch active owners for dropdown
$owners = $wpdb->get_results("
    SELECT o.OwnerID, o.Nickname, d.DepartmentName 
    FROM Owners o
    LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
    WHERE o.StatusID = 1
    ORDER BY o.Nickname ASC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal Login - IT Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 50%, #06b6d4 100%);
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            color: white;
            font-size: 28px;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 1.65rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.5rem 0;
        }

        .login-header p {
            color: #6b7280;
            font-size: 0.95rem;
            margin: 0;
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
            border-radius: 12px;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        .btn-login {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            height: 48px;
            display: flex;
            align-items: center;
            padding-left: 6px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px;
            right: 10px;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        .divider span {
            padding: 0 10px;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo-icon">💻</div>
        <div class="login-header">
            <h1>Employee IT Portal</h1>
            <p>Select your profile to access IT services</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: '<?= esc_js($error_msg) ?>',
                        confirmButtonColor: '#4f46e5'
                    });
                });
            </script>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="owner_id">Select Your Name / Profile</label>
                <select name="owner_id" id="owner_id" class="form-control">
                    <option value="">-- Search & Select Your Name --</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= esc_attr($owner->OwnerID) ?>">
                            <?= esc_html($owner->Nickname) ?> (<?= esc_html($owner->DepartmentName ?: 'No Dept') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="divider"><span>OR</span></div>

            <div class="form-group">
                <label for="login_input">Enter Email or Nickname</label>
                <input type="text" name="login_input" id="login_input" class="form-control" placeholder="e.g. user@domain.com or Nickname">
            </div>

            <button type="submit" class="btn-login">Log In to Portal</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#owner_id').select2({
                placeholder: "-- Search & Select Your Name --",
                allowClear: true,
                width: '100%'
            });
        });
    </script>

</body>

</html>
