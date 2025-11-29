<?php
// Email configuration setup
echo "<h1>ERepair Email Configuration Setup</h1>";

echo "<h2>Current Email Configuration</h2>";
echo "<p>To enable email functionality, you need to update the email configuration in <code>backend/config/email.php</code></p>";

echo "<h3>Required Changes:</h3>";
echo "<ol>";
echo "<li><strong>SMTP Server:</strong> Update the SMTP host (currently set to Gmail)</li>";
echo "<li><strong>Email Address:</strong> Replace 'your-email@gmail.com' with your actual email</li>";
echo "<li><strong>Password:</strong> Replace 'your-app-password' with your email app password</li>";
echo "</ol>";

echo "<h3>Gmail Setup Instructions:</h3>";
echo "<ol>";
echo "<li>Enable 2-Factor Authentication on your Gmail account</li>";
echo "<li>Generate an App Password:</li>";
echo "<ul>";
echo "<li>Go to Google Account settings</li>";
echo "<li>Security → 2-Step Verification → App passwords</li>";
echo "<li>Generate a new app password for 'Mail'</li>";
echo "<li>Use this 16-character password in the configuration</li>";
echo "</ul>";
echo "<li>Update the configuration file with your Gmail address and app password</li>";
echo "</ol>";

echo "<h3>Other Email Providers:</h3>";
echo "<ul>";
echo "<li><strong>Outlook/Hotmail:</strong> smtp-mail.outlook.com, Port 587</li>";
echo "<li><strong>Yahoo:</strong> smtp.mail.yahoo.com, Port 587</li>";
echo "<li><strong>Custom SMTP:</strong> Update host, port, and authentication settings</li>";
echo "</ul>";

echo "<h3>Test Email Functionality:</h3>";
echo "<p>After configuring, you can test email sending by:</p>";
echo "<ol>";
echo "<li>Registering a new user account</li>";
echo "<li>Checking if verification emails are sent</li>";
echo "<li>Monitoring server logs for email errors</li>";
echo "</ol>";

echo "<h3>Current Configuration Preview:</h3>";
echo "<pre>";
echo "SMTP Host: smtp.gmail.com\n";
echo "Port: 587\n";
echo "Encryption: STARTTLS\n";
echo "From Email: your-email@gmail.com (NEEDS UPDATE)\n";
echo "Password: your-app-password (NEEDS UPDATE)\n";
echo "</pre>";

echo "<p><strong>Note:</strong> Email functionality is currently disabled until you update the configuration.</p>";

echo "<h3>Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='config/email.php'>View Email Configuration File</a></li>";
echo "<li><a href='test_api.php'>Test API Endpoints</a></li>";
echo "<li><a href='../frontend/auth/index.php'>Go to Frontend</a></li>";
echo "</ul>";
?>
