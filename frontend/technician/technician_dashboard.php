<?php
require_once __DIR__ . '/../../backend/config/database.php';
function redirect_to_login(){ header('Location: ../auth/index.php'); exit; }
$token = $_COOKIE['auth_token'] ?? ($_GET['token'] ?? '');
if (!empty($_GET['token'])) setcookie('auth_token', $_GET['token'], time()+86400, '/');
if (empty($token)) redirect_to_login();
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT u.id,u.name,u.email,u.phone,u.avatar as avatar_url,u.role,t.id AS tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
$stmt->execute([$token]);
$user=$stmt->fetch();
if(!$user||$user['role']!=='technician'||empty($user['tech_id'])) redirect_to_login();
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$avatarCandidate = $user['avatar_url'] ?? '';
$avatarUrl = $avatarCandidate !== '' ? $avatarCandidate : ('https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff');
// Normalize avatar URL to be correct relative to frontend/technician/
if ($avatarUrl && !str_starts_with($avatarUrl, 'http') && !str_starts_with($avatarUrl, 'data:')) {
    $avatarUrl = '../' . ltrim($avatarUrl, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERepair Technician - Manage your repair jobs and schedule">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ERepair Tech">
    <title>Technician Dashboard - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="../../backend/api/favicon.php">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-generator.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-styles.css" rel="stylesheet">
    <script src="../assets/js/erepair-common.js" defer></script>
</head>
<body class="bg-light" x-data="techDashboard()">
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
        /* Gradient text */
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        /* Schedule page enhancements */
        .schedule-jobs {
            max-height: 400px;
            overflow-y: auto;
        }
        .schedule-jobs::-webkit-scrollbar {
            width: 4px;
        }
        .schedule-jobs::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: 2px;
        }
        .schedule-jobs::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 2px;
        }
        .schedule-jobs::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
        }
        /* Day card hover effects */
        .schedule-day-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .schedule-day-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15) !important;
        }
        /* Job card animations */
        .job-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .job-card:hover {
            transform: translateX(6px) translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important;
        }
        /* Chart container fixes */
        #tech-status-chart, #tech-timeline-chart {
            max-height: 180px !important;
            height: 180px !important;
        }
        .chart-container {
            position: relative;
            height: 180px;
            width: 100%;
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
        
        /* Pulse animation for status indicator */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.95); }
        }
        
        /* Smooth sidebar transitions */
        .sidebar {
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        
        .animate-pulse {
            animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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
        }
    </style>
    <!-- Mobile Sidebar Toggle Button -->
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
        <i class="fas fa-bars" x-show="!sidebarOpen"></i>
        <i class="fas fa-times" x-show="sidebarOpen"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false" x-show="sidebarOpen" x-cloak></div>
    
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
                        <div class="small" style="color: rgba(255,255,255,.7);">Technician Portal</div>
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
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='jobs' }" @click="section='jobs'; sidebarOpen = false; jobStatusFilter='all'; renderJobs();">
                        <i class="fas fa-briefcase me-3"></i><span>My Jobs</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='schedule' }" @click="section='schedule'; sidebarOpen = false; renderSchedule();">
                        <i class="fas fa-calendar-week me-3"></i><span>My Schedule</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='completed' }" @click="section='completed'; sidebarOpen = false; renderCompletedJobs();">
                        <i class="fas fa-check-circle me-3"></i><span>Completed Jobs</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='reviews' }" @click="section='reviews'; sidebarOpen = false; loadReviews();">
                        <i class="fas fa-star me-3"></i><span>My Reviews</span>
                    </button>
                </li>
                <li style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(99,102,241,.2);">
                    <button class="w-100 text-start px-4 py-3 logout-btn" @click="logout()">
                        <i class="fas fa-right-from-bracket me-3"></i><span>Logout</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="flex-1 w-100 main-content">
            <div class="container py-4">
                <div x-show="section==='home'" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Modern Welcome Header -->
                    <div class="card border-0 shadow-lg mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(168,85,247,0.1) 100%);">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="fas fa-tools text-primary fs-4"></i>
                                    </div>
                            <div>
                                        <h2 class="h4 fw-bold mb-1 text-dark">Welcome back, <?php echo h($user['name']); ?>!</h2>
                                        <p class="text-muted small mb-0">Here's your job overview and performance metrics</p>
                            </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button class="btn btn-primary btn-sm shadow-sm px-4" style="border-radius: 25px; font-weight: 500;" @click="loadStats(); renderJobs(); renderSchedule();">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-wrap" style="border-top: 1px solid rgba(99,102,241,0.1); padding-top: 1rem;">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-clock text-primary"></i>
                                    <span>Today is <?php echo date('l, F j, Y'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Stats Cards -->
                    <div class="row g-4 mb-4" id="tech-stats">
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6 !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Total Jobs</div>
                                            <div class="h2 fw-bold text-primary mb-2" id="st-total" style="font-size: 2rem;">0</div>
                                    </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-briefcase text-primary" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(22, 163, 74, 0.05) 100%); border-left: 4px solid #22c55e !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(34, 197, 94, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Completed</div>
                                            <div class="h2 fw-bold text-success mb-2" id="st-completed" style="font-size: 2rem;">0</div>
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
                                 style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.08) 0%, rgba(202, 138, 4, 0.05) 100%); border-left: 4px solid #eab308 !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(234, 179, 8, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">In Progress</div>
                                            <div class="h2 fw-bold mb-2" id="st-inprog" style="color: #eab308; font-size: 2rem;">0</div>
                                    </div>
                                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(234, 179, 8, 0.15); width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-spinner" style="color: #eab308; font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.08) 0%, rgba(147, 51, 234, 0.05) 100%); border-left: 4px solid #a855f7 !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(168, 85, 247, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Pending</div>
                                            <div class="h2 fw-bold mb-2" id="st-pending" style="color: #a855f7; font-size: 2rem;">0</div>
                                    </div>
                                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(168, 85, 247, 0.15); width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-clock" style="color: #a855f7; font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Overview & Rating -->
                    <div class="row g-4 mb-4">
                        <div class="col-12 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-line text-primary"></i>
                                </div>
                                        <h5 class="mb-0 fw-bold">Performance Overview</h5>
                            </div>
                                    <div class="text-center p-4 rounded" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%);">
                                        <div class="h2 fw-bold text-primary mb-2" id="st-completion-rate">0%</div>
                                        <div class="text-muted fw-semibold">Completion Rate</div>
                        </div>
                                        </div>
                                    </div>
                                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-star text-warning"></i>
                                    </div>
                                        <h5 class="mb-0 fw-bold">Average Rating</h5>
                                </div>
                                    <div class="text-center p-4 rounded" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.1) 0%, rgba(202, 138, 4, 0.05) 100%);">
                                        <div class="h2 fw-bold mb-2" id="tech-average-rating" style="color: #eab308;">0.0</div>
                                        <div class="mb-2">
                                            <span id="tech-rating-stars" class="text-warning"></span>
                                        </div>
                                        <div class="text-muted fw-semibold">
                                            <span id="tech-total-reviews">0</span> reviews
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-pie text-primary"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Jobs by Status</h5>
                                    </div>
                                    <div class="position-relative" style="height: 250px; overflow: hidden;">
                                    <canvas id="tech-status-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-bar text-success"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Jobs Timeline (Last 7 Days)</h5>
                                    </div>
                                    <div class="position-relative" style="height: 250px; overflow: hidden;">
                                    <canvas id="tech-timeline-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-history text-warning"></i>
                                </div>
                                <h5 class="mb-0 fw-bold">Recent Activity</h5>
                            </div>
                        <div id="recent-activity">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-clock fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">No recent activity</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div x-show="section==='reviews'" class="glass-advanced rounded border p-4 mt-4" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="neon-text mb-1"><i class="fas fa-star text-warning me-2"></i>My Reviews</h5>
                            <small class="text-muted">View all customer reviews and ratings</small>
                        </div>
                        <button class="btn btn-primary btn-sm shadow-sm px-4" style="border-radius: 25px; font-weight: 500;" @click="loadReviews()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                    
                    <!-- Rating Summary -->
                    <div class="row g-3 mb-4" x-show="reviewsData && reviewsData.rating">
                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.08) 0%, rgba(202, 138, 4, 0.05) 100%); border-left: 4px solid #eab308;">
                                <div class="card-body p-4 text-center">
                                    <div class="h2 fw-bold mb-2" style="color: #eab308;" x-text="reviewsData.rating.average_rating || '0.0'">0.0</div>
                                    <div class="mb-2">
                                        <template x-for="i in 5" :key="i">
                                            <i class="fas fa-star" :class="i <= Math.round(reviewsData.rating.average_rating || 0) ? 'text-warning' : 'text-muted'"></i>
                                        </template>
                                    </div>
                                    <div class="text-muted small">Average Rating</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6;">
                                <div class="card-body p-4 text-center">
                                    <div class="h2 fw-bold text-primary mb-2" x-text="reviewsData.rating.total_reviews || 0">0</div>
                                    <div class="text-muted small">Total Reviews</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(22, 163, 74, 0.05) 100%); border-left: 4px solid #22c55e;">
                                <div class="card-body p-4 text-center">
                                    <div class="h2 fw-bold text-success mb-2" x-text="reviewsData.reviews ? reviewsData.reviews.length : 0">0</div>
                                    <div class="text-muted small">Reviews Displayed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reviews List -->
                    <div x-show="reviewsData && reviewsData.reviews && reviewsData.reviews.length > 0">
                        <template x-for="(review, index) in reviewsData.reviews" :key="review.id">
                            <div class="card border-0 shadow-sm mb-3 modern-review-card" 
                                 style="transition: all 0.3s ease; border-left: 4px solid rgba(234, 179, 8, 0.5);"
                                 onmouseover="this.style.transform='translateX(4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-user text-warning"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold" x-text="review.customer_name || 'Anonymous'">Customer</h6>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <template x-for="i in 5" :key="i">
                                                        <i class="fas fa-star" :class="i <= review.rating ? 'text-warning' : 'text-muted'" style="font-size: 0.9rem;"></i>
                                                    </template>
                                                    <span class="ms-2 fw-semibold" x-text="review.rating + '/5'"></span>
                                                </div>
                                                <small class="text-muted" x-text="formatDate(review.created_at)"></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2" x-text="review.shop_name || 'Shop'"></div>
                                        </div>
                                    </div>
                                    <div x-show="review.comment" class="mt-3">
                                        <p class="mb-0 text-dark" x-text="review.comment"></p>
                                    </div>
                                    <div x-show="review.device_description" class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-mobile-alt me-1"></i>
                                            <span x-text="review.device_description"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Empty State -->
                    <div x-show="!reviewsData || !reviewsData.reviews || reviewsData.reviews.length === 0" class="text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3 opacity-50"></i>
                        <h6 class="text-muted mb-2">No Reviews Yet</h6>
                        <p class="text-muted small mb-0">You haven't received any reviews from customers yet.</p>
                    </div>
                </div>
                <div x-show="section==='jobs'" class="glass-advanced rounded border p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="m-0 neon-text">My Jobs (Active)</h6>
                            <small class="text-muted">
                                Manage your assigned repair jobs
                                <span x-show="isRefreshing" class="ms-2">
                                    <i class="fas fa-sync-alt fa-spin text-primary"></i> Refreshing...
                                </span>
                                <span x-show="!isRefreshing && lastRefreshTime" class="ms-2 text-muted">
                                    <small>Last updated: <span x-text="lastRefreshTime"></span></small>
                                </span>
                            </small>
                        </div>
                        
                        <!-- Status Filter Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" type="button" id="jobStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i :class="getJobStatusInfo(jobStatusFilter).icon" class="me-2"></i>
                                <span x-text="getJobStatusInfo(jobStatusFilter).label"></span>
                                <span class="badge rounded-pill ms-2" :class="getJobStatusInfo(jobStatusFilter).badgeClass" x-text="getJobCount(jobStatusFilter)"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="jobStatusDropdown" style="min-width: 280px;">
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': jobStatusFilter === 'all' }" href="#" @click.prevent="jobStatusFilter = 'all'; closeDropdown('jobStatusDropdown'); renderJobs();">
                                        <span><i class="fas fa-list me-2 text-primary"></i>All Active</span>
                                        <span class="badge bg-primary rounded-pill" x-text="getJobCount('all')"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': jobStatusFilter === 'assigned' }" href="#" @click.prevent="jobStatusFilter = 'assigned'; closeDropdown('jobStatusDropdown'); renderJobs();">
                                        <span><i class="fas fa-user-check me-2 text-info"></i>Assigned</span>
                                        <span class="badge bg-info rounded-pill" x-text="getJobCount('assigned')"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': jobStatusFilter === 'in_progress' }" href="#" @click.prevent="jobStatusFilter = 'in_progress'; closeDropdown('jobStatusDropdown'); renderJobs();">
                                        <span><i class="fas fa-spinner me-2 text-primary"></i>In Progress</span>
                                        <span class="badge bg-primary rounded-pill" x-text="getJobCount('in_progress')"></span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': jobStatusFilter === 'today' }" href="#" @click.prevent="jobStatusFilter = 'today'; closeDropdown('jobStatusDropdown'); renderJobs();">
                                        <span><i class="fas fa-calendar-day me-2 text-warning"></i>Today</span>
                                        <span class="badge bg-warning text-dark rounded-pill" x-text="getJobCount('today')"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" :class="{ 'active': jobStatusFilter === 'upcoming' }" href="#" @click.prevent="jobStatusFilter = 'upcoming'; closeDropdown('jobStatusDropdown'); renderJobs();">
                                        <span><i class="fas fa-calendar-plus me-2 text-info"></i>Upcoming</span>
                                        <span class="badge bg-info rounded-pill" x-text="getJobCount('upcoming')"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Search Filter -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control" 
                                placeholder="Search by customer name, service, device, or issue..." 
                                x-model="jobSearchQuery"
                                @input="sanitizeJobSearch($event); renderJobs()"
                                maxlength="100"
                            />
                            <button 
                                class="btn btn-outline-secondary" 
                                type="button" 
                                @click="jobSearchQuery = ''; renderJobs();"
                                x-show="jobSearchQuery"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="jobs-list">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
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
                                    <div x-show="isPollingActive" class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-3 border-white shadow-sm" style="width: 24px; height: 24px;" title="Active"></div>
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
                    <form @submit.prevent="saveProfile">
                                <div class="row g-4">
                            <div class="col-md-6">
                                        <label class="form-label fw-semibold mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-envelope text-primary"></i>
                                            <span>Email Address</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="email" 
                                                   class="form-control form-control-lg border-2 ps-5" 
                                                   x-model="profile.email" 
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
                                       x-model="profile.name"
                                       @input="sanitizeTechnicianName($event)"
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
                                       x-model="profile.phone"
                                       @input="sanitizeTechnicianPhone($event)"
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
                                <div class="mt-4 pt-4 border-top d-flex flex-wrap gap-3">
                                    <button type="button" class="btn btn-outline-secondary px-5 py-2" style="border-radius: 25px;" @click="showChangePassword = false">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm" style="border-radius: 25px;">
                                        <i class="fas fa-save me-2"></i>Update Password
                                    </button>
                            </div>
                        </form>
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
                            <p class="text-muted small mb-0">Stay updated with your job assignments and important updates</p>
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
                                                       'fa-briefcase text-primary': notif.type === 'technician_assigned' || notif.type === 'new_job',
                                                       'fa-check-circle text-success': notif.type === 'booking_completed' || notif.type === 'job_completed',
                                                       'fa-spinner text-info': notif.type === 'job_in_progress',
                                                       'fa-calendar-check text-warning': notif.type === 'schedule_reminder',
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
                                                    <p class="mb-2 text-muted small" x-text="notif.message"></p>
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
                <!-- Schedule Section -->
                <div x-show="section==='schedule'" class="glass-advanced rounded border p-4 mt-4" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <!-- Modern Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1 neon-text d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-calendar-week text-primary"></i>
                        </div>
                                <span>My Schedule</span>
                            </h2>
                            <p class="text-muted small mb-0">View your weekly job assignments</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary px-3" @click="changeWeek(-1)" style="border-radius: 25px 0 0 25px;">
                                    <i class="fas fa-chevron-left me-1"></i>Previous
                                </button>
                                <button type="button" class="btn btn-primary px-4" @click="changeWeek(0)" style="border-radius: 0;">
                                    <i class="fas fa-calendar-week me-1"></i>This Week
                                </button>
                                <button type="button" class="btn btn-outline-primary px-3" @click="changeWeek(1)" style="border-radius: 0 25px 25px 0;">
                                    Next<i class="fas fa-chevron-right ms-1"></i>
                                </button>
                            </div>
                            <div class="badge bg-primary px-4 py-2 fs-6 shadow-sm" x-text="weekRange" style="border-radius: 20px;"></div>
                        </div>
                    </div>
                    <div id="schedule-grid" class="row g-4"></div>
                </div>
                <div x-show="section==='completed'" class="glass-advanced rounded border p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="m-0 neon-text"><i class="fas fa-check-circle text-success me-2"></i>Completed Jobs History</h6>
                            <small class="text-muted">View your completed repair jobs</small>
                        </div>
                        <div class="badge bg-success fs-6">
                            <span x-text="completedJobsCount"></span> completed jobs
                        </div>
                    </div>
                    <div id="completed-jobs-list">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function techDashboard(){
        return {
            section:'home',
            sidebarOpen: false, // Mobile sidebar toggle state
            isPollingActive: true,
            pollingInterval: null,
            isRefreshing: false,
            lastRefreshTime: null,
            notifications: [],
            unreadCount: 0,
            completedJobsCount: 0,
            reviewsData: null,
            profile:{ name: <?php echo json_encode($user['name']); ?>, email: <?php echo json_encode($user['email']); ?>, phone: <?php echo json_encode($user['phone'] ?? ''); ?>, password:'' },
            avatarUrl: <?php 
                $avatarUrl = $user['avatar_url'] ?: ('https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff');
                // Normalize avatar URL to be correct relative to frontend/technician/
                if ($avatarUrl && !str_starts_with($avatarUrl, 'http') && !str_starts_with($avatarUrl, 'data:')) {
                    $avatarUrl = '../' . ltrim($avatarUrl, '/');
                }
                echo json_encode($avatarUrl);
            ?>,
            showChangePassword: false,
            jobs: [],
            jobFilter: 'all', // Keep for backward compatibility
            jobStatusFilter: 'all', // New status filter: 'all', 'assigned', 'in_progress', 'today', 'upcoming'
            jobSearchQuery: '', // Search query for jobs
            weekDays: [],
            weekRange: '',
            weekStart: null,
            async init(){ 
                await this.loadWebsiteLogo(); // Load admin's website logo for favicon
                this.loadJobs();
                this.loadNotifications();
                this.loadReviews(); // Load reviews on init
                this.startPolling();
            },
            startPolling(){
                this.isPollingActive = true;
                this.pollingInterval = setInterval(() => {
                    console.log('Technician: Auto-refresh triggered');
                    this.loadJobs();
                    this.loadNotifications(); // Also poll notifications
                }, 10000); // Poll every 10 seconds (same as other dashboards)
                console.log('Technician: AJAX auto-refresh started (every 10 seconds)');
            },
            stopPolling(){
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                    this.isPollingActive = false;
                }
                console.log('Technician: AJAX polling stopped');
            },
            async loadNotifications(){
                try {
                    const res = await fetch('../auth/notifications.php');
                    const data = await res.json();
                    if(data.success) {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
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
                try {
                    console.log('Handling notification:', notif);
                    
                    // Mark notification as read
                    if(!notif.is_read) {
                        try {
                            await fetch('../auth/notifications.php', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ notification_id: notif.id }) 
                            });
                            notif.is_read = true;
                            if(this.unreadCount > 0) this.unreadCount = this.unreadCount - 1;
                        } catch(e) {
                            console.warn('Failed to mark notification as read:', e);
                        }
                    }
                    
                    // Navigate based on notification type/title
                    const title = (notif.title || '').toLowerCase();
                    const message = (notif.message || '').toLowerCase();
                    
                    console.log('Notification content:', { title, message });
                    
                    // Determine which section to navigate to based on notification content
                    if(title.includes('new job') || title.includes('assigned') || message.includes('assigned')) {
                        // Navigate to My Jobs section
                        this.section = 'jobs';
                        this.jobFilter = 'all';
                        this.jobStatusFilter = 'all';
                        console.log('Navigating to jobs section');
                        await this.loadJobs(); // Load jobs first, then render
                        this.renderJobs();
                    } else if(title.includes('completed') || message.includes('completed')) {
                        // Navigate to Completed Jobs section
                        this.section = 'completed';
                        console.log('Navigating to completed section');
                        await this.loadJobs(); // Load jobs first, then render
                    } else if(title.includes('schedule') || message.includes('schedule')) {
                        // Navigate to Schedule section
                        this.section = 'schedule';
                        console.log('Navigating to schedule section');
                        await this.loadJobs(); // Load jobs first, then render
                    } else {
                        // Default: navigate to My Jobs
                        this.section = 'jobs';
                        this.jobFilter = 'all';
                        this.jobStatusFilter = 'all';
                        console.log('Navigating to default jobs section');
                        await this.loadJobs(); // Load jobs first, then render
                        this.renderJobs();
                    }
                    
                    // Show a brief toast notification
                    Notiflix.Notify.info('Navigated to ' + this.section.charAt(0).toUpperCase() + this.section.slice(1), {
                        position: 'right-top',
                        timeout: 2000,
                        clickToClose: true
                    });
                } catch(e) {
                    console.error('Error handling notification:', e);
                    // Show error message to user
                    Notiflix.Notify.failure('Error handling notification', {
                        position: 'right-top',
                        timeout: 3000,
                        clickToClose: true
                    });
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
                        // Normalize for frontend/technician/ (one level deep from frontend/)
                        if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                            if (logoUrl.startsWith('../backend/')) {
                                // Path is relative to frontend/, need to add ../ for technician/
                                logoUrl = '../' + logoUrl; // ../backend/... becomes ../../backend/...
                            } else if (logoUrl.startsWith('backend/')) {
                                logoUrl = '../../' + logoUrl;
                            } else if (!logoUrl.startsWith('../') && !logoUrl.startsWith('/')) {
                                logoUrl = '../../backend/uploads/logos/' + logoUrl.split('/').pop();
                            }
                        }
                        this.updateFavicon(logoUrl);
                        console.log('Technician dashboard: Favicon updated to:', logoUrl);
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
                fetch('technician_profile_photo_upload.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(r=>r.json())
                    .then(data=>{ 
                        if(data.success){ 
                            // Normalize avatar URL to be correct relative to frontend/technician/
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
                
                if(newPwd!==confirmPwd){ 
                    Notiflix.Report.failure('Error','Passwords do not match','OK'); 
                    return; 
                }
                
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
            async loadJobs(){
                try{
                    console.log('Technician: Loading jobs...');
                    this.isRefreshing = true;
                    const res = await fetch('tech_jobs.php', { 
                        cache: 'no-store',
                        headers: {
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Pragma': 'no-cache',
                            'Expires': '0'
                        }
                    });
                    console.log('Response status:', res.status);
                    
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                    }
                    
                    const data = await res.json();
                    console.log('Response data:', data);
                    
                    if(data.success){ 
                        const oldJobsCount = this.jobs.length;
                        this.jobs = data.jobs||[]; 
                        this.lastRefreshTime = new Date().toLocaleTimeString();
                        
                        console.log(`Technician: Jobs refreshed at ${this.lastRefreshTime}. Count: ${oldJobsCount} → ${this.jobs.length}`);
                        // Only render if we're on the jobs section
                        if(this.section === 'jobs') {
                            this.renderJobs();
                        }
                        this.renderStats(); 
                    }
                    else { 
                        console.error('Technician: Failed to load jobs:', data.error || 'Unknown error');
                        // Only update jobs-list if we're on the jobs section
                        if(this.section === 'jobs') {
                            document.getElementById('jobs-list').innerHTML = '<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i><p class="mb-0">Failed to load jobs. Please try again.</p></div>'; 
                        }
                    }
                }catch(e){ 
                    console.error('Technician: Error loading jobs:', e);
                    // Only update jobs-list if we're on the jobs section
                    if(this.section === 'jobs') {
                        document.getElementById('jobs-list').innerHTML = '<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i><p class="mb-0">Error loading jobs. Please check your connection.</p></div>'; 
                    }
                } finally {
                    this.isRefreshing = false;
                }
            },
            getJobStatusInfo(status) {
                const statusMap = {
                    'all': {
                        icon: 'fas fa-list',
                        label: 'All Active',
                        badgeClass: 'bg-primary text-white'
                    },
                    'assigned': {
                        icon: 'fas fa-user-check',
                        label: 'Assigned',
                        badgeClass: 'bg-info text-white'
                    },
                    'in_progress': {
                        icon: 'fas fa-spinner',
                        label: 'In Progress',
                        badgeClass: 'bg-primary text-white'
                    },
                    'today': {
                        icon: 'fas fa-calendar-day',
                        label: 'Today',
                        badgeClass: 'bg-warning text-dark'
                    },
                    'upcoming': {
                        icon: 'fas fa-calendar-plus',
                        label: 'Upcoming',
                        badgeClass: 'bg-info text-white'
                    }
                };
                return statusMap[status] || {
                    icon: 'fas fa-list',
                    label: 'All Active',
                    badgeClass: 'bg-primary text-white'
                };
            },
            getJobCount(status) {
                const jobs = Array.isArray(this.jobs) ? this.jobs : [];
                const activeJobs = jobs.filter(j => j.status !== 'completed');
                const todayStr = new Date().toISOString().slice(0,10);
                
                if (status === 'all') {
                    return activeJobs.length;
                } else if (status === 'assigned') {
                    return activeJobs.filter(j => j.status === 'assigned').length;
                } else if (status === 'in_progress') {
                    return activeJobs.filter(j => j.status === 'in_progress').length;
                } else if (status === 'today') {
                    return activeJobs.filter(j => j.date === todayStr).length;
                } else if (status === 'upcoming') {
                    return activeJobs.filter(j => j.date > todayStr).length;
                }
                return 0;
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
            loadStats(){
                this.renderStats();
                this.loadReviews(); // Also load reviews to show rating on home
            },
            renderStats(){
                const jobs = Array.isArray(this.jobs) ? this.jobs : [];
                const total = jobs.length;
                const completed = jobs.filter(j=>j.status==='completed').length;
                const inprog = jobs.filter(j=>j.status==='in_progress').length;
                const pending = jobs.filter(j=>['pending','approved','assigned'].includes(j.status)).length;
                const set = (id,val)=>{ const el=document.getElementById(id); if(el) el.textContent = val; };
                set('st-total', total);
                set('st-completed', completed);
                set('st-inprog', inprog);
                set('st-pending', pending);
                this.completedJobsCount = completed;

                // Calculate performance metrics
                const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;
                set('st-completion-rate', completionRate + '%');

                // Render charts
                this.renderCharts(jobs, completed, inprog, pending);
                this.renderRecentActivity(jobs);
            },
            async loadReviews(){
                try {
                    const res = await fetch('../../backend/api/technician/reviews.php', {
                        credentials: 'include'
                    });
                    const data = await res.json();
                    if(data.success && data.data) {
                        this.reviewsData = data.data;
                        
                        // Update rating display on home page
                        if(this.reviewsData.rating) {
                            const avgRating = this.reviewsData.rating.average_rating || 0;
                            const totalReviews = this.reviewsData.rating.total_reviews || 0;
                            
                            const ratingEl = document.getElementById('tech-average-rating');
                            const starsEl = document.getElementById('tech-rating-stars');
                            const reviewsEl = document.getElementById('tech-total-reviews');
                            
                            if(ratingEl) ratingEl.textContent = avgRating.toFixed(1);
                            if(reviewsEl) reviewsEl.textContent = totalReviews;
                            
                            if(starsEl) {
                                let starsHtml = '';
                                const fullStars = Math.round(avgRating);
                                for(let i = 1; i <= 5; i++) {
                                    if(i <= fullStars) {
                                        starsHtml += '<i class="fas fa-star text-warning"></i>';
                                    } else {
                                        starsHtml += '<i class="far fa-star text-muted"></i>';
                                    }
                                }
                                starsEl.innerHTML = starsHtml;
                            }
                        }
                    } else {
                        console.error('Failed to load reviews:', data.error);
                    }
                } catch(e) {
                    console.error('Error loading reviews:', e);
                }
            },
            formatDate(dateString){
                if(!dateString) return '';
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                
                if(diffDays === 0) return 'Today';
                if(diffDays === 1) return 'Yesterday';
                if(diffDays < 7) return diffDays + ' days ago';
                if(diffDays < 30) return Math.floor(diffDays / 7) + ' weeks ago';
                if(diffDays < 365) return Math.floor(diffDays / 30) + ' months ago';
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            },
            renderCharts(jobs, completed, inprog, pending){
                try {
                    // Check if Chart.js is loaded
                    if (typeof Chart === 'undefined') {
                        console.warn('Chart.js is not loaded, skipping chart rendering');
                        return;
                    }
                    
                    // Destroy existing charts if they exist
                    const statusCanvas = document.getElementById('tech-status-chart');
                    const timelineCanvas = document.getElementById('tech-timeline-chart');
                    
                    if(!statusCanvas || !timelineCanvas) return;

                    if(statusCanvas._chartInstance) statusCanvas._chartInstance.destroy();
                    if(timelineCanvas._chartInstance) timelineCanvas._chartInstance.destroy();

                // Status Pie Chart
                const statusChart = new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'In Progress', 'Pending/Assigned'],
                        datasets: [{
                            data: [completed, inprog, pending],
                            backgroundColor: ['#22c55e', '#eab308', '#a855f7'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.2,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 12,
                                    font: { size: 11 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                statusCanvas._chartInstance = statusChart;

                // Timeline Bar Chart (Last 7 days)
                const days = [...Array(7)].map((_, i) => {
                    const d = new Date();
                    d.setDate(d.getDate() - (6 - i));
                    return d;
                });
                const labels = days.map(d => d.toLocaleDateString('en-US', { weekday: 'short' }));
                const completedData = days.map(d => {
                    const ymd = d.toISOString().slice(0, 10);
                    return jobs.filter(j => j.date === ymd && j.status === 'completed').length;
                });
                const inProgressData = days.map(d => {
                    const ymd = d.toISOString().slice(0, 10);
                    return jobs.filter(j => j.date === ymd && j.status === 'in_progress').length;
                });

                const timelineChart = new Chart(timelineCanvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Completed',
                                data: completedData,
                                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                borderColor: '#22c55e',
                                borderWidth: 1
                            },
                            {
                                label: 'In Progress',
                                data: inProgressData,
                                backgroundColor: 'rgba(234, 179, 8, 0.8)',
                                borderColor: '#eab308',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.8,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    font: { size: 11 }
                                }
                            },
                            x: {
                                ticks: {
                                    font: { size: 11 }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 12,
                                    font: { size: 11 }
                                }
                            }
                        }
                    }
                });
                timelineCanvas._chartInstance = timelineChart;
                } catch (e) {
                    console.error('Error rendering charts:', e);
                }
            },
            renderRecentActivity(jobs){
                const activityWrap = document.getElementById('recent-activity');
                if(!activityWrap) return;

                // Get recent jobs (last 5, sorted by date descending)
                const recentJobs = jobs
                    .sort((a, b) => {
                        const dateCompare = b.date.localeCompare(a.date);
                        if(dateCompare !== 0) return dateCompare;
                        return (b.time_slot || '').localeCompare(a.time_slot || '');
                    })
                    .slice(0, 5);

                if(recentJobs.length === 0){
                    activityWrap.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-clock fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No recent activity</p>
                        </div>
                    `;
                    return;
                }

                activityWrap.innerHTML = '';
                recentJobs.forEach((j, index) => {
                    const statusColors = {
                        'pending': 'warning',
                        'approved': 'primary',
                        'assigned': 'info',
                        'in_progress': 'primary',
                        'completed': 'success',
                        'cancelled': 'danger'
                    };
                    const statusColor = statusColors[j.status] || 'secondary';
                    const statusIcons = {
                        'completed': 'fa-check-circle',
                        'in_progress': 'fa-spinner',
                        'assigned': 'fa-user-check',
                        'pending': 'fa-clock'
                    };
                    const statusIcon = statusIcons[j.status] || 'fa-briefcase';

                    const div = document.createElement('div');
                    div.className = `d-flex align-items-start gap-3 pb-3 mb-3 ${index < recentJobs.length - 1 ? 'border-bottom' : ''}`;
                    div.innerHTML = `
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-${statusColor} bg-opacity-10 p-3">
                                <i class="fas ${statusIcon} text-${statusColor}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">${j.service}</div>
                                    <div class="small text-muted">${j.customer_name} • ${j.shop_name}</div>
                                    <div class="small text-muted">
                                        <i class="fas fa-calendar me-1"></i>${j.date} 
                                        <i class="fas fa-clock ms-2 me-1"></i>${j.time_slot || 'No time'}
                                    </div>
                                </div>
                                <span class="badge bg-${statusColor}">${j.status}</span>
                            </div>
                        </div>
                    `;
                    activityWrap.appendChild(div);
                });
            },
            renderCompletedJobs(){
                const wrap = document.getElementById('completed-jobs-list');
                const jobs = Array.isArray(this.jobs) ? this.jobs : [];
                const completedJobs = jobs.filter(j => j.status === 'completed').sort((a, b) => {
                    // Sort by date descending (most recent first)
                    return b.date.localeCompare(a.date) || (b.time_slot || '').localeCompare(a.time_slot || '');
                });
                
                if(completedJobs.length === 0){
                    wrap.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-check fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">No completed jobs yet</p>
                        </div>
                    `;
                    return;
                }
                
                wrap.innerHTML = '';
                completedJobs.forEach(j => {
                    const div = document.createElement('div');
                    div.className = 'border rounded p-4 mb-3 glass-advanced';
                    div.style.background = 'linear-gradient(135deg, rgba(34, 197, 94, 0.05), rgba(22, 163, 74, 0.05))';
                    div.style.border = '1px solid rgba(34, 197, 94, 0.2)';
                    div.style.transition = 'all 0.3s ease';
                    
                    const phoneHref = j.customer_phone ? `tel:${encodeURIComponent(j.customer_phone)}` : '';
                    const mailHref = j.customer_email ? `mailto:${encodeURIComponent(j.customer_email)}` : '';
                    
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="fw-bold fs-5">${j.service}</div>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Completed
                                    </span>
                                </div>
                                
                                ${j.device_type ? `
                                    <div class="mb-2">
                                        <span class="badge bg-light text-dark me-2">
                                            <i class="fas fa-mobile-alt me-1"></i>${j.device_type}
                                        </span>
                                    </div>
                                ` : ''}
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-store me-1"></i><strong>Shop:</strong> ${j.shop_name}
                                        </div>
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-user me-1"></i><strong>Customer:</strong> ${j.customer_name}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar me-1"></i><strong>Date:</strong> ${j.date} · 
                                            <i class="fas fa-clock me-1"></i><strong>Time:</strong> ${j.time_slot || 'No time slot'}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        ${j.device_issue_description ? `
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i><strong>Issue:</strong> ${j.device_issue_description}
                                            </div>
                                        ` : ''}
                                        ${j.description ? `
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-info-circle me-1"></i><strong>Notes:</strong> ${j.description}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                                
                                ${j.device_photo ? `
                                    <div class="mb-3">
                                        <img src="../${j.device_photo}" class="img-thumbnail" style="max-width:200px; max-height:150px; object-fit: cover;" alt="Device Photo">
                                    </div>
                                ` : ''}
                                
                                <div class="d-flex gap-2 flex-wrap">
                                    ${phoneHref ? `
                                        <a class="btn btn-outline-success btn-sm" href="${phoneHref}">
                                            <i class="fas fa-phone me-1"></i>Call Customer
                                        </a>
                                    ` : ''}
                                    ${mailHref ? `
                                        <a class="btn btn-outline-info btn-sm" href="${mailHref}">
                                            <i class="fas fa-envelope me-1"></i>Email Customer
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    wrap.appendChild(div);
                });
            },
            computeWeek(){
                const now = this.weekStart instanceof Date ? new Date(this.weekStart) : new Date();
                const day = now.getDay(); // 0=Sun
                const mondayOffset = (day === 0 ? -6 : 1 - day);
                const monday = new Date(now);
                monday.setDate(now.getDate() + mondayOffset);
                monday.setHours(0,0,0,0);
                this.weekDays = [...Array(7)].map((_,i)=>{
                    const d = new Date(monday);
                    d.setDate(monday.getDate()+i);
                    return d;
                });
                const fmt = d=> d.toLocaleDateString(undefined,{month:'short',day:'numeric'});
                this.weekRange = `${fmt(this.weekDays[0])} - ${fmt(this.weekDays[6])}`;
            },
            changeWeek(direction){
                // direction: -1 previous, 0 this week, 1 next
                if(direction === 0){ this.weekStart = null; }
                else {
                    const base = this.weekStart instanceof Date ? new Date(this.weekStart) : new Date();
                    const day = base.getDay();
                    const mondayOffset = (day === 0 ? -6 : 1 - day);
                    const monday = new Date(base);
                    monday.setDate(base.getDate() + mondayOffset + (direction*7));
                    monday.setHours(0,0,0,0);
                    this.weekStart = monday;
                }
                this.computeWeek();
                this.renderSchedule();
            },
            renderSchedule(){
                if(!this.weekDays.length) this.computeWeek();
                const grid = document.getElementById('schedule-grid');
                if(!grid) return;
                const jobs = Array.isArray(this.jobs) ? this.jobs : [];
                const map = {};
                this.weekDays.forEach(d=>{ map[d.toISOString().slice(0,10)] = []; });
                jobs.forEach(j=>{ if(map[j.date]) map[j.date].push(j); });
                grid.innerHTML = '';
                
                this.weekDays.forEach((d, index) => {
                    const ymd = d.toISOString().slice(0,10);
                    const dayJobs = (map[ymd]||[]).sort((a,b)=> (a.time_slot||'').localeCompare(b.time_slot||''));
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6 col-lg-4 col-xl-3';
                    
                    const isToday = d.toDateString() === new Date().toDateString();
                    const isWeekend = d.getDay() === 0 || d.getDay() === 6;
                    const pretty = d.toLocaleDateString(undefined,{weekday:'short', month:'short', day:'numeric'});
                    
                    // Create job cards with enhanced styling
                    const items = dayJobs.map(j => {
                        const statusColors = {
                            'pending': 'bg-warning text-dark',
                            'approved': 'bg-primary',
                            'assigned': 'bg-info',
                            'in_progress': 'bg-primary',
                            'completed': 'bg-success',
                            'cancelled': 'bg-danger'
                        };
                        const statusIcons = {
                            'pending': 'fa-clock',
                            'approved': 'fa-check',
                            'assigned': 'fa-user-check',
                            'in_progress': 'fa-spinner',
                            'completed': 'fa-check-circle',
                            'cancelled': 'fa-times'
                        };
                        
                        const statusClass = statusColors[j.status] || 'bg-secondary';
                        const statusIcon = statusIcons[j.status] || 'fa-briefcase';
                        
                        return `
                            <div class="card border-0 shadow-sm mb-3 job-card" style="background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.9)); border-left: 3px solid ${statusClass.includes('primary') ? '#6366f1' : statusClass.includes('success') ? '#22c55e' : statusClass.includes('warning') ? '#eab308' : statusClass.includes('info') ? '#3b82f6' : '#6b7280'} !important; transition: all 0.3s ease;">
                                <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-clock text-muted" style="font-size: 0.85rem;"></i>
                                            <span class="small text-muted fw-semibold">${j.time_slot || '—'}</span>
                                    </div>
                                        <span class="badge ${statusClass} shadow-sm" style="border-radius: 12px;">
                                        <i class="fas ${statusIcon} me-1"></i>${j.status}
                                    </span>
                                </div>
                                    <div class="fw-bold mb-2 text-dark" style="font-size: 0.95rem;">${j.service}</div>
                                ${j.device_type ? `
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <i class="fas fa-mobile-alt text-muted" style="font-size: 0.75rem;"></i>
                                            <span class="small text-muted">${j.device_type}</span>
                                    </div>
                                ` : ''}
                                ${j.shop_name ? `
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <i class="fas fa-store text-muted" style="font-size: 0.75rem;"></i>
                                            <span class="small text-muted">${j.shop_name}</span>
                                    </div>
                                ` : ''}
                                ${j.customer_name ? `
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <i class="fas fa-user text-muted" style="font-size: 0.75rem;"></i>
                                            <span class="small text-muted">${j.customer_name}</span>
                                    </div>
                                ` : ''}
                                ${j.price ? `
                                        <div class="d-flex align-items-center gap-1 mt-2 pt-2 border-top">
                                            <i class="fas fa-dollar-sign text-success" style="font-size: 0.75rem;"></i>
                                            <span class="small text-success fw-bold">₱${Number(j.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </div>
                                ` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    // Create day card with enhanced modern styling
                    const dayCardClass = isToday 
                        ? 'border-primary border-2 shadow-lg' 
                        : isWeekend 
                            ? 'border-warning border-2' 
                            : 'border border-2';
                    const dayCardBg = isToday 
                        ? 'background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(99,102,241,0.03) 100%);' 
                        : isWeekend 
                            ? 'background: linear-gradient(135deg, rgba(234,179,8,0.05) 0%, rgba(234,179,8,0.02) 100%);' 
                            : 'background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);';
                    const dayHeaderClass = isToday ? 'text-primary fw-bold' : isWeekend ? 'text-warning fw-semibold' : 'text-dark fw-semibold';
                    
                    col.innerHTML = `
                        <div class="card border-0 shadow-sm h-100 schedule-day-card ${dayCardClass}" style="${dayCardBg} transition: all 0.3s ease;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                <div class="${dayHeaderClass}">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-calendar-day ${isToday ? 'text-primary' : isWeekend ? 'text-warning' : 'text-muted'}" style="font-size: 1.1rem;"></i>
                                            <div>
                                                <div class="fw-bold fs-6">${pretty}</div>
                                                ${isToday ? '<span class="badge bg-primary rounded-pill mt-1" style="font-size: 0.65rem;">Today</span>' : ''}
                                </div>
                                </div>
                            </div>
                                    <div class="badge ${isToday ? 'bg-primary' : isWeekend ? 'bg-warning text-dark' : 'bg-light text-dark'} px-3 py-1 shadow-sm" style="border-radius: 15px;">
                                        <i class="fas fa-briefcase me-1"></i>${dayJobs.length} job${dayJobs.length !== 1 ? 's' : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="card-body px-4 pb-4">
                            <div class="schedule-jobs">
                                ${items || `
                                        <div class="text-center py-5">
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-calendar-times fa-2x text-muted opacity-50"></i>
                                            </div>
                                            <div class="text-muted small fw-semibold">No jobs scheduled</div>
                                    </div>
                                `}
                                </div>
                            </div>
                        </div>
                    `;
                    grid.appendChild(col);
                });
            },
            renderJobs(){
                const wrap = document.getElementById('jobs-list');
                if (!wrap) return; // Safety check
                
                const jobs = Array.isArray(this.jobs) ? this.jobs : [];
                // Exclude completed jobs from "My Jobs" section
                const activeJobs = jobs.filter(j => j.status !== 'completed');
                
                if(activeJobs.length===0){ 
                    wrap.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-briefcase fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">No active jobs assigned to you yet</p>
                        </div>
                    `; 
                    return; 
                }
                
                const todayStr = new Date().toISOString().slice(0,10);
                // Sanitize search query as a safety measure
                let searchQuery = (this.jobSearchQuery || '').toString().replace(/[<>{}[\]();'"`\\/|&*%$#~^!]/g, '');
                searchQuery = searchQuery.toLowerCase().trim();
                
                // Apply status filter
                let filtered = activeJobs.filter(j => {
                    // Status filter
                    if (this.jobStatusFilter === 'assigned') {
                        return j.status === 'assigned';
                    } else if (this.jobStatusFilter === 'in_progress') {
                        return j.status === 'in_progress';
                    } else if (this.jobStatusFilter === 'today') {
                        return j.date === todayStr;
                    } else if (this.jobStatusFilter === 'upcoming') {
                        return j.date > todayStr;
                    }
                    // 'all' or default - show all active jobs
                    return true;
                });
                
                // Apply search filter
                if (searchQuery) {
                    filtered = filtered.filter(j => {
                        const searchableText = [
                            j.customer_name || '',
                            j.service || '',
                            j.device_type || '',
                            j.device_issue_description || '',
                            j.description || '',
                            j.shop_name || ''
                        ].join(' ').toLowerCase();
                        return searchableText.includes(searchQuery);
                    });
                }
                if(filtered.length===0){ 
                    wrap.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-filter fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">No jobs matching this filter</p>
                        </div>
                    `; 
                    return; 
                }
                wrap.innerHTML = '';
                filtered.forEach(j=>{
                    const div = document.createElement('div');
                    div.className = 'border rounded p-4 mb-3 glass-advanced';
                    div.style.background = 'linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7))';
                    div.style.border = '1px solid rgba(99, 102, 241, 0.2)';
                    div.style.transition = 'all 0.3s ease';
                    
                    const statusClass = {
                        'pending': 'bg-warning text-dark',
                        'approved': 'bg-primary',
                        'assigned': 'bg-info',
                        'in_progress': 'bg-primary',
                        'completed': 'bg-success',
                        'cancelled': 'bg-danger'
                    }[j.status] || 'bg-secondary';
                    
                    const statusIcons = {
                        'pending': 'fa-clock',
                        'approved': 'fa-check',
                        'assigned': 'fa-user-check',
                        'in_progress': 'fa-spinner',
                        'completed': 'fa-check-circle',
                        'cancelled': 'fa-times'
                    };
                    
                    const phoneHref = j.customer_phone ? `tel:${encodeURIComponent(j.customer_phone)}` : '';
                    const mailHref = j.customer_email ? `mailto:${encodeURIComponent(j.customer_email)}` : '';
                    
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="fw-bold fs-5">${j.service}</div>
                                    <span class="badge ${statusClass}">
                                        <i class="fas ${statusIcons[j.status] || 'fa-briefcase'} me-1"></i>${j.status}
                                    </span>
                                </div>
                                
                                ${j.device_type ? `
                                    <div class="mb-2">
                                        <span class="badge bg-light text-dark me-2">
                                            <i class="fas fa-mobile-alt me-1"></i>${j.device_type}
                                        </span>
                                    </div>
                                ` : ''}
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-store me-1"></i><strong>Shop:</strong> ${j.shop_name}
                                        </div>
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-user me-1"></i><strong>Customer:</strong> ${j.customer_name}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar me-1"></i><strong>Date:</strong> ${j.date} · 
                                            <i class="fas fa-clock me-1"></i><strong>Time:</strong> ${j.time_slot || 'No time slot'}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        ${j.device_issue_description ? `
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i><strong>Issue:</strong> ${j.device_issue_description}
                                            </div>
                                        ` : ''}
                                        ${j.description ? `
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-info-circle me-1"></i><strong>Notes:</strong> ${j.description}
                                            </div>
                                        ` : ''}
                                        ${j.price ? `
                                            <div class="small text-success fw-bold">
                                                <i class="fas fa-dollar-sign me-1"></i><strong>Price:</strong> ₱${Number(j.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                                
                                ${j.device_photo ? `
                                    <div class="mb-3">
                                        <img src="../${j.device_photo}" class="img-thumbnail" style="max-width:200px; max-height:150px; object-fit: cover;" alt="Device Photo">
                                    </div>
                                ` : ''}
                                
                                <div class="d-flex gap-2 flex-wrap">
                                    ${phoneHref ? `
                                        <a class="btn btn-outline-success btn-sm" href="${phoneHref}">
                                            <i class="fas fa-phone me-1"></i>Call Customer
                                        </a>
                                    ` : ''}
                                    ${mailHref ? `
                                        <a class="btn btn-outline-info btn-sm" href="${mailHref}">
                                            <i class="fas fa-envelope me-1"></i>Email Customer
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <div class="btn-group-vertical btn-group-sm">
                                    ${j.status==='assigned' ? `
                                        <button class="btn btn-primary btn-sm mb-2" onclick="updateJobStatus(${j.id}, 'in_progress')">
                                            <i class="fas fa-play me-1"></i>Start Work
                                        </button>
                                    ` : ''}
                                    ${j.status==='in_progress' ? `
                                        <button class="btn btn-success btn-sm mb-2" onclick="updateJobStatus(${j.id}, 'completed')">
                                            <i class="fas fa-check me-1"></i>Mark Complete
                                        </button>
                                    ` : ''}
                                    ${j.status==='pending' || j.status==='approved' ? `
                                        <div class="text-muted small">
                                            <i class="fas fa-hourglass-half me-1"></i>Waiting for assignment
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    wrap.appendChild(div);
                });
            },
            sanitizeTechnicianName(event){
                // Remove dangerous characters from technician name
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#@~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '').replace(/\s+/g, ' ');
                if(originalValue !== sanitized){
                    this.profile.name = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            sanitizeTechnicianPhone(event){
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
                if(this.profile.phone !== value){
                    this.profile.phone = value;
                    if(event.target.value !== value){
                        event.target.style.borderColor = '#ffc107';
                        setTimeout(() => {
                            event.target.style.borderColor = '';
                        }, 1000);
                    }
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
            sanitizeJobSearch(event){
                // Remove dangerous characters from job search query
                // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and @ for email searches
                const dangerousChars = /[<>{}[\]();'"`\\/|&*%$#~^!]/g;
                const originalValue = event.target.value;
                const sanitized = originalValue.replace(dangerousChars, '');
                if(originalValue !== sanitized){
                    this.jobSearchQuery = sanitized;
                    // Visual feedback
                    event.target.style.borderColor = '#ffc107';
                    setTimeout(() => {
                        event.target.style.borderColor = '';
                    }, 1000);
                }
            },
            async saveProfile(){
                try {
                    // Validate and sanitize inputs before sending
                    const name = (this.profile.name || '').trim();
                    const phone = (this.profile.phone || '').trim();
                    
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
                    
                    // Validate phone number format
                    if(phone && !/^09[0-9]{9}$/.test(phone)){
                        Notiflix.Report.failure('Error', 'Phone number must start with 09 and be exactly 11 digits', 'OK');
                        return;
                    }
                    
                const res = await fetch('technician_profile_update.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials: 'same-origin', body: JSON.stringify(this.profile) });
                const data = await res.json();
                    if(data.success){ 
                        Notiflix.Report.success('Saved','Profile updated','OK'); 
                        this.profile.password=''; 
                    } else { 
                        Notiflix.Report.failure('Error',data.error||'Failed','OK'); 
                    }
                } catch(e) {
                    console.error('Profile update error:', e);
                    Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                }
            },
            async logout(){
                Notiflix.Confirm.show(
                    'Logout',
                    'Are you sure you want to logout?',
                    'Yes, logout',
                    'Cancel',
                    async () => {
                        try {
                            const res = await fetch('../auth/logout.php', { 
                                method:'POST', 
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'same-origin' 
                            });
                            const data = await res.json();
                            if(data.success){
                                Notiflix.Report.success('Logged out', 'You have been successfully logged out', 'OK');
                                setTimeout(() => {
                                    window.location.href = '../auth/index.php';
                                }, 1200);
                            } else {
                                window.location.href = '../auth/login.html';
                            }
                        } catch (e) {
                            console.error('Logout error:', e);
                            Notiflix.Report.failure('Error', 'Network error during logout', 'OK');
                            setTimeout(() => {
                                window.location.href = '../auth/login.html';
                            }, 1500);
                        }
                    },
                    () => {
                        // Cancel callback - do nothing
                    }
                );
            }
        }
    }
    async function updateJobStatus(bookingId, status){
        try{
            console.log('Updating job status:', {bookingId, status});
            // Show loading while server processes (e.g., sending emails)
            Notiflix.Loading.standard('Please wait while we update the job status...');
            
            const res = await fetch('job_status_update.php', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                body: JSON.stringify({ booking_id: bookingId, status: status }) 
            });
            
            console.log('Response status:', res.status);
            
            // Use the enhanced response handler
            const data = await handleApiResponse(res, false, false); // Don't auto-show messages
            
            // Close loading before showing the result
            Notiflix.Loading.remove();
            
            // Show success message
            Notiflix.Report.success('Success', data.message || 'Job status updated successfully', 'OK');
            
            // Reload jobs immediately
            const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
            if (alpineComponent && alpineComponent.loadJobs) {
                alpineComponent.loadJobs();
            }
        }catch(e){
            console.error('Update job status error:', e);
            // Ensure loading is closed on error
            Notiflix.Loading.remove();
            Notiflix.Report.failure('Error', `Network error: ${e.message}`, 'OK');
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/pwa-register.js"></script>
</body>
</html>


