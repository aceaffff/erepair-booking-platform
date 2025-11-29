    <?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function redirect_to_login() {
    header('Location: ../auth/index.php');
    exit;
}

// Read token from cookie or GET and validate
$token = null;
if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
    $token = InputValidator::validateToken($_COOKIE['auth_token']);
} elseif (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = InputValidator::validateToken(trim($_GET['token']));
    // Persist valid token in cookie for subsequent requests
    if ($token !== null) {
        setcookie('auth_token', $token, time() + 24 * 60 * 60, '/', '', false, true);
    }
}

if ($token === null) {
    redirect_to_login();
}

// Verify token and fetch user
$database = new Database();
$db = $database->getConnection();
if (!$db) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Service Unavailable</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">';
    echo '<div class="container py-5"><div class="alert alert-danger"><strong>Database connection failed.</strong> Please ensure MySQL is running and configured.</div>';
    echo '<ul class="small text-muted mb-0"><li>Start MySQL in XAMPP (MySQL → Start)</li><li>Verify credentials in backend/config/database.php</li><li>Confirm database exists: repair_booking</li></ul></div></body></html>';
    exit;
}

try {
    $stmt = $db->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.address, u.role, u.email_verified, u.created_at, u.avatar_url
        FROM users u
        INNER JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    // Backwards compatibility when avatar_url column is not yet added
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.address, u.role, u.email_verified, u.created_at
        FROM users u
        INNER JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) { $user['avatar_url'] = null; }
}

if (!$user || $user['role'] !== 'customer') {
    redirect_to_login();
}

// Normalize avatar URL to work from the customer/ directory
$avatarCandidate = $user['avatar_url'] ?? '';
if ($avatarCandidate && !preg_match('/^https?:\/\//', $avatarCandidate) && strpos($avatarCandidate, '/') !== 0) {
    // Stored as relative to frontend root (e.g., uploads/avatars/..)
    $normalizedAvatarUrl = '../' . ltrim($avatarCandidate, '/');
} else {
    $normalizedAvatarUrl = $avatarCandidate;
}
if ($normalizedAvatarUrl === '') {
    $normalizedAvatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff';
}

// Simple helper for HTML escaping
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERepair - Book and manage your electronics repair services">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ERepair">
    <title>Customer Dashboard - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="../../backend/api/favicon.php">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-generator.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-styles.css" rel="stylesheet">
    <script src="../assets/js/erepair-common.js" defer></script>
    <style>
        .nav-btn { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            color: rgba(255,255,255,.85); 
            position: relative;
            border-radius: 12px;
            margin-bottom: 4px;
        }
        .nav-btn::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 0;
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
            border-radius: 0 4px 4px 0;
            transition: height 0.3s ease;
        }
        .nav-btn:hover { 
            background: linear-gradient(90deg, rgba(99,102,241,.12), rgba(99,102,241,.06)); 
            color: #fff; 
            transform: translateX(4px);
            padding-left: 1.5rem;
        }
        .nav-btn:hover::before {
            height: 60%;
        }
        .nav-btn.active { 
            background: linear-gradient(90deg, rgba(99,102,241,.25), rgba(99,102,241,.15)); 
            color: #a5b4fc; 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(99,102,241,.2);
            border-left: 3px solid #6366f1;
            padding-left: 1.5rem;
        }
        .nav-btn.active::before {
            height: 70%;
            width: 4px;
        }
        .nav-btn i { 
            width: 22px; 
            text-align: center; 
            opacity: .9;
            transition: all 0.3s ease;
        }
        .nav-btn:hover i {
            transform: scale(1.1);
            opacity: 1;
        }
        .nav-btn.active i { 
            color: #a5b4fc; 
            transform: scale(1.15);
        }
        /* Tab navigation buttons - styled like sidebar nav but with light background */
        .tab-nav-btn { 
            transition: background-color .2s ease, color .2s ease, transform .08s ease; 
            color: #495057; 
            background-color: rgba(248, 249, 250, 0.8);
            border: 1px solid rgba(0,0,0,0.1);
        }
        .tab-nav-btn:hover { 
            background-color: rgba(99,102,241,.08); 
            color: #6366f1; 
            transform: translateY(-1px);
            border-color: rgba(99,102,241,.2);
        }
        .tab-nav-btn.active { 
            background: rgba(99,102,241,.12); 
            color: #6366f1; 
            font-weight: 600; 
            box-shadow: 0 2px 4px rgba(99,102,241,.15);
            border-color: rgba(99,102,241,.3);
        }
        .tab-nav-btn i { width: 22px; text-align: center; opacity: .95; }
        .tab-nav-btn.active i { color: #6366f1; }
        .logout-btn { 
            color: #f87171; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            margin-top: 8px;
            position: relative;
        }
        .logout-btn::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 0;
            background: linear-gradient(180deg, #ef4444, #f87171);
            border-radius: 0 4px 4px 0;
            transition: height 0.3s ease;
        }
        .logout-btn:hover { 
            background: linear-gradient(90deg, rgba(239,68,68,.15), rgba(239,68,68,.08)); 
            color: #fff; 
            transform: translateX(4px);
            padding-left: 1.5rem;
        }
        .logout-btn:hover::before {
            height: 60%;
        }
        .logout-btn i {
            transition: transform 0.3s ease;
        }
        .logout-btn:hover i {
            transform: translateX(-2px);
        }
        .brand-wrap { 
            background: linear-gradient(180deg, rgba(99,102,241,.08), rgba(99,102,241,.03)); 
            border-bottom: 1px solid rgba(99,102,241,.15); 
            position: relative;
            overflow: hidden;
        }
        .brand-wrap::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(99,102,241,.5), transparent);
        }
        .bg-gradient-primary { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%); }
        .logo-container { transition: transform 0.3s ease; }
        .logo-container:hover { transform: scale(1.05); }
        .card-hover { transition: box-shadow .2s ease, transform .2s ease; }
        .card-hover:hover { box-shadow: 0 10px 25px rgba(0,0,0,.08); transform: translateY(-2px); }
        /* Inline form styles for change password */
        .change-password-form {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }
        /* Compact calendar styles */
        .calendar-grid .cal-day { padding: 6px !important; min-height: 60px; }
        .calendar-grid .cal-day .small { font-size: 11px; }
        .calendar-grid .cal-day.is-today { box-shadow: inset 0 0 0 2px rgba(99,102,241,.4); }
        /* True calendar grid (7 columns) */
        .calendar-grid-7 { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
        .weekday-cell { font-size: 12px; text-transform: uppercase; color: #6b7280; text-align: center; padding: 4px 0; }
        .cal-cell { cursor: pointer; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; padding: 8px; min-height: 62px; text-align: center; transition: background .15s ease, transform .05s ease, border-color .15s ease; }
        .cal-cell:hover { background: #eef2ff; }
        .cal-cell.blank { background: transparent; border-color: transparent; cursor: default; }
        .cal-cell.selected { border-color: #4f46e5; box-shadow: inset 0 0 0 2px #4f46e5; }
        .cal-cell.is-today { box-shadow: inset 0 0 0 2px rgba(99,102,241,.35); }
        .cal-cell.is-past { opacity: .45; background: #f3f4f6; cursor: not-allowed; }
        /* Ensure buttons are always visible */
        .btn-group-vertical .btn {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-bottom: 0.25rem;
        }
        /* Prevent flickering on tab/content switches */
        [x-cloak] { display: none !important; }
        
        /* Ensure Alpine.js x-show switches instantly without transitions */
        .tab-content > div[x-show] {
            transition: none !important;
        }
        
        /* Consistent Card Styling */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out, transform 0.15s ease-in-out;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Consistent Badge Styling */
        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            font-weight: 600;
        }
        
        /* Modern Map Styles */
        .modern-map-container {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .modern-map-container:hover {
            box-shadow: 0 15px 50px rgba(0,0,0,0.15) !important;
        }
        
        /* Modern Map Markers */
        .modern-shop-marker {
            background: transparent !important;
            border: none !important;
        }
        
        .shop-marker-pin {
            width: 32px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .shop-marker-pin i {
            color: white;
            transform: rotate(45deg);
            font-size: 14px;
        }
        
        .shop-marker-pin:hover {
            transform: rotate(-45deg) scale(1.2);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
        }
        
        .modern-user-marker {
            background: transparent !important;
            border: none !important;
        }
        
        .user-marker-pulse {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .user-marker-dot {
            position: absolute;
            width: 12px;
            height: 12px;
            background: #6366f1;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }
        
        /* Modern Map Popup */
        .modern-popup .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: 1px solid rgba(99,102,241,0.1);
            padding: 0;
            overflow: hidden;
        }
        
        .modern-popup .leaflet-popup-content {
            margin: 0;
            padding: 0;
        }
        
        .modern-popup .leaflet-popup-tip {
            background: white;
            border: 1px solid rgba(99,102,241,0.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .modern-popup-content {
            font-family: 'Space Grotesk', sans-serif;
        }
        
        /* Modern Shop Cards */
        .modern-shop-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(99,102,241,0.1) !important;
            position: relative;
            z-index: 1;
        }
        
        .modern-shop-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(99,102,241,0.15) !important;
            border-color: rgba(99,102,241,0.3) !important;
        }
        
        .modern-shop-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modern-shop-card:hover::before {
            opacity: 1;
        }
        
        /* Modern Service Cards */
        .modern-service-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(99,102,241,0.1) !important;
        }
        
        .modern-service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(99,102,241,0.15) !important;
            border-color: rgba(99,102,241,0.3) !important;
        }
        
        /* Modern Item Cards */
        .modern-item-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(99,102,241,0.1) !important;
            overflow: hidden;
        }
        
        .modern-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12) !important;
            border-color: rgba(99,102,241,0.3) !important;
        }
        
        .item-image-hover {
            transition: transform 0.5s ease;
        }
        
        .modern-item-card:hover .item-image-hover {
            transform: scale(1.1);
        }
        
        /* Smooth transitions for Alpine.js */
        [x-transition] {
            transition: all 0.3s ease;
        }
        
        /* Map tile styling */
        .modern-map-tiles {
            filter: brightness(1.05) contrast(1.05) saturate(1.1);
        }
        
        /* Pulse animation for status indicator */
        @keyframes pulse-dot {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }
        
        .animate-pulse {
            animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        /* Sidebar scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.5);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.7);
        }
        
        /* Enhanced logo hover effect */
        .logo-container {
            position: relative;
            overflow: hidden;
        }
        
        .logo-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        
        .logo-container:hover::before {
            width: 100%;
            height: 100%;
        }
        
        /* Smooth sidebar transitions */
        .sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Modern Form Input Styling */
        .form-control-lg.border-2 {
            transition: all 0.3s ease;
        }
        
        .form-control-lg.border-2:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15) !important;
            transform: translateY(-1px);
        }
        
        .form-control-lg.border-2:hover:not(:disabled):not(:focus) {
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        /* Profile Card Enhancements */
        .card.shadow-lg {
            transition: all 0.3s ease;
        }
        
        .card.shadow-lg:hover {
            box-shadow: 0 15px 50px rgba(0,0,0,0.12) !important;
        }
        
        /* Button Enhancements */
        .btn[style*="border-radius: 25px"] {
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn[style*="border-radius: 25px"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .btn-primary[style*="border-radius: 25px"]:hover {
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        
        /* Profile Photo Hover Effect */
        .card-body img.rounded-circle {
            transition: all 0.3s ease;
        }
        
        .card-body:hover img.rounded-circle {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3) !important;
        }
        
        /* Icon Container Hover */
        .bg-primary.bg-opacity-10.rounded-circle {
            transition: all 0.3s ease;
        }
        
        .card:hover .bg-primary.bg-opacity-10.rounded-circle {
            background-color: rgba(99, 102, 241, 0.15) !important;
            transform: scale(1.1);
        }
        
        /* Modern Notification Cards */
        .modern-notification-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid transparent;
        }
        
        .modern-notification-card:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
            border-left-color: rgba(99, 102, 241, 0.5) !important;
        }
        
        .modern-notification-card.border-primary {
            background: linear-gradient(90deg, rgba(99,102,241,0.03) 0%, rgba(255,255,255,1) 100%);
        }
        
        /* Notification Icon Animation */
        .modern-notification-card:hover .rounded-circle {
            transform: scale(1.1);
            transition: transform 0.3s ease;
        }
        
        /* Space between notifications */
        .space-y-3 > * + * {
            margin-top: 1rem;
        }
        
        /* Modern Action Cards */
        .modern-action-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(99,102,241,0.1) !important;
        }
        
        .modern-action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
            border-color: rgba(99,102,241,0.3) !important;
        }
        
        .modern-action-card:hover .rounded-circle {
            transform: scale(1.1) rotate(5deg);
            transition: transform 0.3s ease;
        }
        
        .modern-action-card .btn {
            transition: all 0.3s ease;
        }
        
        .modern-action-card:hover .btn {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important;
        }
        
        /* Modern Booking Items */
        .modern-booking-item {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .modern-booking-item:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
            border-left-color: rgba(99,102,241,0.5) !important;
        }
        
        .modern-booking-item .badge {
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        /* Consistent Empty State */
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }
        
        .empty-state i {
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        /* Consistent Table Styling */
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table tbody td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }
        /* Improve card hover effects */
        .list-group-item {
            transition: all 0.2s ease;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Mobile Sidebar Toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1051;
            background-color: #0b1220;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            font-size: 18px;
        }
        
        .sidebar-toggle:hover {
            background-color: #1a2332;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        
        /* Main content margin for fixed sidebar */
        .main-content {
            margin-left: 280px;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block !important;
            }
            
            .sidebar {
                position: fixed !important;
                left: -280px !important;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1050 !important;
                height: 100vh;
                width: 280px !important;
                pointer-events: none;
            }
            
            .sidebar.open {
                left: 0 !important;
                pointer-events: auto !important;
            }
            
            .sidebar-overlay {
                z-index: 1040 !important;
            }
            
            .sidebar-overlay.show {
                display: block !important;
                pointer-events: auto !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .d-flex {
                flex-direction: column;
            }
            
            /* Ensure sidebar buttons are clickable */
            .sidebar.open .nav-btn,
            .sidebar.open .logout-btn,
            .sidebar.open button {
                pointer-events: auto !important;
                z-index: 1;
            }
        }
        
        /* Modern Booking Form Styles */
        .form-select-lg, .form-control-lg {
            font-size: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .form-select-lg:focus, .form-control-lg:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control.border-2 {
            border-width: 2px !important;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .form-control.border-2:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        textarea.form-control.border-2 {
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        
        textarea.form-control.border-2:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .custom-file-upload .btn-outline-primary {
            border-width: 2px;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .custom-file-upload .btn-outline-primary:hover {
            background-color: #6366f1;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-holographic {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-holographic:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-holographic:active {
            transform: translateY(0);
        }
        
        /* Form label enhancements */
        .form-label.fw-semibold {
            color: #374151;
            font-size: 0.95rem;
        }
        
        /* Section headers */
        h5.fw-bold {
            color: #1f2937;
            font-size: 1.1rem;
        }
        
        /* Icon styling in labels */
        .form-label i, h5 i {
            font-size: 1rem;
        }
        
        /* Modern Booking Cards */
        .booking-card-modern {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .booking-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        .booking-details .detail-item {
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 0.75rem;
        }
        
        .booking-details .detail-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        /* Enhanced Empty State */
        .empty-state {
            padding: 4rem 2rem;
        }
        
        /* Modern Badge Styles */
        .badge.rounded-pill {
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
        }
        
        /* Dropdown Menu Enhancements */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .dropdown-item {
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            transform: translateX(4px);
        }
        
        .dropdown-item.active {
            font-weight: 600;
        }
    </style>
<body class="bg-light" x-data="dashboard()" x-init="init()">
    <!-- Mobile Sidebar Toggle Button -->
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
        <i class="fas fa-bars" x-show="!sidebarOpen"></i>
        <i class="fas fa-times" x-show="sidebarOpen"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false" x-show="sidebarOpen" x-cloak style="z-index: 1040;"></div>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar shadow-md min-h-screen text-white" :class="{ 'open': sidebarOpen }" style="position: fixed; left: 0; width: 280px; background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); top: 0; height: 100vh; overflow-y: auto; z-index: 1050; border-right: 1px solid rgba(99,102,241,.2);">
            <div class="p-4 brand-wrap">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center logo-container shadow-lg" style="width: 52px; height: 52px; box-shadow: 0 4px 15px rgba(99,102,241,.4);">
                        <i class="fas fa-screwdriver-wrench text-white fs-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl fw-bold m-0" style="letter-spacing:.3px; color: #ffffff; text-shadow: 0 2px 8px rgba(0,0,0,.3);">ERepair</h2>
                        <div class="small" style="color: rgba(255,255,255,.7);">Customer Portal</div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="position-relative">
                            <img :src="avatarUrl" alt="Avatar" class="rounded-circle border border-3 d-block mx-auto shadow-lg" style="width:80px;height:80px;object-fit:cover; border-color: rgba(99,102,241,.4) !important; box-shadow: 0 4px 20px rgba(99,102,241,.3);">
                            <!-- Auto-refresh indicator -->
                            <div x-show="isPollingActive" class="position-absolute bottom-0 end-0 bg-success rounded-circle shadow-sm" style="width: 18px; height: 18px; border: 3px solid #0f172a; animation: pulse-dot 2s infinite;" title="Active connection"></div>
                        </div>
                    </div>
                    <div class="fw-bold mb-1" style="color:#ffffff !important; font-size: 1rem;">
                        <?php echo h($user['name']); ?>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button class="btn btn-sm btn-outline-light border-2 px-3" style="border-radius: 20px; transition: all 0.3s ease;" @click="section='profile'; sidebarOpen = false" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(255,255,255,.2)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <i class="fas fa-user-edit me-1"></i>Edit profile
                        </button>
                        <button class="btn btn-sm btn-outline-light border-2 position-relative px-3" style="border-radius: 20px; transition: all 0.3s ease;" @click="section='notifications'; sidebarOpen = false; loadNotifications()" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(255,255,255,.2)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <i class="fas fa-bell"></i>
                            <span x-show="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger animate-pulse" x-text="unreadCount" style="font-size: 0.65rem; padding: 2px 6px;"></span>
                        </button>
                    </div>
                </div>
            </div>
            <ul class="list-unstyled px-3 pb-3 space-y-2" style="margin-top: 1rem;">
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='home' }" @click="section='home'; sidebarOpen = false">
                        <i class="fas fa-home me-3"></i><span>Home</span>
                    </button>
                </li>
                
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='map' }" @click="section='map'; sidebarOpen = false; $nextTick(() => { initMap(); loadShops(); })">
                        <i class="fas fa-map-marked-alt me-3"></i><span>Find Nearest Shop</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='booking' }" @click="section='booking'; sidebarOpen = false">
                        <i class="fas fa-calendar-plus me-3"></i><span>Request Service</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='view_bookings' }" @click="section='view_bookings'; bookingTab='pending_review'; sidebarOpen = false; loadMyBookings()">
                        <i class="fas fa-list-ul me-3"></i><span>My Bookings</span>
                    </button>
                </li>
                
                <li style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(99,102,241,.2);">
                    <button class="w-100 text-start px-4 py-3 logout-btn" @click="logout()">
                        <i class="fas fa-right-from-bracket me-3"></i><span>Logout</span>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="flex-1 w-100 main-content">
            <div class="container py-4">
                <!-- Home Section -->
                <div x-show="section==='home'" class="mt-4" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Welcome Header -->
                    <div class="card border-0 shadow-lg mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(168,85,247,0.1) 100%);">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-home text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h2 class="h4 fw-bold mb-1 text-dark">Welcome Back, <?php echo h($user['name']); ?>!</h2>
                                    <p class="text-muted small mb-0">Manage your repair services and track your bookings</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row g-4 mb-5">
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card border-0 shadow-lg h-100 modern-action-card" style="background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(255,255,255,1) 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                            <i class="fas fa-calendar-plus text-primary fs-5"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-dark">New Booking</h6>
                                            <small class="text-muted">Book a repair appointment</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary w-100 py-2 shadow-sm" style="border-radius: 25px;" @click="section='booking'">
                                        <i class="fas fa-plus me-2"></i>Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card border-0 shadow-lg h-100 modern-action-card" style="background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(255,255,255,1) 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-info bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                            <i class="fas fa-list-ul text-info fs-5"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-dark">My Bookings</h6>
                                            <small class="text-muted">View all your bookings</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-info w-100 py-2 shadow-sm text-white" style="border-radius: 25px;" @click="section='view_bookings'; bookingTab='pending_review'; loadMyBookings()">
                                        <i class="fas fa-eye me-2"></i>View Bookings
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card border-0 shadow-lg h-100 modern-action-card" style="background: linear-gradient(135deg, rgba(16,185,129,0.05) 0%, rgba(255,255,255,1) 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-success bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                            <i class="fas fa-check-circle text-success fs-5"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-dark">Completed</h6>
                                            <small class="text-muted">View completed bookings</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-success w-100 py-2 shadow-sm text-white" style="border-radius: 25px;" @click="section='view_bookings'; bookingTab='completed'; loadMyBookings()">
                                        <i class="fas fa-check-double me-2"></i>View Completed
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings -->
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-clock text-primary"></i>
                                    </div>
                                    <span>Recent Bookings</span>
                                </h5>
                                <button class="btn btn-sm btn-outline-primary" @click="section='view_bookings'; bookingTab='pending_review'; loadMyBookings()">
                                    <i class="fas fa-arrow-right me-1"></i>View All
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <!-- Empty State -->
                            <div x-show="bookings.length === 0" class="text-center py-5">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                    <i class="fas fa-calendar-times fa-3x text-muted opacity-50"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-2">No Bookings Yet</h6>
                                <p class="text-muted mb-4">Start by booking your first repair service!</p>
                                <button class="btn btn-primary px-4" style="border-radius: 25px;" @click="section='booking'">
                                    <i class="fas fa-plus me-2"></i>Create Your First Booking
                                </button>
                            </div>
                            
                            <!-- Bookings List -->
                            <div x-show="bookings.length > 0" class="space-y-3">
                                <template x-for="(booking, index) in bookings.slice(0, 5)" :key="booking.id">
                                    <div class="card border-0 shadow-sm modern-booking-item" 
                                         style="transition: all 0.3s ease;"
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0 transform translate-x-4"
                                         x-transition:enter-end="opacity-100 transform translate-x-0"
                                         :style="'transition-delay: ' + (index * 0.05) + 's'">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-start gap-3 mb-3">
                                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-store text-primary"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="small text-muted mb-1">Shop</div>
                                                            <h6 class="fw-bold mb-2 text-dark" x-text="booking.shop_name"></h6>
                                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <i class="fas fa-wrench text-primary" style="font-size: 0.85rem;"></i>
                                                                    <span class="text-muted small" x-text="booking.service"></span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                                                <div class="d-flex align-items-center gap-1 text-muted small">
                                                                    <i class="fas fa-calendar"></i>
                                                                    <span x-text="booking.date || 'Not scheduled'"></span>
                                                                </div>
                                                                <div x-show="booking.time_slot" class="d-flex align-items-center gap-1 text-muted small">
                                                                    <i class="fas fa-clock"></i>
                                                                    <span x-text="booking.time_slot"></span>
                                                                </div>
                                                            </div>
                                                            <div class="mt-2" x-show="booking.reschedule_status==='accepted'">
                                                                <span class="badge bg-success rounded-pill">
                                                                    <i class="fas fa-check me-1"></i>Reschedule Accepted
                                                                </span>
                                                                <span class="small text-muted ms-2" x-show="booking.reschedule_new_at" x-text="'→ ' + new Date(booking.reschedule_new_at).toLocaleString()"></span>
                                                            </div>
                                                            <div class="mt-2" x-show="booking.reschedule_status==='declined'">
                                                                <span class="badge bg-danger rounded-pill">
                                                                    <i class="fas fa-times me-1"></i>Reschedule Declined
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end flex-shrink-0 ms-3">
                                                    <span class="badge rounded-pill px-3 py-2" :class="{
                                                        'bg-warning text-dark': booking.status==='pending' || booking.status==='pending_review',
                                                        'bg-info text-white': booking.status==='approved' || booking.status==='awaiting_customer_confirmation',
                                                        'bg-primary text-white': booking.status==='assigned' || booking.status==='confirmed_by_customer',
                                                        'bg-secondary text-white': booking.status==='in_progress',
                                                        'bg-success text-white': booking.status==='completed',
                                                        'bg-danger text-white': booking.status==='cancelled' || booking.status==='rejected'
                                                    }" x-text="booking.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                                    <div class="small text-muted mt-2" x-show="booking.status==='assigned'">
                                                        <i class="fas fa-user-cog me-1"></i>Technician Assigned
                                                    </div>
                                                    <div class="small text-muted mt-2" x-show="booking.status==='in_progress'">
                                                        <i class="fas fa-tools me-1"></i>In Progress
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Bookings Section (Enhanced with new statuses) -->
                <div x-show="section==='view_bookings'" class="mt-4" x-init="if(section==='view_bookings' && bookings.length===0) loadMyBookings()">
                    <!-- Modern Header Card -->
                    <div class="bg-white rounded-3 shadow-lg border-0 overflow-hidden mb-4">
                        <div class="p-4 p-md-5 border-bottom bg-light">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <h2 class="h4 mb-2 fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="fas fa-calendar-check text-primary"></i>
                                        <span>View Bookings</span>
                                    </h2>
                                    <p class="text-muted small mb-0">Track and manage all your repair service bookings</p>
                                </div>
                                
                                <!-- Status Filter Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-primary dropdown-toggle d-flex align-items-center gap-2 px-4 py-2 shadow-sm" type="button" id="customerBookingStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 0.75rem; font-weight: 500;">
                                        <i :class="getBookingStatusInfo(bookingTab).icon"></i>
                                        <span x-text="getBookingStatusInfo(bookingTab).label"></span>
                                        <span class="badge bg-white text-primary rounded-pill ms-2" x-text="getBookingCount(bookingTab)"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="customerBookingStatusDropdown" style="min-width: 300px; border-radius: 0.75rem; padding: 0.5rem;">
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-primary text-white': bookingTab === 'pending_review' }" href="#" @click.prevent="bookingTab = 'pending_review'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-search"></i>Pending Review</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'pending_review' ? 'bg-white text-primary' : 'bg-primary text-white'" x-text="getBookingCount('pending_review')"></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-warning text-dark': bookingTab === 'awaiting_confirmation' }" href="#" @click.prevent="bookingTab = 'awaiting_confirmation'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-exclamation-circle"></i>Awaiting Confirmation</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'awaiting_confirmation' ? 'bg-dark text-warning' : 'bg-warning text-dark'" x-text="getBookingCount('awaiting_confirmation')"></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-info text-white': bookingTab === 'confirmed' }" href="#" @click.prevent="bookingTab = 'confirmed'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-hourglass-half"></i>Confirmed</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'confirmed' ? 'bg-white text-info' : 'bg-info text-white'" x-text="getBookingCount('confirmed')"></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-success text-white': bookingTab === 'active' }" href="#" @click.prevent="bookingTab = 'active'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-check-circle"></i>Active Bookings</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'active' ? 'bg-white text-success' : 'bg-success text-white'" x-text="getBookingCount('active')"></span>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-2"></li>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-danger text-white': bookingTab === 'rejected' }" href="#" @click.prevent="bookingTab = 'rejected'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-ban"></i>Rejected</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'rejected' ? 'bg-white text-danger' : 'bg-danger text-white'" x-text="getBookingCount('rejected')"></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-secondary text-white': bookingTab === 'cancelled' }" href="#" @click.prevent="bookingTab = 'cancelled'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-times-circle"></i>Cancelled</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'cancelled' ? 'bg-white text-secondary' : 'bg-secondary text-white'" x-text="getBookingCount('cancelled')"></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center py-3 px-3" :class="{ 'active bg-success text-white': bookingTab === 'completed' }" href="#" @click.prevent="bookingTab = 'completed'; closeDropdown('customerBookingStatusDropdown')" style="border-radius: 0.5rem; margin: 0.25rem;">
                                                <span class="d-flex align-items-center gap-2"><i class="fas fa-check-double"></i>Completed</span>
                                                <span class="badge rounded-pill" :class="bookingTab === 'completed' ? 'bg-white text-success' : 'bg-success text-white'" x-text="getBookingCount('completed')"></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    
                        <!-- Tab Content -->
                        <div class="p-4 p-md-5">
                            <!-- Pending Review Tab -->
                            <div x-show="bookingTab === 'pending_review'">
                                <div x-show="bookings.filter(b=>b.status==='pending_review').length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-search fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No bookings pending diagnosis</h5>
                                    <p class="text-muted small mb-0">Your pending bookings will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>b.status==='pending_review').length > 0">
                                    <template x-for="b in bookings.filter(b=>b.status==='pending_review')" :key="'pr'+b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #6366f1;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-search text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Pending Review</h6>
                                                                <small class="text-muted">Awaiting Diagnosis</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-primary rounded-pill px-3 py-1">New</span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-primary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-primary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-primary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-exclamation-circle text-primary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Issue</span>
                                                            </div>
                                                            <div class="text-secondary small ms-4" x-text="(b.device_issue_description || b.description || 'No description').substring(0, 60) + ((b.device_issue_description || b.description || '').length > 60 ? '...' : '')"></div>
                                                        </div>
                                                        <div class="detail-item">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-calendar-alt text-primary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Schedule</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="b.date ? b.date + (b.time_slot ? ' at ' + b.time_slot : '') : 'Not scheduled'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        
                            <!-- Awaiting Confirmation Tab -->
                            <div x-show="bookingTab === 'awaiting_confirmation'">
                                <div x-show="bookings.filter(b=>b.status==='awaiting_customer_confirmation').length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-exclamation-circle fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No quotations pending</h5>
                                    <p class="text-muted small mb-0">Quotations awaiting your confirmation will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>b.status==='awaiting_customer_confirmation').length > 0">
                                    <template x-for="b in bookings.filter(b=>b.status==='awaiting_customer_confirmation')" :key="'ac'+b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #f59e0b;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-exclamation-circle text-warning"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Awaiting Confirmation</h6>
                                                                <small class="text-muted">Action Required</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1">Urgent</span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-warning" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-warning" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-warning" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-peso-sign text-warning" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Estimated Cost</span>
                                                            </div>
                                                            <div class="text-success fw-bold ms-4">₱<span x-text="Number(b.estimated_cost||0).toFixed(2)"></span></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-clock text-warning" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Estimated Time</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="formatEstimatedTime(b.estimated_time_days)"></div>
                                                        </div>
                                                        <div class="detail-item mb-4">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-clipboard-list text-warning" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Diagnosis</span>
                                                            </div>
                                                            <div class="text-secondary small ms-4" x-text="(b.diagnostic_notes || 'No diagnosis notes').substring(0, 80) + ((b.diagnostic_notes || '').length > 80 ? '...' : '')"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex flex-column gap-2 mt-4 pt-3 border-top">
                                                        <button class="btn btn-holographic text-white w-100 py-2.5 fw-semibold shadow-sm" @click="confirmBooking(b)" style="border-radius: 0.75rem;">
                                                            <i class="fas fa-check-circle me-2"></i>Confirm Booking
                                                        </button>
                                                        <button class="btn btn-outline-secondary w-100 py-2.5 fw-semibold" @click="cancelBookingWithReason(b)" style="border-radius: 0.75rem; border-width: 2px;">
                                                            <i class="fas fa-times-circle me-2"></i>Cancel Booking
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        
                            <!-- Confirmed Tab -->
                            <div x-show="bookingTab === 'confirmed'">
                                <div x-show="bookings.filter(b=>b.status==='confirmed_by_customer').length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-hourglass-half fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No confirmed bookings</h5>
                                    <p class="text-muted small mb-0">Your confirmed bookings will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>b.status==='confirmed_by_customer').length > 0">
                                    <template x-for="b in bookings.filter(b=>b.status==='confirmed_by_customer')" :key="'cc'+b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #06b6d4;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-info bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-hourglass-half text-info"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Confirmed</h6>
                                                                <small class="text-muted">Awaiting Shop Approval</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-info rounded-pill px-3 py-1">Pending</span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-info" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-info" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-info" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-calendar-alt text-info" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Schedule</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="b.date ? b.date + (b.time_slot ? ' at ' + b.time_slot : '') : 'Not scheduled'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        
                            <!-- Active Bookings Tab -->
                            <div x-show="bookingTab === 'active'">
                                <div x-show="bookings.filter(b=>['approved','assigned','in_progress'].includes(b.status)).length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-check-circle fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No active bookings</h5>
                                    <p class="text-muted small mb-0">Your active repair bookings will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>['approved','assigned','in_progress'].includes(b.status)).length > 0">
                                    <template x-for="b in bookings.filter(b=>['approved','assigned','in_progress'].includes(b.status))" :key="'active'+b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #10b981;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Active Booking</h6>
                                                                <small class="text-muted">In Progress</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge rounded-pill px-3 py-1" :class="{
                                                            'bg-success': b.status==='approved',
                                                            'bg-primary': b.status==='assigned',
                                                            'bg-secondary': b.status==='in_progress'
                                                        }" x-text="b.status.replace('_', ' ')"></span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-calendar-alt text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Schedule</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="b.date ? b.date + (b.time_slot ? ' at ' + b.time_slot : '') : 'Not scheduled'"></div>
                                                        </div>
                                                        <div class="detail-item" x-show="b.technician_name">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-user-cog text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Technician</span>
                                                            </div>
                                                            <div class="text-primary fw-semibold ms-4" x-text="b.technician_name"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        
                            <!-- Rejected Tab -->
                            <div x-show="bookingTab === 'rejected'">
                                <div x-show="bookings.filter(b=>b.status==='rejected').length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-ban fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No rejected bookings</h5>
                                    <p class="text-muted small mb-0">Rejected bookings will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>b.status==='rejected').length > 0">
                                    <template x-for="b in bookings.filter(b=>b.status==='rejected')" :key="'r'+b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #ef4444;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-danger bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-ban text-danger"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Rejected</h6>
                                                                <small class="text-muted">Booking Declined</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-danger rounded-pill px-3 py-1">Rejected</span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-danger" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-danger" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-danger" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-calendar-alt text-danger" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Schedule</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="b.date ? b.date + (b.time_slot ? ' at ' + b.time_slot : '') : 'Not scheduled'"></div>
                                                        </div>
                                                        <div class="detail-item">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-exclamation-triangle text-danger" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Reason</span>
                                                            </div>
                                                            <div class="text-danger small ms-4" x-text="b.rejection_reason || 'No reason provided'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Cancelled Tab -->
                            <div x-show="bookingTab === 'cancelled'">
                                <div x-show="bookings.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status)).length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-times-circle fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No cancelled bookings</h5>
                                    <p class="text-muted small mb-0">Cancelled bookings will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status)).length > 0">
                                    <template x-for="b in bookings.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status))" :key="'c'+b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #6b7280;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-secondary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-times-circle text-secondary"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Cancelled</h6>
                                                                <small class="text-muted">Booking Cancelled</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-secondary rounded-pill px-3 py-1" x-text="b.status.replace('_', ' ')"></span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-secondary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-secondary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-secondary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-calendar-alt text-secondary" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Schedule</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="b.date ? b.date + (b.time_slot ? ' at ' + b.time_slot : '') : 'Not scheduled'"></div>
                                                        </div>
                                                        <template x-if="b.cancellation_reason">
                                                            <div class="mt-3">
                                                                <div class="alert alert-danger border-0 py-2 px-3 mb-0 rounded" style="background: rgba(220,53,69,0.1); border-left: 3px solid #dc3545 !important; font-size: 0.875rem;">
                                                                    <strong><i class="fas fa-times-circle me-2"></i>Reason for cancellation:</strong> 
                                                                    <span x-text="b.cancellation_reason"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Completed Tab -->
                            <div x-show="bookingTab === 'completed'">
                                <div x-show="bookings.filter(b=>b.status==='completed').length===0" class="empty-state text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                        <i class="fas fa-check-double fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No completed bookings</h5>
                                    <p class="text-muted small mb-0">Your completed repair bookings will appear here</p>
                                </div>
                                <div class="row g-4" x-show="bookings.filter(b=>b.status==='completed').length > 0">
                                    <template x-for="b in bookings.filter(b=>b.status==='completed')" :key="b.id">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="booking-card-modern h-100" style="border-left: 4px solid #10b981;">
                                                <div class="card-body p-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-check-double text-success"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">Completed</h6>
                                                                <small class="text-muted">Repair Finished</small>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-success rounded-pill px-3 py-1">Completed</span>
                                                    </div>
                                                    
                                                    <div class="booking-details">
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-mobile-alt text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Device</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.device_type || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-store text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Shop</span>
                                                            </div>
                                                            <div class="fw-semibold text-dark ms-4" x-text="b.shop_name || 'Not assigned'"></div>
                                                        </div>
                                                        <div class="detail-item mb-3">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-screwdriver-wrench text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Service</span>
                                                            </div>
                                                            <div class="text-secondary ms-4" x-text="b.service || 'Not specified'"></div>
                                                        </div>
                                                        <div class="detail-item">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <i class="fas fa-calendar-alt text-success" style="width: 16px;"></i>
                                                                <span class="small text-muted fw-semibold">Schedule</span>
                                                            </div>
                                                            <div class="small ms-4" x-text="b.date ? b.date + (b.time_slot ? ' at ' + b.time_slot : '') : 'Not scheduled'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Bookings Section -->
                <div x-show="section==='history'" class="mt-4 glass-advanced rounded shadow-sm border border-gray-200 p-4">
                    <h2 class="h5 mb-3 neon-text">Completed Bookings</h2>
                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-primary" @click="loadMyBookings()" :disabled="isRefreshing">
                            <span x-show="!isRefreshing"><i class="fas fa-sync-alt me-1"></i>Refresh</span>
                            <span x-show="isRefreshing"><i class="fas fa-spinner fa-spin me-1"></i>Refreshing...</span>
                        </button>
                        <small x-show="lastRefreshTime" class="text-muted ms-2">Last updated: <span x-text="lastRefreshTime"></span></small>
                    </div>
                    <div x-show="bookings.filter(b=>b.status==='completed').length === 0" class="text-center py-4 text-gray-300">No completed bookings.</div>
                    <div x-show="bookings.filter(b=>b.status==='completed').length > 0" class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Shop</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="b in bookings.filter(b=>b.status==='completed')" :key="b.id">
                                    <tr>
                                        <td x-text="b.shop_name"></td>
                                        <td x-text="b.service"></td>
                                        <td x-text="b.date || 'Not scheduled'"></td>
                                        <td x-text="b.time_slot || '-'"></td>
                                        <td>
                                            <span x-show="b.technician_name" class="text-muted small" x-text="b.technician_name"></span>
                                            <span x-show="!b.technician_name" class="text-muted small fst-italic">Not assigned</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success" x-text="b.status"></span>
                                        </td>
                                        <td class="text-end">
                                            <button x-show="!b.reviewed" class="btn btn-sm btn-outline-primary" @click="openReviewModal(b)">Review</button>
                                            <span x-show="b.reviewed" class="text-success small"><i class="fas fa-check-circle me-1"></i>Reviewed</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Rejected Bookings Section -->
                <div x-show="section==='rejected'" class="mt-4 glass-advanced rounded shadow-sm border border-gray-200 p-4">
                    <h2 class="h5 mb-3 neon-text">Rejected Bookings</h2>
                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-primary" @click="loadMyBookings()" :disabled="isRefreshing">
                            <span x-show="!isRefreshing"><i class="fas fa-sync-alt me-1"></i>Refresh</span>
                            <span x-show="isRefreshing"><i class="fas fa-spinner fa-spin me-1"></i>Refreshing...</span>
                        </button>
                        <small x-show="lastRefreshTime" class="text-muted ms-2">Last updated: <span x-text="lastRefreshTime"></span></small>
                    </div>
                    <div x-show="bookings.filter(b=>b.status==='rejected').length === 0" class="text-center py-4 text-gray-300">No rejected bookings.</div>
                    <div x-show="bookings.filter(b=>b.status==='rejected').length > 0" class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Shop</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Rejection Reason</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="b in bookings.filter(b=>b.status==='rejected')" :key="'r'+b.id">
                                    <tr>
                                        <td x-text="b.shop_name"></td>
                                        <td x-text="b.service"></td>
                                        <td x-text="b.date || 'Not scheduled'"></td>
                                        <td x-text="b.time_slot || '-'"></td>
                                        <td>
                                            <span class="badge bg-danger" x-text="b.status"></span>
                                        </td>
                                        <td>
                                            <span x-show="b.rejection_reason" class="text-muted small" x-text="b.rejection_reason"></span>
                                            <span x-show="!b.rejection_reason" class="text-muted small fst-italic">No reason provided</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-primary" @click="rebook(b)">Rebook</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cancelled Bookings Section -->
                <div x-show="section==='cancelled'" class="mt-4 glass-advanced rounded shadow-sm border border-gray-200 p-4">
                    <h2 class="h5 mb-3 neon-text">Cancelled Bookings</h2>
                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-primary" @click="loadMyBookings()" :disabled="isRefreshing">
                            <span x-show="!isRefreshing"><i class="fas fa-sync-alt me-1"></i>Refresh</span>
                            <span x-show="isRefreshing"><i class="fas fa-spinner fa-spin me-1"></i>Refreshing...</span>
                        </button>
                        <small x-show="lastRefreshTime" class="text-muted ms-2">Last updated: <span x-text="lastRefreshTime"></span></small>
                    </div>
                    <div x-show="bookings.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status)).length === 0" class="text-center py-4 text-gray-300">No cancelled bookings.</div>
                    <div x-show="bookings.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status)).length > 0" class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Shop</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="b in bookings.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status))" :key="'c'+b.id">
                                    <tr>
                                        <td x-text="b.shop_name"></td>
                                        <td x-text="b.service"></td>
                                        <td x-text="b.date || 'Not scheduled'"></td>
                                        <td x-text="b.time_slot || '-'"></td>
                                        <td>
                                            <span x-show="b.technician_name" class="text-muted small" x-text="b.technician_name"></span>
                                            <span x-show="!b.technician_name" class="text-muted small fst-italic">Not assigned</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger" x-text="b.status"></span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-primary" @click="rebook(b)">Rebook</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Map Section -->
                <div x-show="section==='map'" class="mt-4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Modern Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1 neon-text d-flex align-items-center gap-2">
                                <i class="fas fa-map-marked-alt text-primary"></i>
                                <span>Find Nearest Shop</span>
                            </h2>
                            <p class="text-muted small mb-0">Discover repair shops near you on the map</p>
                        </div>
                        <div x-show="shops.length > 0" class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                            <i class="fas fa-store me-1"></i>
                            <span x-text="shops.length"></span> <span x-text="shops.length === 1 ? 'Shop' : 'Shops'"></span>
                        </div>
                    </div>
                    
                    <!-- Modern Map Container -->
                    <div class="position-relative mb-4">
                        <div id="map" class="modern-map-container" style="height: 500px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid rgba(99,102,241,0.1);"></div>
                        <div class="position-absolute top-0 end-0 m-3">
                            <button class="btn btn-light btn-sm shadow-sm" @click="$nextTick(() => { if(!map) initMap(); else { plotShops(userLocation?.lat || null, userLocation?.lng || null); loadShops(); } })" title="Refresh Map">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Available Shops List (shown when no shop selected) -->
                    <div x-show="!selectedShop" class="mt-4" x-transition>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="fas fa-list text-primary"></i>
                                <span>Available Shops</span>
                            </h5>
                            <div x-show="shops.length > 0" class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Click on a shop to view details
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div x-show="shops.length === 0" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mb-0">Loading nearby shops...</p>
                        </div>
                        
                        <!-- Shop Cards Grid -->
                        <div x-show="shops.length > 0" class="row g-3">
                            <template x-for="shop in shops" :key="shop.id">
                                <div class="col-md-6 col-lg-4">
                                    <div class="modern-shop-card card h-100 border-0 shadow-sm position-relative overflow-hidden">
                                        <div class="card-body p-4">
                                            <!-- Shop Header -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1 text-dark" x-text="shop.shop_name"></h6>
                                                    <div x-show="shop.distance_km" class="small text-muted d-flex align-items-center gap-1">
                                                        <i class="fas fa-route text-primary"></i>
                                                        <span x-text="Number(shop.distance_km).toFixed(1) + ' km away'"></span>
                                                    </div>
                                                </div>
                                                <div x-show="shop.total_reviews > 0" class="text-end">
                                                    <div class="d-flex align-items-center gap-1 bg-warning bg-opacity-10 px-2 py-1 rounded-pill">
                                                        <span class="text-warning" x-text="'⭐'.repeat(Math.floor(Number(shop.average_rating)))"></span>
                                                        <span class="fw-bold small text-dark" x-text="Number(shop.average_rating).toFixed(1)"></span>
                                                    </div>
                                                    <div class="small text-muted mt-1" x-text="'(' + shop.total_reviews + ' review' + (shop.total_reviews !== 1 ? 's' : '') + ')'"></div>
                                                </div>
                                            </div>
                                            
                                            <!-- Shop Info -->
                                            <div class="mb-3">
                                                <div class="small text-muted mb-2 d-flex align-items-start gap-2">
                                                    <i class="fas fa-map-marker-alt text-primary mt-1" style="width: 14px;"></i>
                                                    <span x-text="shop.shop_address"></span>
                                                </div>
                                                <div class="small text-muted d-flex align-items-center gap-2">
                                                    <i class="fas fa-phone text-primary" style="width: 14px;"></i>
                                                    <span x-text="shop.shop_phone || 'Phone not available'"></span>
                                                </div>
                                                <div x-show="shop.total_reviews === 0" class="small text-muted mt-2 d-flex align-items-center gap-1">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span>No ratings yet</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-2 mt-auto">
                                                <button class="btn btn-sm btn-outline-primary flex-grow-1" 
                                                        @click="viewShop(shop.id, shop.shop_name, shop.shop_latitude, shop.shop_longitude); section='map'; $nextTick(() => { if(!map) initMap(); })">
                                                    <i class="fas fa-eye me-1"></i>View Shop
                                                </button>
                                                <button class="btn btn-sm btn-primary flex-grow-1" @click="bookShop(shop.id)">
                                                    <i class="fas fa-calendar-check me-1"></i>Book Now
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Decorative gradient overlay -->
                                        <div class="position-absolute top-0 end-0 w-100 h-100" style="background: linear-gradient(135deg, rgba(99,102,241,0.03) 0%, transparent 50%); pointer-events: none; z-index: 0;"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Shop Homepage (shown when shop is selected) -->
                    <div x-show="selectedShop" class="mt-4" 
                         x-transition:enter="transition ease-out duration-300" 
                         x-transition:enter-start="opacity-0 transform translate-y-4" 
                         x-transition:enter-end="opacity-100 transform translate-y-0">
                        <!-- Loading State -->
                        <div x-show="loadingShop" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mb-0">Loading shop information...</p>
                        </div>
                        
                        <!-- Shop Content -->
                        <div x-show="!loadingShop && selectedShopData.shop && selectedShopData.shop.shop_name" 
                             x-transition:enter="transition ease-out duration-300" 
                             x-transition:enter-start="opacity-0" 
                             x-transition:enter-end="opacity-100">
                            <!-- Modern Shop Header Card -->
                            <div class="card border-0 shadow-lg mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(255,255,255,1) 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center gap-4 flex-grow-1">
                                            <div class="position-relative">
                                                <img :src="getShopAvatar(selectedShopData.shop)" 
                                                     class="rounded-circle border border-3 border-primary shadow-sm" 
                                                     style="width: 100px; height: 100px; object-fit: cover;"
                                                     :alt="selectedShopData.shop.shop_name">
                                                <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-3 border-white" style="width: 20px; height: 20px;"></div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h4 class="fw-bold mb-2 text-dark" x-text="selectedShopData.shop.shop_name"></h4>
                                                <div class="mb-2">
                                                    <div class="small text-muted mb-1 d-flex align-items-center gap-2">
                                                        <i class="fas fa-map-marker-alt text-primary"></i>
                                                        <span x-text="selectedShopData.shop.shop_address"></span>
                                                    </div>
                                                    <div class="small text-muted d-flex align-items-center gap-2">
                                                        <i class="fas fa-phone text-primary"></i>
                                                        <span x-text="selectedShopData.shop.shop_phone || selectedShopData.shop.owner_phone || 'Phone not available'"></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="d-flex align-items-center gap-2 bg-warning bg-opacity-10 px-3 py-2 rounded-pill">
                                                        <span class="text-warning fs-5" x-text="'⭐'.repeat(Math.floor(selectedShopData.ratings.average_rating || 0))"></span>
                                                        <span class="fw-bold text-dark" x-text="(selectedShopData.ratings.average_rating || 0).toFixed(1)"></span>
                                                        <span class="text-muted small" x-text="'(' + (selectedShopData.ratings.total_reviews || 0) + ' reviews)'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline-secondary btn-sm align-self-start" @click="selectedShop = null; loadingShop = false">
                                            <i class="fas fa-arrow-left me-1"></i>Back to List
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Services Section -->
                            <div class="mb-5">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-screwdriver-wrench text-primary"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0">Available Services</h5>
                                </div>
                                <div class="row g-3">
                                    <template x-for="service in selectedShopData.services" :key="service.id">
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card h-100 border-0 shadow-sm modern-service-card">
                                                <div class="card-body p-4">
                                                    <h6 class="card-title fw-bold mb-2 text-dark" x-text="service.service_name"></h6>
                                                    <p class="text-muted small mb-3" x-text="service.description || 'Professional repair service'"></p>
                                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                                        <div>
                                                            <span class="text-muted small">Price</span>
                                                            <div class="fw-bold text-primary fs-5">₱<span x-text="parseFloat(service.price).toFixed(2)"></span></div>
                                                        </div>
                                                        <button class="btn btn-primary btn-sm" @click="bookService(selectedShop, service.service_name)">
                                                            <i class="fas fa-calendar-plus me-1"></i>Book
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="selectedShopData.services.length === 0" class="alert alert-info border-0 shadow-sm text-center mb-0">
                                    <i class="fas fa-info-circle me-2"></i>No services available at this shop.
                                </div>
                            </div>
                            
                            <!-- Items for Sale Section -->
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-shopping-bag text-success"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0">Items for Sale</h5>
                                </div>
                                <div class="row g-3">
                                    <template x-for="item in selectedShopData.items" :key="item.id">
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card h-100 border-0 shadow-sm modern-item-card" :class="{'opacity-75': !item.is_available}">
                                                <div class="position-relative" style="height: 180px; overflow: hidden; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                                    <img x-show="item.image_url && !item.imageError" 
                                                         :src="getItemImageUrl(item.image_url)" 
                                                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" 
                                                         :alt="item.item_name" 
                                                         @error="item.imageError = true"
                                                         class="item-image-hover">
                                                    <div x-show="!item.image_url || item.imageError" class="d-flex align-items-center justify-content-center h-100">
                                                        <i class="fas fa-box-open fa-3x text-muted opacity-50"></i>
                                                    </div>
                                                    <span class="badge position-absolute top-0 end-0 m-2" 
                                                          :class="item.is_available ? 'bg-success' : 'bg-danger'" 
                                                          x-text="item.is_available ? 'Available' : 'Out of Stock'"></span>
                                                </div>
                                                <div class="card-body p-4 d-flex flex-column">
                                                    <h6 class="card-title fw-bold mb-2 text-dark" x-text="item.item_name"></h6>
                                                    <p class="text-muted small mb-3 flex-grow-1" x-text="item.description"></p>
                                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                                        <div>
                                                            <span class="text-muted small">Price</span>
                                                            <div class="fw-bold text-primary fs-5">₱<span x-text="item.price.toFixed(2)"></span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="selectedShopData.items.length === 0" class="alert alert-info border-0 shadow-sm text-center mb-0">
                                    <i class="fas fa-info-circle me-2"></i>No items for sale at this shop.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Section -->
                <div x-show="section==='profile'" class="mt-4" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Modern Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1 neon-text d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user-circle text-primary"></i>
                                </div>
                                <span>Profile Settings</span>
                            </h2>
                            <p class="text-muted small mb-0">Manage your account information and preferences</p>
                        </div>
                    </div>
                    
                    <!-- Profile Photo Card -->
                    <div class="card border-0 shadow-lg mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(255,255,255,1) 100%);">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-4">
                                <div class="position-relative">
                                    <img :src="avatarUrl" alt="Avatar" class="rounded-circle border border-3 shadow-lg" style="width: 100px; height: 100px; object-fit: cover; border-color: rgba(99,102,241,0.3) !important;">
                                    <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-3 border-white shadow-sm" style="width: 24px; height: 24px;" title="Active"></div>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1 text-dark">Profile Photo</h5>
                                    <p class="text-muted small mb-3">Upload a new profile picture. Recommended size: 200x200px</p>
                                    <input type="file" accept="image/*" x-ref="avatarInput" class="d-none" @change="onAvatarChange($event)">
                                    <button type="button" class="btn btn-primary btn-sm px-4" @click="$refs.avatarInput.click()" style="border-radius: 20px;">
                                        <i class="fas fa-camera me-2"></i>Change Photo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Information Card -->
                    <div class="card border-0 shadow-lg mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="fas fa-user-edit text-primary"></i>
                                <span>Personal Information</span>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form @submit.prevent="updateProfile">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-envelope text-primary"></i>
                                            <span>Email Address</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="email" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="form.email" 
                                                   disabled
                                                   style="background-color: #f8f9fa;">
                                            <i class="fas fa-envelope position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Email cannot be changed</span>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-user text-primary"></i>
                                            <span>Full Name <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="form.name" 
                                                   @input="sanitizeCustomerName($event)"
                                                   maxlength="100"
                                                   placeholder="Enter your full name"
                                                   required>
                                            <i class="fas fa-user position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-phone text-primary"></i>
                                            <span>Phone Number</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="tel" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="form.phone"
                                                   @input="sanitizeCustomerPhone($event)"
                                                   pattern="^09[0-9]{9}$"
                                                   maxlength="11"
                                                   minlength="11"
                                                   placeholder="09XXXXXXXXX">
                                            <i class="fas fa-phone position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Format: 09XXXXXXXXX (11 digits)</span>
                                        </small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-map-marker-alt text-primary"></i>
                                            <span>Address</span>
                                        </label>
                                        <div class="position-relative">
                                            <textarea class="form-control form-control-lg border-2 ps-5 pt-3" 
                                                      rows="3" 
                                                      x-model="form.address" 
                                                      @input="sanitizeCustomerAddress($event)"
                                                      maxlength="500"
                                                      placeholder="Enter your complete address"></textarea>
                                            <i class="fas fa-map-marker-alt position-absolute top-0 start-0 ms-3 mt-3 text-muted"></i>
                                        </div>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Maximum 500 characters</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-top d-flex flex-wrap gap-3">
                                    <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm" style="border-radius: 25px;">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <button type="button" class="btn btn-outline-primary px-5 py-2" style="border-radius: 25px;" @click="showChangePassword = !showChangePassword">
                                        <i class="fas fa-key me-2"></i>
                                        <span x-show="!showChangePassword">Change Password</span>
                                        <span x-show="showChangePassword">Hide Password Form</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password Card -->
                    <div x-show="showChangePassword" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform translate-y-4"
                         class="card border-0 shadow-lg">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-key text-warning"></i>
                                </div>
                                <span>Change Password</span>
                            </h5>
                            <p class="text-muted small mb-0 mt-2">Update your password to keep your account secure</p>
                        </div>
                        <div class="card-body p-4">
                            <form @submit.prevent="submitChangePassword($refs.oldPwd.value, $refs.newPwd.value, $refs.confirmPwd.value)">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-lock text-primary"></i>
                                            <span>Current Password <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="password" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-ref="oldPwd" 
                                                   @input="sanitizePassword($event, $refs.oldPwd)"
                                                   placeholder="Enter current password"
                                                   required>
                                            <i class="fas fa-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-key text-primary"></i>
                                            <span>New Password <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="password" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-ref="newPwd" 
                                                   @input="sanitizePassword($event, $refs.newPwd)"
                                                   minlength="6"
                                                   maxlength="128"
                                                   placeholder="Enter new password"
                                                   required>
                                            <i class="fas fa-key position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Minimum 6 characters</span>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-shield-alt text-primary"></i>
                                            <span>Confirm Password <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="password" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-ref="confirmPwd" 
                                                   @input="sanitizePassword($event, $refs.confirmPwd)"
                                                   minlength="6"
                                                   maxlength="128"
                                                   placeholder="Confirm new password"
                                                   required>
                                            <i class="fas fa-shield-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-top d-flex gap-3">
                                    <button type="button" class="btn btn-outline-secondary px-5 py-2" style="border-radius: 25px;" @click="showChangePassword = false">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm" style="border-radius: 25px;">
                                        <i class="fas fa-check me-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notifications Section -->
                <div x-show="section==='notifications'" class="mt-4 px-3 px-md-4" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Modern Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1 neon-text d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-bell text-primary"></i>
                                </div>
                                <span>Notifications</span>
                                <span x-show="unreadCount > 0" class="badge bg-danger rounded-pill animate-pulse" x-text="unreadCount" style="font-size: 0.7rem; padding: 4px 8px;"></span>
                            </h2>
                            <p class="text-muted small mb-0">Stay updated with your booking status and important updates</p>
                        </div>
                        <button class="btn btn-outline-primary px-4 py-2" style="border-radius: 25px;" @click="markAllAsRead()" x-show="unreadCount > 0">
                            <i class="fas fa-check-double me-2"></i>Mark All as Read
                        </button>
                    </div>
                    
                    <!-- Empty State -->
                    <div x-show="notifications.length === 0" class="card border-0 shadow-lg">
                        <div class="card-body text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                <i class="fas fa-bell-slash fa-3x text-muted opacity-50"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">No Notifications</h5>
                            <p class="text-muted mb-0">You're all caught up! New notifications will appear here.</p>
                        </div>
                    </div>
                    
                    <!-- Notifications List -->
                    <div x-show="notifications.length > 0" class="space-y-3">
                        <template x-for="(notif, index) in notifications" :key="notif.id">
                            <div class="card border-0 shadow-sm modern-notification-card" 
                                 :class="{'border-start border-primary border-4': !notif.is_read, 'opacity-75': notif.is_read}"
                                 style="cursor: pointer; transition: all 0.3s ease;"
                                 @click="handleNotification(notif)"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform translate-x-4"
                                 x-transition:enter-end="opacity-100 transform translate-x-0"
                                 :style="'transition-delay: ' + (index * 0.05) + 's'">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <!-- Notification Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                 :class="notif.is_read ? 'bg-light' : 'bg-primary bg-opacity-10'"
                                                 style="width: 48px; height: 48px;">
                                                <i class="fas" 
                                                   :class="{
                                                       'fa-search text-primary': notif.type === 'booking_diagnosed',
                                                       'fa-check-circle text-success': notif.type === 'booking_completed',
                                                       'fa-exclamation-circle text-warning': notif.type === 'booking_rejected',
                                                       'fa-calendar-check text-info': notif.type === 'booking_confirmed',
                                                       'fa-bell text-primary': true
                                                   }"
                                                   style="font-size: 1.2rem;"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Notification Content -->
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <h6 class="fw-bold mb-0 text-dark" x-text="notif.title"></h6>
                                                        <span x-show="!notif.is_read" class="badge bg-primary rounded-pill animate-pulse" style="font-size: 0.65rem; padding: 2px 8px;">New</span>
                                                    </div>
                                                    <p class="mb-2 text-muted small" x-html="formatNotificationMessage(notif.message)"></p>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-clock text-muted" style="font-size: 0.75rem;"></i>
                                                        <small class="text-muted" x-text="formatTime(notif.created_at)"></small>
                                                    </div>
                                                </div>
                                                
                                                <!-- Delete Button -->
                                                <button class="btn btn-sm btn-link text-danger p-1 flex-shrink-0" 
                                                        @click.stop="deleteNotification(notif.id)"
                                                        style="opacity: 0.6; transition: all 0.2s ease;"
                                                        onmouseover="this.style.opacity='1'; this.style.transform='scale(1.2)'"
                                                        onmouseout="this.style.opacity='0.6'; this.style.transform='scale(1)'">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Booking Section -->
                <div x-show="section==='booking'" class="mt-4">
                    <!-- Modern Card Design -->
                    <div class="bg-white rounded-3 shadow-lg border-0 overflow-hidden">
                        <!-- Header Section -->
                        <div class="p-4 p-md-5 border-bottom">
                            <h2 class="h4 mb-2 fw-bold text-dark">Create Booking</h2>
                            <p class="mb-0 text-muted small">Submit your device for diagnosis. You'll select a schedule when confirming the booking after receiving the quotation.</p>
                        </div>
                        
                        <!-- Form Section -->
                        <div class="p-4 p-md-5">
                            <form @submit.prevent="createBooking">
                                <!-- Shop & Service Selection -->
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-store text-primary"></i>
                                            <span>Select Shop <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <select class="form-select form-select-lg border-2" 
                                                    style="padding-left: 2.5rem;"
                                                    x-model.number="booking.shop_owner_id" 
                                                    @focus="shops.length===0 && loadShops()" 
                                                    @change="servicesLoaded = false; loadShopServices()"
                                                    required>
                                                <option value="">Choose a repair shop...</option>
                                                <template x-for="s in shops" :key="s.id">
                                                    <option :value="Number(s.id)" x-text="(s.distance_km!=null?('['+s.distance_km+' km] '):'') + s.shop_name + (s.total_reviews > 0 ? ' ⭐ ' + Number(s.average_rating).toFixed(1) + ' (' + s.total_reviews + ')' : '') + ' — ' + (s.shop_address||'')"></option>
                                                </template>
                                            </select>
                                            <i class="fas fa-chevron-down position-absolute top-50 end-0 translate-middle-y me-3 text-muted" style="pointer-events: none;"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-screwdriver-wrench text-primary"></i>
                                            <span>Service <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <select class="form-select form-select-lg border-2" 
                                                    style="padding-left: 2.5rem;"
                                                    x-model="booking.service" 
                                                    :disabled="!booking.shop_owner_id"
                                                    required>
                                                <option value="">Select a service...</option>
                                                <option value="" disabled x-show="booking.shop_owner_id && shopServices.length === 0">Loading services...</option>
                                                <template x-for="service in shopServices" :key="service.id">
                                                    <option :value="service.service_name" x-text="service.service_name + ' - ₱' + Number(service.price).toFixed(2)"></option>
                                                </template>
                                            </select>
                                            <i class="fas fa-chevron-down position-absolute top-50 end-0 translate-middle-y me-3 text-muted" style="pointer-events: none;"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <hr class="my-4" style="border-color: #e5e7eb;">

                                <!-- Device Information -->
                                <div class="mb-4">
                                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                        <i class="fas fa-mobile-alt text-primary"></i>
                                        <span>Device Information</span>
                                    </h5>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                                <i class="fas fa-tag text-primary"></i>
                                                <span>Device Type <span class="text-danger">*</span></span>
                                            </label>
                                            <div class="position-relative">
                                                <input 
                                                    type="text" 
                                                    class="form-control form-control-lg border-2 ps-5" 
                                                    x-model="booking.device_type" 
                                                    placeholder="e.g., iPhone 13, Samsung Galaxy S21" 
                                                    @input="booking.device_type = sanitizeInput(booking.device_type, 'device')"
                                                    @keydown="preventSpecialChars($event, 'device')"
                                                    @paste="handlePaste($event, 'device')"
                                                    required
                                                />
                                                <i class="fas fa-mobile-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                            </div>
                                            <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                                <i class="fas fa-info-circle"></i>
                                                <span>Only letters, numbers, spaces, and common punctuation allowed</span>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                                <i class="fas fa-camera text-primary"></i>
                                                <span>Device Photo <span class="text-muted small">(Optional)</span></span>
                                            </label>
                                            <div class="custom-file-upload">
                                                <input type="file" 
                                                       class="d-none" 
                                                       x-ref="devicePhoto" 
                                                       accept="image/jpeg,image/png,image/jpg,image/webp"
                                                       @change="handleFileSelect($event)">
                                                <button type="button" 
                                                        @click="$refs.devicePhoto.click()"
                                                        class="btn btn-outline-primary w-100 py-3 border-2 d-flex align-items-center justify-content-center gap-2">
                                                    <i class="fas fa-upload"></i>
                                                    <span x-text="devicePhotoName || 'Choose Photo'"></span>
                                                </button>
                                            </div>
                                            <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                                <i class="fas fa-info-circle"></i>
                                                <span>Max 2MB. JPG, PNG, or WebP format. Helps with diagnosis.</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <hr class="my-4" style="border-color: #e5e7eb;">

                                <!-- Issue Description -->
                                <div class="mb-4">
                                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                        <i class="fas fa-clipboard-list text-primary"></i>
                                        <span>Issue Details</span>
                                    </h5>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-exclamation-circle text-primary"></i>
                                            <span>Issue Description <span class="text-danger">*</span></span>
                                        </label>
                                        <textarea 
                                            class="form-control border-2" 
                                            rows="5" 
                                            x-model="booking.issue_description" 
                                            placeholder="Describe what's wrong with your device in detail. Include any error messages, when the issue started, and what you were doing when it occurred..." 
                                            @input="booking.issue_description = sanitizeInput(booking.issue_description, 'text')"
                                            @keydown="preventSpecialChars($event, 'text')"
                                            @paste="handlePaste($event, 'text')"
                                            style="resize: vertical; min-height: 120px;"
                                            required
                                        ></textarea>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Only letters, numbers, spaces, and common punctuation allowed</span>
                                        </small>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-sticky-note text-primary"></i>
                                            <span>Additional Notes <span class="text-muted small">(Optional)</span></span>
                                        </label>
                                        <textarea 
                                            class="form-control border-2" 
                                            rows="3" 
                                            x-model="booking.description" 
                                            placeholder="Any other information that might help with the diagnosis..."
                                            @input="booking.description = sanitizeInput(booking.description, 'text')"
                                            @keydown="preventSpecialChars($event, 'text')"
                                            @paste="handlePaste($event, 'text')"
                                            style="resize: vertical; min-height: 80px;"
                                        ></textarea>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Only letters, numbers, spaces, and common punctuation allowed</span>
                                        </small>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-flex justify-content-end gap-3 pt-4 border-top">
                                    <button type="button" 
                                            class="btn btn-outline-secondary px-5 py-2" 
                                            @click="booking = { service: '', device_type: '', issue_description: '', description: '', shop_owner_id: null }; if($refs.devicePhoto) $refs.devicePhoto.value = ''; devicePhotoName = ''; shopServices = [];">
                                        <i class="fas fa-times me-2"></i>Clear Form
                                    </button>
                                    <button type="submit" 
                                            class="btn btn-holographic text-white px-5 py-2 shadow-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Submit for Diagnosis
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function dashboard() {
        return {
            section: 'home',
            bookingTab: 'pending_review', // Default tab for booking sections
            showChangePassword: false,
            sidebarOpen: false, // Mobile sidebar toggle state
            isPollingActive: true,
            userLocation: null, // Store user's current location for route calculation
            pollingInterval: null,
            notifications: [],
            unreadCount: 0,
            isRefreshing: false,
            lastRefreshTime: null,
            emailVerified: <?php echo $user['email_verified'] ? 'true' : 'false'; ?>,
            form: {
                name: <?php echo json_encode($user['name']); ?>,
                phone: <?php echo json_encode($user['phone']); ?>,
                email: <?php echo json_encode($user['email']); ?>,
                address: <?php echo json_encode($user['address'] ?? ''); ?>,
                password: ''
            },
            avatarUrl: <?php echo json_encode($normalizedAvatarUrl); ?>,
            booking: { service: '', device_type: '', issue_description: '', description: '', shop_owner_id: null },
            devicePhotoName: '',
            bookings: [],
            shops: [],
            shopServices: [],
            loadingServices: false,
            selectedShop: null,
            selectedShopData: {
                shop: {},
                ratings: { average_rating: 0, total_reviews: 0 },
                services: [],
                items: []
            },
            loadingShop: false,
            selectedBooking: null,
            reviewForm: { rating: 0, comment: '' },
            isSubmittingReview: false,
            currentBookingForSchedule: null,
            currentBookingForCancel: null,
            calendarData: null,
            selectedDate: null,
            availableSlots: [],
            calendarDays: [],
            leadingBlanks: [],
            todayYmd: new Date().toISOString().slice(0,10),
            tomorrowYmd: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().slice(0,10),
            map: null,
            markersLayer: null,
            routeLayer: null, // Store route polyline layer
            steps: ['requested','approved','assigned','in_progress','completed'],
            openTimelineId: null,
            init() {
                this.loadWebsiteLogo(); // Load admin's website logo for favicon
                // Always load bookings first for dashboard visibility
                this.loadMyBookings();
                this.loadNotifications();
                // Start auto-refresh polling
                this.startPolling();
                // Then optionally load shops (non-blocking)
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(async (pos)=>{
                        try {
                            const { latitude, longitude } = pos.coords;
                            const res = await fetch(`../admin/shops_list.php?lat=${encodeURIComponent(latitude)}&lng=${encodeURIComponent(longitude)}`);
                            const data = await res.json();
                            if(data.success){ this.shops = data.shops || []; }
                        } catch(e) {}
                    }, ()=>{ /* silent fail */ });
                }
            },
            startPolling(){
                this.isPollingActive = true;
                this.pollingInterval = setInterval(() => {
                    console.log('Customer: Auto-refresh triggered');
                    this.loadMyBookings();
                    this.loadNotifications(); // Also poll notifications
                }, 10000); // Poll every 10 seconds
                console.log('Customer: AJAX auto-refresh started (every 10 seconds)');
            },
            stopPolling(){
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                    this.isPollingActive = false;
                }
                console.log('Customer: AJAX polling stopped');
            },
            togglePolling(){
                if (this.isPollingActive) {
                    this.stopPolling();
                } else {
                    this.startPolling();
                }
            },
            async loadNotifications(){
                try {
                    console.log('Customer: Loading notifications...');
                    const res = await fetch('../auth/notifications.php');
                    const data = await res.json();
                    console.log('Customer: Notifications API response:', data);
                    if(data.success) {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                        console.log('Customer: Notifications loaded:', this.notifications.length, 'unread:', this.unreadCount);
                        console.log('Customer: unreadCount value:', this.unreadCount, 'type:', typeof this.unreadCount);
                        console.log('Customer: unreadCount > 0:', this.unreadCount > 0);
                    } else {
                        console.error('Customer: Notifications API error:', data.error);
                    }
                } catch(e) {
                    console.error('Customer: Error loading notifications:', e);
                }
            },
            async markAllAsRead(){
                try {
                    const res = await fetch('../auth/notifications.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ mark_all_read: true })
                    });
                    const data = await res.json();
                    if(data.success) {
                        this.loadNotifications();
                        Notiflix.Report.success('Success', 'All notifications marked as read', 'OK');
                    }
                } catch(e) {
                    console.error('Error marking notifications as read:', e);
                }
            },
            async deleteNotification(id){
                try {
                    const res = await fetch(`../auth/notifications.php?id=${id}`, { method: 'DELETE' });
                    const data = await res.json();
                    if(data.success) {
                        this.loadNotifications();
                    }
                } catch(e) {
                    console.error('Error deleting notification:', e);
                }
            },
            async handleNotification(notif){
                try{
                    // Mark notification as read
                    await fetch('../auth/notifications.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ notification_id: notif.id }) });
                    notif.is_read = true;
                    if(this.unreadCount>0) this.unreadCount = this.unreadCount - 1;
                }catch(e){ /* ignore */ }

                const t = String(notif.type||'');
                console.log('Customer Dashboard Notification Type:', t, notif);
                
                // Load bookings first to ensure we have the latest data
                this.loadMyBookings();
                
                // Navigate based on notification type - all bookings are in My Bookings section with tabs
                if(t === 'booking_diagnosed'){
                    // Shop provided diagnosis - booking status is 'awaiting_customer_confirmation'
                    this.section = 'view_bookings';
                    this.bookingTab = 'awaiting_confirmation';
                } else if(t === 'booking_completed'){
                    // Booking completed - booking status is 'completed'
                    this.section = 'view_bookings';
                    this.bookingTab = 'completed';
                } else if(t === 'booking_rejected'){
                    // Booking rejected - booking status is 'rejected'
                    this.section = 'view_bookings';
                    this.bookingTab = 'rejected';
                } else if(t === 'customer_cancelled' || t === 'booking_cancelled' || t === 'cancelled_by_customer'){
                    // Booking cancelled - booking status is 'cancelled' or 'cancelled_by_customer'
                    this.section = 'view_bookings';
                    this.bookingTab = 'cancelled';
                } else if(t === 'booking_approved'){
                    // Shop approved booking - booking status is 'approved'
                    this.section = 'view_bookings';
                    this.bookingTab = 'active';
                } else if(t === 'technician_assigned'){
                    // Technician assigned - booking status is 'assigned'
                    this.section = 'view_bookings';
                    this.bookingTab = 'active';
                } else if(t === 'booking_in_progress'){
                    // Work started - booking status is 'in_progress'
                    this.section = 'view_bookings';
                    this.bookingTab = 'active';
                } else if(t === 'reschedule_accepted' || t === 'reschedule_declined'){
                    // Reschedule response - booking still active
                    this.section = 'view_bookings';
                    this.bookingTab = 'active';
                } else if(t === 'new_booking' || t === 'booking_submitted'){
                    // New booking submitted - booking status is 'pending_review'
                    this.section = 'view_bookings';
                    this.bookingTab = 'pending_review';
                } else {
                    // Default: go to view bookings with pending review tab
                    this.section = 'view_bookings';
                    this.bookingTab = 'pending_review';
                }
            },
            formatTime(timestamp){
                const date = new Date(timestamp);
                const now = new Date();
                const diff = (now - date) / 1000; // seconds
                
                if(diff < 60) return 'Just now';
                if(diff < 3600) return Math.floor(diff / 60) + ' min ago';
                if(diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
                if(diff < 604800) return Math.floor(diff / 86400) + ' days ago';
                return date.toLocaleDateString();
            },
            formatNotificationMessage(message) {
                if (!message) return '';
                
                // Replace estimated time format: "X day(s)" with formatted time
                let formatted = message.replace(/(\d+\.?\d*)\s+day\(s\)/gi, (match, days) => {
                    const timeDays = parseFloat(days);
                    if (timeDays === 0) return '0 hours';
                    if (timeDays < 1) {
                        const hours = Math.round(timeDays * 24);
                        return hours === 1 ? '1 hour' : `${hours} hours`;
                    } else if (timeDays === 1) {
                        return '1 day';
                    } else {
                        // For values >= 1, show days with hours if there's a fractional part
                        const wholeDays = Math.floor(timeDays);
                        const fractionalDays = timeDays - wholeDays;
                        if (fractionalDays > 0) {
                            const hours = Math.round(fractionalDays * 24);
                            if (hours > 0) {
                                return wholeDays === 1 
                                    ? `1 day, ${hours} ${hours === 1 ? 'hour' : 'hours'}`
                                    : `${wholeDays} days, ${hours} ${hours === 1 ? 'hour' : 'hours'}`;
                            }
                        }
                        return `${timeDays} days`;
                    }
                });
                
                // Highlight important parts (cost, time) with spans for styling
                formatted = formatted.replace(/(Estimated cost: ₱[\d,]+\.?\d*)/gi, '<strong class="text-success">$1</strong>');
                formatted = formatted.replace(/(Estimated time: [^\.]+)/gi, '<strong class="text-primary">$1</strong>');
                
                return formatted;
            },
            updateFavicon(logoUrl) {
                // Update the favicon with website logo
                const favicon = document.getElementById('favicon');
                if (favicon && logoUrl) {
                    // Normalize the logo URL for favicon
                    let faviconUrl = logoUrl;
                    if (!faviconUrl.startsWith('http://') && !faviconUrl.startsWith('https://')) {
                        if (!faviconUrl.startsWith('../')) {
                            faviconUrl = '../' + faviconUrl.replace(/^\/+/, '');
                        }
                    }
                    favicon.href = faviconUrl;
                    
                    // Also update apple-touch-icon
                    let appleIcon = document.querySelector("link[rel='apple-touch-icon']");
                    if (!appleIcon) {
                        appleIcon = document.createElement('link');
                        appleIcon.rel = 'apple-touch-icon';
                        document.head.appendChild(appleIcon);
                    }
                    appleIcon.href = faviconUrl;
                }
            },
            async loadWebsiteLogo() {
                // Fetch admin's website logo for favicon
                try {
                    const res = await fetch('../../backend/api/get-website-logo.php');
                    const data = await res.json();
                    if (data.success && data.logo_url) {
                        let logoUrl = data.logo_url;
                        // Normalize for frontend/customer/ (one level deep from frontend/)
                        if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                            if (logoUrl.startsWith('../backend/')) {
                                // Path is relative to frontend/, need to add ../ for customer/
                                logoUrl = '../' + logoUrl; // ../backend/... becomes ../../backend/...
                            } else if (logoUrl.startsWith('backend/')) {
                                logoUrl = '../../' + logoUrl;
                            } else if (!logoUrl.startsWith('../') && !logoUrl.startsWith('/')) {
                                logoUrl = '../../backend/uploads/logos/' + logoUrl.split('/').pop();
                            }
                        }
                        this.updateFavicon(logoUrl);
                        console.log('Customer dashboard: Favicon updated to:', logoUrl);
                    }
                } catch (e) {
                    console.error('Error loading website logo:', e);
                }
            },
            onAvatarChange(e){
                const file = e.target.files && e.target.files[0];
                if(!file) return;
                const formData = new FormData();
                formData.append('avatar', file);
                fetch('profile_photo_upload.php', { method: 'POST', body: formData })
                    .then(r=>r.json())
                    .then(data=>{
                        if(data.success){
                            // Normalize avatar URL to be correct relative to frontend/customer/
                            let avatarUrl = data.avatar_url;
                            if(avatarUrl && !avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
                                avatarUrl = '../' + avatarUrl.replace(/^\/+/, '');
                            }
                            this.avatarUrl = avatarUrl;
                            // Note: Favicon uses website logo, not user avatar
                            Notiflix.Report.success('Updated', 'Profile photo updated', 'OK');
                        } else {
                            Notiflix.Report.failure('Error', data.error||'Upload failed', 'OK');
                        }
                    })
                    .catch((e) => {
                        console.error('Upload error:', e);
                        Notiflix.Report.failure('Error', 'Network error occurred during upload', 'OK');
                    });
            },
            initMap(){
                if(this.map) { this.map.invalidateSize(); return; }
                console.log('Initializing map...');
                this.map = L.map('map', {
                    zoomControl: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    boxZoom: true,
                    keyboard: true,
                    dragging: true,
                    touchZoom: true
                }).setView([9.647, 123.856], 13);
                
                // Modern tile layer with better styling
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                    maxZoom: 19, 
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    className: 'modern-map-tiles'
                }).addTo(this.map);
                
                this.markersLayer = L.layerGroup().addTo(this.map);
                console.log('Map initialized, requesting location...');
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((pos)=>{
                        console.log('Location obtained:', pos.coords);
                        const { latitude, longitude } = pos.coords;
                        // Store user location for route calculation
                        this.userLocation = { lat: latitude, lng: longitude };
                        this.map.setView([latitude, longitude], 14);
                        
                        // Modern user location marker
                        const userIcon = L.divIcon({
                            className: 'modern-user-marker',
                            html: '<div class="user-marker-pulse"></div><div class="user-marker-dot"></div>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        });
                        L.marker([latitude, longitude], { icon: userIcon })
                            .addTo(this.map)
                            .bindPopup('<div class="modern-popup-content"><strong>📍 You are here</strong></div>', {
                                className: 'modern-popup'
                            });
                        this.plotShops(latitude, longitude);
                    }, (error)=>{
                        console.log('Geolocation error:', error);
                        this.userLocation = null;
                        this.plotShops(null, null);
                    });
                } else { 
                    console.log('Geolocation not supported');
                    this.userLocation = null;
                    this.plotShops(null, null); 
                }
            },
            async plotShops(lat,lng){
                try{
                    console.log('Plotting shops with lat:', lat, 'lng:', lng);
                    let url = '../admin/shops_list.php';
                    if(lat!=null&&lng!=null) url += `?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
                    console.log('Fetching URL:', url);
                    const res = await fetch(url);
                    const data = await res.json();
                    console.log('Shops data received:', data);
                    if(!(data.success)) return;
                    this.markersLayer.clearLayers();
                    console.log('Plotting', data.shops.length, 'shops');
                    const self = this;
                    (data.shops||[]).forEach(async (s)=>{
                        console.log('Adding marker for shop:', s.shop_name, 'at', s.shop_latitude, s.shop_longitude);
                        
                        // Modern shop marker icon
                        const shopIcon = L.divIcon({
                            className: 'modern-shop-marker',
                            html: `<div class="shop-marker-pin">
                                     <i class="fas fa-store"></i>
                                   </div>`,
                            iconSize: [32, 40],
                            iconAnchor: [16, 40],
                            popupAnchor: [0, -40]
                        });
                        
                        const m = L.marker([Number(s.shop_latitude)||0, Number(s.shop_longitude)||0], { icon: shopIcon });
                        
                        const ratingDisplay = s.total_reviews > 0 ? 
                            `<div class="d-flex align-items-center gap-2 mb-3 p-2 bg-warning bg-opacity-10 rounded-pill">
                                <span class="text-warning fs-6">${'⭐'.repeat(Math.floor(Number(s.average_rating)))}</span>
                                <span class="fw-bold text-dark">${Number(s.average_rating).toFixed(1)}</span>
                                <span class="small text-muted">(${s.total_reviews} review${s.total_reviews !== 1 ? 's' : ''})</span>
                            </div>` : 
                            `<div class="small text-muted mb-3 p-2 bg-light rounded text-center">No ratings yet</div>`;
                        
                        const distanceDisplay = s.distance_km ? 
                            `<div class="small text-primary mb-2 d-flex align-items-center gap-1">
                                <i class="fas fa-route"></i>
                                <span>${Number(s.distance_km).toFixed(1)} km away</span>
                            </div>` : '';
                        
                        const popup = `
                            <div class="modern-popup-content" style="min-width:280px; padding: 0;">
                                <div class="p-3 border-bottom bg-primary bg-opacity-5">
                                    <h6 class="fw-bold mb-1 text-dark">${s.shop_name}</h6>
                                    ${distanceDisplay}
                                </div>
                                <div class="p-3">
                                    <div class="small text-muted mb-2 d-flex align-items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-primary mt-1" style="width: 14px;"></i>
                                        <span>${s.shop_address||'Address not available'}</span>
                                    </div>
                                    <div class="small text-muted mb-3 d-flex align-items-center gap-2">
                                        <i class="fas fa-phone text-primary" style="width: 14px;"></i>
                                        <span>${s.shop_phone||'Phone not available'}</span>
                                    </div>
                                    ${ratingDisplay}
                                    <div class="d-flex flex-column gap-2 mt-3">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary flex-grow-1 view-shop-btn" data-shop-id="${Number(s.id)}">
                                                <i class="fas fa-eye me-1"></i>View Shop
                                            </button>
                                            <button class="btn btn-sm btn-primary flex-grow-1" onclick="window.scrollTo({top:0,behavior:'smooth'}); document.querySelector('[x-data]').__x.$data.bookShop(${Number(s.id)});">
                                                <i class="fas fa-calendar-check me-1"></i>Book Now
                                            </button>
                                        </div>
                                        <button class="btn btn-sm btn-success w-100 get-directions-btn" 
                                                data-shop-lat="${Number(s.shop_latitude)||0}" 
                                                data-shop-lng="${Number(s.shop_longitude)||0}"
                                                data-shop-address="${(s.shop_address||'').replace(/"/g, '&quot;')}">
                                            <i class="fas fa-route me-1"></i>Get Directions
                                        </button>
                                    </div>
                                </div>
                            </div>`;
                        m.bindPopup(popup, {
                            className: 'modern-popup',
                            maxWidth: 320
                        });
                        this.markersLayer.addLayer(m);
                        
                        // Add event handler when popup opens
                        m.on('popupopen', function() {
                            const popupElement = this.getPopup().getElement();
                            if (popupElement) {
                                const viewShopBtn = popupElement.querySelector('.view-shop-btn');
                                if (viewShopBtn) {
                                    viewShopBtn.onclick = function() {
                                        const shopId = parseInt(this.dataset.shopId);
                                        // Pass shop coordinates to pan map immediately
                                        self.viewShop(shopId, s.shop_name, s.shop_latitude, s.shop_longitude);
                                        if (self.map) {
                                            self.map.closePopup();
                                        }
                                    };
                                }
                                
                                // Add directions button handler
                                const directionsBtn = popupElement.querySelector('.get-directions-btn');
                                if (directionsBtn) {
                                    directionsBtn.onclick = function() {
                                        const shopLat = parseFloat(this.dataset.shopLat);
                                        const shopLng = parseFloat(this.dataset.shopLng);
                                        const shopAddress = this.dataset.shopAddress || '';
                                        self.getDirections(shopLat, shopLng, shopAddress);
                                    };
                                }
                            }
                        });
                    });
                }catch(e){
                    console.error('Error plotting shops:', e);
                }
            },
            getDirections(shopLat, shopLng, shopAddress) {
                if (!this.userLocation || !this.userLocation.lat || !this.userLocation.lng) {
                    // Request location if not available
                    if (navigator.geolocation) {
                        Notiflix.Confirm.show(
                            'Getting Your Location',
                            'Please allow location access to get directions',
                            'Allow',
                            'Cancel',
                            () => {
                                navigator.geolocation.getCurrentPosition((pos) => {
                                    this.userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                                    this.openDirections(shopLat, shopLng, shopAddress);
                                }, (error) => {
                                    Notiflix.Report.failure('Error', 'Unable to get your location. Please enable location services.', 'OK');
                                });
                            },
                            () => {}
                        );
                    } else {
                        Notiflix.Report.failure('Error', 'Location services not available. Please enter your location manually.', 'OK');
                    }
                    return;
                }
                
                this.openDirections(shopLat, shopLng, shopAddress);
            },
            openDirections(shopLat, shopLng, shopAddress) {
                if (!shopLat || !shopLng || shopLat === 0 || shopLng === 0) {
                    Notiflix.Report.failure('Error', 'Shop location not available', 'OK');
                    return;
                }
                
                const userLat = this.userLocation.lat;
                const userLng = this.userLocation.lng;
                
                // Open Google Maps with directions
                // Format: origin=lat,lng&destination=lat,lng
                const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${shopLat},${shopLng}&travelmode=driving`;
                
                // Open in new tab
                window.open(googleMapsUrl, '_blank');
                
                // Also show route on the map if Leaflet Routing Machine is available
                this.showRouteOnMap(userLat, userLng, shopLat, shopLng);
            },
            showRouteOnMap(userLat, userLng, shopLat, shopLng) {
                // Try to show route on the map using OSRM (free routing service)
                // This is optional and will work if the service is available
                try {
                    const routeUrl = `https://router.project-osrm.org/route/v1/driving/${userLng},${userLat};${shopLng},${shopLat}?overview=full&geometries=geojson`;
                    
                    fetch(routeUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                                const route = data.routes[0];
                                const routeCoordinates = route.geometry.coordinates.map(coord => [coord[1], coord[0]]); // Convert [lng, lat] to [lat, lng]
                                
                                // Remove existing route if any
                                if (this.routeLayer) {
                                    this.map.removeLayer(this.routeLayer);
                                }
                                
                                // Add route polyline
                                this.routeLayer = L.polyline(routeCoordinates, {
                                    color: '#3388ff',
                                    weight: 5,
                                    opacity: 0.7
                                }).addTo(this.map);
                                
                                // Fit map to show entire route
                                this.map.fitBounds(this.routeLayer.getBounds(), { padding: [50, 50] });
                                
                                // Show route info
                                const distance = (route.distance / 1000).toFixed(2); // Convert to km
                                const duration = Math.round(route.duration / 60); // Convert to minutes
                                
                                Notiflix.Report.success('Route Displayed', `Distance: ${distance} km\nEstimated Time: ${duration} minutes`, 'OK');
                            }
                        })
                        .catch(error => {
                            console.log('Route service not available, using Google Maps only');
                        });
                } catch (error) {
                    console.log('Error showing route on map:', error);
                }
            },
            async viewShop(shopId, shopName, shopLat = null, shopLng = null) {
                this.selectedShop = shopId;
                this.loadingShop = true;
                
                // If coordinates are provided, pan map immediately
                if (shopLat && shopLng && this.map) {
                    this.map.setView([Number(shopLat), Number(shopLng)], 15, {
                        animate: true,
                        duration: 0.5
                    });
                    // Switch to map section to show the shop location
                    this.section = 'map';
                }
                
                try {
                    const response = await fetch(`../../backend/api/shop-homepage.php?shop_id=${shopId}`);
                    const data = await response.json();
                    if (data.success) {
                        this.selectedShopData = data;
                        
                        // Pan map to shop location if coordinates are available
                        if (this.map && data.shop.latitude && data.shop.longitude) {
                            const lat = Number(data.shop.latitude);
                            const lng = Number(data.shop.longitude);
                            if (lat && lng) {
                                this.map.setView([lat, lng], 15, {
                                    animate: true,
                                    duration: 0.5
                                });
                                
                                // Highlight the shop marker (open its popup)
                                this.markersLayer.eachLayer((layer) => {
                                    if (layer instanceof L.Marker) {
                                        const markerLat = layer.getLatLng().lat;
                                        const markerLng = layer.getLatLng().lng;
                                        // Check if this marker is close to the shop location (within 0.001 degrees)
                                        if (Math.abs(markerLat - lat) < 0.001 && Math.abs(markerLng - lng) < 0.001) {
                                            layer.openPopup();
                                        }
                                    }
                                });
                            }
                        }
                        
                        // Scroll to the shop details section
                        setTimeout(() => {
                            const shopDetailElement = document.querySelector('[x-show="selectedShop"]');
                            if (shopDetailElement) {
                                shopDetailElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        }, 100);
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Failed to load shop data', 'OK');
                        this.selectedShop = null;
                    }
                } catch (error) {
                    console.error('Error loading shop data:', error);
                    Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                    this.selectedShop = null;
                } finally {
                    this.loadingShop = false;
                }
            },
            getShopAvatar(shop) {
                // Priority: 1. Shop owner's avatar, 2. Shop logo, 3. Generated avatar from shop name
                let avatarUrl = shop.owner_avatar_url || shop.logo || null;
                
                if (avatarUrl) {
                    // If it's already a full URL (http/https), return as is
                    if (avatarUrl.startsWith('http://') || avatarUrl.startsWith('https://')) {
                        return avatarUrl;
                    }
                    
                    // Normalize relative path - remove leading slash if present
                    let cleanPath = avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl;
                    
                    // From frontend/customer/customer_dashboard.php to frontend/uploads/avatars/...
                    // We need: ../uploads/avatars/...
                    if (cleanPath.startsWith('uploads/')) {
                        return '../' + cleanPath;
                    } else {
                        return '../' + cleanPath;
                    }
                }
                
                // Fallback to generated avatar from shop name
                return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(shop.shop_name) + '&size=120&background=4f46e5&color=fff';
            },
            getItemImageUrl(imageUrl) {
                if (!imageUrl || !imageUrl.trim()) {
                    return '';
                }
                
                // If it's already a full URL (http/https), return as is
                if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
                    return imageUrl;
                }
                
                // Remove leading slash if present
                let cleanPath = imageUrl.startsWith('/') ? imageUrl.substring(1) : imageUrl;
                
                // From frontend/customer/customer_dashboard.php to frontend/uploads/shop_items/image.jpg
                // We need: ../uploads/shop_items/image.jpg
                // The image_url in DB is stored as: uploads/shop_items/filename.jpg
                
                if (cleanPath.startsWith('uploads/')) {
                    // Path already has uploads/ prefix - just add ../ to go up from customer/ to frontend/
                    return '../' + cleanPath;
                } else if (cleanPath.startsWith('shop_items/')) {
                    // Path has shop_items/ but missing uploads/ prefix
                    return '../uploads/' + cleanPath;
                } else if (!cleanPath.includes('/')) {
                    // Just a filename, assume it's in shop_items
                    return '../uploads/shop_items/' + cleanPath;
                } else {
                    // Some other path structure, try relative
                    return '../' + cleanPath;
                }
            },
            async loadMyBookings(){
                try{
                    this.isRefreshing = true;
                    const res = await fetch('customer_bookings.php', { 
                        cache: 'no-store',
                        headers: {
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Pragma': 'no-cache',
                            'Expires': '0'
                        }
                    });
                    const data = await res.json();
                    if(data && data.success){
                        const oldBookingsCount = this.bookings.length;
                        this.bookings = Array.isArray(data.bookings) ? data.bookings : [];
                        this.lastRefreshTime = new Date().toLocaleTimeString();
                        
                        // Log refresh activity
                        console.log(`Customer: Bookings refreshed at ${this.lastRefreshTime}. Count: ${oldBookingsCount} → ${this.bookings.length}`);
                        
                        this.maybeToastReschedule(this.bookings);
                    } else {
                        console.error('Customer: Failed to load bookings:', data.error);
                        this.bookings = [];
                    }
                }catch(e){ 
                    console.error('Customer: Error loading bookings:', e);
                    this.bookings = []; 
                } finally {
                    this.isRefreshing = false;
                }
            },
            maybeToastReschedule(list){
                const accepted = list.find(b=>b.reschedule_status==='accepted');
                const declined = list.find(b=>b.reschedule_status==='declined');
                if(accepted){ Notiflix.Report.success('Reschedule accepted', accepted.reschedule_new_at ? new Date(accepted.reschedule_new_at).toLocaleString() : '', 'OK'); }
                else if(declined){ Notiflix.Report.info('Reschedule declined', '', 'OK'); }
            },
            getBookingStatusInfo(status) {
                const statusMap = {
                    'pending_review': {
                        icon: 'fas fa-search',
                        label: 'Pending Review',
                        badgeClass: 'bg-primary text-white'
                    },
                    'awaiting_confirmation': {
                        icon: 'fas fa-exclamation-circle',
                        label: 'Awaiting Confirmation',
                        badgeClass: 'bg-warning text-dark'
                    },
                    'confirmed': {
                        icon: 'fas fa-hourglass-half',
                        label: 'Confirmed',
                        badgeClass: 'bg-info text-white'
                    },
                    'active': {
                        icon: 'fas fa-check-circle',
                        label: 'Active Bookings',
                        badgeClass: 'bg-success text-white'
                    },
                    'rejected': {
                        icon: 'fas fa-ban',
                        label: 'Rejected',
                        badgeClass: 'bg-danger text-white'
                    },
                    'cancelled': {
                        icon: 'fas fa-times-circle',
                        label: 'Cancelled',
                        badgeClass: 'bg-secondary text-white'
                    },
                    'completed': {
                        icon: 'fas fa-check-double',
                        label: 'Completed',
                        badgeClass: 'bg-success text-white'
                    }
                };
                return statusMap[status] || {
                    icon: 'fas fa-list',
                    label: 'Select Status',
                    badgeClass: 'bg-secondary text-white'
                };
            },
            getBookingCount(status) {
                if (status === 'pending_review') {
                    return this.bookings.filter(b => b.status === 'pending_review').length;
                } else if (status === 'awaiting_confirmation') {
                    return this.bookings.filter(b => b.status === 'awaiting_customer_confirmation').length;
                } else if (status === 'confirmed') {
                    return this.bookings.filter(b => b.status === 'confirmed_by_customer').length;
                } else if (status === 'active') {
                    return this.bookings.filter(b => ['approved', 'assigned', 'in_progress'].includes(b.status)).length;
                } else if (status === 'rejected') {
                    return this.bookings.filter(b => b.status === 'rejected').length;
                } else if (status === 'cancelled') {
                    return this.bookings.filter(b => ['cancelled', 'cancelled_by_customer'].includes(b.status)).length;
                } else if (status === 'completed') {
                    return this.bookings.filter(b => b.status === 'completed').length;
                }
                return 0;
            },
            formatEstimatedTime(timeDays) {
                const days = parseFloat(timeDays || 0);
                if (days === 0) return 'Not specified';
                if (days < 1) {
                    const hours = Math.round(days * 24);
                    return hours === 1 ? '1 hour' : `${hours} hours`;
                } else if (days === 1) {
                    return '1 day';
                } else {
                    // For values >= 1, show days with hours if there's a fractional part
                    const wholeDays = Math.floor(days);
                    const fractionalDays = days - wholeDays;
                    if (fractionalDays > 0) {
                        const hours = Math.round(fractionalDays * 24);
                        if (hours > 0) {
                            return wholeDays === 1 
                                ? `1 day, ${hours} ${hours === 1 ? 'hour' : 'hours'}`
                                : `${wholeDays} days, ${hours} ${hours === 1 ? 'hour' : 'hours'}`;
                        }
                    }
                    return `${days} days`;
                }
            },
            closeDropdown(dropdownId) {
                const dropdownElement = document.getElementById(dropdownId);
                if (dropdownElement) {
                    const dropdown = bootstrap.Dropdown.getInstance(dropdownElement);
                    if (dropdown) {
                        dropdown.hide();
                    }
                }
            },
            // Input validation functions to prevent hacking attempts
            sanitizeInput(input, fieldType) {
                if (!input) return '';
                
                // Define allowed characters based on field type
                let allowedPattern;
                if (fieldType === 'device') {
                    // Device type: letters, numbers, spaces, and safe punctuation (., -, _)
                    allowedPattern = /[^a-zA-Z0-9\s.\-_]/g;
                } else if (fieldType === 'text') {
                    // Text fields: letters, numbers, spaces, and safe punctuation
                    // Allow: letters, numbers, spaces, ., ,, -, _, :, ?, !, newlines
                    // Block dangerous characters: < > { } [ ] ( ) ; ' " ` \ / | & * % $ # @ ~ ^
                    allowedPattern = /[<>{}[\]();'"`\\/|&*%$#@~^]/g;
                } else {
                    // Default: only alphanumeric and spaces
                    allowedPattern = /[^a-zA-Z0-9\s]/g;
                }
                
                // Remove dangerous characters
                return input.replace(allowedPattern, '');
            },
            preventSpecialChars(event, fieldType) {
                const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'];
                if (allowedKeys.includes(event.key)) return;
                
                // Allow Ctrl/Cmd + A/C/V/X/Z
                if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x', 'z'].includes(event.key.toLowerCase())) return;
                
                // Define allowed characters based on field type
                let allowedPattern;
                if (fieldType === 'device') {
                    // Device type: letters, numbers, spaces, and safe punctuation
                    allowedPattern = /^[a-zA-Z0-9\s.\-_]$/;
                } else if (fieldType === 'text') {
                    // Text fields: letters, numbers, spaces, and safe punctuation
                    // Allow: letters, numbers, spaces, ., ,, -, _, :, ?, !, newlines
                    // Block: < > { } [ ] ( ) ; ' " ` \ / | & * % $ # @ ~ ^
                    allowedPattern = /^[a-zA-Z0-9\s.,\-_?!:\n\r]$/;
                } else {
                    // Default: only alphanumeric and spaces
                    allowedPattern = /^[a-zA-Z0-9\s]$/;
                }
                
                // Block characters that don't match the allowed pattern
                if (!allowedPattern.test(event.key)) {
                    event.preventDefault();
                    // Show a brief warning
                    if (event.key.length === 1) {
                        const input = event.target;
                        input.style.borderColor = '#dc3545';
                        setTimeout(() => {
                            input.style.borderColor = '';
                        }, 500);
                    }
                }
            },
            handlePaste(event, fieldType) {
                event.preventDefault();
                const paste = (event.clipboardData || window.clipboardData).getData('text');
                const filtered = this.sanitizeInput(paste, fieldType);
                
                // Insert filtered content at cursor position
                const input = event.target;
                const start = input.selectionStart || 0;
                const end = input.selectionEnd || input.value.length;
                const currentValue = input.value || '';
                const newValue = currentValue.substring(0, start) + filtered + currentValue.substring(end);
                
                // Update the input value
                input.value = newValue;
                
                // Update Alpine.js model based on field
                if (input.getAttribute('x-model')) {
                    const modelPath = input.getAttribute('x-model');
                    if (modelPath === 'booking.device_type') {
                        this.booking.device_type = newValue;
                    } else if (modelPath === 'booking.issue_description') {
                        this.booking.issue_description = newValue;
                    } else if (modelPath === 'booking.description') {
                        this.booking.description = newValue;
                    }
                }
                
                // Set cursor position after the inserted text
                try {
                    const newCursorPos = start + filtered.length;
                    input.setSelectionRange(newCursorPos, newCursorPos);
                } catch (e) {
                    input.focus();
                }
                
                // Show notification if content was filtered
                if (paste !== filtered) {
                    const removedChars = paste.length - filtered.length;
                    if (removedChars > 0) {
                        // Brief visual feedback
                        input.style.borderColor = '#ffc107';
                        setTimeout(() => {
                            input.style.borderColor = '';
                        }, 1000);
                    }
                }
            },
            canReview(b){
                return String(b.status) === 'completed';
            },
            async loadNotifications(){
                try{ const r = await fetch('../auth/notifications.php', { cache: 'no-store' }); const d = await r.json(); if(d.success){ this.notifications = d.notifications||[]; this.unreadCount = d.unread_count || 0; } }catch(e){}
            },
            openReviewModal(b){
                this.selectedBooking = b;
                this.reviewForm = { rating: 0, comment: '' };
                const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
                modal.show();
            },
            async submitReview(){
                if (!this.selectedBooking || !this.reviewForm.rating) {
                    Notiflix.Report.warning('Error', 'Please select a rating', 'OK');
                    return;
                }
                
                this.isSubmittingReview = true;
                
                
                try {
                    const response = await fetch('../../backend/api/submit-review.php', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            booking_id: this.selectedBooking.id,
                            rating: this.reviewForm.rating,
                            comment: this.reviewForm.comment
                        })
                    });
                    
                    const data = await response.json();
                    
                    
                    if (data.success) {
                        // Update the booking to show as reviewed
                        const bookingIndex = this.bookings.findIndex(b => b.id === this.selectedBooking.id);
                        if (bookingIndex !== -1) {
                            this.bookings[bookingIndex].reviewed = true;
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                        modal.hide();
                        
                        // Show success message
                        Notiflix.Report.success('Review Submitted!', `Thank you for rating ${data.technician_name || 'the technician'}. Your feedback helps improve our service.`, 'OK');
                        
                        // Reset form
                        this.reviewForm = { rating: 0, comment: '' };
                        this.selectedBooking = null;
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Failed to submit review', 'OK');
                    }
                } catch (error) {
                    console.error('Error submitting review:', error);
                    Notiflix.Report.failure('Error', 'Network error occurred. Please try again.', 'OK');
                } finally {
                    this.isSubmittingReview = false;
                }
            },
            async loadAddresses(){
                try{ const r = await fetch('addresses.php'); const d = await r.json(); if(d.success){ this.addresses = d.addresses||[]; } }catch(e){}
            },
            async addAddress(){
                const label = this.$refs.addrLabel?.value?.trim() || '';
                const line1 = this.$refs.addrLine1?.value?.trim() || '';
                const city = this.$refs.addrCity?.value?.trim() || '';
                if(!line1){ Notiflix.Report.warning('Address required', '', 'OK'); return; }
                try{ const r = await fetch('addresses.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ label, line1, city }) }); const d = await r.json(); if(d.success){ this.addresses.push(d.address); if(this.$refs.addrLabel) this.$refs.addrLabel.value=''; if(this.$refs.addrLine1) this.$refs.addrLine1.value=''; if(this.$refs.addrCity) this.$refs.addrCity.value=''; } else { Notiflix.Report.failure('Error', d.error||'Failed', 'OK'); } }catch(e){ 
                    console.error('Add address error:', e);
                    Notiflix.Report.failure('Error', e.message || 'Network error occurred', 'OK'); 
                }
            },
            async deleteAddress(id){
                try{ const r = await fetch('addresses.php?id='+encodeURIComponent(id), { method:'DELETE' }); const d = await r.json(); if(d.success){ this.addresses = this.addresses.filter(a=>a.id!==id); } }catch(e){}
            },
            getStepIndex(status){ return Math.max(0, this.steps.indexOf(status||'requested')); },
            toggleTimeline(id){ this.openTimelineId = (this.openTimelineId===id? null : id); },
            timelineHtml(b){
                const idx = this.getStepIndex(b.status);
                return this.steps.map((s,i)=>{
                    const active = i<=idx;
                    const label = s.replace('_',' ').replace('_',' ');
                    return `<div class=\"d-flex align-items-center mb-2\">
                        <span class=\"badge ${active?'bg-primary':'bg-secondary'} me-2\">${i+1}</span>
                        <span>${label}</span>
                    </div>`;
                }).join('');
            },
            openTimelineModal(booking){
                const idx = this.getStepIndex(booking.status);
                const html = this.steps.map((s,i)=>{
                    const active = i<=idx;
                    const label = s.replace('_',' ').replace('_',' ');
                    return `<div class="d-flex align-items-center mb-2">
                        <span class="badge ${active?'bg-primary':'bg-secondary'} me-2">${i+1}</span>
                        <span>${label}</span>
                    </div>`;
                }).join('');
                Notiflix.Report.info('Booking Timeline', html.replace(/<[^>]*>/g, ''), 'Close');
            },
            rebook(b){
                this.section = 'booking';
                this.booking = { service: b.service || '', date: '', time_slot: '', description: b.description || '', rebook_of: b.id };
                if (b.shop_id) {
                    this.booking.shop_owner_id = Number(b.shop_id);
                    this.loadShopServices();
                } else {
                    // fallback: try to select by name
                    const shop = this.shops.find(s=>String(s.shop_name).toLowerCase()===String(b.shop_name).toLowerCase());
                    if (shop) { this.booking.shop_owner_id = Number(shop.id); this.loadShopServices(); }
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            async promptReschedule(){
                // Select one of the approved/assigned bookings to reschedule
                const approved = this.bookings.filter(b=>['approved','assigned','in_progress'].includes(b.status));
                if(approved.length===0){ Notiflix.Report.info('No eligible booking','You have no approved bookings to reschedule','OK'); return; }
                
                // Populate booking select
                const bookingSelect = document.getElementById('reschedule-booking-select');
                if(bookingSelect) {
                    bookingSelect.innerHTML = '<option value="">Choose a booking</option>';
                    approved.forEach(b => {
                        const option = document.createElement('option');
                        option.value = b.id;
                        option.textContent = `${b.shop_name} • ${b.date ? b.date + ' ' + (b.time_slot||'') : 'Not scheduled'} • ${b.service}`;
                        bookingSelect.appendChild(option);
                    });
                }
                
                // Reset form
                document.getElementById('reschedule-date').value = '';
                document.getElementById('reschedule-time').value = '';
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
                modal.show();
            },
            async submitReschedule(){
                const bookingId = document.getElementById('reschedule-booking-select').value;
                const date = document.getElementById('reschedule-date').value;
                const time = document.getElementById('reschedule-time').value;
                
                if(!bookingId || !date || !time) {
                    Notiflix.Report.failure('Error', 'Please fill in all fields', 'OK');
                    return;
                }
                
                try{
                    const res = await fetch('booking_reschedule_request.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ booking_id: Number(bookingId), date, time_slot: time }) });
                    const data = await res.json();
                    if(data.success){ 
                        Notiflix.Report.success('Sent','Reschedule request sent to shop','OK'); 
                        this.loadMyBookings();
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('rescheduleModal'));
                        modal.hide();
                    }
                    else { Notiflix.Report.failure('Error', data.error||'Failed to request reschedule','OK'); }
                }catch(e){ 
                    console.error('Reschedule request error:', e);
                    Notiflix.Report.failure('Error', e.message || 'Network error occurred', 'OK'); 
                }
            },
            async loadShops(){
                try {
                    const res = await fetch('../admin/shops_list.php');
                    const data = await res.json();
                    if(data.success){ this.shops = data.shops || []; }
                } catch(e) {}
            },
            async loadShopServices(serviceNameToSelect = null){
                console.log('Loading shop services for shop ID:', this.booking.shop_owner_id, 'Service to select:', serviceNameToSelect);
                if(!this.booking.shop_owner_id){
                    this.shopServices = [];
                    this.booking.service = '';
                    this.loadingServices = false;
                    this.servicesLoaded = false;
                    this.calendarDays = [];
                    console.log('No shop selected, clearing services');
                    return;
                }
                this.loadingServices = true;
                this.servicesLoaded = false;
                try {
                    const res = await fetch(`../shop/shop_services.php?shop_owner_id=${encodeURIComponent(this.booking.shop_owner_id)}`);
                    const data = await res.json();
                    console.log('Services API response:', data);
                    if(data.success){
                        this.shopServices = data.services || [];
                        this.servicesLoaded = true; // Mark as loaded
                        // If a service name is provided, select it; otherwise reset
                        if(serviceNameToSelect) {
                            // Wait a moment for Alpine to update the DOM
                            await new Promise(resolve => setTimeout(resolve, 50));
                            this.booking.service = serviceNameToSelect;
                            console.log('Set service to:', serviceNameToSelect);
                        } else {
                            this.booking.service = ''; // Reset service selection
                        }
                        console.log('Loaded services:', this.shopServices);
                        
                        // Load calendar view for current month
                        const today = new Date();
                        this.loadCalendarView(today.getMonth() + 1, today.getFullYear(), null);
                    } else {
                        console.error('Failed to load services:', data.error);
                        this.shopServices = [];
                        this.servicesLoaded = true; // Mark as loaded even if empty
                    }
                } catch(e) {
                    console.error('Error loading services:', e);
                    this.shopServices = [];
                    this.servicesLoaded = true; // Mark as loaded even on error
                } finally {
                    this.loadingServices = false;
                }
            },
            async bookShop(shopId) {
                // Switch to booking section
                this.section = 'booking';
                
                // Ensure shops are loaded first
                if (this.shops.length === 0) {
                    await this.loadShops();
                }
                
                // Set the shop owner ID (convert to number to match dropdown)
                this.booking.shop_owner_id = Number(shopId);
                
                // Wait a moment for Alpine.js to update the DOM and trigger @change
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Load services (without pre-selecting a service)
                await this.loadShopServices();
                
                // Scroll to top
                window.scrollTo({top: 0, behavior: 'smooth'});
            },
            async bookService(shopId, serviceName) {
                console.log('bookService called with shopId:', shopId, 'serviceName:', serviceName);
                
                // Switch to booking section
                this.section = 'booking';
                
                // Ensure shops are loaded first
                if (this.shops.length === 0) {
                    await this.loadShops();
                }
                
                // Convert shopId to number to match dropdown format
                const shopIdNum = Number(shopId);
                console.log('Setting shop_owner_id to:', shopIdNum);
                
                // Set the shop owner ID (convert to number to match dropdown)
                this.booking.shop_owner_id = shopIdNum;
                
                // Wait a moment for Alpine.js to update the DOM and trigger @change
                await new Promise(resolve => setTimeout(resolve, 200));
                
                // Verify shop is set
                console.log('Shop owner ID after setting:', this.booking.shop_owner_id);
                
                // Load services and auto-select the service
                await this.loadShopServices(serviceName);
                
                // Double-check service is set after loading
                console.log('Service after loading:', this.booking.service);
                
                // Scroll to top
                window.scrollTo({top: 0, behavior: 'smooth'});
            },
            async loadCalendarAvailability(){
                if(!this.booking.shop_owner_id || !this.booking.date){
                    this.availableSlots = [];
                    this.booking.time_slot = '';
                    return;
                }
                
                console.log('Loading calendar availability for shop:', this.booking.shop_owner_id, 'date:', this.booking.date);
                
                try {
                    const date = new Date(this.booking.date);
                    const month = date.getMonth() + 1;
                    const year = date.getFullYear();
                    
                    const res = await fetch(`calendar_availability.php?shop_id=${encodeURIComponent(this.booking.shop_owner_id)}&month=${month}&year=${year}`);
                    const data = await res.json();
                    
                    if(data.success && data.availability[this.booking.date]){
                        const now = new Date();
                        const selectedDate = new Date(this.booking.date);
                        this.availableSlots = data.time_slots.map(time => {
                            const base = data.availability[this.booking.date][time];
                            let available = base.available;
                            if (selectedDate.toDateString() === now.toDateString()) {
                                const parts = time.split(':');
                                const hh = Number(parts[0]||0);
                                const mm = Number(parts[1]||0);
                                const slotDate = new Date(selectedDate);
                                slotDate.setHours(hh, mm, 0, 0);
                                if (slotDate <= now) available = false;
                            }
                            return { time: time, available: available, booked_count: base.booked_count };
                        });
                        this.booking.time_slot = ''; // Reset time slot selection
                        console.log('Loaded availability for', this.booking.date, ':', this.availableSlots);
                    } else {
                        console.error('Failed to load availability:', data.error);
                        this.availableSlots = [];
                    }
                    
                    // Also load calendar view
                    this.loadCalendarView(month, year, data);
                } catch(e) {
                    console.error('Error loading availability:', e);
                    this.availableSlots = [];
                }
            },
            async loadCalendarView(month, year, availabilityData) {
                if(!this.booking.shop_owner_id) return;
                
                try {
                    const today = new Date();
                    const currentMonth = today.getMonth() + 1;
                    const currentYear = today.getFullYear();
                    const currentDay = today.getDate();
                    
                    const firstDayOfMonth = new Date(year, month - 1, 1);
                    const startWeekday = firstDayOfMonth.getDay(); // 0=Sun
                    const daysInMonth = new Date(year, month, 0).getDate();
                    this.calendarDays = [];
                    this.leadingBlanks = Array.from({length: startWeekday}, (_, i) => i);
                    
                    for(let day = 1; day <= daysInMonth; day++) {
                        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        const date = new Date(year, month - 1, day);
                        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                        
                        let bookedSlots = 0;
                        if(availabilityData && availabilityData.availability[dateStr]) {
                            bookedSlots = Object.values(availabilityData.availability[dateStr])
                                .filter(slot => !slot.available).length;
                        }
                        
                        const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
                        this.calendarDays.push({
                            date: dateStr,
                            dayNumber: day,
                            dayName: dayName,
                            isToday: (month === currentMonth && year === currentYear && day === currentDay),
                            isPast: isPast,
                            hasBookings: bookedSlots > 0,
                            bookedSlots: bookedSlots
                        });
                    }
                } catch(e) {
                    console.error('Error loading calendar view:', e);
                }
            },
            async verifyEmail() {
                try {
                    const res = await fetch('../verification/verify-email.php', { method: 'POST' });
                    const data = await res.json();
                    if (data.success) {
                        this.emailVerified = true;
                        Notiflix.Report.success('Verified', 'Your email has been verified.', 'OK');
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Verification failed', 'OK');
                    }
                } catch (e) {
                    Notiflix.Report.failure('Error', 'Network error', 'OK');
                }
            },
            sanitizeCustomerName(event){
                // Remove dangerous characters from customer name
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.form.name = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizeCustomerPhone(event){
                // Only allow numbers, must start with 09, exactly 11 digits
                let value = event.target.value.replace(/[^0-9]/g, ''); // Remove all non-numeric characters
                
                // If it doesn't start with 09, force it to start with 09
                if(value.length > 0 && !value.startsWith('09')){
                    if(value.startsWith('9')){
                        value = '0' + value;
                    } else if(value.startsWith('0') && value.length > 1 && value[1] !== '9'){
                        value = '09' + value.substring(1);
                    } else {
                        value = '09' + value;
                    }
                }
                
                // Limit to 11 digits
                if(value.length > 11){
                    value = value.substring(0, 11);
                }
                
                // If value changed, update and show feedback
                if(this.form.phone !== value){
                    this.form.phone = value;
                    if(event.target.value !== value){
                        event.target.style.borderColor = '#ffc107';
                        setTimeout(() => {
                            event.target.style.borderColor = '';
                        }, 1000);
                    }
                }
            },
            sanitizeCustomerAddress(event){
                // Remove dangerous characters from address
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, parentheses, and forward slashes
                const dangerousChars = /[<>{}[\]();'"`\\|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.form.address = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            async updateProfile() {
                try {
                    // Validate and sanitize inputs before sending
                    const name = (this.form.name || '').trim();
                    const phone = (this.form.phone || '').trim();
                    const address = (this.form.address || '').trim();
                    
                    // Check for dangerous characters
                    const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/;
                    
                    if(!name){
                        Notiflix.Report.failure('Error', 'Name is required', 'OK');
                        return;
                    }
                    
                    if(name && dangerousChars.test(name)){
                        Notiflix.Report.failure('Error', 'Name contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ ! are not allowed', 'OK');
                        return;
                    }
                    
                    if(phone && !/^09[0-9]{9}$/.test(phone)){
                        Notiflix.Report.failure('Error', 'Phone number must start with 09 and be exactly 11 digits', 'OK');
                        return;
                    }
                    
                    if(address && dangerousChars.test(address)){
                        Notiflix.Report.failure('Error', 'Address contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ ! are not allowed', 'OK');
                        return;
                    }
                    
                    const res = await fetch('profile_update.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        Notiflix.Report.success('Saved', 'Profile updated successfully', 'OK');
                        this.form.password = '';
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Update failed', 'OK');
                    }
                } catch (e) {
                    console.error('Profile update error:', e);
                    Notiflix.Report.failure('Error', e.message || 'Network error occurred', 'OK');
                }
            },
            sanitizePassword(event, inputRef){
                // Remove dangerous characters that could be used for SQL injection
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '');
                if(originalValue !== sanitized){
                    inputRef.value = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            async submitChangePassword(oldPwd, newPwd, confirmPwd){
                // Validate password length
                if(newPwd.length < 6 || newPwd.length > 128){
                    Notiflix.Report.failure('Error', 'Password must be between 6 and 128 characters', 'OK');
                    return;
                }
                
                // Check for dangerous characters in password
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^]/;
                if(dangerousChars.test(newPwd)){
                    Notiflix.Report.failure('Error', 'Password contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ are not allowed', 'OK');
                    return;
                }
                
                if(newPwd !== confirmPwd){ 
                    Notiflix.Report.failure('Error','Passwords do not match','OK'); 
                    return; 
                }
                
                try{
                    const res = await fetch('../auth/change_password.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, credentials: 'same-origin', body: JSON.stringify({ old_password: oldPwd, new_password: newPwd })});
                    const data = await res.json();
                    if(data.success){ 
                        Notiflix.Report.success('Updated','Password changed','OK'); 
                        this.showChangePassword = false; // Hide the form after success
                    }
                    else { Notiflix.Report.failure('Error', data.error||'Failed to change password','OK'); }
                }catch(e){ Notiflix.Report.failure('Error','Network error','OK'); }
            },
            handleFileSelect(event) {
                const file = event.target.files && event.target.files[0];
                if (file) {
                    // Validate file size (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        Notiflix.Report.failure('File Too Large', 'Please select an image smaller than 2MB', 'OK');
                        event.target.value = '';
                        this.devicePhotoName = '';
                        return;
                    }
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        Notiflix.Report.failure('Invalid File Type', 'Please select a JPG, PNG, or WebP image', 'OK');
                        event.target.value = '';
                        this.devicePhotoName = '';
                        return;
                    }
                    this.devicePhotoName = file.name;
                } else {
                    this.devicePhotoName = '';
                }
            },
            async createBooking() {
                if (!this.emailVerified) {
                    Notiflix.Report.warning('Email Required', 'Please verify your email to make a booking.', 'OK');
                    return;
                }
                
                // Validate required fields (date and time_slot are no longer required - schedule will be selected during confirmation)
                if(!this.booking.shop_owner_id || !this.booking.service || !this.booking.device_type || 
                   !this.booking.issue_description) {
                    Notiflix.Report.failure('Error', 'Please fill in all required fields', 'OK');
                    return;
                }
                
                console.log('Creating booking for diagnosis:', this.booking);
                
                try {
                    // Create FormData for file upload support
                    const formData = new FormData();
                    
                    // Add booking data as JSON (no date/time_slot - schedule will be selected during confirmation)
                    formData.append('booking_data', JSON.stringify({
                        shop_owner_id: this.booking.shop_owner_id,
                        service: this.booking.service,
                        device_type: this.booking.device_type,
                        issue_description: this.booking.issue_description,
                        description: this.booking.description
                    }));
                    
                    // Add device photo if selected
                    const photoInput = this.$refs.devicePhoto;
                    if(photoInput && photoInput.files && photoInput.files[0]) {
                        formData.append('device_photo', photoInput.files[0]);
                    }
                    
                    const res = await fetch('booking_create_v2.php', {
                        method: 'POST',
                        body: formData // Send as multipart/form-data
                    });
                    
                    // Use the enhanced response handler
                    const data = await handleApiResponse(res, false, false); // Don't auto-show messages
                    
                    if (data.error) {
                        // Show validation error message
                        Notiflix.Report.failure('Error', data.message || 'Booking failed', 'OK');
                        return;
                    }
                    
                    // Show success message
                    Notiflix.Report.success('Success', data.message || 'Booking submitted for diagnosis.', 'OK');
                    
                    // Reset form
                    this.booking = { service: '', device_type: '', issue_description: '', description: '', shop_owner_id: null };
                    if(photoInput) photoInput.value = '';
                    this.devicePhotoName = '';
                    this.loadMyBookings();
                } catch (e) {
                    Notiflix.Report.failure('Error', 'Network error', 'OK');
                }
            },
            async confirmBooking(booking) {
                // First, show booking details
                const bookingDetails = `Device: ${booking.device_type || 'N/A'}\nService: ${booking.service}\nEstimated Cost: ₱${Number(booking.estimated_cost||0).toFixed(2)}\nEstimated Time: ${this.formatEstimatedTime(booking.estimated_time_days)}${booking.diagnostic_notes ? '\n\n' + booking.diagnostic_notes : ''}`;
                
                Notiflix.Confirm.show(
                    'Confirm Booking?',
                    bookingDetails,
                    'Select Schedule',
                    'Not Yet',
                    () => {
                        this.proceedToScheduleSelection(booking);
                    },
                    () => {}
                );
            },
            async proceedToScheduleSelection(booking) {
                // Store booking for later use
                this.currentBookingForSchedule = booking;
                
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowYmd = tomorrow.toISOString().slice(0, 10);
                
                // Set min date
                const dateInput = document.getElementById('schedule-date');
                if(dateInput) {
                    dateInput.min = tomorrowYmd;
                    dateInput.value = '';
                }
                
                // Reset time select
                const timeSelect = document.getElementById('schedule-time');
                if(timeSelect) {
                    timeSelect.innerHTML = '<option value="">Select date first</option>';
                    timeSelect.disabled = true;
                }
                
                // Show booking details
                document.getElementById('schedule-device').textContent = booking.device_type || 'N/A';
                document.getElementById('schedule-service').textContent = booking.service || 'N/A';
                document.getElementById('schedule-cost').textContent = `₱${Number(booking.estimated_cost||0).toFixed(2)}`;
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                modal.show();
            },
            async loadScheduleSlots(){
                if(!this.currentBookingForSchedule) return;
                
                const dateInput = document.getElementById('schedule-date');
                const timeSelect = document.getElementById('schedule-time');
                const loadingMsg = document.getElementById('schedule-loading-slots');
                const noSlotsMsg = document.getElementById('schedule-no-slots');
                
                const selectedDate = dateInput.value;
                if (!selectedDate) {
                    timeSelect.innerHTML = '<option value="">Select date first</option>';
                    timeSelect.disabled = true;
                    return;
                }
                
                // Reset and show loading
                timeSelect.innerHTML = '<option value="">Loading...</option>';
                timeSelect.disabled = true;
                loadingMsg.classList.remove('d-none');
                noSlotsMsg.classList.add('d-none');
                
                try {
                    const res = await fetch(`booking_availability.php?shop_id=${encodeURIComponent(this.currentBookingForSchedule.shop_id)}&date=${encodeURIComponent(selectedDate)}&booking_id=${encodeURIComponent(this.currentBookingForSchedule.id)}`);
                    const data = await res.json();
                    const slots = data.success ? (data.available_slots || []) : [];
                    
                    // Hide loading
                    loadingMsg.classList.add('d-none');
                    
                    // Update time select
                    if (slots.length === 0) {
                        timeSelect.innerHTML = '<option value="">No slots available</option>';
                        timeSelect.disabled = true;
                        noSlotsMsg.classList.remove('d-none');
                    } else {
                        timeSelect.innerHTML = '<option value="">Select a time slot</option>';
                        slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = slot.time + (slot.available ? '' : ' (Unavailable)');
                            option.disabled = !slot.available;
                            option.style.color = slot.available ? '' : '#999';
                            timeSelect.appendChild(option);
                        });
                        timeSelect.disabled = false;
                        noSlotsMsg.classList.add('d-none');
                    }
                } catch(e) {
                    console.error('Error loading availability:', e);
                    loadingMsg.classList.add('d-none');
                    timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                    timeSelect.disabled = true;
                }
            },
            async confirmSchedule(){
                if(!this.currentBookingForSchedule) return;
                
                const dateInput = document.getElementById('schedule-date');
                const timeSelect = document.getElementById('schedule-time');
                
                const date = dateInput.value;
                const time = timeSelect.value;
                
                if(!date || !time) {
                    Notiflix.Report.failure('Error', 'Please select both date and time slot', 'OK');
                    return;
                }
                
                // Validate date is at least one day in advance
                const selectedDate = new Date(date + 'T00:00:00');
                const today = new Date();
                const tomorrow = new Date(today);
                tomorrow.setDate(today.getDate() + 1);
                tomorrow.setHours(0, 0, 0, 0);
                
                if(selectedDate < tomorrow) {
                    Notiflix.Report.failure('Error', 'Bookings must be made at least one day in advance', 'OK');
                    return;
                }
                
                try {
                    const res = await fetch('booking_customer_confirm.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            booking_id: this.currentBookingForSchedule.id,
                            action: 'confirm',
                            date: date,
                            time_slot: time
                        })
                    });
                    
                    // Use the enhanced response handler
                    const data = await handleApiResponse(res, false, false); // Don't auto-show messages
                    
                    if (data.error) {
                        Notiflix.Report.failure('Error', data.message || 'Failed to confirm booking', 'OK');
                        return;
                    }
                    
                    // Close modal and remove backdrop
                    const modalElement = document.getElementById('scheduleModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if(modal) {
                        modal.hide();
                        // Remove backdrop if it exists
                        setTimeout(() => {
                            const backdrop = document.querySelector('.modal-backdrop');
                            if(backdrop) {
                                backdrop.remove();
                            }
                            // Remove modal-open class from body
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 300);
                    }
                    
                    // Show success message
                    Notiflix.Notify.success(data.message || 'Booking confirmed successfully', {
                        timeout: 3000,
                        position: 'top-right'
                    });
                    
                    // Reload bookings
                    this.loadMyBookings();
                    
                } catch(e) {
                    Notiflix.Report.failure('Error', e.message || 'Network error', 'OK');
                }
            },
            async cancelBookingWithReason(booking) {
                // Store booking for later use
                this.currentBookingForCancel = booking;
                
                // Show booking details
                document.getElementById('cancel-device').textContent = booking.device_type || 'N/A';
                document.getElementById('cancel-cost').textContent = `₱${Number(booking.estimated_cost||0).toFixed(2)}`;
                
                // Reset reason textarea
                const reasonTextarea = document.getElementById('cancel-reason');
                if(reasonTextarea) {
                    reasonTextarea.value = '';
                }
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
                modal.show();
            },
            async confirmCancelBooking(){
                if(!this.currentBookingForCancel) return;
                
                const reasonTextarea = document.getElementById('cancel-reason');
                const reason = reasonTextarea ? reasonTextarea.value.trim() : '';
                
                if(!reason) {
                    Notiflix.Report.failure('Error', 'Cancellation reason is required', 'OK');
                    return;
                }
                
                try {
                    const res = await fetch('booking_customer_confirm.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            booking_id: this.currentBookingForCancel.id,
                            action: 'cancel',
                            cancellation_reason: reason
                        })
                    });
                    
                    // Use the enhanced response handler
                    const data = await handleApiResponse(res, false, false); // Don't auto-show messages
                    
                    if (data.error) {
                        Notiflix.Report.failure('Error', data.message || 'Failed to cancel booking', 'OK');
                        return;
                    }
                    
                    // Close modal and remove backdrop
                    const modalElement = document.getElementById('cancelBookingModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if(modal) {
                        modal.hide();
                        // Remove backdrop if it exists
                        setTimeout(() => {
                            const backdrop = document.querySelector('.modal-backdrop');
                            if(backdrop) {
                                backdrop.remove();
                            }
                            // Remove modal-open class from body
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 300);
                    }
                    
                    // Show success message
                    Notiflix.Notify.success(data.message || 'Booking cancelled successfully', {
                        timeout: 3000,
                        position: 'top-right'
                    });
                    
                    // Reload bookings
                    this.loadMyBookings();
                    
                } catch(e) {
                    Notiflix.Report.failure('Error', e.message || 'Network error', 'OK');
                }
            },
            async logout() {
                Notiflix.Confirm.show(
                    'Logout?',
                    'You will be signed out of your account.',
                    'Logout',
                    'Cancel',
                    () => {
                        fetch('../auth/logout.php', { method: 'POST' })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Notiflix.Report.success('Logged out', '', 'OK', () => {
                                        window.location.href = '../auth/index.php';
                                    });
                                } else {
                                    window.location.href = '../auth/login.html';
                                }
                            })
                            .catch(() => {
                                window.location.href = '../auth/login.html';
                            });
                    },
                    () => {}
                );
            }
        }
    }
    
    // Cleanup polling when page is unloaded
    window.addEventListener('beforeunload', function() {
        const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
        if (alpineComponent && alpineComponent.stopPolling) {
            alpineComponent.stopPolling();
        }
    });
    </script>
    
    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Rate Your Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div x-show="selectedBooking">
                        <div class="mb-3">
                            <label class="form-label">Service: <span x-text="selectedBooking?.service"></span></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Technician: <span x-text="selectedBooking?.technician_name"></span></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating *</label>
                            <div class="rating-stars">
                                <template x-for="i in 5" :key="i">
                                    <i class="fas fa-star rating-star" 
                                       :class="i <= (reviewForm.rating || 0) ? 'text-warning' : 'text-muted'"
                                       @click="reviewForm.rating = i"
                                       style="cursor: pointer; font-size: 1.5rem; margin-right: 0.25rem;"></i>
                                </template>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reviewComment" class="form-label">Comment (Optional)</label>
                            <textarea class="form-control" id="reviewComment" rows="3" 
                                      x-model="reviewForm.comment" 
                                      placeholder="Share your experience..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="submitReview()" :disabled="!reviewForm.rating || isSubmittingReview">
                        <span x-show="!isSubmittingReview">Submit Review</span>
                        <span x-show="isSubmittingReview"><i class="fas fa-spinner fa-spin me-1"></i>Submitting...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Schedule Selection Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Select Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-1"><strong>Device:</strong> <span id="schedule-device"></span></p>
                        <p class="mb-1"><strong>Service:</strong> <span id="schedule-service"></span></p>
                        <p class="mb-3"><strong>Estimated Cost:</strong> <span id="schedule-cost"></span></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Date</strong> <span class="text-danger">*</span></label>
                        <input type="date" id="schedule-date" class="form-control" @change="loadScheduleSlots()" required>
                        <small class="text-muted">Select a date to see available time slots</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Time Slot</strong> <span class="text-danger">*</span></label>
                        <select id="schedule-time" class="form-select" required disabled>
                            <option value="">Select date first</option>
                        </select>
                        <small id="schedule-loading-slots" class="text-muted d-none">
                            <i class="fas fa-spinner fa-spin me-1"></i>Loading available slots...
                        </small>
                        <small id="schedule-no-slots" class="text-danger d-none">No available slots for this date</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="confirmSchedule()">Confirm Booking</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelBookingModalLabel">Cancel Booking?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-1">Device: <strong id="cancel-device"></strong></p>
                        <p class="mb-3">Estimated Cost: <strong id="cancel-cost"></strong></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for cancellation <span class="text-danger">*</span></label>
                        <textarea id="cancel-reason" class="form-control" rows="4" placeholder="Please provide a reason for cancellation..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" @click="confirmCancelBooking()">Yes, Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reschedule Modal -->
    <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><strong>Select Booking</strong> <span class="text-danger">*</span></label>
                        <select id="reschedule-booking-select" class="form-select" required>
                            <option value="">Choose a booking</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>New Date</strong> <span class="text-danger">*</span></label>
                        <input type="date" id="reschedule-date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>New Time</strong> <span class="text-danger">*</span></label>
                        <input type="time" id="reschedule-time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="submitReschedule()">Submit Request</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/pwa-register.js"></script>
</body>
</html>
    
    



