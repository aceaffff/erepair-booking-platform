<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function redirect_to_login() {
    header('Location: ../auth/index.php');
    exit;
}

// Read token from cookie or GET and validate
$token = null;
if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
    $token = InputValidator::validateToken($_COOKIE['auth_token']);
} elseif (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = InputValidator::validateToken(trim($_GET['token']));
    // Persist valid token in cookie for subsequent requests
    if ($token !== null) {
        setcookie('auth_token', $token, time() + 24 * 60 * 60, '/', '', false, true);
    }
}

if ($token === null) {
    redirect_to_login();
}

$shop_id = filter_var($_GET['shop_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$shop_id) {
    error_log("Invalid shop_id in shop_homepage.php: " . ($_GET['shop_id'] ?? 'null'));
    header('Location: customer_dashboard.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Service Unavailable</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">';
    echo '<div class="container py-5"><div class="alert alert-danger"><strong>Database connection failed.</strong> Please ensure MySQL is running and configured.</div>';
    echo '<ul class="small text-muted mb-0"><li>Start MySQL in XAMPP (MySQL → Start)</li><li>Verify credentials in backend/config/database.php</li><li>Confirm database exists: repair_booking</li></ul></div></body></html>';
    exit;
}

try {
    try {
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.avatar as avatar_url, u.role 
            FROM users u 
            INNER JOIN sessions s ON u.id = s.user_id 
            WHERE s.token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        // Backwards compatibility when avatar_url column is not yet added
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.role 
            FROM users u 
            INNER JOIN sessions s ON u.id = s.user_id 
            WHERE s.token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user) { $user['avatar_url'] = null; }
    }
} catch (Exception $e) {
    error_log("Error in shop_homepage.php user query: " . $e->getMessage());
    redirect_to_login();
}

if (!$user || $user['role'] !== 'customer') redirect_to_login();

// Normalize avatar URL to work from the customer/ directory
$avatarCandidate = $user['avatar_url'] ?? '';
if ($avatarCandidate && !preg_match('/^https?:\/\//', $avatarCandidate) && strpos($avatarCandidate, '/') !== 0) {
    // Stored as relative to frontend root (e.g., uploads/avatars/..)
    $user['avatar_url'] = '../' . $avatarCandidate;
} elseif (!$avatarCandidate) {
    $user['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff';
}

function h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Homepage - ERepair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-styles.css" rel="stylesheet">
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <script src="../assets/js/erepair-common.js" defer></script>
    <style>
        .shop-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            color: white;
            padding: 3rem 0;
            border-radius: 0 0 2rem 2rem;
        }
        .shop-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .item-image {
            width: 100%;
            height: 160px;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .price-tag {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-weight: bold;
        }
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body class="bg-light" x-data="shopHomepage(<?php echo h($shop_id); ?>)" x-init="init()">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0b1220;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="customer_dashboard.php">
                <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 50%;">
                    <i class="fas fa-tools text-white"></i>
                </div>
                <span class="fw-bold">ERepair</span>
            </a>
            <button class="btn btn-outline-light" onclick="window.location.href='customer_dashboard.php'">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </button>
        </div>
    </nav>

    <!-- Shop Header -->
    <div class="shop-header" x-show="!loading">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img :src="getShopAvatar(shopData.shop)" 
                         :alt="shopData.shop.shop_name" 
                         class="shop-logo">
                </div>
                <div class="col-md-10">
                    <h1 class="display-4 fw-bold mb-2" x-text="shopData.shop.shop_name"></h1>
                    <div class="d-flex align-items-center gap-4 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span x-text="shopData.shop.shop_address"></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-phone me-2"></i>
                            <span x-text="shopData.shop.shop_phone"></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center bg-white bg-opacity-20 px-3 py-2 rounded-pill">
                            <span class="text-warning" x-text="'⭐'.repeat(Math.floor(shopData.ratings.average_rating))"></span>
                            <span class="ms-2 fw-bold" x-text="shopData.ratings.average_rating.toFixed(1)"></span>
                            <span class="ms-2" x-text="'(' + shopData.ratings.total_reviews + ' reviews)'"></span>
                        </div>
                        <button class="btn btn-light btn-lg" @click="scrollToBook">
                            <i class="fas fa-calendar-check me-2"></i>Book Service
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="container mt-5">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading shop information...</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-5" x-show="!loading">
        <!-- Services Section -->
        <div class="row mb-5" id="services-section">
            <div class="col-12 mb-4">
                <h2 class="fw-bold mb-3">
                    <i class="fas fa-concierge-bell text-primary me-2"></i>Available Services
                </h2>
            </div>
            <template x-for="service in shopData.services" :key="service.id">
                <div class="col-md-4 mb-4">
                    <div class="card card-hover h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title fw-bold" x-text="service.service_name"></h5>
                            <p class="text-muted small" x-text="service.description"></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="price-tag">₱<span x-text="parseFloat(service.price).toFixed(2)"></span></span>
                                <button class="btn btn-primary btn-sm" @click="openBookingModal(service)">
                                    <i class="fas fa-calendar-plus me-2"></i>Book
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="shopData.services.length === 0" class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No services available at this shop.
                </div>
            </div>
        </div>

        <!-- Items for Sale Section -->
        <div class="row mb-5" id="items-section">
            <div class="col-12 mb-4">
                <h2 class="fw-bold mb-3">
                    <i class="fas fa-shopping-bag text-primary me-2"></i>Items for Sale
                </h2>
            </div>
            <template x-for="item in shopData.items" :key="item.id">
                <div class="col-md-3 mb-4">
                    <div class="card card-hover h-100 shadow-sm position-relative">
                        <div class="card-img-top item-image">
                            <img x-show="item.image_url && !item.imageError" 
                                 :src="getItemImageUrl(item.image_url)" 
                                 :alt="item.item_name" 
                                 @error="item.imageError = true">
                            <i x-show="!item.image_url || item.imageError" class="fas fa-box-open fa-2x text-muted"></i>
                        </div>
                        <span x-show="!item.is_available" class="badge bg-danger stock-badge">Out of Stock</span>
                        <span x-show="item.is_available" class="badge bg-success stock-badge">Available</span>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold" x-text="item.item_name"></h5>
                            <p class="text-muted small" x-text="item.description"></p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="price-tag">₱<span x-text="item.price.toFixed(2)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="shopData.items.length === 0" class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No items for sale at this shop.
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mb-5" id="reviews-section">
            <div class="col-12 mb-4">
                <h2 class="fw-bold mb-3">
                    <i class="fas fa-star text-primary me-2"></i>Customer Reviews
                </h2>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="display-1 mb-3" x-text="shopData.ratings.average_rating.toFixed(1)"></div>
                        <div class="mb-3">
                            <span class="text-warning" x-text="'⭐'.repeat(5)"></span>
                        </div>
                        <h5 class="mb-1" x-text="shopData.ratings.total_reviews + ' total reviews'"></h5>
                        <p class="text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            Reviews are based on completed bookings
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function shopHomepage(shopId) {
            return {
                shopId: shopId,
                loading: true,
                shopData: {
                    shop: {},
                    ratings: { average_rating: 0, total_reviews: 0 },
                    services: [],
                    items: []
                },
                async init() {
                    await this.loadShopData();
                    this.loading = false;
                },
                async loadShopData() {
                    try {
                        const response = await fetch(`../../backend/api/shop-homepage.php?shop_id=${this.shopId}`);
                        const data = await response.json();
                        if (data.success) {
                            this.shopData = data;
                        } else {
                            Notiflix.Report.failure('Error', data.error || 'Failed to load shop data', 'OK');
                            setTimeout(() => window.location.href = 'customer_dashboard.php', 2000);
                        }
                    } catch (error) {
                        console.error('Error loading shop data:', error);
                        Notiflix.Report.failure('Error', 'Network error occurred', 'OK');
                        setTimeout(() => window.location.href = 'customer_dashboard.php', 2000);
                    }
                },
                scrollToBook() {
                    document.getElementById('services-section').scrollIntoView({ behavior: 'smooth' });
                },
                openBookingModal(service) {
                    // Save shop info and redirect to booking page
                    localStorage.setItem('selectedShop', JSON.stringify({
                        shopId: this.shopId,
                        shopName: this.shopData.shop.shop_name,
                        serviceId: service.id,
                        serviceName: service.service_name,
                        price: service.price
                    }));
                    window.location.href = 'booking_create_v2.php';
                },
                getShopAvatar(shop) {
                    // Priority: 1. Shop owner's avatar, 2. Shop logo, 3. Generated avatar from shop name
                    let avatarUrl = shop.owner_avatar_url || shop.logo || null;
                    
                    if (avatarUrl) {
                        // If it's already a full URL (http/https), return as is
                        if (avatarUrl.startsWith('http://') || avatarUrl.startsWith('https://')) {
                            return avatarUrl;
                        }
                        
                        // Normalize relative path - remove leading slash if present
                        let cleanPath = avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl;
                        
                        // From frontend/customer/shop_homepage.php to frontend/uploads/avatars/...
                        // We need: ../uploads/avatars/...
                        if (cleanPath.startsWith('uploads/')) {
                            return '../' + cleanPath;
                        } else {
                            return '../' + cleanPath;
                        }
                    }
                    
                    // Fallback to generated avatar from shop name
                    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(shop.shop_name) + '&size=200&background=4f46e5&color=fff';
                },
                getItemImageUrl(imageUrl) {
                    if (!imageUrl || !imageUrl.trim()) {
                        return '';
                    }
                    
                    // If it's already a full URL (http/https), return as is
                    if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
                        return imageUrl;
                    }
                    
                    // Remove leading slash if present
                    let cleanPath = imageUrl.startsWith('/') ? imageUrl.substring(1) : imageUrl;
                    
                    // From frontend/customer/shop_homepage.php to frontend/uploads/shop_items/image.jpg
                    // We need: ../uploads/shop_items/image.jpg
                    // The image_url in DB is stored as: uploads/shop_items/filename.jpg
                    
                    if (cleanPath.startsWith('uploads/')) {
                        // Path already has uploads/ prefix - just add ../ to go up from customer/ to frontend/
                        return '../' + cleanPath;
                    } else if (cleanPath.startsWith('shop_items/')) {
                        // Path has shop_items/ but missing uploads/ prefix
                        return '../uploads/' + cleanPath;
                    } else if (!cleanPath.includes('/')) {
                        // Just a filename, assume it's in shop_items
                        return '../uploads/shop_items/' + cleanPath;
                    } else {
                        // Some other path structure, try relative
                        return '../' + cleanPath;
                    }
                },
                addToCart(item) {
                    try {
                        // Get existing cart from localStorage
                        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
                        
                        // Check if item already exists in cart
                        const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id && cartItem.shop_id === this.shopId);
                        
                        if (existingItemIndex > -1) {
                            // Update quantity
                            cart[existingItemIndex].quantity += 1;
                        } else {
                            // Add new item to cart
                            cart.push({
                                id: item.id,
                                shop_id: this.shopId,
                                shop_name: this.shopData.shop.shop_name,
                                item_name: item.item_name,
                                price: item.price,
                                image_url: item.image_url,
                                stock_quantity: item.stock_quantity,
                                quantity: 1
                            });
                        }
                        
                        // Save to localStorage
                        localStorage.setItem('cart', JSON.stringify(cart));
                        
                        // Show success message
                        Notiflix.Notify.success(`${item.item_name} has been added to your cart`, {
                            position: 'right-top',
                            timeout: 2000,
                            clickToClose: true
                        });
                        
                        // Update cart count if there's a cart indicator
                        this.updateCartCount();
                    } catch (error) {
                        console.error('Error adding to cart:', error);
                        Notiflix.Report.failure('Error', 'Failed to add item to cart', 'OK');
                    }
                },
                buyNow(item) {
                    try {
                        // Get existing cart from localStorage
                        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
                        
                        // Check if item already exists in cart
                        const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id && cartItem.shop_id === this.shopId);
                        
                        if (existingItemIndex > -1) {
                            // Update quantity
                            cart[existingItemIndex].quantity += 1;
                        } else {
                            // Add new item to cart
                            cart.push({
                                id: item.id,
                                shop_id: this.shopId,
                                shop_name: this.shopData.shop.shop_name,
                                item_name: item.item_name,
                                price: item.price,
                                image_url: item.image_url,
                                stock_quantity: item.stock_quantity,
                                quantity: 1
                            });
                        }
                        
                        // Save to localStorage
                        localStorage.setItem('cart', JSON.stringify(cart));
                        
                        // Update cart count
                        this.updateCartCount();
                        
                        // Redirect to customer dashboard orders section
                        window.location.href = 'customer_dashboard.php';
                        // Note: The cart will be loaded when dashboard initializes
                    } catch (error) {
                        console.error('Error processing buy now:', error);
                        Notiflix.Report.failure('Error', 'Failed to process purchase', 'OK');
                    }
                },
                updateCartCount() {
                    try {
                        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
                        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                        
                        // Update cart badge if it exists
                        const cartBadge = document.querySelector('.cart-badge');
                        if (cartBadge) {
                            cartBadge.textContent = totalItems;
                            cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
                        }
                    } catch (error) {
                        console.error('Error updating cart count:', error);
                    }
                }
            };
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

