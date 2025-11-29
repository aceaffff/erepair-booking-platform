<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="../../backend/api/favicon.php">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-styles.css" rel="stylesheet">
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <script src="../assets/js/erepair-common.js"></script>
</head>
<body class="bg-light min-h-screen" x-data="verificationPage()" x-init="init()">
    <!-- Navigation - match login style -->
    <nav class="fixed w-full z-50 bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../auth/index.php" class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-slate-900">
                            <i class="fas fa-tools mr-2 icon-morph"></i>ERepair
                        </h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../auth/index.php" class="text-slate-700 hover:text-slate-900 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:bg-slate-100">
                        Home
                    </a>
                    <a href="../auth/index.php" class="btn-holographic text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Login
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Spacer to keep content below fixed navbar (same height as nav) -->
    <div class="h-16 w-full"></div>

    <!-- Main Content framed under the navbar -->
    <div class="min-h-screen flex items-center justify-center pb-12 px-4 sm:px-6 lg:px-8 relative">
        <!-- Background Effects -->
        <div class="hero-gradient absolute inset-0"></div>
        <div class="hero-grid"></div>
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="particles"></div>
        
        <div class="max-w-4xl w-full relative z-10">
            <!-- Verification Status Card -->
            <div class="glass-advanced rounded-lg shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="cta-advanced px-6 py-8 text-center">
                    <div x-show="loading" class="mb-4">
                        <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-white"></div>
                        <p class="text-white mt-4">Verifying your email...</p>
                    </div>
                    
                    <div x-show="!loading && success" class="mb-4">
                        <div class="success-animation inline-block">
                            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto">
                                <svg class="w-12 h-12 text-green-500 checkmark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5" stroke-dasharray="100" stroke-dashoffset="100" style="animation: checkmarkDraw 1s ease-in-out forwards;"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-3xl font-bold text-white mt-4">Email Verified Successfully!</h2>
                        <p class="text-indigo-100 mt-2">Your account is now active and ready to use</p>
                    </div>
                    
                    <div x-show="!loading && !success && !errorMessage" class="mb-4">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto">
                            <i class="fas fa-shield-halved text-4xl text-indigo-600"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-white mt-4">Verify Your Email</h2>
                        <p class="text-indigo-100 mt-2">Enter the 6-digit code sent to your email</p>
                    </div>
                    <div x-show="!loading && !success && errorMessage" class="mb-4">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                            <i class="fas fa-times text-4xl text-red-500"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-white mt-4">Verification Failed</h2>
                        <p class="text-indigo-100 mt-2">There was a problem verifying your email</p>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-8">
                    <!-- Success Content -->
                    <div x-show="!loading && success" class="text-center space-y-6">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                                <span class="text-green-800 font-medium">Verification Complete</span>
                            </div>
                        </div>
                        
                        <div class="text-left bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Account Details:</h3>
                            <p class="text-gray-600"><strong>Name:</strong> <span x-text="userData.name || 'N/A'"></span></p>
                            <p class="text-gray-600"><strong>Email:</strong> <span x-text="userData.email || 'N/A'"></span></p>
                            <p class="text-gray-600"><strong>Status:</strong> <span class="text-green-600 font-medium">Verified ✓</span></p>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 text-lg mr-3 mt-1"></i>
                                <div class="text-left">
                                    <h4 class="font-semibold text-blue-900 mb-1">What's Next?</h4>
                                    <p class="text-blue-800 text-sm">
                                        You can now log in to your account and start using ERepair services. 
                                        If you're a shop owner, your account will be reviewed by our admin team.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="../auth/index.php" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                            </a>
                            <a href="../auth/index.php" class="border border-indigo-600 text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-indigo-50 transition-colors">
                                <i class="fas fa-home mr-2"></i>Back to Home
                            </a>
                        </div>
                    </div>

                    <!-- Code Entry Content -->
                    <div x-show="!loading && !success" class="text-center space-y-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-6 text-left">
                            <h3 class="font-semibold text-gray-900 mb-4">Enter Verification Code</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input id="verify-email-input" type="email" placeholder="you@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" readonly />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">6-digit Code</label>
                                    <input 
                                        id="verify-code-input" 
                                        type="text" 
                                        maxlength="6" 
                                        placeholder="123456" 
                                        x-model="codeInput"
                                        @input="handleCodeInput($event)"
                                        @paste="handleCodePaste($event)"
                                        @keydown="handleCodeKeydown($event)"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md tracking-widest text-center text-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" 
                                    />
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-4">
                                <div class="text-sm text-gray-600">Code expires in <span id="verify-timer" class="font-semibold">05:00</span></div>
                                <div class="flex gap-2">
                                    <button @click="verifyEmail()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Verify</button>
                                    <button @click="resendCode()" class="border border-indigo-600 text-indigo-600 px-4 py-2 rounded-md hover:bg-indigo-50">Resend Code</button>
                                </div>
                            </div>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4" x-show="errorMessage">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
                                <span class="text-red-800 font-medium">Verification Failed</span>
                            </div>
                        </div>
                        
                        <div class="text-left bg-gray-50 rounded-lg p-4" x-show="errorMessage">
                            <h3 class="font-semibold text-gray-900 mb-2">Error Details:</h3>
                            <p class="text-gray-600"><strong>Message:</strong> <span x-text="errorMessage || 'Unknown error occurred'"></span></p>
                            <p class="text-gray-600"><strong>Code:</strong> <span x-text="verificationCode || 'N/A'"></span></p>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-lightbulb text-yellow-500 text-lg mr-3 mt-1"></i>
                                <div class="text-left">
                                    <h4 class="font-semibold text-yellow-900 mb-1">Possible Solutions:</h4>
                                    <ul class="text-yellow-800 text-sm space-y-1">
                                        <li>• Make sure you're using the latest code email</li>
                                        <li>• Codes expire in 5 minutes</li>
                                        <li>• Use the Resend Code button to get a fresh code</li>
                                        <li>• Contact support if the problem persists</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="../register-step.php" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>Register Again
                            </a>
                            <a href="../auth/index.php" class="border border-indigo-600 text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-indigo-50 transition-colors">
                                <i class="fas fa-home mr-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-8 text-center">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Need Help?</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-envelope text-indigo-500 mr-2"></i>
                            <span>support@erepair.com</span>
                        </div>
                        <div class="flex items-center justify-center">
                            <i class="fas fa-phone text-indigo-500 mr-2"></i>
                            <span>+1 (555) 123-4567</span>
                        </div>
                        <div class="flex items-center justify-center">
                            <i class="fas fa-clock text-indigo-500 mr-2"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function verificationPage() {
            return {
                loading: true,
                success: false,
                userData: {},
                errorMessage: '',
                verificationCode: '',
                codeInput: '',
                isResending: false, // Prevent duplicate resend calls

                initCalled: false, // Prevent init from running multiple times
                
                async init() {
                    // Prevent multiple init calls
                    if (this.initCalled) {
                        return;
                    }
                    this.initCalled = true;
                    
                    this.loading = false;
                    try {
                        const stored = localStorage.getItem('pending_verify_email');
                        if (stored) {
                            const input = document.getElementById('verify-email-input');
                            if (input) {
                                input.value = stored;
                                // Small delay to ensure page is fully loaded before resending
                                setTimeout(async () => {
                                    // Automatically resend verification code when redirected from login (silent mode)
                                    await this.resendCode(true);
                                }, 500);
                            }
                        }
                    } catch (e) {
                        console.error('Error in init:', e);
                    }
                },

                handleCodeInput(event) {
                    // Remove all non-digit characters
                    const input = event.target;
                    const cleaned = input.value.replace(/[^0-9]/g, '');
                    // Limit to 6 digits
                    const limited = cleaned.substring(0, 6);
                    input.value = limited;
                    this.codeInput = limited;
                },

                handleCodePaste(event) {
                    event.preventDefault();
                    const paste = (event.clipboardData || window.clipboardData).getData('text');
                    // Extract only digits from pasted content (handles spaces, newlines, etc.)
                    const digits = paste.replace(/[^0-9]/g, '');
                    // Limit to 6 digits
                    const limited = digits.substring(0, 6);
                    const input = event.target;
                    
                    // Use setTimeout to ensure DOM updates properly
                    setTimeout(() => {
                        input.value = limited;
                        this.codeInput = limited;
                        // Auto-focus and select all for easy replacement
                        input.focus();
                        try {
                            // Only set selection range if supported (text inputs support it)
                            if (input.type === 'text' || !input.type) {
                                input.setSelectionRange(0, limited.length);
                            }
                        } catch (e) {
                            // If setSelectionRange fails, just focus the input
                            input.focus();
                        }
                        
                        // Auto-verify if 6 digits are pasted
                        if (limited.length === 6) {
                            // Small delay to ensure input is updated
                            setTimeout(() => {
                                this.verifyEmail();
                            }, 100);
                        }
                    }, 0);
                },

                handleCodeKeydown(event) {
                    // Allow navigation keys
                    const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
                    if (allowedKeys.includes(event.key)) return;
                    
                    // Allow Ctrl/Cmd combinations
                    if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) return;
                    
                    // Only allow digits
                    if (!/^[0-9]$/.test(event.key)) {
                        event.preventDefault();
                        return;
                    }
                    
                    // Prevent if already at 6 digits
                    const input = event.target;
                    const currentValue = input.value || '';
                    const selectionStart = input.selectionStart || 0;
                    const selectionEnd = input.selectionEnd || 0;
                    const textBeforeCursor = currentValue.substring(0, selectionStart);
                    const textAfterCursor = currentValue.substring(selectionEnd);
                    const newValue = textBeforeCursor + event.key + textAfterCursor;
                    const numericValue = newValue.replace(/[^0-9]/g, '');
                    
                    if (numericValue.length > 6) {
                        event.preventDefault();
                    }
                },

                async verifyEmail() {
                    const emailInput = document.getElementById('verify-email-input');
                    const email = emailInput?.value?.trim() || '';
                    const code = this.codeInput || (document.getElementById('verify-code-input')?.value || '').replace(/[^0-9]/g, '');
                    
                    // Validate email
                    if (!email) {
                        Notiflix.Report.failure('Email Required', 'Please enter your email address.', 'OK');
                        if (emailInput) emailInput.focus();
                        return;
                    }
                    
                    // Validate email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        Notiflix.Report.failure('Invalid Email', 'Please enter a valid email address.', 'OK');
                        if (emailInput) emailInput.focus();
                        return;
                    }
                    
                    // Validate code
                    if (!code || !/^\d{6}$/.test(code)) {
                        Notiflix.Report.failure('Invalid Code', 'Please enter a valid 6-digit verification code.', 'OK');
                        const codeInput = document.getElementById('verify-code-input');
                        if (codeInput) codeInput.focus();
                        return;
                    }
                    this.loading = true;
                    this.errorMessage = '';
                    try {
                        const res = await fetch('../../backend/api/verify-email-code.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email, code })
                        });
                        
                        // Get response text first
                        const text = await res.text();
                        let data;
                        
                        // Try to parse JSON response
                        try {
                            data = JSON.parse(text);
                        } catch (parseError) {
                            console.error('Failed to parse response:', parseError);
                            console.error('Response text:', text);
                            // If response is not JSON, it might be a PHP error
                            if (text.includes('<br />') || text.includes('Fatal error') || text.includes('Warning')) {
                                throw new Error('Server error: ' + text.substring(0, 200));
                            }
                            throw new Error('Invalid response from server: ' + text.substring(0, 100));
                        }
                        
                        this.loading = false;
                        
                        // Check if response is ok
                        if (!res.ok) {
                            this.success = false;
                            this.errorMessage = data.error || `Server error (${res.status})`;
                            this.verificationCode = code;
                            Notiflix.Report.failure('Verification Failed', this.errorMessage, 'OK');
                            return;
                        }
                        
                        if (data.success) {
                            this.success = true;
                            this.userData = data.user || { email };
                            this.errorMessage = '';
                            Notiflix.Report.success('Email Verified!', 'Your email has been successfully verified.', 'OK');
                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = '../auth/index.php';
                            }, 2000);
                        } else {
                            this.success = false;
                            this.errorMessage = data.error || 'Verification failed';
                            this.verificationCode = code;
                            Notiflix.Report.failure('Verification Failed', this.errorMessage, 'OK');
                        }
                    } catch (e) {
                        this.loading = false;
                        this.success = false;
                        this.errorMessage = e.message || 'Network error occurred. Please check your connection and try again.';
                        this.verificationCode = code;
                        console.error('Verification error:', e);
                        Notiflix.Report.failure('Error', this.errorMessage, 'OK');
                    }
                },

                async resendCode(silent = false) {
                    // Prevent duplicate calls
                    if (this.isResending) {
                        console.log('Resend already in progress, skipping duplicate call');
                        return;
                    }
                    
                    const email = document.getElementById('verify-email-input')?.value || '';
                    if (!email) {
                        if (!silent) {
                            Notiflix.Report.failure('Email Required', 'Enter your email to resend the code.', 'OK');
                        }
                        return;
                    }
                    
                    // Set flag to prevent duplicate calls
                    this.isResending = true;
                    
                    // Show loading state only if not silent
                    if (!silent) {
                        Notiflix.Loading.standard('Sending Code...');
                    }
                    
                    try {
                        const response = await fetch('../../backend/api/resend-verification-code.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email })
                        });
                        
                        const data = await response.json();
                        
                        if (!silent) {
                            Notiflix.Loading.remove();
                        }
                        
                        if (data.success || response.ok) {
                            if (!silent) {
                                Notiflix.Report.success('Code Sent!', 'A new 6-digit verification code has been sent to your email. Please check your inbox.', 'OK');
                            } else {
                                // Show a subtle toast notification when automatically resending
                                Notiflix.Notify.info('Verification Code Sent - A new verification code has been sent to your email.', {
                                    position: 'right-top',
                                    timeout: 3000,
                                    clickToClose: true
                                });
                            }
                            // Reset timer and code input
                            if (typeof startCountdown === 'function') {
                                startCountdown();
                            }
                            this.codeInput = '';
                            const codeInput = document.getElementById('verify-code-input');
                            if (codeInput) {
                                codeInput.value = '';
                                codeInput.focus();
                            }
                        } else {
                            if (!silent) {
                                Notiflix.Report.failure('Failed to Send Code', data.error || 'Could not resend code. Please try again.', 'OK');
                            } else {
                                console.error('Failed to resend code:', data.error);
                            }
                        }
                    } catch (e) {
                        if (!silent) {
                            Notiflix.Loading.remove();
                            Notiflix.Report.failure('Network Error', 'Could not connect to server. Please check your connection and try again.', 'OK');
                        }
                        console.error('Resend code error:', e);
                    } finally {
                        // Reset flag after operation completes
                        this.isResending = false;
                    }
                }
            }
        }
    </script>
    <script>
        // Simple countdown helper (5 minutes)
        function startCountdown() {
            const el = document.getElementById('verify-timer');
            let remaining = 5 * 60; // seconds
            if (!el) return;
            clearInterval(window._verifyTimer);
            const tick = () => {
                const m = String(Math.floor(remaining / 60)).padStart(2, '0');
                const s = String(remaining % 60).padStart(2, '0');
                el.textContent = `${m}:${s}`;
                remaining -= 1;
                if (remaining < 0) {
                    clearInterval(window._verifyTimer);
                    el.textContent = 'Expired';
                }
            };
            tick();
            window._verifyTimer = setInterval(tick, 1000);
        }
        document.addEventListener('DOMContentLoaded', () => {
            startCountdown();
        });
    </script>
    <script>
        // Favicon loading functions (same as index.php)
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

        // Load website logo function
        async function loadWebsiteLogo() {
            // Fetch admin's website logo for favicon
            try {
                const res = await fetch('../../backend/api/get-website-logo.php');
                const data = await res.json();
                if (data.success && data.logo_url) {
                    let logoUrl = data.logo_url;
                    // Normalize for frontend/verification/ (one level deep from frontend/)
                    if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                        if (logoUrl.startsWith('../backend/')) {
                            // Path is relative to frontend/, need to add ../ for verification/
                            logoUrl = '../' + logoUrl; // ../backend/... becomes ../../backend/...
                        } else if (logoUrl.startsWith('backend/')) {
                            logoUrl = '../../' + logoUrl;
                        } else if (!logoUrl.startsWith('../') && !logoUrl.startsWith('/')) {
                            logoUrl = '../../backend/uploads/logos/' + logoUrl.split('/').pop();
                        }
                    }
                    updateFavicon(logoUrl);
                    console.log('Verify Email page: Favicon updated to:', logoUrl);
                }
            } catch (e) {
                console.error('Error loading website logo:', e);
            }
        }

        // Load logo on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadWebsiteLogo();
        });
    </script>
</body>
</html>
