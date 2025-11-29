/**
 * ERepair Custom Notiflix Configuration
 * Customized notifications with unique branding
 * Can be used alongside SweetAlert2 for gradual migration
 */

// Wait for Notiflix to be available
(function() {
    'use strict';
    
    // Check if Notiflix is loaded
    if (typeof Notiflix === 'undefined') {
        console.warn('Notiflix library not loaded. Please include Notiflix before this script.');
        return;
    }

    // Custom Notiflix configuration
    Notiflix.Notify.init({
        width: '350px',
        position: 'right-top',
        distance: '20px',
        opacity: 1,
        borderRadius: '16px',
        rtl: false,
        timeout: 4000,
        messageMaxLength: 500,
        backOverlay: false,
        backOverlayColor: 'rgba(11, 18, 32, 0.7)',
        plainText: true,
        showOnlyTheLastOne: false,
        clickToClose: true,
        pauseOnHover: true,
        
        // Custom CSS classes
        cssAnimationStyle: 'zoom',
        cssAnimationDuration: 400,
        
        // Font settings
        fontFamily: 'Space Grotesk, sans-serif',
        fontSize: '15px',
        useIcon: true,
        useFontAwesome: true,
        fontAwesomeIconStyle: 'basic',
        fontAwesomeIconSize: '20px',
        
        // Success notification - Solid (No Glassmorphism)
        success: {
            background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            textColor: '#ffffff',
            childClassName: 'erepair-notiflix-success',
            notiflixIconColor: '#ffffff',
            fontAwesomeClassName: 'fas fa-check-circle',
            fontAwesomeIconColor: '#ffffff',
            backOverlayColor: 'rgba(16, 185, 129, 0.2)',
        },
        
        // Failure notification - Solid (No Glassmorphism)
        failure: {
            background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            textColor: '#ffffff',
            childClassName: 'erepair-notiflix-failure',
            notiflixIconColor: '#ffffff',
            fontAwesomeClassName: 'fas fa-times-circle',
            fontAwesomeIconColor: '#ffffff',
            backOverlayColor: 'rgba(239, 68, 68, 0.2)',
        },
        
        // Warning notification - Solid (No Glassmorphism)
        warning: {
            background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            textColor: '#ffffff',
            childClassName: 'erepair-notiflix-warning',
            notiflixIconColor: '#ffffff',
            fontAwesomeClassName: 'fas fa-exclamation-triangle',
            fontAwesomeIconColor: '#ffffff',
            backOverlayColor: 'rgba(245, 158, 11, 0.2)',
        },
        
        // Info notification - Solid (No Glassmorphism)
        info: {
            background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
            textColor: '#ffffff',
            childClassName: 'erepair-notiflix-info',
            notiflixIconColor: '#ffffff',
            fontAwesomeClassName: 'fas fa-info-circle',
            fontAwesomeIconColor: '#ffffff',
            backOverlayColor: 'rgba(59, 130, 246, 0.2)',
        },
    });

    // Custom Report configuration - True Glassmorphism
    Notiflix.Report.init({
        className: 'erepair-notiflix-report',
        width: '400px',
        backgroundColor: 'rgba(255, 255, 255, 0.15)',
        borderRadius: '24px',
        rtl: false,
        backOverlay: true,
        backOverlayColor: 'rgba(11, 18, 32, 0.8)',
        fontFamily: 'Space Grotesk, sans-serif',
        svgSize: '80px',
        plainText: false,
        titleFontSize: '20px',
        titleMaxLength: 34,
        messageFontSize: '16px',
        messageMaxLength: 400,
        buttonFontSize: '14px',
        buttonMaxLength: 34,
        cssAnimation: true,
        cssAnimationDuration: 360,
        cssAnimationStyle: 'zoom',
        
        // Success report - Enhanced for glassmorphism
        success: {
            svgColor: '#10b981',
            titleColor: '#111827',
            messageColor: '#374151',
            buttonBackground: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
            buttonColor: '#ffffff',
            backOverlayColor: 'rgba(16, 185, 129, 0.2)',
        },
        
        // Failure report - Enhanced for glassmorphism
        failure: {
            svgColor: '#ef4444',
            titleColor: '#111827',
            messageColor: '#374151',
            buttonBackground: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
            buttonColor: '#ffffff',
            backOverlayColor: 'rgba(239, 68, 68, 0.2)',
        },
        
        // Warning report - Enhanced for glassmorphism
        warning: {
            svgColor: '#f59e0b',
            titleColor: '#111827',
            messageColor: '#374151',
            buttonBackground: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
            buttonColor: '#ffffff',
            backOverlayColor: 'rgba(245, 158, 11, 0.2)',
        },
        
        // Info report - Enhanced for glassmorphism
        info: {
            svgColor: '#3b82f6',
            titleColor: '#111827',
            messageColor: '#374151',
            buttonBackground: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
            buttonColor: '#ffffff',
            backOverlayColor: 'rgba(59, 130, 246, 0.2)',
        },
    });

    // Custom Confirm configuration - True Glassmorphism
    Notiflix.Confirm.init({
        className: 'erepair-notiflix-confirm',
        width: '400px',
        zindex: 4003,
        position: 'center',
        distance: '20px',
        backgroundColor: 'rgba(255, 255, 255, 0.15)',
        borderRadius: '24px',
        backOverlay: true,
        backOverlayColor: 'rgba(11, 18, 32, 0.8)',
        rtl: false,
        fontFamily: 'Space Grotesk, sans-serif',
        cssAnimation: true,
        cssAnimationDuration: 300,
        cssAnimationStyle: 'zoom',
        plainText: true,
        titleColor: '#111827',
        titleFontSize: '20px',
        titleMaxLength: 34,
        messageColor: '#374151',
        messageFontSize: '16px',
        messageMaxLength: 400,
        buttonsFontSize: '15px',
        buttonsMaxLength: 34,
        okButtonColor: '#ffffff',
        okButtonBackground: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
        cancelButtonColor: '#6b7280',
        cancelButtonBackground: '#ffffff',
        cancelButtonBorder: '2px solid #e5e7eb',
    });

    // Custom Loading configuration - Glassmorphism
    Notiflix.Loading.init({
        className: 'erepair-notiflix-loading',
        zindex: 4000,
        backgroundColor: 'rgba(255, 255, 255, 0.1)',
        rtl: false,
        fontFamily: 'Space Grotesk, sans-serif',
        cssAnimation: true,
        cssAnimationDuration: 400,
        clickToClose: false,
        customSvgUrl: null,
        svgSize: '80px',
        svgColor: '#6366f1',
        messageID: 'NotiflixLoadingMessage',
        messageFontSize: '16px',
        messageMaxLength: 34,
        messageColor: '#ffffff',
    });

    // Custom Block configuration - Glassmorphism
    Notiflix.Block.init({
        className: 'erepair-notiflix-block',
        zindex: 1000,
        backgroundColor: 'rgba(255, 255, 255, 0.85)',
        rtl: false,
        fontFamily: 'Space Grotesk, sans-serif',
        cssAnimation: true,
        cssAnimationDuration: 300,
        cssAnimationStyle: 'zoom',
        svgSize: '45px',
        svgColor: '#6366f1',
        messageFontSize: '14px',
        messageMaxLength: 34,
        messageColor: '#1f2937',
    });

    // Create ERepair Notiflix wrapper for easy migration
    window.ERepairNotiflix = {
        // Notify methods (toast notifications)
        success: function(message, options = {}) {
            return Notiflix.Notify.success(message, options);
        },
        
        failure: function(message, options = {}) {
            return Notiflix.Notify.failure(message, options);
        },
        
        warning: function(message, options = {}) {
            return Notiflix.Notify.warning(message, options);
        },
        
        info: function(message, options = {}) {
            return Notiflix.Notify.info(message, options);
        },
        
        // Report methods (full dialogs)
        reportSuccess: function(title, message, buttonText = 'OK', callback = null) {
            return Notiflix.Report.success(title, message, buttonText, callback);
        },
        
        reportFailure: function(title, message, buttonText = 'OK', callback = null) {
            return Notiflix.Report.failure(title, message, buttonText, callback);
        },
        
        reportWarning: function(title, message, buttonText = 'OK', callback = null) {
            return Notiflix.Report.warning(title, message, buttonText, callback);
        },
        
        reportInfo: function(title, message, buttonText = 'OK', callback = null) {
            return Notiflix.Report.info(title, message, buttonText, callback);
        },
        
        // Confirm dialog
        confirm: function(title, message, okText = 'Yes', cancelText = 'No', okCallback = null, cancelCallback = null) {
            return Notiflix.Confirm.show(
                title,
                message,
                okText,
                cancelText,
                okCallback,
                cancelCallback
            );
        },
        
        // Loading
        loading: function(message = 'Loading...', options = {}) {
            return Notiflix.Loading.standard(message, options);
        },
        
        loadingRemove: function(delay = 0) {
            return Notiflix.Loading.remove(delay);
        },
        
        // Block
        block: function(selector, message = 'Loading...', options = {}) {
            return Notiflix.Block.standard(selector, message, options);
        },
        
        blockRemove: function(selector, delay = 0) {
            return Notiflix.Block.remove(selector, delay);
        },
        
        // Direct access to Notiflix
        notify: Notiflix.Notify,
        report: Notiflix.Report,
        confirmModule: Notiflix.Confirm,
        loadingModule: Notiflix.Loading,
        blockModule: Notiflix.Block,
    };

    // Also create shorthand alias
    window.ENotiflix = window.ERepairNotiflix;

    // Apply glassmorphism to all Notiflix elements dynamically
    function applyGlassmorphism() {
        // Use MutationObserver to catch dynamically created Notiflix elements
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const classList = node.classList || [];
                        const classArray = Array.from(classList);
                        
                        // Check if it's a backdrop/overlay element
                        const isBackdrop = classArray.some(cls => {
                            const lower = cls.toLowerCase();
                            return lower.includes('notiflix') && (lower.includes('backdrop') || lower.includes('overlay'));
                        });
                        
                        // Check if it's a Notiflix dialog/notification
                        const isNotiflixElement = classArray.some(cls => cls.toLowerCase().includes('notiflix')) ||
                            classArray.some(cls => cls.startsWith('erepair-notiflix'));
                        
                        // Apply backdrop blur to backdrop elements
                        if (isBackdrop) {
                            node.style.setProperty('backdrop-filter', 'blur(20px) saturate(200%)', 'important');
                            node.style.setProperty('-webkit-backdrop-filter', 'blur(20px) saturate(200%)', 'important');
                            node.style.setProperty('background', 'rgba(11, 18, 32, 0.85)', 'important');
                        }
                        
                        // Apply glassmorphism to dialog elements
                        if (isNotiflixElement && !isBackdrop) {
                            // Ensure glassmorphism styles are applied
                            if (classArray.includes('erepair-notiflix-loading')) {
                                node.style.setProperty('backdrop-filter', 'blur(20px) saturate(180%)', 'important');
                                node.style.setProperty('-webkit-backdrop-filter', 'blur(20px) saturate(180%)', 'important');
                            } else if (classArray.includes('erepair-notiflix-block')) {
                                node.style.setProperty('backdrop-filter', 'blur(12px) saturate(150%)', 'important');
                                node.style.setProperty('-webkit-backdrop-filter', 'blur(12px) saturate(150%)', 'important');
                            }
                        }
                        
                        // Check by style attributes for backdrop (fallback)
                        if (!isBackdrop && !isNotiflixElement && node.style) {
                            const style = node.style.cssText || '';
                            const computedStyle = window.getComputedStyle(node);
                            if (
                                (style.includes('position: fixed') || computedStyle.position === 'fixed') &&
                                (style.includes('z-index') || parseInt(computedStyle.zIndex) > 3000) &&
                                !classArray.some(cls => cls.startsWith('erepair-notiflix'))
                            ) {
                                node.style.setProperty('backdrop-filter', 'blur(20px) saturate(200%)', 'important');
                                node.style.setProperty('-webkit-backdrop-filter', 'blur(20px) saturate(200%)', 'important');
                                node.style.setProperty('background', 'rgba(11, 18, 32, 0.85)', 'important');
                            }
                        }
                    }
                });
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });
        
        // Also apply to existing elements
        setTimeout(function() {
            // Apply to backdrops
            const existingBackdrops = document.querySelectorAll(
                '.notiflix-backdrop, [class*="notiflix-backdrop"], [class*="NotiflixBackdrop"], [class*="notiflix-overlay"], [class*="NotiflixOverlay"]'
            );
            existingBackdrops.forEach(function(backdrop) {
                backdrop.style.setProperty('backdrop-filter', 'blur(20px) saturate(200%)', 'important');
                backdrop.style.setProperty('-webkit-backdrop-filter', 'blur(20px) saturate(200%)', 'important');
                backdrop.style.setProperty('background', 'rgba(11, 18, 32, 0.85)', 'important');
            });
            
            // Apply to Loading dialogs
            const loadingDialogs = document.querySelectorAll('.erepair-notiflix-loading');
            loadingDialogs.forEach(function(dialog) {
                dialog.style.setProperty('backdrop-filter', 'blur(20px) saturate(180%)', 'important');
                dialog.style.setProperty('-webkit-backdrop-filter', 'blur(20px) saturate(180%)', 'important');
            });
            
            // Apply to Block overlays
            const blockOverlays = document.querySelectorAll('.erepair-notiflix-block');
            blockOverlays.forEach(function(block) {
                block.style.setProperty('backdrop-filter', 'blur(12px) saturate(150%)', 'important');
                block.style.setProperty('-webkit-backdrop-filter', 'blur(12px) saturate(150%)', 'important');
            });
        }, 100);
    }
    
    // Apply glassmorphism when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyGlassmorphism);
    } else {
        applyGlassmorphism();
    }

    console.log('ERepair Notiflix configuration loaded successfully!');
})();

