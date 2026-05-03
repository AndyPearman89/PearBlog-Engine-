<?php
/**
 * SMSProvider
 *
 * Sends SMS notifications to contractors.
 *
 * @package PearBlog\LeadAI\Infrastructure
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Infrastructure;

/**
 * SMS Provider
 *
 * Integration with SMS gateway (e.g., Twilio, SMSApi.pl).
 */
class SMSProvider {
	private string $api_key;
	private string $sender_name;
	private bool $enabled;

	public function __construct() {
		$this->api_key     = get_option('pt24_sms_api_key', '');
		$this->sender_name = get_option('pt24_sms_sender', 'PT24');
		$this->enabled     = (bool) get_option('pt24_sms_enabled', false);
	}

	/**
	 * Send SMS message.
	 *
	 * @param string $phone Phone number (international format).
	 * @param string $message Message content (max 160 chars recommended).
	 * @return bool Success status.
	 */
	public function send(string $phone, string $message): bool {
		if (!$this->enabled) {
			error_log('[SMSProvider] SMS disabled in settings');
			return false;
		}

		if (empty($this->api_key)) {
			error_log('[SMSProvider] API key not configured');
			return false;
		}

		// Normalize phone number
		$phone = $this->normalizePhone($phone);

		// Truncate message if too long
		if (strlen($message) > 160) {
			$message = substr($message, 0, 157) . '...';
		}

		// Send via SMS gateway
		$result = $this->sendViaSMSApi($phone, $message);

		// Log result
		$this->logSMS($phone, $message, $result);

		return $result;
	}

	/**
	 * Send via SMSApi.pl (Polish SMS gateway).
	 */
	private function sendViaSMSApi(string $phone, string $message): bool {
		$url = 'https://api.smsapi.pl/sms.do';

		$response = wp_remote_post($url, [
			'body' => [
				'access_token' => $this->api_key,
				'to'           => $phone,
				'message'      => $message,
				'from'         => $this->sender_name,
				'format'       => 'json',
			],
			'timeout' => 10,
		]);

		if (is_wp_error($response)) {
			error_log('[SMSProvider] Error: ' . $response->get_error_message());
			return false;
		}

		$status_code = wp_remote_retrieve_response_code($response);
		return $status_code === 200;
	}

	/**
	 * Normalize phone number to international format.
	 */
	private function normalizePhone(string $phone): string {
		// Remove non-numeric characters
		$phone = preg_replace('/[^0-9+]/', '', $phone);

		// Add +48 prefix for Polish numbers if missing
		if (!str_starts_with($phone, '+')) {
			if (strlen($phone) === 9) {
				$phone = '+48' . $phone;
			} else {
				$phone = '+' . $phone;
			}
		}

		return $phone;
	}

	/**
	 * Log SMS for audit trail.
	 */
	private function logSMS(string $phone, string $message, bool $success): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_sms_log';

		$wpdb->insert(
			$table_name,
			[
				'phone'      => $phone,
				'message'    => $message,
				'success'    => $success ? 1 : 0,
				'sent_at'    => current_time('mysql'),
			],
			['%s', '%s', '%d', '%s']
		);
	}
}
