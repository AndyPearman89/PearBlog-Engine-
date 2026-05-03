<?php
/**
 * Offer Model - Local service offers
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Offer model for local discovery
 */
class Offer {

	/** @var int */
	public int $id;

	/** @var string */
	public string $title;

	/** @var string */
	public string $slug;

	/** @var string */
	public string $description;

	/** @var array{city: string, region: string, address: string, lat: float, lng: float} */
	public array $location;

	/** @var int Provider/Expert ID */
	public int $provider_id;

	/** @var array{min: float, max: float, currency: string} */
	public array $price_range;

	/** @var string */
	public string $category;

	/** @var array<string> */
	public array $tags;

	/** @var array<string> Image URLs */
	public array $images;

	/** @var bool */
	public bool $featured;

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
		$offer = new self();
		$offer->id = $post->ID;
		$offer->title = $post->post_title;
		$offer->slug = $post->post_name;
		$offer->description = $post->post_content;

		$location_data = get_post_meta( $post->ID, 'pearblog_offer_location', true );
		$offer->location = $location_data ? json_decode( $location_data, true ) : [];

		$offer->provider_id = (int) get_post_meta( $post->ID, 'pearblog_offer_provider_id', true );

		$price_data = get_post_meta( $post->ID, 'pearblog_offer_price_range', true );
		$offer->price_range = $price_data ? json_decode( $price_data, true ) : [
			'min' => 0,
			'max' => 0,
			'currency' => 'PLN',
		];

		$offer->category = get_post_meta( $post->ID, 'pearblog_offer_category', true ) ?: '';

		$tags = wp_get_post_terms( $post->ID, 'pearblog_offer_tag', [ 'fields' => 'names' ] );
		$offer->tags = is_array( $tags ) ? $tags : [];

		$images_data = get_post_meta( $post->ID, 'pearblog_offer_images', true );
		$offer->images = $images_data ? json_decode( $images_data, true ) : [];

		$offer->featured = (bool) get_post_meta( $post->ID, 'pearblog_offer_featured', true );

		$offer->created_at = $post->post_date;
		$offer->updated_at = $post->post_modified;

		return $offer;
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
				'post_title' => $this->title,
				'post_name' => $this->slug,
				'post_content' => $this->description,
			] );
		} else {
			$this->id = wp_insert_post( [
				'post_title' => $this->title,
				'post_name' => $this->slug,
				'post_content' => $this->description,
				'post_status' => 'publish',
				'post_type' => 'pearblog_offer',
			] );
		}

		update_post_meta( $this->id, 'pearblog_offer_location', wp_json_encode( $this->location ) );
		update_post_meta( $this->id, 'pearblog_offer_provider_id', $this->provider_id );
		update_post_meta( $this->id, 'pearblog_offer_price_range', wp_json_encode( $this->price_range ) );
		update_post_meta( $this->id, 'pearblog_offer_category', $this->category );
		update_post_meta( $this->id, 'pearblog_offer_images', wp_json_encode( $this->images ) );
		update_post_meta( $this->id, 'pearblog_offer_featured', $this->featured );

		// Set tags
		wp_set_post_terms( $this->id, $this->tags, 'pearblog_offer_tag' );

		return $this->id;
	}

	/**
	 * Get formatted price range
	 *
	 * @return string
	 */
	public function get_price_display(): string {
		if ( $this->price_range['min'] === $this->price_range['max'] ) {
			return number_format( $this->price_range['min'], 0, ',', ' ' ) . ' ' . $this->price_range['currency'];
		}

		return sprintf(
			'%s - %s %s',
			number_format( $this->price_range['min'], 0, ',', ' ' ),
			number_format( $this->price_range['max'], 0, ',', ' ' ),
			$this->price_range['currency']
		);
	}

	/**
	 * Render offer card
	 *
	 * @return string HTML
	 */
	public function render_card(): string {
		$featured_class = $this->featured ? ' featured' : '';

		$html = '<div class="offer-card' . $featured_class . '">';

		if ( ! empty( $this->images ) ) {
			$html .= '<div class="offer-image">';
			$html .= '<img src="' . esc_url( $this->images[0] ) . '" alt="' . esc_attr( $this->title ) . '" />';
			$html .= '</div>';
		}

		$html .= '<div class="offer-content">';
		$html .= '<h3>' . esc_html( $this->title ) . '</h3>';
		$html .= '<p class="offer-location">' . esc_html( $this->location['city'] ) . '</p>';
		$html .= '<p class="offer-description">' . esc_html( wp_trim_words( $this->description, 20 ) ) . '</p>';
		$html .= '<div class="offer-price">' . esc_html( $this->get_price_display() ) . '</div>';
		$html .= '<a href="' . esc_url( get_permalink( $this->id ) ) . '" class="btn btn-primary">Zobacz szczegóły</a>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}
}
