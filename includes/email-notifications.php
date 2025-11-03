<?php
/**
 * Email Notification System
 *
 * Send email notifications for various events:
 * - Scan completion
 * - Security alerts
 * - Domain expiration warnings
 * - Scheduled report delivery
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/database.php';

class EmailNotificationSystem {
    private $db;
    private $from_email;
    private $from_name;
    private $smtp_enabled = false;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->from_email = getenv('MAIL_FROM') ?: 'noreply@controllodomini.it';
        $this->from_name = getenv('MAIL_FROM_NAME') ?: 'Controllo Domini';
        $this->smtp_enabled = getenv('MAIL_SMTP_ENABLED') === 'true';
    }

    /**
     * Send scan completion notification
     */
    public function sendScanCompletionEmail($user_email, $domain, $scan_results) {
        $subject = "Scan completata per {$domain}";

        $score = $scan_results['overall_score']['score'] ?? 0;
        $grade = $scan_results['overall_score']['grade'] ?? 'N/A';

        // Count recommendations by priority
        $critical = 0;
        $important = 0;
        foreach ($scan_results['recommendations'] ?? [] as $rec) {
            if ($rec['priority'] === 'Critical') $critical++;
            if ($rec['priority'] === 'Important') $important++;
        }

        $body = $this->getEmailTemplate('scan_completion', [
            'domain' => $domain,
            'score' => $score,
            'grade' => $grade,
            'critical_count' => $critical,
            'important_count' => $important,
            'scan_date' => $scan_results['scan_date'] ?? date('Y-m-d H:i:s'),
            'view_url' => 'https://controllodomini.it/complete-scan.php?domain=' . urlencode($domain)
        ]);

        return $this->sendEmail($user_email, $subject, $body);
    }

    /**
     * Send security alert notification
     */
    public function sendSecurityAlertEmail($user_email, $domain, $alert_type, $alert_details) {
        $subject = "🚨 Security Alert: {$domain}";

        $body = $this->getEmailTemplate('security_alert', [
            'domain' => $domain,
            'alert_type' => $alert_type,
            'alert_details' => $alert_details,
            'timestamp' => date('Y-m-d H:i:s'),
            'dashboard_url' => 'https://controllodomini.it/dashboard.php'
        ]);

        return $this->sendEmail($user_email, $subject, $body, true);
    }

    /**
     * Send domain expiration warning
     */
    public function sendExpirationWarningEmail($user_email, $domain, $days_until_expiration, $expiration_date) {
        $subject = "⚠️ Domain expiring soon: {$domain}";

        $body = $this->getEmailTemplate('expiration_warning', [
            'domain' => $domain,
            'days_remaining' => $days_until_expiration,
            'expiration_date' => $expiration_date,
            'urgency' => $days_until_expiration <= 7 ? 'URGENT' : 'WARNING'
        ]);

        return $this->sendEmail($user_email, $subject, $body, true);
    }

    /**
     * Send scheduled report
     */
    public function sendScheduledReportEmail($user_email, $report_name, $attachment_path) {
        $subject = "📊 Scheduled Report: {$report_name}";

        $body = $this->getEmailTemplate('scheduled_report', [
            'report_name' => $report_name,
            'generated_date' => date('Y-m-d H:i:s'),
            'has_attachment' => !empty($attachment_path)
        ]);

        return $this->sendEmail($user_email, $subject, $body, false, $attachment_path);
    }

    /**
     * Send webhook failure notification
     */
    public function sendWebhookFailureEmail($user_email, $webhook_url, $error_message) {
        $subject = "Webhook Failure Notification";

        $body = $this->getEmailTemplate('webhook_failure', [
            'webhook_url' => $webhook_url,
            'error_message' => $error_message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $this->sendEmail($user_email, $subject, $body);
    }

    /**
     * Send generic email
     */
    private function sendEmail($to, $subject, $body, $high_priority = false, $attachment = null) {
        try {
            if ($this->smtp_enabled) {
                return $this->sendSMTP($to, $subject, $body, $high_priority, $attachment);
            } else {
                return $this->sendPHP($to, $subject, $body, $high_priority, $attachment);
            }
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using PHP mail()
     */
    private function sendPHP($to, $subject, $body, $high_priority = false, $attachment = null) {
        $headers = [
            "From: {$this->from_name} <{$this->from_email}>",
            "Reply-To: {$this->from_email}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "X-Mailer: Controllo Domini v4.2"
        ];

        if ($high_priority) {
            $headers[] = "X-Priority: 1 (Highest)";
            $headers[] = "X-MSMail-Priority: High";
            $headers[] = "Importance: High";
        }

        $headers_string = implode("\r\n", $headers);

        // For now, skip attachments with PHP mail (complex boundary handling)
        // In production, use PHPMailer or similar for attachments

        return mail($to, $subject, $body, $headers_string);
    }

    /**
     * Send email using SMTP (placeholder for future implementation)
     */
    private function sendSMTP($to, $subject, $body, $high_priority = false, $attachment = null) {
        // TODO: Implement SMTP sending with PHPMailer or similar
        // For now, fallback to PHP mail
        return $this->sendPHP($to, $subject, $body, $high_priority, $attachment);
    }

    /**
     * Get email template
     */
    private function getEmailTemplate($template_name, $vars = []) {
        $templates = [
            'scan_completion' => $this->getScanCompletionTemplate($vars),
            'security_alert' => $this->getSecurityAlertTemplate($vars),
            'expiration_warning' => $this->getExpirationWarningTemplate($vars),
            'scheduled_report' => $this->getScheduledReportTemplate($vars),
            'webhook_failure' => $this->getWebhookFailureTemplate($vars)
        ];

        return $templates[$template_name] ?? '';
    }

    /**
     * Scan completion email template
     */
    private function getScanCompletionTemplate($vars) {
        $domain = htmlspecialchars($vars['domain']);
        $score = $vars['score'];
        $grade = $vars['grade'];
        $critical = $vars['critical_count'];
        $important = $vars['important_count'];
        $scan_date = $vars['scan_date'];
        $view_url = htmlspecialchars($vars['view_url']);

        $grade_color = [
            'A' => '#28a745',
            'B' => '#17a2b8',
            'C' => '#ffc107',
            'D' => '#fd7e14',
            'F' => '#dc3545'
        ][$grade] ?? '#6c757d';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                            <h1 style="color: white; margin: 0; font-size: 28px;">✅ Scan Completata</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #333; margin-top: 0;">Ciao!</h2>
                            <p style="color: #666; line-height: 1.6;">
                                La scansione completa del dominio <strong style="color: #667eea;">{$domain}</strong> è stata completata con successo.
                            </p>

                            <!-- Score Card -->
                            <div style="background: {$grade_color}; color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
                                <div style="font-size: 48px; font-weight: bold;">{$score}</div>
                                <div style="font-size: 18px; opacity: 0.9;">Grade: {$grade}</div>
                            </div>

                            <!-- Summary -->
                            <table width="100%" cellpadding="10" style="margin: 20px 0;">
                                <tr>
                                    <td style="background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                                        <strong style="color: #856404;">⚠️ {$critical} Critical Issues</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="background: #d1ecf1; border-left: 4px solid #17a2b8; border-radius: 4px;">
                                        <strong style="color: #0c5460;">ℹ️ {$important} Important Recommendations</strong>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #666; line-height: 1.6;">
                                <strong>Data scan:</strong> {$scan_date}
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{$view_url}" style="background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                                    Visualizza Report Completo
                                </a>
                            </div>

                            <p style="color: #999; font-size: 14px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                                Questa è una email automatica da Controllo Domini. Per domande o supporto, visita
                                <a href="https://controllodomini.it" style="color: #667eea;">controllodomini.it</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;">
                            © 2025 G Tech Group - Controllo Domini<br>
                            Tutti i diritti riservati
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Security alert email template
     */
    private function getSecurityAlertTemplate($vars) {
        $domain = htmlspecialchars($vars['domain']);
        $alert_type = htmlspecialchars($vars['alert_type']);
        $alert_details = htmlspecialchars($vars['alert_details']);
        $timestamp = $vars['timestamp'];
        $dashboard_url = htmlspecialchars($vars['dashboard_url']);

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); padding: 30px; text-align: center;">
                            <h1 style="color: white; margin: 0; font-size: 28px;">🚨 Security Alert</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                                <strong style="color: #721c24;">Security issue detected for: {$domain}</strong>
                            </div>

                            <h3 style="color: #dc3545;">Alert Type: {$alert_type}</h3>
                            <p style="color: #666; line-height: 1.6;">{$alert_details}</p>

                            <p style="color: #999; font-size: 14px; margin-top: 20px;">
                                <strong>Timestamp:</strong> {$timestamp}
                            </p>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{$dashboard_url}" style="background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                                    View Dashboard
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;">
                            © 2025 G Tech Group - Controllo Domini
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Expiration warning email template
     */
    private function getExpirationWarningTemplate($vars) {
        $domain = htmlspecialchars($vars['domain']);
        $days = $vars['days_remaining'];
        $expiration_date = $vars['expiration_date'];
        $urgency = $vars['urgency'];

        $bg_color = $days <= 7 ? '#dc3545' : '#ffc107';
        $text_color = $days <= 7 ? '#721c24' : '#856404';

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td style="background: {$bg_color}; padding: 30px; text-align: center;">
                            <h1 style="color: white; margin: 0; font-size: 28px;">⚠️ Domain Expiration Warning</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <div style="background: #fff3cd; border-left: 4px solid {$bg_color}; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                                <strong style="color: {$text_color};">{$urgency}: {$domain} expires in {$days} days!</strong>
                            </div>

                            <p style="color: #666; line-height: 1.6; font-size: 16px;">
                                Il dominio <strong>{$domain}</strong> scadrà tra <strong>{$days} giorni</strong>.
                            </p>

                            <p style="color: #666; line-height: 1.6;">
                                <strong>Data di scadenza:</strong> {$expiration_date}
                            </p>

                            <p style="color: #666; line-height: 1.6;">
                                Assicurati di rinnovare il dominio prima della scadenza per evitare interruzioni del servizio.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;">
                            © 2025 G Tech Group - Controllo Domini
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Scheduled report email template
     */
    private function getScheduledReportTemplate($vars) {
        $report_name = htmlspecialchars($vars['report_name']);
        $generated_date = $vars['generated_date'];

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 30px; text-align: center;">
                            <h1 style="color: white; margin: 0; font-size: 28px;">📊 Scheduled Report</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #333; margin-top: 0;">Your report is ready!</h2>
                            <p style="color: #666; line-height: 1.6;">
                                Il report programmato "<strong>{$report_name}</strong>" è stato generato con successo.
                            </p>
                            <p style="color: #999; font-size: 14px;">
                                <strong>Generated:</strong> {$generated_date}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;">
                            © 2025 G Tech Group - Controllo Domini
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Webhook failure email template
     */
    private function getWebhookFailureTemplate($vars) {
        $webhook_url = htmlspecialchars($vars['webhook_url']);
        $error_message = htmlspecialchars($vars['error_message']);
        $timestamp = $vars['timestamp'];

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td style="background: #fd7e14; padding: 30px; text-align: center;">
                            <h1 style="color: white; margin: 0; font-size: 28px;">⚠️ Webhook Failure</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <p style="color: #666; line-height: 1.6;">
                                Failed to deliver webhook to: <code style="background: #f8f9fa; padding: 2px 5px; border-radius: 3px;">{$webhook_url}</code>
                            </p>
                            <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 4px;">
                                <strong style="color: #721c24;">Error:</strong><br>
                                <code style="color: #666;">{$error_message}</code>
                            </div>
                            <p style="color: #999; font-size: 14px;">
                                <strong>Timestamp:</strong> {$timestamp}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;">
                            © 2025 G Tech Group - Controllo Domini
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}

/**
 * Helper function to get email notification instance
 */
function getEmailNotifications() {
    static $instance = null;
    if ($instance === null) {
        $instance = new EmailNotificationSystem();
    }
    return $instance;
}
