// PWA Service Worker Registration
// This file registers the service worker for the ERepair PWA

(function() {
    'use strict';

    // Check if app is already installed
    function isAppInstalled() {
        // Check if running in standalone mode (installed PWA)
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true ||
            document.referrer.includes('android-app://')) {
            return true;
        }
        return false;
    }
    
    // Update install buttons based on installation status (global function)
    window.updateInstallButtonState = function() {
        const installButtons = document.querySelectorAll('.pwa-install-button');
        const installed = isAppInstalled();
        
        installButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            if (installed) {
                // App is installed - show "Installed" state
                btn.classList.add('installed');
                btn.title = 'App is installed';
                btn.setAttribute('aria-label', 'App is installed');
                if (icon) {
                    icon.className = 'fas fa-check-circle';
                }
                if (span) {
                    span.textContent = 'Installed';
                }
                // Make button open the app if clicked
                btn.onclick = function() {
                    // Try to open in standalone mode or reload
                    if (window.location.href.includes('?')) {
                        window.location.href = window.location.href.split('?')[0];
                    } else {
                        window.location.reload();
                    }
                };
            } else {
                // App not installed - show install state
                btn.classList.remove('installed');
                btn.title = 'Install ERepair App';
                btn.setAttribute('aria-label', 'Install ERepair App');
                if (icon) {
                    icon.className = 'fas fa-download';
                }
                if (span) {
                    span.textContent = 'Install App';
                }
                // Set install function (use global handler if available, otherwise set directly)
                if (typeof window.handlePWAInstallClick === 'function') {
                    btn.onclick = window.handlePWAInstallClick;
                } else {
                    btn.onclick = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof installPWA === 'function') {
                            installPWA();
                        } else {
                            // Fallback: show instructions
                            alert('Installation is not available yet. Please use your browser\'s menu to "Add to Home Screen" or look for the install icon in your browser\'s address bar.');
                        }
                    };
                }
            }
        });
    };
    
    // Update button state on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateInstallButtonState);
    } else {
        updateInstallButtonState();
    }
    
    // Handle beforeinstallprompt event EARLY (before service worker registration)
    // This event can fire even before service worker is registered
    window.deferredPrompt = null;
    
    window.addEventListener('beforeinstallprompt', function(e) {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later
        window.deferredPrompt = e;
        console.log('[PWA] Install prompt available - deferredPrompt set');
        
        // Update button state (will show install option if not already installed)
        if (typeof updateInstallButtonState === 'function') {
            updateInstallButtonState();
        }
    });
    
    // Handle app installed event
    window.addEventListener('appinstalled', function() {
        console.log('[PWA] App installed successfully');
        window.deferredPrompt = null;
        // Update button to show installed state
        if (typeof updateInstallButtonState === 'function') {
            updateInstallButtonState();
        }
    });
    
    // Check if service workers are supported
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            // Calculate service worker path based on current page location
            // For: /repair-booking-platform/frontend/auth/index.php
            // Service worker should be at: /repair-booking-platform/frontend/service-worker.js
            const currentPath = window.location.pathname;
            let swPath = null;
            
            // Method 1: Extract from current page path
            if (currentPath.includes('/frontend/')) {
                const basePath = currentPath.substring(0, currentPath.indexOf('/frontend/') + '/frontend/'.length);
                swPath = basePath + 'service-worker.js';
            }
            
            // Method 2: Try to find from script tag location
            if (!swPath) {
                const scripts = document.getElementsByTagName('script');
                for (let i = 0; i < scripts.length; i++) {
                    if (scripts[i].src && scripts[i].src.includes('pwa-register.js')) {
                        try {
                            const scriptUrl = new URL(scripts[i].src);
                            const scriptPath = scriptUrl.pathname;
                            const match = scriptPath.match(/(.*\/frontend\/)/);
                            if (match) {
                                swPath = match[1] + 'service-worker.js';
                                break;
                            }
                        } catch (e) {
                            console.warn('[PWA] Could not parse script URL:', e);
                        }
                    }
                }
            }
            
            // Method 3: Fallback to default path
            if (!swPath) {
                swPath = '/repair-booking-platform/frontend/service-worker.js';
            }
            
            console.log('[PWA] Current page:', currentPath);
            console.log('[PWA] Registering service worker at:', swPath);
            
            // Register service worker with no cache option
            navigator.serviceWorker.register(swPath, { updateViaCache: 'none' })
                .then(function(registration) {
                    console.log('[PWA] Service Worker registered successfully:', registration.scope);
                    
                    // Force update check immediately
                    registration.update();
                    
                    // Check for updates periodically
                    setInterval(function() {
                        registration.update();
                    }, 30000); // Check every 30 seconds

                    // Handle updates
                    registration.addEventListener('updatefound', function() {
                        const newWorker = registration.installing;
                        console.log('[PWA] New service worker version found, updating...');
                        
                        newWorker.addEventListener('statechange', function() {
                            if (newWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                    // New service worker available, show update notification
                                    console.log('[PWA] New version installed, prompting user...');
                                    if (typeof Notiflix !== 'undefined') {
                                        Notiflix.Confirm.show(
                                            'Update Available!',
                                            'A new version of ERepair is available. Would you like to reload now?',
                                            'Reload Now',
                                            'Later',
                                            () => {
                                                window.location.reload();
                                            },
                                            () => {
                                                // User chose later
                                            }
                                        );
                                    } else {
                                        if (confirm('A new version of ERepair is available. Reload to update?')) {
                                            window.location.reload();
                                        }
                                    }
                                } else {
                                    // First time installation
                                    console.log('[PWA] Service Worker installed for the first time');
                                }
                            }
                        });
                    });
                })
                .catch(function(error) {
                    console.error('[PWA] Service Worker registration failed:', error);
                });

            // Handle service worker messages
            navigator.serviceWorker.addEventListener('message', function(event) {
                console.log('[PWA] Message from service worker:', event.data);
                
                if (event.data && event.data.type === 'SW_UPDATED') {
                    console.log('[PWA] Service Worker updated to version:', event.data.version);
                    // Optionally show a notification
                    if (typeof Notiflix !== 'undefined' && event.data.message) {
                        Notiflix.Notify.success(event.data.message, {
                            position: 'right-top',
                            timeout: 2000,
                            clickToClose: true
                        });
                    }
                }
            });
        });

        // Global function to check for updates
        window.checkForUpdates = function() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistration().then(function(registration) {
                    if (registration) {
                        console.log('[PWA] Checking for updates...');
                        registration.update().then(function() {
                            console.log('[PWA] Update check complete');
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.info('Checking for updates... If a new version is available, you will be notified.', {
                                    position: 'right-top',
                                    timeout: 2000,
                                    clickToClose: true
                                });
                            }
                        }).catch(function(error) {
                            console.error('[PWA] Update check failed:', error);
                        });
                    }
                });
            }
        };
        
        // Global function to trigger PWA install
        window.installPWA = function() {
            if (window.deferredPrompt) {
                // Show the install prompt
                window.deferredPrompt.prompt();
                // Wait for the user to respond
                window.deferredPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('[PWA] User accepted the install prompt');
                    } else {
                        console.log('[PWA] User dismissed the install prompt');
                    }
                    window.deferredPrompt = null;
                    // Update button state (will show installed if app was installed)
                    updateInstallButtonState();
                });
            } else {
                // No install prompt available - show manual instructions
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
        };
        
        // Note: beforeinstallprompt and appinstalled listeners are now attached earlier
        // (before service worker registration) to ensure they're always available
    } else {
        console.warn('[PWA] Service Workers are not supported in this browser');
    }
})();

