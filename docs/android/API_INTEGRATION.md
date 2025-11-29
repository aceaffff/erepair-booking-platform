# ERepair Android App - API Integration Guide

## Overview

This document provides detailed information on how the Android app integrates with the ERepair backend API.

## Base Configuration

### API Base URL

```kotlin
const val BASE_URL = "https://your-domain.com/repair-booking-platform/backend/api/"
```

### API Endpoints Mapping

| Backend Endpoint | Android Method | Description |
|-----------------|----------------|-------------|
| `login.php` | `login()` | User authentication |
| `register-customer.php` | `registerCustomer()` | Customer registration |
| `register-shop-owner.php` | `registerShopOwner()` | Shop owner registration |
| `verify-email-code.php` | `verifyEmail()` | Email verification |
| `forgot-password-request.php` | `forgotPassword()` | Password reset request |
| `reset-password.php` | `resetPassword()` | Password reset |
| `users/me.php` | `getCurrentUser()` | Get current user info |
| `shop-homepage.php` | `getShopDetails()` | Get shop information |
| `shops/list.php` | `getShops()` | Get shops list |
| `bookings/create.php` | `createBooking()` | Create new booking |
| `bookings/list.php` | `getBookings()` | Get bookings list |
| `bookings/{id}.php` | `getBookingDetails()` | Get booking details |
| `bookings/{id}/update-status.php` | `updateBookingStatus()` | Update booking status |
| `submit-review.php` | `submitReview()` | Submit review |
| `get-ratings.php` | `getRatings()` | Get ratings |
| `notifications/list.php` | `getNotifications()` | Get notifications |

## Request/Response Examples

### 1. Login

**Request:**
```kotlin
data class LoginRequest(
    val email: String,
    val password: String
)

// Usage
val request = LoginRequest("user@example.com", "password123")
val response = apiService.login(request)
```

**Response:**
```json
{
  "success": true,
  "token": "64-character-hex-token",
  "role": "customer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "customer"
  }
}
```

### 2. Register Customer

**Request:**
```kotlin
// Create multipart request
val namePart = "John Doe".toRequestBody("text/plain".toMediaType())
val emailPart = "john@example.com".toRequestBody("text/plain".toMediaType())
val phonePart = "09123456789".toRequestBody("text/plain".toMediaType())
val passwordPart = "password123".toRequestBody("text/plain".toMediaType())

// File parts
val idFile = File(idFilePath)
val idFilePart = MultipartBody.Part.createFormData(
    "id_file",
    idFile.name,
    idFile.asRequestBody("image/jpeg".toMediaType())
)

val selfieFile = File(selfieFilePath)
val selfieFilePart = MultipartBody.Part.createFormData(
    "selfie_file",
    selfieFile.name,
    selfieFile.asRequestBody("image/jpeg".toMediaType())
)

val response = apiService.registerCustomer(
    name = namePart,
    email = emailPart,
    phone = phonePart,
    password = passwordPart,
    address = addressPart,
    latitude = latitudePart,
    longitude = longitudePart,
    idFile = idFilePart,
    selfieFile = selfieFilePart
)
```

### 3. Create Booking

**Request:**
```kotlin
data class CreateBookingRequest(
    val shopId: Int,
    val deviceType: String,
    val deviceDescription: String,
    val deviceIssueDescription: String,
    val scheduledAt: String, // ISO 8601 format
    val devicePhoto: File?
)

// Convert to multipart
val shopIdPart = request.shopId.toString().toRequestBody("text/plain".toMediaType())
val deviceTypePart = request.deviceType.toRequestBody("text/plain".toMediaType())
// ... other parts

val devicePhotoPart = request.devicePhoto?.let {
    MultipartBody.Part.createFormData(
        "device_photo",
        it.name,
        it.asRequestBody("image/jpeg".toMediaType())
    )
}

val response = apiService.createBooking(
    shopId = shopIdPart,
    deviceType = deviceTypePart,
    deviceDescription = deviceDescriptionPart,
    deviceIssueDescription = issueDescriptionPart,
    scheduledAt = scheduledAtPart,
    devicePhoto = devicePhotoPart
)
```

### 4. Get Bookings

**Request:**
```kotlin
// Get all bookings for current user
val response = apiService.getBookings(
    status = null, // or "pending_review", "in_progress", etc.
    role = "customer"
)
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 1,
      "shop_id": 5,
      "device_type": "Smartphone",
      "status": "in_progress",
      "scheduled_at": "2024-01-15 10:00:00",
      "total_price": 1500.00
    }
  ]
}
```

## Error Handling

### Standard Error Response

```json
{
  "error": true,
  "message": "Error description",
  "details": {
    "field": "error message"
  }
}
```

### Error Handling in Android

```kotlin
try {
    val response = apiService.login(loginRequest)
    
    if (response.isSuccessful) {
        val body = response.body()
        if (body?.success == true) {
            // Success
            val token = body.token
            val user = body.user
        } else {
            // API returned error
            val errorMessage = body?.message ?: "Unknown error"
            // Handle error
        }
    } else {
        // HTTP error (4xx, 5xx)
        when (response.code()) {
            400 -> // Bad Request
            401 -> // Unauthorized
            403 -> // Forbidden
            404 -> // Not Found
            500 -> // Server Error
        }
    }
} catch (e: IOException) {
    // Network error
} catch (e: Exception) {
    // Other errors
}
```

## Authentication Token Management

### Storing Token

```kotlin
// After successful login
preferences.saveAuthToken(token)
preferences.saveUserId(user.id)
preferences.saveUserRole(user.role)
```

### Using Token in Requests

The `AuthInterceptor` automatically adds the token to all requests:

```kotlin
class AuthInterceptor(
    private val preferences: AppPreferences
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        val token = preferences.getAuthToken()
        
        val authenticatedRequest = if (token != null) {
            request.newBuilder()
                .addHeader("X-Auth-Token", token)
                .build()
        } else {
            request
        }
        
        return chain.proceed(authenticatedRequest)
    }
}
```

### Handling Token Expiration

```kotlin
override fun intercept(chain: Interceptor.Chain): Response {
    // ... add token to request
    
    val response = chain.proceed(authenticatedRequest)
    
    // Handle 401 Unauthorized
    if (response.code == 401) {
        preferences.clearAuthToken()
        // Navigate to login screen
        EventBus.post(LogoutEvent())
    }
    
    return response
}
```

## File Upload

### Image Compression

```kotlin
fun compressImage(imageFile: File): File {
    val bitmap = BitmapFactory.decodeFile(imageFile.absolutePath)
    val maxWidth = 1920
    val maxHeight = 1920
    
    val scaledBitmap = if (bitmap.width > maxWidth || bitmap.height > maxHeight) {
        val scale = min(
            maxWidth.toFloat() / bitmap.width,
            maxHeight.toFloat() / bitmap.height
        )
        Bitmap.createScaledBitmap(bitmap, 
            (bitmap.width * scale).toInt(),
            (bitmap.height * scale).toInt(),
            true
        )
    } else {
        bitmap
    }
    
    // Save compressed image
    val compressedFile = File(context.cacheDir, "compressed_${System.currentTimeMillis()}.jpg")
    compressedFile.outputStream().use {
        scaledBitmap.compress(Bitmap.CompressFormat.JPEG, 85, it)
    }
    
    return compressedFile
}
```

### Multipart File Upload

```kotlin
fun File.toMultipartBody(partName: String): MultipartBody.Part {
    val requestFile = this.asRequestBody("image/jpeg".toMediaType())
    return MultipartBody.Part.createFormData(partName, this.name, requestFile)
}

// Usage
val imageFile = File(imagePath)
val compressedFile = compressImage(imageFile)
val imagePart = compressedFile.toMultipartBody("device_photo")
```

## Pagination

### Implementing Pagination

```kotlin
interface ApiService {
    @GET("bookings/list.php")
    suspend fun getBookings(
        @Query("page") page: Int = 1,
        @Query("limit") limit: Int = 20,
        @Query("status") status: String? = null
    ): Response<ApiResponse<PaginatedResponse<Booking>>>
}

data class PaginatedResponse<T>(
    val data: List<T>,
    val currentPage: Int,
    val totalPages: Int,
    val totalItems: Int,
    val hasMore: Boolean
)
```

## Caching Strategy

### Cache-First Approach

```kotlin
suspend fun getBookings(): Flow<List<Booking>> {
    return flow {
        // Emit cached data first
        val cachedBookings = bookingDao.getAllBookings()
        emit(cachedBookings.map { it.toDomain() })
        
        try {
            // Fetch from API
            val response = apiService.getBookings()
            if (response.isSuccessful) {
                val bookings = response.body()?.data ?: emptyList()
                
                // Update cache
                bookingDao.insertAll(bookings.map { it.toEntity() })
                
                // Emit fresh data
                emit(bookings)
            }
        } catch (e: Exception) {
            // On error, keep cached data
            // Log error
        }
    }
}
```

## Network Monitoring

### Connectivity Check

```kotlin
fun isNetworkAvailable(context: Context): Boolean {
    val connectivityManager = context.getSystemService(Context.CONNECTIVITY_SERVICE) 
        as ConnectivityManager
    val network = connectivityManager.activeNetwork ?: return false
    val capabilities = connectivityManager.getNetworkCapabilities(network) ?: return false
    return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
}
```

### Retry Logic

```kotlin
suspend fun <T> retryApiCall(
    maxRetries: Int = 3,
    delayMillis: Long = 1000,
    block: suspend () -> Response<T>
): Response<T> {
    repeat(maxRetries) { attempt ->
        try {
            val response = block()
            if (response.isSuccessful) {
                return response
            }
        } catch (e: Exception) {
            if (attempt == maxRetries - 1) throw e
        }
        delay(delayMillis * (attempt + 1)) // Exponential backoff
    }
    throw Exception("Max retries reached")
}
```

## Testing API Integration

### Mock API Responses

```kotlin
class MockApiService : ApiService {
    override suspend fun login(request: LoginRequest): Response<LoginResponse> {
        return Response.success(LoginResponse(
            success = true,
            token = "mock_token",
            role = "customer",
            user = User(id = 1, name = "Test User", email = request.email)
        ))
    }
}
```

### Unit Testing

```kotlin
@Test
fun `test login success`() = runTest {
    val repository = AuthRepository(mockApiService, mockPreferences)
    val result = repository.login("test@example.com", "password")
    
    assertTrue(result.isSuccess)
    assertEquals("mock_token", result.getOrNull()?.token)
}
```

## Best Practices

1. **Always handle errors**: Network calls can fail
2. **Show loading states**: User feedback is important
3. **Cache data**: Improve offline experience
4. **Compress images**: Reduce upload time and bandwidth
5. **Validate inputs**: Client-side validation before API calls
6. **Use coroutines**: Non-blocking async operations
7. **Handle token expiration**: Automatic logout on 401
8. **Implement retry logic**: For transient failures
9. **Monitor network state**: Check connectivity before requests
10. **Log API calls**: For debugging in production

---

**Last Updated**: 2024

