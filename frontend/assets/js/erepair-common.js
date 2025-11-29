// ERepair Common JavaScript Functions

// Particle System
function createParticles() {
    const particleContainer = document.querySelector('.particles');
    if (particleContainer) {
        // Clear existing particles
        particleContainer.innerHTML = '';
        
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 5) + 's';
            particleContainer.appendChild(particle);
        }
    }
}

// Advanced Scroll Effects
function initScrollEffects() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Support existing animated cards
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                // Support landing fade-in utility
                if (entry.target.classList.contains('fade-in')) {
                    entry.target.classList.add('visible');
                }
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.card-3d, .step-advanced, .step-advanced-light').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(50px)';
        el.style.transition = 'all 0.8s ease';
        observer.observe(el);
    });

    document.querySelectorAll('.fade-in').forEach(el => {
        // Only set inline styles if not already controlled by CSS
        if (!el.style.transition) {
            el.style.transition = 'all 0.6s ease';
        }
        observer.observe(el);
    });
}

// Smooth Navigation for dark glass nav (landing style)
function initLandingNavigation() {
    const nav = document.querySelector('.nav-professional');
    if (!nav) return;
    let lastScroll = 0;
    const applyStyles = (scrolled) => {
        if (scrolled) {
            nav.style.background = 'rgba(6, 11, 23, 1)';
            nav.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.5)';
        } else {
            nav.style.background = 'rgba(6, 11, 23, 0.98)';
            nav.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.4)';
        }
    };
    applyStyles(window.pageYOffset > 100);
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        applyStyles(currentScroll > 100);
        lastScroll = currentScroll;
    });
}

// Authentication Helper Functions
function getAuthToken() {
    return localStorage.getItem('auth_token');
}

function getUserData() {
    const userData = localStorage.getItem('user_data');
    return userData ? JSON.parse(userData) : null;
}

function setAuthData(token, userData) {
    localStorage.setItem('auth_token', token);
    localStorage.setItem('user_data', JSON.stringify(userData));
    
    // Also set cookie for PHP fallback
    try {
        const expires = new Date(Date.now() + 24*60*60*1000).toUTCString();
        document.cookie = `auth_token=${token}; expires=${expires}; path=/`;
    } catch (e) {
        console.warn('Could not set auth cookie:', e);
    }
}

function clearAuthData() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    
    // Clear cookie
    try {
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    } catch (e) {
        console.warn('Could not clear auth cookie:', e);
    }
}

function redirectToLogin() {
    clearAuthData();
    window.location.href = '../auth/index.php';
}

// API Helper Functions
async function apiRequest(url, options = {}) {
    const token = getAuthToken();
    
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` })
        }
    };
    
    const finalOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, finalOptions);
        
        if (response.status === 401) {
            redirectToLogin();
            return null;
        }
        
        return response;
    } catch (error) {
        console.error('API request failed:', error);
        throw error;
    }
}

// Enhanced API response handler
async function handleApiResponse(response, showSuccess = true, showError = true) {
    try {
        // Check if response is ok
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP error response:', errorText);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Try to parse JSON
        const data = await response.json().catch(() => {
            throw new Error('Invalid JSON response from server');
        });
        
        // Check for API-level errors
        if (data.error) {
            const errorMessage = data.message || data.detail || 'An error occurred';
            if (showError) {
                // Use the global showError function if available, otherwise use console
                if (typeof showError === 'function') {
                    showError(errorMessage);
                } else if (showError === true && typeof window.showError === 'function') {
                    window.showError(errorMessage);
                } else {
                    console.error('API Error:', errorMessage);
                }
            }
            throw new Error(errorMessage);
        }
        
        // Success response
        if (data.success && showSuccess && data.message) {
            if (typeof showSuccess === 'function') {
                showSuccess(data.message);
            } else if (showSuccess === true && typeof window.showSuccess === 'function') {
                window.showSuccess(data.message);
            } else {
                console.log('API Success:', data.message);
            }
        }
        
        return data;
    } catch (error) {
        console.error('API response handling error:', error);
        if (showError) {
            if (typeof showError === 'function') {
                showError(error.message || 'An unexpected error occurred');
            } else if (showError === true && typeof window.showError === 'function') {
                window.showError(error.message || 'An unexpected error occurred');
            } else {
                console.error('API Error:', error.message || 'An unexpected error occurred');
            }
        }
        throw error;
    }
}

// User Role Redirect
function redirectUser(role) {
    const token = getAuthToken() || '';
    const base = `${window.location.origin}/ERepair/repair-booking-platform/frontend/`;
    
    const dashboards = {
        'customer': `${base}customer_dashboard.php?token=${encodeURIComponent(token)}`,
        'shop_owner': `${base}shop_dashboard.php?token=${encodeURIComponent(token)}`,
        'admin': `${base}admin_dashboard.php?token=${encodeURIComponent(token)}`,
        'technician': `${base}technician_dashboard.php?token=${encodeURIComponent(token)}`
    };
    
    const dashboard = dashboards[role] || dashboards['customer'];
    window.location.replace(dashboard);
}

// Logout Function
async function logout() {
    Notiflix.Confirm.show(
        'Logout',
        'Are you sure you want to logout?',
        'Yes, logout',
        'Cancel',
        async () => {
        try {
            const token = getAuthToken();
            if (token) {
                await apiRequest('../backend/api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
            }
        } catch (error) {
            console.error('Logout API call failed:', error);
        }
        
        clearAuthData();
        window.location.href = '../auth/index.php';
        },
        () => {
            // User cancelled
    }
    );
}

// Form Validation Helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/\s/g, ''));
}

function validatePassword(password) {
    return password.length >= 8;
}

// File Upload Helpers
function validateFile(file, maxSize = 5 * 1024 * 1024, allowedTypes = ['.jpg', '.jpeg', '.png', '.pdf']) {
    if (!file) return { valid: false, error: 'No file selected' };
    
    if (file.size > maxSize) {
        return { valid: false, error: `File size must be less than ${maxSize / (1024 * 1024)}MB` };
    }
    
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    if (!allowedTypes.includes(fileExtension)) {
        return { valid: false, error: `File type not allowed. Allowed types: ${allowedTypes.join(', ')}` };
    }
    
    return { valid: true };
}

// Notification Helpers
function showSuccess(message, title = 'Success') {
    Notiflix.Report.success(title, message, 'OK');
}

function showError(message, title = 'Error') {
    Notiflix.Report.failure(title, message, 'OK');
}

// Make functions globally available
window.showSuccess = showSuccess;
window.showError = showError;

function showLoading(message = 'Loading...') {
    Notiflix.Loading.standard(message);
}

function hideLoading() {
    Notiflix.Loading.remove();
}

// Make hideLoading globally available
window.hideLoading = hideLoading;

// Date/Time Helpers
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Status Helpers
function getStatusColor(status) {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function getStatusIcon(status) {
    const icons = {
        'pending': 'fas fa-clock',
        'approved': 'fas fa-check-circle',
        'rejected': 'fas fa-times-circle',
        'in_progress': 'fas fa-spinner',
        'completed': 'fas fa-check-circle',
        'cancelled': 'fas fa-times-circle'
    };
    return icons[status] || 'fas fa-question-circle';
}

// Initialize common functionality
function initERepair() {
    // Initialize particles if container exists
    createParticles();
    
    // Initialize scroll effects
    initScrollEffects();
    // Initialize landing-style nav if present
    initLandingNavigation();
    
    // Set light mode as default
    document.body.style.backgroundColor = '#f8fafc';
    document.body.classList.remove('bg-black');
    
    // Add loading states to buttons (only for non-Alpine.js forms)
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        // Skip if button is inside an Alpine.js component
        if (button.closest('[x-data]')) {
            return;
        }
        
        button.addEventListener('click', function() {
            if (this.form && this.form.checkValidity()) {
                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
                
                // Re-enable after 5 seconds as fallback
                setTimeout(() => {
                    this.disabled = false;
                    this.innerHTML = originalText;
                }, 5000);
            }
        });
    });
}

// Export functions for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        createParticles,
        initScrollEffects,
        getAuthToken,
        getUserData,
        setAuthData,
        clearAuthData,
        redirectToLogin,
        apiRequest,
        redirectUser,
        logout,
        validateEmail,
        validatePhone,
        validatePassword,
        validateFile,
        showSuccess,
        showError,
        showLoading,
        formatDate,
        formatDateTime,
        getStatusColor,
        getStatusIcon,
        initERepair
    };
}

// Global error handling for debugging
window.addEventListener('unhandledrejection', e => {
    console.error("Unhandled Promise Rejection:", e.reason);
    // Don't prevent default to allow normal error handling
});

window.addEventListener('error', e => {
    console.error("Global JavaScript Error:", e.message, "at", e.filename, "line", e.lineno);
    // Don't prevent default to allow normal error handling
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initERepair);
