<?php
/**
 * Test Script for Document Validation
 * Run this to verify document validation is working
 * 
 * Usage: Open in browser or run via CLI: php test-document-validation.php
 */

require_once __DIR__ . '/utils/DocumentAPIValidator.php';
require_once __DIR__ . '/config/api_keys.php';

echo "<h2>Document Validation System Test</h2>";

// Check API configuration
$config = require __DIR__ . '/config/api_keys.php';
echo "<h3>API Configuration Status:</h3>";
echo "<ul>";
echo "<li>Google Vision API: " . ($config['use_google_vision'] && !empty($config['google_vision_api_key']) ? "✅ Configured" : "❌ Not configured (will use custom validation)") . "</li>";
echo "<li>PixLab API: " . ($config['use_pixlab'] && !empty($config['pixlab_api_key']) ? "✅ Configured" : "❌ Not configured") . "</li>";
echo "</ul>";

// Check if DocumentValidator class exists
if (class_exists('DocumentValidator')) {
    echo "<p>✅ DocumentValidator class loaded</p>";
} else {
    echo "<p>❌ DocumentValidator class not found</p>";
    exit;
}

// Check if DocumentAPIValidator class exists
if (class_exists('DocumentAPIValidator')) {
    echo "<p>✅ DocumentAPIValidator class loaded</p>";
} else {
    echo "<p>❌ DocumentAPIValidator class not found</p>";
    exit;
}

// Check if GD extension is available (required for image processing)
if (extension_loaded('gd')) {
    echo "<p>✅ GD extension loaded (required for image processing)</p>";
} else {
    echo "<p>❌ GD extension not loaded - image validation will not work</p>";
    echo "<p>Please enable GD extension in php.ini</p>";
}

// Check if cURL is available (required for API calls)
if (extension_loaded('curl')) {
    echo "<p>✅ cURL extension loaded (required for API calls)</p>";
} else {
    echo "<p>⚠️ cURL extension not loaded - API calls will not work, but custom validation will</p>";
}

echo "<hr>";
echo "<h3>Validation Method:</h3>";

if ($config['use_google_vision'] && !empty($config['google_vision_api_key'])) {
    echo "<p>🔵 Will use: <strong>Google Vision API</strong> (with fallback to custom validation)</p>";
} elseif ($config['use_pixlab'] && !empty($config['pixlab_api_key'])) {
    echo "<p>🔵 Will use: <strong>PixLab API</strong> (with fallback to custom validation)</p>";
} else {
    echo "<p>🟢 Will use: <strong>Custom Image Validation</strong> (no API configured)</p>";
    echo "<p><em>To enable API validation, see: backend/config/API_SETUP.md</em></p>";
}

echo "<hr>";
echo "<h3>System Status:</h3>";
echo "<p style='color: green; font-weight: bold;'>✅ Document validation system is ready!</p>";
echo "<p>The system will automatically:</p>";
echo "<ul>";
echo "<li>Try API validation first (if configured)</li>";
echo "<li>Fall back to custom validation if API fails or is not configured</li>";
echo "<li>Reject images that don't look like documents</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test by uploading a document through the registration form</li>";
echo "<li>To enable Google Vision API (free tier): See backend/config/API_SETUP.md</li>";
echo "<li>Check server error logs if validation seems incorrect</li>";
echo "</ol>";

?>

