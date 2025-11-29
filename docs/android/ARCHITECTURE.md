# ERepair Android App Architecture Documentation

## Table of Contents
1. [Overview](#overview)
2. [App Architecture](#app-architecture)
3. [Project Structure](#project-structure)
4. [Technology Stack](#technology-stack)
5. [API Integration](#api-integration)
6. [Key Components](#key-components)
7. [Data Management](#data-management)
8. [Authentication Flow](#authentication-flow)
9. [UI/UX Architecture](#uiux-architecture)
10. [Security Implementation](#security-implementation)
11. [Build Configuration](#build-configuration)
12. [Deployment](#deployment)

---

## Overview

The ERepair Android application is a native Android app built with **Kotlin** and **Jetpack Compose** (or **XML layouts** for traditional approach). It provides a seamless mobile experience for customers, shop owners, technicians, and administrators to manage electronics repair bookings.

### App Features

- **Multi-role Support**: Customer, Shop Owner, Technician, Admin
- **Real-time Booking Management**: Create, track, and manage repair bookings
- **Push Notifications**: Real-time updates on booking status
- **Location Services**: GPS-based shop discovery and navigation
- **Image Upload**: Device photo capture and upload
- **Offline Support**: Basic offline functionality with sync
- **Biometric Authentication**: Fingerprint/Face unlock support
- **Dark Mode**: System-wide dark theme support

---

## App Architecture

### Architecture Pattern: MVVM (Model-View-ViewModel)

```
┌─────────────────────────────────────────────────────────────┐
│                         UI Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  Activities  │  │  Fragments   │  │  Compose UI  │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└───────────────────────────┬─────────────────────────────────┘
                            │ Observes
┌───────────────────────────▼─────────────────────────────────┐
│                      ViewModel Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ AuthViewModel│  │BookingViewModel│ │ ShopViewModel│       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└───────────────────────────┬─────────────────────────────────┘
                            │ Uses
┌───────────────────────────▼─────────────────────────────────┐
│                      Repository Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ AuthRepo     │  │ BookingRepo  │  │ ShopRepo     │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                      Data Sources                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  API Service │  │ Local Database│  │ SharedPrefs  │      │
│  │  (Retrofit)  │  │   (Room DB)   │  │   (Token)    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                    Backend API (PHP)                         │
│              http://your-domain.com/api/                      │
└─────────────────────────────────────────────────────────────┘
```

### Architecture Components

- **ViewModel**: Manages UI-related data and survives configuration changes
- **Repository**: Single source of truth for data
- **Use Cases**: Business logic operations
- **Data Sources**: API, Local Database, SharedPreferences
- **State Management**: LiveData / StateFlow / Compose State

---

## Project Structure

```
app/
├── src/
│   ├── main/
│   │   ├── java/com/erepair/
│   │   │   ├── ERepairApplication.kt          # Application class
│   │   │   │
│   │   │   ├── data/
│   │   │   │   ├── api/                       # API interfaces & models
│   │   │   │   │   ├── ApiService.kt
│   │   │   │   │   ├── ApiClient.kt
│   │   │   │   │   ├── models/
│   │   │   │   │   │   ├── User.kt
│   │   │   │   │   │   ├── Booking.kt
│   │   │   │   │   │   ├── Shop.kt
│   │   │   │   │   │   ├── Review.kt
│   │   │   │   │   │   └── ApiResponse.kt
│   │   │   │   │   └── interceptors/
│   │   │   │   │       └── AuthInterceptor.kt
│   │   │   │   │
│   │   │   │   ├── local/                    # Local data storage
│   │   │   │   │   ├── database/
│   │   │   │   │   │   ├── AppDatabase.kt
│   │   │   │   │   │   ├── dao/
│   │   │   │   │   │   │   ├── BookingDao.kt
│   │   │   │   │   │   │   ├── UserDao.kt
│   │   │   │   │   │   │   └── ShopDao.kt
│   │   │   │   │   │   └── entities/
│   │   │   │   │   │       ├── BookingEntity.kt
│   │   │   │   │   │       └── UserEntity.kt
│   │   │   │   │   │
│   │   │   │   │   └── preferences/
│   │   │   │   │       └── AppPreferences.kt
│   │   │   │   │
│   │   │   │   └── repository/               # Repository implementations
│   │   │   │       ├── AuthRepository.kt
│   │   │   │       ├── BookingRepository.kt
│   │   │   │       ├── ShopRepository.kt
│   │   │   │       └── UserRepository.kt
│   │   │   │
│   │   │   ├── domain/                       # Business logic
│   │   │   │   ├── usecases/
│   │   │   │   │   ├── auth/
│   │   │   │   │   │   ├── LoginUseCase.kt
│   │   │   │   │   │   └── RegisterUseCase.kt
│   │   │   │   │   ├── booking/
│   │   │   │   │   │   ├── CreateBookingUseCase.kt
│   │   │   │   │   │   └── UpdateBookingStatusUseCase.kt
│   │   │   │   │   └── shop/
│   │   │   │   │       └── GetShopsUseCase.kt
│   │   │   │   │
│   │   │   │   └── models/                   # Domain models
│   │   │   │       └── [Domain models]
│   │   │   │
│   │   │   ├── ui/                           # UI components
│   │   │   │   ├── theme/                    # Material Design theme
│   │   │   │   │   ├── Color.kt
│   │   │   │   │   ├── Typography.kt
│   │   │   │   │   └── Theme.kt
│   │   │   │   │
│   │   │   │   ├── auth/
│   │   │   │   │   ├── LoginActivity.kt
│   │   │   │   │   ├── RegisterActivity.kt
│   │   │   │   │   ├── ForgotPasswordActivity.kt
│   │   │   │   │   └── EmailVerificationActivity.kt
│   │   │   │   │
│   │   │   │   ├── customer/
│   │   │   │   │   ├── CustomerDashboardActivity.kt
│   │   │   │   │   ├── BookingListFragment.kt
│   │   │   │   │   ├── CreateBookingActivity.kt
│   │   │   │   │   ├── BookingDetailsActivity.kt
│   │   │   │   │   └── ReviewActivity.kt
│   │   │   │   │
│   │   │   │   ├── shop/
│   │   │   │   │   ├── ShopDashboardActivity.kt
│   │   │   │   │   ├── BookingManagementActivity.kt
│   │   │   │   │   ├── ShopProfileActivity.kt
│   │   │   │   │   └── ServicesManagementActivity.kt
│   │   │   │   │
│   │   │   │   ├── technician/
│   │   │   │   │   ├── TechnicianDashboardActivity.kt
│   │   │   │   │   └── JobListActivity.kt
│   │   │   │   │
│   │   │   │   ├── admin/
│   │   │   │   │   ├── AdminDashboardActivity.kt
│   │   │   │   │   ├── ShopApprovalActivity.kt
│   │   │   │   │   └── UserManagementActivity.kt
│   │   │   │   │
│   │   │   │   ├── common/                   # Shared UI components
│   │   │   │   │   ├── components/
│   │   │   │   │   │   ├── LoadingDialog.kt
│   │   │   │   │   │   ├── ErrorDialog.kt
│   │   │   │   │   │   └── ImagePicker.kt
│   │   │   │   │   └── adapters/
│   │   │   │   │       ├── BookingAdapter.kt
│   │   │   │   │       └── ShopAdapter.kt
│   │   │   │   │
│   │   │   │   └── main/
│   │   │   │       └── MainActivity.kt       # Main entry point
│   │   │   │
│   │   │   ├── viewmodel/                    # ViewModels
│   │   │   │   ├── AuthViewModel.kt
│   │   │   │   ├── BookingViewModel.kt
│   │   │   │   ├── ShopViewModel.kt
│   │   │   │   └── UserViewModel.kt
│   │   │   │
│   │   │   ├── di/                           # Dependency Injection
│   │   │   │   ├── AppModule.kt
│   │   │   │   ├── NetworkModule.kt
│   │   │   │   ├── DatabaseModule.kt
│   │   │   │   └── RepositoryModule.kt
│   │   │   │
│   │   │   ├── utils/                        # Utility classes
│   │   │   │   ├── Constants.kt
│   │   │   │   ├── Extensions.kt
│   │   │   │   ├── ImageUtils.kt
│   │   │   │   ├── LocationUtils.kt
│   │   │   │   └── ValidationUtils.kt
│   │   │   │
│   │   │   └── workers/                      # Background workers
│   │   │       └── SyncWorker.kt
│   │   │
│   │   ├── res/
│   │   │   ├── layout/                        # XML layouts (if not using Compose)
│   │   │   ├── drawable/                     # Icons and images
│   │   │   ├── values/
│   │   │   │   ├── strings.xml
│   │   │   │   ├── colors.xml
│   │   │   │   └── dimens.xml
│   │   │   └── mipmap/                       # App icons
│   │   │
│   │   └── AndroidManifest.xml
│   │
│   └── test/                                 # Unit tests
│       └── java/com/erepair/
│
├── build.gradle.kts                          # App-level build config
└── proguard-rules.pro                        # ProGuard rules
```

---

## Technology Stack

### Core Technologies

- **Language**: Kotlin 1.9+
- **Minimum SDK**: Android 7.0 (API 24)
- **Target SDK**: Android 14 (API 34)
- **Build Tool**: Gradle 8.0+

### Android Jetpack Libraries

- **ViewModel**: Lifecycle-aware data management
- **LiveData / StateFlow**: Reactive data streams
- **Room**: Local database
- **Navigation Component**: Navigation between screens
- **WorkManager**: Background tasks
- **DataStore**: Modern preferences storage
- **Hilt / Koin**: Dependency injection

### UI Libraries

- **Jetpack Compose**: Modern declarative UI (or XML layouts)
- **Material Design 3**: Material You design system
- **Coil**: Image loading library
- **Lottie**: Animations

### Networking

- **Retrofit 2**: HTTP client
- **OkHttp**: HTTP client with interceptors
- **Gson / Moshi**: JSON parsing
- **Coroutines**: Asynchronous programming

### Additional Libraries

- **OpenStreetMap (osmdroid)**: Free maps and location services (alternative to Google Maps)
- **Google Play Services Location**: Free location services (basic usage)
- **Firebase**: Push notifications, Crashlytics (optional, free tier available)
- **CameraX**: Camera functionality (free)
- **Biometric**: Fingerprint/Face authentication (free)

**Note**: All services are FREE. See [FREE_SERVICES_GUIDE.md](FREE_SERVICES_GUIDE.md) for details on building without paid APIs.

---

## API Integration

### API Base Configuration

```kotlin
// ApiClient.kt
object ApiClient {
    private const val BASE_URL = "https://your-domain.com/repair-booking-platform/backend/api/"
    
    private val okHttpClient = OkHttpClient.Builder()
        .addInterceptor(AuthInterceptor())
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) 
                HttpLoggingInterceptor.Level.BODY 
            else 
                HttpLoggingInterceptor.Level.NONE
        })
        .build()
    
    val retrofit = Retrofit.Builder()
        .baseUrl(BASE_URL)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
}
```

### API Service Interface

```kotlin
// ApiService.kt
interface ApiService {
    
    // Authentication
    @POST("login.php")
    suspend fun login(@Body request: LoginRequest): Response<LoginResponse>
    
    @POST("register-customer.php")
    @Multipart
    suspend fun registerCustomer(
        @Part("name") name: RequestBody,
        @Part("email") email: RequestBody,
        @Part("phone") phone: RequestBody,
        @Part("password") password: RequestBody,
        @Part("address") address: RequestBody?,
        @Part("latitude") latitude: RequestBody?,
        @Part("longitude") longitude: RequestBody?,
        @Part idFile: MultipartBody.Part,
        @Part selfieFile: MultipartBody.Part
    ): Response<ApiResponse<User>>
    
    @POST("register-shop-owner.php")
    @Multipart
    suspend fun registerShopOwner(
        @Part("name") name: RequestBody,
        @Part("email") email: RequestBody,
        @Part("phone") phone: RequestBody,
        @Part("password") password: RequestBody,
        @Part("shop_name") shopName: RequestBody,
        @Part("shop_address") shopAddress: RequestBody,
        @Part("shop_phone") shopPhone: RequestBody,
        @Part("shop_latitude") shopLatitude: RequestBody,
        @Part("shop_longitude") shopLongitude: RequestBody,
        @Part("id_type") idType: RequestBody,
        @Part("id_number") idNumber: RequestBody,
        @Part idFileFront: MultipartBody.Part,
        @Part idFileBack: MultipartBody.Part,
        @Part businessPermitFile: MultipartBody.Part
    ): Response<ApiResponse<User>>
    
    @POST("verify-email-code.php")
    suspend fun verifyEmail(@Body request: VerifyEmailRequest): Response<ApiResponse<Unit>>
    
    @POST("forgot-password-request.php")
    suspend fun forgotPassword(@Body request: ForgotPasswordRequest): Response<ApiResponse<Unit>>
    
    @POST("reset-password.php")
    suspend fun resetPassword(@Body request: ResetPasswordRequest): Response<ApiResponse<Unit>>
    
    // User Management
    @GET("users/me.php")
    suspend fun getCurrentUser(): Response<ApiResponse<User>>
    
    // Bookings
    @POST("bookings/create.php")
    @Multipart
    suspend fun createBooking(
        @Part("shop_id") shopId: RequestBody,
        @Part("device_type") deviceType: RequestBody,
        @Part("device_description") deviceDescription: RequestBody,
        @Part("device_issue_description") issueDescription: RequestBody,
        @Part("scheduled_at") scheduledAt: RequestBody,
        @Part devicePhoto: MultipartBody.Part?
    ): Response<ApiResponse<Booking>>
    
    @GET("bookings/list.php")
    suspend fun getBookings(
        @Query("status") status: String?,
        @Query("role") role: String
    ): Response<ApiResponse<List<Booking>>>
    
    @GET("bookings/{id}.php")
    suspend fun getBookingDetails(@Path("id") bookingId: Int): Response<ApiResponse<Booking>>
    
    @POST("bookings/{id}/update-status.php")
    suspend fun updateBookingStatus(
        @Path("id") bookingId: Int,
        @Body request: UpdateStatusRequest
    ): Response<ApiResponse<Booking>>
    
    // Shops
    @GET("shop-homepage.php")
    suspend fun getShopDetails(@Query("shop_id") shopId: Int): Response<ApiResponse<Shop>>
    
    @GET("shops/list.php")
    suspend fun getShops(
        @Query("latitude") latitude: Double?,
        @Query("longitude") longitude: Double?,
        @Query("radius") radius: Double?
    ): Response<ApiResponse<List<Shop>>>
    
    // Reviews
    @POST("submit-review.php")
    suspend fun submitReview(@Body request: ReviewRequest): Response<ApiResponse<Review>>
    
    @GET("get-ratings.php")
    suspend fun getRatings(
        @Query("shop_id") shopId: Int?,
        @Query("technician_id") technicianId: Int?
    ): Response<ApiResponse<Ratings>>
    
    // Notifications
    @GET("notifications/list.php")
    suspend fun getNotifications(): Response<ApiResponse<List<Notification>>>
    
    @POST("notifications/{id}/read.php")
    suspend fun markNotificationRead(@Path("id") notificationId: Int): Response<ApiResponse<Unit>>
}
```

### Authentication Interceptor

```kotlin
// AuthInterceptor.kt
class AuthInterceptor(
    private val preferences: AppPreferences
) : Interceptor {
    
    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        val token = preferences.getAuthToken()
        
        val authenticatedRequest = if (token != null) {
            request.newBuilder()
                .addHeader("Authorization", "Bearer $token")
                .addHeader("X-Auth-Token", token)
                .build()
        } else {
            request
        }
        
        val response = chain.proceed(authenticatedRequest)
        
        // Handle token expiration (401)
        if (response.code == 401) {
            preferences.clearAuthToken()
            // Navigate to login
        }
        
        return response
    }
}
```

### Data Models

```kotlin
// User.kt
data class User(
    val id: Int,
    val name: String,
    val email: String,
    val phone: String?,
    val role: String, // "customer", "shop_owner", "technician", "admin"
    val emailVerified: Boolean,
    val status: String,
    val address: String?,
    val latitude: Double?,
    val longitude: Double?,
    val avatar: String?
)

// Booking.kt
data class Booking(
    val id: Int,
    val customerId: Int,
    val shopId: Int,
    val serviceId: Int?,
    val technicianId: Int?,
    val deviceType: String?,
    val deviceDescription: String?,
    val deviceIssueDescription: String?,
    val devicePhoto: String?,
    val status: BookingStatus,
    val scheduledAt: String,
    val durationMinutes: Int,
    val totalPrice: Double,
    val estimatedCost: Double?,
    val estimatedTimeDays: Double?,
    val notes: String?,
    val diagnosticNotes: String?,
    val createdAt: String,
    val updatedAt: String
)

enum class BookingStatus {
    PENDING_REVIEW,
    AWAITING_CUSTOMER_CONFIRMATION,
    CONFIRMED_BY_CUSTOMER,
    APPROVED,
    ASSIGNED,
    IN_PROGRESS,
    COMPLETED,
    CANCELLED_BY_CUSTOMER,
    REJECTED,
    CANCELLED
}

// ApiResponse.kt
data class ApiResponse<T>(
    val success: Boolean? = null,
    val error: Boolean? = null,
    val message: String,
    val data: T? = null,
    val details: Any? = null
)
```

---

## Key Components

### Repository Pattern

```kotlin
// BookingRepository.kt
class BookingRepository(
    private val apiService: ApiService,
    private val bookingDao: BookingDao,
    private val preferences: AppPreferences
) {
    
    suspend fun getBookings(role: String, status: String? = null): Flow<List<Booking>> {
        return flow {
            // Try to get from cache first
            val cachedBookings = bookingDao.getAllBookings()
            emit(cachedBookings.map { it.toDomain() })
            
            try {
                // Fetch from API
                val response = apiService.getBookings(status, role)
                if (response.isSuccessful && response.body()?.success == true) {
                    val bookings = response.body()?.data ?: emptyList()
                    
                    // Cache in local database
                    bookingDao.insertAll(bookings.map { it.toEntity() })
                    
                    emit(bookings)
                }
            } catch (e: Exception) {
                // Return cached data on error
                emit(cachedBookings.map { it.toDomain() })
            }
        }
    }
    
    suspend fun createBooking(bookingRequest: CreateBookingRequest): Result<Booking> {
        return try {
            val response = apiService.createBooking(
                shopId = bookingRequest.shopId.toRequestBody(),
                deviceType = bookingRequest.deviceType.toRequestBody(),
                deviceDescription = bookingRequest.deviceDescription.toRequestBody(),
                deviceIssueDescription = bookingRequest.issueDescription.toRequestBody(),
                scheduledAt = bookingRequest.scheduledAt.toRequestBody(),
                devicePhoto = bookingRequest.devicePhoto?.toMultipartBody()
            )
            
            if (response.isSuccessful && response.body()?.success == true) {
                val booking = response.body()?.data
                if (booking != null) {
                    bookingDao.insert(booking.toEntity())
                    Result.success(booking)
                } else {
                    Result.failure(Exception("No booking data returned"))
                }
            } else {
                Result.failure(Exception(response.body()?.message ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
```

### ViewModel

```kotlin
// BookingViewModel.kt
@HiltViewModel
class BookingViewModel @Inject constructor(
    private val repository: BookingRepository,
    private val userRepository: UserRepository
) : ViewModel() {
    
    private val _bookings = MutableStateFlow<List<Booking>>(emptyList())
    val bookings: StateFlow<List<Booking>> = _bookings.asStateFlow()
    
    private val _loading = MutableStateFlow(false)
    val loading: StateFlow<Boolean> = _loading.asStateFlow()
    
    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error.asStateFlow()
    
    init {
        loadBookings()
    }
    
    fun loadBookings(status: String? = null) {
        viewModelScope.launch {
            _loading.value = true
            _error.value = null
            
            try {
                val user = userRepository.getCurrentUser()
                repository.getBookings(user.role, status)
                    .collect { bookingList ->
                        _bookings.value = bookingList
                        _loading.value = false
                    }
            } catch (e: Exception) {
                _error.value = e.message
                _loading.value = false
            }
        }
    }
    
    fun createBooking(request: CreateBookingRequest) {
        viewModelScope.launch {
            _loading.value = true
            _error.value = null
            
            repository.createBooking(request)
                .onSuccess { booking ->
                    loadBookings() // Refresh list
                }
                .onFailure { exception ->
                    _error.value = exception.message
                }
            
            _loading.value = false
        }
    }
}
```

---

## Data Management

### Room Database

```kotlin
// AppDatabase.kt
@Database(
    entities = [BookingEntity::class, UserEntity::class, ShopEntity::class],
    version = 1,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun bookingDao(): BookingDao
    abstract fun userDao(): UserDao
    abstract fun shopDao(): ShopDao
    
    companion object {
        @Volatile
        private var INSTANCE: AppDatabase? = null
        
        fun getDatabase(context: Context): AppDatabase {
            return INSTANCE ?: synchronized(this) {
                val instance = Room.databaseBuilder(
                    context.applicationContext,
                    AppDatabase::class.java,
                    "erepair_database"
                )
                    .fallbackToDestructiveMigration()
                    .build()
                INSTANCE = instance
                instance
            }
        }
    }
}
```

### SharedPreferences / DataStore

```kotlin
// AppPreferences.kt
class AppPreferences @Inject constructor(
    private val context: Context
) {
    private val dataStore = context.dataStore
    
    companion object {
        private val AUTH_TOKEN_KEY = stringPreferencesKey("auth_token")
        private val USER_ID_KEY = intPreferencesKey("user_id")
        private val USER_ROLE_KEY = stringPreferencesKey("user_role")
    }
    
    suspend fun saveAuthToken(token: String) {
        context.dataStore.edit { preferences ->
            preferences[AUTH_TOKEN_KEY] = token
        }
    }
    
    suspend fun getAuthToken(): String? {
        return context.dataStore.data.first()[AUTH_TOKEN_KEY]
    }
    
    suspend fun clearAuthToken() {
        context.dataStore.edit { preferences ->
            preferences.remove(AUTH_TOKEN_KEY)
            preferences.remove(USER_ID_KEY)
            preferences.remove(USER_ROLE_KEY)
        }
    }
}
```

---

## Authentication Flow

### Login Flow

```
1. User enters email/password
   ↓
2. ViewModel calls AuthRepository.login()
   ↓
3. Repository calls API service
   ↓
4. API returns token + user data
   ↓
5. Repository saves token to DataStore
   ↓
6. Repository saves user to Room DB
   ↓
7. ViewModel updates UI state
   ↓
8. Navigate to appropriate dashboard
```

### Token Management

- **Storage**: Encrypted SharedPreferences / DataStore
- **Expiration**: 7 days (matches backend)
- **Refresh**: Automatic token validation on each request
- **Logout**: Clear token and local data

### Biometric Authentication

```kotlin
// BiometricAuthHelper.kt
class BiometricAuthHelper(private val context: Context) {
    
    fun authenticate(callback: (Boolean) -> Unit) {
        val executor = ContextCompat.getMainExecutor(context)
        val biometricPrompt = BiometricPrompt(
            context as FragmentActivity,
            executor,
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(
                    result: BiometricPrompt.AuthenticationResult
                ) {
                    callback(true)
                }
                
                override fun onAuthenticationError(
                    errorCode: Int,
                    errString: CharSequence
                ) {
                    callback(false)
                }
            }
        )
        
        val promptInfo = BiometricPrompt.PromptInfo.Builder()
            .setTitle("ERepair Authentication")
            .setSubtitle("Use your fingerprint to login")
            .setNegativeButtonText("Cancel")
            .build()
        
        biometricPrompt.authenticate(promptInfo)
    }
}
```

---

## UI/UX Architecture

### Navigation Structure

```
MainActivity
├── Splash Screen
├── Auth Flow
│   ├── Login
│   ├── Register (Customer/Shop Owner)
│   ├── Email Verification
│   └── Forgot Password
│
├── Customer Flow
│   ├── Dashboard
│   ├── Create Booking
│   ├── My Bookings
│   ├── Booking Details
│   ├── Submit Review
│   └── Profile
│
├── Shop Owner Flow
│   ├── Dashboard
│   ├── Booking Management
│   ├── Services Management
│   ├── Shop Profile
│   └── Analytics
│
├── Technician Flow
│   ├── Dashboard
│   ├── Assigned Jobs
│   └── Job Details
│
└── Admin Flow
    ├── Dashboard
    ├── Shop Approvals
    ├── User Management
    └── Reports
```

### Material Design 3 Theme

```kotlin
// Theme.kt
@Composable
fun ERepairTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) {
        darkColorScheme(
            primary = Purple80,
            secondary = PurpleGrey80,
            tertiary = Pink80
        )
    } else {
        lightColorScheme(
            primary = Purple40,
            secondary = PurpleGrey40,
            tertiary = Pink40
        )
    }
    
    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
```

---

## Security Implementation

### Security Measures

1. **Token Encryption**: Encrypted storage of auth tokens
2. **Certificate Pinning**: SSL pinning for API calls
3. **ProGuard/R8**: Code obfuscation
4. **Input Validation**: Client-side validation
5. **Secure Storage**: Android Keystore for sensitive data
6. **Biometric Auth**: Optional biometric login

### Network Security Config

```xml
<!-- network_security_config.xml -->
<network-security-config>
    <domain-config cleartextTrafficPermitted="false">
        <domain includeSubdomains="true">your-domain.com</domain>
        <pin-set expiration="2025-12-31">
            <pin digest="SHA-256">base64-encoded-pin</pin>
        </pin-set>
    </domain-config>
</network-security-config>
```

---

## Build Configuration

### build.gradle.kts (App Level)

```kotlin
plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("kotlin-kapt")
    id("dagger.hilt.android.plugin")
}

android {
    namespace = "com.erepair"
    compileSdk = 34
    
    defaultConfig {
        applicationId = "com.erepair"
        minSdk = 24
        targetSdk = 34
        versionCode = 1
        versionName = "1.0.0"
    }
    
    buildTypes {
        release {
            isMinifyEnabled = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
    
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    
    kotlinOptions {
        jvmTarget = "17"
    }
    
    buildFeatures {
        compose = true
    }
    
    composeOptions {
        kotlinCompilerExtensionVersion = "1.5.3"
    }
}

dependencies {
    // Core Android
    implementation("androidx.core:core-ktx:1.12.0")
    implementation("androidx.appcompat:appcompat:1.6.1")
    implementation("com.google.android.material:material:1.10.0")
    
    // Compose
    implementation("androidx.compose.ui:ui:1.5.4")
    implementation("androidx.compose.material3:material3:1.1.2")
    implementation("androidx.compose.ui:ui-tooling-preview:1.5.4")
    implementation("androidx.activity:activity-compose:1.8.1")
    
    // ViewModel & LiveData
    implementation("androidx.lifecycle:lifecycle-viewmodel-compose:2.6.2")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.6.2")
    
    // Navigation
    implementation("androidx.navigation:navigation-compose:2.7.5")
    
    // Room
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
    kapt("androidx.room:room-compiler:2.6.1")
    
    // Retrofit
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
    
    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
    
    // Hilt
    implementation("com.google.dagger:hilt-android:2.48")
    kapt("com.google.dagger:hilt-compiler:2.48")
    implementation("androidx.hilt:hilt-navigation-compose:1.1.0")
    
    // Image Loading
    implementation("io.coil-kt:coil-compose:2.5.0")
    
    // Location (Free - basic usage)
    implementation("com.google.android.gms:play-services-location:21.0.1")
    
    // OpenStreetMap (Free alternative to Google Maps)
    implementation("org.osmdroid:osmdroid-android:6.1.16")
    
    // Biometric
    implementation("androidx.biometric:biometric:1.1.0")
    
    // DataStore
    implementation("androidx.datastore:datastore-preferences:1.0.0")
    
    // WorkManager
    implementation("androidx.work:work-runtime-ktx:2.9.0")
    
    // Testing
    testImplementation("junit:junit:4.13.2")
    androidTestImplementation("androidx.test.ext:junit:1.1.5")
    androidTestImplementation("androidx.test.espresso:espresso-core:3.5.1")
}
```

---

## Deployment

### Release Build Process

1. **Version Update**: Update versionCode and versionName
2. **ProGuard Rules**: Configure obfuscation rules
3. **Signing Config**: Set up release signing
4. **Build APK/AAB**: Generate release bundle
5. **Testing**: Test on multiple devices
6. **Google Play Console**: Upload to Play Store

### Google Play Store Listing

- **App Name**: ERepair - Electronics Repair Booking
- **Short Description**: Book electronics repair services easily
- **Full Description**: [Detailed app description]
- **Screenshots**: App screenshots for different device sizes
- **Privacy Policy**: Link to privacy policy
- **App Icon**: 512x512 PNG icon

---

## Conclusion

The ERepair Android app provides a native, performant mobile experience with:

- **Modern Architecture**: MVVM with clean separation
- **Offline Support**: Local caching with Room
- **Security**: Encrypted storage and secure API communication
- **User Experience**: Material Design 3 with smooth animations
- **Scalability**: Modular structure for easy expansion



