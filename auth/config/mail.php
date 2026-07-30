<?php
/**
 * config/mail.php — SMTP / Email configuration
 * Bus Pass Management System · Authentication Module
 *
 * =====================================================================
 *  THIS IS THE FILE THAT MAKES "FORGOT PASSWORD" SEND A REAL EMAIL.
 * =====================================================================
 * XAMPP cannot send mail on its own, so we use PHPMailer over SMTP.
 * The easiest free option is Gmail + an "App Password".
 *
 * HOW TO GET YOUR GMAIL APP PASSWORD (free, 2 minutes):
 *  1. Go to https://myaccount.google.com/security
 *  2. Turn ON "2-Step Verification".
 *  3. Go to https://myaccount.google.com/apppasswords
 *  4. Create an app password (name it "BusPass"), copy the 16-char key.
 *  5. Paste it below as MAIL_PASSWORD (remove spaces).
 *  6. Put the same Gmail address in MAIL_USERNAME and MAIL_FROM.
 *
 * The reset link is delivered to the recipient's inbox within seconds.
 */

// --- SMTP credentials (Gmail example) --------------------------------
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_SMTP_SECURE', 'tls');          // tls for 587, ssl for 465
define('MAIL_USERNAME', 'sujalpclogin@gmail.com');      // ← your Gmail
define('MAIL_PASSWORD', 'iwfdpqkbeuuhzlui');        // ← 16-char App Password
define('MAIL_SMTP_AUTH', true);

// --- Sender identity shown to the recipient ---------------------------
define('MAIL_FROM',     'sujalpclogin@gmail.com');      // ← same Gmail address
define('MAIL_FROM_NAME','BPMS devs');

// Fallback: if you configure XAMPP's sendmail instead of SMTP,
// set this to true to use PHP's native mail() function.
define('MAIL_USE_PHP_MAIL', false);
