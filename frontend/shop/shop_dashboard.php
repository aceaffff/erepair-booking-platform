<?php
require_once __DIR__ . '/../../backend/config/database.php';

function redirect_to_login() {
    header('Location: ../auth/index.php');
    exit;
}

$token = $_COOKIE['auth_token'] ?? ($_GET['token'] ?? '');
if (!empty($_GET['token'])) {
    setcookie('auth_token', $_GET['token'], time() + 24 * 60 * 60, '/');
}
if (empty($token)) redirect_to_login();

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, u.avatar as avatar_url, u.role, so.id as shop_id, so.shop_name, so.shop_address, so.shop_latitude, so.shop_longitude, so.approval_status FROM users u INNER JOIN sessions s ON u.id = s.user_id LEFT JOIN shop_owners so ON so.user_id = u.id WHERE s.token = ? AND s.expires_at > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'shop_owner') redirect_to_login();

// Check if shop profile exists
if (empty($user['shop_id'])) {
    // Shop owner exists but shop_owners record is missing
    // This can happen if registration was incomplete
    // Show error message and redirect to registration or show helpful message
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Shop Profile Required</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:600px;margin:50px auto;padding:20px;text-align:center;}";
    echo ".error-box{background:#fee;border:2px solid #fcc;padding:20px;border-radius:8px;margin:20px 0;}";
    echo ".btn{display:inline-block;padding:10px 20px;background:#4f46e5;color:white;text-decoration:none;border-radius:5px;margin:10px;}";
    echo "</style></head><body>";
    echo "<div class='error-box'>";
    echo "<h2>Shop Profile Required</h2>";
    echo "<p>Your shop profile is incomplete. Please complete your shop registration to access the dashboard.</p>";
    echo "<p><strong>What to do:</strong></p>";
    echo "<ol style='text-align:left;display:inline-block;'>";
    echo "<li>Log out and complete your shop owner registration</li>";
    echo "<li>Make sure all required documents are uploaded</li>";
    echo "<li>Wait for admin approval if your account is pending</li>";
    echo "</ol>";
    echo "<a href='../auth/index.php?logout=1' class='btn'>Go to Login</a>";
    echo "</div></body></html>";
    exit;
}

function h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERepair Shop Owner - Manage your shop, bookings, and technicians">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ERepair Shop">
    <title>Shop Owner Dashboard - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="../../backend/api/favicon.php">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-generator.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-styles.css" rel="stylesheet">
    <link href="../assets/css/erepair-swal.css" rel="stylesheet">
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-swal.js"></script>
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/erepair-common.js" defer></script>
</head>
<body class="bg-light" x-data="shopDashboard()" x-init="init()">
    <!-- Mobile Sidebar Toggle Button -->
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
        <i class="fas fa-bars" x-show="!sidebarOpen"></i>
        <i class="fas fa-times" x-show="sidebarOpen"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" 
         :class="{ 'show': sidebarOpen }" 
         @click="sidebarOpen = false" 
         x-show="sidebarOpen" 
         x-cloak
         style="display: none; z-index: 1040;"></div>
    
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
        /* Prevent flickering on tab/content switches */
        [x-cloak] { display: none !important; }
        
        /* Ensure Alpine.js x-show switches instantly without transitions */
        .tab-content > div[x-show] {
            transition: none !important;
        }
        
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
        /* Inline form styles for change password */
        .change-password-form {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }
        /* Ensure buttons are always visible */
        
        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .modal-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
        
        /* Modal Panel - Centered */
        .modal-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            width: 90%;
            max-width: 450px;
            max-height: calc(100vh - 40px);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 2001;
            opacity: 0;
            transition: all 0.3s ease;
            overflow-y: auto;
        }
        .modal-panel.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        
        @media (max-width: 640px) {
            .modal-panel {
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0.95);
                width: calc(100% - 20px);
                max-width: none;
                max-height: calc(100vh - 40px);
            }
            .modal-panel.show {
                transform: translate(-50%, -50%) scale(1);
            }
        }
        .btn-group-vertical .btn {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-bottom: 0.25rem;
        }
        .btn-group-vertical .btn:last-child {
            margin-bottom: 0;
        }
        /* Improve booking card hover effects */
        .border.rounded.p-3.mb-3 {
            transition: all 0.2s ease;
        }
        .border.rounded.p-3.mb-3:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        /* Stats card animations */
        .glass-advanced {
            transition: all 0.3s ease;
        }
        .glass-advanced:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        /* Number counter animation */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .h3, .h2, .h4 {
            animation: countUp 0.5s ease-out;
        }
        /* Pulse animation for icons */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .glass-advanced:hover .rounded-circle {
            animation: pulse 1s ease-in-out;
        }
        /* Notification hover effect */
        .list-group-item-action {
            transition: all 0.2s ease;
        }
        .list-group-item-action:hover {
            background-color: rgba(99, 102, 241, 0.05) !important;
            border-left: 3px solid #6366f1;
            padding-left: 1.2rem;
        }
        
        /* Modern Notification Card Styles */
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
        
        /* Pulse animation for unread badge */
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.1);
                opacity: 0.8;
            }
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        /* Space-y utility for notifications */
        .space-y-3 > * + * {
            margin-top: 1rem;
        }
        
        /* Modern Stat Card Hover Effects */
        .modern-stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modern-stat-card:hover .bg-primary.bg-opacity-10,
        .modern-stat-card:hover .bg-success.bg-opacity-10,
        .modern-stat-card:hover [style*="background-color: rgba"] {
            transform: scale(1.1);
            transition: transform 0.3s ease;
        }
        
        /* Space-y utility for shop status items */
        .space-y-3 > * + * {
            margin-top: 0.75rem;
        }
        
        /* Notiflix Confirm Modal Text Styling */
        .notiflix-confirm-title,
        .notiflix-confirm-message {
            color: #000000 !important;
            text-shadow: none !important;
            font-weight: 500;
        }
        
        .notiflix-confirm-title {
            font-size: 1.25rem !important;
            font-weight: 600 !important;
        }
        
        .notiflix-confirm-message {
            font-size: 0.95rem !important;
            color: #333333 !important;
        }
        
        /* Profile Section Enhanced Styles */
        .profile-input {
            transition: all 0.2s ease;
        }
        .profile-input:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            border-color: #6366f1;
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
        .profile-avatar-hover {
            transition: all 0.3s ease;
        }
        .profile-avatar-hover:hover {
            transform: scale(1.05);
        }
        
        /* Profile avatar hover overlay */
        .avatar-overlay {
            background-color: rgba(0,0,0,0);
            transition: background-color 0.3s ease;
        }
        .avatar-container:hover .avatar-overlay {
            background-color: rgba(0,0,0,0.5);
        }
        .avatar-overlay i {
            transition: opacity 0.3s ease;
        }
        .avatar-container:hover .avatar-overlay i {
            opacity: 1 !important;
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
            z-index: 1039;
            pointer-events: none;
        }
        
        .sidebar-overlay.show {
            pointer-events: auto;
        }
        
        /* Main content margin for fixed sidebar */
        .main-content {
            margin-left: 280px;
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
    </style>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar shadow-md min-vh-100 text-white" :class="{ 'open': sidebarOpen }" style="position: fixed; left: 0; width: 280px; background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); top: 0; height: 100vh; overflow-y: auto; z-index: 1050; border-right: 1px solid rgba(99,102,241,.2);" @click.stop>
            <div class="p-4 brand-wrap">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center logo-container shadow-lg" style="width: 52px; height: 52px; box-shadow: 0 4px 15px rgba(99,102,241,.4);">
                        <i class="fas fa-store text-white fs-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl fw-bold m-0" style="letter-spacing:.3px; color: #ffffff; text-shadow: 0 2px 8px rgba(0,0,0,.3);">ERepair</h2>
                        <div class="small" style="color: rgba(255,255,255,.7);">Shop Owner Portal</div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="position-relative">
                            <img :src="avatarUrl" 
                                 class="rounded-circle border border-3 d-block mx-auto shadow-lg" 
                                 style="width:80px;height:80px;object-fit:cover; border-color: rgba(99,102,241,.4) !important; box-shadow: 0 4px 20px rgba(99,102,241,.3);" 
                                 alt="Avatar"
                                 @error="handleAvatarError()"
                                 :data-name="userForm.name">
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
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='technicians' }" @click="section='technicians'; sidebarOpen = false">
                        <i class="fas fa-users-cog me-3"></i><span>Technicians</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='services' }" @click="section='services'; sidebarOpen = false">
                        <i class="fas fa-list me-3"></i><span>Services</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='items' }" @click="section='items'; sidebarOpen = false; loadItems()">
                        <i class="fas fa-box me-3"></i><span>Items for Sale</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='approved' }" @click="section='approved'; approvedTab='pending_review'; sidebarOpen = false">
                        <i class="fas fa-tasks me-3"></i><span>Bookings</span>
                    </button>
                </li>
                
                <li style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(99,102,241,.2);">
                    <button class="w-100 text-start px-4 py-3 logout-btn" @click="logout()">
                        <i class="fas fa-right-from-bracket me-3"></i><span>Logout</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="flex-1 w-100 main-content" style="background-color: #F3F4F6;">
            <div class="container py-4">
                <div x-show="section==='home'" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Welcome Header -->
                    <div class="card border-0 shadow-lg mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(168,85,247,0.1) 100%);">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="fas fa-store text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h2 class="h4 fw-bold mb-1 text-dark">Welcome Back, <?php echo h($user['name']); ?>!</h2>
                                        <p class="text-muted small mb-0">Manage your repair services and track your bookings</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button class="btn btn-primary btn-sm shadow-sm px-4" style="border-radius: 25px; font-weight: 500;" @click="section='approved'; approvedTab='pending_review'" title="Quick Intake">
                                        <i class="fas fa-plus me-2"></i>New Booking
                                    </button>
                                    <span class="badge <?php echo ($user['approval_status'] ?? 'pending')==='approved' ? 'bg-success' : (($user['approval_status'] ?? 'pending')==='rejected' ? 'bg-danger' : 'bg-warning text-dark'); ?> px-3 py-2" style="font-size: 0.85rem; border-radius: 20px;">
                                        <?php echo ($user['approval_status'] ?? 'pending')==='approved' ? '✓ Approved' : ucfirst($user['approval_status'] ?? 'pending'); ?>
                                    </span>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="shopStatusToggle" x-model="shopIsOpen" @change="toggleShopStatus()" style="width: 3em; height: 1.5em; cursor: pointer;">
                                        <label class="form-check-label text-dark fw-semibold ms-2" for="shopStatusToggle" x-text="shopIsOpen ? 'Open' : 'Closed'" style="cursor: pointer;"></label>
                                    </div>
                                </div>
                            </div>
                            <!-- Shop Details in One Line -->
                            <div class="d-flex align-items-center gap-3 flex-wrap" style="border-top: 1px solid rgba(99,102,241,0.1); padding-top: 1rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-store text-primary"></i>
                                    <span class="text-dark fw-semibold"><?php echo h($user['shop_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="text-muted">•</div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                    <span class="text-muted"><?php echo h($user['shop_address'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="text-muted">•</div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-phone text-primary"></i>
                                    <span class="text-muted"><?php echo h($user['phone'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="ms-auto d-flex align-items-center gap-3">
                                    <div class="mb-0" x-show="shopRating.total_reviews > 0">
                                        <div class="d-flex align-items-center gap-2" style="cursor: pointer;" @click="viewShopRatings()" title="Click to view detailed ratings">
                                            <span class="text-warning fs-6" x-text="'⭐'.repeat(Math.floor(shopRating.average_rating))"></span>
                                            <span class="fw-bold text-dark" x-text="shopRating.average_rating.toFixed(1)"></span>
                                            <span class="small text-muted" x-text="'(' + shopRating.total_reviews + ' review' + (shopRating.total_reviews !== 1 ? 's' : '') + ')'"></span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('l, F j, Y'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Quick Action Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6 !important; transition: all 0.3s ease; cursor: pointer;"
                                 @click="section='approved'" 
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Total Bookings</div>
                                            <div class="h2 fw-bold text-primary mb-2" id="r-total" style="font-size: 2rem;">0</div>
                                            <div class="small text-muted d-flex align-items-center gap-1" id="r-total-trend">
                                                <i class="fas fa-arrow-up text-success"></i>
                                                <span>0 new today</span>
                                            </div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-clipboard-list text-primary" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(22, 163, 74, 0.05) 100%); border-left: 4px solid #22c55e !important; transition: all 0.3s ease; cursor: pointer;"
                                 @click="section='approved'; approvedTab='completed'" 
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(34, 197, 94, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Completed</div>
                                            <div class="h2 fw-bold text-success mb-2" id="r-completed" style="font-size: 2rem;">0</div>
                                            <div class="small text-success d-flex align-items-center gap-1" id="r-completed-trend">
                                                <i class="fas fa-arrow-up"></i>
                                                <span>0% from yesterday</span>
                                            </div>
                                        </div>
                                        <div class="bg-success bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(251, 146, 60, 0.08) 0%, rgba(234, 88, 12, 0.05) 100%); border-left: 4px solid #fb923c !important; transition: all 0.3s ease; cursor: pointer;"
                                 @click="section='approved'; approvedTab='pending_review'" 
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(251, 146, 60, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Pending</div>
                                            <div class="h2 fw-bold mb-2" id="r-pending" style="color: #ea580c; font-size: 2rem;">0</div>
                                            <div class="small d-flex align-items-center gap-1" id="r-pending-trend" style="color: #ea580c;">
                                                <i class="fas fa-arrow-up"></i>
                                                <span>0 new today</span>
                                            </div>
                                        </div>
                                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(251, 146, 60, 0.15); width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-clock" style="color: #ea580c; font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.05) 100%); border-left: 4px solid #6366f1 !important; transition: all 0.3s ease; cursor: pointer;"
                                 @click="section='approved'; approvedTab='assigned'" 
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(99, 102, 241, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Assigned</div>
                                            <div class="h2 fw-bold mb-2" id="r-assigned" style="color: #6366f1; font-size: 2rem;">0</div>
                                            <div class="small d-flex align-items-center gap-1" id="r-assigned-trend" style="color: #6366f1;">
                                                <i class="fas fa-arrow-up"></i>
                                                <span>0 active now</span>
                                            </div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-user-check" style="color: #6366f1; font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Charts Section -->
                    <div class="row g-4 mb-4">
                        <div class="col-12 col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-chart-line text-success"></i>
                                            </div>
                                            <h5 class="mb-0 fw-bold">Bookings Activity</h5>
                                        </div>
                                        <span class="badge bg-light text-dark px-3 py-2">Last 14 Days</span>
                                    </div>
                                    <div style="height: 300px; position: relative; min-height: 300px;">
                                        <canvas id="chart-trend" style="max-height: 300px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-store text-primary"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Shop Status</h5>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: rgba(99, 102, 241, 0.05);">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-users text-primary"></i>
                                                <span class="small text-muted">Total Technicians</span>
                                            </div>
                                            <span class="fw-bold text-primary" id="total-techs">0</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: rgba(34, 197, 94, 0.05);">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-user-check text-success"></i>
                                                <span class="small text-muted">Active Now</span>
                                            </div>
                                            <span class="fw-bold text-success" id="active-techs">0</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: rgba(99, 102, 241, 0.05);">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-tools text-primary"></i>
                                                <span class="small text-muted">Total Services</span>
                                            </div>
                                            <span class="fw-bold text-primary" id="total-services">0</span>
                                        </div>
                                        <hr class="my-3">
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: rgba(99, 102, 241, 0.05);">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-circle" :class="shopIsOpen ? 'text-success' : 'text-secondary'"></i>
                                                <span class="small text-muted">Shop Status</span>
                                            </div>
                                            <span class="badge px-3 py-2" :class="shopIsOpen ? 'bg-success' : 'bg-secondary'" x-text="shopIsOpen ? 'Open' : 'Closed'" style="border-radius: 20px;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Chart & Urgent Attention -->
                    <div class="row g-4 mb-4">
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-pie text-primary"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Bookings by Status</h5>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-12 col-md-7">
                                            <div style="height: 300px; position: relative; min-height: 300px;">
                                                <canvas id="chart-status" style="max-height: 300px;"></canvas>
                                                <div id="chart-status-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; z-index: 10;">
                                                    <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-2" style="width: 80px; height: 80px; border: 3px solid #6366f1;">
                                                        <div class="h4 fw-bold text-primary mb-0" id="chart-status-total" style="font-size: 1.75rem;">0</div>
                                                    </div>
                                                    <div class="small text-muted fw-semibold">Active Jobs</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div id="chart-status-legend" class="d-flex flex-column gap-2" style="max-height: 300px; overflow-y: auto;">
                                                <!-- Legend will be populated by JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-danger bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Urgent Attention</h5>
                                    </div>
                                    <div id="urgent-attention" style="max-height: 280px; overflow-y: auto;">
                                        <div class="text-center py-5">
                                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-check-circle fa-2x text-success"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">All Clear!</h6>
                                            <p class="text-muted mb-0 small">No overdue items requiring attention</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Technicians & Recent Activity -->
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-trophy text-warning"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Top Performing Technicians</h5>
                                    </div>
                                    <div id="top-technicians">
                                        <div class="text-center py-5">
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-user-friends fa-2x text-muted"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">No Data Yet</h6>
                                            <p class="text-muted mb-0 small">Complete jobs to see top performers</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-info bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-history text-info"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Live Activity Feed</h5>
                                    </div>
                                    <div id="recent-bookings" style="max-height: 400px; overflow-y: auto;">
                                        <div class="text-center py-5">
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-clock fa-2x text-muted"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">No Recent Activity</h6>
                                            <p class="text-muted mb-0 small">Activity will appear here as bookings are processed</p>
                                        </div>
                                    </div>
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
                            <p class="text-muted small mb-0">Manage your account settings and preferences</p>
                        </div>
                    </div>
                    
                    <!-- Profile Photo Card -->
                    <div class="card border-0 shadow-lg mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(255,255,255,1) 100%);">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-4">
                                <div class="position-relative">
                                    <img :src="avatarUrl" 
                                         alt="Avatar" 
                                         class="rounded-circle border border-3 shadow-lg" 
                                         style="width: 100px; height: 100px; object-fit: cover; border-color: rgba(99,102,241,0.3) !important; cursor: pointer;"
                                         @error="handleAvatarError($event)"
                                         @click="$refs.avatarInput.click()"
                                         :data-name="userForm.name">
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
                    
                    <form @submit.prevent="saveShopProfile">
                        <!-- Shop Information Card -->
                        <div class="card border-0 shadow-lg mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
                                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                    <i class="fas fa-store text-primary"></i>
                                    <span>Shop Information</span>
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-store text-primary"></i>
                                            <span>Shop Name <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="shop.shop_name"
                                                   @input="sanitizeShopName($event)"
                                                   maxlength="255"
                                                   placeholder="Enter shop name"
                                                   required>
                                            <i class="fas fa-store position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-map-marker-alt text-primary"></i>
                                            <span>Shop Address</span>
                                        </label>
                                        <div class="position-relative">
                                            <textarea class="form-control form-control-lg border-2 ps-5 pt-3" 
                                                      rows="3" 
                                                      x-model="shop.shop_address"
                                                      @input="sanitizeShopAddress($event)"
                                                      maxlength="500"
                                                      placeholder="Enter your shop address"></textarea>
                                            <i class="fas fa-map-marker-alt position-absolute top-0 start-0 ms-3 mt-3 text-muted"></i>
                                        </div>
                                        <small class="text-muted d-flex align-items-center gap-1 mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Maximum 500 characters</span>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Map Location Picker -->
                                <div class="mt-4 pt-4 border-top">
                                    <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                        <i class="fas fa-map-marked-alt text-primary"></i>
                                        <span>Shop Location on Map</span>
                                    </label>
                                    <p class="text-muted small mb-3">Click on the map or drag the marker to update your shop's location</p>
                                    <div id="shop-location-map" class="border rounded-3 overflow-hidden shadow-sm" style="height: 400px; width: 100%; border-color: rgba(99,102,241,0.2) !important;" x-init="initShopLocationMap()"></div>
                                    <div class="d-flex gap-4 mt-3 text-muted small">
                                        <div>
                                            <span class="fw-semibold">Latitude:</span>
                                            <span x-text="shop.shop_latitude ? parseFloat(shop.shop_latitude).toFixed(6) : 'Not set'"></span>
                                        </div>
                                        <div>
                                            <span class="fw-semibold">Longitude:</span>
                                            <span x-text="shop.shop_longitude ? parseFloat(shop.shop_longitude).toFixed(6) : 'Not set'"></span>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            @click="getCurrentLocation()"
                                            class="btn btn-outline-primary btn-sm mt-3" style="border-radius: 20px;">
                                        <i class="fas fa-crosshairs me-2"></i>Use My Current Location
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Information Card -->
                        <div class="card border-0 shadow-lg mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
                                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                    <i class="fas fa-user-edit text-primary"></i>
                                    <span>Personal Information</span>
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-user text-primary"></i>
                                            <span>Owner Name <span class="text-danger">*</span></span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="userForm.name"
                                                   @input="sanitizeOwnerName($event)"
                                                   maxlength="100"
                                                   placeholder="Enter your name"
                                                   required>
                                            <i class="fas fa-user position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-envelope text-primary"></i>
                                            <span>Email Address</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="email" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="userForm.email" 
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
                                            <i class="fas fa-phone text-primary"></i>
                                            <span>Owner Phone</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="tel" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="userForm.phone"
                                                   @input="sanitizeOwnerPhone($event)"
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
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="card border-0 shadow-lg mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex flex-wrap gap-3">
                                    <button type="button" class="btn btn-outline-primary px-5 py-2" style="border-radius: 25px;" @click="showChangePassword = !showChangePassword">
                                        <i class="fas fa-key me-2"></i>
                                        <span x-show="!showChangePassword">Change Password</span>
                                        <span x-show="showChangePassword">Hide Password Form</span>
                                    </button>
                                    <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm" style="border-radius: 25px;">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
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

                </div>

                <div x-show="section==='technicians'" class="mt-4">
                    <!-- Header with Search -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-900 mb-0">Technicians</h2>
                        <div class="relative" style="width: 300px;">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                        </div>
                            <input type="text" 
                                   x-model="techSearchQuery" 
                                   @input="sanitizeSearch($event); filterTechnicians()"
                                   placeholder="Search technicians..." 
                                   class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   maxlength="100">
                        </div>
                    </div>
                    
                    <!-- Technicians Table Card -->
                    <div class="bg-white rounded-xl shadow-lg p-6 relative">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">The Team</h3>
                            <button @click="showTechModal = true" 
                                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2">
                                <i class="fas fa-plus"></i>
                                <span>Add Technician</span>
                                        </button>
                                    </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Contact</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Performance</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tech-list" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div x-show="section==='services'" class="mt-4 space-y-4">
                    <!-- Services Header Card -->
                    <div class="bg-white rounded-3 shadow-lg border border-light p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="neon-text fw-bold mb-1">Services</h5>
                                <p class="text-muted small mb-0">Create a polished list of what your shop offers.</p>
                            </div>
                            <button @click="showServiceModal = true" 
                                    class="btn btn-holographic text-white px-4 py-2 rounded-3 shadow-sm">
                                <i class="fas fa-plus me-2"></i>Add Service
                            </button>
                        </div>
                    </div>

                    <!-- Services List Card -->
                    <div class="bg-white rounded-3 shadow-lg border border-light">
                        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                            <h6 class="mb-0 text-uppercase text-muted small fw-semibold">Current Services</h6>
                            <span class="badge bg-light text-muted fw-semibold" x-text="services.length ? services.length + ' total' : 'No services yet'"></span>
                        </div>
                        <div class="table-responsive d-none d-md-block">
                            <table class="table mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-muted text-uppercase small fw-semibold ps-4">Name</th>
                                        <th class="text-muted text-uppercase small fw-semibold text-end">Price (₱)</th>
                                        <th class="text-muted text-uppercase small fw-semibold text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                            <tbody id="svc-list"></tbody>
                        </table>
                        </div>
                        <div class="d-md-none p-3" id="svc-card-list"></div>
                    </div>
                </div>

                <!-- Items for Sale Section -->
                <div x-show="section==='items'" class="glass-advanced rounded border p-4 mt-4">
                    <!-- Header with Search and Filters -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1 neon-text fw-bold">Items for Sale</h5>
                            <p class="text-muted small mb-0">Manage your inventory and products</p>
                        </div>
                            <button class="btn btn-primary shadow-sm" @click="showItemModal()">
                            <i class="fas fa-plus me-2"></i>Add New Item
                        </button>
                        </div>
                        
                        <!-- Search and Filter Bar -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           placeholder="Search products..."
                                           x-model="itemSearchQuery"
                                           @input="sanitizeItemSearch($event); filterItems()"
                                           maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" x-model="itemCategoryFilter" @change="filterItems()">
                                    <option value="all">All Categories</option>
                                    <template x-for="cat in getUniqueCategories()" :key="cat">
                                        <option :value="cat" x-text="cat"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" x-model="itemStockFilter" @change="filterItems()">
                                    <option value="all">All Stock</option>
                                    <option value="in_stock">In Stock</option>
                                    <option value="low_stock">Low Stock (&lt; 5)</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-end">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <span x-text="filteredItems.length"></span> items
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <template x-for="item in filteredItems" :key="item.id">
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden bg-white" 
                                     style="transition: all 0.3s ease; border-radius: 12px;">
                                    <!-- Image Container with Action Icons -->
                                    <div class="position-relative" style="height: 250px; overflow: hidden; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                                        <template x-if="item.image_url">
                                            <img 
                                                :src="getItemImageUrl(item.image_url)" 
                                                class="w-100 h-100" 
                                                style="object-fit: cover; transition: transform 0.3s ease;"
                                                :alt="item.item_name"
                                                @error="handleImageError($event)"
                                                onmouseover="this.style.transform='scale(1.05)'"
                                                onmouseout="this.style.transform='scale(1)'"
                                            >
                                        </template>
                                        <template x-if="!item.image_url">
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <div class="text-center">
                                                    <i class="fas fa-image fa-4x text-muted opacity-50"></i>
                                                    <p class="text-muted small mt-2 mb-0">No Image</p>
                                                </div>
                                            </div>
                                        </template>
                                        
                                        <!-- Action Icons (Top Right) -->
                                        <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                                            <button class="btn btn-sm btn-light rounded-circle shadow-sm p-2" 
                                                    @click="editItem(item)"
                                                    style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;"
                                                    title="Edit Item">
                                                <i class="fas fa-edit text-primary"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light rounded-circle shadow-sm p-2" 
                                                    @click="deleteItem(item.id)"
                                                    style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;"
                                                    title="Delete Item">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Status Badge (Top Left) -->
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <span class="badge rounded-pill px-3 py-2 shadow-sm" 
                                                  :class="getItemStatusBadge(item).class">
                                                <i class="fas me-1" :class="getItemStatusBadge(item).icon"></i>
                                                <span x-text="getItemStatusBadge(item).text"></span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Card Body -->
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold mb-2 text-dark" 
                                            style="font-size: 1.1rem; line-height: 1.4;"
                                            x-text="item.item_name"></h6>
                                        
                                        <p class="text-muted small mb-3" 
                                           style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; min-height: 2.5rem;"
                                           x-text="item.description || ''"></p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <span class="text-muted small d-block mb-1">Price</span>
                                                <span class="fw-bold text-dark" style="font-size: 1.3rem;">
                                                    ₱<span x-text="parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-muted small d-block mb-1">Stock</span>
                                                <div>
                                                    <span class="fw-bold" 
                                                          :class="getStockColorClass(item.stock_quantity)"
                                                      style="font-size: 1.1rem;"
                                                      x-text="item.stock_quantity.toLocaleString()"></span>
                                                    <span x-show="item.stock_quantity > 0 && item.stock_quantity < 5" 
                                                          class="badge bg-danger ms-2">Low Stock!</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div x-show="item.category" class="mb-3">
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-tag me-1"></i>
                                                <span x-text="item.category"></span>
                                            </span>
                                    </div>
                                    
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Empty State -->
                    <div x-show="filteredItems.length === 0 && items.length > 0" class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-search fa-4x text-muted opacity-25 mb-3"></i>
                        </div>
                        <h6 class="text-muted mb-2">No items match your filters</h6>
                        <p class="text-muted small mb-4">Try adjusting your search or filter criteria</p>
                        <button class="btn btn-outline-primary" @click="itemSearchQuery=''; itemCategoryFilter='all'; itemStockFilter='all'; filterItems()">
                            <i class="fas fa-redo me-2"></i>Clear Filters
                        </button>
                    </div>
                    
                    <!-- Empty State (No Items) -->
                    <div x-show="items.length === 0" class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-box-open fa-5x text-muted opacity-25 mb-3"></i>
                        </div>
                        <h6 class="text-muted mb-2">No items added yet</h6>
                        <p class="text-muted small mb-4">Start building your inventory by adding your first item</p>
                        <button class="btn btn-primary" @click="showItemModal()">
                            <i class="fas fa-plus me-2"></i>Add Your First Item
                        </button>
                    </div>
                </div>

                <div x-show="section==='approved'" class="glass-advanced rounded border p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 neon-text">Active Bookings</h6>
                        
                        <!-- Status Filter Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" type="button" id="bookingStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i :class="getStatusInfo(approvedTab).icon" class="me-2"></i>
                                <span x-text="getStatusInfo(approvedTab).label"></span>
                                <span class="badge rounded-pill ms-2" :class="getStatusInfo(approvedTab).badgeClass" x-text="approvedCounts[approvedTab] || 0"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bookingStatusDropdown" style="min-width: 280px;">
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'pending_review' }" href="#" @click.prevent="approvedTab = 'pending_review'; closeDropdown('bookingStatusDropdown'); renderCurrentBookingTab()">
                                        <span><i class="fas fa-search me-2 text-primary"></i>Pending Diagnosis</span>
                                        <span class="badge bg-primary rounded-pill" x-text="approvedCounts.pending_review || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'awaiting_confirmation' }" href="#" @click.prevent="approvedTab = 'awaiting_confirmation'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-hourglass-half me-2 text-warning"></i>Awaiting Confirmation</span>
                                        <span class="badge bg-warning text-dark rounded-pill" x-text="approvedCounts.awaiting_confirmation || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'confirmed' }" href="#" @click.prevent="approvedTab = 'confirmed'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-check-double me-2 text-info"></i>Confirmed Bookings</span>
                                        <span class="badge bg-info rounded-pill" x-text="approvedCounts.confirmed || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'approved' }" href="#" @click.prevent="approvedTab = 'approved'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-check me-2 text-primary"></i>Approved</span>
                                        <span class="badge bg-primary rounded-pill" x-text="approvedCounts.approved || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'assigned' }" href="#" @click.prevent="approvedTab = 'assigned'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-user-check me-2 text-info"></i>Assigned</span>
                                        <span class="badge bg-info rounded-pill" x-text="approvedCounts.assigned || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'in_progress' }" href="#" @click.prevent="approvedTab = 'in_progress'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-tools me-2 text-warning"></i>In Progress</span>
                                        <span class="badge bg-warning text-dark rounded-pill" x-text="approvedCounts.in_progress || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'completed' }" href="#" @click.prevent="approvedTab = 'completed'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-check-circle me-2 text-success"></i>Completed</span>
                                        <span class="badge bg-success rounded-pill" x-text="approvedCounts.completed || 0"></span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'rejected' }" href="#" @click.prevent="approvedTab = 'rejected'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-ban me-2 text-danger"></i>Rejected</span>
                                        <span class="badge bg-danger rounded-pill" x-text="approvedCounts.rejected || 0"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': approvedTab === 'cancelled' }" href="#" @click.prevent="approvedTab = 'cancelled'; closeDropdown('bookingStatusDropdown')">
                                        <span><i class="fas fa-times-circle me-2 text-secondary"></i>Cancelled</span>
                                        <span class="badge bg-secondary rounded-pill" x-text="approvedCounts.cancelled || 0"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Search and Filter Bar -->
                    <div class="card border-0 shadow-sm mb-3" style="background: rgba(99,102,241,0.05);">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-1">Search Bookings</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-start-0" 
                                               placeholder="Search by customer, device, service..."
                                               x-model="bookingSearchQuery"
                                               @input="filterBookings()"
                                               style="border-left: none;">
                                        <button class="btn btn-outline-secondary" type="button" @click="bookingSearchQuery=''; filterBookings()" x-show="bookingSearchQuery">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted mb-1">Sort By</label>
                                    <select class="form-select form-select-sm" x-model="bookingSortBy" @change="filterBookings()">
                                        <option value="date_desc">Date (Newest)</option>
                                        <option value="date_asc">Date (Oldest)</option>
                                        <option value="customer_asc">Customer (A-Z)</option>
                                        <option value="customer_desc">Customer (Z-A)</option>
                                        <option value="service_asc">Service (A-Z)</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted mb-1">Date Range</label>
                                    <select class="form-select form-select-sm" x-model="bookingDateFilter" @change="filterBookings()">
                                        <option value="all">All Dates</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted mb-1">Device Type</label>
                                    <select class="form-select form-select-sm" x-model="bookingDeviceFilter" @change="filterBookings()">
                                        <option value="all">All Devices</option>
                                        <option value="laptop">Laptop</option>
                                        <option value="phone">Phone</option>
                                        <option value="tablet">Tablet</option>
                                        <option value="desktop">Desktop</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary w-100 btn-sm" @click="clearBookingFilters()" title="Clear all filters">
                                        <i class="fas fa-redo me-1"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="tab-content mt-3">
                        <!-- Pending Review Tab Content -->
                        <div x-show="approvedTab === 'pending_review'">
                            <div id="shop-bookings-pending-review">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Awaiting Confirmation Tab Content -->
                        <div x-show="approvedTab === 'awaiting_confirmation'">
                            <div id="shop-bookings-awaiting-confirmation">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Confirmed Tab Content -->
                        <div x-show="approvedTab === 'confirmed'">
                            <div id="shop-bookings-confirmed">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Approved Tab Content -->
                        <div x-show="approvedTab === 'approved'">
                            <div id="shop-bookings-approved">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Assigned Tab Content -->
                        <div x-show="approvedTab === 'assigned'">
                            <div id="shop-bookings-assigned">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- In Progress Tab Content -->
                        <div x-show="approvedTab === 'in_progress'">
                            <div id="shop-bookings-in-progress">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Completed Tab Content -->
                        <div x-show="approvedTab === 'completed'">
                            <div id="shop-bookings-completed-tab">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rejected Tab Content -->
                        <div x-show="approvedTab === 'rejected'">
                            <div id="shop-bookings-rejected">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cancelled Tab Content -->
                        <div x-show="approvedTab === 'cancelled'">
                            <div id="shop-bookings-cancelled">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
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
                                                       'fa-wrench text-primary': notif.type === 'booking_diagnosed' || notif.type === 'new_booking' || notif.type === 'diagnosis_required',
                                                       'fa-check-circle text-success': notif.type === 'booking_completed',
                                                       'fa-calendar-check text-info': notif.type === 'booking_confirmed' || notif.type === 'customer_confirmed',
                                                       'fa-check text-success': notif.type === 'booking_approved',
                                                       'fa-user-check text-info': notif.type === 'technician_assigned' || notif.type === 'new_job',
                                                       'fa-tools text-warning': notif.type === 'booking_in_progress',
                                                       'fa-times-circle text-danger': notif.type === 'booking_cancelled' || notif.type === 'booking_rejected',
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

                <div x-show="section==='reports'" class="glass-advanced rounded border p-3 mt-4">
                    <h6 class="mb-3 neon-text">Reports</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="bg-indigo-50 p-2 rounded text-center">
                                <div class="small text-muted">Total</div>
                                <div id="r-total" class="h5 m-0">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="bg-green-50 p-2 rounded text-center">
                                <div class="small text-muted">Completed</div>
                                <div id="r-completed" class="h5 m-0">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="bg-yellow-50 p-2 rounded text-center">
                                <div class="small text-muted">Pending</div>
                                <div id="r-pending" class="h5 m-0">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="bg-blue-50 p-2 rounded text-center">
                                <div class="small text-muted">Assigned</div>
                                <div id="r-assigned" class="h5 m-0">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="border rounded p-2">
                                <div class="small text-muted mb-1">Bookings by Status</div>
                                <canvas id="chart-status" height="80"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded p-2">
                                <div class="small text-muted mb-1">Bookings Last 14 Days</div>
                                <canvas id="chart-trend" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script>
    function shopDashboard(){
        return {
            section: 'home',
            sidebarOpen: false, // Mobile sidebar toggle state
            approvedTab: 'pending_review', // For approved bookings sub-navigation
            approvedCounts: { 
                pending_review: 0, 
                awaiting_confirmation: 0, 
                confirmed: 0, 
                approved: 0, 
                assigned: 0, 
                in_progress: 0, 
                completed: 0, 
                rejected: 0, 
                cancelled: 0 
            }, // Counts for each tab
            showChangePassword: false,
            isRefreshing: false,
            isPollingActive: true,
            pollingInterval: null,
            lastRefreshTime: null,
            notifications: [],
            unreadCount: 0,
            shop: { 
                shop_name: <?php echo json_encode($user['shop_name'] ?? ''); ?>, 
                shop_address: <?php echo json_encode($user['shop_address'] ?? ''); ?>,
                shop_latitude: <?php echo json_encode($user['shop_latitude'] ?? null); ?>,
                shop_longitude: <?php echo json_encode($user['shop_longitude'] ?? null); ?>
            },
            map: null,
            mapMarker: null,
            userForm: { name: <?php echo json_encode($user['name']); ?>, email: <?php echo json_encode($user['email']); ?>, phone: <?php echo json_encode($user['phone'] ?? ''); ?> },
            avatarUrl: <?php 
                $avatarUrl = trim($user['avatar_url'] ?? '');
                error_log("Shop Dashboard - Raw avatar_url from DB: " . var_export($avatarUrl, true));
                
                if (empty($avatarUrl)) {
                    // No avatar uploaded, use generated avatar
                    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff&size=120';
                    error_log("Shop Dashboard - No avatar, using generated: " . $avatarUrl);
                } else {
                // Normalize avatar URL to be correct relative to frontend/shop/
                    if (!str_starts_with($avatarUrl, 'http') && !str_starts_with($avatarUrl, 'https') && !str_starts_with($avatarUrl, 'data:')) {
                        // Remove leading slash if present
                        $avatarUrl = ltrim($avatarUrl, '/');
                        // Check if file exists
                        $fullPath = __DIR__ . '/../' . $avatarUrl;
                        error_log("Shop Dashboard - Checking file: " . $fullPath);
                        if (!file_exists($fullPath)) {
                            // File doesn't exist, use generated avatar
                            error_log("Shop Dashboard - File does not exist, using generated avatar");
                            $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff&size=120';
                        } else {
                            // Prepend ../ to go from frontend/shop/ to frontend/
                            $avatarUrl = '../' . $avatarUrl;
                            error_log("Shop Dashboard - File exists, using: " . $avatarUrl);
                }
                    }
                }
                error_log("Shop Dashboard - Final avatar URL: " . $avatarUrl);
                echo json_encode($avatarUrl);
            ?>,
            techForm: { name: '', email: '', phone: '', password: '' },
            showTechPassword: false,
            showTechModal: false,
            showServiceModal: false,
            techSearchQuery: '',
            allTechnicians: [],
            filteredTechnicians: [],
            serviceForm: { service_name: '', price: '' },
            services: [],
            items: [],
            filteredItems: [],
            itemSearchQuery: '',
            itemCategoryFilter: 'all',
            itemStockFilter: 'all',
            itemForm: { item_name: '', description: '', price: 0, stock_quantity: 0, category: 'general', image_url: '', is_available: true },
            editingItem: null,
            shopRating: { average_rating: 0, total_reviews: 0 },
            shopIsOpen: true,
            // Booking search and filters
            bookingSearchQuery: '',
            bookingSortBy: 'date_desc',
            bookingDateFilter: 'all',
            bookingDeviceFilter: 'all',
            allBookings: [], // Store all bookings for filtering
            filteredBookings: {}, // Store filtered bookings by status
            toggleShopStatus(){
                // Save shop status to backend (you can implement this API call)
                console.log('Shop status toggled:', this.shopIsOpen);
                // TODO: Add API call to save shop status
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
                        // Normalize for frontend/shop/ (one level deep from frontend/)
                        if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                            if (logoUrl.startsWith('../backend/')) {
                                // Path is relative to frontend/, need to add ../ for shop/
                                logoUrl = '../' + logoUrl; // ../backend/... becomes ../../backend/...
                            } else if (logoUrl.startsWith('backend/')) {
                                logoUrl = '../../' + logoUrl;
                            } else if (!logoUrl.startsWith('../') && !logoUrl.startsWith('/')) {
                                logoUrl = '../../backend/uploads/logos/' + logoUrl.split('/').pop();
                            }
                        }
                        this.updateFavicon(logoUrl);
                        console.log('Shop dashboard: Favicon updated to:', logoUrl);
                    }
                } catch (e) {
                    console.error('Error loading website logo:', e);
                }
            },
            init(){
                // Debug: Log avatar URL to help troubleshoot
                console.log('Avatar URL initialized:', this.avatarUrl);
                console.log('User form name:', this.userForm.name);
                
                // Test if avatar image exists
                if (this.avatarUrl && !this.avatarUrl.includes('ui-avatars.com')) {
                    const img = new Image();
                    img.onload = () => console.log('Avatar image exists and loaded:', this.avatarUrl);
                    img.onerror = () => {
                        console.warn('Avatar image does not exist:', this.avatarUrl);
                        this.handleAvatarError({target: {src: this.avatarUrl}});
                    };
                    img.src = this.avatarUrl;
                }
                
                // Watch for section changes
                this.$watch('section', (value) => {
                    if(value === 'profile') {
                        setTimeout(() => {
                            this.initShopLocationMap();
                        }, 100);
                    } else if(value === 'home') {
                        // Ensure all home section functions are called when switching to home
                        this.refreshHomeSection();
                    }
                });
                
                this.loadWebsiteLogo(); // Load admin's website logo for favicon
                // Preload technicians and services so the page shows data immediately
                this.loadTechs();
                this.loadServices();
                this.loadItems();
                this.loadBookings();
                this.loadShopRating();
                this.loadNotifications();
                this.startPolling();
            },
            startPolling(){
                this.isPollingActive = true;
                this.pollingInterval = setInterval(() => {
                    console.log('Shop: Auto-refresh triggered');
                    this.loadBookings();
                    this.loadNotifications(); // Also poll notifications
                }, 10000); // Poll every 10 seconds (same as customer dashboard)
                console.log('Shop: AJAX auto-refresh started (every 10 seconds)');
            },
            stopPolling(){
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                    this.isPollingActive = false;
                }
                console.log('AJAX polling stopped');
            },
            async loadNotifications(){
                try {
                    console.log('Loading notifications...');
                    const res = await fetch('../auth/notifications.php');
                    const data = await res.json();
                    console.log('Notifications API response:', data);
                    if(data.success) {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                        console.log('Notifications loaded:', this.notifications.length, 'unread:', this.unreadCount);
                    } else {
                        console.error('Notifications API error:', data.error);
                    }
                } catch(e) {
                    console.error('Error loading notifications:', e);
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
                    await fetch('../auth/notifications.php', { 
                        method: 'POST', 
                        headers: {'Content-Type':'application/json'}, 
                        body: JSON.stringify({ notification_id: notif.id }) 
                    });
                    notif.is_read = true;
                    if(this.unreadCount > 0) this.unreadCount = this.unreadCount - 1;
                    
                    const t = String(notif.type||'');
                    console.log('Shop Dashboard Notification Type:', t, notif);
                    
                    // Load bookings first to ensure we have the latest data
                    this.loadBookings();
                    
                    // Navigate based on notification type - all bookings are now in Active Bookings section with tabs
                    // When customer confirms, booking status becomes 'confirmed_by_customer', so navigate to confirmed tab
                    if(t === 'customer_confirmed'){
                        // Customer confirmed the quotation - booking status is now 'confirmed_by_customer'
                        this.section = 'approved';
                        this.approvedTab = 'confirmed';
                    } else if(t === 'diagnosis_required' || t === 'new_booking'){
                        // New booking submitted, needs diagnosis
                        this.section = 'approved';
                        this.approvedTab = 'pending_review';
                    } else if(t === 'booking_approved'){
                        // Shop approved the booking - booking status is 'approved'
                        this.section = 'approved';
                        this.approvedTab = 'approved';
                    } else if(t === 'technician_assigned' || t === 'new_job'){
                        // Technician assigned - booking status is 'assigned'
                        this.section = 'approved';
                        this.approvedTab = 'assigned';
                    } else if(t === 'booking_in_progress'){
                        // Work started - booking status is 'in_progress'
                        this.section = 'approved';
                        this.approvedTab = 'in_progress';
                    } else if(t === 'booking_completed'){
                        // Work completed - booking status is 'completed'
                        this.section = 'approved';
                        this.approvedTab = 'completed';
                    } else if(t === 'booking_rejected' || t === 'shop_rejected'){
                        // Booking rejected - booking status is 'rejected'
                        this.section = 'approved';
                        this.approvedTab = 'rejected';
                    } else if(t === 'customer_cancelled' || t === 'booking_cancelled'){
                        // Customer cancelled - booking status is 'cancelled' or 'cancelled_by_customer'
                        this.section = 'approved';
                        this.approvedTab = 'cancelled';
                    } else if(t === 'shop_approved'){
                        // Shop approval notifications - stay on home
                        this.section = 'home';
                    } else {
                        // Default: go to pending review tab
                        this.section = 'approved';
                        this.approvedTab = 'pending_review';
                    }
                }catch(e){ 
                    console.error('Error handling notification:', e);
                }
            },
            // Function to switch to appropriate tab based on booking status
            switchToApprovedTab(status) {
                if (['approved'].includes(status)) {
                    this.approvedTab = 'approved';
                } else if (['assigned'].includes(status)) {
                    this.approvedTab = 'assigned';
                } else if (['in_progress'].includes(status)) {
                    this.approvedTab = 'in_progress';
                } else if (['completed'].includes(status)) {
                    this.approvedTab = 'completed';
                }
            },
            // Get status information for dropdown display
            getStatusInfo(status) {
                const statusMap = {
                    'pending_review': {
                        icon: 'fas fa-search',
                        label: 'Pending Diagnosis',
                        badgeClass: 'bg-primary text-white'
                    },
                    'awaiting_confirmation': {
                        icon: 'fas fa-hourglass-half',
                        label: 'Awaiting Confirmation',
                        badgeClass: 'bg-warning text-dark'
                    },
                    'confirmed': {
                        icon: 'fas fa-check-double',
                        label: 'Confirmed Bookings',
                        badgeClass: 'bg-info text-white'
                    },
                    'approved': {
                        icon: 'fas fa-check',
                        label: 'Approved',
                        badgeClass: 'bg-primary text-white'
                    },
                    'assigned': {
                        icon: 'fas fa-user-check',
                        label: 'Assigned',
                        badgeClass: 'bg-info text-white'
                    },
                    'in_progress': {
                        icon: 'fas fa-tools',
                        label: 'In Progress',
                        badgeClass: 'bg-warning text-dark'
                    },
                    'completed': {
                        icon: 'fas fa-check-circle',
                        label: 'Completed',
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
                    }
                };
                return statusMap[status] || {
                    icon: 'fas fa-list',
                    label: 'Select Status',
                    badgeClass: 'bg-secondary text-white'
                };
            },
            // Close Bootstrap dropdown
            closeDropdown(dropdownId) {
                const dropdownElement = document.getElementById(dropdownId);
                if (dropdownElement) {
                    const dropdown = bootstrap.Dropdown.getInstance(dropdownElement);
                    if (dropdown) {
                        dropdown.hide();
                    }
                }
            },
            async logout() {
                Notiflix.Confirm.show(
                    'Logout?',
                    'You will be signed out of your account.',
                    'Logout',
                    'Cancel',
                    () => {
                        fetch('../auth/logout.php', { 
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Notiflix.Report.success('Logged out', '', 'OK', () => {
                                    window.location.href = '../auth/index.php';
                                });
                            } else {
                                Notiflix.Report.failure('Error', data.error || 'Logout failed', 'OK');
                            }
                        })
                        .catch(e => {
                            console.error('Logout error:', e);
                            Notiflix.Report.failure('Error', 'Network error: ' + e.message, 'OK');
                        });
                    },
                    () => {}
                );
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
                formatted = formatted.replace(/(Estimated time: [^\.]+)/gi, '<strong class="text-info">$1</strong>');
                
                return formatted;
            },
            handleAvatarError(event){
                // Fallback to generated avatar if image fails to load
                const name = this.userForm.name || 'User';
                const fallbackUrl = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=4f46e5&color=fff&size=120';
                console.warn('Avatar image failed to load:', this.avatarUrl, 'Using fallback:', fallbackUrl);
                
                // Only update if current URL is not already a fallback
                if (!this.avatarUrl || !this.avatarUrl.includes('ui-avatars.com')) {
                    this.avatarUrl = fallbackUrl;
                }
                
                // Update the image source directly
                if(event && event.target) {
                    event.target.src = fallbackUrl;
                    event.target.onerror = null; // Prevent infinite loop
                }
            },
            onAvatarChange(e){
                const file = e.target.files && e.target.files[0];
                if(!file) return;
                const formData = new FormData();
                formData.append('avatar', file);
                fetch('shop_profile_photo_upload.php', { method: 'POST', body: formData })
                    .then(r=>r.json())
                    .then(data=>{ 
                        if(data.success){ 
                            // Normalize avatar URL to be correct relative to frontend/shop/
                            let avatarUrl = data.avatar_url;
                            if(avatarUrl && !avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
                                avatarUrl = '../' + avatarUrl.replace(/^\/+/, '');
                            }
                            this.avatarUrl = avatarUrl;
                            // Note: Favicon uses website logo, not user avatar
                            Notiflix.Report.success('Updated','Profile photo updated','OK'); 
                        } else { 
                            Notiflix.Report.failure('Error', data.error||'Upload failed','OK'); 
                        } 
                    })
                    .catch((e) => {
                        console.error('Upload error:', e);
                        Notiflix.Report.failure('Error', 'Network error occurred during upload', 'OK');
                    });
            },
            async saveShopProfile(){
                try {
                    // Validate and sanitize inputs before sending
                    const shopName = (this.shop.shop_name || '').trim();
                    const ownerName = (this.userForm.name || '').trim();
                    const phone = (this.userForm.phone || '').trim();
                    const address = (this.shop.shop_address || '').trim();
                    
                    // Check for dangerous characters
                    const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/;
                    
                    if(shopName && dangerousChars.test(shopName)){
                        Notiflix.Report.failure('Error', 'Shop name contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ ! are not allowed', 'OK');
                        return;
                    }
                    
                    if(ownerName && dangerousChars.test(ownerName)){
                        Notiflix.Report.failure('Error', 'Owner name contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ ! are not allowed', 'OK');
                        return;
                    }
                    
                    if(address && dangerousChars.test(address)){
                        Notiflix.Report.failure('Error', 'Address contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ ! are not allowed', 'OK');
                        return;
                    }
                    
                    if(!ownerName){
                        Notiflix.Report.failure('Error', 'Owner name is required', 'OK');
                        return;
                    }
                    
                    // Validate phone number format
                    if(phone && !/^09[0-9]{9}$/.test(phone)){
                        Notiflix.Report.failure('Error', 'Phone number must start with 09 and be exactly 11 digits', 'OK');
                        return;
                    }
                    
                const res = await fetch('shop_profile_update.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ shop: this.shop, user: this.userForm }) });
                const data = await res.json();
                    if(data.success){ 
                        // Show toast notification
                        Notiflix.Notify.success('Profile updated successfully!', {
                            position: 'right-top',
                            timeout: 3000,
                            clickToClose: true
                        });
                    } else { 
                        Notiflix.Report.failure('Error', data.error||'Failed to update profile','OK'); 
                    }
                } catch(error) {
                    console.error('Profile update error:', error);
                    Notiflix.Report.failure('Error', 'Network error occurred. Please try again.','OK');
                }
            },
            initShopLocationMap(){
                // Only initialize if profile section is visible
                if(this.section !== 'profile') return;
                
                // Wait for next tick to ensure DOM is ready
                this.$nextTick(() => {
                    setTimeout(() => {
                        if(this.map) {
                            // Map already exists, just update size
                            this.map.invalidateSize();
                            return;
                        }
                        
                        const mapElement = document.getElementById('shop-location-map');
                        if(!mapElement) return;
                        
                        // Default location (Manila, Philippines) or use existing shop location
                        const defaultLat = this.shop.shop_latitude || 14.5995;
                        const defaultLng = this.shop.shop_longitude || 120.9842;
                        const zoom = this.shop.shop_latitude ? 15 : 14;
                        
                        // Initialize map
                        this.map = L.map('shop-location-map').setView([defaultLat, defaultLng], zoom);
                        
                        // Add OpenStreetMap tiles
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors',
                            maxZoom: 19
                        }).addTo(this.map);
                        
                        // Create draggable marker
                        this.mapMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(this.map);
                        
                        // Handle marker drag end
                        this.mapMarker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.shop.shop_latitude = pos.lat;
                            this.shop.shop_longitude = pos.lng;
                            this.updateAddressFromCoords(pos.lat, pos.lng);
                        });
                        
                        // Handle map click
                        this.map.on('click', (e) => {
                            this.mapMarker.setLatLng(e.latlng);
                            this.shop.shop_latitude = e.latlng.lat;
                            this.shop.shop_longitude = e.latlng.lng;
                            this.updateAddressFromCoords(e.latlng.lat, e.latlng.lng);
                        });
                        
                        // Invalidate size to ensure proper rendering
                        setTimeout(() => {
                            if(this.map) this.map.invalidateSize();
                        }, 100);
                    }, 300);
                });
            },
            async updateAddressFromCoords(lat, lng){
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                    const data = await response.json();
                    if(data && data.address){
                        const addr = data.address;
                        const addressParts = [];
                        if(addr.road) addressParts.push(addr.road);
                        if(addr.village || addr.town || addr.city) addressParts.push(addr.village || addr.town || addr.city);
                        if(addr.state) addressParts.push(addr.state);
                        if(addr.postcode) addressParts.push(addr.postcode);
                        if(addr.country) addressParts.push(addr.country);
                        if(addressParts.length > 0){
                            this.shop.shop_address = addressParts.join(', ');
                        }
                    }
                } catch(e){
                    console.warn('Failed to reverse geocode:', e);
                }
            },
            getCurrentLocation(){
                if(!navigator.geolocation){
                    Notiflix.Report.failure('Error', 'Geolocation is not supported by your browser', 'OK');
                    return;
                }
                
                Notiflix.Loading.standard('Getting location...');
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        this.shop.shop_latitude = lat;
                        this.shop.shop_longitude = lng;
                        
                        if(this.map && this.mapMarker){
                            this.map.setView([lat, lng], 15);
                            this.mapMarker.setLatLng([lat, lng]);
                        }
                        
                        this.updateAddressFromCoords(lat, lng);
                        
                        Notiflix.Loading.remove();
                        Notiflix.Report.success('Success', 'Location updated!', 'OK');
                    },
                    (error) => {
                        Notiflix.Loading.remove();
                        Notiflix.Report.failure('Error', 'Failed to get your location: ' + error.message, 'OK');
                    }
                );
            },
            async submitChangePassword(oldPwd, newPwd, confirmPwd){
                if(newPwd!==confirmPwd){ Notiflix.Report.failure('Error','Passwords do not match','OK'); return; }
                try{
                    const res = await fetch('../auth/change_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials: 'same-origin', body: JSON.stringify({ old_password: oldPwd, new_password: newPwd }) });
                    const data = await res.json();
                    if(data.success){ 
                        Notiflix.Report.success('Updated','Password changed','OK'); 
                        this.showChangePassword = false;
                    }
                    else { Notiflix.Report.failure('Error', data.error||'Failed to change password','OK'); }
                }catch(e){ 
                    console.error('Change password error:', e);
                    Notiflix.Report.failure('Error', e.message || 'Network error occurred', 'OK'); 
                }
            },
            sanitizeEmail(event){
                // Remove dangerous characters that could be used for SQL injection
                // Allow valid email characters: letters, numbers, @, ., -, _, +
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '');
                if(originalValue !== sanitized){
                    this.techForm.email = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizePhone(event){
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
                if(this.techForm.phone !== value){
                    this.techForm.phone = value;
                    if(event.target.value !== value){
                        event.target.style.borderColor = '#ffc107';
                        setTimeout(() => {
                            event.target.style.borderColor = '';
                        }, 1000);
                    }
                }
            },
            sanitizeSearch(event){
                // Remove dangerous characters from search query to prevent SQL injection
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and @ for email searches
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '');
                if(originalValue !== sanitized){
                    this.techSearchQuery = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizePassword(event){
                // Remove dangerous characters that could be used for SQL injection
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '');
                if(originalValue !== sanitized){
                    this.techForm.password = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizeServiceName(event){
                // Remove dangerous characters from service name
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and parentheses
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.serviceForm.service_name = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizePrice(event){
                // Prevent negative numbers and ensure only valid numeric input
                let value = event.target.value;
                // Remove any non-numeric characters except decimal point
                value = value.replace(/[^0-9.]/g, '');
                // Prevent multiple decimal points
                const parts = value.split('.');
                if(parts.length > 2){
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                // Convert to number and ensure it's not negative
                const numValue = parseFloat(value) || 0;
                if(numValue < 0){
                    value = '0';
                } else {
                    value = numValue.toString();
                }
                // Update if changed
                if(this.serviceForm.price !== numValue){
                    this.serviceForm.price = numValue;
                    if(event.target.value !== value){
                        event.target.value = value;
                        // Visual feedback
                        event.target.style.borderColor = '#ffc107';
                        setTimeout(() => {
                            event.target.style.borderColor = '';
                        }, 1000);
                    }
                }
            },
            sanitizeItemSearch(event){
                // Remove dangerous characters from item search query
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '');
                if(originalValue !== sanitized){
                    this.itemSearchQuery = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizeItemName(name){
                // Remove dangerous characters from item name
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and parentheses
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                return name.replace(dangerousChars, '').replace(/\s+/g, ' ').trim();
            },
            sanitizeItemDescription(description){
                // Remove dangerous characters from description
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, parentheses, and newlines
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                return description.replace(dangerousChars, '').replace(/\s+/g, ' ').trim();
            },
            sanitizeItemCategory(category){
                // Remove dangerous characters from category
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                return category.replace(dangerousChars, '').replace(/\s+/g, ' ').trim();
            },
            sanitizeShopName(event){
                // Remove dangerous characters from shop name
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and parentheses
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.shop.shop_name = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizeOwnerName(event){
                // Remove dangerous characters from owner name
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.userForm.name = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizeOwnerPhone(event){
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
                if(this.userForm.phone !== value){
                    this.userForm.phone = value;
                    if(event.target.value !== value){
                        event.target.style.borderColor = '#ffc107';
                        setTimeout(() => {
                            event.target.style.borderColor = '';
                        }, 1000);
                    }
                }
            },
            sanitizeShopAddress(event){
                // Remove dangerous characters from address
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, parentheses, and forward slashes for addresses
                const dangerousChars = /[<>{}[\]();'"`\\|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.shop.shop_address = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            async createTechnician(){
                try{
                    // Client-side validation
                    if(!this.techForm.name || this.techForm.name.trim().length < 2 || this.techForm.name.trim().length > 100){
                        Notiflix.Report.warning('Invalid Name', 'Name must be between 2 and 100 characters.', 'OK');
                        return;
                    }
                    
                    if(!this.techForm.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.techForm.email)){
                        Notiflix.Report.warning('Invalid Email', 'Please enter a valid email address.', 'OK');
                        return;
                    }
                    
                    if(!this.techForm.phone || !/^09[0-9]{9}$/.test(this.techForm.phone)){
                        Notiflix.Report.warning('Invalid Phone', 'Phone number must start with 09 and be exactly 11 digits.', 'OK');
                        return;
                    }
                    
                    // Check for dangerous characters in password
                    const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^]/;
                    if(this.techForm.password && dangerousChars.test(this.techForm.password)){
                        Notiflix.Report.warning('Invalid Password', 'Password contains invalid characters. Please remove special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^', 'OK');
                        return;
                    }
                    
                    if(!this.techForm.password || this.techForm.password.length < 6 || this.techForm.password.length > 128){
                        Notiflix.Report.warning('Invalid Password', 'Password must be between 6 and 128 characters.', 'OK');
                        return;
                    }
                    
                    // Check for SQL injection patterns in name
                    const sqlPatterns = /\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onload)\b/i;
                    if(sqlPatterns.test(this.techForm.name)){
                        Notiflix.Report.warning('Invalid Name', 'Name contains invalid characters.', 'OK');
                        return;
                    }
                    
                const res = await fetch('../technician/technician_create.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(this.techForm) });
                    let data = {};
                    let responseText = '';
                    try { 
                        responseText = await res.text();
                        console.log('Technician create response:', responseText);
                        data = responseText ? JSON.parse(responseText) : {};
                    } catch(e) { 
                        console.error('Failed to parse response:', e, 'Response text:', responseText);
                        data = { error: 'Invalid server response', detail: responseText.substring(0, 200) };
                    }
                    
                    if(res.ok && data.success){
                        Notiflix.Report.success('Success','Technician added successfully!','OK');
                        this.techForm={name:'',email:'',phone:'',password:''};
                        this.showTechPassword = false;
                        this.showTechModal = false;
                        this.loadTechs();
                    } else {
                        // Provide more specific error messages
                        let errorMsg = data.error || data.detail || `Failed to create technician (HTTP ${res.status})`;
                        
                        // Log full error for debugging
                        console.error('Technician creation failed:', {
                            status: res.status,
                            error: data.error,
                            detail: data.detail,
                            fullResponse: data
                        });
                        
                        // Map common error codes to user-friendly messages
                        if(res.status === 409) {
                            errorMsg = 'This email is already registered. Please use a different email address.';
                        } else if(res.status === 400) {
                            if(errorMsg.includes('Shop profile') || errorMsg.includes('shop profile')) {
                                errorMsg = 'Your shop profile is incomplete. Please complete your shop registration first.';
                            } else if(errorMsg.includes('Email already') || errorMsg.includes('email')) {
                                errorMsg = 'This email is already registered. Please use a different email address.';
                            } else if(errorMsg.includes('Phone')) {
                                errorMsg = 'Phone number must start with 09 and be exactly 11 digits.';
                            } else if(errorMsg.includes('Password')) {
                                errorMsg = 'Password must be between 6 and 128 characters and contain only allowed characters.';
                            } else if(errorMsg.includes('Name')) {
                                errorMsg = 'Name must be between 2 and 100 characters.';
                            }
                        } else if(res.status === 401) {
                            errorMsg = 'Your session has expired. Please refresh the page and try again.';
                        } else if(res.status === 403) {
                            errorMsg = 'Your shop is not yet approved. Please wait for admin approval.';
                        }
                        
                        Notiflix.Report.failure('Error Creating Technician', errorMsg,'OK');
                    }
                }catch(e){ 
                    console.error('Create technician error:', e);
                    Notiflix.Report.failure('Error', e.message || 'Network or server error occurred', 'OK'); 
                }
            },
            async loadShopRating(){
                try{
                    const res = await fetch(`shop_ratings.php?type=shop&id=<?php echo $user['shop_id']; ?>`);
                    const data = await res.json();
                    if(data.success){
                        this.shopRating = data.rating;
                    } else {
                        console.error('Shop rating API error:', data.error);
                    }
                } catch(error){
                    console.error('Error loading shop rating:', error);
                }
            },
            getInitials(name){
                if(!name) return '?';
                const parts = name.trim().split(' ');
                if(parts.length >= 2){
                    return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
                }
                return name.substring(0, 2).toUpperCase();
            },
            filterTechnicians(){
                // Sanitize search query to prevent any special characters
                let query = (this.techSearchQuery || '').toString();
                // Remove dangerous characters as a safety measure
                query = query.replace(/[<>{}[\]();'"`\\/|&*%$#~^!]/g, '');
                query = query.toLowerCase().trim();
                
                if(!query){
                    this.filteredTechnicians = this.allTechnicians || [];
                } else {
                    this.filteredTechnicians = (this.allTechnicians || []).filter(t => 
                        (t.name || '').toLowerCase().includes(query) ||
                        (t.email || '').toLowerCase().includes(query) ||
                        (t.phone || '').includes(query)
                    );
                }
                this.renderTechnicians();
            },
            renderTechnicians(){
                const tbody = document.getElementById('tech-list');
                if(!tbody) return;
                tbody.innerHTML = '';
                
                const technicians = this.filteredTechnicians.length > 0 ? this.filteredTechnicians : (this.allTechnicians || []);
                
                if(technicians.length === 0){
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                                <p>No technicians found</p>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                    technicians.forEach(t => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 transition-colors';
                    const isActive = !['deactivated','rejected'].includes((t.status||'approved'));
                    
                    // Get initials for avatar
                    const initials = this.getInitials(t.name);
                    
                    // Format rating display
                    const rating = parseFloat(t.average_rating || 0);
                    const totalReviews = parseInt(t.total_reviews || 0);
                    const ratingDisplay = rating > 0 ? 
                        `<div class="flex items-center gap-1"><span class="text-yellow-500">⭐</span><span class="font-semibold">${rating.toFixed(1)}</span><span class="text-gray-500 text-sm">(${totalReviews} ${totalReviews === 1 ? 'review' : 'reviews'})</span></div>` : 
                        '<span class="text-gray-400 text-sm">No ratings</span>';
                    
                    tr.innerHTML = `
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-sm">
                                    ${initials}
                                </div>
                                <span class="font-medium text-gray-900">${t.name}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="text-sm text-gray-900">${t.email}</div>
                            <div class="text-xs text-gray-500">${t.phone || 'No phone'}</div>
                        </td>
                        <td class="px-4 py-4">
                            ${ratingDisplay}
                        </td>
                        <td class="px-4 py-4">
                            ${isActive ? 
                                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #dcfce7; color: #166534;"><span class="w-1.5 h-1.5 mr-1.5 rounded-full" style="background-color: #22c55e;"></span>Active</span>' : 
                                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fee2e2; color: #991b1b;"><span class="w-1.5 h-1.5 mr-1.5 rounded-full" style="background-color: #ef4444;"></span>Inactive</span>'
                            }
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button class="p-2 text-yellow-500 hover:bg-yellow-50 rounded-lg transition-colors" 
                                        onclick="viewTechRatings('${t.id}', '${t.name}')" 
                                        title="View Ratings">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" 
                                        onclick="resetTech('${t.id}')" 
                                        title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button class="p-2 rounded-lg transition-colors ${isActive ? 'text-red-600 hover:bg-red-50' : 'text-green-600 hover:bg-green-50'}" 
                                        onclick="toggleTechActive('${t.id}', ${isActive ? 'false' : 'true'})" 
                                        title="${isActive ? 'Deactivate' : 'Activate'}"
                                        style="color: ${isActive ? '#dc2626' : '#16a34a'};">
                                    <i class="fas ${isActive ? 'fa-ban' : 'fa-check-circle'}"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            },
            async loadTechs(){
                const res = await fetch('../technician/technician_list.php');
                const data = await res.json();
                this.allTechnicians = data.technicians || [];
                this.filteredTechnicians = [...this.allTechnicians];
                this.renderTechnicians();
            },
            async addService(){
                // Validate service name
                const serviceName = (this.serviceForm.service_name || '').trim();
                if(!serviceName){
                    Notiflix.Report.failure('Error', 'Service name is required', 'OK');
                    return;
                }
                
                // Check for dangerous characters in service name
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/;
                if(dangerousChars.test(serviceName)){
                    Notiflix.Report.failure('Error', 'Service name contains invalid characters. Special characters like < > { } [ ] ( ) ; \' " ` \\ / | & * % $ # @ ~ ^ ! are not allowed', 'OK');
                    return;
                }
                
                // Validate price
                const price = Number(this.serviceForm.price) || 0;
                if(price < 0){
                    Notiflix.Report.failure('Error', 'Price cannot be negative', 'OK');
                    return;
                }
                
                const payload = {
                    service_name: serviceName,
                    price: price
                };
                try{
                    const res = await fetch('services_manage.php', {
                        method:'POST',
                        headers:{'Content-Type':'application/json'},
                        body: JSON.stringify(payload)
                    });
                const data = await res.json();
                    if(data.success){
                        this.serviceForm = { service_name:'', price:'' };
                        this.showServiceModal = false;
                        this.showToast('Service added successfully!');
                        this.loadServices();
                    } else {
                        Notiflix.Report.failure('Error', data.error||'Failed to add service', 'OK');
                    }
                } catch(error){
                    console.error('Error adding service:', error);
                    Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                }
            },
            async loadServices(){
                try{
                const res = await fetch('services_manage.php');
                const data = await res.json();
                    this.services = data.services || [];
                    this.renderServicesTable();
                    this.renderServicesMobile();
                } catch(error){
                    console.error('Error loading services:', error);
                }
            },
            formatCurrency(value = 0){
                const amount = Number(value) || 0;
                return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            escapeHtml(str = ''){
                const safeString = String(str ?? '');
                return safeString
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            },
            getServicePriceDisplay(service){
                const amount = Number(service.price) || 0;
                if(amount <= 0){
                    return '<span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color:#ecfdf5;color:#15803d;">FREE</span>';
                }
                return `<span class="fw-semibold text-gray-900">${this.formatCurrency(amount)}</span>`;
            },
            renderServicesTable(){
                const tbody = document.getElementById('svc-list');
                if(!tbody) return;
                tbody.innerHTML = '';
                if(!this.services.length){
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `<td colspan="3" class="text-center text-muted py-4">No services yet. Add your first service above.</td>`;
                    tbody.appendChild(emptyRow);
                    return;
                }
                this.services.forEach(service=>{
                    const tr = document.createElement('tr');
                    tr.className = 'align-middle';
                    tr.innerHTML = `
                        <td class="py-4 ps-4">
                            <div class="fw-semibold text-gray-900">${this.escapeHtml(service.service_name)}</div>
                        </td>
                        <td class="py-4 pe-4 text-end">${this.getServicePriceDisplay(service)}</td>
                        <td class="py-4 pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm btn-edit-service" title="Edit Service">
                                    <i class="fas fa-pen text-secondary"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm btn-delete-service" title="Delete Service">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    const editBtn = tr.querySelector('.btn-edit-service');
                    if(editBtn){
                        editBtn.addEventListener('click', () => this.editService(service));
                    }
                    const deleteBtn = tr.querySelector('.btn-delete-service');
                    if(deleteBtn){
                        deleteBtn.addEventListener('click', () => this.confirmDeleteService(service));
                    }
                    tbody.appendChild(tr);
                });
            },
            renderServicesMobile(){
                const container = document.getElementById('svc-card-list');
                if(!container) return;
                container.innerHTML = '';
                if(!this.services.length){
                    container.innerHTML = `<div class="text-center text-muted small py-3">No services yet. Add one above.</div>`;
                    return;
                }
                this.services.forEach(service=>{
                    const card = document.createElement('div');
                    card.className = 'border rounded-3 p-3 shadow-sm mb-3';
                    card.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold text-gray-900">${this.escapeHtml(service.service_name)}</div>
                            </div>
                            <div class="text-end">${this.getServicePriceDisplay(service)}</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill btn-edit-service" title="Edit Service">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill btn-delete-service" title="Delete Service">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    const editBtn = card.querySelector('.btn-edit-service');
                    if(editBtn){
                        editBtn.addEventListener('click', () => this.editService(service));
                    }
                    const deleteBtn = card.querySelector('.btn-delete-service');
                    if(deleteBtn){
                        deleteBtn.addEventListener('click', () => this.confirmDeleteService(service));
                    }
                    container.appendChild(card);
                });
            },
            showToast(message, icon='success'){
                // Use Notiflix for toast notifications
                const notifyOptions = {
                    position: 'right-top',
                    timeout: 2500,
                    clickToClose: true
                };
                
                switch(icon){
                    case 'success':
                        Notiflix.Notify.success(message, notifyOptions);
                        break;
                    case 'error':
                        Notiflix.Notify.failure(message, notifyOptions);
                        break;
                    case 'warning':
                        Notiflix.Notify.warning(message, notifyOptions);
                        break;
                    case 'info':
                        Notiflix.Notify.info(message, notifyOptions);
                        break;
                    default:
                        Notiflix.Notify.success(message, notifyOptions);
                }
            },
            async editService(service){
                const sanitizedName = this.escapeHtml(service.service_name);
                const priceValue = (Number(service.price) || 0).toFixed(2);
                const { value: formValues } = await Swal.fire({
                    title: 'Edit Service',
                    html: `
                        <div class="text-start">
                            <label class="form-label">Service Name</label>
                            <input id="swal-service-name" class="form-control" value="${sanitizedName}" required>
                            <label class="form-label mt-3">Price (₱)</label>
                            <input id="swal-service-price" class="form-control" type="number" min="0" step="0.01" value="${priceValue}" required>
                        </div>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Save Changes',
                    preConfirm: () => {
                        const name = document.getElementById('swal-service-name').value.trim();
                        const priceValue = document.getElementById('swal-service-price').value;
                        const price = Number(priceValue);
                        if(!name){
                            Swal.showValidationMessage('Service name is required');
                            return false;
                        }
                        if(isNaN(price) || price < 0){
                            Swal.showValidationMessage('Price must be zero or higher');
                            return false;
                        }
                        return { name, price };
                    }
                });
                if(!formValues) return;
                try{
                    const res = await fetch('services_manage.php', {
                        method:'PUT',
                        headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({
                            id: service.id,
                            service_name: formValues.name,
                            price: formValues.price
                        })
                    });
                    const data = await res.json();
                    if(data.success){
                        this.showToast('Service updated successfully!');
                        this.loadServices();
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Failed to update service', 'OK');
                    }
                } catch(error){
                    console.error('Error updating service:', error);
                    Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                }
            },
            filterBookings(){
                if(!this.allBookings || this.allBookings.length === 0) {
                    this.filteredBookings = {};
                    return;
                }
                
                let filtered = [...this.allBookings];
                
                // Search filter
                if(this.bookingSearchQuery && this.bookingSearchQuery.trim()){
                    const query = this.bookingSearchQuery.toLowerCase().trim();
                    filtered = filtered.filter(b => {
                        return (b.customer_name && b.customer_name.toLowerCase().includes(query)) ||
                               (b.customer_email && b.customer_email.toLowerCase().includes(query)) ||
                               (b.customer_phone && b.customer_phone.includes(query)) ||
                               (b.device_type && b.device_type.toLowerCase().includes(query)) ||
                               (b.device_issue_description && b.device_issue_description.toLowerCase().includes(query)) ||
                               (b.service && b.service.toLowerCase().includes(query)) ||
                               (b.description && b.description.toLowerCase().includes(query));
                    });
                }
                
                // Device type filter
                if(this.bookingDeviceFilter !== 'all'){
                    filtered = filtered.filter(b => {
                        const deviceType = (b.device_type || '').toLowerCase();
                        if(this.bookingDeviceFilter === 'other'){
                            return !['laptop', 'phone', 'tablet', 'desktop'].includes(deviceType);
                        }
                        return deviceType === this.bookingDeviceFilter;
                    });
                }
                
                // Date range filter
                if(this.bookingDateFilter !== 'all'){
                    const now = new Date();
                    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(monthAgo.getMonth() - 1);
                    
                    filtered = filtered.filter(b => {
                        const bookingDate = new Date(b.date);
                        switch(this.bookingDateFilter){
                            case 'today':
                                return bookingDate >= today;
                            case 'week':
                                return bookingDate >= weekAgo;
                            case 'month':
                                return bookingDate >= monthAgo;
                            default:
                                return true;
                        }
                    });
                }
                
                // Sort
                filtered.sort((a, b) => {
                    switch(this.bookingSortBy){
                        case 'date_desc':
                            return new Date(b.date) - new Date(a.date);
                        case 'date_asc':
                            return new Date(a.date) - new Date(b.date);
                        case 'customer_asc':
                            return (a.customer_name || '').localeCompare(b.customer_name || '');
                        case 'customer_desc':
                            return (b.customer_name || '').localeCompare(a.customer_name || '');
                        case 'service_asc':
                            return (a.service || '').localeCompare(b.service || '');
                        default:
                            return 0;
                    }
                });
                
                // Group by status
                this.filteredBookings = {
                    pending_review: filtered.filter(b=>b.status==='pending_review'),
                    awaiting_confirmation: filtered.filter(b=>b.status==='awaiting_customer_confirmation'),
                    confirmed: filtered.filter(b=>b.status==='confirmed_by_customer'),
                    approved: filtered.filter(b=>b.status==='approved'),
                    assigned: filtered.filter(b=>b.status==='assigned'),
                    in_progress: filtered.filter(b=>b.status==='in_progress'),
                    completed: filtered.filter(b=>b.status==='completed'),
                    rejected: filtered.filter(b=>b.status==='rejected'),
                    cancelled: filtered.filter(b=>['cancelled','cancelled_by_customer'].includes(b.status))
                };
                
                // Re-render the current tab
                this.renderCurrentBookingTab();
            },
            renderCurrentBookingTab(){
                if(!this.renderBookingList) return;
                
                const filtered = this.filteredBookings;
                const tabMap = {
                    'pending_review': { list: filtered.pending_review || [], container: 'shop-bookings-pending-review', text: 'No bookings pending diagnosis.' },
                    'awaiting_confirmation': { list: filtered.awaiting_confirmation || [], container: 'shop-bookings-awaiting-confirmation', text: 'No bookings awaiting customer confirmation.' },
                    'confirmed': { list: filtered.confirmed || [], container: 'shop-bookings-confirmed', text: 'No confirmed bookings.' },
                    'approved': { list: filtered.approved || [], container: 'shop-bookings-approved', text: 'No approved bookings.' },
                    'assigned': { list: filtered.assigned || [], container: 'shop-bookings-assigned', text: 'No assigned bookings.' },
                    'in_progress': { list: filtered.in_progress || [], container: 'shop-bookings-in-progress', text: 'No bookings in progress.' },
                    'completed': { list: filtered.completed || [], container: 'shop-bookings-completed-tab', text: 'No completed bookings.' },
                    'rejected': { list: filtered.rejected || [], container: 'shop-bookings-rejected', text: 'No rejected bookings.' },
                    'cancelled': { list: filtered.cancelled || [], container: 'shop-bookings-cancelled', text: 'No cancelled bookings.' }
                };
                
                const currentTab = tabMap[this.approvedTab];
                if(currentTab && this.renderBookingList){
                    this.renderBookingList(currentTab.list, currentTab.container, currentTab.text);
                }
            },
            clearBookingFilters(){
                this.bookingSearchQuery = '';
                this.bookingSortBy = 'date_desc';
                this.bookingDateFilter = 'all';
                this.bookingDeviceFilter = 'all';
                this.filterBookings();
            },
            async confirmDeleteService(service){
                Notiflix.Confirm.show(
                    'Delete service?',
                    `Remove ${this.escapeHtml(service.service_name)}?`,
                    'Delete',
                    'Cancel',
                    () => {
                        fetch(`services_manage.php?id=${encodeURIComponent(service.id)}`, { method:'DELETE' })
                            .then(res => res.json())
                            .then(data => {
                                if(data.success){
                                    this.showToast('Service removed', 'success');
                                    this.loadServices();
                                } else {
                                    Notiflix.Report.failure('Error', data.error || 'Failed to delete service', 'OK');
                                }
                            })
                            .catch(error => {
                                console.error('Error deleting service:', error);
                                Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                            });
                    },
                    () => {},
                    {
                        titleColor: '#000000',
                        messageColor: '#333333',
                        titleFontSize: '1.25rem',
                        messageFontSize: '0.95rem',
                        titleMaxLength: 100,
                        messageMaxLength: 500
                    }
                );
            },
            async loadBookings(){
                try{
                    console.log('Shop: Loading bookings...');
                    this.isRefreshing = true;
                    const res = await fetch('shop_bookings.php', { 
                        cache: 'no-store',
                        headers: {
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Pragma': 'no-cache',
                            'Expires': '0'
                        }
                    });
                    const data = await res.json();
                    this.lastRefreshTime = new Date().toLocaleTimeString();
                    if(data.success){
                        const all = data.bookings||[];
                        this.allBookings = all; // Store all bookings for filtering
                        
                        // Apply filters
                        this.filterBookings();
                        
                        const filtered = this.filteredBookings;
                        const pendingReview = filtered.pending_review || [];
                        const awaitingConfirmation = filtered.awaiting_confirmation || [];
                        const confirmed = filtered.confirmed || [];
                        const approved = filtered.approved || [];
                        const assigned = filtered.assigned || [];
                        const inProgress = filtered.in_progress || [];
                        const completed = filtered.completed || [];
                        const rejected = filtered.rejected || [];
                        const cancelled = filtered.cancelled || [];

                        console.log('Bookings loaded:', {
                            total: all.length,
                            pendingReview: pendingReview.length,
                            awaitingConfirmation: awaitingConfirmation.length,
                            confirmed: confirmed.length,
                            approved: approved.length,
                            assigned: assigned.length,
                            inProgress: inProgress.length,
                            completed: completed.length,
                            rejected: rejected.length,
                            cancelled: cancelled.length
                        });

                        // Update approved tab counts
                        this.approvedCounts = {
                            pending_review: pendingReview.length,
                            awaiting_confirmation: awaitingConfirmation.length,
                            confirmed: confirmed.length,
                            approved: approved.length,
                            assigned: assigned.length,
                            in_progress: inProgress.length,
                            completed: completed.length,
                            rejected: rejected.length,
                            cancelled: cancelled.length
                        };

                        const renderList = (list, containerId, emptyText) => {
                            const container = document.getElementById(containerId);
                            if(!container) return;
                            if(list.length===0){
                                container.innerHTML = `
                                    <div class="text-center py-5">
                                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                            <i class="fas fa-inbox fa-2x text-muted"></i>
                                        </div>
                                        <h6 class="text-muted mb-2">${emptyText}</h6>
                                    </div>
                                `;
                                return;
                            }
                            container.innerHTML = '';
                            list.forEach(b=>{
                            const div = document.createElement('div');
                            div.className = 'card border-0 shadow-sm mb-3';
                            div.style.transition = 'all 0.3s ease';
                            div.style.borderRadius = '12px';
                            div.onmouseenter = function() { 
                                this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.12)'; 
                                this.style.transform = 'translateY(-4px)'; 
                            };
                            div.onmouseleave = function() { 
                                this.style.boxShadow = ''; 
                                this.style.transform = ''; 
                            };
                            
                            const statusInfo = {
                                'pending_review': { class: 'bg-warning text-dark', icon: 'fa-clock', label: 'Pending Review' },
                                'awaiting_customer_confirmation': { class: 'bg-info text-white', icon: 'fa-hourglass-half', label: 'Awaiting Confirmation' },
                                'confirmed_by_customer': { class: 'bg-primary text-white', icon: 'fa-check-double', label: 'Confirmed' },
                                'approved': { class: 'bg-primary text-white', icon: 'fa-check', label: 'Approved' },
                                'assigned': { class: 'bg-info text-white', icon: 'fa-user-check', label: 'Assigned' },
                                'in_progress': { class: 'bg-warning text-dark', icon: 'fa-tools', label: 'In Progress' },
                                'completed': { class: 'bg-success text-white', icon: 'fa-check-circle', label: 'Completed' },
                                'cancelled': { class: 'bg-danger text-white', icon: 'fa-times-circle', label: 'Cancelled' },
                                'cancelled_by_customer': { class: 'bg-danger text-white', icon: 'fa-times-circle', label: 'Cancelled' },
                                'rejected': { class: 'bg-danger text-white', icon: 'fa-ban', label: 'Rejected' }
                            }[b.status] || { class: 'bg-secondary text-white', icon: 'fa-circle', label: b.status };
                            
                            const timeDays = parseFloat(b.estimated_time_days || 0);
                            const timeDisplay = timeDays < 1 ? `${Math.round(timeDays * 24)} hours` : timeDays === 1 ? '1 day' : `${timeDays} days`;
                            
                            div.innerHTML = `
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <!-- Left Column: Customer & Booking Info -->
                                        <div class="col-lg-10">
                                            <!-- Customer Info -->
                                            <div class="d-flex align-items-start gap-3 mb-3">
                                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1 text-dark">${b.customer_name}</h6>
                                                    <div class="d-flex flex-wrap gap-3 small text-muted">
                                                        <span><i class="fas fa-envelope me-1"></i>${b.customer_email}</span>
                                                        <span><i class="fas fa-phone me-1"></i>${b.customer_phone || 'No phone'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Device & Issue -->
                                            <div class="mb-3">
                                                ${b.device_type ? `
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <i class="fas fa-laptop text-primary"></i>
                                                        <span class="fw-semibold text-dark">Device:</span>
                                                        <span class="text-dark">${b.device_type}</span>
                                                    </div>
                                                ` : ''}
                                                ${b.device_issue_description ? `
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                                        <span class="fw-semibold text-dark">Issue:</span>
                                                        <span class="text-muted">${b.device_issue_description}</span>
                                                    </div>
                                                ` : ''}
                                                ${b.device_photo ? `
                                                    <div class="mt-2 mb-3">
                                                        <img src="../${b.device_photo}" class="img-thumbnail rounded" style="max-width:200px; border-radius: 8px !important;" alt="Device Photo">
                                                    </div>
                                                ` : ''}
                                            </div>
                                            
                                            <!-- Service Info -->
                                            <div class="mb-3 p-3 rounded" style="background: rgba(99,102,241,0.05); border-left: 3px solid #6366f1;">
                                                <div class="fw-bold text-dark mb-1">${b.service}</div>
                                                <div class="small text-muted">${b.description || 'No description'}</div>
                                            </div>
                                            
                                            <!-- Diagnosis Info -->
                                            ${b.diagnostic_notes ? `
                                                <div class="alert alert-info border-0 mb-3" style="background: rgba(13,202,240,0.1); border-left: 3px solid #0dcaf0 !important;">
                                                    <div class="fw-semibold mb-2"><i class="fas fa-stethoscope me-2"></i>Diagnosis</div>
                                                    <div class="mb-2">${b.diagnostic_notes}</div>
                                                    <div class="d-flex gap-4 small">
                                                        <span><i class="fas fa-dollar-sign me-1"></i><strong>Cost:</strong> ₱${Number(b.estimated_cost||0).toFixed(2)}</span>
                                                        <span><i class="fas fa-clock me-1"></i><strong>Time:</strong> ${timeDisplay}</span>
                                                    </div>
                                                </div>
                                            ` : ''}
                                            
                                            <!-- Date, Time & Status -->
                                            <div class="d-flex align-items-center flex-wrap gap-3 pt-3" style="border-top: 1px solid rgba(0,0,0,0.1);">
                                                <div class="d-flex align-items-center gap-2 text-muted small">
                                                    <i class="fas fa-calendar text-primary"></i>
                                                    <span>${b.date}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 text-muted small">
                                                    <i class="fas fa-clock text-primary"></i>
                                                    <span>${b.time_slot || 'No time slot'}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 text-muted small">
                                                    <i class="fas fa-history text-primary"></i>
                                                    <span>${new Date(b.created_at).toLocaleDateString()}</span>
                                                </div>
                                                <div class="ms-auto">
                                                    <span class="badge ${statusInfo.class} px-3 py-2 rounded-pill" style="font-size: 0.85rem;">
                                                        <i class="fas ${statusInfo.icon} me-1"></i>${statusInfo.label}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Additional Info -->
                                            ${b.reschedule_request ? `
                                                <div class="mt-3 p-2 rounded" style="background: rgba(255,193,7,0.1); border-left: 3px solid #ffc107;">
                                                    <span class="badge bg-warning text-dark me-2">Reschedule Requested</span>
                                                    ${b.reschedule_new_at ? `<span class="small text-muted">→ ${new Date(b.reschedule_new_at).toLocaleString()}</span>`:''}
                                                </div>
                                            ` : ''}
                                            ${b.technician_name ? `
                                                <div class="mt-2 d-flex align-items-center gap-2 text-muted small">
                                                    <i class="fas fa-user-cog text-primary"></i>
                                                    <span>Assigned to: <strong>${b.technician_name}</strong></span>
                                                </div>
                                            ` : (b.status==='assigned' ? `
                                                <div class="mt-2 d-flex align-items-center gap-2 text-warning small">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <span>Technician assigned but name not found</span>
                                                </div>
                                            ` : '')}
                                            ${(b.status==='cancelled' || b.status==='cancelled_by_customer') && b.cancellation_reason ? `
                                                <div class="mt-3 alert alert-danger border-0" style="background: rgba(220,53,69,0.1); border-left: 3px solid #dc3545 !important;">
                                                    <strong><i class="fas fa-times-circle me-2"></i>Reason for cancellation:</strong> ${b.cancellation_reason}
                                                </div>
                                            ` : ''}
                                        </div>
                                        
                                        <!-- Right Column: Actions -->
                                        <div class="col-lg-2">
                                            <div class="d-flex flex-column gap-2">
                                                ${b.status==='pending_review' ? `
                                                    <button class="btn btn-sm btn-primary w-100 shadow-sm" onclick="provideDiagnosis(${b.id})" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-stethoscope me-2"></i>Provide Diagnosis
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger w-100" onclick="manageBooking(${b.id}, 'reject')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-times me-2"></i>Reject
                                                    </button>
                                                ` : ''}
                                                ${b.status==='confirmed_by_customer' ? `
                                                    <button class="btn btn-sm btn-success w-100 shadow-sm" onclick="manageBooking(${b.id}, 'approve')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-check me-2"></i>Approve Booking
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger w-100" onclick="manageBooking(${b.id}, 'reject')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-times me-2"></i>Reject
                                                    </button>
                                                ` : ''}
                                                ${b.reschedule_request ? `
                                                    <button class="btn btn-sm btn-success w-100 shadow-sm" onclick="manageBooking(${b.id}, 'reschedule_accept')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-check me-2"></i>Accept Reschedule
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger w-100" onclick="manageBooking(${b.id}, 'reschedule_decline')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-times me-2"></i>Decline
                                                    </button>
                                                ` : ''}
                                                ${b.status==='approved' ? `
                                                    <button class="btn btn-sm btn-primary w-100 shadow-sm" onclick="assignTechnician(${b.id})" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-user-plus me-2"></i>Assign Technician
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger w-100" onclick="manageBooking(${b.id}, 'cancel')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-times me-2"></i>Cancel Booking
                                                    </button>
                                                ` : ''}
                                                ${b.status==='assigned' ? `
                                                    <button class="btn btn-sm btn-warning w-100 shadow-sm" onclick="manageBooking(${b.id}, 'status', 'in_progress')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-play me-2"></i>Start Work
                                                    </button>
                                                ` : ''}
                                                ${b.status==='in_progress' ? `
                                                    <button class="btn btn-sm btn-success w-100 shadow-sm" onclick="manageBooking(${b.id}, 'status', 'completed')" style="border-radius: 8px; font-size: 0.875rem; padding: 0.4rem 0.75rem;">
                                                        <i class="fas fa-check-circle me-2"></i>Mark as Completed
                                                    </button>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                                container.appendChild(div);
                            });
                        };

                        renderList(pendingReview, 'shop-bookings-pending-review', 'No bookings pending diagnosis.');
                        renderList(awaitingConfirmation, 'shop-bookings-awaiting-confirmation', 'No bookings awaiting customer confirmation.');
                        renderList(confirmed, 'shop-bookings-confirmed', 'No confirmed bookings.');
                        renderList(approved, 'shop-bookings-approved', 'No approved bookings.');
                        renderList(assigned, 'shop-bookings-assigned', 'No assigned bookings.');
                        renderList(inProgress, 'shop-bookings-in-progress', 'No bookings in progress.');
                        renderList(completed, 'shop-bookings-completed-tab', 'No completed bookings.');
                        renderList(rejected, 'shop-bookings-rejected', 'No rejected bookings.');
                        renderList(cancelled, 'shop-bookings-cancelled', 'No cancelled bookings.');
                        
                        // Store renderList for filterBookings to use
                        this.renderBookingList = renderList;

                        // Update report counters and charts
                        const total = all.length;
                        const completedCount = completed.length;
                        const pendingCount = all.filter(b=>b.status==='pending_review').length;
                        const assignedCount = all.filter(b=>b.status==='assigned').length;
                        const inProgressCount = all.filter(b=>b.status==='in_progress').length;
                        
                        // Safely update counters (only if elements exist)
                        const rTotal = document.getElementById('r-total');
                        if(rTotal) rTotal.textContent = total;
                        const rCompleted = document.getElementById('r-completed');
                        if(rCompleted) rCompleted.textContent = completedCount;
                        const rPending = document.getElementById('r-pending');
                        if(rPending) rPending.textContent = pendingCount;
                        const rAssigned = document.getElementById('r-assigned');
                        if(rAssigned) rAssigned.textContent = assignedCount;

                        // Update trend indicators (simplified - you can enhance with real data)
                        const trendTotal = document.getElementById('r-total-trend');
                        if(trendTotal) trendTotal.textContent = `↑ ${total} total`;
                        const trendCompleted = document.getElementById('r-completed-trend');
                        if(trendCompleted) trendCompleted.textContent = completedCount > 0 ? `↑ ${completedCount} completed` : 'No completions yet';
                        const trendPending = document.getElementById('r-pending-trend');
                        if(trendPending) trendPending.textContent = pendingCount > 0 ? `↑ ${pendingCount} pending` : 'All clear';
                        const trendAssigned = document.getElementById('r-assigned-trend');
                        if(trendAssigned) trendAssigned.textContent = assignedCount > 0 ? `↑ ${assignedCount} active` : 'None assigned';
                        
                        // Render urgent attention (only if on home section)
                        if(this.section === 'home') {
                            this.renderUrgentAttention(all);
                        }

                        // Charts (only render if on home section)
                        if(this.section === 'home') {
                        setTimeout(()=>{
                            // Status doughnut
                            const ctx1 = document.getElementById('chart-status');
                            if(ctx1){
                                const existing = ctx1._chartInstance; if(existing){ existing.destroy(); }
                                const statusData = [
                                                all.filter(b=>b.status==='pending_review').length,
                                                all.filter(b=>b.status==='approved').length,
                                                all.filter(b=>b.status==='assigned').length,
                                                all.filter(b=>b.status==='in_progress').length,
                                                all.filter(b=>b.status==='completed').length,
                                                all.filter(b=>b.status==='cancelled').length,
                                                all.filter(b=>b.status==='rejected').length
                                ];
                                const activeTotal = statusData.slice(0, 4).reduce((a, b) => a + b, 0);
                                
                                // Update center text (only if element exists and we're on home section)
                                if(this.section === 'home') {
                                    const centerEl = document.getElementById('chart-status-total');
                                    if(centerEl) centerEl.textContent = activeTotal;
                                }
                                
                                const chart1 = new Chart(ctx1, {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['Pending Review','Approved','Assigned','In Progress','Completed','Cancelled','Rejected'],
                                        datasets: [{
                                            data: statusData,
                                            backgroundColor: [
                                                '#fbbf24', // Pending Review - brighter yellow
                                                '#3b82f6', // Approved - blue
                                                '#06b6d4', // Assigned - cyan
                                                '#f59e0b', // In Progress - orange
                                                '#22c55e', // Completed - green
                                                '#ef4444', // Cancelled - red
                                                '#dc2626'  // Rejected - dark red
                                            ],
                                            borderWidth: 3,
                                            borderColor: '#ffffff',
                                            hoverBorderWidth: 4,
                                            hoverOffset: 8
                                        }]
                                    },
                                    options: { 
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '65%',
                                        layout: {
                                            padding: 10
                                        },
                                        plugins: { 
                                            legend: { 
                                                display: false // We'll use custom legend
                                            },
                                            tooltip: {
                                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                                padding: 12,
                                                titleFont: { size: 14, weight: 'bold' },
                                                bodyFont: { size: 13 },
                                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                                borderWidth: 1,
                                                cornerRadius: 8,
                                                displayColors: true,
                                                callbacks: {
                                                    label: function(context) {
                                                        const label = context.label || '';
                                                        const value = context.parsed || 0;
                                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                                        return `${label}: ${value} booking${value !== 1 ? 's' : ''} (${percentage}%)`;
                                                    }
                                                }
                                            }
                                        },
                                        animation: {
                                            animateRotate: true,
                                            animateScale: true,
                                            duration: 1000,
                                            easing: 'easeOutQuart'
                                        }
                                    }
                                });
                                
                                // Create custom legend as buttons
                                const legendContainer = document.getElementById('chart-status-legend');
                                if(legendContainer) {
                                    const labels = ['Pending Review','Approved','Assigned','In Progress','Completed','Cancelled','Rejected'];
                                    const colors = ['#fbbf24','#3b82f6','#06b6d4','#f59e0b','#22c55e','#ef4444','#dc2626'];
                                    const icons = ['fa-clock','fa-check','fa-user-check','fa-tools','fa-check-circle','fa-times-circle','fa-ban'];
                                    
                                    legendContainer.innerHTML = labels.map((label, index) => {
                                        const value = statusData[index] || 0;
                                        const total = statusData.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        
                                        return `
                                            <button type="button" class="btn w-100 text-start p-3 mb-2 rounded" 
                                                    style="background: ${colors[index]}; color: white; border: none; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';"
                                                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas ${icons[index]}" style="font-size: 1.1rem;"></i>
                                                        <div>
                                                            <div class="fw-semibold" style="font-size: 0.95rem;">${label}</div>
                                                            <div class="small" style="opacity: 0.9;">${value} booking${value !== 1 ? 's' : ''} • ${percentage}%</div>
                                                        </div>
                                                    </div>
                                                    <div class="badge bg-white bg-opacity-25 px-2 py-1 rounded-pill" style="color: white; font-size: 0.85rem;">
                                                        ${value}
                                                    </div>
                                                </div>
                                            </button>
                                        `;
                                    }).join('');
                                }
                                ctx1._chartInstance = chart1;
                            }

                            // Trend line last 14 days
                            const days = [...Array(14)].map((_,i)=>{
                                const d = new Date(); d.setDate(d.getDate() - (13-i));
                                return d;
                            });
                            const labels = days.map(d=> d.toLocaleDateString());
                            const counts = days.map(d=>{
                                const ymd = d.toISOString().slice(0,10);
                                return all.filter(b=>b.date===ymd).length;
                            });
                            const ctx2 = document.getElementById('chart-trend');
                            if(ctx2){
                                const existing2 = ctx2._chartInstance; if(existing2){ existing2.destroy(); }
                                const chart2 = new Chart(ctx2, {
                                    type: 'line',
                                    data: {
                                        labels,
                                        datasets: [{ 
                                            label: 'Bookings', 
                                            data: counts, 
                                            borderColor: '#3b82f6', 
                                            backgroundColor: 'rgba(59,130,246,.2)', 
                                            tension: .3, 
                                            fill: true,
                                            borderWidth: 2,
                                            pointRadius: 3,
                                            pointHoverRadius: 5
                                        }]
                                    },
                                    options: { 
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: { 
                                            y: { 
                                                beginAtZero: true,
                                                ticks: {
                                                    font: { size: 10 },
                                                    stepSize: 1
                                                },
                                                grid: {
                                                    color: 'rgba(0, 0, 0, 0.05)'
                                                }
                                            },
                                            x: {
                                                ticks: {
                                                    font: { size: 9 },
                                                    maxRotation: 45,
                                                    minRotation: 45
                                                },
                                                grid: {
                                                    display: false
                                                }
                                            }
                                        }, 
                                        plugins:{ 
                                            legend: { display: false },
                                            tooltip: {
                                                mode: 'index',
                                                intersect: false
                                            }
                                        } 
                                    }
                                });
                                ctx2._chartInstance = chart2;
                            }
                        }, 0);
                        }

                        // Load technician data and stats
                        this.loadTechStats(all);
                        
                        // Render recent activity (only if on home section)
                        if(this.section === 'home') {
                        this.renderRecentActivity(all);
                        }
                    } else {
                        console.error('Shop: Failed to load bookings:', data.error);
                        container.innerHTML = '<div class="text-danger">Failed to load bookings.</div>';
                    }
                }catch(e){
                    console.error('Shop: Error loading bookings:', e);
                    document.getElementById('shop-bookings').innerHTML = '<div class="text-danger">Error loading bookings.</div>';
                } finally {
                    this.isRefreshing = false;
                }
            },
            refreshHomeSection(){
                // Refresh all home section data when switching to home
                if(this.section !== 'home') return;
                
                // Wait for DOM to be ready
                this.$nextTick(() => {
                    setTimeout(() => {
                        // Reload bookings which will trigger all rendering functions including tech stats
                        this.loadBookings();
                    }, 100);
                });
            },
            async loadTechStats(bookings){
                // Only load stats if on home section
                if(this.section !== 'home') return;
                
                try {
                    const res = await fetch('../technician/technician_list.php');
                    const data = await res.json();
                    const techs = data.technicians || [];
                    const activeTechs = techs.filter(t => !['deactivated','rejected'].includes((t.status||'approved')));
                    
                    // Safely update tech stats (only if elements exist)
                    const totalTechsEl = document.getElementById('total-techs');
                    if(totalTechsEl) totalTechsEl.textContent = techs.length;
                    const activeTechsEl = document.getElementById('active-techs');
                    if(activeTechsEl) activeTechsEl.textContent = activeTechs.length;
                    
                    // Calculate top performing technicians
                    const techStats = {};
                    bookings.filter(b => b.technician_id && b.status === 'completed').forEach(b => {
                        if(!techStats[b.technician_id]) {
                            techStats[b.technician_id] = {
                                name: b.technician_name || 'Unknown',
                                completed: 0,
                                revenue: 0
                            };
                        }
                        techStats[b.technician_id].completed++;
                        techStats[b.technician_id].revenue += Number(b.price) || 0;
                    });
                    
                    const topTechs = Object.values(techStats)
                        .sort((a, b) => b.completed - a.completed)
                        .slice(0, 5);
                    
                    // Only render if on home section
                    if(this.section === 'home') {
                    this.renderTopTechnicians(topTechs);
                    }
                } catch(e) {
                    console.error('Error loading tech stats:', e);
                }
                
                // Load services count (only if on home section)
                if(this.section === 'home') {
                try {
                    const res = await fetch('services_manage.php');
                    const data = await res.json();
                        const totalServicesEl = document.getElementById('total-services');
                        if(totalServicesEl) totalServicesEl.textContent = (data.services || []).length;
                } catch(e) {
                    console.error('Error loading services:', e);
                    }
                }
            },
            getInitials(name){
                return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
            },
            renderTopTechnicians(topTechs){
                const container = document.getElementById('top-technicians');
                if(!container) return;
                
                if(topTechs.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-user-friends fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No completed jobs yet</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = '';
                
                // Podium layout for top 3
                const top3 = topTechs.slice(0, 3);
                const rest = topTechs.slice(3);
                
                if(top3.length >= 3) {
                    // Podium style
                    const podium = document.createElement('div');
                    podium.className = 'd-flex justify-content-center align-items-end gap-2 mb-4';
                    podium.style.minHeight = '180px';
                    
                    // Silver (2nd)
                    const silver = top3[1];
                    const silverDiv = document.createElement('div');
                    silverDiv.className = 'text-center';
                    silverDiv.style.flex = '1';
                    silverDiv.innerHTML = `
                        <div class="mb-2">
                            <div class="w-12 h-12 rounded-full bg-gray-200 mx-auto d-flex align-items-center justify-content-center fw-bold text-gray-600" style="background-image: url('${silver.avatar_url || ''}'); background-size: cover; background-position: center;">
                                ${!silver.avatar_url ? this.getInitials(silver.name) : ''}
                            </div>
                        </div>
                        <div class="bg-light rounded p-3" style="height: 120px;">
                            <div class="fw-semibold small">${silver.name}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">${silver.completed} jobs</div>
                            <div class="mt-2">🥈</div>
                        </div>
                    `;
                    podium.appendChild(silverDiv);
                    
                    // Gold (1st)
                    const gold = top3[0];
                    const goldDiv = document.createElement('div');
                    goldDiv.className = 'text-center';
                    goldDiv.style.flex = '1.2';
                    goldDiv.innerHTML = `
                        <div class="mb-2">
                            <div class="w-14 h-14 rounded-full bg-yellow-100 mx-auto d-flex align-items-center justify-content-center fw-bold text-yellow-600" style="background-image: url('${gold.avatar_url || ''}'); background-size: cover; background-position: center;">
                                ${!gold.avatar_url ? this.getInitials(gold.name) : ''}
                            </div>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-3" style="height: 150px;">
                            <div class="fw-bold">${gold.name}</div>
                            <div class="text-muted small">${gold.completed} jobs</div>
                            <div class="mt-2">🥇</div>
                        </div>
                    `;
                    podium.insertBefore(goldDiv, silverDiv.nextSibling);
                    
                    // Bronze (3rd)
                    const bronze = top3[2];
                    const bronzeDiv = document.createElement('div');
                    bronzeDiv.className = 'text-center';
                    bronzeDiv.style.flex = '1';
                    bronzeDiv.innerHTML = `
                        <div class="mb-2">
                            <div class="w-12 h-12 rounded-full bg-orange-100 mx-auto d-flex align-items-center justify-content-center fw-bold text-orange-600" style="background-image: url('${bronze.avatar_url || ''}'); background-size: cover; background-position: center;">
                                ${!bronze.avatar_url ? this.getInitials(bronze.name) : ''}
                            </div>
                        </div>
                        <div class="bg-light rounded p-3" style="height: 100px;">
                            <div class="fw-semibold small">${bronze.name}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">${bronze.completed} jobs</div>
                            <div class="mt-2">🥉</div>
                        </div>
                    `;
                    podium.appendChild(bronzeDiv);
                    
                    container.appendChild(podium);
                } else {
                    // Fallback for less than 3
                topTechs.forEach((tech, index) => {
                        const medals = ['🥇', '🥈', '🥉'];
                    const div = document.createElement('div');
                    div.className = 'd-flex justify-content-between align-items-center p-3 mb-2 rounded';
                    div.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 d-flex align-items-center justify-content-center fw-bold text-indigo-600" style="background-image: url('${tech.avatar_url || ''}'); background-size: cover; background-position: center;">
                                    ${!tech.avatar_url ? this.getInitials(tech.name) : ''}
                                </div>
                            <div>
                                <div class="fw-semibold">${tech.name}</div>
                                <div class="small text-muted">${tech.completed} completed jobs</div>
                            </div>
                        </div>
                            <span style="font-size: 1.5rem;">${medals[index] || '🏅'}</span>
                        `;
                        container.appendChild(div);
                    });
                }
                
                // Rest of the list
                rest.forEach((tech, index) => {
                    const div = document.createElement('div');
                    div.className = 'd-flex justify-content-between align-items-center p-2 mb-2 rounded border';
                    div.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 d-flex align-items-center justify-content-center fw-bold text-gray-600 small" style="background-image: url('${tech.avatar_url || ''}'); background-size: cover; background-position: center;">
                                ${!tech.avatar_url ? this.getInitials(tech.name) : ''}
                        </div>
                            <div>
                                <div class="fw-semibold small">${tech.name}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">${tech.completed} jobs</div>
                            </div>
                        </div>
                        <span class="badge bg-secondary">#${index + 4}</span>
                    `;
                    container.appendChild(div);
                });
            },
            getRelativeTime(dateString){
                const now = new Date();
                const then = new Date(dateString);
                const diffMs = now - then;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);
                
                if(diffMins < 1) return 'Just now';
                if(diffMins < 60) return `${diffMins} min${diffMins !== 1 ? 's' : ''} ago`;
                if(diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
                if(diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
                return then.toLocaleDateString();
            },
            renderRecentActivity(bookings){
                const container = document.getElementById('recent-bookings');
                if(!container) return;
                
                const recentBookings = bookings
                    .sort((a, b) => {
                        const dateA = new Date(a.created_at || a.date);
                        const dateB = new Date(b.created_at || b.date);
                        return dateB - dateA;
                    })
                    .slice(0, 10);
                
                if(recentBookings.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-clock fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No recent activity</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = '';
                recentBookings.forEach((b, index) => {
                    const statusIcons = {
                        'completed': '🛠️',
                        'in_progress': '⚙️',
                        'assigned': '👤',
                        'pending_review': '⏳',
                        'approved': '✅',
                        'cancelled': '❌',
                        'rejected': '🚫'
                    };
                    const statusIcon = statusIcons[b.status] || '📋';
                    const statusMessages = {
                        'completed': `${b.technician_name || 'Technician'} completed repair on ${b.device_type || 'device'}`,
                        'in_progress': `${b.technician_name || 'Technician'} started repair on ${b.device_type || 'device'}`,
                        'assigned': `Assigned to ${b.technician_name || 'technician'}`,
                        'pending_review': `New booking from ${b.customer_name}`,
                        'approved': `Booking approved for ${b.customer_name}`,
                        'cancelled': `Booking cancelled`,
                        'rejected': `Booking rejected`
                    };
                    const message = statusMessages[b.status] || `${b.service} - ${b.customer_name}`;
                    
                    const div = document.createElement('div');
                    div.className = 'position-relative ps-4 pb-3';
                    if(index < recentBookings.length - 1) {
                        div.style.borderLeft = '2px solid #e5e7eb';
                    }
                    div.innerHTML = `
                        <div class="position-absolute start-0" style="margin-left: -6px;">
                            <div class="rounded-circle bg-white border border-2" style="width: 12px; height: 12px; border-color: #3b82f6 !important;"></div>
                            </div>
                        <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span style="font-size: 1.2rem;">${statusIcon}</span>
                                    <span class="fw-semibold small">${message}</span>
                                    </div>
                                <div class="small text-muted">${b.service}</div>
                                </div>
                            <div class="text-end">
                                <div class="small text-muted">${this.getRelativeTime(b.created_at || b.date)}</div>
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                });
            },
            renderUrgentAttention(bookings){
                const container = document.getElementById('urgent-attention');
                if(!container) return;
                
                const now = new Date();
                const urgent = bookings.filter(b => {
                    if(b.status !== 'pending_review') return false;
                    const created = new Date(b.created_at || b.date);
                    const daysDiff = (now - created) / (1000 * 60 * 60 * 24);
                    return daysDiff > 3;
                });
                
                if(urgent.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 opacity-50 text-success"></i>
                            <p class="mb-0">No overdue items</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = '';
                urgent.forEach(b => {
                    const created = new Date(b.created_at || b.date);
                    const daysDiff = Math.floor((now - created) / (1000 * 60 * 60 * 24));
                    const div = document.createElement('div');
                    div.className = 'border rounded p-3 mb-2 bg-danger bg-opacity-10';
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="fw-semibold">${b.service}</div>
                            <span class="badge bg-danger">${daysDiff} days</span>
                        </div>
                        <div class="small text-muted">${b.customer_name}</div>
                        <div class="small text-muted">${b.device_type || 'Device'}</div>
                    `;
                    container.appendChild(div);
                });
            },
            async loadItems(){
                try {
                    const res = await fetch('../../backend/api/shop-items.php');
                    const data = await res.json();
                    if(data.success) {
                        this.items = data.items || [];
                        this.filterItems();
                    }
                } catch(e) {
                    console.error('Error loading items:', e);
                }
            },
            filterItems(){
                let filtered = [...this.items];
                
                // Search filter
                if(this.itemSearchQuery.trim()) {
                    // Sanitize search query as a safety measure
                    let query = this.itemSearchQuery.toString().replace(/[<>{}[\]();'"`\\/|&*%$#~^!]/g, '');
                    query = query.toLowerCase().trim();
                    filtered = filtered.filter(item => 
                        item.item_name.toLowerCase().includes(query) ||
                        (item.description && item.description.toLowerCase().includes(query)) ||
                        (item.category && item.category.toLowerCase().includes(query))
                    );
                }
                
                // Category filter
                if(this.itemCategoryFilter !== 'all') {
                    filtered = filtered.filter(item => item.category === this.itemCategoryFilter);
                }
                
                // Stock filter
                if(this.itemStockFilter === 'low_stock') {
                    filtered = filtered.filter(item => item.stock_quantity > 0 && item.stock_quantity < 5);
                } else if(this.itemStockFilter === 'out_of_stock') {
                    filtered = filtered.filter(item => item.stock_quantity === 0);
                } else if(this.itemStockFilter === 'in_stock') {
                    filtered = filtered.filter(item => item.stock_quantity > 0);
                }
                
                this.filteredItems = filtered;
            },
            getUniqueCategories(){
                if(!this.items || this.items.length === 0) return [];
                const categories = [...new Set(this.items.map(item => item.category || 'general').filter(cat => cat && cat.trim()))];
                return categories.sort();
            },
            getItemStatusBadge(item){
                if(item.stock_quantity === 0) {
                    return { class: 'bg-danger', icon: 'fa-times-circle', text: 'Out of Stock' };
                } else if(!item.is_available) {
                    return { class: 'bg-secondary', icon: 'fa-ban', text: 'Unavailable' };
                } else {
                    return { class: 'bg-success', icon: 'fa-check-circle', text: 'Available' };
                }
            },
            getStockColorClass(stock){
                if(stock === 0) return 'text-danger';
                if(stock < 5) return 'text-danger fw-bold';
                if(stock < 10) return 'text-warning';
                return 'text-dark';
            },
            async quickAddStock(event, item){
                // Handle both call patterns: @click="quickAddStock(item)" and @click="quickAddStock($event, item)"
                if(event && typeof event === 'object' && !event.preventDefault && !event.stopPropagation) {
                    // event is actually the item, swap them
                    item = event;
                    event = null;
                }
                
                event?.preventDefault?.();
                event?.stopPropagation?.();
                
                if(!item || !item.id) {
                    console.error('Invalid item in quickAddStock:', item);
                    Notiflix.Report.failure('Error', 'Invalid item data. Item ID is missing. Please refresh the page and try again.', 'OK');
                    return;
                }
                
                const { value: quantity } = await Swal.fire({
                    title: 'Add Stock',
                    html: `Current stock: <strong>${item.stock_quantity || 0}</strong><br>How many units to add?`,
                    input: 'number',
                    inputValue: 1,
                    inputAttributes: {
                        min: 1,
                        step: 1
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Add Stock',
                    confirmButtonColor: '#22c55e',
                    inputValidator: (value) => {
                        if (!value || value <= 0) {
                            return 'Please enter a valid quantity';
                        }
                    }
                });
                
                if(!quantity) return;
                
                try {
                    const newStock = (parseInt(item.stock_quantity) || 0) + parseInt(quantity);
                    const formData = new FormData();
                    formData.append('id', String(item.id));
                    formData.append('item_name', String(item.item_name || ''));
                    formData.append('description', String(item.description || ''));
                    formData.append('price', String(item.price || 0));
                    formData.append('stock_quantity', String(newStock));
                    formData.append('category', String(item.category || 'general'));
                    formData.append('is_available', item.is_available ? '1' : '0');
                    formData.append('keep_image', '1');
                    
                    // Debug: Log the item ID being sent
                    console.log('Adding stock - Item ID:', item.id, 'Item:', item);
                    
                    // Verify FormData has the ID
                    const formDataId = formData.get('id');
                    if(!formDataId) {
                        console.error('FormData missing ID!', formData);
                        Notiflix.Report.failure('Error', 'Failed to prepare request. Item ID is missing.', 'OK');
                        return;
                    }
                    
                    const res = await fetch('../../backend/api/shop-items.php', {
                        method: 'PUT',
                        body: formData
                    });
                    
                    const data = await res.json();
                    if(data.success) {
                        Notiflix.Report.success('Success!', `Added ${quantity} units. New stock: ${newStock}`, 'OK');
                        this.loadItems();
                    } else {
                        console.error('Stock update error:', data);
                        Notiflix.Report.failure('Error', data.error || 'Failed to update stock', 'OK');
                    }
                } catch(e) {
                    console.error('Error updating stock:', e);
                    Notiflix.Report.failure('Error', 'Network error occurred: ' + e.message, 'OK');
                }
            },
            async quickDecreaseStock(event, item){
                // Handle both call patterns: @click="quickDecreaseStock(item)" and @click="quickDecreaseStock($event, item)"
                if(event && typeof event === 'object' && !event.preventDefault && !event.stopPropagation) {
                    // event is actually the item, swap them
                    item = event;
                    event = null;
                }
                
                event?.preventDefault?.();
                event?.stopPropagation?.();
                
                if(!item || !item.id) {
                    console.error('Invalid item in quickDecreaseStock:', item);
                    Notiflix.Report.failure('Error', 'Invalid item data. Item ID is missing. Please refresh the page and try again.', 'OK');
                    return;
                }
                
                const currentStock = parseInt(item.stock_quantity) || 0;
                const { value: quantity } = await Swal.fire({
                    title: 'Decrease Stock',
                    html: `Current stock: <strong>${currentStock}</strong><br>How many units to remove?`,
                    input: 'number',
                    inputValue: 1,
                    inputAttributes: {
                        min: 1,
                        max: currentStock,
                        step: 1
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Decrease Stock',
                    confirmButtonColor: '#f59e0b',
                    inputValidator: (value) => {
                        if (!value || value <= 0) {
                            return 'Please enter a valid quantity';
                        }
                        if (parseInt(value) > currentStock) {
                            return `Cannot decrease more than current stock (${currentStock})`;
                        }
                    }
                });
                
                if(!quantity) return;
                
                try {
                    const decreaseAmount = parseInt(quantity);
                    const newStock = Math.max(0, currentStock - decreaseAmount);
                    
                    const formData = new FormData();
                    formData.append('id', String(item.id));
                    formData.append('item_name', String(item.item_name || ''));
                    formData.append('description', String(item.description || ''));
                    formData.append('price', String(item.price || 0));
                    formData.append('stock_quantity', String(newStock));
                    formData.append('category', String(item.category || 'general'));
                    formData.append('is_available', item.is_available ? '1' : '0');
                    formData.append('keep_image', '1');
                    
                    // Debug: Log the item ID being sent
                    console.log('Decreasing stock - Item ID:', item.id, 'Item:', item);
                    
                    // Verify FormData has the ID
                    const formDataId = formData.get('id');
                    if(!formDataId) {
                        console.error('FormData missing ID!', formData);
                        Notiflix.Report.failure('Error', 'Failed to prepare request. Item ID is missing.', 'OK');
                        return;
                    }
                    
                    const res = await fetch('../../backend/api/shop-items.php', {
                        method: 'PUT',
                        body: formData
                    });
                    
                    const data = await res.json();
                    if(data.success) {
                        Notiflix.Report.success('Success!', `Removed ${decreaseAmount} units. New stock: ${newStock}`, 'OK');
                        this.loadItems();
                    } else {
                        console.error('Stock decrease error:', data);
                        Notiflix.Report.failure('Error', data.error || 'Failed to update stock', 'OK');
                    }
                } catch(e) {
                    console.error('Error decreasing stock:', e);
                    Notiflix.Report.failure('Error', 'Network error occurred: ' + e.message, 'OK');
                }
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
                
                // From frontend/shop/shop_dashboard.php to frontend/uploads/shop_items/image.jpg
                // We need: ../uploads/shop_items/image.jpg
                // The image_url in DB is stored as: uploads/shop_items/filename.jpg
                
                if (cleanPath.startsWith('uploads/')) {
                    // Path already has uploads/ prefix - just add ../ to go up from shop/ to frontend/
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
            handleImageError(event) {
                // Log the error for debugging
                console.error('Image failed to load:', event.target.src);
                
                // Replace broken image with placeholder
                event.target.style.display = 'none';
                const container = event.target.parentElement;
                if (container && !container.querySelector('.image-placeholder')) {
                    container.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center h-100 image-placeholder">
                            <div class="text-center">
                                <i class="fas fa-image fa-4x text-muted opacity-50"></i>
                                <p class="text-muted small mt-2 mb-0">Image not available</p>
                                <p class="text-muted" style="font-size: 0.7rem;">Path: ${event.target.src}</p>
                            </div>
                        </div>
                    `;
                }
            },
            showItemModal(item = null){
                if(item){
                    // Ensure we have a valid item with ID
                    if(!item.id) {
                        console.error('Item missing ID:', item);
                        Notiflix.Report.failure('Error', 'Invalid item data. Item ID is missing.', 'OK');
                        return;
                    }
                    // Store the full item object including ID
                    this.editingItem = { ...item, id: parseInt(item.id) };
                    this.itemForm = {
                        item_name: item.item_name || '',
                        description: item.description || '',
                        price: parseFloat(item.price) || 0,
                        stock_quantity: parseInt(item.stock_quantity) || 0,
                        category: item.category || 'general',
                        image_url: item.image_url || '',
                        is_available: item.is_available || false
                    };
                    console.log('Editing item - ID:', this.editingItem.id, 'Item:', this.editingItem);
                } else {
                    this.editingItem = null;
                    this.itemForm = { item_name: '', description: '', price: 0, stock_quantity: 0, category: 'general', image_url: '', is_available: true };
                }
                
                Swal.fire({
                    title: item ? 'Edit Item' : 'Add New Item',
                    html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input id="swal-item-name" class="form-control" placeholder="e.g., iPhone Screen" value="${this.itemForm.item_name}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="swal-item-desc" class="form-control" rows="2" placeholder="Optional description">${this.itemForm.description}</textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Price (₱) <span class="text-danger">*</span></label>
                                <input id="swal-item-price" class="form-control" type="number" step="0.01" min="0" value="${this.itemForm.price}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Stock Quantity</label>
                                <input id="swal-item-stock" class="form-control" type="number" min="0" value="${this.itemForm.stock_quantity}">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Category</label>
                                <input id="swal-item-category" class="form-control" value="${this.itemForm.category}" placeholder="e.g., electronics, parts">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Availability</label>
                                <select id="swal-item-available" class="form-control">
                                    <option value="true" ${this.itemForm.is_available ? 'selected' : ''}>Available</option>
                                    <option value="false" ${!this.itemForm.is_available ? 'selected' : ''}>Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Image (optional)</label>
                            <input type="file" id="swal-item-image" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, or GIF (max 5MB)</small>
                            ${this.itemForm.image_url && this.itemForm.image_url.startsWith('http') ? `
                                <div class="mt-2">
                                    <small class="text-muted">Current image:</small>
                                    <img src="${this.itemForm.image_url}" class="img-thumbnail mt-1" style="max-width: 150px; max-height: 150px;" alt="Current image">
                                </div>
                            ` : (this.itemForm.image_url ? `
                                <div class="mt-2">
                                    <small class="text-muted">Current image:</small>
                                    <img src="../../${this.itemForm.image_url}" class="img-thumbnail mt-1" style="max-width: 150px; max-height: 150px;" alt="Current image">
                                </div>
                            ` : '')}
                        </div>
                    </div>
                `,
                width: '600px',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: item ? 'Update Item' : 'Add Item',
                cancelButtonText: 'Cancel',
                didOpen: () => {
                    // Add real-time sanitization to form fields
                    const nameInput = document.getElementById('swal-item-name');
                    const descInput = document.getElementById('swal-item-desc');
                    const priceInput = document.getElementById('swal-item-price');
                    const stockInput = document.getElementById('swal-item-stock');
                    const categoryInput = document.getElementById('swal-item-category');
                    
                    if(nameInput) {
                        nameInput.addEventListener('input', (e) => {
                            const sanitized = this.sanitizeItemName(e.target.value);
                            if(e.target.value !== sanitized) {
                                e.target.value = sanitized;
                                e.target.style.borderColor = '#ffc107';
                                setTimeout(() => { e.target.style.borderColor = ''; }, 1000);
                            }
                        });
                    }
                    
                    if(descInput) {
                        descInput.addEventListener('input', (e) => {
                            const sanitized = this.sanitizeItemDescription(e.target.value);
                            if(e.target.value !== sanitized) {
                                e.target.value = sanitized;
                                e.target.style.borderColor = '#ffc107';
                                setTimeout(() => { e.target.style.borderColor = ''; }, 1000);
                            }
                        });
                    }
                    
                    if(priceInput) {
                        priceInput.addEventListener('input', (e) => {
                            let value = e.target.value.replace(/[^0-9.]/g, '');
                            const parts = value.split('.');
                            if(parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
                            const numValue = parseFloat(value) || 0;
                            if(numValue < 0) value = '0';
                            else value = numValue.toString();
                            if(e.target.value !== value) {
                                e.target.value = value;
                                e.target.style.borderColor = '#ffc107';
                                setTimeout(() => { e.target.style.borderColor = ''; }, 1000);
                            }
                        });
                    }
                    
                    if(stockInput) {
                        stockInput.addEventListener('input', (e) => {
                            let value = e.target.value.replace(/[^0-9]/g, '');
                            const numValue = parseInt(value) || 0;
                            if(numValue < 0) value = '0';
                            else value = numValue.toString();
                            if(e.target.value !== value) {
                                e.target.value = value;
                                e.target.style.borderColor = '#ffc107';
                                setTimeout(() => { e.target.style.borderColor = ''; }, 1000);
                            }
                        });
                    }
                    
                    if(categoryInput) {
                        categoryInput.addEventListener('input', (e) => {
                            const sanitized = this.sanitizeItemCategory(e.target.value);
                            if(e.target.value !== sanitized) {
                                e.target.value = sanitized;
                                e.target.style.borderColor = '#ffc107';
                                setTimeout(() => { e.target.style.borderColor = ''; }, 1000);
                            }
                        });
                    }
                },
                preConfirm: () => {
                    // Sanitize all inputs
                    const item_name = this.sanitizeItemName(document.getElementById('swal-item-name').value);
                    const description = this.sanitizeItemDescription(document.getElementById('swal-item-desc').value);
                    const price = parseFloat(document.getElementById('swal-item-price').value);
                    const stock_quantity = parseInt(document.getElementById('swal-item-stock').value) || 0;
                    const category = this.sanitizeItemCategory(document.getElementById('swal-item-category').value) || 'general';
                    const is_available = document.getElementById('swal-item-available').value === 'true';
                    const imageFile = document.getElementById('swal-item-image').files[0];
                    
                    if(!item_name || item_name.trim() === '') {
                        Swal.showValidationMessage('Item name is required');
                        return false;
                    }
                    
                    if(!price || price <= 0) {
                        Swal.showValidationMessage('Valid price (greater than 0) is required');
                        return false;
                    }
                    
                    if(stock_quantity < 0) {
                        Swal.showValidationMessage('Stock quantity cannot be negative');
                        return false;
                    }
                    
                    // Check for dangerous characters (double-check)
                    const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/;
                    if(dangerousChars.test(item_name)) {
                        Swal.showValidationMessage('Item name contains invalid characters');
                        return false;
                    }
                    if(dangerousChars.test(category)) {
                        Swal.showValidationMessage('Category contains invalid characters');
                        return false;
                    }
                    
                    // Validate image file if provided
                    if(imageFile) {
                        if(imageFile.size > 5 * 1024 * 1024) {
                            Swal.showValidationMessage('Image file size must be less than 5MB');
                            return false;
                        }
                        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if(!validTypes.includes(imageFile.type)) {
                            Swal.showValidationMessage('Please upload a valid image file (JPG, PNG, or GIF)');
                            return false;
                        }
                    }
                    
                    return { item_name, description, price, stock_quantity, category, is_available, imageFile, keepCurrentImage: !imageFile && this.itemForm.image_url };
                }
                }).then(async (result) => {
                    if(result.isConfirmed && result.value) {
                        await this.saveItem(result.value);
                    }
                });
            },
            async saveItem(formData){
                try {
                    const url = '../../backend/api/shop-items.php';
                    const method = 'POST'; // Always use POST for multipart/form-data
                    
                    // Use FormData for file uploads
                    const formDataObj = new FormData();
                    formDataObj.append('item_name', String(formData.item_name || ''));
                    formDataObj.append('description', String(formData.description || ''));
                    formDataObj.append('price', String(formData.price || 0));
                    formDataObj.append('stock_quantity', String(formData.stock_quantity || 0));
                    formDataObj.append('category', String(formData.category || 'general'));
                    formDataObj.append('is_available', formData.is_available ? '1' : '0');
                    
                    if(this.editingItem) {
                        const itemId = parseInt(this.editingItem.id);
                        if(!itemId || itemId <= 0) {
                            console.error('Invalid item ID in editingItem:', this.editingItem);
                            Notiflix.Report.failure('Error', 'Item ID is missing or invalid. Please refresh and try again.', 'OK');
                            return;
                        }
                        formDataObj.append('id', String(itemId));
                        formDataObj.append('action', 'update'); // Indicate this is an update
                        console.log('Updating item - ID:', itemId);
                    } else {
                        console.log('Creating new item');
                    }
                    
                    // Add image file if provided
                    if(formData.imageFile) {
                        formDataObj.append('image', formData.imageFile);
                    } else if(formData.keepCurrentImage && this.editingItem && this.editingItem.image_url) {
                        // Keep existing image if no new file uploaded
                        formDataObj.append('keep_image', '1');
                    }
                    
                    // Verify FormData has ID for updates
                    if(this.editingItem) {
                        const formDataId = formDataObj.get('id');
                        if(!formDataId) {
                            console.error('FormData missing ID for update!', formDataObj);
                            Notiflix.Report.failure('Error', 'Failed to prepare update request. Item ID is missing.', 'OK');
                            return;
                        }
                        console.log('FormData ID verified:', formDataId);
                    }
                    
                    const res = await fetch(url, {
                        method: method,
                        body: formDataObj
                    });
                    
                    const data = await res.json();
                    
                    if(data.success) {
                        Notiflix.Report.success('Success!', this.editingItem ? 'Item updated successfully' : 'Item added successfully', 'OK');
                        this.editingItem = null; // Clear editing state
                        this.loadItems();
                    } else {
                        console.error('Save item error:', data);
                        Notiflix.Report.failure('Error', data.error || 'Failed to save item', 'OK');
                    }
                } catch(e) {
                    console.error('Error saving item:', e);
                    Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                }
            },
            editItem(item){
                if(!item || !item.id) {
                    console.error('Invalid item in editItem:', item);
                    Notiflix.Report.failure('Error', 'Cannot edit item: Item ID is missing.', 'OK');
                    return;
                }
                console.log('Edit item called - ID:', item.id, 'Item:', item);
                this.showItemModal(item);
            },
            async deleteItem(id){
                Notiflix.Confirm.show(
                    'Delete Item?',
                    'This action cannot be undone',
                    'Delete',
                    'Cancel',
                    () => {
                        fetch(`../../backend/api/shop-items.php?id=${id}`, {
                            method: 'DELETE'
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                Notiflix.Report.success('Deleted!', 'Item removed successfully', 'OK');
                                this.loadItems();
                            } else {
                                Notiflix.Report.failure('Error', data.error || 'Failed to delete item', 'OK');
                            }
                        })
                        .catch(e => {
                            console.error('Error deleting item:', e);
                            Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
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
    
    async function resetTech(id){
        const { value: pwd } = await Swal.fire({ title:'New Password', input:'password', inputPlaceholder:'Enter new password', showCancelButton:true });
        if(!pwd) return;
        const res = await fetch('../technician/technician_update.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials: 'same-origin', body: JSON.stringify({ id, password: pwd }) });
        const data = await res.json();
        if(data.success){ Notiflix.Report.success('Updated','Password reset','OK'); } else { Notiflix.Report.failure('Error', data.error||'Failed','OK'); }
    }
    async function toggleTechActive(id, active){
        Notiflix.Confirm.show(
            active ? 'Activate technician?' : 'Deactivate technician?',
            '',
            active ? 'Activate' : 'Deactivate',
            'Cancel',
            () => {
                fetch('../technician/technician_update.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials: 'same-origin', body: JSON.stringify({ id, active }) })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        // Hard refresh the technician list and currently visible bookings to reflect changes immediately
                        const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
                        if (alpineComponent) {
                            alpineComponent.loadTechs();
                            alpineComponent.loadBookings();
                        }
                        Notiflix.Report.success('Success', active ? 'Technician activated' : 'Technician deactivated', 'OK');
                    } else {
                        const msg = data.error || `Failed (HTTP ${res.status})`;
                        Notiflix.Report.failure('Error', msg, 'OK');
                    }
                })
                .catch(e => {
                    Notiflix.Report.failure('Error', e.message || 'Network error occurred', 'OK');
                });
            },
            () => {}
        );
    }
    
    // Global function to refresh bookings
    async function refreshBookings(){
        try {
            // Get the Alpine component instance
            const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
            if (alpineComponent && alpineComponent.loadBookings) {
                // Store the current section before refreshing
                const currentSection = alpineComponent.section;
                await alpineComponent.loadBookings();
                // Restore the section after refreshing
                alpineComponent.section = currentSection;
            }
        } catch (e) {
            console.error('Error refreshing bookings:', e);
            // Fallback: reload the page
            window.location.reload();
        }
    }
    
    async function manageBooking(bookingId, action, status = null){
        try{
            console.log('Managing booking:', {bookingId, action, status});
            
            let rejectionReason = null;
            let cancellationReason = null;
            
            // For reject action, require a reason
            if (action === 'reject') {
                const { value: reason } = await Swal.fire({
                    title: 'Reject Booking?',
                    html: `<div style="margin-bottom: 10px; color: #333;">Please provide a reason for rejecting this booking:</div>`,
                    input: 'textarea',
                    inputPlaceholder: 'e.g., Cannot repair this device model, insufficient parts, etc.',
                    inputAttributes: {
                        'style': 'margin-top: 10px;'
                    },
                    inputValidator: (value) => {
                        if (!value || value.trim() === '') {
                            return 'Please provide a reason for rejection';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Reject Booking',
                    confirmButtonColor: '#dc3545'
                });
                
                if (reason === undefined) return; // User clicked cancel button
                
                if (!reason || reason.trim() === '') {
                    Notiflix.Report.failure('Error', 'Rejection reason is required', 'OK');
                    return;
                }
                
                rejectionReason = reason.trim();
            }
            
            // For cancel action, require a reason
            if (action === 'cancel') {
                const { value: reason } = await Swal.fire({
                    title: 'Cancel Booking?',
                    html: `<div style="margin-bottom: 10px; color: #333;">Please provide a reason for cancelling this booking:</div>`,
                    input: 'textarea',
                    inputPlaceholder: 'e.g., Customer requested cancellation, unable to complete service, etc.',
                    inputAttributes: {
                        'style': 'margin-top: 10px;'
                    },
                    inputValidator: (value) => {
                        if (!value || value.trim() === '') {
                            return 'Please provide a reason for cancellation';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Cancel Booking',
                    confirmButtonColor: '#dc3545'
                });
                
                if (reason === undefined) return; // User clicked cancel button
                
                if (!reason || reason.trim() === '') {
                    Notiflix.Report.failure('Error', 'Cancellation reason is required', 'OK');
                    return;
                }
                
                cancellationReason = reason.trim();
            }
            
            const payload = { booking_id: bookingId, action };
            if(status) payload.status = status;
            if(rejectionReason) payload.rejection_reason = rejectionReason;
            if(cancellationReason) payload.cancellation_reason = cancellationReason;
            
            // Show loading while server processes (e.g., sending emails)
            let loadingMessage = 'Please wait while we update the booking...';
            if (action === 'approve') {
                loadingMessage = 'Approving booking and sending email notification...';
            } else if (action === 'assign') {
                loadingMessage = 'Assigning technician and sending email notification...';
            } else if (action === 'status' && status === 'completed') {
                loadingMessage = 'Marking as completed and sending email notification...';
            }
            
            Notiflix.Loading.standard(loadingMessage);

            const res = await fetch('booking_manage.php', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                credentials: 'include', // Include cookies for authentication
                body: JSON.stringify(payload) 
            });
            
            console.log('Response status:', res.status);
            
            // Handle 401 Unauthorized separately
            if (res.status === 401) {
                Notiflix.Loading.remove();
                Notiflix.Report.warning('Session Expired', 'Your session has expired. Please log in again.', 'Go to Login', () => {
                    window.location.href = '../auth/index.php';
                });
                return;
            }
            
            // Use the enhanced response handler
            const data = await handleApiResponse(res, false, false); // Don't auto-show messages
            
            // Close loading before showing the result
            Notiflix.Loading.remove();
            
            // Show success message
            Notiflix.Report.success('Success', data.message || 'Booking updated successfully', 'OK');
            
            // Refresh bookings immediately
            refreshBookings();
            
            // Switch to appropriate tab based on action
            const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
            if (alpineComponent && alpineComponent.switchToApprovedTab) {
                if (action === 'approve') {
                    alpineComponent.switchToApprovedTab('approved');
                } else if (action === 'assign') {
                    alpineComponent.switchToApprovedTab('assigned');
                } else if (action === 'status' && status === 'in_progress') {
                    alpineComponent.switchToApprovedTab('in_progress');
                } else if (action === 'status' && status === 'completed') {
                    alpineComponent.switchToApprovedTab('completed');
                } else if (action === 'cancel') {
                    alpineComponent.switchToApprovedTab('cancelled');
                }
            }
            
        }catch(e){
            console.error('Booking management error:', e);
            // Ensure loading is closed on error
            Notiflix.Loading.remove();
            
            // Show specific error message from the API response
            const errorMessage = e.message || 'An unexpected error occurred';
            Notiflix.Report.failure('Error', errorMessage, 'OK');
        }
    }
    async function viewShopRatings(){
        try{
            const res = await fetch(`shop_ratings.php?type=shop&id=<?php echo $user['shop_id']; ?>`);
            const data = await res.json();
            
            if(data.success){
                const rating = data.rating;
                const reviews = data.recent_reviews || [];
                
                let reviewsHtml = '';
                if(reviews.length > 0){
                    reviewsHtml = '<div class="mt-3"><h6>Recent Reviews:</h6><div class="list-group">';
                    reviews.forEach(review => {
                        const stars = '⭐'.repeat(Math.floor(review.rating));
                        reviewsHtml += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>${review.customer_name}</strong>
                                    <span class="text-warning">${stars} ${review.rating}/5</span>
                                </div>
                                <p class="mb-1 mt-2">${review.comment || 'No comment'}</p>
                                <small class="text-muted">${new Date(review.created_at).toLocaleDateString()}</small>
                            </div>
                        `;
                    });
                    reviewsHtml += '</div></div>';
                } else {
                    reviewsHtml = '<div class="mt-3"><p class="text-muted">No reviews yet</p></div>';
                }
                
                Swal.fire({
                    title: `<?php echo h($user['shop_name'] ?? 'Shop'); ?> - Overall Rating`,
                    html: `
                        <div class="text-center">
                            <div class="display-4 text-warning">${rating.average_rating.toFixed(1)} ⭐</div>
                            <p class="mb-2">Based on ${rating.total_reviews} review${rating.total_reviews !== 1 ? 's' : ''}</p>
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: ${(rating.average_rating / 5) * 100}%"></div>
                            </div>
                        </div>
                        ${reviewsHtml}
                    `,
                    width: '600px',
                    showConfirmButton: true,
                    confirmButtonText: 'Close'
                });
            } else {
                console.error('Shop rating API error:', data.error);
                Notiflix.Report.failure('Error', data.error || 'Failed to load shop ratings', 'OK');
            }
        } catch(error){
            console.error('Error loading shop ratings:', error);
            Notiflix.Report.failure('Error', 'Failed to load shop ratings', 'OK');
        }
    }
    
    async function viewTechRatings(techId, techName){
        try{
            console.log('Viewing ratings for technician:', techId, techName);
            const res = await fetch(`shop_ratings.php?type=technician&id=${techId}`);
            const data = await res.json();
            
            if(data.success){
                const rating = data.rating;
                const reviews = data.recent_reviews || [];
                
                let reviewsHtml = '';
                if(reviews.length > 0){
                    reviewsHtml = '<div class="mt-3"><h6>Recent Reviews:</h6><div class="list-group">';
                    reviews.forEach(review => {
                        const stars = '⭐'.repeat(Math.floor(review.rating));
                        reviewsHtml += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>${review.customer_name}</strong>
                                    <span class="text-warning">${stars} ${review.rating}/5</span>
                                </div>
                                <p class="mb-1 mt-2">${review.comment || 'No comment'}</p>
                                <small class="text-muted">${new Date(review.created_at).toLocaleDateString()}</small>
                            </div>
                        `;
                    });
                    reviewsHtml += '</div></div>';
                } else {
                    reviewsHtml = '<div class="mt-3"><p class="text-muted">No reviews yet</p></div>';
                }
                
                if(rating.total_reviews > 0){
                    Swal.fire({
                        title: `${techName} - Rating Details`,
                        html: `
                            <div class="text-center">
                                <div class="display-4 text-warning">${rating.average_rating.toFixed(1)} ⭐</div>
                                <p class="mb-2">Based on ${rating.total_reviews} review${rating.total_reviews !== 1 ? 's' : ''}</p>
                                <div class="progress mb-3" style="height: 20px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: ${(rating.average_rating / 5) * 100}%"></div>
                                </div>
                            </div>
                            ${reviewsHtml}
                        `,
                        width: '600px',
                        showConfirmButton: true,
                        confirmButtonText: 'Close'
                    });
                } else {
                    Swal.fire({
                        title: `${techName} - No Ratings Yet`,
                        html: `
                            <div class="text-center">
                                <div class="display-4 text-muted">⭐</div>
                                <p class="mb-2">This technician hasn't received any reviews yet</p>
                                <p class="text-muted">Ratings will appear here once customers submit reviews for completed bookings</p>
                            </div>
                        `,
                        width: '500px',
                        showConfirmButton: true,
                        confirmButtonText: 'Close'
                    });
                }
            } else {
                console.error('Technician rating API error:', data.error);
                Notiflix.Report.failure('Error', data.error || 'Failed to load ratings', 'OK');
            }
        } catch(error){
            console.error('Error loading technician ratings:', error);
            Notiflix.Report.failure('Error', 'Failed to load technician ratings', 'OK');
        }
    }
    
    async function assignTechnician(bookingId){
        try{
            // Get technicians for this shop
            const res = await fetch('../technician/technician_list.php');
            const data = await res.json();
            const techs = Array.isArray(data.technicians) ? data.technicians : [];
            const activeTechs = techs.filter(t => !['deactivated','rejected'].includes((t.status||'approved')));
            if(!data.success || activeTechs.length === 0){
                Notiflix.Report.warning('No Technicians', 'Please add technicians first', 'OK');
                return;
            }
            
            const { value: techId } = await Swal.fire({
                title: 'Assign Technician',
                input: 'select',
                inputOptions: activeTechs.reduce((acc, tech) => {
                    acc[tech.id] = tech.name;
                    return acc;
                }, {}),
                showCancelButton: true,
                confirmButtonText: 'Assign'
            });
            
            if(techId){
                // Show loading while assigning technician and sending email
                Notiflix.Loading.standard('Assigning technician and sending email notification...');
                
                const payload = { booking_id: bookingId, action: 'assign', technician_id: parseInt(techId) };
                const res2 = await fetch('booking_manage.php', { 
                    method:'POST', 
                    headers:{'Content-Type':'application/json'}, 
                    body: JSON.stringify(payload) 
                });
                
                // Close loading before processing response
                Notiflix.Loading.remove();
                
                // Use the enhanced response handler
                const data2 = await handleApiResponse(res2, false, false); // Don't auto-show messages
                
                // Show success message
                Notiflix.Report.success('Success', data2.message || 'Technician assigned successfully', 'OK');
                // Refresh bookings immediately
                refreshBookings();
            }
        }catch(e){
            // Close loading if still open
            Notiflix.Loading.remove();
            console.error('Assign technician error:', e);
            // Show specific error message from the API response
            const errorMessage = e.message || 'An unexpected error occurred';
            Notiflix.Report.failure('Error', errorMessage, 'OK');
        }
    }
    
    async function provideDiagnosis(bookingId){
        const { value: formValues } = await Swal.fire({
            title: 'Provide Diagnosis & Quotation',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label">Diagnostic Notes <span class="text-danger">*</span></label>
                        <textarea id="swal-diag-notes" class="form-control" rows="4" 
                                  placeholder="Describe the issue and recommended solution..." 
                                  oninput="sanitizeDiagnosticInput(this)"
                                  onkeydown="preventSpecialCharsDiagnostic(event)"
                                  onpaste="handlePasteDiagnostic(event)"
                                  required></textarea>
                        <small class="text-muted">Only letters, numbers, spaces, and common punctuation allowed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimated Cost (₱) <span class="text-danger">*</span></label>
                        <input id="swal-cost" type="number" class="form-control" step="0.01" min="0" 
                               placeholder="2500.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimated Time <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input id="swal-time" type="number" class="form-control" min="0.5" step="0.5" value="3" placeholder="Enter time" required>
                            <select id="swal-time-unit" class="form-select" style="max-width: 120px;">
                                <option value="hours" selected>Hours</option>
                                <option value="days">Days</option>
                            </select>
                        </div>
                    </div>
                </div>
            `,
            width: '600px',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Send Quotation to Customer',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const notes = document.getElementById('swal-diag-notes').value;
                const cost = parseFloat(document.getElementById('swal-cost').value);
                const timeValue = parseFloat(document.getElementById('swal-time').value);
                const timeUnit = document.getElementById('swal-time-unit').value;
                
                if(!notes || !cost || !timeValue || timeValue <= 0) {
                    Swal.showValidationMessage('All fields are required and time must be greater than 0');
                    return false;
                }
                
                // Convert hours to days if needed (store everything in days)
                let days = timeUnit === 'hours' ? timeValue / 24 : timeValue;
                // Round to 1 decimal place for precision
                days = Math.round(days * 10) / 10;
                
                return {
                    diagnostic_notes: notes,
                    estimated_cost: cost,
                    estimated_time_days: days,
                    estimated_time_unit: timeUnit,
                    estimated_time_value: timeValue
                };
            }
        });
        
        if (!formValues) return;
        
        try {
            Notiflix.Loading.standard('Sending quotation and email notification to customer...');
            
            const res = await fetch('booking_manage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: bookingId,
                    action: 'diagnose',
                    ...formValues
                })
            });
            
            // Use the enhanced response handler
            const data = await handleApiResponse(res, false, false); // Don't auto-show messages
            
            Notiflix.Loading.remove();
            
            // Show success message
            Notiflix.Report.success('Success!', data.message || 'Quotation sent to customer', 'OK');
            refreshBookings();
        } catch(e) {
            Notiflix.Loading.remove();
            Notiflix.Report.failure('Error', 'Network error: ' + e.message, 'OK');
        }
    }
    </script>
    
    <script>
    // Input validation functions for diagnostic notes to prevent hacking attempts
    function sanitizeDiagnosticInput(input) {
        if (!input || !input.value) return;
        // Remove dangerous characters: < > { } [ ] ( ) ; ' " ` \ / | & * % $ # @ ~ ^
        const dangerousPattern = /[<>{}[\]();'"`\\/|&*%$#@~^]/g;
        const originalValue = input.value;
        input.value = originalValue.replace(dangerousPattern, '');
        
        // Show visual feedback if content was filtered
        if (originalValue !== input.value) {
            input.style.borderColor = '#ffc107';
            setTimeout(() => {
                input.style.borderColor = '';
            }, 1000);
        }
    }
    
    function preventSpecialCharsDiagnostic(event) {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'];
        if (allowedKeys.includes(event.key)) return;
        
        // Allow Ctrl/Cmd + A/C/V/X/Z
        if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x', 'z'].includes(event.key.toLowerCase())) return;
        
        // Allow: letters, numbers, spaces, ., ,, -, _, :, ?, !, newlines
        // Block: < > { } [ ] ( ) ; ' " ` \ / | & * % $ # @ ~ ^
        const allowedPattern = /^[a-zA-Z0-9\s.,\-_?!:\n\r]$/;
        
        if (!allowedPattern.test(event.key)) {
            event.preventDefault();
            // Show brief warning
            if (event.key.length === 1) {
                const input = event.target;
                input.style.borderColor = '#dc3545';
                setTimeout(() => {
                    input.style.borderColor = '';
                }, 500);
            }
        }
    }
    
    function handlePasteDiagnostic(event) {
        event.preventDefault();
        const paste = (event.clipboardData || window.clipboardData).getData('text');
        const dangerousPattern = /[<>{}[\]();'"`\\/|&*%$#@~^]/g;
        const filtered = paste.replace(dangerousPattern, '');
        
        // Insert filtered content at cursor position
        const input = event.target;
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || input.value.length;
        const currentValue = input.value || '';
        const newValue = currentValue.substring(0, start) + filtered + currentValue.substring(end);
        
        // Update the input value
        input.value = newValue;
        
        // Set cursor position after the inserted text
        try {
            const newCursorPos = start + filtered.length;
            input.setSelectionRange(newCursorPos, newCursorPos);
        } catch (e) {
            input.focus();
        }
        
        // Show notification if content was filtered
        if (paste !== filtered) {
            input.style.borderColor = '#ffc107';
            setTimeout(() => {
                input.style.borderColor = '';
            }, 1000);
        }
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/pwa-register.js"></script>
    
    <!-- Add Technician Modal -->
    <div x-show="showTechModal" 
         x-cloak
         @click.away="showTechModal = false"
         class="modal-overlay"
         :class="{ 'show': showTechModal }">
        <div class="modal-panel" 
             :class="{ 'show': showTechModal }"
             @click.stop>
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Add New Technician</h3>
                    <button @click="showTechModal = false" 
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Modal Form -->
                <form @submit.prevent="createTechnician">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200" 
                                   placeholder="Name" 
                                   x-model="techForm.name"
                                   @input="techForm.name = techForm.name.replace(/[^a-zA-Z0-9\s\-\'\.]/g, '').replace(/\s+/g, ' ')"
                                   maxlength="100"
                                   minlength="2"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200" 
                                   placeholder="Email" 
                                   x-model="techForm.email"
                                   @input="sanitizeEmail($event)"
                                   maxlength="255"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200" 
                                   placeholder="09XXXXXXXXX" 
                                   x-model="techForm.phone"
                                   @input="sanitizePhone($event)"
                                   maxlength="11"
                                   minlength="11"
                                   pattern="^09[0-9]{9}$"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <input :type="showTechPassword ? 'text' : 'password'" 
                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200" 
                                       placeholder="Password" 
                                       x-model="techForm.password"
                                       @input="sanitizePassword($event)"
                                       minlength="6"
                                       maxlength="128"
                                       required>
                                <button type="button" 
                                        @click="showTechPassword = !showTechPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i :class="showTechPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button"
                                    @click="showTechModal = false"
                                    class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-plus mr-2"></i>Add Technician
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div x-show="showServiceModal" 
         x-cloak
         @click.away="showServiceModal = false"
         class="modal-overlay"
         :class="{ 'show': showServiceModal }">
        <div class="modal-panel" 
             :class="{ 'show': showServiceModal }"
             @click.stop>
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Add New Service</h3>
                    <button @click="showServiceModal = false" 
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Modal Form -->
                <form @submit.prevent="addService">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                            <input type="text" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200" 
                                   placeholder="e.g., Battery Replacement" 
                                   x-model="serviceForm.service_name"
                                   @input="sanitizeServiceName($event)"
                                   maxlength="255"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-600 font-semibold">₱</span>
                                <input type="number" 
                                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200" 
                                       placeholder="0.00" 
                                       x-model.number="serviceForm.price"
                                       @input="sanitizePrice($event)"
                                       min="0"
                                       step="0.01"
                                       required>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button"
                                    @click="showServiceModal = false"
                                    class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                                <i class="fas fa-plus mr-2"></i>Add Service
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>


