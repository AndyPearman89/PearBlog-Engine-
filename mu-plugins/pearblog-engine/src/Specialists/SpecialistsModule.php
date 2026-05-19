<?php
/**
 * Specialists Module bootstrapper.
 *
 * Registers the module with ModuleRegistry, creates DB tables on activation,
 * and attaches the REST API controller and event listeners.
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\FeatureFlags;
use PearBlogEngine\Core\ModuleRegistry;
use PearBlogEngine\Core\ReviewPublishedEvent;
use PearBlogEngine\Core\SpecialistVerifiedEvent;
use PearBlogEngine\Rankings\RankingService;

/**
 * SpecialistsModule
 *
 * Call register() once from Plugin::boot().
 */
class SpecialistsModule {

	private SpecialistProfile  $profiles;
	private ReviewSystem       $reviews;
	private BadgeEngine        $badges;
	private VerificationEngine $verification;

	public function __construct() {
		$this->profiles     = new SpecialistProfile();
		$this->reviews      = new ReviewSystem( $this->profiles );
		$this->badges       = new BadgeEngine();
		$this->verification = new VerificationEngine();
	}

	/**
	 * Register the module: DB tables, REST routes, event listeners.
	 */
	public function register(): void {
		if ( FeatureFlags::disabled( 'specialists_marketplace' ) ) {
			return;
		}

		ModuleRegistry::add(
			'specialists',
			'Specialists Marketplace',
			'1.0.0',
			__NAMESPACE__
		);

		// DB tables on activation.
		register_activation_hook(
			PEARBLOG_PLUGIN_FILE,
			static function (): void {
				( new SpecialistsSchema() )->create_tables();
			}
		);

		// REST API.
		add_action( 'rest_api_init', function (): void {
			( new SpecialistsController(
				$this->profiles,
				$this->reviews,
				$this->badges,
				$this->verification
			) )->register_routes();
		} );

		// When a review is published → re-evaluate badges for that specialist.
		EventBus::listen(
			ReviewPublishedEvent::class,
			function ( ReviewPublishedEvent $event ): void {
				$profile = $this->profiles->find( $event->specialist_id );
				if ( $profile ) {
					$this->badges->evaluate( $event->specialist_id, $profile );
				}
			}
		);

		// When a specialist is verified → recalculate their ranking score.
		EventBus::listen(
			SpecialistVerifiedEvent::class,
			function ( SpecialistVerifiedEvent $event ): void {
				$service = new RankingService();
				$profile = $this->profiles->find( $event->specialist_id );
				if ( $profile ) {
					$service->recalculate_score(
						$event->specialist_id,
						$profile['category'],
						$profile['city']
					);
				}
			}
		);

		// Admin meta boxes for the pearblog_expert CPT (badge and verification UI).
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post_pearblog_expert', [ $this, 'save_meta_boxes' ], 10, 2 );
	}

	// -----------------------------------------------------------------------
	// Admin meta boxes
	// -----------------------------------------------------------------------

	public function register_meta_boxes(): void {
		add_meta_box(
			'pearblog_specialist_badges',
			'🏅 Odznaki specjalisty',
			[ $this, 'render_badges_meta_box' ],
			'pearblog_expert',
			'side'
		);

		add_meta_box(
			'pearblog_specialist_verification',
			'✅ Weryfikacja',
			[ $this, 'render_verification_meta_box' ],
			'pearblog_expert',
			'side'
		);
	}

	public function render_badges_meta_box( \WP_Post $post ): void {
		$badges = $this->badges->get_badges( $post->ID );
		echo '<ul style="margin:0;padding:0;list-style:none;">';
		foreach ( $badges as $badge ) {
			echo '<li>' . esc_html( $badge['icon'] ?? '🏅' ) . ' ' . esc_html( $badge['label'] ) . '</li>';
		}
		if ( empty( $badges ) ) {
			echo '<li><em>Brak odznak</em></li>';
		}
		echo '</ul>';
	}

	public function render_verification_meta_box( \WP_Post $post ): void {
		$level  = get_post_meta( $post->ID, '_pearblog_verification_level', true ) ?: 'none';
		$labels = [ 'none' => '⚪ Niezweryfikowany', 'bronze' => '🥉 Bronze', 'silver' => '🥈 Silver', 'gold' => '🥇 Gold' ];
		echo '<p>' . esc_html( $labels[ $level ] ?? $level ) . '</p>';
		wp_nonce_field( 'pearblog_verification', 'pearblog_verification_nonce' );
		echo '<select name="pearblog_verification_level">';
		foreach ( $labels as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $level, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	}

	public function save_meta_boxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['pearblog_verification_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['pearblog_verification_nonce'] ), 'pearblog_verification' )
			|| ! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		$level = sanitize_key( $_POST['pearblog_verification_level'] ?? 'none' );
		if ( in_array( $level, [ 'none', 'bronze', 'silver', 'gold' ], true ) ) {
			update_post_meta( $post_id, '_pearblog_verification_level', $level );
		}
	}
}
