# ERepair Android Application

A native Android application for the ERepair electronics repair booking platform, built with Kotlin and modern Android development practices.

## Features

- 🔐 **Multi-role Authentication**: Support for Customers, Shop Owners, Technicians, and Admins
- 📱 **Real-time Booking Management**: Create, track, and manage repair bookings
- 📍 **Location Services**: GPS-based shop discovery and navigation
- 📸 **Image Upload**: Device photo capture and upload
- 🔔 **Push Notifications**: Real-time booking status updates
- 🌙 **Dark Mode**: System-wide dark theme support
- 🔒 **Biometric Authentication**: Fingerprint/Face unlock support
- 📴 **Offline Support**: Basic offline functionality with sync

## Requirements

- **Android Studio**: Hedgehog (2023.1.1) or later
- **JDK**: 17 or later
- **Minimum SDK**: Android 7.0 (API 24)
- **Target SDK**: Android 14 (API 34)
- **Kotlin**: 1.9.0 or later
- **Gradle**: 8.0 or later

## Project Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/erepair-android.git
cd erepair-android
```

### 2. Configure API Base URL

Edit `app/src/main/java/com/erepair/data/api/ApiClient.kt`:

```kotlin
private const val BASE_URL = "https://your-domain.com/repair-booking-platform/backend/api/"
```

### 3. Configure Build Variants

Edit `app/build.gradle.kts`:

```kotlin
buildTypes {
    debug {
        applicationIdSuffix = ".debug"
        isDebuggable = true
    }
    release {
        isMinifyEnabled = true
        proguardFiles(
            getDefaultProguardFile("proguard-android-optimize.txt"),
            "proguard-rules.pro"
        )
    }
}
```

### 4. Sync and Build

1. Open the project in Android Studio
2. Click **File > Sync Project with Gradle Files**
3. Wait for Gradle sync to complete
4. Build the project: **Build > Make Project**

## Running the App

### Debug Build

1. Connect an Android device or start an emulator
2. Click **Run > Run 'app'** or press `Shift + F10`
3. Select your device/emulator

### Release Build

1. **Build > Generate Signed Bundle / APK**
2. Select **Android App Bundle** or **APK**
3. Follow the signing wizard
4. Build and install the generated file

## Project Structure

```
app/
├── src/main/java/com/erepair/
│   ├── data/              # Data layer (API, Database, Repository)
│   ├── domain/            # Business logic (Use Cases)
│   ├── ui/                # UI layer (Activities, Fragments, Compose)
│   ├── viewmodel/         # ViewModels
│   ├── di/                # Dependency Injection
│   └── utils/             # Utility classes
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed structure.

## Key Components

### Authentication

- **Login**: Email/password authentication
- **Registration**: Customer and Shop Owner registration
- **Email Verification**: 6-digit code verification
- **Password Reset**: Forgot password flow
- **Biometric Auth**: Optional fingerprint/face unlock

### Booking Management

- **Create Booking**: Device photo upload, scheduling
- **View Bookings**: List all bookings with filters
- **Booking Details**: Detailed booking information
- **Status Updates**: Real-time status changes
- **Reviews**: Submit reviews after completion

### Shop Features

- **Shop Discovery**: GPS-based shop search
- **Shop Details**: View shop information and ratings
- **Services**: View available services
- **Booking Management**: For shop owners

### Notifications

- **In-app Notifications**: Booking status updates
- **Push Notifications**: Background notifications (if configured)

## API Integration

The app integrates with the ERepair PHP backend API. See [API_INTEGRATION.md](API_INTEGRATION.md) for detailed integration guide.

### Base URL Configuration

Update the base URL in `ApiClient.kt`:

```kotlin
private const val BASE_URL = "https://your-domain.com/repair-booking-platform/backend/api/"
```

### Authentication

All authenticated requests automatically include the auth token via `AuthInterceptor`.

## Dependencies

### Core Libraries

- **Jetpack Compose**: Modern UI toolkit
- **Material Design 3**: Material You design system
- **ViewModel & LiveData**: Lifecycle-aware components
- **Room**: Local database
- **Retrofit**: HTTP client
- **Coroutines**: Asynchronous programming
- **Hilt**: Dependency injection

### See `app/build.gradle.kts` for complete dependency list.

## Testing

### Unit Tests

```bash
./gradlew test
```

### Instrumented Tests

```bash
./gradlew connectedAndroidTest
```

## Building for Production

### 1. Update Version

Edit `app/build.gradle.kts`:

```kotlin
defaultConfig {
    versionCode = 2
    versionName = "1.0.1"
}
```

### 2. Generate Signed Bundle

1. **Build > Generate Signed Bundle / APK**
2. Select **Android App Bundle**
3. Choose your keystore
4. Build the bundle

### 3. Upload to Play Store

1. Go to [Google Play Console](https://play.google.com/console)
2. Create new release
3. Upload the generated `.aab` file
4. Complete store listing
5. Submit for review

## Configuration

### Environment Variables

Create `local.properties` (not committed to git):

```properties
API_BASE_URL=https://your-domain.com/repair-booking-platform/backend/api/
```

**Note**: No API keys needed! The app uses free services:
- OpenStreetMap (free, no API key)
- Android Location Services (free)
- Your own backend API (free)

See [FREE_SERVICES_GUIDE.md](FREE_SERVICES_GUIDE.md) for details.

### ProGuard Rules

Edit `proguard-rules.pro` for release builds to keep necessary classes.

## Troubleshooting

### Build Errors

- **Gradle Sync Failed**: Check internet connection, invalidate caches
- **Dependency Conflicts**: Update to latest versions
- **Kotlin Version**: Ensure Kotlin version matches Gradle plugin

### Runtime Errors

- **Network Errors**: Check API base URL and internet connection
- **Authentication Errors**: Verify token storage and expiration
- **Database Errors**: Clear app data and reinstall

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@erepair.com or create an issue in the repository.

## Documentation

- [Architecture Documentation](ARCHITECTURE.md)
- [API Integration Guide](API_INTEGRATION.md)
- [Backend API Documentation](../backend/ARCHITECTURE.md)

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Maintained by**: ERepair Development Team

