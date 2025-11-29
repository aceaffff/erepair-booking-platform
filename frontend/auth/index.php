<?php
// Get website logo for favicon
$faviconUrl = '../../backend/api/favicon.php';
$logoUrl = null;

try {
    require_once __DIR__ . '/../../backend/config/database.php';
    $db = (new Database())->getConnection();
    
    // Get admin user's logo_url
    $stmt = $db->prepare("SELECT logo_url FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && !empty($admin['logo_url'])) {
        $logoUrl = $admin['logo_url'];
        
        // Normalize the URL for this page (frontend/auth/)
        if (!preg_match('/^https?:\/\//', $logoUrl)) {
            if (strpos($logoUrl, '../backend/') === 0) {
                // Path is relative to frontend/, already correct for auth/
                // Just keep as is
            } elseif (strpos($logoUrl, 'backend/') === 0) {
                $logoUrl = '../' . $logoUrl;
            } elseif (strpos($logoUrl, '../') === false && strpos($logoUrl, '/') === false) {
                $logoUrl = '../../backend/uploads/logos/' . basename($logoUrl);
            }
        }
    }
} catch (Exception $e) {
    // Use default favicon on error
    $logoUrl = null;
}

// Use favicon.php as default, or the logo URL if available
$faviconHref = $logoUrl ? $logoUrl : $faviconUrl;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERepair - Book and manage your electronics repair services">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ERepair">
    <!-- Prevent caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Smart Electronics Repair Booking</title>
    <link rel="icon" type="image/png" id="favicon" href="../../backend/api/favicon.php">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-generator.php?size=192">
    <link href="../assets/css/tailwind.css?v=1.4.0" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-styles.css?v=1.4.0" rel="stylesheet">
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <script src="../assets/js/erepair-common.js?v=1.4.0" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .mono {
            font-family: 'JetBrains Mono', monospace;
        }
        
        body {
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* Dark Blue Hero Background */
        .hero-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e2a54 25%, #1e3a8a 50%, #1e40af 75%, #3b82f6 100%);
            background-size: 400% 400%;
            animation: gradient-flow 15s ease infinite;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes gradient-flow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Subtle Grid Pattern */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: grid-subtle 20s linear infinite;
        }
        
        @keyframes grid-subtle {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }
        
        /* Professional Navigation */
        .nav-professional {
            background: rgba(6, 11, 23, 0.98);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(30, 58, 138, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
        }
        
        /* PWA Install Button */
        .pwa-install-button {
            display: inline-flex !important; /* Always visible */
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            z-index: 1000;
            pointer-events: auto;
            user-select: none;
        }
        
        .pwa-install-button.installed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .pwa-install-button.installed:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .pwa-install-button:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .pwa-install-button:active {
            transform: translateY(0);
        }
        
        .pwa-install-button i {
            font-size: 1rem;
        }
        
        /* Responsive: Show only icon on mobile */
        @media (max-width: 640px) {
            .pwa-install-button span {
                display: none;
            }
            .pwa-install-button {
                padding: 0.5rem;
                min-width: 40px;
                justify-content: center;
            }
        }
        
        /* Refined Glass Effect */
        .glass-minimal {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        /* Professional Card Design */
        .card-minimal {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-minimal:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            border-color: #1e3a8a;
        }
        
        /* Subtle Icon Effects */
        .icon-subtle {
            transition: all 0.3s ease;
        }
        
        .card-minimal:hover .icon-subtle {
            transform: scale(1.1);
        }
        
        /* Professional Button */
        .btn-professional {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1e40af 100%);
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-professional::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.5s;
        }
        
        .btn-professional:hover::before {
            left: 100%;
        }
        
        .btn-professional:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.5);
        }
        
        /* Refined Step Design */
        .step-minimal {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            transition: all 0.4s ease;
            position: relative;
        }
        
        .step-minimal::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -24px;
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, #0f172a, #1e3a8a);
            transform: translateY(-50%);
            z-index: -1;
        }
        
        .step-minimal:last-child::after {
            display: none;
        }
        
        .step-minimal:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border-color: #1e3a8a;
        }
        
        /* Clean Number Badge */
        .number-badge {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1e40af 100%);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            margin: 0 auto;
            transition: all 0.3s ease;
        }
        
        .step-minimal:hover .number-badge {
            transform: scale(1.1);
        }
        
        /* Professional Typography */
        .heading-primary {
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        
        .heading-secondary {
            font-weight: 600;
            letter-spacing: -0.01em;
            line-height: 1.3;
        }
        
        /* Subtle Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Professional CTA Section */
        .cta-professional {
            background: linear-gradient(135deg, #030712 0%, #0f172a 25%, #1e293b 50%, #334155 75%, #475569 100%);
            position: relative;
        }
        
        .cta-professional::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231e3a8a' fill-opacity='0.08'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }
        
        /* Clean Footer */
        .footer-professional {
            background: linear-gradient(135deg, #030712 0%, #0f172a 50%, #1e293b 100%);
            color: #d1d5db;
        }
        
        /* Brand Enhancement */
        .brand-text {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        /* Hero Logo Styling - Left Side */
        .hero-logo {
            width: 85%;
            height: 85%;
            max-width: 85%;
            max-height: 85%;
            object-fit: contain;
            filter: drop-shadow(0 4px 20px rgba(0, 0, 0, 0.3));
            position: relative;
        }
        
        /* Logo Container with Gradient Background - Round */
        .hero-logo-container {
            position: relative;
            background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.15) 0%, transparent 60%),
                        radial-gradient(circle at 70% 70%, rgba(139, 92, 246, 0.15) 0%, transparent 60%);
            border-radius: 50%;
            width: 400px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            animation: containerPulse 4s ease-in-out infinite;
        }
        
        @keyframes containerPulse {
            0%, 100% {
                box-shadow: 0 0 20px rgba(99, 102, 241, 0.2),
                            0 0 40px rgba(139, 92, 246, 0.1);
            }
            50% {
                box-shadow: 0 0 30px rgba(99, 102, 241, 0.3),
                            0 0 60px rgba(139, 92, 246, 0.2);
            }
        }
        
        /* Animated gradient border effect - Round */
        .hero-logo-container::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6366f1, #8b5cf6, #a855f7, #ec4899, #6366f1);
            background-size: 300% 300%;
            opacity: 0.3;
            z-index: -1;
            animation: gradientShift 4s ease infinite;
            filter: blur(8px);
        }
        
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        @media (max-width: 768px) {
            .hero-logo-container {
                width: 300px;
                height: 300px;
            }
        }
        
        @media (max-width: 640px) {
            .hero-logo-container {
                width: 250px;
                height: 250px;
            }
        }
        
        /* Hero Content Layout */
        .hero-content-wrapper {
            display: flex;
            align-items: center;
            gap: 3rem;
        }
        
        .hero-logo-container {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-text-container {
            flex: 1;
            text-align: left;
        }
        
        @media (max-width: 968px) {
            .hero-content-wrapper {
                flex-direction: column;
                gap: 2rem;
            }
            
            .hero-text-container {
                text-align: center;
            }
        }
        
        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Professional Spacing */
        .section-padding {
            padding: 6rem 0;
        }
        
        @media (max-width: 768px) {
            .section-padding {
                padding: 4rem 0;
            }
        }
        
        /* Alpine.js x-cloak */
        [x-cloak] {
            display: none !important;
        }
        
        /* Modal backdrop blur effect */
        .modal-backdrop {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>
    <script>
        // Professional Scroll Effects
        function initScrollEffects() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
            
            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });
        }
        
        // Smooth Navigation
        function initNavigation() {
            const nav = document.querySelector('.nav-professional');
            let lastScroll = 0;
            
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll > 100) {
                    nav.style.background = 'rgba(6, 11, 23, 1)';
                    nav.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.5)';
                } else {
                    nav.style.background = 'rgba(6, 11, 23, 0.98)';
                    nav.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.4)';
                }
                
                lastScroll = currentScroll;
            });
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            initScrollEffects();
            initNavigation();
        });
    </script>
    <script>
        // Main page component
        function indexPage() {
            return {
                showLoginModal: false,
                showForgotPasswordModal: false
            }
        }

        // Login form component (same as login.html)
        function loginForm() {
            return {
                form: {
                    email: '',
                    password: '',
                    remember: false
                },
                showPassword: false,
                loading: false,

                async handleLogin() {
                    if (!this.form.email || !this.form.password) {
                        Notiflix.Report.failure('Validation Error', 'Please fill in all required fields', 'OK');
                        return;
                    }
                    
                    // Client-side SQL injection prevention
                    if (this.detectSqlInjection(this.form.email) || this.detectSqlInjection(this.form.password)) {
                        Notiflix.Report.failure('Invalid Characters', 'Special characters that could be used for security attacks are not allowed. Please use only letters, numbers, dots, hyphens, underscores, and @ symbol for email.', 'OK');
                        return;
                    }
                    
                    // Additional client-side validation
                    if (this.form.email.length > 254 || this.form.password.length > 128) {
                        Notiflix.Report.failure('Validation Error', 'Input is too long', 'OK');
                        return;
                    }
                    
                    this.loading = true;
                    
                    try {
                        const response = await fetch('../../backend/api/login.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.form)
                        });

                        // Parse response as JSON (even for error status codes)
                        let data;
                        try {
                            data = await response.json();
                        } catch (e) {
                            // If response is not JSON, create error object
                            data = { error: 'Server returned an invalid response. Please try again.' };
                        }

                        if (response.ok && data.success) {
                            // Store token in localStorage
                            localStorage.setItem('auth_token', data.token);
                            localStorage.setItem('user_data', JSON.stringify(data.user));
                            // Also set an auth cookie for PHP to read as fallback
                            try {
                                const expires = new Date(Date.now() + 24*60*60*1000).toUTCString();
                                document.cookie = `auth_token=${data.token}; expires=${expires}; path=/`;
                            } catch (e) {}

                            // Close modal - access parent indexPage scope
                            const bodyEl = document.querySelector('body[x-data="indexPage()"]');
                            if (bodyEl) {
                                const indexPageData = Alpine.$data(bodyEl);
                                if (indexPageData) {
                                    indexPageData.showLoginModal = false;
                                }
                            }

                            // Store role for redirect
                            const userRole = data.user.role;
                            
                            // Show success dialog - wait a moment for it to display
                            setTimeout(() => {
                                Notiflix.Report.success('Login Successful!', 'Welcome back! Redirecting to your dashboard...', 'OK', () => {
                                    // Clear auto-redirect timer if user clicks OK
                                    if (this._redirectTimer) {
                                        clearTimeout(this._redirectTimer);
                                        this._redirectTimer = null;
                                    }
                                    // Redirect immediately when OK is clicked
                                    this.redirectUser(userRole);
                                });
                                
                                // Auto-redirect after 3 seconds (gives time for dialog to display and be visible)
                                // Only redirect if timer hasn't been cleared (user hasn't clicked OK)
                                this._redirectTimer = setTimeout(() => {
                                    if (this._redirectTimer) {
                                        this._redirectTimer = null;
                                        this.redirectUser(userRole);
                                    }
                                }, 3000);
                            }, 100);
                        } else {
                            // Handle error responses (403, 401, etc.)
                            const errorMessage = data.error || 'Invalid credentials. Please try again.';
                            
                            // Check if user needs to verify email
                            if (response.status === 403 && data.redirect_to_verification && data.email) {
                                // Close modal
                                const bodyEl = document.querySelector('body[x-data="indexPage()"]');
                                if (bodyEl) {
                                    const indexPageData = Alpine.$data(bodyEl);
                                    if (indexPageData) {
                                        indexPageData.showLoginModal = false;
                                    }
                                }
                                
                                // Store email for verification page
                                localStorage.setItem('pending_verify_email', data.email);
                                
                                // Redirect to verification page
                                const base = `${window.location.origin}/ERepair/repair-booking-platform/frontend/verification/verify-email.php`;
                                window.location.href = base;
                                return;
                            }
                            
                            // Show error/warning based on status code
                            if (response.status === 403) {
                                Notiflix.Report.warning('Access Denied', errorMessage, 'OK');
                            } else {
                                Notiflix.Report.failure('Login Failed', errorMessage, 'OK');
                            }
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        Notiflix.Report.failure('Error', 'Network error. Please try again.', 'OK');
                    } finally {
                        this.loading = false;
                    }
                },

                redirectUser(role) {
                    // Prevent multiple redirects
                    if (this._isRedirecting) {
                        console.log('Redirect already in progress, skipping...');
                        return;
                    }
                    this._isRedirecting = true;
                    
                    // Clear any pending redirect timer
                    if (this._redirectTimer) {
                        clearTimeout(this._redirectTimer);
                        this._redirectTimer = null;
                    }
                    
                    const token = localStorage.getItem('auth_token') || '';
                    if (!token) {
                        console.error('No auth token found for redirect');
                        Notiflix.Report.failure('Error', 'Authentication token not found. Please try logging in again.', 'OK');
                        this._isRedirecting = false;
                        return;
                    }
                    
                    // Build dashboard URL
                    const base = `${window.location.origin}/ERepair/repair-booking-platform/frontend/`;
                    const dashboards = {
                        'customer': `${base}customer/customer_dashboard.php?token=${encodeURIComponent(token)}`,
                        'shop_owner': `${base}shop/shop_dashboard.php?token=${encodeURIComponent(token)}`,
                        'admin': `${base}admin/admin_dashboard.php?token=${encodeURIComponent(token)}`,
                        'technician': `${base}technician/technician_dashboard.php?token=${encodeURIComponent(token)}`
                    };
                    const dashboard = dashboards[role] || dashboards['customer'];
                    
                    console.log('Redirecting to dashboard:', dashboard, 'Role:', role);
                    
                    // Small delay to ensure any dialogs are closed
                    setTimeout(() => {
                        // Force redirect using replace to prevent going back
                        window.location.replace(dashboard);
                    }, 100);
                },
                
                detectSqlInjection(input) {
                    if (!input || typeof input !== 'string') return false;
                    
                    const inputLower = input.toLowerCase();
                    
                    // Block dangerous special characters that could be used for SQL injection
                    const dangerousChars = ['\'', '"', ';', '--', '/*', '*/', '#', '\\', '`', '(', ')', '[', ']', '{', '}', '|', '&', '^', '%', '*', '+', '=', '<', '>', '?', '!', '~', ':', '\\', '/'];
                    for (let char of dangerousChars) {
                        if (input.includes(char)) {
                            return true;
                        }
                    }
                    
                    // Common SQL injection patterns
                    const dangerousPatterns = [
                        /\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script)\b/i,
                        /--|\/\*|\*\/|#/,
                        /['"]\s*(or|and)\s*['"]?\d*['"]?\s*=\s*['"]?\d*['"]?/i,
                        /\b(or|and)\s+\d+\s*=\s*\d+/i,
                        /\b(sleep|waitfor|benchmark)\s*\(/i,
                        /\b(extractvalue|updatexml|exp|floor|rand)\s*\(/i,
                        /;\s*(select|insert|update|delete|drop|create|alter)/i,
                        /union\s+(all\s+)?select/i,
                        /information_schema|mysql\.user|pg_user|sys\.databases/i,
                        /\b(user|database|version|@@version|@@hostname|@@datadir)\b/i,
                        /\b(load_file|into\s+outfile|into\s+dumpfile)\b/i,
                        /\b(sp_|xp_|ms_)\w+/i,
                        /\b(if|case|when|then|else|end)\b/i,
                        /\b(concat|substring|ascii|char|hex|unhex)\s*\(/i,
                        /\b(pow|sqrt|abs|ceil|floor|round)\s*\(/i,
                        /\b(now|curdate|curtime|date|time|year|month|day)\s*\(/i,
                        /\b(count|sum|avg|min|max|group_concat)\s*\(/i,
                        /\b(regexp|rlike|soundex)\b/i,
                        /\b(md5|sha1|sha2|aes_encrypt|aes_decrypt)\s*\(/i,
                        /\b(json_extract|json_object|json_array)\s*\(/i,
                        /\b(xml|extractvalue|updatexml)\s*\(/i,
                        /\b(declare|set|begin|end|goto|return)\b/i,
                        /\b(transaction|commit|rollback|savepoint)\b/i,
                        /\b(grant|revoke|deny|backup|restore)\b/i,
                        /\b(kill|shutdown|restart|reload)\b/i,
                        /\b(show|describe|explain|analyze)\b/i,
                        /\b(optimize|repair|check|flush)\b/i,
                        /\b(lock|unlock|get_lock|release_lock)\b/i,
                        /\b(load|source|use|connect)\b/i,
                        /\b(change|rename|truncate|vacuum)\b/i
                    ];
                    
                    return dangerousPatterns.some(pattern => pattern.test(inputLower));
                },
                
                // Prevent special characters on keydown
                preventSpecialChars(event, fieldType) {
                    const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
                    if (allowedKeys.includes(event.key)) return;
                    
                    // Allow Ctrl/Cmd + A/C/V/X
                    if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) return;
                    
                    // Define allowed characters based on field type
                    let allowedPattern;
                    if (fieldType === 'email') {
                        // Email: only alphanumeric, dots, hyphens, underscores, and @
                        allowedPattern = /^[a-zA-Z0-9._@-]$/;
                    } else if (fieldType === 'password') {
                        // Password: only alphanumeric, dots, hyphens, and underscores
                        allowedPattern = /^[a-zA-Z0-9._-]$/;
                    } else {
                        // Default: only alphanumeric and basic punctuation
                        allowedPattern = /^[a-zA-Z0-9._-]$/;
                    }
                    
                    // Block characters that don't match the allowed pattern
                    if (!allowedPattern.test(event.key)) {
                        event.preventDefault();
                    }
                },

                // Handle paste events
                handlePaste(event, fieldType) {
                    event.preventDefault();
                    const paste = (event.clipboardData || window.clipboardData).getData('text');
                    const filtered = this.filterInput(paste, fieldType);
                    
                    // Insert filtered content at cursor position
                    const input = event.target;
                    const start = input.selectionStart || 0;
                    const end = input.selectionEnd || input.value.length;
                    const currentValue = input.value;
                    const newValue = currentValue.substring(0, start) + filtered + currentValue.substring(end);
                    
                    // Update the input value
                    input.value = newValue;
                    this.form[fieldType] = newValue;
                    
                    // Set cursor position after the inserted text (only if supported)
                    try {
                        const newCursorPos = start + filtered.length;
                        // Check if setSelectionRange is supported for this input type
                        if (input.type !== 'email' && input.type !== 'number' && input.type !== 'date' && input.type !== 'time') {
                            input.setSelectionRange(newCursorPos, newCursorPos);
                        } else {
                            // For email and other special input types, just focus the input
                            input.focus();
                        }
                    } catch (e) {
                        // If setSelectionRange fails, just focus the input
                        input.focus();
                    }
                },

                // Real-time input filtering to prevent dangerous characters
                filterInput(input, fieldType) {
                    if (!input) return input;
                    
                    // Define allowed characters based on field type
                    let allowedPattern;
                    if (fieldType === 'email') {
                        // Email: only alphanumeric, dots, hyphens, underscores, and @
                        allowedPattern = /^[a-zA-Z0-9._@-]+$/;
                    } else if (fieldType === 'password') {
                        // Password: only alphanumeric, dots, hyphens, and underscores
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    } else {
                        // Default: only alphanumeric and basic punctuation
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    }
                    
                    // Remove any characters that don't match the allowed pattern
                    return input.split('').filter(char => allowedPattern.test(char)).join('');
                }
            }
        }

        // Forgot Password form component
        function forgotPasswordForm() {
            return {
                step: 'request', // 'request' or 'reset'
                email: '',
                code: '',
                password: '',
                confirmPassword: '',
                showPassword: false,
                loading: false,
                message: '',
                success: false,
                
                async handleSubmit() {
                    if (this.step === 'request') {
                        await this.sendCode();
                    } else {
                        await this.resetPassword();
                    }
                },
                
                async sendCode() {
                    this.loading = true; 
                    this.message = '';
                    this.success = false;
                    
                    if(!this.email || !this.email.trim()){
                        this.message = 'Please enter your email address';
                        this.loading = false;
                        return;
                    }
                    
                    try{
                        const res = await fetch('../../backend/api/forgot-password-request.php', {
                            method: 'POST', 
                            headers: {'Content-Type': 'application/json'}, 
                            body: JSON.stringify({email: this.email.trim()})
                        });
                        
                        const data = await res.json();
                        
                        if(data.success){
                            this.success = true;
                            this.message = 'Code sent! Please check your email for the 6-digit code.';
                            
                            // Switch to reset step after a short delay
                            setTimeout(() => {
                                this.step = 'reset';
                                this.message = '';
                            }, 1500);
                        } else {
                            this.success = false;
                            this.message = data.error || 'An error occurred. Please try again.';
                        }
                    } catch(e){ 
                        console.error('Send code error:', e);
                        this.success = false;
                        this.message = 'Network error. Please check your connection and try again.';
                    } finally {
                        this.loading = false;
                    }
                },
                
                async resetPassword(){
                    this.loading = true;
                    this.message = '';
                    this.success = false;
                    
                    // Validation
                    if(!this.email || !this.email.trim()){
                        this.message = 'Email is required';
                        this.loading = false;
                        return;
                    }
                    
                    if(!this.code || this.code.length !== 6){
                        this.message = 'Please enter a valid 6-digit code';
                        this.loading = false;
                        return;
                    }
                    
                    if(!this.password || this.password.length < 6){
                        this.message = 'Password must be at least 6 characters';
                        this.loading = false;
                        return;
                    }
                    
                    if(this.password !== this.confirmPassword){
                        this.message = 'Passwords do not match';
                        this.loading = false;
                        return;
                    }
                    
                    try{
                        const res = await fetch('../../backend/api/reset-password.php', {
                            method: 'POST', 
                            headers: {'Content-Type': 'application/json'}, 
                            body: JSON.stringify({
                                email: this.email.trim(),
                                code: this.code.trim(),
                                new_password: this.password
                            })
                        });
                        
                        if (!res.ok) {
                            let errorData;
                            try {
                                errorData = await res.json();
                            } catch (e) {
                                errorData = { error: 'Server error occurred' };
                            }
                            throw new Error(errorData.error || `HTTP ${res.status}: ${res.statusText}`);
                        }
                        
                        const data = await res.json();
                        
                        if(data.success){
                            this.success = true;
                            this.message = 'Password changed successfully! Redirecting to login...';
                            setTimeout(() => {
                                const bodyEl = document.querySelector('body[x-data="indexPage()"]');
                                if (bodyEl) {
                                    const indexPageData = Alpine.$data(bodyEl);
                                    if (indexPageData) {
                                        indexPageData.showForgotPasswordModal = false;
                                        indexPageData.showLoginModal = true;
                                    }
                                }
                            }, 1500);
                        } else {
                            this.success = false;
                            this.message = data.error || 'An error occurred. Please check your code and try again.';
                        }
                    } catch(e){ 
                        console.error('Reset password error:', e);
                        this.success = false;
                        this.message = e.message || 'Network error. Please check your connection and try again.';
                    } finally {
                        this.loading = false;
                    }
                },
                
                filterDigitKeydown(e) {
                    const allowedKeys = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
                    if (allowedKeys.includes(e.key)) return; 
                    if ((e.ctrlKey || e.metaKey) && ['a','c','v','x'].includes(e.key.toLowerCase())) return;
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                        return;
                    }
                    if (this.code && this.code.length >= 6) {
                        e.preventDefault();
                    }
                },
                
                filterDigitPaste(e, targetField) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numeric = paste.replace(/[^0-9]/g, '');
                    const limitedNumeric = numeric.substring(0, 6);
                    this[targetField] = limitedNumeric;
                },
                
                filterInput(input, fieldType) {
                    if (!input) return input;
                    let allowedPattern;
                    if (fieldType === 'email') {
                        allowedPattern = /^[a-zA-Z0-9._@-]+$/;
                    } else if (fieldType === 'password') {
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    } else if (fieldType === 'code') {
                        allowedPattern = /^[0-9]+$/;
                    } else {
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    }
                    return input.split('').filter(char => allowedPattern.test(char)).join('');
                },
                
                preventSpecialChars(event, fieldType) {
                    const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
                    if (allowedKeys.includes(event.key)) return;
                    if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) return;
                    
                    let allowedPattern;
                    if (fieldType === 'email') {
                        allowedPattern = /^[a-zA-Z0-9._@-]$/;
                    } else if (fieldType === 'password') {
                        allowedPattern = /^[a-zA-Z0-9._-]$/;
                    } else {
                        allowedPattern = /^[a-zA-Z0-9._-]$/;
                    }
                    
                    if (!allowedPattern.test(event.key)) {
                        event.preventDefault();
                    }
                },
                
                handlePaste(event, fieldType) {
                    event.preventDefault();
                    const paste = (event.clipboardData || window.clipboardData).getData('text');
                    const filtered = this.filterInput(paste, fieldType);
                    
                    const input = event.target;
                    const start = input.selectionStart || 0;
                    const end = input.selectionEnd || input.value.length;
                    const currentValue = input.value;
                    const newValue = currentValue.substring(0, start) + filtered + currentValue.substring(end);
                    
                    input.value = newValue;
                    if (fieldType === 'email') {
                        this.email = newValue;
                    } else if (fieldType === 'password') {
                        if (input.id === 'reset-password') {
                            this.password = newValue;
                        } else if (input.id === 'reset-confirm-password') {
                            this.confirmPassword = newValue;
                        }
                    } else if (fieldType === 'code') {
                        this.code = newValue.substring(0, 6);
                    }
                    
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    
                    try {
                        const newCursorPos = start + filtered.length;
                        if (input.type !== 'email' && input.type !== 'number' && input.type !== 'date' && input.type !== 'time') {
                            input.setSelectionRange(newCursorPos, newCursorPos);
                        } else {
                            input.focus();
                        }
                    } catch (e) {
                        input.focus();
                    }
                }
            }
        }

        // Initialize Alpine.js components
        document.addEventListener('alpine:init', () => {
            Alpine.data('indexPage', indexPage);
            Alpine.data('loginForm', loginForm);
            Alpine.data('forgotPasswordForm', forgotPasswordForm);
        });
    </script>
</head>
    <body class="bg-slate-900" x-data="indexPage()">
    <!-- Professional Navigation -->
    <nav class="nav-professional fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold brand-text">
                            <i class="fas fa-tools mr-2 icon-subtle"></i>ERepair
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- PWA Install Button -->
                    <button 
                        id="pwa-install-btn"
                        class="pwa-install-button"
                        type="button"
                        title="Install ERepair App"
                        aria-label="Install ERepair App"
                        onclick="handlePWAInstallClick(event)">
                        <i class="fas fa-download"></i>
                        <span>Install App</span>
                    </button>
                    <button @click="showLoginModal = true" class="text-gray-400 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-white hover:bg-opacity-5">
                        Sign In
                    </button>
                    <a href="../register-step.php" class="btn-professional text-white px-6 py-2 rounded-lg text-sm font-medium">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white section-padding relative min-h-screen flex items-center">
        <div class="hero-overlay"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="glass-minimal rounded-3xl p-12 max-w-6xl mx-auto">
                <div class="hero-content-wrapper">
                    <!-- Logo on Left -->
                    <div class="hero-logo-container">
                        <img id="hero-logo" src="../assets/icons/repairhublogo.png" alt="ERepair Logo" class="hero-logo">
                    </div>
                    
                    <!-- Content on Right -->
                    <div class="hero-text-container">
                        <h1 class="heading-primary text-4xl md:text-6xl mb-6">
                            Smart Electronics Repair Booking
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 text-white text-opacity-90">
                            Connect with verified repair professionals and get your devices fixed quickly and efficiently with our intelligent booking platform
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button @click="showLoginModal = true" class="bg-white text-slate-900 hover:bg-gray-100 px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 hover:transform hover:scale-105">
                                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                            </button>
                            <a href="../register-step.php" class="glass-minimal text-white hover:bg-white hover:bg-opacity-20 px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 border border-white border-opacity-30">
                                <i class="fas fa-user-plus mr-2"></i>Create Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 fade-in">
                <h2 class="heading-secondary text-3xl md:text-4xl text-gray-900 mb-4">
                    Why Choose ERepair?
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    We make electronics repair simple, transparent, and reliable through innovative technology and trusted partnerships
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Easy Booking -->
                <div class="card-minimal rounded-2xl p-8 fade-in">
                    <div class="text-center">
                        <div class="bg-blue-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-calendar-check text-2xl text-blue-600 icon-subtle"></i>
                        </div>
                        <h3 class="heading-secondary text-xl text-gray-900 mb-4">Effortless Booking</h3>
                        <p class="text-gray-600">
                            Schedule repair appointments in just a few clicks. Choose your preferred time slot and receive instant confirmation with automated reminders.
                        </p>
                    </div>
                </div>

                <!-- Verified Shops -->
                <div class="card-minimal rounded-2xl p-8 fade-in" style="animation-delay: 0.2s;">
                    <div class="text-center">
                        <div class="bg-green-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-shield-alt text-2xl text-green-600 icon-subtle"></i>
                        </div>
                        <h3 class="heading-secondary text-xl text-gray-900 mb-4">Verified Professionals</h3>
                        <p class="text-gray-600">
                            All repair shops undergo rigorous verification and are continuously rated by customers. Quality assurance and warranty protection included.
                        </p>
                    </div>
                </div>

                <!-- Real-Time Updates -->
                <div class="card-minimal rounded-2xl p-8 fade-in" style="animation-delay: 0.4s;">
                    <div class="text-center">
                        <div class="bg-purple-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-sync-alt text-2xl text-purple-600 icon-subtle"></i>
                        </div>
                        <h3 class="heading-secondary text-xl text-gray-900 mb-4">Live Progress Tracking</h3>
                        <p class="text-gray-600">
                            Stay informed with real-time notifications and progress updates. Track every step from diagnosis to completion with full transparency.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section-padding bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 fade-in">
                <h2 class="heading-secondary text-3xl md:text-4xl text-gray-900 mb-4">
                    How It Works
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Get your device repaired with our streamlined 3-step process designed for maximum convenience
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="step-minimal rounded-2xl p-8 text-center fade-in">
                    <div class="number-badge mb-6">
                        1
                    </div>
                    <h3 class="heading-secondary text-xl text-gray-900 mb-4">Book Your Appointment</h3>
                    <p class="text-gray-600">
                        Select your device type, describe the issue in detail, and choose from available time slots that fit your schedule perfectly.
                    </p>
                </div>
                
                <div class="step-minimal rounded-2xl p-8 text-center fade-in" style="animation-delay: 0.2s;">
                    <div class="number-badge mb-6">
                        2
                    </div>
                    <h3 class="heading-secondary text-xl text-gray-900 mb-4">Professional Diagnosis</h3>
                    <p class="text-gray-600">
                        Visit the repair shop at your scheduled time for expert diagnosis and transparent pricing before any work begins.
                    </p>
                </div>
                
                <div class="step-minimal rounded-2xl p-8 text-center fade-in" style="animation-delay: 0.4s;">
                    <div class="number-badge mb-6">
                        3
                    </div>
                    <h3 class="heading-secondary text-xl text-gray-900 mb-4">Quality Restoration</h3>
                    <p class="text-gray-600">
                        Receive progress updates throughout the repair process and collect your fully functional device with warranty coverage.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-professional section-padding relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="card-minimal rounded-3xl p-12 max-w-4xl mx-auto fade-in">
                <h2 class="heading-secondary text-3xl md:text-4xl text-white mb-4">
                    Ready to Get Started?
                </h2>
                <p class="text-xl text-gray-200 mb-8 max-w-2xl mx-auto">
                    Join thousands of satisfied customers who trust ERepair for professional, reliable electronics repair services.
                </p>
                <a href="../register-step.php" class="btn-professional text-white px-8 py-4 rounded-lg text-lg font-semibold inline-block">
                    <i class="fas fa-rocket mr-2"></i>Start Your Repair Journey
                </a>
            </div>
        </div>
    </section>

    <!-- Professional Footer -->
    <footer class="footer-professional py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="fade-in">
                    <h3 class="text-xl font-bold mb-4 brand-text text-white">
                        <i class="fas fa-tools mr-2"></i>ERepair
                    </h3>
                    <p class="text-gray-400 mb-4">
                        Your trusted partner for professional electronics repair services. Quality craftsmanship, reliable service, and complete customer satisfaction guaranteed.
                    </p>
                </div>
                
                <div class="fade-in" style="animation-delay: 0.1s;">
                    <h4 class="text-lg font-semibold mb-4 text-white">Navigation</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                        <li><button @click="showLoginModal = true" class="text-gray-400 hover:text-white transition-colors">Sign In</button></li>
                        <li><a href="../register-step.php" class="text-gray-400 hover:text-white transition-colors">Create Account</a></li>
                    </ul>
                </div>
                
                <div class="fade-in" style="animation-delay: 0.2s;">
                    <h4 class="text-lg font-semibold mb-4 text-white">Repair Services</h4>
                    <ul class="space-y-3">
                        <li class="text-gray-400">Smartphone Repair</li>
                        <li class="text-gray-400">Laptop & Computer Repair</li>
                        <li class="text-gray-400">Tablet Repair</li>
                        <li class="text-gray-400">Gaming Console Repair</li>
                    </ul>
                </div>
                
                <div class="fade-in" style="animation-delay: 0.3s;">
                    <h4 class="text-lg font-semibold mb-4 text-white">Get in Touch</h4>
                    <div class="space-y-3">
                        <p class="text-gray-400 flex items-center">
                            <i class="fas fa-envelope mr-3 text-blue-400"></i>support@erepair.com
                        </p>
                        <p class="text-gray-400 flex items-center">
                            <i class="fas fa-phone mr-3 text-blue-400"></i>+639060643212
                        </p>
                        <p class="text-gray-400 flex items-center">
                            <i class="fas fa-map-marker-alt mr-3 text-blue-400"></i>Loon, Bohol, Digital City
                        </p>
                    </div>
                </div>
            </div>
                
            <div class="border-t border-gray-700 mt-12 pt-8 text-center fade-in">
                <p class="text-gray-400">
                    © 2025 ERepair. All rights reserved. | <a href="#" class="hover:text-white transition-colors">Privacy Policy</a> | <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div x-show="showLoginModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.away="showLoginModal = false"
         @keydown.escape.window="showLoginModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay with blur -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 modal-backdrop" 
                 x-show="showLoginModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showLoginModal = false"></div>

            <!-- Modal panel - much wider -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
                 x-show="showLoginModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.stop>
                <div class="bg-white px-6 py-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Sign in to your account</h3>
                        <button @click="showLoginModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200 ml-4">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-6">
                        Or <a href="../register-step.php" class="font-medium text-indigo-600 hover:text-indigo-700">create a new account</a>
                    </p>
                    
                    <form class="space-y-6" x-data="loginForm()" @submit.prevent="handleLogin">
                        <div>
                            <label for="modal-email" class="block text-sm font-medium text-gray-700">
                                Email address
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="modal-email" 
                                    name="email" 
                                    type="email" 
                                    autocomplete="email" 
                                    required 
                                    x-model="form.email"
                                    @keydown="preventSpecialChars($event, 'email')"
                                    @input="form.email = filterInput($event.target.value, 'email')"
                                    @paste="handlePaste($event, 'email')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 bg-white border border-gray-300 rounded-md placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your email"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="modal-password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="modal-password" 
                                    name="password" 
                                    :type="showPassword ? 'text' : 'password'" 
                                    autocomplete="current-password" 
                                    required 
                                    x-model="form.password"
                                    @keydown="preventSpecialChars($event, 'password')"
                                    @input="form.password = filterInput($event.target.value, 'password')"
                                    @paste="handlePaste($event, 'password')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 pr-10 bg-white border border-gray-300 rounded-md placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your password"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button type="button" @click="showPassword = !showPassword" class="text-gray-400 hover:text-gray-600">
                                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input 
                                    id="modal-remember-me" 
                                    name="remember-me" 
                                    type="checkbox" 
                                    x-model="form.remember"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <label for="modal-remember-me" class="ml-2 block text-sm text-gray-700">
                                    Remember me
                                </label>
                            </div>

                            <div class="text-sm">
                                <button type="button" @click="showLoginModal = false; showForgotPasswordModal = true" class="font-medium text-indigo-600 hover:text-indigo-700">
                                    Forgot your password?
                                </button>
                            </div>
                        </div>

                        <div>
                            <button 
                                type="submit" 
                                :disabled="loading"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-sign-in-alt'" class="text-indigo-500 group-hover:text-indigo-300"></i>
                                </span>
                                <span x-text="loading ? 'Signing in...' : 'Sign in'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div x-show="showForgotPasswordModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.away="showForgotPasswordModal = false"
         @keydown.escape.window="showForgotPasswordModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay with blur -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 modal-backdrop" 
                 x-show="showForgotPasswordModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showForgotPasswordModal = false"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                 x-show="showForgotPasswordModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.stop>
                <div class="bg-white px-6 py-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Reset Password</h3>
                        <button @click="showForgotPasswordModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200 ml-4">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <form class="space-y-6" x-data="forgotPasswordForm()" @submit.prevent="handleSubmit" novalidate>
                        <!-- Step 1: Request Code -->
                        <div x-show="step === 'request'" x-transition>
                            <p class="text-sm text-gray-600 mb-4">Enter your email and we'll send a 6-digit code to reset your password.</p>
                            
                            <div>
                                <label for="forgot-email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email
                                </label>
                                <div class="mt-1 relative">
                                    <input 
                                        id="forgot-email" 
                                        type="email" 
                                        x-model="email" 
                                        :required="step === 'request'"
                                        :disabled="loading"
                                        @keydown="preventSpecialChars($event, 'email')"
                                        @input="email = filterInput($event.target.value, 'email')"
                                        @paste="handlePaste($event, 'email')"
                                        class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        placeholder="you@example.com"
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <button 
                                    type="submit" 
                                    :disabled="loading" 
                                    class="w-full bg-indigo-600 text-white rounded-md px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-indigo-700 transition-colors"
                                >
                                    <span x-show="!loading">Send Code</span>
                                    <span x-show="loading">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Sending...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Reset Password -->
                        <div x-show="step === 'reset'" x-transition>
                            <p class="text-sm text-gray-600 mb-4">Enter the 6-digit code sent to your email and your new password.</p>
                            
                            <div>
                                <label for="reset-email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email
                                </label>
                                <div class="mt-1 relative">
                                    <input 
                                        id="reset-email" 
                                        type="email" 
                                        x-model="email" 
                                        :required="step === 'reset'"
                                        readonly
                                        :disabled="loading"
                                        class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 bg-gray-50 text-gray-500 sm:text-sm"
                                        placeholder="you@example.com"
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="reset-code" class="block text-sm font-medium text-gray-700 mb-1">
                                    6-digit Code
                                </label>
                                <div class="mt-1 relative">
                                    <input 
                                        id="reset-code" 
                                        type="text" 
                                        x-model="code" 
                                        maxlength="6" 
                                        :required="step === 'reset'"
                                        pattern="[0-9]{6}"
                                        :disabled="loading"
                                        @keydown="filterDigitKeydown($event)"
                                        @input="code = filterInput($event.target.value, 'code')"
                                        @paste="filterDigitPaste($event, 'code')"
                                        class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        placeholder="123456"
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="reset-password" class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <div class="mt-1 relative">
                                    <input 
                                        id="reset-password" 
                                        :type="showPassword ? 'text' : 'password'" 
                                        x-model="password" 
                                        :required="step === 'reset'"
                                        minlength="6"
                                        :disabled="loading"
                                        @keydown="preventSpecialChars($event, 'password')"
                                        @input="password = filterInput($event.target.value, 'password')"
                                        @paste="handlePaste($event, 'password')"
                                        class="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        placeholder="••••••••"
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <button type="button" @click="showPassword = !showPassword" class="text-gray-400 hover:text-gray-600">
                                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="reset-confirm-password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password
                                </label>
                                <div class="mt-1 relative">
                                    <input 
                                        id="reset-confirm-password" 
                                        :type="showPassword ? 'text' : 'password'" 
                                        x-model="confirmPassword" 
                                        :required="step === 'reset'"
                                        minlength="6"
                                        :disabled="loading"
                                        @keydown="preventSpecialChars($event, 'password')"
                                        @input="confirmPassword = filterInput($event.target.value, 'password')"
                                        @paste="handlePaste($event, 'password')"
                                        class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        placeholder="••••••••"
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                </div>
                                <template x-if="password && confirmPassword && password !== confirmPassword">
                                    <p class="text-red-600 text-xs mt-1">Passwords do not match</p>
                                </template>
                            </div>
                            
                            <div class="flex gap-2">
                                <button 
                                    type="button"
                                    @click="step = 'request'; code = ''; password = ''; confirmPassword = ''; message = '';"
                                    :disabled="loading"
                                    class="flex-1 bg-gray-300 text-gray-700 rounded-md px-4 py-2 disabled:opacity-50 hover:bg-gray-400 transition-colors"
                                >
                                    Back
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="loading || password !== confirmPassword" 
                                    class="flex-1 bg-green-600 text-white rounded-md px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-green-700 transition-colors"
                                >
                                    <span x-show="!loading">Change Password</span>
                                    <span x-show="loading">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Changing...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <template x-if="message">
                            <div class="mt-4 text-sm p-3 rounded" 
                                 :class="success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'" 
                                 x-text="message"></div>
                        </template>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // favicon 
        function updateFavicon(logoUrl) {
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
        }

        // Load website logo function - uses repairhublogo.png
        async function loadWebsiteLogo() {
            // Use the repairhublogo.png from assets/icons
            const heroLogo = document.getElementById('hero-logo');
            if (heroLogo) {
                // Logo is already set in HTML, just ensure it's visible when loaded
                heroLogo.onload = function() {
                    this.style.display = 'block';
                    console.log('Hero logo loaded successfully:', this.src);
                };
                heroLogo.onerror = function() {
                    console.error('Hero logo failed to load:', this.src);
                    this.style.display = 'none';
                };
                
                // Trigger load check
                if (heroLogo.complete) {
                    heroLogo.style.display = 'block';
                }
            }
            
            // Still load admin logo for favicon
            try {
                const res = await fetch('../../backend/api/get-website-logo.php');
                const data = await res.json();
                if (data.success && data.logo_url) {
                    let logoUrl = data.logo_url;
                    // Normalize for frontend/auth/ (we're in auth/, need to go up 2 levels to root)
                    if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                        if (logoUrl.startsWith('../backend/')) {
                            logoUrl = '../' + logoUrl; // ../backend/... becomes ../../backend/...
                        } else if (logoUrl.startsWith('backend/')) {
                            logoUrl = '../../' + logoUrl;
                        } else if (logoUrl.startsWith('/')) {
                            if (logoUrl.startsWith('/repair-booking-platform/')) {
                                // Already absolute, use as is
                            } else {
                                logoUrl = '../../' + logoUrl.substring(1);
                            }
                        } else if (!logoUrl.includes('/')) {
                            logoUrl = '../../backend/uploads/logos/' + logoUrl;
                        } else {
                            logoUrl = '../../' + logoUrl;
                        }
                    }
                    
                    // Update favicon only
                    updateFavicon(logoUrl);
                    console.log('Index page: Favicon updated to:', logoUrl);
                }
            } catch (e) {
                console.error('Error loading website logo for favicon:', e);
            }
        }

        // Load logo on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadWebsiteLogo();
            
            // Force service worker update on page load
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(registrations => {
                    registrations.forEach(registration => {
                        registration.update();
                    });
                });
            }
        });
    </script>
    <script src="../assets/js/pwa-register.js?v=1.4.0"></script>
    <script>
        // Global PWA Install Click Handler (works immediately)
        window.handlePWAInstallClick = function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            console.log('[PWA] Install button clicked');
            console.log('[PWA] deferredPrompt available:', !!window.deferredPrompt);
            console.log('[PWA] installPWA function available:', typeof window.installPWA === 'function');
            
            // Check if app is already installed
            const isInstalled = window.matchMedia('(display-mode: standalone)').matches || 
                               window.navigator.standalone === true ||
                               document.referrer.includes('android-app://');
            
            if (isInstalled) {
                // App is installed - reload
                window.location.reload();
                return;
            }
            
            // Priority 1: Use deferredPrompt if available (most reliable)
            if (window.deferredPrompt) {
                console.log('[PWA] Using deferredPrompt to show install dialog');
                window.deferredPrompt.prompt();
                window.deferredPrompt.userChoice.then(function(choiceResult) {
                    console.log('[PWA] User choice:', choiceResult.outcome);
                    if (choiceResult.outcome === 'accepted') {
                        console.log('[PWA] User accepted the install prompt');
                    } else {
                        console.log('[PWA] User dismissed the install prompt');
                    }
                    window.deferredPrompt = null;
                    // Update button state after installation
                    if (typeof updateInstallButtonState === 'function') {
                        setTimeout(updateInstallButtonState, 1000);
                    }
                }).catch(function(error) {
                    console.error('[PWA] Error showing install prompt:', error);
                    // Fallback to manual instructions
                    showManualInstallInstructions();
                });
                return;
            }
            
            // Priority 2: Use installPWA function if available
            if (typeof window.installPWA === 'function') {
                console.log('[PWA] Using installPWA function');
                window.installPWA();
                return;
            }
            
            // Priority 3: Fallback to manual instructions
            console.log('[PWA] No install prompt available, showing manual instructions');
            showManualInstallInstructions();
        };
        
        // Helper function for manual install instructions
        function showManualInstallInstructions() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            
            let message = 'To install this app:\n\n';
            if (isIOS) {
                message += '1. Tap the Share button (square with arrow)\n';
                message += '2. Select "Add to Home Screen"\n';
                message += '3. Tap "Add"';
            } else if (isAndroid) {
                message += '1. Tap the menu (3 dots) in your browser\n';
                message += '2. Select "Add to Home Screen" or "Install App"\n';
                message += '3. Confirm installation';
            } else {
                message += 'Look for the install icon in your browser\'s address bar, or use the browser menu to install.';
            }
            
            alert(message);
        }
    </script>
</body>
</html>

