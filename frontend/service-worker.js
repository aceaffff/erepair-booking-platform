// ERepair Service Worker
// Version 1.4.0 - Updated logo container to round with proper image fitting
const CACHE_NAME = 'erepair-v1.4.0';
const RUNTIME_CACHE = 'erepair-runtime-v1.4.0';

// Assets to cache on install
// Note: Using absolute paths that work from the root
// Note: We don't cache index.php in static assets - it's always fetched fresh
const STATIC_ASSETS = [
  '/repair-booking-platform/frontend/manifest.json'
];

// Additional assets will be cached on-demand (runtime cache)

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_ASSETS.map(url => {
          try {
            return new Request(url, { mode: 'no-cors' });
          } catch (e) {
            return url;
          }
        })).catch((err) => {
          console.log('[Service Worker] Cache addAll failed:', err);
          // Continue even if some assets fail to cache
          return Promise.resolve();
        });
      })
  );
  self.skipWaiting(); // Activate immediately
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating new version...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Delete all old caches (anything that doesn't match current version)
          if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      ).then(() => {
        // Notify all clients about the update
        return self.clients.matchAll().then((clients) => {
          clients.forEach((client) => {
            client.postMessage({
              type: 'SW_UPDATED',
              version: '1.4.0',
              message: 'Service Worker updated successfully'
            });
          });
        });
      });
    })
  );
  return self.clients.claim(); // Take control of all pages immediately
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // For index.php - always fetch fresh, NEVER cache
  if (url.pathname.includes('index.php')) {
    // Always fetch from network, bypass all caches, never store
    event.respondWith(
      fetch(request, { 
        cache: 'no-store',
        headers: {
          'Cache-Control': 'no-cache, no-store, must-revalidate',
          'Pragma': 'no-cache'
        }
      })
        .then((response) => {
          // Don't cache index.php - always get fresh version
          return response;
        })
        .catch(() => {
          // If network fails completely, try cache as last resort only
          return caches.match(request).then(cached => {
            if (cached) return cached;
            // Return a basic error response
            return new Response('Offline - Please check your connection', {
              status: 503,
              headers: { 'Content-Type': 'text/plain' }
            });
          });
        })
    );
    return;
  }

  // For API requests - always use network-first (always fresh)
  if (url.pathname.includes('/backend/api/') || 
      url.pathname.includes('/api/')) {
    // Skip caching for non-GET requests (POST, PUT, DELETE, etc.)
    if (request.method !== 'GET') {
      event.respondWith(fetch(request));
      return;
    }
    
    // Network-first strategy - always try network first
    event.respondWith(
      fetch(request, { cache: 'no-store' })
        .then((response) => {
          // Cache successful GET responses for offline use
          if (response.status === 200 && request.method === 'GET') {
            const responseClone = response.clone();
            caches.open(RUNTIME_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // If network fails, try cache (only for GET requests)
          if (request.method === 'GET') {
            return caches.match(request);
          }
          return new Response('Network error', { status: 503 });
        })
    );
    return;
  }

  // For other PHP files, use network-first strategy
  if (url.pathname.endsWith('.php')) {
    // Skip caching for non-GET requests (POST, PUT, DELETE, etc.)
    if (request.method !== 'GET') {
      event.respondWith(fetch(request));
      return;
    }
    
    event.respondWith(
      fetch(request, { cache: 'no-store' })
        .then((response) => {
          // Cache successful GET responses only
          if (response.status === 200 && request.method === 'GET') {
            const responseClone = response.clone();
            caches.open(RUNTIME_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // If network fails, try cache (only for GET requests)
          if (request.method === 'GET') {
            return caches.match(request);
          }
          return new Response('Network error', { status: 503 });
        })
    );
    return;
  }

  // Handle root path or 404 - redirect to index.php
  if (request.mode === 'navigate' && (
    url.pathname === '/' || 
    url.pathname.endsWith('/frontend/') ||
    url.pathname.endsWith('/frontend') ||
    !url.pathname.includes('.') ||
    url.pathname.includes('/frontend/') && !url.pathname.match(/\.(php|html|css|js|png|jpg|jpeg|gif|svg|ico|json)$/i)
  )) {
    // If it's a navigation request to root or invalid path, try to serve index.php
    const indexPath = url.pathname.includes('/frontend/') 
      ? url.pathname.replace(/\/frontend\/.*$/, '/frontend/auth/index.php')
      : '/repair-booking-platform/frontend/auth/index.php';
    
    event.respondWith(
      fetch(new Request(indexPath, { method: 'GET', headers: request.headers }))
        .then((response) => {
          if (response.status === 200) {
            return response;
          }
          // If fetch fails, try cache
          return caches.match(indexPath).then((cached) => {
            if (cached) return cached;
            // Last resort: try to find any cached index.php
            return caches.match('/repair-booking-platform/frontend/auth/index.php');
          });
        })
        .catch(() => {
          // Try cache as fallback
          return caches.match(indexPath).then((cached) => {
            if (cached) return cached;
            return caches.match('/repair-booking-platform/frontend/auth/index.php');
          });
        })
    );
    return;
  }

  // For static assets, use cache-first strategy
  // Skip caching for non-GET requests
  if (request.method !== 'GET') {
    event.respondWith(fetch(request));
    return;
  }
  
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }

        // Not in cache, fetch from network
        return fetch(request)
          .then((response) => {
            // Don't cache non-successful responses or non-GET requests
            if (!response || response.status !== 200 || response.type !== 'basic' || request.method !== 'GET') {
              return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            caches.open(RUNTIME_CACHE)
              .then((cache) => {
                cache.put(request, responseToCache);
              });

            return response;
          })
          .catch(() => {
            // If both cache and network fail, return offline page
            if (request.headers.get('accept') && request.headers.get('accept').includes('text/html')) {
              // Try multiple fallback paths
              return caches.match('/repair-booking-platform/frontend/auth/index.php')
                .then((cached) => {
                  if (cached) return cached;
                  // Try relative path
                  const relativePath = url.pathname.substring(0, url.pathname.lastIndexOf('/')) + '/auth/index.php';
                  return caches.match(relativePath);
                });
            }
          });
      })
  );
});

// Handle push notifications (for future use)
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'ERepair Notification';
  const options = {
    body: data.body || 'You have a new notification',
    icon: '/repair-booking-platform/frontend/assets/icons/icon-192x192.png',
    badge: '/repair-booking-platform/frontend/assets/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    tag: 'erepair-notification',
    requireInteraction: false
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  event.waitUntil(
    clients.openWindow('/repair-booking-platform/frontend/auth/index.php')
  );
});

// Background sync (for future use)
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    event.waitUntil(
      // Perform background sync tasks here
      Promise.resolve()
    );
  }
});

