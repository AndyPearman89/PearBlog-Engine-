<?php
/**
 * Lead Generation System
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Lead Generation System
 */
class LeadGenerator {

	/**
	 * Submit lead from form
	 *
	 * @param array{name: string, email: string, phone: string, city: string, message: string, category: string} $data
	 * @return int Lead post ID
	 */
	public function submit_lead( array $data ): int {
		// Validate required fields
		$required = [ 'name', 'email', 'phone', 'city' ];
		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				throw new \InvalidArgumentException( "Missing required field: {$field}" );
			}
		}

		// Validate email
		if ( ! is_email( $data['email'] ) ) {
			throw new \InvalidArgumentException( 'Invalid email address' );
		}

		// Create lead post
		$post_id = wp_insert_post( [
			'post_title' => sprintf(
				'Lead: %s - %s',
				$data['name'],
				$data['category'] ?? 'General'
			),
			'post_type' => 'pearblog_lead',
			'post_status' => 'publish',
			'meta_input' => [
				'pearblog_lead_name' => sanitize_text_field( $data['name'] ),
				'pearblog_lead_email' => sanitize_email( $data['email'] ),
				'pearblog_lead_phone' => sanitize_text_field( $data['phone'] ),
				'pearblog_lead_city' => sanitize_text_field( $data['city'] ),
				'pearblog_lead_message' => sanitize_textarea_field( $data['message'] ?? '' ),
				'pearblog_lead_category' => sanitize_text_field( $data['category'] ?? '' ),
				'pearblog_lead_source_url' => sanitize_url( $data['source_url'] ?? $_SERVER['HTTP_REFERER'] ?? '' ),
				'pearblog_lead_status' => 'new',
				'pearblog_lead_created_at' => current_time( 'mysql' ),
			],
		] );

		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( 'Failed to create lead: ' . $post_id->get_error_message() );
		}

		// Send notifications
		$this->send_lead_notifications( $post_id, $data );

		// Track analytics event
		do_action( 'pearblog_lead_generated', $post_id, $data );

		return $post_id;
	}

	/**
	 * Send lead notifications
	 *
	 * @param int $lead_id
	 * @param array<string, mixed> $data
	 */
	private function send_lead_notifications( int $lead_id, array $data ): void {
		// Send email to admin
		$admin_email = get_option( 'admin_email' );
		$subject = sprintf( 'Nowe zapytanie: %s', $data['category'] ?? 'General' );
		$message = sprintf(
			"Nowe zapytanie od: %s\n\nEmail: %s\nTelefon: %s\nMiasto: %s\n\nWiadomość:\n%s\n\nŹródło: %s",
			$data['name'],
			$data['email'],
			$data['phone'],
			$data['city'],
			$data['message'] ?? '',
			$data['source_url'] ?? ''
		);

		wp_mail( $admin_email, $subject, $message );

		// Send confirmation to user
		$user_subject = 'Potwierdzenie otrzymania zapytania';
		$user_message = sprintf(
			"Cześć %s,\n\nDziękujemy za przesłanie zapytania. Skontaktujemy się z Tobą wkrótce.\n\nTwoje dane:\nEmail: %s\nTelefon: %s\nMiasto: %s\n\nPozdrawiamy,\nZespół Poradnik.pro",
			$data['name'],
			$data['email'],
			$data['phone'],
			$data['city']
		);

		wp_mail( $data['email'], $user_subject, $user_message );

		// Send Slack/Discord notification if configured
		$this->send_slack_notification( $data );
	}

	/**
	 * Send Slack notification
	 *
	 * @param array<string, mixed> $data
	 */
	private function send_slack_notification( array $data ): void {
		$webhook_url = get_option( 'pearblog_alert_slack_webhook' );
		if ( ! $webhook_url ) {
			return;
		}

		$payload = [
			'text' => sprintf(
				'🔔 *Nowy lead*: %s\n📧 %s | 📞 %s | 🏙️ %s\n📂 Kategoria: %s',
				$data['name'],
				$data['email'],
				$data['phone'],
				$data['city'],
				$data['category'] ?? 'General'
			),
		];

		wp_remote_post( $webhook_url, [
			'body' => wp_json_encode( $payload ),
			'headers' => [ 'Content-Type' => 'application/json' ],
		] );
	}

	/**
	 * Match lead with experts
	 *
	 * @param int $lead_id
	 * @return array<Expert>
	 */
	public function match_experts( int $lead_id ): array {
		$category = get_post_meta( $lead_id, 'pearblog_lead_category', true );
		$city = get_post_meta( $lead_id, 'pearblog_lead_city', true );

		// Find experts matching category and location
		$args = [
			'post_type' => 'pearblog_expert',
			'post_status' => 'publish',
			'posts_per_page' => 5,
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => 'pearblog_expert_category',
					'value' => $category,
					'compare' => '=',
				],
			],
		];

		$posts = get_posts( $args );
		$experts = [];

		foreach ( $posts as $post ) {
			$expert = Expert::from_post( $post );

			// Filter by location
			if ( $expert->location['city'] === $city ) {
				$experts[] = $expert;
			}
		}

		// Sort by rating
		usort( $experts, function( $a, $b ) {
			return $b->rating <=> $a->rating;
		} );

		return $experts;
	}

	/**
	 * Register custom post type
	 */
	public function register_post_type(): void {
		register_post_type( 'pearblog_lead', [
			'labels' => [
				'name' => 'Leady',
				'singular_name' => 'Lead',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'capability_type' => 'post',
			'capabilities' => [
				'create_posts' => 'do_not_allow',
			],
			'map_meta_cap' => true,
			'supports' => [ 'title' ],
		] );
	}
}
