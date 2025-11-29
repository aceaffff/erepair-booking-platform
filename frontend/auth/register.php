<?php
require_once __DIR__ . '/../includes/get-logo.php';
$faviconUrl = getWebsiteLogo('../../backend/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ERepair</title>
    <link rel="icon" type="image/png" id="favicon" href="<?php echo $faviconUrl; ?>">
    <link rel="apple-touch-icon" href="<?php echo $faviconUrl; ?>">
    <link href="../assets/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.6/dist/notiflix-aio-3.2.6.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/erepair-notiflix.css?v=2.1.0" rel="stylesheet">
    <script src="../assets/js/erepair-notiflix.js?v=2.1.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <link href="../assets/css/erepair-styles.css" rel="stylesheet">
    <script src="../assets/js/erepair-common.js"></script>
    <style>
        /* Ensure selfie file input is completely hidden and inaccessible */
        #customer-selfie-file {
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
    </style>
</head>
<body class="bg-light min-h-screen">
    <!-- Navigation -->
    <nav class="nav-advanced fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0">
                        <h1 class="text-2xl font-bold holo-text">
                            <i class="fas fa-tools mr-2 icon-morph"></i>ERepair
                        </h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:bg-white hover:bg-opacity-10">
                        Home
                    </a>
                    <a href="login.php" class="btn-holographic text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Register Form -->
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
        
        <div class="max-w-2xl w-full space-y-8 relative z-10">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold neon-text">
                    Create your account
                </h2>
                <p class="mt-2 text-sm text-indigo-100">
                    Already have an account?
                    <a href="login.php" class="font-medium text-white hover:text-indigo-300">
                        Sign in here
                    </a>
                </p>
            </div>
            
            <div class="glass-advanced py-8 px-6 shadow-xl rounded-lg" x-data="registerForm()">
                <!-- User Type Selection -->
                <div class="mb-8">
                    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                        <button 
                            type="button"
                            @click="userType = 'customer'"
                            :class="userType === 'customer' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"
                        >
                            <i class="fas fa-user mr-2"></i>Customer
                        </button>
                        <button 
                            type="button"
                            @click="userType = 'shop_owner'"
                            :class="userType === 'shop_owner' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"
                        >
                            <i class="fas fa-store mr-2"></i>Shop Owner
                        </button>
                    </div>
                </div>

                <!-- Customer Registration Form -->
                <form x-show="userType === 'customer'" class="space-y-6" x-data="customerForm()" @submit.prevent="handleCustomerRegister" enctype="multipart/form-data" novalidate>
                    <!-- Instructional Note -->
                    <div class="rounded-md bg-indigo-50 border border-indigo-200 p-4">
                        <p class="text-sm text-indigo-800"><i class="fas fa-info-circle mr-2"></i><span class="font-medium">Tip:</span> Pinpoint your exact location on the map below. Address does not auto-fill — enter it manually to match the pin.</p>
                    </div>
                    
                    <!-- Verification Documents Note -->
                    <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
                        <p class="text-sm text-amber-900"><i class="fas fa-id-card mr-2"></i><span class="font-medium">Required:</span> Please upload a clear photo of your valid ID and a selfie with your ID for account verification.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="customer-name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="customer-name" 
                                    name="name" 
                                    type="text" 
                                    required 
                                    x-model="form.name"
                                    @keydown="preventSpecialChars($event, 'name')"
                                    @input="form.name = filterInput($event.target.value, 'name')"
                                    @paste="handlePaste($event, 'name')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your full name"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="customer-email" class="block text-sm font-medium text-gray-700">
                                Email Address
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="customer-email" 
                                    name="email" 
                                    type="email" 
                                    required 
                                    x-model="form.email"
                                    @keydown="preventSpecialChars($event, 'email')"
                                    @input="validateEmail($event); form.email = filterInput($event.target.value, 'email')"
                                    @paste="handlePaste($event, 'email')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your email (e.g., user@gmail.com)"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                            </div>
                            <div x-show="emailError" class="text-red-500 text-xs mt-1" x-text="emailError"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="customer-phone" class="block text-sm font-medium text-gray-700">
                                Phone Number
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="customer-phone" 
                                    name="phone" 
                                    type="tel" 
                                    required 
                                    x-model="form.phone"
                                    inputmode="numeric" pattern="\\d*"
                                    maxlength="11"
                                    @keydown="validatePhoneKeydown($event)"
                                    @input="validatePhone($event)"
                                    @paste="handlePhonePaste($event)"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your phone number"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                            </div>
                            <div x-show="phoneError" class="text-red-500 text-xs mt-1" x-text="phoneError"></div>
                        </div>

                        <div>
                            <label for="customer-password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="customer-password" 
                                    name="password" 
                                    :type="showPassword ? 'text' : 'password'" 
                                    required 
                                    x-model="form.password" 
                                    @keydown="preventSpecialChars($event, 'password')"
                                    @input="updatePasswordStrength(form.password); form.password = filterInput($event.target.value, 'password')"
                                    @paste="handlePaste($event, 'password')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <div class="text-gray-600">Tip: Use 8+ chars with A-z, 0-9 and _.</div>
                                <div :class="passwordStrengthClass">Strength: <span x-text="passwordStrengthLabel"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Location + Address (optional) -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="button" @click="getCurrentLocation()" :disabled="locationLoading" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i :class="locationLoading ? 'fas fa-spinner fa-spin' : 'fas fa-location-crosshairs'" class="mr-2"></i>
                                <span x-text="locationLoading ? 'Getting Location…' : 'Use Current Location'"></span>
                            </button>
                            <span class="text-xs text-gray-500 self-center">Click the map to set your location and auto-fill your address.</span>
                        </div>
                        <div id="customer-map" class="w-full h-56 rounded-md border border-gray-200"></div>
                        <div>
                            <label for="customer-address" class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea id="customer-address" x-model="address" rows="2" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter the full address that matches the map pin"></textarea>
                        </div>
                    </div>

                    <div>
                        <label for="customer-confirm-password" class="block text-sm font-medium text-gray-700">
                            Confirm Password
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="customer-confirm-password" 
                                name="confirm_password" 
                                :type="showPassword ? 'text' : 'password'" 
                                required 
                                x-model="form.confirm_password"
                                @keydown="preventSpecialChars($event, 'password')"
                                @input="form.confirm_password = filterInput($event.target.value, 'password')"
                                @paste="handlePaste($event, 'password')"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Confirm your password"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- ID Picture Upload -->
                    <div>
                        <label for="customer-id-file" class="block text-sm font-medium text-gray-700">
                            ID Picture <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input 
                                type="file" 
                                id="customer-id-file" 
                                name="id_file" 
                                accept="image/jpeg,image/jpg,image/png,application/pdf"
                                required
                                @change="handleFileSelect($event, 'id_file')"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            >
                            <p class="mt-1 text-xs text-gray-500">Upload a clear photo of your valid ID (Driver's License, National ID, Passport, etc.). JPG, PNG, or PDF (max 5MB)</p>
                        </div>
                        <div x-show="files.id_file" class="mt-3">
                            <div x-show="files.id_file && files.id_file.type && files.id_file.type.startsWith('image/')" class="border border-gray-300 rounded-md p-2">
                                <img :src="getFilePreview('id_file')" alt="ID Preview" class="max-w-full h-auto rounded-md" style="max-height: 200px;">
                            </div>
                            <div x-show="files.id_file && files.id_file.type === 'application/pdf'" class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-600" x-text="files.id_file ? files.id_file.name : ''"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Selfie with ID Upload -->
                    <div>
                        <label for="customer-selfie-file" class="block text-sm font-medium text-gray-700">
                            Selfie with ID <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <button type="button" 
                                    @click="openCameraForSelfie()"
                                    class="w-full px-4 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                                <i class="fas fa-camera"></i>
                                <span>Take Selfie with ID</span>
                            </button>
                            <input 
                                type="file" 
                                id="customer-selfie-file" 
                                name="selfie_file" 
                                accept="image/*"
                                capture="user"
                                required
                                @change="handleFileSelect($event, 'selfie_file')"
                                class="hidden"
                                style="display: none !important;"
                            >
                            <p class="mt-1 text-xs text-gray-500">Take a selfie holding your ID next to your face using your camera. JPG or PNG only (max 5MB)</p>
                        </div>
                        <div x-show="files.selfie_file" class="mt-3">
                            <div class="border border-gray-300 rounded-md p-2">
                                <img :src="getFilePreview('selfie_file')" alt="Selfie Preview" class="max-w-full h-auto rounded-md" style="max-height: 200px;">
                                <button type="button" 
                                        @click="retakeSelfie()"
                                        class="mt-2 w-full px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                                    <i class="fas fa-redo mr-2"></i>Retake Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="loading || !files.id_file || !files.selfie_file"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-user-plus'" class="text-indigo-500 group-hover:text-indigo-400"></i>
                            </span>
                            <span x-text="loading ? 'Creating Account...' : 'Create Customer Account'"></span>
                        </button>
                    </div>
                </form>

                <!-- Shop Owner Registration Form -->
                <form x-show="userType === 'shop_owner'" class="space-y-6" x-data="shopOwnerForm()" @submit.prevent="handleShopOwnerRegister" enctype="multipart/form-data" novalidate>
                    <!-- Instructional Note -->
                    <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
                        <p class="text-sm text-amber-900"><i class="fas fa-map-pin mr-2"></i><span class="font-medium">Important:</span> Please pinpoint the exact location of your shop on the map. Your shop address will auto-fill based on where you click.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="shop-name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="shop-name" 
                                    name="name" 
                                    type="text" 
                                    required 
                                    x-model="form.name"
                                    @keydown="preventSpecialChars($event, 'name')"
                                    @input="form.name = filterInput($event.target.value, 'name')"
                                    @paste="handlePaste($event, 'name')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your full name"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="shop-email" class="block text-sm font-medium text-gray-700">
                                Email Address
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="shop-email" 
                                    name="email" 
                                    type="email" 
                                    required 
                                    x-model="form.email"
                                    @keydown="preventSpecialChars($event, 'email')"
                                    @input="validateEmail($event); form.email = filterInput($event.target.value, 'email')"
                                    @paste="handlePaste($event, 'email')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your email (e.g., user@gmail.com)"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                            </div>
                            <div x-show="emailError" class="text-red-500 text-xs mt-1" x-text="emailError"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="shop-phone" class="block text-sm font-medium text-gray-700">
                                Phone Number
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="shop-phone" 
                                    name="phone" 
                                    type="tel" 
                                    required 
                                    x-model="form.phone"
                                    inputmode="numeric" pattern="\\d*"
                                    maxlength="11"
                                    @keydown="validatePhoneKeydown($event)"
                                    @input="validatePhone($event)"
                                    @paste="handlePhonePaste($event)"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your phone number"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                            </div>
                            <div x-show="phoneError" class="text-red-500 text-xs mt-1" x-text="phoneError"></div>
                        </div>

                        <div>
                            <label for="shop-password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1 relative">
                                <input 
                                    id="shop-password" 
                                    name="password" 
                                    :type="showPassword ? 'text' : 'password'" 
                                    required 
                                    x-model="form.password" 
                                    @keydown="preventSpecialChars($event, 'password')"
                                    @input="updatePasswordStrength(form.password); form.password = filterInput($event.target.value, 'password')"
                                    @paste="handlePaste($event, 'password')"
                                    class="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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
                    </div>

                    <div>
                        <label for="shop-name-input" class="block text-sm font-medium text-gray-700">
                            Shop Name
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="shop-name-input" 
                                name="shop_name" 
                                type="text" 
                                required 
                                x-model="form.shop_name"
                                @keydown="preventSpecialChars($event, 'name')"
                                @input="form.shop_name = filterInput($event.target.value, 'name')"
                                @paste="handlePaste($event, 'name')"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter your shop name"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-store text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Map and Address for Shop Owner -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Shop Location</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="button" @click="getCurrentLocation()" :disabled="locationLoading" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i :class="locationLoading ? 'fas fa-spinner fa-spin' : 'fas fa-location-crosshairs'" class="mr-2"></i>
                                <span x-text="locationLoading ? 'Getting Location…' : 'Use Current Location'"></span>
                            </button>
                            <span class="text-xs text-gray-500 self-center">Click the map to set your shop location. Address will not auto-fill.</span>
                        </div>
                        <div id="shop-map" class="w-full h-56 rounded-md border border-gray-200"></div>
                        <div>
                            <label for="shop-address" class="block text-sm font-medium text-gray-700">Shop Address</label>
                            <textarea id="shop-address" name="shop_address" x-model="form.shop_address" rows="2" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter the full shop address matching the map pin"></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="business-permit-file" class="block text-sm font-medium text-gray-700">
                                Business Permit/License
                            </label>
                            <div class="mt-1">
                                <input 
                                    id="business-permit-file" 
                                    name="business_permit_file" 
                                    type="file" 
                                    required 
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    @change="handleFileSelect($event, 'business_permit_file')"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                >
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or PDF (max 5MB)</p>
                                <p class="mt-1 text-xs text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i>Documents only - no photos of people</p>
                            </div>
                        </div>

                        <div>
                            <label for="id-file" class="block text-sm font-medium text-gray-700">
                                Valid ID Document
                            </label>
                            <div class="mt-1">
                                <input 
                                    id="id-file" 
                                    name="id_file" 
                                    type="file" 
                                    required 
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    @change="handleFileSelect($event, 'id_file')"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                >
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or PDF (max 5MB)</p>
                                <p class="mt-1 text-xs text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i>Documents only - no photos of people</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selfie with ID Upload -->
                    <div>
                        <label for="shop-owner-selfie-file" class="block text-sm font-medium text-gray-700">
                            Selfie with ID <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <button type="button" 
                                    @click="openCameraForSelfie()"
                                    class="w-full px-4 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                                <i class="fas fa-camera"></i>
                                <span>Take Selfie with ID</span>
                            </button>
                            <input 
                                type="file" 
                                id="shop-owner-selfie-file" 
                                name="selfie_file" 
                                accept="image/*"
                                capture="user"
                                required
                                @change="handleFileSelect($event, 'selfie_file')"
                                class="hidden"
                                style="display: none !important;"
                            >
                            <p class="mt-1 text-xs text-gray-500">Take a selfie holding your ID next to your face using your camera. JPG or PNG only (max 5MB)</p>
                            <p class="mt-1 text-xs text-gray-500 flex items-center gap-1">
                                <i class="fas fa-info-circle"></i>
                                <span>This helps us verify your identity</span>
                            </p>
                        </div>
                        <div x-show="files.selfie_file" class="mt-3">
                            <div class="border border-gray-300 rounded-md p-2">
                                <img :src="getFilePreview('selfie_file')" alt="Selfie Preview" class="max-w-full h-auto rounded-md" style="max-height: 200px;">
                                <button type="button" 
                                        @click="retakeSelfie()"
                                        class="mt-2 w-full px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                                    <i class="fas fa-redo mr-2"></i>Retake Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="loading || !files.business_permit_file || !files.id_file || !files.selfie_file"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-store'" class="text-indigo-500 group-hover:text-indigo-400"></i>
                            </span>
                            <span x-text="loading ? 'Creating Account...' : 'Create Shop Owner Account'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Main component
        function registerForm() {
            return {
                userType: 'customer'
            }
        }

        // Customer form component
        function customerForm() {
            return {
                form: {
                    name: '',
                    email: '',
                    phone: '',
                    password: '',
                    confirm_password: ''
                },
                files: {
                    id_file: null,
                    selfie_file: null
                },
                address: '',
                showPassword: false,
                loading: false,
                locationLoading: false,
                latitude: null,
                longitude: null,
                map: null,
                marker: null,
                passwordStrengthLabel: 'Easy',
                passwordStrengthClass: 'text-red-600',
                
                // Validation errors
                phoneError: '',
                emailError: '',

                init() {
                    this.$nextTick(() => {
                        this.initializeMap();
                    });
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

                validatePhoneKeydown(e) {
                    const allowedKeys = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
                    if (allowedKeys.includes(e.key)) return;
                    if ((e.ctrlKey || e.metaKey) && ['a','c','v','x'].includes(e.key.toLowerCase())) return;
                    
                    // Only allow digits
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Prevent input if already at 11 digits
                    if (this.form.phone && this.form.phone.length >= 11) {
                        e.preventDefault();
                    }
                },

                validatePhone(event) {
                    const value = event.target.value;
                    // Remove any non-numeric characters
                    const numericValue = value.replace(/[^0-9]/g, '');
                    
                    // Limit to 11 digits maximum
                    const limitedValue = numericValue.substring(0, 11);
                    event.target.value = limitedValue;
                    this.form.phone = limitedValue;
                    
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

                handlePhonePaste(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numeric = paste.replace(/[^0-9]/g, '');
                    const limitedNumeric = numeric.substring(0, 11);
                    
                    // Insert cleaned value
                    const input = e.target;
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const currentValue = input.value;
                    const newValue = currentValue.substring(0, start) + limitedNumeric + currentValue.substring(end);
                    
                    // Limit to 11 item 
                    const finalValue = newValue.replace(/[^0-9]/g, '').substring(0, 11);
                    input.value = finalValue;
                    this.form.phone = finalValue;
                    
                    // Validate the result
                    this.validatePhone({ target: input });
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
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const currentValue = input.value;
                    const newValue = currentValue.substring(0, start) + filtered + currentValue.substring(end);
                    
                    // Update the input value
                    input.value = newValue;
                    this.form[fieldType] = newValue;
                    
                    // Set cursor position after the inserted text
                    const newCursorPos = start + filtered.length;
                    input.setSelectionRange(newCursorPos, newCursorPos);
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
                    } else {
                        // Default: only alphanumeric and basic punctuation
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    }
                    
                    // Remove any characters that don't match the allowed pattern
                    return input.split('').filter(char => allowedPattern.test(char)).join('');
                },

                initializeMap() {
                    this.map = L.map('customer-map').setView([14.5995, 120.9842], 11);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(this.map);
                    this.map.on('click', (e) => {
                        this.updateFromCoords(e.latlng.lat, e.latlng.lng);
                    });
                },

                async getCurrentLocation() {
                    this.locationLoading = true;
                    try {
                        const pos = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000 });
                        });
                        await this.updateFromCoords(pos.coords.latitude, pos.coords.longitude);
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.locationLoading = false;
                    }
                },

                buildFullAddress(addr, lat, lng) {
                    if (!addr) return `Lat ${lat.toFixed(6)}, Lng ${lng.toFixed(6)}`;
                    const purok = addr.suburb || addr.neighbourhood || addr.quarter || addr.hamlet || '';
                    const barangay = addr.village || addr.subdistrict || addr.barangay || '';
                    const city = addr.city || addr.town || addr.municipality || addr.county || '';
                    let province = addr.province || addr.state || addr.region || '';
                    if (province.startsWith('Province of ')) province = province.replace(/^Province of\s+/i, '');
                    const country = addr.country || '';
                    const tokens = [purok, barangay, city, province, country].filter(Boolean);
                    if (tokens.length) return tokens.join(', ');
                    return `Lat ${lat.toFixed(6)}, Lng ${lng.toFixed(6)}`;
                },

                async updateFromCoords(lat, lng) {
                    this.latitude = lat;
                    this.longitude = lng;
                    this.map.setView([lat, lng], 15);
                    if (this.marker) this.map.removeLayer(this.marker);
                    this.marker = L.marker([lat, lng]).addTo(this.map);
                    // Do not auto-fill address
                },

                handleFileSelect(event, fileType) {
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
                    
                    // Validate file size (max 5MB)
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    if (file.size > maxSize) {
                        Notiflix.Report.failure('File Too Large', 'File size must be less than 5MB', 'OK');
                        event.target.value = '';
                        this.files[fileType] = null;
                        return;
                    }
                    
                    // Validate file type
                    if (fileType === 'id_file') {
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                        if (!allowedTypes.includes(file.type)) {
                            Notiflix.Report.failure('Invalid File Type', 'ID file must be JPG, PNG, or PDF', 'OK');
                            event.target.value = '';
                            this.files[fileType] = null;
                            return;
                        }
                    }
                    
                    this.files[fileType] = file;
                },
                
                getFilePreview(fileType) {
                    if (!this.files[fileType]) return '';
                    return URL.createObjectURL(this.files[fileType]);
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
                    if (this.files.selfie_file) {
                        const preview = URL.createObjectURL(this.files.selfie_file);
                        URL.revokeObjectURL(preview);
                    }
                    const input = document.getElementById('customer-selfie-file');
                    if (input) {
                        input.value = '';
                    }
                },
                
                async handleCustomerRegister() {
                    if (this.form.password !== this.form.confirm_password) {
                        Notiflix.Report.failure('Password Mismatch', 'Passwords do not match', 'OK');
                        return;
                    }
                    
                    if (!this.files.id_file || !this.files.selfie_file) {
                        Notiflix.Report.failure('Missing Documents', 'Please upload both ID picture and selfie with ID', 'OK');
                        return;
                    }

                    this.loading = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('name', this.form.name);
                        formData.append('email', this.form.email);
                        formData.append('phone', this.form.phone);
                        formData.append('password', this.form.password);
                        formData.append('address', this.address || '');
                        if (this.latitude) formData.append('latitude', this.latitude);
                        if (this.longitude) formData.append('longitude', this.longitude);
                        formData.append('id_file', this.files.id_file);
                        formData.append('selfie_file', this.files.selfie_file);
                        
                        const response = await fetch('../backend/api/register-customer.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            const emailToStore = this.form.email;
                            localStorage.setItem('pending_verify_email', emailToStore);
                            Notiflix.Report.success('Account Created!', 'We sent a verification link to your email. Please verify to continue.', 'Go to Verification', () => {
                                window.location.href = '../verification/verify-email.php';
                            });
                        } else {
                            Notiflix.Report.failure('Registration Failed', data.error || 'Something went wrong', 'OK');
                        }
                    } catch (error) {
                        console.error('Registration error:', error);
                        Notiflix.Report.failure('Error', 'Network error. Please try again.', 'OK');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        // Shop owner form component
        function shopOwnerForm() {
            return {
                form: {
                    name: '',
                    email: '',
                    phone: '',
                    password: '',
                    shop_name: '',
                    shop_address: '',
                    latitude: null,
                    longitude: null
                },
                files: {
                    business_permit_file: null,
                    id_file: null,
                    selfie_file: null
                },
                showPassword: false,
                loading: false,
                locationLoading: false,
                map: null,
                marker: null,
                faceApiLoaded: false,
                
                // Validation errors
                phoneError: '',
                emailError: '',

                async init() {
                    this.$nextTick(() => {
                        this.initializeMap();
                    });
                    // Load face-api.js models
                    await this.loadFaceApiModels();
                },

                async loadFaceApiModels() {
                    try {
                        // Check if face-api is available
                        if (typeof faceapi === 'undefined') {
                            console.warn('face-api.js not loaded');
                            this.faceApiLoaded = false;
                            return;
                        }

                        // Load face detection model from CDN
                        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights';
                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
                        ]);
                        this.faceApiLoaded = true;
                        console.log('Face detection models loaded successfully');
                    } catch (error) {
                        console.error('Error loading face-api models:', error);
                        // Continue without face detection if models fail to load
                        this.faceApiLoaded = false;
                    }
                },

                async detectFacesInImage(file) {
                    if (!this.faceApiLoaded || typeof faceapi === 'undefined') {
                        // If face-api is not loaded, show warning but allow the file (fallback)
                        console.warn('Face detection not available, allowing file');
                        return { hasFace: false, faceCount: 0, warning: true };
                    }

                    return new Promise((resolve) => {
                        const img = new Image();
                        const objectUrl = URL.createObjectURL(file);
                        
                        img.onload = async () => {
                            try {
                                // Detect faces in the image with optimized settings
                                const detections = await faceapi
                                    .detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ 
                                        inputSize: 320,
                                        scoreThreshold: 0.5 
                                    }))
                                    .withFaceLandmarks();
                                
                                // Clean up object URL
                                URL.revokeObjectURL(objectUrl);
                                
                                resolve({
                                    hasFace: detections.length > 0,
                                    faceCount: detections.length
                                });
                            } catch (error) {
                                console.error('Face detection error:', error);
                                URL.revokeObjectURL(objectUrl);
                                // On error, allow the file (fail open)
                                resolve({ hasFace: false, faceCount: 0 });
                            }
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(objectUrl);
                            // If image fails to load, reject it
                            resolve({ hasFace: false, faceCount: 0, error: true });
                        };
                        img.src = objectUrl;
                    });
                },

                validatePhoneKeydown(e) {
                    const allowedKeys = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
                    if (allowedKeys.includes(e.key)) return;
                    if ((e.ctrlKey || e.metaKey) && ['a','c','v','x'].includes(e.key.toLowerCase())) return;
                    
                    // Only allow digits
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Prevent input if already at 11 digits
                    if (this.form.phone && this.form.phone.length >= 11) {
                        e.preventDefault();
                    }
                },

                validatePhone(event) {
                    const value = event.target.value;
                    // Remove any non-numeric characters
                    const numericValue = value.replace(/[^0-9]/g, '');
                    
                    // Limit to 11 digits maximum
                    const limitedValue = numericValue.substring(0, 11);
                    event.target.value = limitedValue;
                    this.form.phone = limitedValue;
                    
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

                handlePhonePaste(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numeric = paste.replace(/[^0-9]/g, '');
                    const limitedNumeric = numeric.substring(0, 11);
                    
                    // Insert cleaned value
                    const input = e.target;
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const currentValue = input.value;
                    const newValue = currentValue.substring(0, start) + limitedNumeric + currentValue.substring(end);
                    
                    // Limit to 11 digits
                    const finalValue = newValue.replace(/[^0-9]/g, '').substring(0, 11);
                    input.value = finalValue;
                    this.form.phone = finalValue;
                    
                    // Validate the result
                    this.validatePhone({ target: input });
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
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const currentValue = input.value;
                    const newValue = currentValue.substring(0, start) + filtered + currentValue.substring(end);
                    
                    // Update the input value
                    input.value = newValue;
                    this.form[fieldType] = newValue;
                    
                    // Set cursor position after the inserted text
                    const newCursorPos = start + filtered.length;
                    input.setSelectionRange(newCursorPos, newCursorPos);
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
                    } else {
                        // Default: only alphanumeric and basic punctuation
                        allowedPattern = /^[a-zA-Z0-9._-]+$/;
                    }
                    
                    // Remove any characters that don't match the allowed pattern
                    return input.split('').filter(char => allowedPattern.test(char)).join('');
                },

                initializeMap() {
                    this.map = L.map('shop-map').setView([14.5995, 120.9842], 11);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(this.map);
                    this.map.on('click', (e) => {
                        this.updateFromCoords(e.latlng.lat, e.latlng.lng);
                    });
                },

                async getCurrentLocation() {
                    this.locationLoading = true;
                    try {
                        const pos = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000 });
                        });
                        await this.updateFromCoords(pos.coords.latitude, pos.coords.longitude);
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.locationLoading = false;
                    }
                },

                async updateFromCoords(lat, lng) {
                    this.form.latitude = lat;
                    this.form.longitude = lng;
                    this.map.setView([lat, lng], 15);
                    if (this.marker) this.map.removeLayer(this.marker);
                    this.marker = L.marker([lat, lng]).addTo(this.map);
                    // Do not auto-fill address
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
                        return;
                    }

                    // Check if file is an image (not PDF)
                    const isImage = file.type.startsWith('image/');
                    
                    if (isImage) {
                        // Validate file size (max 5MB)
                        if (file.size > 5 * 1024 * 1024) {
                            Notiflix.Report.failure('File Too Large', 'File size must be less than 5MB', 'OK');
                            event.target.value = ''; // Clear the input
                            this.files[fileType] = null;
                            return;
                        }

                        // Show loading message
                        Notiflix.Loading.standard('Validating image...');

                        // Detect faces in the image
                        const detectionResult = await this.detectFacesInImage(file);
                        
                        Notiflix.Loading.remove();

                        if (detectionResult.hasFace) {
                            Notiflix.Report.failure('Human Image Detected', 'You can only upload documents, not photos of people.\n\nPlease upload:\n• Business permit/license documents\n• ID documents (not photos of yourself)\n• Official documents only', 'I Understand');
                            event.target.value = ''; // Clear the input
                            this.files[fileType] = null;
                            return;
                        }

                        // If no face detected, allow the file
                        this.files[fileType] = file;
                        Notiflix.Notify.success('File Accepted - Document validated successfully', {
                            timeout: 2000,
                            clickToClose: true
                        });
                    } else if (file.type === 'application/pdf') {
                        // Validate PDF file size
                        if (file.size > 5 * 1024 * 1024) {
                            Notiflix.Report.failure('File Too Large', 'File size must be less than 5MB', 'OK');
                            event.target.value = '';
                            this.files[fileType] = null;
                            return;
                        }
                        // PDF files are allowed without face detection
                        this.files[fileType] = file;
                    } else {
                        Notiflix.Report.failure('Invalid File Type', 'Please upload JPG, PNG, or PDF files only', 'OK');
                        event.target.value = '';
                        this.files[fileType] = null;
                    }
                },
                
                getFilePreview(fileType) {
                    if (!this.files[fileType]) return '';
                    return URL.createObjectURL(this.files[fileType]);
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
                        
                        // Capture photo
                        captureBtn.onclick = () => {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0);
                            
                            // Stop camera stream
                            stream.getTracks().forEach(track => track.stop());
                            
                            // Convert canvas to blob
                            canvas.toBlob((blob) => {
                                // Create a File object from the blob
                                const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                                this.files.selfie_file = file;
                                
                                // Update the hidden input (for form submission)
                                const input = document.getElementById('shop-owner-selfie-file');
                                if (input) {
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    input.files = dataTransfer.files;
                                }
                                
                                // Remove modal
                                document.body.removeChild(modal);
                                
                                Notiflix.Notify.success('Photo captured successfully!', {
                                    timeout: 2000
                                });
                            }, 'image/jpeg', 0.9);
                        };
                        
                        // Cancel camera
                        cancelBtn.onclick = () => {
                            stream.getTracks().forEach(track => track.stop());
                            document.body.removeChild(modal);
                        };
                        
                    } catch (error) {
                        console.error('Camera error:', error);
                        Notiflix.Report.failure(
                            'Camera Access Denied', 
                            'Please allow camera access to take a selfie. Check your browser permissions and try again.', 
                            'OK'
                        );
                    }
                },
                
                retakeSelfie() {
                    this.files.selfie_file = null;
                    // Revoke preview URL to free memory
                    if (this.files.selfie_file) {
                        const preview = URL.createObjectURL(this.files.selfie_file);
                        URL.revokeObjectURL(preview);
                    }
                    const input = document.getElementById('shop-owner-selfie-file');
                    if (input) {
                        input.value = '';
                    }
                },

                async handleShopOwnerRegister() {
                    if (!this.files.business_permit_file || !this.files.id_file || !this.files.selfie_file) {
                        Notiflix.Report.failure('Missing Files', 'Please upload business permit, ID document, and selfie with ID', 'OK');
                        return;
                    }

                    this.loading = true;
                    
                    try {
                        const formData = new FormData();
                        
                        // Add form fields
                        Object.keys(this.form).forEach(key => {
                            formData.append(key, this.form[key]);
                        });
                        
                        // Add files
                        formData.append('business_permit_file', this.files.business_permit_file);
                        formData.append('id_file', this.files.id_file);
                        formData.append('selfie_file', this.files.selfie_file);

                    const response = await fetch('../backend/api/register-shop-owner.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            const emailToStore = this.form.email;
                            localStorage.setItem('pending_verify_email', emailToStore);
                            Notiflix.Report.success('Account Created!', 'We sent a verification link. Your account is pending admin approval after verification.', 'Go to Verification', () => {
                                window.location.href = '../verification/verify-email.php';
                            });
                        } else {
                            Notiflix.Report.failure('Registration Failed', data.error || 'Something went wrong', 'OK');
                        }
                    } catch (error) {
                        console.error('Registration error:', error);
                        Notiflix.Report.failure('Error', 'Network error. Please try again.', 'OK');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        // Initialize the main component
        document.addEventListener('alpine:init', () => {
            Alpine.data('registerForm', registerForm);
            Alpine.data('customerForm', customerForm);
            Alpine.data('shopOwnerForm', shopOwnerForm);
        });
    </script>
</body>
</html>
