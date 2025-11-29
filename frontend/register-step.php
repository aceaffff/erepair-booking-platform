<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERepair - Register and book your electronics repair services">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ERepair">
    <title>Register - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="../backend/api/favicon.php">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-generator.php?size=192">
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="assets/css/erepair-styles.css" rel="stylesheet">
    <script src="assets/js/philippine-locations.js"></script>
    <script src="assets/js/erepair-common.js"></script>
    <style>
        .step-indicator {
            position: relative;
        }
        .step-indicator::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 100%;
            height: 2px;
            background: rgba(99, 102, 241, 0.3);
            transform: translateY(-50%);
        }
        .step-indicator:last-child::after {
            display: none;
        }
        .step-indicator.active::after {
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
        }
        /* Ensure selfie file input is completely hidden and inaccessible */
        #selfie-file-input {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .dropdown-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.15s;
        }
        .dropdown-item:hover {
            background-color: #f3f4f6;
        }
        .dropdown-item.active {
            background-color: #e0e7ff;
        }
        
        /* Terms and Conditions Modal Scrollbar */
        .terms-modal-scroll {
            scrollbar-width: thin;
            scrollbar-color: #6366f1 #e5e7eb;
            overflow-y: scroll !important;
        }
        
        .terms-modal-scroll::-webkit-scrollbar {
            width: 10px;
            display: block !important;
        }
        
        .terms-modal-scroll::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 5px;
            margin: 4px;
        }
        
        .terms-modal-scroll::-webkit-scrollbar-thumb {
            background: #6366f1;
            border-radius: 5px;
            border: 2px solid #e5e7eb;
        }
        
        .terms-modal-scroll::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }
        
        .terms-modal-scroll::-webkit-scrollbar-corner {
            background: #e5e7eb;
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
    </style>
</head>
<body class="bg-light min-h-screen" x-data="stepRegistration()">
    <!-- Navigation - match login style -->
    <nav class="fixed w-full z-50 bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="auth/index.php" class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-slate-900">
                            <i class="fas fa-tools mr-2 icon-morph"></i>ERepair
                        </h1>
                    </a>
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
                    <a href="auth/index.php" class="text-slate-700 hover:text-slate-900 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:bg-slate-100">
                        Home
                    </a>
                    <a href="auth/index.php" class="btn-holographic text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Spacer to keep content below fixed navbar -->
    <div class="h-16 w-full"></div>

    <!-- Main Content -->
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
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
        
        <div class="max-w-4xl w-full space-y-8 relative z-10">
            <!-- Header -->
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold neon-text">
                    Create your account
                </h2>
                <p class="mt-2 text-sm text-indigo-100">
                    Already have an account?
                    <a href="auth/index.php" class="font-medium text-white hover:text-indigo-300">
                        Sign in here
                    </a>
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="glass-advanced py-8 px-6 shadow-xl rounded-lg">
                <!-- Step Indicator -->
                <div class="flex items-center justify-center mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="step-indicator flex items-center" :class="currentStep >= 1 ? 'active' : ''">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                                 :class="currentStep >= 1 ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'">
                                <span class="text-sm font-medium">1</span>
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="currentStep >= 1 ? 'text-indigo-600' : 'text-gray-500'">Account Type</span>
                        </div>
                        <div class="step-indicator flex items-center" :class="currentStep >= 2 ? 'active' : ''">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                                 :class="currentStep >= 2 ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'">
                                <span class="text-sm font-medium">2</span>
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="currentStep >= 2 ? 'text-indigo-600' : 'text-gray-500'">Personal Info</span>
                        </div>
                        <div class="step-indicator flex items-center" :class="currentStep >= 3 ? 'active' : ''" x-show="userType === 'shop_owner'">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                                 :class="currentStep >= 3 ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'">
                                <span class="text-sm font-medium">3</span>
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="currentStep >= 3 ? 'text-indigo-600' : 'text-gray-500'">Location</span>
                        </div>
                        <div class="step-indicator flex items-center" :class="currentStep >= (userType === 'customer' ? 3 : 4) ? 'active' : ''">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                                 :class="currentStep >= (userType === 'customer' ? 3 : 4) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'">
                                <span class="text-sm font-medium" x-text="userType === 'customer' ? '3' : '4'"></span>
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="currentStep >= (userType === 'customer' ? 3 : 4) ? 'text-indigo-600' : 'text-gray-500'">Documents</span>
                        </div>
                        <div class="step-indicator flex items-center" :class="currentStep >= (userType === 'customer' ? 4 : 5) ? 'active' : ''">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                                 :class="currentStep >= (userType === 'customer' ? 4 : 5) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 text-gray-500'">
                                <span class="text-sm font-medium" x-text="userType === 'customer' ? '4' : '5'"></span>
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="currentStep >= (userType === 'customer' ? 4 : 5) ? 'text-indigo-600' : 'text-gray-500'">Review</span>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Account Type Selection -->
                <div x-show="currentStep === 1" class="space-y-6">
                    <div class="text-center">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Choose Your Account Type</h3>
                        <p class="text-gray-600">Select the type of account you want to create</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border-2 rounded-lg p-6 cursor-pointer transition-all hover:border-indigo-500"
                             :class="userType === 'customer' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                             @click="userType = 'customer'">
                            <div class="text-center">
                                <div class="mx-auto w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-user text-2xl text-indigo-600"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Customer</h4>
                                <p class="text-gray-600 text-sm">I need repair services for my devices</p>
                            </div>
                        </div>
                        
                        <div class="border-2 rounded-lg p-6 cursor-pointer transition-all hover:border-indigo-500"
                             :class="userType === 'shop_owner' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                             @click="userType = 'shop_owner'">
                            <div class="text-center">
                                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-store text-2xl text-green-600"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Shop Owner</h4>
                                <p class="text-gray-600 text-sm">I provide repair services to customers</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Personal Information -->
                <div x-show="currentStep === 2" class="space-y-6">
                    <div class="text-center">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Personal Information</h3>
                        <p class="text-gray-600">Tell us about yourself</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" x-model="formData.name" required
                                   @keydown="preventSpecialChars($event, 'name')"
                                   @input="formData.name = filterInput($event.target.value, 'name')"
                                   @paste="handlePaste($event, 'name')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" x-model="formData.email" required
                                   @keydown="preventSpecialChars($event, 'email')"
                                   @input="validateEmail($event); formData.email = filterInput($event.target.value, 'email')"
                                   @paste="handlePaste($event, 'email')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Enter your email (e.g., user@gmail.com)">
                            <div x-show="emailError" class="text-red-500 text-xs mt-1" x-text="emailError"></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" x-model="formData.phone" required
                                   inputmode="numeric" pattern="\\d*"
                                   maxlength="11"
                                   @keydown="filterDigitKeydown($event)"
                                   @paste="filterDigitPaste($event, 'phone')"
                                   @input="validatePhone($event)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="09XXXXXXXXX (11 digits starting with 09)">
                            <div x-show="phoneError" class="text-red-500 text-xs mt-1" x-text="phoneError"></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" x-model="formData.password" 
                                   @keydown="preventSpecialChars($event, 'password')"
                                   @input="updatePasswordStrength(formData.password); formData.password = filterInput($event.target.value, 'password')"
                                   @paste="handlePaste($event, 'password')"
                                   required
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Enter your password">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400"></i>
                                </button>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <div class="text-gray-600">Tip: Use 8+ chars with A-z, 0-9 and _.</div>
                                <div :class="passwordStrengthClass">Strength: <span x-text="passwordStrengthLabel"></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div x-show="userType === 'shop_owner'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shop Name</label>
                            <input type="text" x-model="formData.shop_name" required
                                   @keydown="preventSpecialChars($event, 'name')"
                                   @input="formData.shop_name = filterInput($event.target.value, 'name')"
                                   @paste="handlePaste($event, 'name')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Enter your shop name">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Location (Shop Owner Only) -->
                <div x-show="currentStep === 3 && userType === 'shop_owner'" class="space-y-6">
                    <div class="text-center">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Location Information</h3>
                        <p class="text-gray-600">Help us find your shop location</p>
                    </div>
                    
                    <!-- Location Controls -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-4">
                        <button @click="getCurrentLocation()" 
                                :disabled="locationLoading"
                                class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i :class="locationLoading ? 'fas fa-spinner fa-spin' : 'fas fa-map-marker-alt'" class="mr-2"></i>
                            <span x-text="locationLoading ? 'Getting Location...' : 'Use Current Location'"></span>
                        </button>
                    </div>
                    
                    <!-- Map -->
                    <div id="map" class="mb-2"></div>
                    <div class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2 mb-4">
                        <i class="fas fa-map-pin mr-1"></i>
                        Please make sure you select the correct location.
                    </div>
                    
                    <!-- Address Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                            <input type="text" x-model="formData.street_address" required
                                   @keydown="preventSpecialChars($event, 'address')"
                                   @input="formData.street_address = filterInput($event.target.value, 'address')"
                                   @paste="handlePaste($event, 'address')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Street address">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Barangay</label>
                            <input type="text" x-model="formData.barangay" required
                                   @keydown="preventSpecialChars($event, 'address')"
                                   @input="formData.barangay = filterInput($event.target.value, 'address')"
                                   @paste="handlePaste($event, 'address')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Barangay">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                            <div class="relative">
                                <input type="text" 
                                       x-model="provinceSearch"
                                       @keydown="preventSpecialChars($event, 'address')"
                                       @input="provinceSearch = filterInput($event.target.value, 'address'); handleProvinceInput(provinceSearch); showProvinceDropdown = true"
                                       @paste="handlePaste($event, 'address')"
                                       @focus="provinceSearch = formData.state || ''; showProvinceDropdown = true"
                                       @blur="setTimeout(() => { showProvinceDropdown = false; }, 200)"
                                       @keydown.escape="showProvinceDropdown = false"
                                       placeholder="Type or select province..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       required>
                                <div x-show="showProvinceDropdown && filteredProvinces.length > 0" 
                                     class="dropdown-suggestions"
                                     @click.away="showProvinceDropdown = false">
                                    <template x-for="province in filteredProvinces.slice(0, 10)" :key="province">
                                        <div class="dropdown-item" 
                                             @click="formData.state = province; provinceSearch = province; showProvinceDropdown = false; handleProvinceInput(province);"
                                             x-text="province"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <div class="relative">
                                <input type="text" 
                                       x-model="citySearch"
                                       @keydown="preventSpecialChars($event, 'address')"
                                       @input="citySearch = filterInput($event.target.value, 'address'); formData.city = citySearch; handleCityInput(citySearch); showCityDropdown = true"
                                       @paste="handlePaste($event, 'address')"
                                       @focus="citySearch = formData.city || ''; showCityDropdown = true"
                                       @blur="setTimeout(() => { showCityDropdown = false; }, 200)"
                                       @keydown.escape="showCityDropdown = false"
                                       placeholder="Type or select city..."
                                       :disabled="!formData.state || formData.state === ''"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                       required>
                                <div x-show="showCityDropdown && filteredCities.length > 0" 
                                     class="dropdown-suggestions"
                                     @click.away="showCityDropdown = false">
                                    <template x-for="city in filteredCities.slice(0, 10)" :key="city">
                                        <div class="dropdown-item" 
                                             @click="formData.city = city; citySearch = city; showCityDropdown = false;"
                                             x-text="city"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                            <input type="text" x-model="formData.postal_code" required
                                   @keydown="preventSpecialChars($event, 'postal_code')"
                                   @input="formData.postal_code = filterInput($event.target.value, 'postal_code')"
                                   @paste="handlePaste($event, 'postal_code')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Postal code">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select x-model="formData.country" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- Select Country --</option>
                                <option value="Philippines" selected>Philippines</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Location Status -->
                    <div x-show="locationStatus" class="p-3 rounded-md" 
                         :class="locationStatus && locationStatus.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        <i :class="locationStatus && locationStatus.success ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'" class="mr-2"></i>
                        <span x-text="locationStatus ? locationStatus.message : ''"></span>
                    </div>
                </div>

                <!-- Step 3/4: Documents -->
                <div x-show="(userType === 'customer' && currentStep === 3) || (userType === 'shop_owner' && currentStep === 4)" class="space-y-6">
                    <div class="text-center">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Required Documents</h3>
                        <p class="text-gray-600" x-show="userType === 'shop_owner'">Upload your business documents for verification</p>
                    </div>
                    
                    <div x-show="userType === 'shop_owner'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Permit/License</label>
                            <input type="file" @change="handleFileSelect($event, 'business_permit_file')" required
                                   accept=".jpg,.jpeg,.png,.pdf"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF (max 5MB)</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ID Type <span class="text-red-500">*</span></label>
                                <select x-model="formData.id_type" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select ID Type</option>
                                    <option value="Driver's License">Driver's License</option>
                                    <option value="Passport">Passport</option>
                                    <option value="National ID">National ID</option>
                                    <option value="PhilHealth ID">PhilHealth ID</option>
                                    <option value="SSS ID">SSS ID</option>
                                    <option value="TIN ID">TIN ID</option>
                                    <option value="Postal ID">Postal ID</option>
                                    <option value="Voter's ID">Voter's ID</option>
                                    <option value="PRC ID">PRC ID</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Number <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="formData.id_number" required
                                           @keydown="preventSpecialChars($event, 'id_number')"
                                           @input="formData.id_number = filterInput($event.target.value, 'id_number')"
                                           @paste="handlePaste($event, 'id_number')"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                           placeholder="Enter ID number">
                                    <p class="text-xs text-gray-500 mt-1">As shown on your ID document</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Expiry Date</label>
                                    <input type="date" x-model="formData.id_expiry_date"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">Leave blank if no expiry date</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Front Side <span class="text-red-500">*</span></label>
                                    <input type="file" @change="handleFileSelect($event, 'id_file_front')" required
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF (max 5MB)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Back Side <span class="text-red-500">*</span></label>
                                    <input type="file" @change="handleFileSelect($event, 'id_file_back')" required
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF (max 5MB)</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Selfie with ID <span class="text-red-500">*</span></label>
                                <button type="button" 
                                        @click="openCameraForSelfie()"
                                        class="w-full px-4 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center gap-2 mb-2">
                                    <i class="fas fa-camera"></i>
                                    <span>Take Selfie with ID</span>
                                </button>
                                <input type="file" 
                                       id="shop-owner-selfie-file-input"
                                       @change="handleFileSelect($event, 'selfie_file')" 
                                       required
                                       accept="image/*"
                                       capture="user"
                                       class="hidden"
                                       style="display: none !important;"
                                       readonly
                                       disabled>
                                <p class="text-xs text-gray-500 mt-1">Take a selfie holding your ID next to your face using your camera. JPG or PNG only (max 5MB)</p>
                                <p class="text-xs text-yellow-600 mt-1"><i class="fas fa-info-circle"></i> This helps us verify your identity</p>
                                <div x-show="files.selfie_file" class="mt-3">
                                    <div class="border border-gray-300 rounded-lg p-2">
                                        <img :src="getFilePreview('selfie_file')" 
                                             alt="Selfie Preview" 
                                             class="max-w-full h-auto rounded-md"
                                             style="max-height: 300px;">
                                        <button type="button" 
                                                @click="retakeSelfie()"
                                                class="mt-2 w-full px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                                            <i class="fas fa-redo mr-2"></i>Retake Photo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div x-show="userType === 'customer'">
                        <h4 class="font-semibold text-gray-900 mb-4">Verification Documents</h4>
                        
                        <!-- ID Picture Upload -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ID Picture <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   @change="handleFileSelect($event, 'id_file')" 
                                   required
                                   accept=".jpg,.jpeg,.png,.pdf"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="text-xs text-gray-500 mt-1">Upload a clear photo of your valid ID (Driver's License, National ID, Passport, etc.). JPG, PNG, or PDF (max 5MB)</p>
                            <div x-show="files.id_file" class="mt-3">
                                <div x-show="files.id_file && files.id_file.type && files.id_file.type.startsWith('image/')" class="border border-gray-300 rounded-lg p-2">
                                    <img :src="getFilePreview('id_file')" 
                                         alt="ID Preview" 
                                         class="max-w-full h-auto rounded-md"
                                         style="max-height: 200px;">
                                </div>
                                <div x-show="files.id_file && files.id_file.type === 'application/pdf'" class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-600" x-text="files.id_file ? files.id_file.name : ''"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selfie with ID Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Selfie with ID <span class="text-red-500">*</span>
                            </label>
                            <button type="button" 
                                    @click="openCameraForSelfie()"
                                    class="w-full px-4 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center gap-2 mb-2">
                                <i class="fas fa-camera"></i>
                                <span>Take Selfie with ID</span>
                            </button>
                            <input type="file" 
                                   id="selfie-file-input"
                                   @change="handleFileSelect($event, 'selfie_file')" 
                                   required
                                   accept="image/*"
                                   capture="user"
                                   class="hidden"
                                   style="display: none !important;"
                                   readonly
                                   disabled>
                            <p class="text-xs text-gray-500 mt-1">Take a selfie holding your ID next to your face using your camera. JPG or PNG only (max 5MB)</p>
                            <p class="text-xs text-yellow-600 mt-1"><i class="fas fa-info-circle"></i> This helps us verify your identity</p>
                            <div x-show="files.selfie_file" class="mt-3">
                                <div class="border border-gray-300 rounded-lg p-2">
                                    <img :src="getFilePreview('selfie_file')" 
                                         alt="Selfie Preview" 
                                         class="max-w-full h-auto rounded-md"
                                         style="max-height: 300px;">
                                    <button type="button" 
                                            @click="retakeSelfie()"
                                            class="mt-2 w-full px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                                        <i class="fas fa-redo mr-2"></i>Retake Photo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>

                <!-- Step 4/5: Review -->
                <div x-show="(userType === 'customer' && currentStep === 4) || (userType === 'shop_owner' && currentStep === 5)" class="space-y-6">
                    <div class="text-center">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Review Your Information</h3>
                        <p class="text-gray-600">Please review your information before submitting</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">Account Type</h4>
                            <p class="text-gray-600 capitalize" x-text="userType.replace('_', ' ')"></p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900">Personal Information</h4>
                            <p class="text-gray-600">Name: <span x-text="formData.name"></span></p>
                            <p class="text-gray-600">Email: <span x-text="formData.email"></span></p>
                            <p class="text-gray-600">Phone: <span x-text="formData.phone"></span></p>
                        </div>
                        
                        <div x-show="userType === 'customer'">
                            <h4 class="font-semibold text-gray-900 mb-4">Documents</h4>
                            <div class="mb-3">
                                <p class="text-sm font-medium text-gray-700 mb-2">ID Picture</p>
                                <p class="text-gray-600 text-sm mb-2" x-text="files.id_file ? files.id_file.name : 'Not uploaded'"></p>
                                <div x-show="files.id_file && files.id_file.type.startsWith('image/')" class="mt-2">
                                    <img :src="getFilePreview('id_file')" 
                                         alt="ID Preview" 
                                         class="max-w-full h-auto border border-gray-300 rounded-lg shadow-sm"
                                         style="max-height: 200px;">
                                </div>
                                <div x-show="files.id_file && files.id_file.type === 'application/pdf'" class="mt-2">
                                    <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                        <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-600">PDF Document</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Selfie with ID</p>
                                <p class="text-gray-600 text-sm mb-2" x-text="files.selfie_file ? files.selfie_file.name : 'Not uploaded'"></p>
                                <div x-show="files.selfie_file && files.selfie_file.type.startsWith('image/')" class="mt-2">
                                    <img :src="getFilePreview('selfie_file')" 
                                         alt="Selfie Preview" 
                                         class="max-w-full h-auto border border-gray-300 rounded-lg shadow-sm"
                                         style="max-height: 300px;">
                                </div>
                            </div>
                        </div>
                        
                        <div x-show="userType === 'shop_owner'">
                            <h4 class="font-semibold text-gray-900">Shop Information</h4>
                            <p class="text-gray-600">Shop Name: <span x-text="formData.shop_name"></span></p>
                        </div>
                        
                        <div x-show="userType === 'shop_owner'">
                            <h4 class="font-semibold text-gray-900">Address</h4>
                            <p class="text-gray-600" x-text="getFullAddress()"></p>
                        </div>
                        
                        <div x-show="userType === 'shop_owner'">
                            <h4 class="font-semibold text-gray-900 mb-4">Documents</h4>
                            
                            <!-- Business Permit -->
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Business Permit</p>
                                <p class="text-gray-600 text-sm mb-2" x-text="files.business_permit_file ? files.business_permit_file.name : 'Not uploaded'"></p>
                                <div x-show="files.business_permit_file && files.business_permit_file.type.startsWith('image/')" class="mt-2">
                                    <img :src="getFilePreview('business_permit_file')" 
                                         alt="Business Permit Preview" 
                                         class="max-w-full h-auto border border-gray-300 rounded-lg shadow-sm"
                                         style="max-height: 200px;">
                                </div>
                                <div x-show="files.business_permit_file && files.business_permit_file.type === 'application/pdf'" class="mt-2">
                                    <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                        <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-600">PDF Document</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ID Information -->
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">ID Information</p>
                                <p class="text-gray-600 text-sm">ID Type: <span x-text="formData.id_type || 'Not selected'"></span></p>
                                <p class="text-gray-600 text-sm">ID Number: <span x-text="formData.id_number || 'Not provided'"></span></p>
                                <p class="text-gray-600 text-sm" x-show="formData.id_expiry_date">ID Expiry: <span x-text="formData.id_expiry_date"></span></p>
                            </div>
                            
                            <!-- ID Front and Back -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2">ID Front Side</p>
                                    <p class="text-gray-600 text-sm mb-2" x-text="files.id_file_front ? files.id_file_front.name : 'Not uploaded'"></p>
                                    <div x-show="files.id_file_front && files.id_file_front.type.startsWith('image/')" class="mt-2">
                                        <img :src="getFilePreview('id_file_front')" 
                                             alt="ID Front Preview" 
                                             class="max-w-full h-auto border border-gray-300 rounded-lg shadow-sm"
                                             style="max-height: 200px;">
                                    </div>
                                    <div x-show="files.id_file_front && files.id_file_front.type === 'application/pdf'" class="mt-2">
                                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                            <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                                            <p class="text-sm text-gray-600">PDF Document</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2">ID Back Side</p>
                                    <p class="text-gray-600 text-sm mb-2" x-text="files.id_file_back ? files.id_file_back.name : 'Not uploaded'"></p>
                                    <div x-show="files.id_file_back && files.id_file_back.type.startsWith('image/')" class="mt-2">
                                        <img :src="getFilePreview('id_file_back')" 
                                             alt="ID Back Preview" 
                                             class="max-w-full h-auto border border-gray-300 rounded-lg shadow-sm"
                                             style="max-height: 200px;">
                                    </div>
                                    <div x-show="files.id_file_back && files.id_file_back.type === 'application/pdf'" class="mt-2">
                                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                            <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                                            <p class="text-sm text-gray-600">PDF Document</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selfie with ID -->
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Selfie with ID</p>
                                <p class="text-gray-600 text-sm mb-2" x-text="files.selfie_file ? files.selfie_file.name : 'Not uploaded'"></p>
                                <div x-show="files.selfie_file && files.selfie_file.type.startsWith('image/')" class="mt-2">
                                    <img :src="getFilePreview('selfie_file')" 
                                         alt="Selfie Preview" 
                                         class="max-w-full h-auto border border-gray-300 rounded-lg shadow-sm"
                                         style="max-height: 300px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6">
                    <button @click="previousStep()" 
                            x-show="currentStep > 1"
                            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>Previous
                    </button>
                    
                    <div class="flex-1"></div>
                    
                    <button @click="nextStep()" 
                            x-show="(userType === 'customer' && currentStep < 4) || (userType === 'shop_owner' && currentStep < 5)"
                            :disabled="!canProceed()"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next<i class="fas fa-arrow-right ml-2"></i>
                    </button>
                    
                    <div x-show="(userType === 'customer' && currentStep === 4) || (userType === 'shop_owner' && currentStep === 5)" class="flex flex-col items-end space-y-3">
                        <div class="flex items-start space-x-2 w-full max-w-md">
                            <input type="checkbox" 
                                   id="terms-checkbox"
                                   x-model="termsAccepted"
                                   class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="terms-checkbox" class="text-sm text-gray-700">
                                I agree to the 
                                <button type="button" 
                                        @click="showTermsModal = true"
                                        class="text-indigo-600 hover:text-indigo-800 underline">
                                    Terms and Conditions
                                </button>
                            </label>
                        </div>
                        <button @click="submitRegistration()" 
                                :disabled="loading || !termsAccepted"
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-check'" class="mr-2"></i>
                            <span x-text="loading ? 'Creating Account...' : 'Create Account'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div x-show="showTermsModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.away="showTermsModal = false"
         @keydown.escape.window="showTermsModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 modal-backdrop" 
                 x-show="showTermsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showTermsModal = false"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full max-h-[85vh] flex flex-col"
                 x-show="showTermsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.stop>
                <!-- Modal Header (Fixed) -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-xl font-bold text-gray-900">Terms and Conditions</h3>
                    <button @click="showTermsModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Scrollable Content Area -->
                <div class="flex-1 overflow-y-scroll px-6 py-4 terms-modal-scroll" style="max-height: calc(85vh - 120px); min-height: 400px;">
                    <div class="space-y-4 text-sm text-gray-700">
                        <!-- Terms and Conditions Content -->
                        <div>
                            <p class="mb-4 text-gray-600">
                                <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                            </p>
                            
                            <p class="mb-4">
                                Welcome to ERepair ("we," "our," or "us"). By creating an account and using our platform, you agree to be bound by these Terms and Conditions and our Privacy Policy, which complies with the Data Privacy Act of 2012 (Republic Act No. 10173) of the Philippines. Please read them carefully.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 1: Data Privacy Act Compliance</h4>
                            <p class="mb-3">
                                1.1 ERepair is committed to protecting your personal information in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173) of the Philippines.
                            </p>
                            <p class="mb-3">
                                1.2 By using our platform, you consent to the collection, use, processing, and storage of your personal data as described in these Terms and Conditions and our Privacy Policy.
                            </p>
                            <p class="mb-3">
                                1.3 We collect only the personal information necessary to provide our services, including but not limited to: name, email address, phone number, address, identification documents, and business information (for shop owners).
                            </p>
                            <p class="mb-3">
                                1.4 Your personal data will be used for account management, service delivery, verification purposes, communication, and compliance with legal obligations.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 2: Data Collection and Processing</h4>
                            <p class="mb-3">
                                2.1 We collect personal information that you voluntarily provide when registering, booking services, or communicating with us.
                            </p>
                            <p class="mb-3">
                                2.2 For shop owners, we collect additional information including business permits, identification documents, and selfie photos for verification and security purposes.
                            </p>
                            <p class="mb-3">
                                2.3 We may collect technical information such as IP address, device information, and usage data to improve our services and ensure platform security.
                            </p>
                            <p class="mb-3">
                                2.4 All personal data is processed fairly and lawfully, in accordance with the Data Privacy Act and our legitimate business purposes.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 3: Data Storage and Security</h4>
                            <p class="mb-3">
                                3.1 We implement appropriate technical and organizational security measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.
                            </p>
                            <p class="mb-3">
                                3.2 Your personal data is stored securely on our servers and is accessible only to authorized personnel who need it to perform their duties.
                            </p>
                            <p class="mb-3">
                                3.3 We retain your personal data only for as long as necessary to fulfill the purposes for which it was collected, or as required by law.
                            </p>
                            <p class="mb-3">
                                3.4 While we strive to protect your data, no method of transmission over the internet or electronic storage is 100% secure. We cannot guarantee absolute security.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 4: Data Sharing and Disclosure</h4>
                            <p class="mb-3">
                                4.1 We do not sell, trade, or rent your personal information to third parties without your explicit consent, except as described in these terms.
                            </p>
                            <p class="mb-3">
                                4.2 We may share your information with service providers who assist us in operating our platform, provided they agree to maintain the confidentiality of your data.
                            </p>
                            <p class="mb-3">
                                4.3 We may disclose your information if required by law, court order, or government regulation, or to protect our rights, property, or safety.
                            </p>
                            <p class="mb-3">
                                4.4 For booking services, necessary information (name, contact details, service requirements) will be shared with the selected repair shop to facilitate service delivery.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 5: Your Rights Under the Data Privacy Act</h4>
                            <p class="mb-3">
                                5.1 <strong>Right to be Informed:</strong> You have the right to be informed about the collection, processing, and sharing of your personal data.
                            </p>
                            <p class="mb-3">
                                5.2 <strong>Right to Access:</strong> You have the right to request access to your personal data that we hold, subject to reasonable limitations.
                            </p>
                            <p class="mb-3">
                                5.3 <strong>Right to Object:</strong> You have the right to object to the processing of your personal data for certain purposes, including direct marketing.
                            </p>
                            <p class="mb-3">
                                5.4 <strong>Right to Erasure or Blocking:</strong> You have the right to request the deletion or blocking of your personal data if it is no longer necessary or if you withdraw consent.
                            </p>
                            <p class="mb-3">
                                5.5 <strong>Right to Damages:</strong> You have the right to claim damages if you suffer harm due to inaccurate, incomplete, outdated, false, or unlawfully obtained personal data.
                            </p>
                            <p class="mb-3">
                                5.6 <strong>Right to Data Portability:</strong> You have the right to obtain a copy of your personal data in a structured, commonly used format.
                            </p>
                            <p class="mb-3">
                                5.7 <strong>Right to File a Complaint:</strong> You have the right to file a complaint with the National Privacy Commission if you believe your data privacy rights have been violated.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 6: Consent and Withdrawal</h4>
                            <p class="mb-3">
                                6.1 By creating an account and using our services, you provide your explicit consent to the collection, processing, and storage of your personal data as described herein.
                            </p>
                            <p class="mb-3">
                                6.2 You may withdraw your consent at any time by contacting us or deleting your account. However, withdrawal may affect your ability to use our services.
                            </p>
                            <p class="mb-3">
                                6.3 We will process your withdrawal request within a reasonable time, subject to legal and contractual obligations.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 7: Account Registration and Eligibility</h4>
                            <p class="mb-3">
                                7.1 You must be at least 18 years old to create an account and use our services.
                            </p>
                            <p class="mb-3">
                                7.2 You agree to provide accurate, current, and complete information during registration and to update such information to keep it accurate, current, and complete.
                            </p>
                            <p class="mb-3">
                                7.3 You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
                            </p>
                            <p class="mb-3">
                                7.4 Shop owners must provide valid business permits, identification documents, and accurate business information. We reserve the right to verify all submitted documents.
                            </p>
                            <p class="mb-3">
                                7.5 We reserve the right to reject, suspend, or terminate any account that violates these terms or provides false information.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 8: User Responsibilities</h4>
                            <p class="mb-3">
                                8.1 You agree to use the platform only for lawful purposes and in accordance with these Terms and Conditions.
                            </p>
                            <p class="mb-3">
                                8.2 You will not use the platform to transmit any harmful, offensive, or illegal content.
                            </p>
                            <p class="mb-3">
                                8.3 You will not attempt to gain unauthorized access to the platform, other accounts, or computer systems.
                            </p>
                            <p class="mb-3">
                                8.4 Customers are responsible for accurately describing device issues and providing correct contact information.
                            </p>
                            <p class="mb-3">
                                8.5 Shop owners are responsible for providing quality repair services, accurate pricing, and timely completion of repairs.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 9: Service Booking and Transactions</h4>
                            <p class="mb-3">
                                9.1 Booking requests are subject to shop owner approval and availability.
                            </p>
                            <p class="mb-3">
                                9.2 All pricing and service terms are set by individual shop owners. ERepair is not responsible for pricing disputes.
                            </p>
                            <p class="mb-3">
                                9.3 Cancellation policies are determined by individual shop owners and must be clearly communicated.
                            </p>
                            <p class="mb-3">
                                9.4 You agree to honor confirmed bookings and provide reasonable notice for cancellations.
                            </p>
                            <p class="mb-3">
                                9.5 ERepair acts as a platform connecting customers and repair shops and is not a party to the actual repair service agreement.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 10: Payment and Fees</h4>
                            <p class="mb-3">
                                10.1 Payment terms are agreed upon directly between customers and shop owners.
                            </p>
                            <p class="mb-3">
                                10.2 ERepair may charge platform fees as disclosed at the time of booking. All fees are non-refundable unless otherwise stated.
                            </p>
                            <p class="mb-3">
                                10.3 You are responsible for all applicable taxes related to transactions conducted through the platform.
                            </p>
                            <p class="mb-3">
                                10.4 Disputes regarding payments must be resolved directly between customers and shop owners. ERepair may assist in dispute resolution but is not liable for payment issues.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 11: Intellectual Property</h4>
                            <p class="mb-3">
                                11.1 All content on the ERepair platform, including logos, text, graphics, and software, is the property of ERepair or its licensors and is protected by copyright and trademark laws.
                            </p>
                            <p class="mb-3">
                                11.2 You may not reproduce, distribute, or create derivative works from platform content without our express written permission.
                            </p>
                            <p class="mb-3">
                                11.3 You retain ownership of content you submit but grant ERepair a license to use, display, and distribute such content on the platform.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 12: Limitation of Liability</h4>
                            <p class="mb-3">
                                12.1 ERepair provides the platform "as is" and "as available" without warranties of any kind, either express or implied.
                            </p>
                            <p class="mb-3">
                                12.2 ERepair is not responsible for the quality, safety, or legality of repair services provided by shop owners.
                            </p>
                            <p class="mb-3">
                                12.3 ERepair shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the platform.
                            </p>
                            <p class="mb-3">
                                12.4 Our total liability to you shall not exceed the amount you paid to ERepair in the 12 months preceding the claim.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 13: Indemnification</h4>
                            <p class="mb-3">
                                13.1 You agree to indemnify and hold harmless ERepair, its officers, directors, employees, and agents from any claims, damages, losses, or expenses arising from your use of the platform or violation of these terms.
                            </p>
                            <p class="mb-3">
                                13.2 This includes but is not limited to claims related to services provided by shop owners, content you submit, or your breach of these Terms and Conditions.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 14: Account Termination</h4>
                            <p class="mb-3">
                                14.1 You may terminate your account at any time by contacting us or using account deletion features.
                            </p>
                            <p class="mb-3">
                                14.2 We reserve the right to suspend or terminate your account immediately if you violate these Terms and Conditions or engage in fraudulent, illegal, or harmful activities.
                            </p>
                            <p class="mb-3">
                                14.3 Upon termination, your right to use the platform will cease immediately, and we will retain your data only as required by law or for legitimate business purposes.
                            </p>
                            <p class="mb-3">
                                14.4 Provisions that by their nature should survive termination will remain in effect, including data privacy obligations.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 15: Dispute Resolution</h4>
                            <p class="mb-3">
                                15.1 Any disputes arising from these terms or your use of the platform shall be resolved through good faith negotiation.
                            </p>
                            <p class="mb-3">
                                15.2 If negotiation fails, disputes shall be resolved through binding arbitration in accordance with Philippine arbitration laws.
                            </p>
                            <p class="mb-3">
                                15.3 You waive your right to participate in class action lawsuits against ERepair.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 16: Governing Law</h4>
                            <p class="mb-3">
                                16.1 These Terms and Conditions shall be governed by and construed in accordance with the laws of the Republic of the Philippines, including the Data Privacy Act of 2012.
                            </p>
                            <p class="mb-3">
                                16.2 Any legal action or proceeding arising under these terms shall be brought exclusively in the courts of the Philippines.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 17: Contact Information</h4>
                            <p class="mb-3">
                                17.1 For questions about these Terms and Conditions or to exercise your data privacy rights, please contact us at:
                            </p>
                            <p class="mb-3">
                                Email: support@erepair.com<br>
                                Phone: +639060643212<br>
                                Address: Loon, Bohol, Digital City, Philippines
                            </p>
                            <p class="mb-3">
                                17.2 For data privacy concerns, you may also contact the National Privacy Commission at privacy.gov.ph
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 18: Miscellaneous</h4>
                            <p class="mb-3">
                                18.1 If any provision of these terms is found to be unenforceable, the remaining provisions will remain in full effect.
                            </p>
                            <p class="mb-3">
                                18.2 These Terms and Conditions constitute the entire agreement between you and ERepair regarding your use of the platform.
                            </p>
                            <p class="mb-3">
                                18.3 Our failure to enforce any right or provision of these terms shall not constitute a waiver of such right or provision.
                            </p>
                            <p class="mb-3">
                                18.4 You may not assign or transfer your account or these terms without our prior written consent.
                            </p>
                            <p class="mb-3">
                                18.5 We reserve the right to modify these terms at any time. Continued use of the platform after changes constitutes acceptance of the modified terms.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 2: Account Registration and Eligibility</h4>
                            <p class="mb-3">
                                2.1 You must be at least 18 years old to create an account and use our services.
                            </p>
                            <p class="mb-3">
                                2.2 You agree to provide accurate, current, and complete information during registration and to update such information to keep it accurate, current, and complete.
                            </p>
                            <p class="mb-3">
                                2.3 You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
                            </p>
                            <p class="mb-3">
                                2.4 Shop owners must provide valid business permits, identification documents, and accurate business information. We reserve the right to verify all submitted documents.
                            </p>
                            <p class="mb-3">
                                2.5 We reserve the right to reject, suspend, or terminate any account that violates these terms or provides false information.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 3: User Responsibilities</h4>
                            <p class="mb-3">
                                3.1 You agree to use the platform only for lawful purposes and in accordance with these Terms and Conditions.
                            </p>
                            <p class="mb-3">
                                3.2 You will not use the platform to transmit any harmful, offensive, or illegal content.
                            </p>
                            <p class="mb-3">
                                3.3 You will not attempt to gain unauthorized access to the platform, other accounts, or computer systems.
                            </p>
                            <p class="mb-3">
                                3.4 Customers are responsible for accurately describing device issues and providing correct contact information.
                            </p>
                            <p class="mb-3">
                                3.5 Shop owners are responsible for providing quality repair services, accurate pricing, and timely completion of repairs.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 4: Service Booking and Transactions</h4>
                            <p class="mb-3">
                                4.1 Booking requests are subject to shop owner approval and availability.
                            </p>
                            <p class="mb-3">
                                4.2 All pricing and service terms are set by individual shop owners. ERepair is not responsible for pricing disputes.
                            </p>
                            <p class="mb-3">
                                4.3 Cancellation policies are determined by individual shop owners and must be clearly communicated.
                            </p>
                            <p class="mb-3">
                                4.4 You agree to honor confirmed bookings and provide reasonable notice for cancellations.
                            </p>
                            <p class="mb-3">
                                4.5 ERepair acts as a platform connecting customers and repair shops and is not a party to the actual repair service agreement.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 5: Payment and Fees</h4>
                            <p class="mb-3">
                                5.1 Payment terms are agreed upon directly between customers and shop owners.
                            </p>
                            <p class="mb-3">
                                5.2 ERepair may charge platform fees as disclosed at the time of booking. All fees are non-refundable unless otherwise stated.
                            </p>
                            <p class="mb-3">
                                5.3 You are responsible for all applicable taxes related to transactions conducted through the platform.
                            </p>
                            <p class="mb-3">
                                5.4 Disputes regarding payments must be resolved directly between customers and shop owners. ERepair may assist in dispute resolution but is not liable for payment issues.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 6: Intellectual Property</h4>
                            <p class="mb-3">
                                6.1 All content on the ERepair platform, including logos, text, graphics, and software, is the property of ERepair or its licensors and is protected by copyright and trademark laws.
                            </p>
                            <p class="mb-3">
                                6.2 You may not reproduce, distribute, or create derivative works from platform content without our express written permission.
                            </p>
                            <p class="mb-3">
                                6.3 You retain ownership of content you submit but grant ERepair a license to use, display, and distribute such content on the platform.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 7: Privacy and Data Protection</h4>
                            <p class="mb-3">
                                7.1 Your use of the platform is also governed by our Privacy Policy, which explains how we collect, use, and protect your personal information.
                            </p>
                            <p class="mb-3">
                                7.2 You consent to the collection and use of your information as described in our Privacy Policy.
                            </p>
                            <p class="mb-3">
                                7.3 We implement reasonable security measures to protect your data but cannot guarantee absolute security.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 8: Limitation of Liability</h4>
                            <p class="mb-3">
                                8.1 ERepair provides the platform "as is" and "as available" without warranties of any kind, either express or implied.
                            </p>
                            <p class="mb-3">
                                8.2 ERepair is not responsible for the quality, safety, or legality of repair services provided by shop owners.
                            </p>
                            <p class="mb-3">
                                8.3 ERepair shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the platform.
                            </p>
                            <p class="mb-3">
                                8.4 Our total liability to you shall not exceed the amount you paid to ERepair in the 12 months preceding the claim.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 9: Indemnification</h4>
                            <p class="mb-3">
                                9.1 You agree to indemnify and hold harmless ERepair, its officers, directors, employees, and agents from any claims, damages, losses, or expenses arising from your use of the platform or violation of these terms.
                            </p>
                            <p class="mb-3">
                                9.2 This includes but is not limited to claims related to services provided by shop owners, content you submit, or your breach of these Terms and Conditions.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 10: Account Termination</h4>
                            <p class="mb-3">
                                10.1 You may terminate your account at any time by contacting us or using account deletion features.
                            </p>
                            <p class="mb-3">
                                10.2 We reserve the right to suspend or terminate your account immediately if you violate these Terms and Conditions or engage in fraudulent, illegal, or harmful activities.
                            </p>
                            <p class="mb-3">
                                10.3 Upon termination, your right to use the platform will cease immediately, but provisions that by their nature should survive will remain in effect.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 11: Dispute Resolution</h4>
                            <p class="mb-3">
                                11.1 Any disputes arising from these terms or your use of the platform shall be resolved through good faith negotiation.
                            </p>
                            <p class="mb-3">
                                11.2 If negotiation fails, disputes shall be resolved through binding arbitration in accordance with Philippine arbitration laws.
                            </p>
                            <p class="mb-3">
                                11.3 You waive your right to participate in class action lawsuits against ERepair.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 12: Governing Law</h4>
                            <p class="mb-3">
                                12.1 These Terms and Conditions shall be governed by and construed in accordance with the laws of the Republic of the Philippines.
                            </p>
                            <p class="mb-3">
                                12.2 Any legal action or proceeding arising under these terms shall be brought exclusively in the courts of the Philippines.
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 13: Contact Information</h4>
                            <p class="mb-3">
                                13.1 For questions about these Terms and Conditions, please contact us at:
                            </p>
                            <p class="mb-3">
                                Email: support@erepair.com<br>
                                Phone: +639060643212<br>
                                Address: Loon, Bohol, Digital City, Philippines
                            </p>

                            <h4 class="font-bold text-lg text-gray-900 mt-6 mb-3">Article 14: Miscellaneous</h4>
                            <p class="mb-3">
                                14.1 If any provision of these terms is found to be unenforceable, the remaining provisions will remain in full effect.
                            </p>
                            <p class="mb-3">
                                14.2 These Terms and Conditions constitute the entire agreement between you and ERepair regarding your use of the platform.
                            </p>
                            <p class="mb-3">
                                14.3 Our failure to enforce any right or provision of these terms shall not constitute a waiver of such right or provision.
                            </p>
                            <p class="mb-3">
                                14.4 You may not assign or transfer your account or these terms without our prior written consent.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer (Fixed) -->
                <div class="flex justify-end px-6 py-4 border-t border-gray-200 flex-shrink-0">
                    <button @click="showTermsModal = false; termsAccepted = true" 
                            class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        I Agree and Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function stepRegistration() {
            return {
                currentStep: 1,
                userType: 'customer',
                showPassword: false,
                loading: false,
                locationLoading: false,
                map: null,
                marker: null,
                locationStatus: null,
                
                formData: {
                    name: '',
                    email: '',
                    phone: '',
                    password: '',
                    shop_name: '',
                    street_address: '',
                    barangay: '',
                    city: '',
                    state: '',
                    postal_code: '',
                    country: 'Philippines',
                    latitude: null,
                    longitude: null,
                    id_type: '',
                    id_number: '',
                    id_expiry_date: ''
                },
                termsAccepted: false,
                showTermsModal: false,
                
                // Philippine locations data
                philippineProvinces: getPhilippineProvinces(),
                availableCities: [],
                provinceSearch: '',
                citySearch: '',
                showProvinceDropdown: false,
                showCityDropdown: false,
                
                // Computed filtered lists for searchable dropdowns
                get filteredProvinces() {
                    if (!this.provinceSearch) return this.philippineProvinces;
                    const search = this.provinceSearch.toLowerCase();
                    return this.philippineProvinces.filter(p => p.toLowerCase().includes(search));
                },
                
                get filteredCities() {
                    if (!this.citySearch) return this.availableCities;
                    const search = this.citySearch.toLowerCase();
                    return this.availableCities.filter(c => c.toLowerCase().includes(search));
                },
                
                // Validation errors
                phoneError: '',
                emailError: '',
                passwordStrengthLabel: 'Easy',
                passwordStrengthClass: 'text-red-600',
                
                files: {
                    business_permit_file: null,
                    id_file_front: null,
                    id_file_back: null,
                    selfie_file: null,
                    id_file: null  // For customer ID file
                },
                filePreviews: {},

                init() {
                    // Don't initialize map here - wait until step 3 is reached
                },
                
                cleanup() {
                    // Revoke object URLs to prevent memory leaks
                    Object.values(this.filePreviews).forEach(url => {
                        if (url) URL.revokeObjectURL(url);
                    });
                    this.filePreviews = {};
                },
                
                getFilePreview(fileType) {
                    const file = this.files[fileType];
                    if (!file) return '';
                    
                    // If preview already exists, return it
                    if (this.filePreviews[fileType]) {
                        return this.filePreviews[fileType];
                    }
                    
                    // Create new preview URL
                    if (file.type.startsWith('image/')) {
                        this.filePreviews[fileType] = URL.createObjectURL(file);
                        return this.filePreviews[fileType];
                    }
                    
                    return '';
                },
                
                async openCameraForSelfie() {
                    try {
                        // Check if camera is available
                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            Notiflix.Report.failure(
                                'Camera Not Available', 
                                'Your browser does not support camera access. Please use a modern browser with camera support (Chrome, Firefox, Safari, Edge).', 
                                'OK'
                            );
                            return;
                        }
                        
                        // Request camera access
                        const stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { 
                                facingMode: 'user', // Front camera
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            } 
                        });
                        
                        // Create camera modal
                        const modal = document.createElement('div');
                        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75';
                        modal.innerHTML = `
                            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                                <h3 class="text-lg font-semibold mb-2">Take Selfie with ID</h3>
                                <p class="text-sm text-gray-600 mb-4">Hold your ID next to your face. Make sure both are clearly visible in the frame.</p>
                                <div class="relative mb-4 bg-black rounded-lg overflow-hidden">
                                    <video id="camera-preview" autoplay playsinline class="w-full" style="max-height: 400px; display: block;"></video>
                                    <canvas id="camera-canvas" class="hidden"></canvas>
                                </div>
                                <div class="flex gap-2">
                                    <button id="capture-btn" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center justify-center gap-2">
                                        <i class="fas fa-camera"></i>
                                        <span>Capture Photo</span>
                                    </button>
                                    <button id="cancel-camera-btn" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        
                        const video = modal.querySelector('#camera-preview');
                        const canvas = modal.querySelector('#camera-canvas');
                        const captureBtn = modal.querySelector('#capture-btn');
                        const cancelBtn = modal.querySelector('#cancel-camera-btn');
                        
                        video.srcObject = stream;
                        
                        const cleanup = () => {
                            if (stream) {
                                stream.getTracks().forEach(track => track.stop());
                            }
                            if (modal && modal.parentNode) {
                                document.body.removeChild(modal);
                            }
                        };
                        
                        captureBtn.onclick = () => {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0);
                            
                            canvas.toBlob((blob) => {
                                const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                                this.files.selfie_file = file;
                                // Create preview
                                if (this.filePreviews.selfie_file) {
                                    URL.revokeObjectURL(this.filePreviews.selfie_file);
                                }
                                this.filePreviews.selfie_file = URL.createObjectURL(file);
                                
                                // Update the hidden input (for form submission)
                                const input = document.getElementById('shop-owner-selfie-file-input') || document.getElementById('selfie-file-input');
                                if (input) {
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    input.files = dataTransfer.files;
                                }
                                
                                cleanup();
                                Notiflix.Notify.success('Photo captured successfully!', {
                                    timeout: 2000,
                                    position: 'top-right'
                                });
                            }, 'image/jpeg', 0.9);
                        };
                        
                        cancelBtn.onclick = cleanup;
                        
                        // Close modal on outside click
                        modal.onclick = (e) => {
                            if (e.target === modal) {
                                cleanup();
                            }
                        };
                        
                    } catch (error) {
                        console.error('Camera access error:', error);
                        let errorMessage = 'Could not access your camera. ';
                        
                        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                            errorMessage += 'Please allow camera access in your browser settings and try again.';
                        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                            errorMessage += 'No camera found. Please connect a camera and try again.';
                        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                            errorMessage += 'Camera is being used by another application. Please close other apps using the camera and try again.';
                        } else {
                            errorMessage += 'Please check your camera permissions and try again.';
                        }
                        
                        Notiflix.Report.failure(
                            'Camera Access Required', 
                            errorMessage + '<br><br><strong>Note:</strong> You must take a photo using your camera. File upload is not allowed for selfie verification.', 
                            'OK'
                        );
                    }
                },
                
                retakeSelfie() {
                    this.files.selfie_file = null;
                    // Revoke preview URL to free memory
                    if (this.filePreviews.selfie_file) {
                        URL.revokeObjectURL(this.filePreviews.selfie_file);
                        delete this.filePreviews.selfie_file;
                    }
                    // Clear both customer and shop owner input fields
                    const input = document.getElementById('selfie-file-input') || document.getElementById('shop-owner-selfie-file-input');
                    if (input) {
                        input.value = '';
                    }
                },
                
                getCitiesForProvince(province) {
                    if (!province) return [];
                    return getCitiesForProvince(province);
                },
                
                handleProvinceInput(value) {
                    // Update provinceSearch
                    this.provinceSearch = value;
                    
                    // Check if the typed value matches a province exactly
                    const matchedProvince = this.philippineProvinces.find(p => p.toLowerCase() === value.toLowerCase());
                    if (matchedProvince) {
                        this.formData.state = matchedProvince;
                        // Update cities list
                        this.formData.city = '';
                        this.citySearch = '';
                        this.availableCities = getCitiesForProvince(matchedProvince);
                    } else {
                        // Update formData.state with the typed value
                        this.formData.state = value;
                        // Keep the typed value but update cities if it matches partially
                        const partialMatch = this.philippineProvinces.find(p => p.toLowerCase().includes(value.toLowerCase()));
                        if (partialMatch && value.length > 0) {
                            this.availableCities = getCitiesForProvince(partialMatch);
                        } else if (value.length === 0) {
                            this.availableCities = [];
                        }
                    }
                },
                
                handleCityInput(value) {
                    // Update citySearch
                    this.citySearch = value;
                    // Update formData.city
                    this.formData.city = value;
                    
                    // Check if the typed value matches a city exactly
                    const matchedCity = this.availableCities.find(c => c.toLowerCase() === value.toLowerCase());
                    if (matchedCity) {
                        this.formData.city = matchedCity;
                        this.citySearch = matchedCity;
                    }
                },
                
                async updateMapFromAddress() {
                    // Update map based on address fields
                    if (!this.map) return;
                    
                    const address = [
                        this.formData.street_address,
                        this.formData.city,
                        this.formData.state,
                        'Philippines'
                    ].filter(Boolean).join(', ');
                    
                    console.log('Updating map from address:', address);
                    
                    // If no specific address, center on province
                    if (!address || address === 'Philippines') {
                        if (this.formData.state && this.philippineProvinces.includes(this.formData.state)) {
                            const provinceCoords = this.getProvinceCoordinates(this.formData.state);
                            if (provinceCoords) {
                                this.map.setView([provinceCoords.lat, provinceCoords.lng], 10);
                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                    this.marker = null;
                                }
                            }
                        }
                        return;
                    }
                    
                    // Try Photon API first for geocoding (faster)
                    try {
                        const photonUrl = `https://photon.komoot.io/api?q=${encodeURIComponent(address)}&limit=1`;
                        const photonResponse = await fetch(photonUrl, { headers: { 'Accept': 'application/json' } });
                        
                        if (photonResponse.ok) {
                            const photonData = await photonResponse.json();
                            if (photonData && photonData.features && photonData.features.length > 0) {
                                const coords = photonData.features[0].geometry.coordinates;
                                const lng = coords[0];
                                const lat = coords[1];
                                
                                console.log('Photon geocoding result:', lat, lng);
                                
                                this.map.setView([lat, lng], 15);
                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                }
                                this.marker = L.marker([lat, lng]).addTo(this.map);
                                
                                // Update coordinates
                                this.formData.latitude = lat;
                                this.formData.longitude = lng;
                                return;
                            }
                        }
                    } catch (error) {
                        console.log('Photon geocoding failed:', error);
                    }
                    
                    // Fallback to Nominatim
                    try {
                        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&zoom=15`;
                        const response = await fetch(url, { 
                            headers: { 
                                'Accept': 'application/json',
                                'User-Agent': 'ERepair/1.0'
                            } 
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            if (data && data.length > 0) {
                                const lat = parseFloat(data[0].lat);
                                const lng = parseFloat(data[0].lon);
                                
                                console.log('Nominatim geocoding result:', lat, lng);
                                
                                this.map.setView([lat, lng], 15);
                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                }
                                this.marker = L.marker([lat, lng]).addTo(this.map);
                                
                                // Update coordinates
                                this.formData.latitude = lat;
                                this.formData.longitude = lng;
                            } else {
                                console.log('No results found for address');
                            }
                        }
                    } catch (error) {
                        console.error('Error geocoding address:', error);
                    }
                },
                
                getProvinceCoordinates(province) {
                    // Approximate coordinates for major Philippine provinces
                    const provinceCoords = {
                        'Metro Manila': { lat: 14.5995, lng: 120.9842 },
                        'Cebu': { lat: 10.3157, lng: 123.8854 },
                        'Davao del Sur': { lat: 7.1907, lng: 125.4553 },
                        'Bohol': { lat: 9.8499, lng: 124.1435 },
                        'Laguna': { lat: 14.2669, lng: 121.4668 },
                        'Cavite': { lat: 14.4791, lng: 120.8970 },
                        'Pampanga': { lat: 15.0794, lng: 120.6200 },
                        'Bulacan': { lat: 14.7943, lng: 120.8799 },
                        'Pangasinan': { lat: 16.0439, lng: 120.3330 },
                        'Rizal': { lat: 14.6507, lng: 121.1510 },
                        'Quezon': { lat: 14.0367, lng: 121.6222 },
                        'Batangas': { lat: 13.7565, lng: 121.0583 },
                        'Negros Occidental': { lat: 10.6407, lng: 122.9689 },
                        'Iloilo': { lat: 10.7202, lng: 122.5621 },
                        'Leyte': { lat: 11.3381, lng: 124.9142 },
                        'Zamboanga del Sur': { lat: 7.8288, lng: 123.4371 },
                        'Davao del Norte': { lat: 7.5619, lng: 125.6530 },
                        'Cotabato': { lat: 7.2048, lng: 124.2464 },
                        'Palawan': { lat: 10.3592, lng: 119.0102 },
                        'Albay': { lat: 13.2316, lng: 123.5964 },
                        'Camarines Sur': { lat: 13.6193, lng: 123.1804 },
                        'Isabela': { lat: 17.0000, lng: 121.7833 },
                        'Cagayan': { lat: 17.6333, lng: 121.7167 },
                        'Nueva Ecija': { lat: 15.5786, lng: 120.9826 },
                        'Tarlac': { lat: 15.4869, lng: 120.5908 },
                        'Zambales': { lat: 15.3082, lng: 120.0169 },
                        'Bataan': { lat: 14.6965, lng: 120.4200 },
                        'Occidental Mindoro': { lat: 13.0000, lng: 120.8333 },
                        'Oriental Mindoro': { lat: 13.0000, lng: 121.0833 },
                        'Ilocos Norte': { lat: 18.1987, lng: 120.5906 },
                        'Ilocos Sur': { lat: 17.5861, lng: 120.3869 },
                        'La Union': { lat: 16.5000, lng: 120.3333 },
                        'Aurora': { lat: 15.7953, lng: 121.4680 },
                        'Abra': { lat: 17.6000, lng: 120.7167 },
                        'Benguet': { lat: 16.4023, lng: 120.5960 },
                        'Ifugao': { lat: 16.8333, lng: 121.1667 },
                        'Kalinga': { lat: 17.4167, lng: 121.4167 },
                        'Mountain Province': { lat: 17.0833, lng: 120.9167 },
                        'Apayao': { lat: 18.0333, lng: 121.0833 },
                        'Nueva Vizcaya': { lat: 16.6167, lng: 121.1167 },
                        'Quirino': { lat: 16.2667, lng: 121.5500 },
                        'Batanes': { lat: 20.4167, lng: 121.9500 },
                        'Marinduque': { lat: 13.4833, lng: 121.9167 },
                        'Romblon': { lat: 12.5833, lng: 122.2833 },
                        'Masbate': { lat: 12.3697, lng: 123.6233 },
                        'Sorsogon': { lat: 12.9742, lng: 124.0028 },
                        'Camarines Norte': { lat: 14.1333, lng: 122.9833 },
                        'Catanduanes': { lat: 13.6167, lng: 124.2167 },
                        'Aklan': { lat: 11.6544, lng: 122.3328 },
                        'Antique': { lat: 11.1667, lng: 122.0500 },
                        'Capiz': { lat: 11.5833, lng: 122.7500 },
                        'Guimaras': { lat: 10.5667, lng: 122.5833 },
                        'Negros Oriental': { lat: 9.3072, lng: 123.3067 },
                        'Siquijor': { lat: 9.2000, lng: 123.5500 },
                        'Biliran': { lat: 11.5833, lng: 124.4833 },
                        'Eastern Samar': { lat: 11.5000, lng: 125.5000 },
                        'Northern Samar': { lat: 12.5000, lng: 124.6667 },
                        'Samar': { lat: 11.8667, lng: 125.0000 },
                        'Southern Leyte': { lat: 10.2500, lng: 125.1667 },
                        'Zamboanga del Norte': { lat: 8.4667, lng: 123.4167 },
                        'Zamboanga Sibugay': { lat: 7.7167, lng: 122.9667 },
                        'Bukidnon': { lat: 8.1500, lng: 125.1167 },
                        'Camiguin': { lat: 9.1667, lng: 124.7167 },
                        'Lanao del Norte': { lat: 8.0833, lng: 124.0167 },
                        'Misamis Occidental': { lat: 8.1500, lng: 123.8500 },
                        'Misamis Oriental': { lat: 8.9500, lng: 125.0000 },
                        'Davao de Oro': { lat: 7.6067, lng: 125.9772 },
                        'Davao Occidental': { lat: 6.0833, lng: 125.5833 },
                        'Davao Oriental': { lat: 7.0833, lng: 126.3333 },
                        'Sarangani': { lat: 5.8667, lng: 125.2833 },
                        'South Cotabato': { lat: 6.2667, lng: 125.0000 },
                        'Sultan Kudarat': { lat: 6.5833, lng: 124.7000 },
                        'Agusan del Norte': { lat: 9.0833, lng: 125.5833 },
                        'Agusan del Sur': { lat: 8.3333, lng: 125.7667 },
                        'Dinagat Islands': { lat: 10.0833, lng: 125.5833 },
                        'Surigao del Norte': { lat: 9.7167, lng: 125.4167 },
                        'Surigao del Sur': { lat: 8.8833, lng: 126.0333 },
                        'Basilan': { lat: 6.7167, lng: 122.0333 },
                        'Lanao del Sur': { lat: 7.8833, lng: 124.4167 },
                        'Maguindanao del Norte': { lat: 7.2167, lng: 124.2667 },
                        'Maguindanao del Sur': { lat: 6.8333, lng: 124.5333 },
                        'Sulu': { lat: 6.0000, lng: 121.0000 },
                        'Tawi-Tawi': { lat: 5.0833, lng: 119.9167 }
                    };
                    
                    return provinceCoords[province] || null;
                },

                validatePhone(event) {
                    const value = event.target.value;
                    // Remove any non-numeric characters
                    const numericValue = value.replace(/[^0-9]/g, '');
                    
                    // Limit to 11 digits maximum
                    const limitedValue = numericValue.substring(0, 11);
                    event.target.value = limitedValue;
                    this.formData.phone = limitedValue;
                    
                    // Philippine mobile number validation: starts with 09, exactly 11 digits
                    if (limitedValue.length === 0) {
                        this.phoneError = '';
                    } else if (!limitedValue.startsWith('09')) {
                        this.phoneError = 'Phone number must start with 09';
                    } else if (limitedValue.length !== 11) {
                        this.phoneError = 'Phone number must be exactly 11 digits';
                    } else {
                        this.phoneError = '';
                    }
                },

                filterDigitKeydown(e) {
                    const allowedKeys = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
                    if (allowedKeys.includes(e.key)) return; 
                    // Allow Ctrl/Cmd + A/C/V/X
                    if ((e.ctrlKey || e.metaKey) && ['a','c','v','x'].includes(e.key.toLowerCase())) return;
                    
                    // Only allow digits
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Prevent input if already at 11 digits
                    if (this.formData.phone && this.formData.phone.length >= 11) {
                        e.preventDefault();
                    }
                },

                filterDigitPaste(e, targetField) {
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numeric = paste.replace(/[^0-9]/g, '');
                    if (numeric !== paste) {
                        e.preventDefault();
                        // Insert cleaned value
                        this.formData[targetField] = (this.formData[targetField] || '') + numeric;
                    }
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
                    } else if (fieldType === 'name') {
                        // Name: only letters, spaces, and basic punctuation
                        allowedPattern = /^[a-zA-Z\s.-]$/;
                    } else if (fieldType === 'address') {
                        // Address: letters, numbers, spaces, and safe address punctuation (comma, period, hyphen, apostrophe, slash, parentheses, hash for unit numbers)
                        allowedPattern = /^[a-zA-Z0-9\s.,'-/()#]$/;
                    } else if (fieldType === 'postal_code') {
                        // Postal code: only alphanumeric, spaces, and hyphens
                        allowedPattern = /^[a-zA-Z0-9\s-]$/;
                    } else if (fieldType === 'id_number') {
                        // ID Number: only alphanumeric, spaces, and hyphens (common in ID formats)
                        allowedPattern = /^[a-zA-Z0-9\s-]$/;
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
                    
                    // Map fieldType to formData property based on x-model binding
                    if (fieldType === 'address') {
                        // For address fields, check which field this is based on x-model
                        const xModel = input.getAttribute('x-model');
                        if (xModel === 'formData.street_address') {
                            this.formData.street_address = newValue;
                        } else if (xModel === 'formData.barangay') {
                            this.formData.barangay = newValue;
                        } else if (xModel === 'provinceSearch') {
                            this.provinceSearch = newValue;
                            this.formData.state = newValue;
                            // Trigger province input handler
                            this.handleProvinceInput(newValue);
                        } else if (xModel === 'citySearch') {
                            this.citySearch = newValue;
                            this.formData.city = newValue;
                            // Trigger city input handler
                            this.handleCityInput(newValue);
                        }
                    } else if (fieldType === 'postal_code') {
                        this.formData.postal_code = newValue;
                    } else if (fieldType === 'id_number') {
                        this.formData.id_number = newValue;
                    } else {
                        // For other fields, try to update formData directly
                        const xModel = input.getAttribute('x-model');
                        if (xModel && xModel.startsWith('formData.')) {
                            const field = xModel.replace('formData.', '');
                            this.formData[field] = newValue;
                        }
                    }
                    
                    // Trigger input event to update Alpine.js reactivity and any debounced handlers
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    
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
                    } else if (fieldType === 'name') {
                        // Name: only letters, spaces, and basic punctuation
                        allowedPattern = /^[a-zA-Z\s.-]+$/;
                    } else if (fieldType === 'address') {
                        // Address: letters, numbers, spaces, and safe address punctuation (comma, period, hyphen, apostrophe, slash, parentheses, hash for unit numbers)
                        allowedPattern = /^[a-zA-Z0-9\s.,'-/()#]+$/;
                    } else if (fieldType === 'postal_code') {
                        // Postal code: only alphanumeric, spaces, and hyphens
                        allowedPattern = /^[a-zA-Z0-9\s-]+$/;
                    } else if (fieldType === 'id_number') {
                        // ID Number: only alphanumeric, spaces, and hyphens (common in ID formats)
                        allowedPattern = /^[a-zA-Z0-9\s-]+$/;
                    } else {
                        // Default: only alphanumeric and basic punctuation
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    }
                    
                    // Remove any characters that don't match the allowed pattern
                    return input.split('').filter(char => allowedPattern.test(char)).join('');
                },


                validateEmail(event) {
                    const value = event.target.value;
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (value && !emailRegex.test(value)) {
                        this.emailError = 'Please enter a valid email address (e.g., user@gmail.com)';
                    } else {
                        this.emailError = '';
                    }
                },

                updatePasswordStrength(value) {
                    const lengthScore = value.length >= 8 ? 1 : 0;
                    const upper = /[A-Z]/.test(value) ? 1 : 0;
                    const lower = /[a-z]/.test(value) ? 1 : 0;
                    const digit = /\d/.test(value) ? 1 : 0;
                    const special = /[^A-Za-z0-9]/.test(value) ? 1 : 0;
                    const score = lengthScore + upper + lower + digit + special;
                    if (score <= 2) {
                        this.passwordStrengthLabel = 'Easy';
                        this.passwordStrengthClass = 'text-red-600';
                    } else if (score === 3 || score === 4) {
                        this.passwordStrengthLabel = 'Hard';
                        this.passwordStrengthClass = 'text-amber-600';
                    } else {
                        this.passwordStrengthLabel = 'Strong';
                        this.passwordStrengthClass = 'text-green-600';
                    }
                },

                initializeMap() {
                    // Check if map element exists
                    const mapElement = document.getElementById('map');
                    if (!mapElement) {
                        console.error('Map element not found');
                        return;
                    }
                    
                    // Initialize map with default location (Manila, Philippines)
                    this.map = L.map('map').setView([14.5995, 120.9842], 14);
                    
                    // Add OpenStreetMap tiles (free)
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(this.map);
                    
                    // Create draggable marker at default location
                    this.marker = L.marker([14.5995, 120.9842], { draggable: true }).addTo(this.map);
                    
                    // Handle marker drag end - update address when marker is dragged
                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.updateLocationFromCoords(pos.lat, pos.lng);
                    });
                    
                    // Add click event to map - move marker and update address
                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                        this.updateLocationFromCoords(e.latlng.lat, e.latlng.lng);
                    });
                    
                    // Call invalidateSize after a short delay to ensure the container is visible
                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 100);
                },

                nextStep() {
                    if (this.canProceed()) {
                        this.currentStep++;
                        if (this.currentStep === 3 && this.userType === 'shop_owner') {
                            // Initialize map when reaching location step for shop owners
                            this.$nextTick(() => {
                                if (!this.map) {
                                    this.initializeMap();
                                }
                                setTimeout(() => {
                                    if (this.map) {
                                        this.map.invalidateSize();
                                    }
                                }, 200);
                            });
                        }
                    }
                },

                previousStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                    }
                },

                canProceed() {
                    switch (this.currentStep) {
                        case 1:
                            return this.userType !== '';
                        case 2:
                            // Normalize inputs
                            const name = (this.formData.name || '').trim();
                            const email = (this.formData.email || '').trim();
                            const phone = (this.formData.phone || '').trim();
                            const password = this.formData.password || '';

                            // Validate email/phone defensively here too (in case oninput didn't run)
                            const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                            const phoneValid = /^09[0-9]{9}$/.test(phone); // Philippine mobile: starts with 09, exactly 11 digits

                            // Update error hints
                            this.emailError = email && !emailValid ? 'Please enter a valid email address (e.g., user@gmail.com)' : '';
                            this.phoneError = phone && !phoneValid ? 'Phone number must start with 09 and be exactly 11 digits' : '';

                            const hasBasicFields = name && email && phone && password;
                            const hasShopFields = this.userType === 'customer' || (this.formData.shop_name || '').trim();
                            const hasNoValidationErrors = emailValid && phoneValid;

                            return hasBasicFields && hasShopFields && hasNoValidationErrors;
                        case 3:
                            if (this.userType === 'customer') {
                                // For customers, step 3 is documents - require ID file and selfie
                                return this.files.id_file && this.files.selfie_file;
                            } else {
                                // For shop owners, step 3 is location
                                return this.formData.street_address && this.formData.barangay && this.formData.city && this.formData.state && 
                                       this.formData.postal_code && this.formData.country;
                            }
                        case 4:
                            if (this.userType === 'customer') {
                                // For customers, step 4 is review
                                return true;
                            } else {
                                // For shop owners, step 4 is documents
                                return this.files.business_permit_file && 
                                       this.formData.id_type && 
                                       this.formData.id_number &&
                                       this.files.id_file_front && 
                                       this.files.id_file_back &&
                                       this.files.selfie_file;
                            }
                        case 5:
                            // Only for shop owners, step 5 is review
                            return true;
                        default:
                            return false;
                    }
                },

                async getCurrentLocation() {
                    this.locationLoading = true;
                    this.locationStatus = null;
                    
                    if (!navigator.geolocation) {
                        this.locationStatus = {
                            success: false,
                            message: 'Geolocation is not supported by this browser.'
                        };
                        this.locationLoading = false;
                        return;
                    }
                    
                    try {
                        const position = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            });
                        });
                        
                        const { latitude, longitude } = position.coords;
                        await this.updateLocationFromCoords(latitude, longitude);
                        
                        this.locationStatus = {
                            success: true,
                            message: 'Location found successfully!'
                        };
                        
                    } catch (error) {
                        this.locationStatus = {
                            success: false,
                            message: `Error getting location: ${error.message}`
                        };
                    } finally {
                        this.locationLoading = false;
                    }
                },

                async updateLocationFromCoords(lat, lng) {
                    this.formData.latitude = lat;
                    this.formData.longitude = lng;
                    
                    // Update map view
                    this.map.setView([lat, lng], 15);
                    
                    // Update marker position (if marker exists, just move it; otherwise create new one)
                    if (this.marker) {
                        this.marker.setLatLng([lat, lng]);
                    } else {
                        this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                        // Re-attach dragend handler if marker was recreated
                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.updateLocationFromCoords(pos.lat, pos.lng);
                        });
                    }
                    
                    this.locationStatus = { success: true, message: 'Location found successfully!' };

                    // Reverse geocode for shop owners to auto-fill address fields (editable)
                    if (this.userType === 'shop_owner') {
                        try {
                            this.locationStatus = { success: true, message: 'Fetching address from location...' };
                            await this.reverseGeocode(lat, lng);
                            
                            // Check if we got at least some data
                            const hasAddress = this.formData.street_address || this.formData.city || this.formData.state;
                            if (hasAddress) {
                                this.locationStatus = { success: true, message: 'Address auto-filled successfully! You can edit if needed.' };
                            } else {
                                this.locationStatus = { success: true, message: 'Location detected. Please complete the address fields.' };
                            }
                        } catch (e) {
                            console.error('Reverse geocoding error:', e);
                            this.locationStatus = { 
                                success: false, 
                                message: 'Could not auto-fill address. The map location is saved. Please enter address manually.' 
                            };
                        }
                    }
                },

                updateLocationFromMap(lat, lng) {
                    this.updateLocationFromCoords(lat, lng);
                },

                

                async handleFileSelect(event, fileType) {
                    // Prevent file selection for selfie - must use camera
                    if (fileType === 'selfie_file') {
                        event.preventDefault();
                        event.target.value = '';
                        Notiflix.Report.warning(
                            'Camera Required', 
                            'You must take a selfie using your camera. File upload is not allowed for selfie verification. Please click "Take Selfie with ID" button to use your camera.', 
                            'OK'
                        );
                        return;
                    }
                    
                    const file = event.target.files[0];
                    if (!file) {
                        this.files[fileType] = null;
                        return;
                    }
                    
                    // Check file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        Notiflix.Report.failure('File Too Large', 'File size must be less than 5MB', 'OK');
                        event.target.value = '';
                        this.files[fileType] = null;
                        return;
                    }
                    
                    // For PDFs, just accept them (server will handle validation)
                    if (file.type === 'application/pdf') {
                        this.files[fileType] = file;
                        return;
                    }
                    
                    // For images, validate dimensions and aspect ratio
                    if (file.type.startsWith('image/')) {
                        try {
                            const validation = await this.validateDocumentImage(file, fileType);
                            if (!validation.valid) {
                                Notiflix.Confirm.show(
                                    'Document Validation',
                                    `${validation.error}\n\nPlease ensure you're uploading a clear image of the actual document.`,
                                    'I understand, continue anyway',
                                    'Cancel',
                                    () => {
                                        this.files[fileType] = file;
                                    },
                                    () => {
                                        event.target.value = '';
                                        this.files[fileType] = null;
                                    }
                                );
                            } else {
                                this.files[fileType] = file;
                            }
                        } catch (error) {
                            console.error('Image validation error:', error);
                            // If validation fails due to technical issues, still allow the file
                            this.files[fileType] = file;
                        }
                    } else {
                        Notiflix.Report.failure('Invalid File Type', 'Please upload JPG, PNG, or PDF files only', 'OK');
                        event.target.value = '';
                        this.files[fileType] = null;
                    }
                },
                
                validateDocumentImage(file, fileType) {
                    return new Promise((resolve) => {
                        const img = new Image();
                        const reader = new FileReader();
                        
                        reader.onload = (e) => {
                            img.onload = () => {
                                const width = img.width;
                                const height = img.height;
                                const aspectRatio = width / height;
                                
                                const errors = [];
                                
                                // Check minimum dimensions
                                if (width < 300 || height < 200) {
                                    errors.push('Image is too small. Please upload a clear, high-resolution image (minimum 300x200 pixels).');
                                }
                                
                                // Check aspect ratio based on document type
                                if (fileType.includes('id_file')) {
                                    // IDs can be portrait or landscape (driver's licenses are often landscape)
                                    if (aspectRatio < 0.55 || aspectRatio > 1.9) {
                                        errors.push('Image does not appear to be an ID document. Please ensure you\'re uploading a clear image of your actual ID.');
                                    }
                                } else if (fileType === 'business_permit_file') {
                                    // Permits can be portrait or landscape-oriented (accept wider range)
                                    if (aspectRatio < 0.6 || aspectRatio > 2.0) {
                                        errors.push('Image does not appear to be a business permit. Please ensure you\'re uploading a clear image of the actual document.');
                                    }
                                }
                                
                                resolve({
                                    valid: errors.length === 0,
                                    error: errors.join(' ')
                                });
                            };
                            
                            img.onerror = () => {
                                resolve({
                                    valid: false,
                                    error: 'Could not load image. Please ensure the file is a valid image.'
                                });
                            };
                            
                            img.src = e.target.result;
                        };
                        
                        reader.onerror = () => {
                            resolve({
                                valid: false,
                                error: 'Could not read file.'
                            });
                        };
                        
                        reader.readAsDataURL(file);
                    });
                },

                async reverseGeocode(lat, lng) {
                    console.log('Reverse geocoding for:', lat, lng);
                    
                    // Use Nominatim API with addressdetails=1 for complete PH address structure
                    try {
                        const url = `https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=${lat}&lon=${lng}`;
                        console.log('Trying Nominatim API...');
                        const response = await fetch(url, { 
                            headers: { 
                                'Accept': 'application/json',
                                'User-Agent': 'ERepair/1.0'
                            } 
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            console.log('Nominatim API full response:', data);
                            
                            if (data && data.address) {
                                const addr = data.address;
                                console.log('Nominatim address object:', addr);
                                
                                this.populateAddressFields(addr);
                                return;
                            }
                        }
                    } catch (error) {
                        console.log('Nominatim API failed:', error.message);
                    }
                    
                    // Fallback to Photon API
                    try {
                        const url = `https://photon.komoot.io/reverse?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`;
                        console.log('Trying Photon API...');
                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        
                        if (response.ok) {
                            const data = await response.json();
                            console.log('Photon API full response:', data);
                            
                            // Photon returns features array
                            if (data && data.features && data.features.length > 0) {
                                const props = data.features[0].properties || {};
                                console.log('Photon properties:', props);
                                
                                this.populateAddressFields(props);
                                return;
                            }
                        }
                    } catch (error) {
                        console.log('Photon API failed:', error.message);
                    }
                    
                    // If both APIs fail, throw error
                    throw new Error('Both geocoding APIs failed');
                },

                populateAddressFields(addr) {
                    if (!addr) return;
                    console.log('Populating address fields with:', addr);
                    
                    // Helper function to clean and validate address values
                    const cleanValue = (value) => {
                        if (!value) return '';
                        return value.toString().trim().replace(/\s+/g, ' ');
                    };
                    
                    // Helper function to check if value exists and is not empty
                    const hasValue = (value) => {
                        return value && cleanValue(value).length > 0;
                    };
                    
                    // STREET - Enhanced extraction with priority order and conflict avoidance
                    // Priority: road > street > residential > pedestrian > path > footway > cycleway
                    // Exclude: village, suburb, hamlet, neighbourhood (these are for barangay)
                    let streetAddress = '';
                    const streetCandidates = [
                        addr.road,
                        addr.street,
                        addr.residential,
                        addr.pedestrian,
                        addr.path,
                        addr.footway,
                        addr.cycleway,
                        addr.track,
                        addr.service,
                        addr.quarter,
                        addr.neighbourhood // Only if not used for barangay
                    ];
                    
                    for (const candidate of streetCandidates) {
                        if (hasValue(candidate)) {
                            streetAddress = cleanValue(candidate);
                            break;
                        }
                    }
                    
                    // If we have house_number, combine it with road for better accuracy
                    if (hasValue(addr.house_number) && hasValue(addr.road)) {
                        streetAddress = `${cleanValue(addr.house_number)} ${cleanValue(addr.road)}`;
                    } else if (hasValue(addr.house_number) && !streetAddress) {
                        streetAddress = cleanValue(addr.house_number);
                    }
                    
                    this.formData.street_address = streetAddress;
                    
                    // BARANGAY - PH-specific detection with priority and conflict resolution
                    // Priority: village > hamlet > suburb > neighbourhood > quarter > locality
                    // Only use if not already used for street
                    let barangay = '';
                    const barangayCandidates = [
                        addr.village,
                        addr.hamlet,
                        addr.suburb,
                        addr.neighbourhood,
                        addr.quarter,
                        addr.locality,
                        addr.subdivision,
                        addr.city_district
                    ];
                    
                    for (const candidate of barangayCandidates) {
                        if (hasValue(candidate)) {
                            const cleaned = cleanValue(candidate);
                            // Avoid conflict: don't use if it's the same as street address
                            if (cleaned.toLowerCase() !== streetAddress.toLowerCase()) {
                                barangay = cleaned;
                                break;
                            }
                        }
                    }
                    
                    // If neighbourhood was used for street, try other barangay fields
                    if (!barangay && streetAddress && addr.neighbourhood && 
                        cleanValue(addr.neighbourhood).toLowerCase() === streetAddress.toLowerCase()) {
                        // Try alternative barangay fields
                        const altBarangayCandidates = [
                            addr.village,
                            addr.hamlet,
                            addr.suburb,
                            addr.locality,
                            addr.subdivision
                        ];
                        for (const candidate of altBarangayCandidates) {
                            if (hasValue(candidate)) {
                                barangay = cleanValue(candidate);
                                break;
                            }
                        }
                    }
                    
                    this.formData.barangay = barangay;
                    
                    // CITY / MUNICIPALITY - Enhanced extraction with priority order
                    // Priority: city > town > municipality > city_district > county > district
                    let city = '';
                    const cityCandidates = [
                        addr.city,
                        addr.town,
                        addr.municipality,
                        addr.city_district,
                        addr.county,
                        addr.district,
                        addr.region // Fallback for some regions
                    ];
                    
                    for (const candidate of cityCandidates) {
                        if (hasValue(candidate)) {
                            const cleaned = cleanValue(candidate);
                            // Avoid conflict: don't use if it's the same as barangay
                            if (cleaned.toLowerCase() !== barangay.toLowerCase()) {
                                city = cleaned;
                                break;
                            }
                        }
                    }
                    
                    this.formData.city = city;
                    this.citySearch = city;
                    
                    // PROVINCE - Enhanced extraction with priority and normalization
                    // Priority: state > state_district > region > province
                    let province = '';
                    const provinceCandidates = [
                        addr.state,
                        addr.state_district,
                        addr.region,
                        addr.province
                    ];
                    
                    for (const candidate of provinceCandidates) {
                        if (hasValue(candidate)) {
                            province = cleanValue(candidate);
                            break;
                        }
                    }
                    
                    // PROVINCE MATCHING - Enhanced with fuzzy matching and normalization
                    if (province) {
                        // Normalize province name (remove common suffixes, handle variations)
                        const normalizeProvince = (name) => {
                            return name.toLowerCase()
                                .replace(/\s+province$/i, '')
                                .replace(/\s+/g, ' ')
                                .trim();
                        };
                        
                        const normalizedProvince = normalizeProvince(province);
                        
                        // Try exact match first
                        let matchedProvince = this.philippineProvinces.find(p => 
                            p.toLowerCase() === normalizedProvince
                        );
                        
                        // If no exact match, try partial/fuzzy matching
                        if (!matchedProvince) {
                            matchedProvince = this.philippineProvinces.find(p => {
                                const normalizedP = normalizeProvince(p);
                                return normalizedP.includes(normalizedProvince) || 
                                       normalizedProvince.includes(normalizedP);
                            });
                        }
                        
                        // If still no match, try case-insensitive contains
                        if (!matchedProvince) {
                            matchedProvince = this.philippineProvinces.find(p => 
                                p.toLowerCase().includes(normalizedProvince) ||
                                normalizedProvince.includes(p.toLowerCase())
                            );
                        }
                        
                        if (matchedProvince) {
                            this.formData.state = matchedProvince;
                            this.provinceSearch = matchedProvince;
                            this.availableCities = getCitiesForProvince(matchedProvince);
                            
                            // Enhanced city matching with fuzzy search
                            if (this.formData.city && this.availableCities.length > 0) {
                                const normalizedCity = this.formData.city.toLowerCase().trim();
                                
                                // Try exact match first
                                let matchedCity = this.availableCities.find(c => 
                                    c.toLowerCase() === normalizedCity
                                );
                                
                                // If no exact match, try partial matching
                                if (!matchedCity) {
                                    matchedCity = this.availableCities.find(c => {
                                        const normalizedC = c.toLowerCase();
                                        return normalizedC.includes(normalizedCity) ||
                                               normalizedCity.includes(normalizedC);
                                    });
                                }
                                
                                if (matchedCity) {
                                    this.formData.city = matchedCity;
                                    this.citySearch = matchedCity;
                                }
                            }
                        } else {
                            // Keep the original province value if no match found
                            this.formData.state = province;
                            this.provinceSearch = province;
                            this.availableCities = [];
                        }
                    } else {
                        this.formData.state = '';
                        this.provinceSearch = '';
                        this.availableCities = [];
                    }
                    
                    // POSTAL CODE - Enhanced extraction with validation
                    let postalCode = '';
                    const postalCandidates = [
                        addr.postcode,
                        addr.postal_code,
                        addr.postal
                    ];
                    
                    for (const candidate of postalCandidates) {
                        if (hasValue(candidate)) {
                            postalCode = cleanValue(candidate);
                            // Validate Philippine postal code format (4 digits)
                            if (/^\d{4}$/.test(postalCode)) {
                                break;
                            }
                        }
                    }
                    
                    this.formData.postal_code = postalCode;
                    
                    // COUNTRY - Enhanced with normalization
                    let country = '';
                    const countryCandidates = [
                        addr.country,
                        addr.country_code
                    ];
                    
                    for (const candidate of countryCandidates) {
                        if (hasValue(candidate)) {
                            country = cleanValue(candidate);
                            break;
                        }
                    }
                    
                    // Normalize country name for Philippines
                    if (country) {
                        const normalizedCountry = country.toLowerCase();
                        if (normalizedCountry.includes('philippine') || normalizedCountry === 'ph') {
                            country = 'Philippines';
                        }
                    } else {
                        country = 'Philippines'; // Default
                    }
                    
                    this.formData.country = country;
                    
                    console.log('Form data after population:', {
                        street_address: this.formData.street_address,
                        barangay: this.formData.barangay,
                        city: this.formData.city,
                        state: this.formData.state,
                        postal_code: this.formData.postal_code,
                        country: this.formData.country
                    });
                },

                getFullAddress() {
                    const barangayPart = this.formData.barangay ? `${this.formData.barangay}, ` : '';
                    return `${this.formData.street_address}, ${barangayPart}${this.formData.city}, ${this.formData.state} ${this.formData.postal_code}, ${this.formData.country}`;
                },

                async submitRegistration() {
                    // Validate terms acceptance
                    if (!this.termsAccepted) {
                        Notiflix.Report.warning('Terms and Conditions Required', 'Please read and accept the Terms and Conditions to create your account.', 'OK');
                        return;
                    }
                    
                    this.loading = true;
                    
                    try {
                        if (this.userType === 'customer') {
                            await this.submitCustomerRegistration();
                        } else {
                            await this.submitShopOwnerRegistration();
                        }
                    } catch (error) {
                        console.error('Registration error:', error);
                        Notiflix.Report.failure('Registration Failed', 'An error occurred during registration. Please try again.', 'OK');
                    } finally {
                        this.loading = false;
                    }
                },

                async submitCustomerRegistration() {
                    // Validate required files
                    if (!this.files.id_file || !this.files.selfie_file) {
                        Notiflix.Report.failure('Missing Documents', 'Please upload both ID picture and selfie with ID', 'OK');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('name', this.formData.name);
                    formData.append('email', this.formData.email);
                    formData.append('phone', this.formData.phone);
                    formData.append('password', this.formData.password);
                    formData.append('address', this.formData.address || '');
                    if (this.formData.latitude) formData.append('latitude', this.formData.latitude);
                    if (this.formData.longitude) formData.append('longitude', this.formData.longitude);
                    formData.append('id_file', this.files.id_file);
                    formData.append('selfie_file', this.files.selfie_file);
                    
                    const response = await fetch('../backend/api/register-customer.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        localStorage.setItem('pending_verify_email', this.formData.email);
                        Notiflix.Report.success('Account Created!', 'We sent a verification link to your email. Please verify to continue.', 'Go to Verification', () => {
                            window.location.href = 'verification/verify-email.php';
                        });
                    } else {
                        Notiflix.Report.failure('Registration Failed', data.error || 'Something went wrong', 'OK');
                    }
                },

                async submitShopOwnerRegistration() {
                    const formData = new FormData();
                    
                    // Add form fields (exclude shop_phone as we use phone instead)
                    Object.keys(this.formData).forEach(key => {
                        if (key !== 'shop_phone' && this.formData[key] !== null && this.formData[key] !== '') {
                            formData.append(key, this.formData[key]);
                        }
                    });
                    
                    // Add address as a single field
                    formData.append('shop_address', this.getFullAddress());
                    
                    // Add files
                    formData.append('business_permit_file', this.files.business_permit_file);
                    formData.append('id_type', this.formData.id_type);
                    formData.append('id_number', this.formData.id_number);
                    formData.append('id_expiry_date', this.formData.id_expiry_date || '');
                    formData.append('id_file_front', this.files.id_file_front);
                    formData.append('id_file_back', this.files.id_file_back);
                    formData.append('selfie_file', this.files.selfie_file);

                    const response = await fetch('../backend/api/register-shop-owner.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        localStorage.setItem('pending_verify_email', this.formData.email);
                        Notiflix.Report.success('Account Created!', 'We sent a verification link. Your account is pending admin approval after verification.', 'Go to Verification', () => {
                            window.location.href = 'verification/verify-email.php';
                        });
                    } else {
                        Notiflix.Report.failure('Registration Failed', data.error || 'Something went wrong', 'OK');
                    }
                }
            }
        }
    </script>
    <script>
        // favicon (copied from index.php)
        function updateFavicon(logoUrl) {
            // Update the favicon with website logo
            const favicon = document.getElementById('favicon');
            if (favicon && logoUrl) {
                // Normalize the logo URL for favicon
                let faviconUrl = logoUrl;
                if (!faviconUrl.startsWith('http://') && !faviconUrl.startsWith('https://')) {
                    // For frontend/ root, paths starting with ../backend/ are already correct
                    // If it doesn't start with ../, ensure it's relative to frontend/
                    if (!faviconUrl.startsWith('../') && !faviconUrl.startsWith('/')) {
                        faviconUrl = faviconUrl.replace(/^\/+/, '');
                    }
                }
                favicon.href = faviconUrl;
                console.log('Register Step: Favicon updated to:', faviconUrl);
                
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

        // Load website logo function (copied from index.php, adjusted for frontend/ root)
        async function loadWebsiteLogo() {
            // Fetch admin's website logo for favicon
            try {
                const res = await fetch('../backend/api/get-website-logo.php');
                const data = await res.json();
                if (data.success && data.logo_url) {
                    let logoUrl = data.logo_url;
                    // Normalize for frontend/ (root level)
                    if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                        if (logoUrl.startsWith('../backend/')) {
                            // Path is relative to frontend/, already correct for frontend/
                            // Keep as is: ../backend/uploads/logos/...
                        } else if (logoUrl.startsWith('backend/')) {
                            // Convert to ../backend/ for frontend/ root
                            logoUrl = '../' + logoUrl;
                        } else if (!logoUrl.startsWith('../') && !logoUrl.startsWith('/')) {
                            logoUrl = '../backend/uploads/logos/' + logoUrl.split('/').pop();
                        }
                    }
                    updateFavicon(logoUrl);
                    console.log('Register Step page: Favicon updated to:', logoUrl);
                }
            } catch (e) {
                console.error('Error loading website logo:', e);
            }
        }

        // Load logo on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadWebsiteLogo();
        });
        
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
    <!-- PWA Service Worker Registration -->
    <script src="assets/js/pwa-register.js?v=1.4.0"></script>
</body>
</html>
