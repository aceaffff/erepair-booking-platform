/**
 * ERepair Custom SweetAlert2 Configuration
 * Customized dialogs with unique branding and animations
 */

// Custom SweetAlert2 configuration
const ERepairSwal = {
    // Default configuration
    config: {
        customClass: {
            container: 'erepair-swal-container',
            popup: 'erepair-swal-popup',
            title: 'erepair-swal-title',
            htmlContainer: 'erepair-swal-content',
            confirmButton: 'erepair-swal-confirm',
            cancelButton: 'erepair-swal-cancel',
            denyButton: 'erepair-swal-deny',
            input: 'erepair-swal-input',
            validationMessage: 'erepair-swal-validation'
        },
        buttonsStyling: false,
        showClass: {
            popup: 'animate__animated animate__zoomIn animate__faster',
            backdrop: 'animate__animated animate__fadeIn animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__zoomOut animate__faster',
            backdrop: 'animate__animated animate__fadeOut animate__faster'
        },
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#6b7280',
        denyButtonColor: '#ef4444',
        allowOutsideClick: false,
        allowEscapeKey: true,
        backdrop: true,
        focusConfirm: true,
        returnFocus: true
    },

    // Success dialog with custom styling
    success: function(title, text = '', options = {}) {
        return Swal.fire({
            ...this.config,
            icon: 'success',
            iconColor: '#10b981',
            title: title,
            text: text,
            confirmButtonText: options.confirmButtonText || 'OK',
            confirmButtonClass: 'erepair-btn-success',
            timer: options.timer || null,
            timerProgressBar: options.timerProgressBar !== false,
            ...options
        });
    },

    // Error dialog with custom styling
    error: function(title, text = '', options = {}) {
        return Swal.fire({
            ...this.config,
            icon: 'error',
            iconColor: '#ef4444',
            title: title,
            text: text,
            confirmButtonText: options.confirmButtonText || 'OK',
            confirmButtonClass: 'erepair-btn-error',
            ...options
        });
    },

    // Warning dialog with custom styling
    warning: function(title, text = '', options = {}) {
        return Swal.fire({
            ...this.config,
            icon: 'warning',
            iconColor: '#f59e0b',
            title: title,
            text: text,
            confirmButtonText: options.confirmButtonText || 'OK',
            confirmButtonClass: 'erepair-btn-warning',
            ...options
        });
    },

    // Info dialog with custom styling
    info: function(title, text = '', options = {}) {
        return Swal.fire({
            ...this.config,
            icon: 'info',
            iconColor: '#3b82f6',
            title: title,
            text: text,
            confirmButtonText: options.confirmButtonText || 'OK',
            confirmButtonClass: 'erepair-btn-info',
            ...options
        });
    },

    // Question/Confirm dialog with custom styling
    question: function(title, text = '', options = {}) {
        return Swal.fire({
            ...this.config,
            icon: 'question',
            iconColor: '#6366f1',
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Yes',
            cancelButtonText: options.cancelButtonText || 'No',
            confirmButtonClass: 'erepair-btn-primary',
            cancelButtonClass: 'erepair-btn-secondary',
            reverseButtons: true,
            ...options
        });
    },

    // Toast notification with custom styling
    toast: function(message, icon = 'success', options = {}) {
        return Swal.fire({
            ...this.config,
            toast: true,
            position: options.position || 'top-end',
            icon: icon,
            title: message,
            showConfirmButton: false,
            timer: options.timer || 3000,
            timerProgressBar: options.timerProgressBar !== false,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            },
            customClass: {
                popup: 'erepair-swal-toast',
                icon: `erepair-swal-icon-${icon}`
            },
            ...options
        });
    },

    // Loading dialog with custom styling
    loading: function(title = 'Loading...', text = 'Please wait', options = {}) {
        return Swal.fire({
            ...this.config,
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
            customClass: {
                popup: 'erepair-swal-loading',
                loader: 'erepair-swal-loader'
            },
            ...options
        });
    },

    // Custom dialog with full control
    fire: function(options) {
        return Swal.fire({
            ...this.config,
            ...options
        });
    },

    // Close current dialog
    close: function() {
        return Swal.close();
    },

    // Show loading spinner
    showLoading: function() {
        return Swal.showLoading();
    },

    // Stop timer
    stopTimer: function() {
        return Swal.stopTimer();
    },

    // Resume timer
    resumeTimer: function() {
        return Swal.resumeTimer();
    }
};

// Make it globally available
window.ERepairSwal = ERepairSwal;

// Also create a shorthand alias
window.ESwal = ERepairSwal;

