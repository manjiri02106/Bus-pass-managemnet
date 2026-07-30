<?php
/**
 * includes/mailer.php — Real email delivery via PHPMailer (SMTP)
 * Bus Pass Management System · Authentication Module
 *
 * PHPMailer classes are bundled in vendor/PHPMailer/src — no Composer needed.
 * Configure credentials in config/mail.php (Gmail App Password).
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email. Returns ['ok' => bool, 'error' => string]
 */
function send_mail(string $toEmail, string $toName, string $subject,
                   string $bodyHtml, string $bodyText = ''): array
{
    // ---- Option B: native mail() (only if you configured XAMPP sendmail) ----
    if (defined('MAIL_USE_PHP_MAIL') && MAIL_USE_PHP_MAIL) {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n"
                 . 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
        $sent = @mail($toEmail, $subject, $bodyHtml, $headers);
        return $sent ? ['ok' => true, 'error' => '']
                     : ['ok' => false, 'error' => 'PHP mail() failed. Configure sendmail or switch to SMTP in config/mail.php.'];
    }

    // ---- Option A (default): PHPMailer over SMTP ----
    $mail = new PHPMailer(true);
    try {
            $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = MAIL_SMTP_AUTH;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTP_SECURE; // MAIL_SMTP_SECURE is 'tls' which matches ENCRYPTION_STARTTLS
        $mail->Port       = MAIL_PORT;
        // Some XAMPP setups have no CA bundle; disable strict verification
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText !== '' ? $bodyText : trim(strip_tags($bodyHtml));

        $mail->send();
        return ['ok' => true, 'error' => ''];
    } catch (Exception $e) {
        return ['ok' => false,
                'error' => 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo
                         . ' — Check the credentials in config/mail.php (App Password, address, port).'];
    }
}

/**
 * Build & send the password-reset email with a one-click reset link.
 */
function send_reset_email(string $toEmail, string $toName, string $rawToken): array
{
    $link = BASE_URL . '/reset_password.php?email=' . urlencode($toEmail) . '&token=' . urlencode($rawToken);

    $html = '<!DOCTYPE html><html><body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
      <tr><td align="center">
        <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.08);">
          <tr><td style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:28px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:22px;">🚌 ' . APP_NAME . '</h1>
            <p style="margin:6px 0 0;color:#e0e7ff;font-size:13px;">' . APP_TAGLINE . '</p>
          </td></tr>
          <tr><td style="padding:32px;">
            <h2 style="margin:0 0 12px;font-size:18px;color:#0f172a;">Reset your password</h2>
            <p style="color:#475569;font-size:14px;line-height:1.6;">Hi ' . htmlspecialchars($toName) . ',</p>
            <p style="color:#475569;font-size:14px;line-height:1.6;">
              We received a request to reset the password for your <b>' . APP_NAME . '</b> account.
              Click the button below to choose a new password. This link expires in <b>30 minutes</b>.
            </p>
            <p style="text-align:center;margin:28px 0;">
              <a href="' . $link . '" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;
                 text-decoration:none;padding:13px 32px;border-radius:10px;font-size:14px;font-weight:700;
                 display:inline-block;">Reset Password</a>
            </p>
            <p style="color:#94a3b8;font-size:12px;line-height:1.6;">
              If the button does not work, copy this link into your browser:<br>
              <span style="word-break:break-all;color:#6366f1;">' . $link . '</span>
            </p>
            <p style="color:#94a3b8;font-size:12px;">If you didn’t request this, you can safely ignore this email.</p>
          </td></tr>
          <tr><td style="background:#f8fafc;padding:16px;text-align:center;color:#94a3b8;font-size:11px;">
            © ' . date('Y') . ' ' . APP_NAME . ' · Bus Pass Management System
          </td></tr>
        </table>
      </td></tr>
    </table></body></html>';

    $text = "Hi {$toName},\n\nReset your " . APP_NAME . " password using this link (valid 30 minutes):\n{$link}\n\nIf you didn't request this, ignore this email.";

    return send_mail($toEmail, $toName, 'Reset your ' . APP_NAME . ' password', $html, $text);
}
