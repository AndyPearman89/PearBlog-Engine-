<?php
/**
 * Expert Model - Service providers and specialists
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Expert model for marketplace
 */
class Expert {

	/** @var int */
	public int $id;

	/** @var string */
	public string $name;

	/** @var string */
	public string $slug;

	/** @var string */
	public string $bio;

	/** @var string */
	public string $category;

	/** @var array<string> Specializations */
	public array $specializations;

	/** @var array{city: string, region: string} */
	public array $location;

	/** @var float Rating 0-5 */
	public float $rating;

	/** @var int Number of reviews */
	public int $review_count;

	/** @var bool */
	public bool $verified;

	/** @var bool */
	public bool $premium;

	/** @var string|null Profile photo URL */
	public ?string $photo;

	/** @var array{phone: string, email: string, website: string} */
	public array $contact;

	/** @var array<array{service: string, price: float, unit: string}> */
	public array $services;

	/** @var array<string> Portfolio image URLs */
	public array $portfolio;

	/** @var string ISO 8601 timestamp */
	public string $created_at;

	/** @var string ISO 8601 timestamp */
	public string $updated_at;

	/**
	 * Create from custom post type
	 *
	 * @param \WP_Post $post
	 * @return self
	 */
	public static function from_post( \WP_Post $post ): self {
		$expert = new self();
		$expert->id = $post->ID;
		$expert->name = $post->post_title;
		$expert->slug = $post->post_name;
		$expert->bio = $post->post_content;

		$expert->category = get_post_meta( $post->ID, 'pearblog_expert_category', true ) ?: '';

		$specializations_data = get_post_meta( $post->ID, 'pearblog_expert_specializations', true );
		$expert->specializations = $specializations_data ? json_decode( $specializations_data, true ) : [];

		$location_data = get_post_meta( $post->ID, 'pearblog_expert_location', true );
		$expert->location = $location_data ? json_decode( $location_data, true ) : [];

		$expert->rating = (float) get_post_meta( $post->ID, 'pearblog_expert_rating', true );
		$expert->review_count = (int) get_post_meta( $post->ID, 'pearblog_expert_review_count', true );

		$expert->verified = (bool) get_post_meta( $post->ID, 'pearblog_expert_verified', true );
		$expert->premium = (bool) get_post_meta( $post->ID, 'pearblog_expert_premium', true );

		$expert->photo = get_post_meta( $post->ID, 'pearblog_expert_photo', true ) ?: null;

		$contact_data = get_post_meta( $post->ID, 'pearblog_expert_contact', true );
		$expert->contact = $contact_data ? json_decode( $contact_data, true ) : [
			'phone' => '',
			'email' => '',
			'website' => '',
		];

		$services_data = get_post_meta( $post->ID, 'pearblog_expert_services', true );
		$expert->services = $services_data ? json_decode( $services_data, true ) : [];

		$portfolio_data = get_post_meta( $post->ID, 'pearblog_expert_portfolio', true );
		$expert->portfolio = $portfolio_data ? json_decode( $portfolio_data, true ) : [];

		$expert->created_at = $post->post_date;
		$expert->updated_at = $post->post_modified;

		return $expert;
	}

	/**
	 * Save to WordPress
	 *
	 * @return int Post ID
	 */
	public function save(): int {
		if ( $this->id > 0 ) {
			wp_update_post( [
				'ID' => $this->id,
				'post_title' => $this->name,
				'post_name' => $this->slug,
				'post_content' => $this->bio,
			] );
		} else {
			$this->id = wp_insert_post( [
				'post_title' => $this->name,
				'post_name' => $this->slug,
				'post_content' => $this->bio,
				'post_status' => 'publish',
				'post_type' => 'pearblog_expert',
			] );
		}

		update_post_meta( $this->id, 'pearblog_expert_category', $this->category );
		update_post_meta( $this->id, 'pearblog_expert_specializations', wp_json_encode( $this->specializations ) );
		update_post_meta( $this->id, 'pearblog_expert_location', wp_json_encode( $this->location ) );
		update_post_meta( $this->id, 'pearblog_expert_rating', $this->rating );
		update_post_meta( $this->id, 'pearblog_expert_review_count', $this->review_count );
		update_post_meta( $this->id, 'pearblog_expert_verified', $this->verified );
		update_post_meta( $this->id, 'pearblog_expert_premium', $this->premium );
		update_post_meta( $this->id, 'pearblog_expert_photo', $this->photo );
		update_post_meta( $this->id, 'pearblog_expert_contact', wp_json_encode( $this->contact ) );
		update_post_meta( $this->id, 'pearblog_expert_services', wp_json_encode( $this->services ) );
		update_post_meta( $this->id, 'pearblog_expert_portfolio', wp_json_encode( $this->portfolio ) );

		return $this->id;
	}

	/**
	 * Render expert card
	 *
	 * @return string HTML
	 */
	public function render_card(): string {
		$premium_class = $this->premium ? ' premium' : '';
		$verified_badge = $this->verified ? ' <span class="badge badge-verified">Zweryfikowany</span>' : '';

		$html = '<div class="expert-card' . $premium_class . '">';

		if ( $this->photo ) {
			$html .= '<div class="expert-photo">';
			$html .= '<img src="' . esc_url( $this->photo ) . '" alt="' . esc_attr( $this->name ) . '" />';
			$html .= '</div>';
		}

		$html .= '<div class="expert-content">';
		$html .= '<h3>' . esc_html( $this->name ) . $verified_badge . '</h3>';
		$html .= '<p class="expert-category">' . esc_html( $this->category ) . '</p>';
		$html .= '<p class="expert-location">' . esc_html( $this->location['city'] ) . '</p>';

		$html .= '<div class="expert-rating">';
		$html .= str_repeat( '⭐', (int) round( $this->rating ) );
		$html .= ' <span>' . number_format( $this->rating, 1 ) . '</span>';
		$html .= ' <span class="review-count">(' . $this->review_count . ' opinii)</span>';
		$html .= '</div>';

		$html .= '<p class="expert-bio">' . esc_html( wp_trim_words( $this->bio, 20 ) ) . '</p>';

		if ( ! empty( $this->specializations ) ) {
			$html .= '<div class="expert-specializations">';
			foreach ( array_slice( $this->specializations, 0, 3 ) as $spec ) {
				$html .= '<span class="badge">' . esc_html( $spec ) . '</span> ';
			}
			$html .= '</div>';
		}

		$html .= '<a href="' . esc_url( get_permalink( $this->id ) ) . '" class="btn btn-primary">Zobacz profil</a>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Add review and update rating
	 *
	 * @param float $new_rating Rating 1-5
	 */
	public function add_review( float $new_rating ): void {
		$total = $this->rating * $this->review_count;
		$this->review_count++;
		$this->rating = ( $total + $new_rating ) / $this->review_count;
	}
}
