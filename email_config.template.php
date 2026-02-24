<?php

/**
 * Email Configuration Template
 * 
 * Copy this file to 'email_config.php' and update with your actual credentials.
 * Add 'email_config.php' to .gitignore to keep credentials secure!
 * 
 * Then include this file in orderprocess.php instead of hardcoding credentials.
 */

// SMTP Server Settings
define('SMTP_HOST', 'sandbox.smtp.mailtrap.io'); // MailHog SMTP host
define('SMTP_PORT', 2525); // MailHog SMTP port
define('SMTP_ENCRYPTION', ''); // No encryption for MailHog

// Email Account Credentials
define('SMTP_USERNAME', '0ff01c6e27eeed'); // Not required for MailHog
define('SMTP_PASSWORD', '3463f55aa8c5a3'); // Not required for MailHog

// From Email Settings
define('MAIL_FROM_ADDRESS', 'no-reply@example.test'); // Sender email address
define('MAIL_FROM_NAME', 'Ticket System'); // Sender name

// Optional: BCC for order notifications
define('ADMIN_EMAIL', 'admin@example.com'); // Admin email for order copies
