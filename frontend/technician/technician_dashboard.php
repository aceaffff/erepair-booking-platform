<?php
require_once __DIR__ . '/../../backend/config/database.php';
function redirect_to_login(){ header('Location: ../auth/index.php'); exit; }
$token = $_COOKIE['auth_token'] ?? ($_GET['token'] ?? '');
if (!empty($_GET['token'])) setcookie('auth_token', $_GET['token'], time()+86400, '/');
if (empty($token)) redirect_to_login();
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT u.id,u.name,u.email,u.phone,u.avatar_url,u.role,t.id AS tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
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
        .nav-btn { transition: background-color .2s ease, color .2s ease, transform .08s ease; color: rgba(255,255,255,.92); }
        .nav-btn:hover { background-color: rgba(255,255,255,.06); color: #fff; }
        .nav-btn.active { background: rgba(99,102,241,.18); color: #8ea2ff; font-weight: 600; box-shadow: inset 0 0 0 1px rgba(99,102,241,.35); }
        .nav-btn i { width: 22px; text-align: center; opacity: .95; }
        .nav-btn.active i { color: #8ea2ff; }
        .logout-btn { color: #ef4444; }
        .logout-btn:hover { background: rgba(239,68,68,.12); color: #fff; }
        .brand-wrap { background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,0)); border-bottom: 1px solid rgba(255,255,255,.06); }
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
            transition: all 0.3s ease;
        }
        .schedule-day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        /* Job card animations */
        .job-card {
            transition: all 0.2s ease;
        }
        .job-card:hover {
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            margin-left: 260px;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block !important;
            }
            
            .sidebar {
                position: fixed !important;
                left: -260px !important;
                transition: left 0.3s ease;
                z-index: 1050;
                height: 100vh;
            }
            
            .sidebar.open {
                left: 0 !important;
            }
            
            .sidebar-overlay.show {
                display: block !important;
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
        <div class="sidebar shadow-md min-vh-100 text-white" :class="{ 'open': sidebarOpen }" style="position: fixed; left: 0; width:260px; background-color:#0b1220; top: 0; height: 100vh; overflow-y: auto; z-index: 1000;">
            <div class="p-4 brand-wrap">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center logo-container" style="width: 48px; height: 48px;">
                        <i class="fas fa-screwdriver-wrench text-white fs-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl fw-bold m-0" style="letter-spacing:.3px; color: #ffffff;">ERepair</h2>
                        <div class="small text-white-50">Technician Portal</div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="position-relative d-inline-block">
                        <img :src="avatarUrl" 
                             class="rounded-circle border d-block mx-auto" 
                             style="width:64px;height:64px;object-fit:cover;" 
                             alt="Avatar">
                        <!-- Auto-refresh indicator -->
                        <div x-show="isPollingActive" class="position-absolute bottom-0 end-0 bg-success rounded-circle" style="width: 16px; height: 16px; border: 2px solid #0b1220;" title="Auto-refresh active"></div>
                    </div>
                    <div class="fw-semibold small mt-2 text-white" style="color:#ffffff !important;">
                        <?php echo h($user['name']); ?>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-2">
                        <button class="btn btn-sm btn-outline-light" @click="section='profile'; sidebarOpen = false"><i class="fas fa-user-pen me-1"></i>Edit profile</button>
                        <button class="btn btn-sm btn-outline-light position-relative" @click="section='notifications'; sidebarOpen = false; loadNotifications()">
                            <i class="fas fa-bell"></i>
                            <span x-show="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="unreadCount" style="font-size: 0.6rem;"></span>
                        </button>
                    </div>
                   
                </div>
            </div>
            <ul class="list-unstyled p-3">
                <li>
                    <button class="nav-btn w-100 text-start px-3 py-2 rounded" :class="{ 'active': section==='home' }" @click="section='home'; sidebarOpen = false">
                        <i class="fas fa-home me-2"></i>Home
                    </button>
                </li>
                <li class="mt-2">
                    <button class="nav-btn w-100 text-start px-3 py-2 rounded" :class="{ 'active': section==='jobs' }" @click="section='jobs'; sidebarOpen = false; jobStatusFilter='all'; renderJobs();">
                        <i class="fas fa-briefcase me-2"></i>My Jobs
                    </button>
                </li>
                <li class="mt-2">
                    <button class="nav-btn w-100 text-start px-3 py-2 rounded" :class="{ 'active': section==='schedule' }" @click="section='schedule'; sidebarOpen = false; renderSchedule();">
                        <i class="fas fa-calendar-week me-2"></i>My Schedule
                    </button>
                </li>
                <li class="mt-2">
                    <button class="nav-btn w-100 text-start px-3 py-2 rounded" :class="{ 'active': section==='completed' }" @click="section='completed'; sidebarOpen = false; renderCompletedJobs();">
                        <i class="fas fa-check-circle me-2"></i>Completed Jobs
                    </button>
                </li>
                <li class="mt-2">
                    <button class="w-100 text-start px-3 py-2 rounded logout-btn" @click="logout()">
                        <i class="fas fa-right-from-bracket me-2"></i>Logout
                    </button>
                </li>
            </ul>
        </div>
        <div class="flex-1 w-100 main-content">
            <div class="container py-4">
                <div x-show="section==='home'">
                    <!-- Welcome Header -->
                    <div class="glass-advanced rounded border p-4 mb-4" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="neon-text mb-2">Welcome Back, <?php echo h($user['name']); ?>!</h4>
                                <p class="text-muted mb-0"><i class="fas fa-clock me-2"></i>Today is <?php echo date('l, F j, Y'); ?></p>
                            </div>
                            <div class="text-end">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-calendar-check text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row g-3 mb-4" id="tech-stats">
                        <div class="col-6 col-md-3">
                            <div class="glass-advanced rounded border p-4 h-100" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(37, 99, 235, 0.05) 100%); border-left: 4px solid #3b82f6;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="small text-muted mb-2">Total Jobs</div>
                                        <div class="h3 fw-bold text-primary mb-0" id="st-total">0</div>
                                    </div>
                                    <div class="bg-blue-100 rounded-circle p-3">
                                        <i class="fas fa-briefcase text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-advanced rounded border p-4 h-100" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(22, 163, 74, 0.05) 100%); border-left: 4px solid #22c55e;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="small text-muted mb-2">Completed</div>
                                        <div class="h3 fw-bold text-success mb-0" id="st-completed">0</div>
                                    </div>
                                    <div class="bg-green-100 rounded-circle p-3">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-advanced rounded border p-4 h-100" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.05) 0%, rgba(202, 138, 4, 0.05) 100%); border-left: 4px solid #eab308;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="small text-muted mb-2">In Progress</div>
                                        <div class="h3 fw-bold text-warning mb-0" id="st-inprog">0</div>
                                    </div>
                                    <div class="bg-yellow-100 rounded-circle p-3">
                                        <i class="fas fa-spinner text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-advanced rounded border p-4 h-100" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.05) 0%, rgba(147, 51, 234, 0.05) 100%); border-left: 4px solid #a855f7;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="small text-muted mb-2">Pending</div>
                                        <div class="h3 fw-bold text-purple mb-0" id="st-pending" style="color: #a855f7;">0</div>
                                    </div>
                                    <div class="rounded-circle p-3" style="background-color: rgba(168, 85, 247, 0.1);">
                                        <i class="fas fa-clock" style="color: #a855f7;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings & Performance -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-lg-4">
                            <div class="glass-advanced rounded border p-4 h-100" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%);">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0"><i class="fas fa-wallet text-success me-2"></i>Total Earnings</h6>
                                    <i class="fas fa-arrow-trend-up text-success"></i>
                                </div>
                                <div class="h2 fw-bold text-success mb-2">₱<span id="st-earnings">0.00</span></div>
                                <div class="small text-muted">From completed jobs</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="glass-advanced rounded border p-4 h-100">
                                <h6 class="mb-3"><i class="fas fa-chart-line text-primary me-2"></i>Performance Overview</h6>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-center p-3 rounded" style="background-color: rgba(59, 130, 246, 0.1);">
                                            <div class="h4 fw-bold text-primary mb-1" id="st-completion-rate">0%</div>
                                            <div class="small text-muted">Completion Rate</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3 rounded" style="background-color: rgba(34, 197, 94, 0.1);">
                                            <div class="h4 fw-bold text-success mb-1" id="st-avg-earnings">₱0</div>
                                            <div class="small text-muted">Avg per Job</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="glass-advanced rounded border p-3">
                                <h6 class="mb-2"><i class="fas fa-chart-pie text-primary me-2"></i>Jobs by Status</h6>
                                <div class="chart-container">
                                    <canvas id="tech-status-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="glass-advanced rounded border p-3">
                                <h6 class="mb-2"><i class="fas fa-chart-bar text-success me-2"></i>Jobs Timeline (Last 7 Days)</h6>
                                <div class="chart-container">
                                    <canvas id="tech-timeline-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="glass-advanced rounded border p-4 mt-4">
                        <h6 class="mb-3"><i class="fas fa-history text-warning me-2"></i>Recent Activity</h6>
                        <div id="recent-activity">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-clock fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">No recent activity</p>
                            </div>
                        </div>
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
                <div x-show="section==='profile'" class="glass-advanced rounded border p-4 mt-4">
                    <h6 class="mb-3 neon-text">Profile</h6>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img :src="avatarUrl" alt="Avatar" class="rounded-circle border" style="width:72px;height:72px;object-fit:cover;">
                        <div>
                            <div class="small text-muted mb-1">Update profile photo</div>
                            <input type="file" accept="image/*" x-ref="avatarInput" class="d-none" @change="onAvatarChange($event)">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="$refs.avatarInput.click()"><i class="fas fa-image me-1"></i>Change Photo</button>
                        </div>
                    </div>
                    <form @submit.prevent="saveProfile">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" x-model="profile.email" disabled></div>
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       x-model="profile.name"
                                       @input="sanitizeTechnicianName($event)"
                                       maxlength="100"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" 
                                       class="form-control" 
                                       x-model="profile.phone"
                                       @input="sanitizeTechnicianPhone($event)"
                                       pattern="^09[0-9]{9}$"
                                       maxlength="11"
                                       minlength="11"
                                       placeholder="09XXXXXXXXX">
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-outline-primary" @click="showChangePassword = !showChangePassword">
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
                         class="mt-4 change-password-form rounded border border-gray-200 p-4">
                        <h5 class="mb-3 neon-text">Change Password</h5>
                        <form @submit.prevent="submitChangePassword($refs.oldPwd.value, $refs.newPwd.value, $refs.confirmPwd.value)">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           x-ref="oldPwd" 
                                           @input="sanitizePassword($event, $refs.oldPwd)"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           x-ref="newPwd" 
                                           @input="sanitizePassword($event, $refs.newPwd)"
                                           minlength="6"
                                           maxlength="128"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           x-ref="confirmPwd" 
                                           @input="sanitizePassword($event, $refs.confirmPwd)"
                                           minlength="6"
                                           maxlength="128"
                                           required>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-secondary" @click="showChangePassword = false">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div x-show="section==='notifications'" class="bg-white rounded border p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Notifications</h6>
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
                            <div class="list-group-item list-group-item-action" 
                                 :class="{'bg-light': !notif.is_read}"
                                 style="cursor: pointer;"
                                 @click="handleNotification(notif)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <strong x-text="notif.title"></strong>
                                            <span x-show="!notif.is_read" class="badge bg-primary" style="font-size: 0.65rem;">New</span>
                                        </div>
                                        <p class="mb-1 small" x-text="notif.message"></p>
                                        <small class="text-muted" x-text="formatTime(notif.created_at)"></small>
                                    </div>
                                    <button class="btn btn-sm btn-link text-danger" 
                                            @click.stop="deleteNotification(notif.id)"
                                            title="Delete notification">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div x-show="section==='schedule'" class="glass-advanced rounded border p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="m-0 neon-text">My Schedule</h6>
                            <small class="text-muted">View your weekly job assignments</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm me-2">
                                <button type="button" class="btn btn-outline-primary" @click="changeWeek(-1)">
                                    <i class="fas fa-chevron-left me-1"></i>Previous
                                </button>
                                <button type="button" class="btn btn-primary" @click="changeWeek(0)">
                                    <i class="fas fa-calendar-week me-1"></i>This Week
                                </button>
                                <button type="button" class="btn btn-outline-primary" @click="changeWeek(1)">
                                    Next<i class="fas fa-chevron-right ms-1"></i>
                                </button>
                            </div>
                            <div class="badge bg-info fs-6" x-text="weekRange"></div>
                        </div>
                    </div>
                    <div id="schedule-grid" class="row g-3"></div>
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
            renderStats(){
                const jobs = Array.isArray(this.jobs) ? this.jobs : [];
                const total = jobs.length;
                const completed = jobs.filter(j=>j.status==='completed').length;
                const inprog = jobs.filter(j=>j.status==='in_progress').length;
                const pending = jobs.filter(j=>['pending','approved','assigned'].includes(j.status)).length;
                const earnings = jobs.filter(j=>j.status==='completed').reduce((s,j)=> s + Number(j.price||0), 0);
                const fmt = n=> Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
                const set = (id,val)=>{ const el=document.getElementById(id); if(el) el.textContent = val; };
                set('st-total', total);
                set('st-completed', completed);
                set('st-inprog', inprog);
                set('st-pending', pending);
                set('st-earnings', fmt(earnings));
                this.completedJobsCount = completed;

                // Calculate performance metrics
                const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;
                const avgEarnings = completed > 0 ? Math.round(earnings / completed) : 0;
                set('st-completion-rate', completionRate + '%');
                set('st-avg-earnings', '₱' + avgEarnings.toLocaleString());

                // Render charts
                this.renderCharts(jobs, completed, inprog, pending);
                this.renderRecentActivity(jobs);
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
                    
                    const mapUrl = j.shop_address ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(j.shop_address)}` : '';
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
                                    ${mapUrl ? `
                                        <a class="btn btn-outline-primary btn-sm" href="${mapUrl}" target="_blank">
                                            <i class="fas fa-map-marker-alt me-1"></i>Open in Maps
                                        </a>
                                    ` : ''}
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
                                <div class="fw-bold text-success fs-4">
                                    ${j.price ? `₱${Number(j.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '—'}
                                </div>
                                <div class="small text-muted">Earnings</div>
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
                            <div class="glass-advanced rounded border p-3 mb-2 job-card" style="background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7)); border: 1px solid rgba(99, 102, 241, 0.2);">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="small text-muted">
                                        <i class="fas fa-clock me-1"></i>${j.time_slot || '—'}
                                    </div>
                                    <span class="badge ${statusClass}">
                                        <i class="fas ${statusIcon} me-1"></i>${j.status}
                                    </span>
                                </div>
                                <div class="fw-semibold mb-1">${j.service}</div>
                                ${j.device_type ? `
                                    <div class="small text-muted mb-1">
                                        <i class="fas fa-mobile-alt me-1"></i>${j.device_type}
                                    </div>
                                ` : ''}
                                ${j.shop_name ? `
                                    <div class="small text-muted">
                                        <i class="fas fa-store me-1"></i>${j.shop_name}
                                    </div>
                                ` : ''}
                                ${j.customer_name ? `
                                    <div class="small text-muted">
                                        <i class="fas fa-user me-1"></i>${j.customer_name}
                                    </div>
                                ` : ''}
                                ${j.price ? `
                                    <div class="small text-success fw-bold mt-1">
                                        <i class="fas fa-dollar-sign me-1"></i>₱${Number(j.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                    
                    // Create day card with enhanced styling
                    const dayCardClass = isToday ? 'border-primary bg-primary bg-opacity-10' : isWeekend ? 'border-warning bg-warning bg-opacity-5' : 'border-light';
                    const dayHeaderClass = isToday ? 'text-primary fw-bold' : isWeekend ? 'text-warning' : 'text-dark';
                    
                    col.innerHTML = `
                        <div class="border rounded p-3 h-100 schedule-day-card ${dayCardClass}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="${dayHeaderClass}">
                                    <div class="fw-bold">${pretty}</div>
                                    ${isToday ? '<small class="text-primary">Today</small>' : ''}
                                </div>
                                <div class="badge bg-light text-dark">
                                    ${dayJobs.length} job${dayJobs.length !== 1 ? 's' : ''}
                                </div>
                            </div>
                            <div class="schedule-jobs">
                                ${items || `
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-calendar-times fa-2x mb-2 opacity-50"></i>
                                        <div class="small">No jobs scheduled</div>
                                    </div>
                                `}
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
                    
                    const mapUrl = j.shop_address ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(j.shop_address)}` : '';
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
                                    ${mapUrl ? `
                                        <a class="btn btn-outline-primary btn-sm" href="${mapUrl}" target="_blank">
                                            <i class="fas fa-map-marker-alt me-1"></i>Open in Maps
                                        </a>
                                    ` : ''}
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


