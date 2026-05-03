<?php
/**
 * EmailProvider
 *
 * Sends email notifications to contractors and customers.
 *
 * @package PearBlog\LeadAI\Infrastructure
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Infrastructure;

/**
 * Email Provider
 *
 * WordPress-based email delivery with HTML templates.
 */
class EmailProvider {
	private string $from_email;
	private string $from_name;

	public function __construct() {
		$this->from_email = get_option('pt24_email_from', get_option('admin_email'));
		$this->from_name  = get_option('pt24_email_from_name', 'PT24');
	}

	/**
	 * Send email message.
	 *
	 * @param string $to Recipient email address.
	 * @param string $subject Email subject.
	 * @param string $message Email body (HTML supported).
	 * @param array $headers Optional headers.
	 * @return bool Success status.
	 */
	public function send(string $to, string $subject, string $message, array $headers = []): bool {
		// Set default headers
		$default_headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->from_name . ' <' . $this->from_email . '>',
		];

		$headers = array_merge($default_headers, $headers);

		// Wrap message in template
		$html_message = $this->wrapInTemplate($message, $subject);

		// Send email
		$result = wp_mail($to, $subject, $html_message, $headers);

		// Log result
		$this->logEmail($to, $subject, $result);

		return $result;
	}

	/**
	 * Wrap message in HTML template.
	 */
	private function wrapInTemplate(string $message, string $subject): string {
		$logo_url = get_option('pt24_logo_url', '');

		return <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{$subject}</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
		.container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
		.content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
		.footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
		a { color: #2563eb; text-decoration: none; }
		.button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 6px; margin: 20px 0; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h2>PT24</h2>
		</div>
		<div class="content">
			{$message}
		</div>
		<div class="footer">
			<p>PT24 - Marketplace usług profesjonalnych</p>
			<p><a href="https://pt24.pl">pt24.pl</a></p>
		</div>
	</div>
</body>
</html>
HTML;
	}

	/**
	 * Log email for audit trail.
	 */
	private function logEmail(string $to, string $subject, bool $success): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_email_log';

		$wpdb->insert(
			$table_name,
			[
				'to_email'   => $to,
				'subject'    => $subject,
				'success'    => $success ? 1 : 0,
				'sent_at'    => current_time('mysql'),
			],
			['%s', '%s', '%d', '%s']
		);
	}

	/**
	 * Send templated email with variables.
	 *
	 * @param string $to Recipient email.
	 * @param string $template_name Template identifier.
	 * @param array $variables Template variables.
	 * @return bool Success status.
	 */
	public function sendTemplate(string $to, string $template_name, array $variables): bool {
		$template = $this->getTemplate($template_name);

		if (!$template) {
			error_log('[EmailProvider] Template not found: ' . $template_name);
			return false;
		}

		// Replace variables
		$subject = $this->replaceVariables($template['subject'], $variables);
		$message = $this->replaceVariables($template['message'], $variables);

		return $this->send($to, $subject, $message);
	}

	/**
	 * Get email template.
	 */
	private function getTemplate(string $template_name): ?array {
		$templates = [
			'lead_notification' => [
				'subject' => 'PT24: Nowe zlecenie - {{category}}',
				'message' => <<<MSG
<h2>Nowe zlecenie wymaga Twojej odpowiedzi</h2>

<p><strong>Kategoria:</strong> {{category}}</p>
<p><strong>Lokalizacja:</strong> {{location}}</p>
<p><strong>Czas oczekiwania:</strong> {{elapsed_time}} minut</p>

<p>{{message}}</p>

<p><a href="{{lead_url}}" class="button">Zobacz szczegóły zlecenia</a></p>

<p>Pamiętaj, że szybka odpowiedź zwiększa Twoje szanse na zlecenie!</p>
MSG,
			],
			'ai_reply_sent' => [
				'subject' => 'PT24: Odpowiedź systemowa na Twoje zapytanie',
				'message' => <<<MSG
<h2>Dziękujemy za zapytanie</h2>

<p>Otrzymaliśmy Twoje zgłoszenie dotyczące: <strong>{{category}}</strong></p>

<p>{{ai_reply}}</p>

<p>Wkrótce skontaktują się z Tobą nasi wykonawcy.</p>
MSG,
			],
		];

		return $templates[$template_name] ?? null;
	}

	/**
	 * Replace template variables.
	 */
	private function replaceVariables(string $text, array $variables): string {
		foreach ($variables as $key => $value) {
			$text = str_replace('{{' . $key . '}}', $value, $text);
		}

		return $text;
	}
}
