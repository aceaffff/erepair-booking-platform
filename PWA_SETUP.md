# ERepair PWA (Progressive Web App) Setup

Your ERepair platform is now configured as a Progressive Web App (PWA), allowing users to install it on their devices and use it offline.

## What's Been Added

### 1. **Manifest File** (`frontend/manifest.json`)
- Defines app name, icons, theme colors, and display mode
- Configures app shortcuts for quick access
- Sets up standalone display mode for app-like experience

### 2. **Service Worker** (`frontend/service-worker.js`)
- Enables offline functionality
- Caches static assets for faster loading
- Handles push notifications (ready for future implementation)
- Implements cache-first strategy for static assets
- Network-first strategy for dynamic PHP content

### 3. **PWA Registration Script** (`frontend/assets/js/pwa-register.js`)
- Automatically registers the service worker
- Handles app installation prompts
- Manages service worker updates
- Provides install button functionality

### 4. **Dynamic Icon Generator** (`frontend/assets/icons/icon-generator.php`)
- Generates PWA icons dynamically from admin's website logo
- Creates icons in all required sizes (72x72 to 512x512)
- Falls back to default ERepair branding if no logo is set
- Serves icons on-demand with proper caching

### 5. **Updated Dashboard Files**
All dashboard files now include:
- PWA meta tags for mobile app experience
- Apple touch icons for iOS devices
- Manifest link
- Service worker registration

## Features

### ✅ Offline Support
- Static assets are cached for offline access
- Previously visited pages work offline
- Service worker automatically updates cached content

### ✅ Installable
- Users can install the app on their devices
- Works on Android, iOS, and desktop browsers
- Appears in app launcher/home screen

### ✅ Fast Loading
- Assets are cached for instant loading
- Reduced server requests
- Improved performance

### ✅ App-like Experience
- Standalone display mode (no browser UI)
- Custom theme colors
- App shortcuts for quick actions

## How to Use

### For Users:

1. **Install the App:**
   - On Android: Visit the site, tap the browser menu, select "Add to Home Screen"
   - On iOS: Tap the Share button, select "Add to Home Screen"
   - On Desktop: Look for the install icon in the address bar

2. **Use Offline:**
   - Once installed, previously visited pages work offline
   - Static content loads from cache
   - Dynamic content requires internet connection

### For Developers:

1. **Generate Icons (Optional):**
   ```bash
   # Run the icon generator to create static icon files
   php frontend/assets/icons/generate-icons.php
   ```
   This creates static PNG files in `frontend/assets/icons/` directory.

2. **Update Service Worker:**
   - Edit `frontend/service-worker.js`
   - Update `CACHE_NAME` version when making changes
   - Add new assets to `STATIC_ASSETS` array if needed

3. **Customize Manifest:**
   - Edit `frontend/manifest.json`
   - Update app name, description, colors
   - Add/modify shortcuts
   - Update start URL if needed

## Testing

### Test PWA Installation:
1. Open the site in Chrome/Edge (desktop or mobile)
2. Check browser console for service worker registration messages
3. Look for install prompt or install icon in address bar
4. Test offline functionality by going offline and reloading

### Test Service Worker:
1. Open DevTools → Application → Service Workers
2. Verify service worker is registered and active
3. Check Cache Storage for cached assets
4. Test offline mode using Network tab throttling

### Test Icons:
1. Visit: `http://localhost/repair-booking-platform/frontend/assets/icons/icon-generator.php?size=192`
2. Should display a 192x192 icon
3. Test different sizes: 72, 96, 128, 144, 152, 192, 384, 512

## Browser Support

- ✅ Chrome/Edge (Android & Desktop)
- ✅ Safari (iOS 11.3+)
- ✅ Firefox (Android & Desktop)
- ✅ Samsung Internet
- ⚠️ Safari (Desktop) - Limited support

## Troubleshooting

### Service Worker Not Registering:
- Ensure site is served over HTTPS (or localhost)
- Check browser console for errors
- Verify `service-worker.js` is accessible

### Icons Not Showing:
- Check `icon-generator.php` is accessible
- Verify admin logo exists in database
- Check PHP GD extension is enabled

### App Not Installable:
- Verify manifest.json is accessible
- Check all required icons are available
- Ensure site is served over HTTPS (production)

## Next Steps (Optional Enhancements)

1. **Push Notifications:**
   - Service worker is ready for push notifications
   - Implement notification API endpoints
   - Add subscription management

2. **Background Sync:**
   - Implement background sync for offline actions
   - Queue actions when offline
   - Sync when connection restored

3. **App Updates:**
   - Add update notification to users
   - Implement update mechanism
   - Show changelog on updates

## Files Created/Modified

### New Files:
- `frontend/manifest.json`
- `frontend/service-worker.js`
- `frontend/assets/js/pwa-register.js`
- `frontend/assets/icons/icon-generator.php`
- `frontend/assets/icons/generate-icons.php`

### Modified Files:
- `frontend/customer/customer_dashboard.php`
- `frontend/admin/admin_dashboard.php`
- `frontend/technician/technician_dashboard.php`
- `frontend/shop/shop_dashboard.php`
- `frontend/auth/index.php`

## Notes

- Icons are generated dynamically from admin's website logo
- Service worker caches assets automatically
- Updates are handled automatically
- All dashboards support PWA features
- Works seamlessly with existing authentication system

