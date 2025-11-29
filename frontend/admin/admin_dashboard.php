<?php
require_once __DIR__ . '/../../backend/config/database.php';

function redirect_to_login() {
    header('Location: ../auth/index.php');
    exit;
}

// Read token from cookie or passthrough query (and persist to cookie)
$token = $_COOKIE['auth_token'] ?? ($_GET['token'] ?? '');
if (!empty($_GET['token'])) {
    setcookie('auth_token', $_GET['token'], time() + 86400, '/');
}
if (empty($token)) redirect_to_login();

$db = (new Database())->getConnection();

// Verify session + admin role
try {
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.role, u.logo_url, u.avatar as avatar_url FROM users u INNER JOIN sessions s ON s.user_id = u.id WHERE s.token = ? AND s.expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    // Backwards compatibility when logo_url or avatar_url columns are not yet added
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.role FROM users u INNER JOIN sessions s ON s.user_id = u.id WHERE s.token = ? AND s.expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) { 
        $user['logo_url'] = null;
        $user['avatar_url'] = null;
    }
}
if (!$user || $user['role'] !== 'admin') redirect_to_login();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERepair Admin - Manage your electronics repair booking platform">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ERepair Admin">
    <title>Admin Dashboard - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="../../backend/api/favicon.php">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-generator.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-swal.css" rel="stylesheet">
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-swal.js"></script>
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
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
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .chart-container { transition: all 0.3s ease; }
        .chart-container:hover { transform: translateY(-1px); }
        
        /* Prevent chart overflow */
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        
        .card-body canvas {
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Ensure proper containment */
        .row.g-4 {
            margin-left: 0;
            margin-right: 0;
        }
        
        .row.g-4 > [class*="col-"] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        /* Custom Scrollbar */
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
        
        /* Mobile Sidebar Toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1051;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 14px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(99,102,241,0.4);
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(99,102,241,0.5);
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
        
        /* Pulse animation for status indicator */
        @keyframes pulse-dot {
            0%, 100% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.2);
                opacity: 0.8;
            }
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
            
            /* Ensure sidebar buttons are clickable */
            .sidebar.open .nav-btn,
            .sidebar.open .logout-btn,
            .sidebar.open button {
                pointer-events: auto !important;
                z-index: 1;
            }
        }
    </style>
</head>
<body class="bg-light" x-data="adminDashboard()" x-init="init()">
    <!-- Mobile Sidebar Toggle Button -->
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
        <i class="fas fa-bars" x-show="!sidebarOpen"></i>
        <i class="fas fa-times" x-show="sidebarOpen"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false" x-show="sidebarOpen" x-cloak></div>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar shadow-md min-h-screen text-white" :class="{ 'open': sidebarOpen }" style="position: fixed; left: 0; width: 280px; background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); top: 0; height: 100vh; overflow-y: auto; z-index: 1050; border-right: 1px solid rgba(99,102,241,.2);" @click.stop>
            <div class="p-4 brand-wrap">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center logo-container shadow-lg" style="width: 52px; height: 52px; box-shadow: 0 4px 15px rgba(99,102,241,.4);">
                        <i class="fas fa-screwdriver-wrench text-white fs-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl fw-bold m-0" style="letter-spacing:.3px; color: #ffffff; text-shadow: 0 2px 8px rgba(0,0,0,.3);">ERepair</h2>
                        <div class="small" style="color: rgba(255,255,255,.7);">Admin Panel</div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="position-relative">
                            <img :src="avatarUrl" 
                                 class="rounded-circle border border-3 d-block mx-auto shadow-lg" 
                                 style="width:80px;height:80px;object-fit:cover; border-color: rgba(99,102,241,.4) !important; box-shadow: 0 4px 20px rgba(99,102,241,.3);" 
                                 alt="Admin Avatar">
                            <!-- Auto-refresh indicator -->
                            <div x-show="isPollingActive" class="position-absolute bottom-0 end-0 bg-success rounded-circle shadow-sm" style="width: 18px; height: 18px; border: 3px solid #0f172a; animation: pulse-dot 2s infinite;" title="Active connection"></div>
                        </div>
                    </div>
                    <div class="fw-bold mb-1" style="color:#ffffff !important; font-size: 1rem;">
                        <?php echo htmlspecialchars($user['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
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
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='shop_owners' }" @click="section='shop_owners'; sidebarOpen = false">
                        <i class="fas fa-store me-3"></i><span>Shop Owners</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn w-100 text-start px-4 py-3" :class="{ 'active': section==='customers' }" @click="section='customers'; sidebarOpen = false; loadCustomers()">
                        <i class="fas fa-users me-3"></i><span>Customers</span>
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
                <!-- Home Section with Integrated Reports -->
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
                                        <i class="fas fa-chart-line text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h2 class="h4 fw-bold mb-1 text-dark">Welcome back, <?php echo h($user['name']); ?>!</h2>
                                        <p class="text-muted small mb-0">Here is a comprehensive overview of the system with detailed analytics</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button class="btn btn-primary btn-sm shadow-sm px-4" style="border-radius: 25px; font-weight: 500;" @click="reloadAll()">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh
                                    </button>
                                    <button class="btn btn-sm shadow-sm px-4" :class="isPollingActive ? 'btn-success' : 'btn-outline-secondary'" style="border-radius: 25px; font-weight: 500;" @click="togglePolling()">
                                        <i class="fas me-2" :class="isPollingActive ? 'fa-pause' : 'fa-play'"></i>
                                        <span x-text="isPollingActive ? 'Auto-refresh ON' : 'Auto-refresh OFF'"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-wrap" style="border-top: 1px solid rgba(99,102,241,0.1); padding-top: 1rem;">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-sync-alt text-primary"></i>
                                    <span x-show="lastRefreshTime" x-text="'Last updated: ' + lastRefreshTime"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modern Quick Action Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6 !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Total Customers</div>
                                            <div class="h2 fw-bold text-primary mb-2" x-text="stats.totalCustomers" style="font-size: 2rem;">0</div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-users text-primary" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.05) 100%); border-left: 4px solid #6366f1 !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(99, 102, 241, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Shop Owners</div>
                                            <div class="h2 fw-bold mb-2" x-text="stats.totalShopOwners" style="color: #6366f1; font-size: 2rem;">0</div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-store" style="color: #6366f1; font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6 !important; transition: all 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Total Technicians</div>
                                            <div class="h2 fw-bold text-primary mb-2" x-text="stats.totalTechnicians" style="font-size: 2rem;">0</div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-user-gear text-primary" style="font-size: 1.5rem;"></i>
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
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Total Bookings</div>
                                            <div class="h2 fw-bold text-success mb-2" x-text="stats.totalBookings" style="font-size: 2rem;">0</div>
                                        </div>
                                        <div class="bg-success bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-calendar-check text-success" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Status Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-6 col-md-4">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(251, 146, 60, 0.08) 0%, rgba(234, 88, 12, 0.05) 100%); border-left: 4px solid #fb923c !important; transition: all 0.3s ease;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(251, 146, 60, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Bookings - Pending</div>
                                            <div class="h2 fw-bold mb-2" x-text="reports.bookings.pending" style="color: #ea580c; font-size: 2rem;">0</div>
                                        </div>
                                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(251, 146, 60, 0.15); width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-clock" style="color: #ea580c; font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(22, 163, 74, 0.05) 100%); border-left: 4px solid #22c55e !important; transition: all 0.3s ease;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(34, 197, 94, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Bookings - Approved</div>
                                            <div class="h2 fw-bold text-success mb-2" x-text="reports.bookings.approved" style="font-size: 2rem;">0</div>
                                        </div>
                                        <div class="bg-success bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="card border-0 shadow-sm h-100 modern-stat-card" 
                                 style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6 !important; transition: all 0.3s ease;"
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25) !important';"
                                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="text-muted small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Bookings - Completed</div>
                                            <div class="h2 fw-bold text-primary mb-2" x-text="reports.bookings.completed" style="font-size: 2rem;">0</div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                            <i class="fas fa-flag-checkered text-primary" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 1 -->
                    <div class="row g-4 mb-4">
                        <!-- Booking Status Pie Chart -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-pie text-primary"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Booking Status Distribution</h5>
                                    </div>
                                    <div class="position-relative" style="height: 250px; overflow: hidden;">
                                        <canvas id="bookingStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Monthly Trends Chart -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-line text-success"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Monthly Booking Trends</h5>
                                    </div>
                                    <div class="position-relative" style="height: 250px; overflow: hidden;">
                                        <canvas id="monthlyTrendsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 2 -->
                    <div class="row g-4 mb-4">
                        <!-- Technicians per Shop Bar Chart -->
                        <div class="col-md-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-info bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-chart-bar text-info"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Technicians per Shop</h5>
                                    </div>
                                    <div class="position-relative" style="height: 250px; overflow: hidden;">
                                        <canvas id="techniciansChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shop Performance -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-4">
                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-trophy text-warning"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold">Top Performing Shops</h5>
                                    </div>
                                    <div class="space-y-2" style="max-height: 250px; overflow-y: auto;">
                                        <template x-for="shop in reports.topShops" :key="shop.shop_id">
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded mb-2" style="background: rgba(99,102,241,0.05); border-left: 3px solid #6366f1; transition: all 0.2s ease;"
                                                 onmouseover="this.style.background='rgba(99,102,241,0.1)'; this.style.transform='translateX(4px)';"
                                                 onmouseout="this.style.background='rgba(99,102,241,0.05)'; this.style.transform='';">
                                                <div>
                                                    <div class="fw-semibold text-dark" x-text="shop.shop_name"></div>
                                                    <div class="small text-muted" x-text="shop.bookings + ' bookings'"></div>
                                                </div>
                                                <div class="badge bg-primary px-3 py-2 rounded-pill" x-text="shop.rating + '★'"></div>
                                            </div>
                                        </template>
                                        <div x-show="!reports.topShops || reports.topShops.length === 0" class="text-center py-4 text-muted">
                                            <i class="fas fa-store fa-2x mb-2 opacity-50"></i>
                                            <p class="mb-0 small">No shop data available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shop Owners Section -->
                <div x-show="section==='shop_owners'" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-store text-primary fs-5"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold">Shop Owners</h4>
                                        <p class="text-muted small mb-0">Manage and review shop owner registrations</p>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm shadow-sm px-4" style="border-radius: 25px; font-weight: 500;" @click="reloadAll()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh
                                </button>
                            </div>
                            
                            <!-- Modern Status Tabs -->
                            <div class="d-flex gap-2 mb-4 flex-wrap" style="border-bottom: 2px solid rgba(99,102,241,0.1); padding-bottom: 1rem;">
                                <button class="btn btn-sm px-4 py-2 shadow-sm" 
                                        style="border-radius: 20px; font-weight: 500; transition: all 0.3s ease;"
                                        :class="shopOwnerFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'"
                                        @click="shopOwnerFilter = 'all'"
                                        onmouseover="if(this.classList.contains('btn-outline-primary')) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.3)'; }"
                                        onmouseout="if(this.classList.contains('btn-outline-primary')) { this.style.transform=''; this.style.boxShadow=''; }">
                                    <i class="fas fa-list me-2"></i>All
                                    <span class="badge rounded-pill ms-2 px-2 py-1" :class="shopOwnerFilter === 'all' ? 'bg-light text-dark' : 'bg-primary text-white'" x-text="owners.length"></span>
                                </button>
                                <button class="btn btn-sm px-4 py-2 shadow-sm" 
                                        style="border-radius: 20px; font-weight: 500; transition: all 0.3s ease;"
                                        :class="shopOwnerFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning'"
                                        @click="shopOwnerFilter = 'pending'"
                                        onmouseover="if(this.classList.contains('btn-outline-warning')) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(251, 146, 60, 0.3)'; }"
                                        onmouseout="if(this.classList.contains('btn-outline-warning')) { this.style.transform=''; this.style.boxShadow=''; }">
                                    <i class="fas fa-clock me-2"></i>Pending
                                    <span class="badge rounded-pill ms-2 px-2 py-1" :class="shopOwnerFilter === 'pending' ? 'bg-light text-dark' : 'bg-warning text-dark'" x-text="owners.filter(o => o.approval_status === 'pending').length"></span>
                                </button>
                                <button class="btn btn-sm px-4 py-2 shadow-sm" 
                                        style="border-radius: 20px; font-weight: 500; transition: all 0.3s ease;"
                                        :class="shopOwnerFilter === 'approved' ? 'btn-success' : 'btn-outline-success'"
                                        @click="shopOwnerFilter = 'approved'"
                                        onmouseover="if(this.classList.contains('btn-outline-success')) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(34, 197, 94, 0.3)'; }"
                                        onmouseout="if(this.classList.contains('btn-outline-success')) { this.style.transform=''; this.style.boxShadow=''; }">
                                    <i class="fas fa-check-circle me-2"></i>Approved
                                    <span class="badge rounded-pill ms-2 px-2 py-1" :class="shopOwnerFilter === 'approved' ? 'bg-light text-dark' : 'bg-success text-white'" x-text="owners.filter(o => o.approval_status === 'approved').length"></span>
                                </button>
                                <button class="btn btn-sm px-4 py-2 shadow-sm" 
                                        style="border-radius: 20px; font-weight: 500; transition: all 0.3s ease;"
                                        :class="shopOwnerFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'"
                                        @click="shopOwnerFilter = 'rejected'"
                                        onmouseover="if(this.classList.contains('btn-outline-danger')) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.3)'; }"
                                        onmouseout="if(this.classList.contains('btn-outline-danger')) { this.style.transform=''; this.style.boxShadow=''; }">
                                    <i class="fas fa-times-circle me-2"></i>Rejected
                                    <span class="badge rounded-pill ms-2 px-2 py-1" :class="shopOwnerFilter === 'rejected' ? 'bg-light text-dark' : 'bg-danger text-white'" x-text="owners.filter(o => o.approval_status === 'rejected').length"></span>
                                </button>
                            </div>
                            
                            <!-- Modern Card-based Layout -->
                            <div class="row g-3">
                                <template x-for="(o, index) in filteredShopOwners" :key="o.id">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm h-100" 
                                             style="transition: all 0.3s ease; border-left: 4px solid;"
                                             :style="o.approval_status==='approved' ? 'border-left-color: #22c55e;' : (o.approval_status==='rejected' ? 'border-left-color: #ef4444;' : 'border-left-color: #fb923c;')"
                                             onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                                             onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                            <div class="card-body p-4">
                                                <div class="row align-items-center">
                                                    <div class="col-md-3 mb-3 mb-md-0">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-store text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="fw-bold mb-1 text-dark" x-text="o.shop_name"></h6>
                                                                <div class="badge px-3 py-1 rounded-pill" 
                                                                     :class="o.approval_status==='approved' ? 'bg-success' : (o.approval_status==='rejected' ? 'bg-danger' : 'bg-warning text-dark')" 
                                                                     x-text="o.approval_status"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3 mb-md-0">
                                                        <div class="small text-muted mb-1"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Address</div>
                                                        <div class="text-dark" x-text="o.shop_address" style="font-size: 0.9rem;"></div>
                                                    </div>
                                                    <div class="col-md-3 mb-3 mb-md-0">
                                                        <div class="small text-muted mb-1"><i class="fas fa-user me-2 text-primary"></i>Owner</div>
                                                        <div class="text-dark fw-semibold" x-text="o.owner_name"></div>
                                                        <div class="small text-muted mt-1"><i class="fas fa-envelope me-2"></i><span x-text="o.email"></span></div>
                                                    </div>
                                                    <div class="col-md-2 text-end">
                                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                                            <button class="btn btn-sm btn-outline-secondary shadow-sm" style="border-radius: 20px;" @click="viewDocs(o.id)" title="View Documents">
                                                                <i class="fas fa-file"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-success shadow-sm" style="border-radius: 20px;" @click="approveOwner(o.id)" :disabled="o.approval_status==='approved'" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger shadow-sm" style="border-radius: 20px;" @click="rejectOwner(o.id)" :disabled="o.approval_status==='rejected'" title="Reject">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <template x-if="o.approval_status==='rejected' && o.rejection_reason">
                                                            <div class="mt-3">
                                                                <div class="alert alert-danger py-2 px-3 mb-0 rounded" style="font-size: 0.85rem; text-align: left;">
                                                                    <strong><i class="fas fa-exclamation-triangle me-1"></i>Reason:</strong> <span x-text="o.rejection_reason"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filteredShopOwners.length === 0" class="col-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-store fa-3x text-muted mb-3 opacity-50"></i>
                                            <h6 class="text-muted mb-0" x-text="shopOwnerFilter === 'all' ? 'No shop owners found' : 'No ' + shopOwnerFilter + ' shop owners found'"></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customers Section -->
                <div x-show="section==='customers'" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 transform translate-y-4" 
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-users text-primary fs-5"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold">Customer Accounts</h4>
                                        <p class="text-muted small mb-0">Manage and view customer information</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="position-relative">
                                        <i class="fas fa-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 1;"></i>
                                        <input type="text" 
                                               class="form-control form-control-sm ps-5" 
                                               placeholder="Search customers..." 
                                               x-model="customerSearch" 
                                               @input="filterCustomers()" 
                                               style="width: 280px; border-radius: 25px; border: 1px solid #dee2e6; padding-left: 2.5rem;">
                                    </div>
                                    <button class="btn btn-primary btn-sm shadow-sm px-4" style="border-radius: 25px; font-weight: 500;" @click="loadCustomers()">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Modern Card-based Layout -->
                            <div class="row g-3">
                                <template x-for="customer in filteredCustomers" :key="customer.id">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm h-100" 
                                             style="transition: all 0.3s ease; border-left: 4px solid;"
                                             :style="customer.email_verified ? 'border-left-color: #22c55e;' : 'border-left-color: #fb923c;'"
                                             onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                                             onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                            <div class="card-body p-4">
                                                <div class="row align-items-center">
                                                    <div class="col-md-2 mb-3 mb-md-0">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <img :src="getCustomerAvatarUrl(customer)" 
                                                                 alt="Avatar" 
                                                                 class="rounded-circle border border-2 shadow-sm" 
                                                                 style="width: 56px; height: 56px; object-fit: cover; border-color: rgba(99,102,241,0.3) !important;"
                                                                 @error="$el.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(customer.name) + '&background=4f46e5&color=fff'">
                                                            <div class="d-md-none">
                                                                <h6 class="fw-bold mb-1 text-dark" x-text="customer.name"></h6>
                                                                <span class="badge px-3 py-1 rounded-pill" 
                                                                      :class="customer.email_verified ? 'bg-success' : 'bg-warning text-dark'" 
                                                                      x-text="customer.email_verified ? 'Verified' : 'Unverified'"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 mb-3 mb-md-0">
                                                        <div class="d-none d-md-block">
                                                            <h6 class="fw-bold mb-1 text-dark" x-text="customer.name"></h6>
                                                            <span class="badge px-3 py-1 rounded-pill" 
                                                                  :class="customer.email_verified ? 'bg-success' : 'bg-warning text-dark'" 
                                                                  x-text="customer.email_verified ? 'Verified' : 'Unverified'"></span>
                                                        </div>
                                                        <div class="small text-muted mt-2">
                                                            <i class="fas fa-envelope me-2 text-primary"></i><span x-text="customer.email"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 mb-3 mb-md-0">
                                                        <div class="small text-muted mb-1"><i class="fas fa-phone me-2 text-primary"></i>Phone</div>
                                                        <div class="text-dark fw-semibold" x-text="customer.phone || 'N/A'"></div>
                                                    </div>
                                                    <div class="col-md-2 mb-3 mb-md-0">
                                                        <div class="small text-muted mb-1"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Address</div>
                                                        <div class="text-dark" x-text="customer.address || 'N/A'" style="font-size: 0.85rem;"></div>
                                                    </div>
                                                    <div class="col-md-2 mb-3 mb-md-0 text-center">
                                                        <div class="small text-muted mb-1"><i class="fas fa-calendar me-2 text-primary"></i>Joined</div>
                                                        <div class="text-dark fw-semibold small" x-text="formatDate(customer.created_at)"></div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-primary px-3 py-1 rounded-pill" x-text="(customer.total_bookings || 0) + ' bookings'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 text-end">
                                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                                            <button class="btn btn-sm btn-outline-info shadow-sm" style="border-radius: 20px;" @click="viewCustomerDetails(customer.id)" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-warning shadow-sm" style="border-radius: 20px;" @click="toggleCustomerStatus(customer.id, customer.email_verified)" 
                                                                    :title="customer.email_verified ? 'Mark as Unverified' : 'Mark as Verified'">
                                                                <i class="fas" :class="customer.email_verified ? 'fa-user-times' : 'fa-user-check'"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filteredCustomers.length === 0" class="col-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3 opacity-50"></i>
                                            <h6 class="text-muted mb-0">No customers found</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Section -->
                <div x-show="section==='profile'" class="glass-advanced rounded border p-4 mt-4">
                    <h6 class="mb-3 neon-text">Admin Profile</h6>
                    
                    <!-- Profile Picture Section -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="position-relative">
                            <img :src="avatarUrl" alt="Avatar" class="rounded-circle border" style="width:84px;height:84px;object-fit:cover;">
                            <input type="file" accept="image/*" x-ref="avatarInput" class="d-none" @change="onAvatarChange($event)">
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" @click="$refs.avatarInput.click()">
                                <i class="fas fa-image me-1"></i>Change Photo
                            </button>
                        </div>
                        <div>
                            <div class="small text-muted">Profile Picture</div>
                            <div class="small text-muted">Update your profile photo</div>
                        </div>
                    </div>
                    
                    <!-- Website Logo Section -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="position-relative">
                            <img :src="logoUrl" alt="Logo" class="rounded-circle border" style="width:84px;height:84px;object-fit:cover;">
                            <input type="file" accept="image/*" x-ref="logoInput" class="d-none" @change="onLogoChange($event)">
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" @click="$refs.logoInput.click()">
                                <i class="fas fa-image me-1"></i>Change Logo
                            </button>
                        </div>
                        <div>
                            <div class="small text-muted">Website Logo</div>
                            <div class="small text-muted">Upload a custom logo for the admin dashboard</div>
                        </div>
                    </div>
                    
                    <form @submit.prevent="updateProfile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" x-model="form.email" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" x-model="form.name" required>
                            </div>
                        </div>
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                            <button type="button" class="btn btn-outline-primary px-4" @click="showChangePassword = !showChangePassword">
                                <span x-show="!showChangePassword">Change Password</span>
                                <span x-show="showChangePassword">Hide Password Form</span>
                            </button>
                        </div>
                    </form>

                    <!-- Change Password Inline Form -->
                    <div x-show="showChangePassword"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="mt-4 glass-advanced rounded border border-gray-200 p-4">
                        <h5 class="mb-3 neon-text">Change Password</h5>
                        <form @submit.prevent="submitChangePassword($refs.oldPwd.value, $refs.newPwd.value, $refs.confirmPwd.value)">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" x-ref="oldPwd" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" x-ref="newPwd" minlength="6" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" x-ref="confirmPwd" minlength="6" required>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-secondary" @click="showChangePassword = false">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notifications Section -->
                <div x-show="section==='notifications'" class="glass-advanced rounded border p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 neon-text">Notifications</h6>
                        <button class="btn btn-sm btn-outline-primary" @click="markAllAsRead()" x-show="unreadCount > 0">
                            <i class="fas fa-check-double me-1"></i>Mark All as Read
                        </button>
                    </div>
                    <div x-show="notifications.length === 0" class="text-center py-5 text-muted">
                        <i class="fas fa-bell-slash fa-3x mb-3 opacity-50"></i>
                        <p>No notifications yet</p>
                    </div>
                    <div class="list-group">
                        <template x-for="notif in notifications" :key="notif.id">
                            <div class="list-group-item" :class="{'bg-light': !notif.is_read}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <strong x-text="notif.title"></strong>
                                            <span x-show="!notif.is_read" class="badge bg-primary" style="font-size: 0.65rem;">New</span>
                                        </div>
                                        <p class="mb-1 small" x-text="notif.message"></p>
                                        <small class="text-muted" x-text="formatTime(notif.created_at)"></small>
                                    </div>
                                    <button class="btn btn-sm btn-link text-danger" @click="deleteNotification(notif.id)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Modal -->
    <div class="modal fade" id="docsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom" style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(168,85,247,0.1) 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-file-alt text-primary"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold">Shop Owner Documents</h5>
                            <small class="text-muted">Review and verify submitted documents</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- ID Details Section -->
                    <div id="doc-id-details" class="mb-4 p-4 bg-light rounded border" style="display: none; background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(168,85,247,0.05) 100%) !important;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-id-card text-primary"></i>
                            <h6 class="mb-0 fw-bold">ID Information</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded shadow-sm">
                                    <div class="small text-muted mb-1">ID Type</div>
                                    <div class="fw-semibold text-dark" id="doc-id-type">-</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded shadow-sm">
                                    <div class="small text-muted mb-1">ID Number</div>
                                    <div class="fw-semibold text-dark" id="doc-id-number">-</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded shadow-sm">
                                    <div class="small text-muted mb-1">Expiry Date</div>
                                    <div class="fw-semibold text-dark" id="doc-id-expiry">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ID Documents -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-id-card text-primary"></i>
                                <h6 class="mb-0 fw-bold">ID Document - Front Side</h6>
                            </div>
                        </div>
                        <div id="doc-id-front-container" class="document-viewer-container"></div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-id-card text-primary"></i>
                                <h6 class="mb-0 fw-bold">ID Document - Back Side</h6>
                            </div>
                        </div>
                        <div id="doc-id-back-container" class="document-viewer-container"></div>
                    </div>
                    <div class="mb-4" id="doc-id-legacy-container" style="display: none;">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-id-card text-primary"></i>
                                <h6 class="mb-0 fw-bold">ID Document</h6>
                            </div>
                        </div>
                        <div id="doc-id-container" class="document-viewer-container"></div>
                    </div>
                    
                    <!-- Selfie with ID -->
                    <div class="mb-4" id="doc-selfie-container-wrapper" style="display: none;">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-camera text-primary"></i>
                                <h6 class="mb-0 fw-bold">Selfie with ID (for verification)</h6>
                            </div>
                        </div>
                        <div id="doc-selfie-container" class="document-viewer-container"></div>
                    </div>
                    
                    <!-- Business Permit -->
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-contract text-primary"></i>
                                <h6 class="mb-0 fw-bold">Business Permit</h6>
                            </div>
                        </div>
                        <div id="doc-permit-container" class="document-viewer-container"></div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary px-4" style="border-radius: 25px;" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fullscreen Image Viewer Modal -->
    <div class="modal fade" id="imageViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white" id="imageViewerTitle">Document Viewer</h5>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-white-50 d-none d-md-block">Press ESC to close | Arrow keys to rotate | +/- to zoom</small>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" style="overflow: hidden;">
                    <div class="position-relative w-100 h-100 d-flex align-items-center justify-content-center">
                        <img id="fullscreenImage" src="" alt="Document" class="img-fluid" style="max-height: 100vh; max-width: 100%; object-fit: contain; transition: transform 0.3s ease;">
                        <div class="position-absolute top-0 start-0 p-2 p-md-3" style="z-index: 10;">
                            <div class="btn-group-vertical gap-2">
                                <button class="btn btn-light btn-sm shadow-sm" onclick="rotateImage(90)" title="Rotate 90° (Right Arrow)">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <button class="btn btn-light btn-sm shadow-sm" onclick="rotateImage(-90)" title="Rotate -90° (Left Arrow)">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button class="btn btn-light btn-sm shadow-sm" onclick="resetImageRotation()" title="Reset Rotation (R)">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="btn btn-light btn-sm shadow-sm" onclick="zoomImage(1.2)" title="Zoom In (+)">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <button class="btn btn-light btn-sm shadow-sm" onclick="zoomImage(0.8)" title="Zoom Out (-)">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button class="btn btn-light btn-sm shadow-sm" onclick="resetImageZoom()" title="Reset Zoom (0)">
                                    <i class="fas fa-compress"></i>
                                </button>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 start-50 translate-middle-x p-2 p-md-3" style="z-index: 10;">
                            <div class="d-flex gap-2">
                                <a id="downloadImageLink" href="" download class="btn btn-primary shadow-sm" title="Download Image (D)">
                                    <i class="fas fa-download me-2"></i><span class="d-none d-md-inline">Download</span>
                                </a>
                                <button class="btn btn-secondary shadow-sm" onclick="resetImageViewer()" title="Reset All">
                                    <i class="fas fa-redo me-2"></i><span class="d-none d-md-inline">Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .document-viewer-container {
            position: relative;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .document-viewer-container:hover {
            border-color: #6366f1;
            background: #f0f4ff;
        }
        
        .document-viewer-container img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .document-viewer-container img:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .document-viewer-container embed {
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .document-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .document-actions .btn {
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.875rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
        }
        
        .document-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        #fullscreenImage {
            transition: transform 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .document-viewer-container {
                padding: 1rem;
                min-height: 200px;
            }
            
            .document-actions {
                position: static;
                margin-top: 10px;
                justify-content: center;
            }
            
            .document-actions .btn {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
        }
    </style>

    <script>
    function adminDashboard(){
        return {
            section: 'home',
            sidebarOpen: false, // Mobile sidebar toggle state
            isPollingActive: true,
            pollingInterval: null,
            lastRefreshTime: null,
            notifications: [],
            unreadCount: 0,
            stats: { totalCustomers: 0, totalShopOwners: 0, shopOwnersPending: 0, shopOwnersApproved: 0, shopOwnersRejected: 0, totalTechnicians: 0, totalBookings: 0 },
            owners: [],
            shopOwnerFilter: 'all', // 'all', 'pending', 'approved', 'rejected'
            customers: [],
            filteredCustomers: [],
            customerSearch: '',
            reports: { 
                bookings: { pending: 0, approved: 0, completed: 0 }, 
                techsPerShop: [],
                monthlyTrends: [],
                totalRevenue: 0,
                topShops: [],
                revenueTrends: []
            },
            charts: {},
            form: {
                name: <?php echo json_encode($user['name']); ?>,
                email: <?php echo json_encode($user['email']); ?>
            },
            showChangePassword: false,
            avatarUrl: <?php 
                $avatarUrl = $user['avatar_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=6366f1&color=fff';
                // Normalize avatar URL to be correct relative to frontend/admin/
                if ($avatarUrl && !str_starts_with($avatarUrl, 'http') && !str_starts_with($avatarUrl, 'data:')) {
                    $avatarUrl = '../' . ltrim($avatarUrl, '/');
                }
                echo json_encode($avatarUrl);
            ?>,
            logoUrl: <?php echo json_encode($user['logo_url'] ?? 'https://ui-avatars.com/api/?name=ERepair&background=6366f1&color=fff'); ?>,
            updateFavicon(logoUrl) {
                // Update the favicon when logo changes
                const favicon = document.getElementById('favicon');
                if (favicon && logoUrl) {
                    // Use the same normalization as fixLogoUrl
                    let faviconUrl = this.fixLogoUrl(logoUrl);
                    favicon.href = faviconUrl;
                    
                    // Also update apple-touch-icon and other icon links if they exist
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
                        // Normalize for frontend/admin/ (one level deep from frontend/)
                        if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                            if (logoUrl.startsWith('../backend/')) {
                                // Path is relative to frontend/, need to add ../ for admin/
                                logoUrl = '../' + logoUrl; // ../backend/... becomes ../../backend/...
                            } else if (logoUrl.startsWith('backend/')) {
                                logoUrl = '../../' + logoUrl;
                            } else if (!logoUrl.startsWith('../') && !logoUrl.startsWith('/')) {
                                logoUrl = '../../backend/uploads/logos/' + logoUrl.split('/').pop();
                            }
                        }
                        this.updateFavicon(logoUrl);
                        console.log('Admin dashboard: Favicon updated to:', logoUrl);
                    }
                } catch (e) {
                    console.error('Error loading website logo:', e);
                }
            },
            fixLogoUrl(u){
                try{
                    if(!u) return this.logoUrl;
                    // Replace any stale "/frontend/backend/" segment
                    if(u.includes('/frontend/backend/')) u = u.replace('/frontend/backend/','/backend/');
                    if(u.startsWith('frontend/backend/')) u = u.replace('frontend/backend/','backend/');
                    // If it starts with backend/, make it relative from /frontend/admin/
                    if(u.startsWith('backend/')) return '../../' + u;
                    // If it's an absolute http(s) URL, keep as is
                    if(/^https?:\/\//.test(u)) return u;
                    // Fallback: use filename under backend/uploads/logos
                    const base = u.split('/').pop();
                    return '../../backend/uploads/logos/' + base;
                }catch(_){ return u; }
            },
            async init(){
                // Normalize any existing stored URL on load
                this.logoUrl = this.fixLogoUrl(this.logoUrl);
                await this.loadWebsiteLogo(); // Load admin's website logo for favicon
                await this.reloadAll();
                this.loadNotifications();
                if(!this._chartsInitialized){
                    this.$nextTick(() => { this.initCharts(); });
                    this._chartsInitialized = true;
                } else {
                    this.$nextTick(() => { this.updateCharts(); });
                }
                this.startPolling();
            },
            startPolling(){
                if(this.pollingInterval){ return; }
                this.isPollingActive = true;
                this.pollingInterval = setInterval(() => {
                    this.reloadAll();
                    this.loadNotifications(); // Also poll notifications
                }, 15000); // Poll every 15 seconds
                console.log('Admin: AJAX polling started (every 15 seconds)');
            },
            stopPolling(){
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                    this.isPollingActive = false;
                }
                console.log('Admin: AJAX polling stopped');
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
                    const res = await fetch('../auth/notifications.php');
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    const data = await res.json();
                    if(data.success) {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                    }
                } catch(e) {
                    // Silently fail for notifications to avoid console spam
                    // console.error('Error loading notifications:', e);
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
            get filteredShopOwners(){
                if(this.shopOwnerFilter === 'all'){
                    return this.owners;
                }
                return this.owners.filter(o => o.approval_status === this.shopOwnerFilter);
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
            async reloadAll(){
                try{
                    const res = await fetch('report_data.php', { cache: 'no-store' });
                    if(!res.ok){ throw new Error('Failed to load data'); }
                    const data = await res.json();
                    this.stats = data.stats || this.stats;
                    this.owners = data.owners || [];
                    this.reports = data.reports || this.reports;
                    this.$nextTick(() => { this.updateCharts(); });
                    
                    // Update last refresh time
                    const now = new Date();
                    this.lastRefreshTime = now.toLocaleTimeString();
                }catch(e){
                    console.error('Error loading data:', e);
                    // avoid repeated error modals during polling
                }
            },
            fixDocumentUrl(url) {
                if (!url) return null;
                // Fix relative paths from shopowner_view.php
                // If it starts with ../../backend/, it's already correct
                if (url.startsWith('../../backend/')) return url;
                // If it starts with ../backend/, fix it
                if (url.startsWith('../backend/')) return url.replace('../backend/', '../../backend/');
                // If it starts with backend/, add ../../
                if (url.startsWith('backend/')) return '../../' + url;
                // If it's an absolute URL, keep as is
                if (/^https?:\/\//.test(url)) return url;
                // Otherwise, assume it's a relative path and fix it
                return '../../backend/uploads/shop_owners/' + url.split('/').pop();
            },
            async viewDocs(ownerId){
                try{
                    const res = await fetch('shopowner_view.php?id='+encodeURIComponent(ownerId));
                    if(!res.ok){ throw new Error('Failed'); }
                    const data = await res.json();
                    
                    // Handle new format with ID details
                    if(data.id_type || data.id_front_url || data.id_back_url){
                        // Show ID details section
                        const idDetailsDiv = document.getElementById('doc-id-details');
                        idDetailsDiv.style.display = 'block';
                        document.getElementById('doc-id-type').textContent = data.id_type || 'N/A';
                        document.getElementById('doc-id-number').textContent = data.id_number || 'N/A';
                        document.getElementById('doc-id-expiry').textContent = data.id_expiry_date ? new Date(data.id_expiry_date).toLocaleDateString() : 'N/A';
                        
                        // Show front and back containers
                        const idFrontC = document.getElementById('doc-id-front-container');
                        const idBackC = document.getElementById('doc-id-back-container');
                        const idLegacyC = document.getElementById('doc-id-legacy-container');
                        idFrontC.innerHTML = renderPreview(this.fixDocumentUrl(data.id_front_url), 'ID Document - Front Side');
                        idBackC.innerHTML = renderPreview(this.fixDocumentUrl(data.id_back_url), 'ID Document - Back Side');
                        idLegacyC.style.display = 'none';
                    } else {
                        // Legacy format - hide ID details, show legacy container
                        document.getElementById('doc-id-details').style.display = 'none';
                        document.getElementById('doc-id-legacy-container').style.display = 'block';
                        document.getElementById('doc-id-front-container').innerHTML = '';
                        document.getElementById('doc-id-back-container').innerHTML = '';
                        const idC = document.getElementById('doc-id-container');
                        idC.innerHTML = renderPreview(this.fixDocumentUrl(data.id_url), 'ID Document');
                    }
                    
                    const pC = document.getElementById('doc-permit-container');
                    pC.innerHTML = renderPreview(this.fixDocumentUrl(data.permit_url), 'Business Permit');
                    
                    // Show selfie if available
                    const selfieWrapper = document.getElementById('doc-selfie-container-wrapper');
                    const selfieContainer = document.getElementById('doc-selfie-container');
                    if (data.selfie_url) {
                        selfieWrapper.style.display = 'block';
                        selfieContainer.innerHTML = renderPreview(this.fixDocumentUrl(data.selfie_url), 'Selfie with ID');
                    } else {
                        selfieWrapper.style.display = 'none';
                        selfieContainer.innerHTML = '';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('docsModal'));
                    modal.show();
                }catch(e){
                    Notiflix.Report.failure('Error', 'Unable to load documents', 'OK');
                }
            },
            async approveOwner(ownerId){
                Notiflix.Confirm.show(
                    'Approve Shop Owner',
                    'Are you sure?',
                    'Approve',
                    'Cancel',
                    async () => {
                        await this._updateOwnerStatus(ownerId, 'approve');
                    },
                    () => {
                        // Cancel callback - do nothing
                    }
                );
            },
            async rejectOwner(ownerId){
                const { value: reason } = await Swal.fire({
                    title: 'Reject Shop Owner?',
                    html: `<div style="margin-bottom: 10px; color: #333;">Please provide a reason for rejecting this shop owner application:</div>`,
                    input: 'textarea',
                    inputPlaceholder: 'e.g., Incomplete documentation, missing required information, document quality issues, etc.',
                    inputAttributes: {
                        'style': 'margin-top: 10px; min-height: 100px;'
                    },
                    inputValidator: (value) => {
                        if (!value || value.trim() === '') {
                            return 'Please provide a reason for rejection';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Reject Shop Owner',
                    confirmButtonColor: '#dc3545',
                    icon: 'warning'
                });
                
                if (reason === undefined) return; // User clicked cancel button
                
                if (!reason || reason.trim() === '') {
                    Notiflix.Report.failure('Error', 'Rejection reason is required', 'OK');
                    return;
                }
                
                await this._updateOwnerStatus(ownerId, 'reject', reason.trim());
            },
            async _updateOwnerStatus(ownerId, action, rejectionReason = null){
                // Show loading indicator
                Notiflix.Loading.standard(
                    action === 'reject' 
                        ? 'Please wait while we reject the shop owner and send the notification email...'
                        : 'Please wait while we approve the shop owner and send the notification email...'
                );
                
                try{
                    const payload = { owner_id: ownerId, action };
                    if (rejectionReason) {
                        payload.rejection_reason = rejectionReason;
                    }
                    
                    const res = await fetch('shopowner_manage.php', { 
                        method:'POST', 
                        headers:{'Content-Type':'application/json'}, 
                        body: JSON.stringify(payload) 
                    });
                    
                    // Try to parse response as JSON
                    let data;
                    try {
                        data = await res.json();
                    } catch(e) {
                        console.error('Failed to parse response as JSON:', e);
                        Notiflix.Loading.remove();
                        Notiflix.Report.failure('Error', 'Server returned an invalid response. Check console for details.', 'OK');
                        return;
                    }
                    
                    Notiflix.Loading.remove();
                    
                    if(res.ok && data.success){
                        Notiflix.Report.success('Success', data.message || 'Updated', 'OK');
                        await this.reloadAll();
                    }else{
                        Notiflix.Report.failure('Error', data.error||'Failed', 'OK');
                    }
                }catch(e){
                    console.error('Request error:', e);
                    Notiflix.Loading.remove();
                    Notiflix.Report.failure('Error', 'Request failed: ' + e.message, 'OK');
                }
            },
            async onLogoChange(e){
                const file = e.target.files && e.target.files[0];
                if(!file) return;
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if(!allowedTypes.includes(file.type)) {
                    Notiflix.Report.failure('Error', 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'OK');
                    return;
                }
                
                // Validate file size (max 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if(file.size > maxSize) {
                    Notiflix.Report.failure('Error', 'File size too large. Maximum size is 2MB.', 'OK');
                    return;
                }
                
                const formData = new FormData();
                formData.append('logo', file);
                
                try {
                    console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);
                    
                    const res = await fetch('../../backend/api/admin/upload-logo.php', { 
                        method: 'POST', 
                        body: formData 
                    });
                    
                    console.log('Response status:', res.status);
                    console.log('Response headers:', res.headers);
                    
                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('HTTP error response:', errorText);
                        throw new Error(`HTTP error! status: ${res.status} - ${errorText}`);
                    }
                    
                    const data = await res.json();
                    console.log('Response data:', data);
                    
                    if(data.success) {
                        let u = data.data.logo_url || '';
                        this.logoUrl = this.fixLogoUrl(u) || this.logoUrl;
                        await this.loadWebsiteLogo(); // Reload website logo for favicon
                        Notiflix.Report.success('Success', 'Logo updated successfully', 'OK');
                    } else {
                        Notiflix.Report.failure('Error', data.message || 'Upload failed', 'OK');
                    }
                } catch(e) {
                    console.error('Logo upload error:', e);
                    Notiflix.Report.failure('Error', 'Failed to upload logo: ' + e.message, 'OK');
                }
            },
            onAvatarChange(e){
                const file = e.target.files && e.target.files[0];
                if(!file) return;
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if(!allowedTypes.includes(file.type)) {
                    Notiflix.Report.failure('Error', 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'OK');
                    return;
                }
                
                // Validate file size (max 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if(file.size > maxSize) {
                    Notiflix.Report.failure('Error', 'File size too large. Maximum size is 2MB.', 'OK');
                    return;
                }
                
                const formData = new FormData();
                formData.append('avatar', file);
                
                fetch('admin_profile_photo_upload.php', { method: 'POST', body: formData })
                    .then(r=>r.json())
                    .then(data=>{ 
                        if(data.success){ 
                            // Normalize avatar URL to be correct relative to frontend/admin/
                            let avatarUrl = data.avatar_url;
                            if(avatarUrl && !avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
                                avatarUrl = '../' + avatarUrl.replace(/^\/+/, '');
                            }
                            this.avatarUrl = avatarUrl; 
                            Notiflix.Report.success('Success', 'Profile photo updated successfully', 'OK'); 
                        } else { 
                            Notiflix.Report.failure('Error', data.error||'Upload failed','OK'); 
                        } 
                    })
                    .catch((e) => {
                        console.error('Upload error:', e);
                        Notiflix.Report.failure('Error', 'Network error occurred during upload', 'OK');
                    });
            },
            async updateProfile() {
                try {
                    const res = await fetch('../backend/api/admin/profile-update.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    
                    const data = await res.json();
                    if (data.success) {
                        Notiflix.Report.success('Success', 'Profile updated successfully', 'OK');
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Update failed', 'OK');
                    }
                } catch (e) {
                    console.error('Profile update error:', e);
                    Notiflix.Report.failure('Error', 'Failed to update profile. Please check your connection and try again.', 'OK');
                }
            },
            async submitChangePassword(oldPwd, newPwd, confirmPwd){
                if(newPwd !== confirmPwd){ Notiflix.Report.failure('Error','Passwords do not match','OK'); return; }
                try{
                    const res = await fetch('../auth/change_password.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, credentials: 'same-origin', body: JSON.stringify({ old_password: oldPwd, new_password: newPwd })});
                    const data = await res.json();
                    if(data.success){ 
                        Notiflix.Report.success('Updated','Password changed','OK'); 
                        this.showChangePassword = false;
                    } else {
                        Notiflix.Report.failure('Error', data.error||'Failed to change password','OK');
                    }
                }catch(e){ Notiflix.Report.failure('Error','Network error','OK'); }
            },
            initCharts() {
                try {
                    // Booking Status Pie Chart
                    const bookingCtx = document.getElementById('bookingStatusChart');
                    if (bookingCtx) {
                        if(this.charts.bookingStatus && typeof this.charts.bookingStatus.destroy==='function'){
                            try{ this.charts.bookingStatus.destroy(); }catch(_){ }
                        }
                        this.charts.bookingStatus = new Chart(bookingCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pending', 'Approved', 'Completed'],
                            datasets: [{
                                data: [
                                    this.reports.bookings.pending,
                                    this.reports.bookings.approved,
                                    this.reports.bookings.completed
                                ],
                                backgroundColor: [
                                    '#fbbf24',
                                    '#3b82f6',
                                    '#10b981'
                                ],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#374151',
                                        font: { size: 10 },
                                        padding: 8,
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                }
                            }
                        }
                    });
                }

                // Monthly Trends Line Chart
                const monthlyCtx = document.getElementById('monthlyTrendsChart');
                if (monthlyCtx) {
                    if(this.charts.monthlyTrends && typeof this.charts.monthlyTrends.destroy==='function'){
                        try{ this.charts.monthlyTrends.destroy(); }catch(_){ }
                    }
                    this.charts.monthlyTrends = new Chart(monthlyCtx, {
                        type: 'line',
                        data: {
                            labels: this.reports.monthlyTrends.map(t => t.month) || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                            datasets: [{
                                label: 'Bookings',
                                data: this.reports.monthlyTrends.map(t => t.count) || [12, 19, 8, 15, 22, 18],
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#374151',
                                        font: { size: 10 }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { 
                                        color: '#6b7280',
                                        font: { size: 9 }
                                    },
                                    grid: { color: 'rgba(107, 114, 128, 0.1)' }
                                },
                                x: {
                                    ticks: { 
                                        color: '#6b7280',
                                        font: { size: 9 }
                                    },
                                    grid: { color: 'rgba(107, 114, 128, 0.1)' }
                                }
                            }
                        }
                    });
                }

                // Technicians per Shop Bar Chart
                const techCtx = document.getElementById('techniciansChart');
                if (techCtx) {
                    if(this.charts.technicians && typeof this.charts.technicians.destroy==='function'){
                        try{ this.charts.technicians.destroy(); }catch(_){ }
                    }
                    this.charts.technicians = new Chart(techCtx, {
                        type: 'bar',
                        data: {
                            labels: this.reports.techsPerShop.map(t => t.shop_name) || ['Shop A', 'Shop B', 'Shop C'],
                            datasets: [{
                                label: 'Technicians',
                                data: this.reports.techsPerShop.map(t => t.technicians) || [5, 8, 3],
                                backgroundColor: [
                                    '#6366f1',
                                    '#8b5cf6',
                                    '#ec4899',
                                    '#f59e0b',
                                    '#10b981'
                                ],
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#374151',
                                        font: { size: 10 }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { 
                                        color: '#6b7280',
                                        font: { size: 9 }
                                    },
                                    grid: { color: 'rgba(107, 114, 128, 0.1)' }
                                },
                                x: {
                                    ticks: { 
                                        color: '#6b7280',
                                        font: { size: 9 }
                                    },
                                    grid: { color: 'rgba(107, 114, 128, 0.1)' }
                                }
                            }
                        }
                    });
                }
                } catch(e) {
                    console.error('Error initializing charts:', e);
                }
            },
            updateCharts() {
                if (!this._chartsInitialized) {
                    return; // Don't update charts if they haven't been initialized yet
                }
                
                try {
                    // Update booking status chart
                    if (this.charts.bookingStatus && typeof this.charts.bookingStatus.update === 'function') {
                        this.charts.bookingStatus.data.datasets[0].data = [
                            this.reports.bookings.pending,
                            this.reports.bookings.approved,
                            this.reports.bookings.completed
                        ];
                        this.charts.bookingStatus.update('none'); // Update without animation to avoid errors
                    }

                    // Update monthly trends chart
                    if (this.charts.monthlyTrends && typeof this.charts.monthlyTrends.update === 'function') {
                        this.charts.monthlyTrends.data.labels = this.reports.monthlyTrends.map(t => t.month);
                        this.charts.monthlyTrends.data.datasets[0].data = this.reports.monthlyTrends.map(t => t.count);
                        this.charts.monthlyTrends.update('none');
                    }

                    // Update technicians chart
                    if (this.charts.technicians && typeof this.charts.technicians.update === 'function') {
                        this.charts.technicians.data.labels = this.reports.techsPerShop.map(t => t.shop_name);
                        this.charts.technicians.data.datasets[0].data = this.reports.techsPerShop.map(t => t.technicians);
                        this.charts.technicians.update('none');
                    }
                } catch(e) {
                    // Silently fail to avoid console spam
                    // console.error('Error updating charts:', e);
                }

            },
            async loadCustomers(){
                try{
                    const res = await fetch('customers_list.php', { cache: 'no-store' });
                    if(!res.ok){ throw new Error('Failed to load customers'); }
                    const data = await res.json();
                    this.customers = data.customers || [];
                    this.filteredCustomers = [...this.customers];
                }catch(e){
                    console.error('Error loading customers:', e);
                    this.customers = [];
                    this.filteredCustomers = [];
                }
            },
            filterCustomers(){
                if(!this.customerSearch.trim()){
                    this.filteredCustomers = [...this.customers];
                    return;
                }
                const search = this.customerSearch.toLowerCase();
                this.filteredCustomers = this.customers.filter(customer => 
                    customer.name.toLowerCase().includes(search) ||
                    customer.email.toLowerCase().includes(search) ||
                    (customer.phone && customer.phone.includes(search)) ||
                    (customer.address && customer.address.toLowerCase().includes(search))
                );
            },
            formatDate(dateString){
                if(!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString();
            },
            getCustomerAvatarUrl(customer){
                if(!customer.avatar_url) {
                    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(customer.name) + '&background=4f46e5&color=fff';
                }
                
                // If avatar_url is a relative path, make it absolute
                if(customer.avatar_url && !customer.avatar_url.startsWith('http')) {
                    // Handle different possible path formats
                    if(customer.avatar_url.startsWith('uploads/')) {
                        return '../' + customer.avatar_url;
                    } else if(customer.avatar_url.startsWith('/')) {
                        return '..' + customer.avatar_url;
                    } else {
                        return '../uploads/' + customer.avatar_url;
                    }
                }
                
                return customer.avatar_url;
            },
            async viewCustomerDetails(customerId){
                try{
                    const res = await fetch(`customer_details.php?id=${customerId}`);
                    if(!res.ok){ throw new Error('Failed to load customer details'); }
                    const data = await res.json();
                    
                    if(data.success){
                        const customer = data.customer;
                        const bookings = data.bookings || [];
                        
                        let bookingsHtml = '';
                        if(bookings.length > 0){
                            bookingsHtml = `
                                <div class="mt-3">
                                    <h6>Recent Bookings:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Service</th>
                                                    <th>Shop</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${bookings.slice(0, 5).map(booking => `
                                                    <tr>
                                                        <td>${booking.service || 'N/A'}</td>
                                                        <td>${booking.shop_name || 'N/A'}</td>
                                                        <td><span class="badge bg-${booking.status === 'completed' ? 'success' : booking.status === 'approved' ? 'primary' : 'warning'}">${booking.status}</span></td>
                                                        <td>${new Date(booking.scheduled_at).toLocaleDateString()}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        }
                        
                        Swal.fire({
                            title: 'Customer Details',
                            html: `
                                <div class="text-start">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <img src="${this.getCustomerAvatarUrl(customer)}" 
                                             alt="Avatar" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;"
                                             onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(customer.name)}&background=4f46e5&color=fff'">
                                        <div>
                                            <h5 class="mb-1">${customer.name}</h5>
                                            <p class="text-muted mb-0">${customer.email}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Phone:</strong> ${customer.phone || 'N/A'}</p>
                                            <p><strong>Address:</strong> ${customer.address || 'N/A'}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong> <span class="badge ${customer.email_verified ? 'bg-success' : 'bg-warning text-dark'}">${customer.email_verified ? 'Verified' : 'Unverified'}</span></p>
                                            <p><strong>Joined:</strong> ${new Date(customer.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>
                                    ${bookingsHtml}
                                </div>
                            `,
                            width: '600px',
                            showConfirmButton: true,
                            confirmButtonText: 'Close'
                        });
                    } else {
                        Notiflix.Report.failure('Error', data.error || 'Failed to load customer details', 'OK');
                    }
                }catch(e){
                    console.error('Error loading customer details:', e);
                    Notiflix.Report.failure('Error', 'Failed to load customer details', 'OK');
                }
            },
            async toggleCustomerStatus(customerId, currentStatus){
                const action = currentStatus ? 'unverify' : 'verify';
                const actionText = currentStatus ? 'unverify' : 'verify';
                
                Notiflix.Confirm.show(
                    `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Customer?`,
                    `Are you sure you want to ${actionText} this customer's email?`,
                    `Yes, ${actionText}`,
                    'Cancel',
                    async () => {
                        try{
                            const res = await fetch('customer_manage.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ customer_id: customerId, action: action })
                            });
                            
                            const data = await res.json();
                            if(data.success){
                                Notiflix.Report.success('Success', data.message || 'Customer status updated', 'OK');
                                await this.loadCustomers();
                            } else {
                                Notiflix.Report.failure('Error', data.error || 'Failed to update customer status', 'OK');
                            }
                        }catch(e){
                            console.error('Error updating customer status:', e);
                            Notiflix.Report.failure('Error', 'Failed to update customer status', 'OK');
                        }
                    },
                    () => {
                        // Cancel callback - do nothing
                    }
                );
            },
            async logout(){
                Notiflix.Confirm.show(
                    'Logout',
                    'Are you sure you want to logout?',
                    'Logout',
                    'Cancel',
                    async () => {
                        try{
                            const res = await fetch('../auth/logout.php', { method:'POST' });
                            // Regardless of result, clear local token redirect
                            window.location.href = '../auth/index.php';
                        }catch(e){ 
                            window.location.href = '../auth/index.php'; 
                        }
                    },
                    () => {
                        // Cancel callback - do nothing
                    }
                );
            }
        }
    }

    function renderPreview(url, title = ''){
        if(!url) return '<div class="text-center text-muted py-5"><i class="fas fa-file-slash fa-3x mb-3 opacity-50"></i><div>No file uploaded</div></div>';
        const lower = url.toLowerCase();
        if(lower.endsWith('.pdf')){
            return `
                <div class="position-relative">
                    <embed src="${url}" type="application/pdf" class="w-100 rounded" style="height:500px; border: 1px solid #dee2e6;" />
                    <div class="document-actions">
                        <a href="${url}" target="_blank" class="btn btn-primary btn-sm" title="Open in new tab">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <a href="${url}" download class="btn btn-success btn-sm" title="Download PDF">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            `;
        }
        const imageId = 'doc-img-' + Math.random().toString(36).substr(2, 9);
        return `
            <div class="position-relative">
                <img id="${imageId}" src="${url}" class="img-fluid rounded" alt="${title || 'Document'}" 
                     onclick="openImageViewer('${url}', '${title || 'Document'}')" 
                     style="cursor: zoom-in; max-height: 500px; width: auto; border: 1px solid #dee2e6;" />
                <div class="document-actions">
                    <button class="btn btn-primary btn-sm" onclick="openImageViewer('${url}', '${title || 'Document'}')" title="View Fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                    <a href="${url}" target="_blank" class="btn btn-info btn-sm" title="Open in new tab">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <a href="${url}" download class="btn btn-success btn-sm" title="Download Image">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        `;
    }
    
    let currentRotation = 0;
    let currentZoom = 1;
    let imageViewerModal = null;
    
    function openImageViewer(url, title) {
        imageViewerModal = new bootstrap.Modal(document.getElementById('imageViewerModal'));
        const img = document.getElementById('fullscreenImage');
        const downloadLink = document.getElementById('downloadImageLink');
        const titleElement = document.getElementById('imageViewerTitle');
        
        img.src = url;
        downloadLink.href = url;
        titleElement.textContent = title || 'Document Viewer';
        currentRotation = 0;
        currentZoom = 1;
        img.style.transform = 'rotate(0deg) scale(1)';
        
        // Add keyboard event listeners
        document.addEventListener('keydown', handleImageViewerKeyboard);
        
        imageViewerModal.show();
    }
    
    function handleImageViewerKeyboard(e) {
        const modal = document.getElementById('imageViewerModal');
        if (!modal.classList.contains('show')) return;
        
        switch(e.key) {
            case 'Escape':
                if (imageViewerModal) {
                    imageViewerModal.hide();
                    document.removeEventListener('keydown', handleImageViewerKeyboard);
                }
                break;
            case 'ArrowRight':
                e.preventDefault();
                rotateImage(90);
                break;
            case 'ArrowLeft':
                e.preventDefault();
                rotateImage(-90);
                break;
            case '+':
            case '=':
                e.preventDefault();
                zoomImage(1.2);
                break;
            case '-':
            case '_':
                e.preventDefault();
                zoomImage(0.8);
                break;
            case '0':
                e.preventDefault();
                resetImageZoom();
                break;
            case 'r':
            case 'R':
                e.preventDefault();
                resetImageRotation();
                break;
            case 'd':
            case 'D':
                e.preventDefault();
                document.getElementById('downloadImageLink').click();
                break;
        }
    }
    
    function rotateImage(degrees) {
        currentRotation += degrees;
        // Normalize rotation to 0-360
        currentRotation = ((currentRotation % 360) + 360) % 360;
        const img = document.getElementById('fullscreenImage');
        img.style.transform = `rotate(${currentRotation}deg) scale(${currentZoom})`;
    }
    
    function resetImageRotation() {
        currentRotation = 0;
        const img = document.getElementById('fullscreenImage');
        img.style.transform = `rotate(0deg) scale(${currentZoom})`;
    }
    
    function zoomImage(factor) {
        currentZoom *= factor;
        if (currentZoom < 0.5) currentZoom = 0.5;
        if (currentZoom > 3) currentZoom = 3;
        const img = document.getElementById('fullscreenImage');
        img.style.transform = `rotate(${currentRotation}deg) scale(${currentZoom})`;
    }
    
    function resetImageZoom() {
        currentZoom = 1;
        const img = document.getElementById('fullscreenImage');
        img.style.transform = `rotate(${currentRotation}deg) scale(1)`;
    }
    
    function resetImageViewer() {
        resetImageRotation();
        resetImageZoom();
    }
    
    // Clean up event listeners when modal is closed
    document.getElementById('imageViewerModal').addEventListener('hidden.bs.modal', function() {
        document.removeEventListener('keydown', handleImageViewerKeyboard);
    });
    
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


