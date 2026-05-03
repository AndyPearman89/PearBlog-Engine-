<?php
/**
 * Article Model - Enhanced content model for Poradnik.pro Decision Platform
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Article model with blocks, intent, geo, and monetization
 */
class Article {

	/** @var int */
	public int $id;

	/** @var string */
	public string $title;

	/** @var string */
	public string $slug;

	/** @var array<string, mixed> Block structure */
	public array $blocks;

	/** @var string Intent type: informational|transactional|navigational|local */
	public string $intent;

	/** @var array{city: string, region: string, country: string}|null */
	public ?array $geo;

	/** @var array{title: string, description: string, keywords: array<string>} */
	public array $seo;

	/** @var int|null */
	public ?int $cluster_id;

	/** @var float Quality score 0-100 */
	public float $score;

	/** @var array<array{question: string, answer: string}> */
	public array $faq;

	/** @var array<int> Expert IDs */
	public array $experts;

	/** @var array<array{type: string, data: mixed}> */
	public array $monetization;

	/** @var bool */
	public bool $lead_enabled;

	/** @var string ISO 8601 timestamp */
	public string $created_at;

	/** @var string ISO 8601 timestamp */
	public string $updated_at;

	/**
	 * Create Article from WordPress post
	 *
	 * @param \WP_Post $post WordPress post object
	 * @return self
	 */
	public static function from_post( \WP_Post $post ): self {
		$article = new self();
		$article->id = $post->ID;
		$article->title = $post->post_title;
		$article->slug = $post->post_name;

		// Load blocks from post meta
		$article->blocks = get_post_meta( $post->ID, 'pearblog_content_blocks', true ) ?: [];

		// Load intent
		$article->intent = get_post_meta( $post->ID, 'pearblog_content_intent', true ) ?: 'informational';

		// Load geo data
		$geo_data = get_post_meta( $post->ID, 'pearblog_geo_data', true );
		$article->geo = $geo_data ? json_decode( $geo_data, true ) : null;

		// Load SEO data
		$article->seo = [
			'title' => get_post_meta( $post->ID, '_yoast_wpseo_title', true ) ?: $post->post_title,
			'description' => get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ?: '',
			'keywords' => explode( ',', get_post_meta( $post->ID, 'pearblog_keywords', true ) ?: '' ),
		];

		// Load cluster
		$article->cluster_id = (int) get_post_meta( $post->ID, 'pearblog_cluster_id', true ) ?: null;

		// Load quality score
		$article->score = (float) get_post_meta( $post->ID, 'pearblog_quality_score', true ) ?: 0.0;

		// Load FAQ
		$faq_data = get_post_meta( $post->ID, 'pearblog_faq', true );
		$article->faq = $faq_data ? json_decode( $faq_data, true ) : [];

		// Load experts
		$experts_data = get_post_meta( $post->ID, 'pearblog_experts', true );
		$article->experts = $experts_data ? json_decode( $experts_data, true ) : [];

		// Load monetization
		$monetization_data = get_post_meta( $post->ID, 'pearblog_monetization', true );
		$article->monetization = $monetization_data ? json_decode( $monetization_data, true ) : [];

		// Load lead settings
		$article->lead_enabled = (bool) get_post_meta( $post->ID, 'pearblog_lead_enabled', true );

		// Timestamps
		$article->created_at = $post->post_date;
		$article->updated_at = $post->post_modified;

		return $article;
	}

	/**
	 * Save Article to WordPress post
	 *
	 * @return int Post ID
	 */
	public function save(): int {
		if ( $this->id > 0 ) {
			// Update existing post
			wp_update_post( [
				'ID' => $this->id,
				'post_title' => $this->title,
				'post_name' => $this->slug,
			] );
		} else {
			// Create new post
			$this->id = wp_insert_post( [
				'post_title' => $this->title,
				'post_name' => $this->slug,
				'post_status' => 'draft',
				'post_type' => 'post',
			] );
		}

		// Save all meta data
		update_post_meta( $this->id, 'pearblog_content_blocks', $this->blocks );
		update_post_meta( $this->id, 'pearblog_content_intent', $this->intent );
		update_post_meta( $this->id, 'pearblog_geo_data', wp_json_encode( $this->geo ) );
		update_post_meta( $this->id, 'pearblog_cluster_id', $this->cluster_id );
		update_post_meta( $this->id, 'pearblog_quality_score', $this->score );
		update_post_meta( $this->id, 'pearblog_faq', wp_json_encode( $this->faq ) );
		update_post_meta( $this->id, 'pearblog_experts', wp_json_encode( $this->experts ) );
		update_post_meta( $this->id, 'pearblog_monetization', wp_json_encode( $this->monetization ) );
		update_post_meta( $this->id, 'pearblog_lead_enabled', $this->lead_enabled );

		return $this->id;
	}

	/**
	 * Convert blocks to HTML
	 *
	 * @return string
	 */
	public function render_blocks(): string {
		$html = '';

		foreach ( $this->blocks as $block ) {
			$html .= BlockRenderer::render( $block );
		}

		return $html;
	}

	/**
	 * Add FAQ item
	 *
	 * @param string $question
	 * @param string $answer
	 */
	public function add_faq( string $question, string $answer ): void {
		$this->faq[] = [
			'question' => $question,
			'answer' => $answer,
		];
	}

	/**
	 * Add expert to article
	 *
	 * @param int $expert_id
	 */
	public function add_expert( int $expert_id ): void {
		if ( ! in_array( $expert_id, $this->experts, true ) ) {
			$this->experts[] = $expert_id;
		}
	}

	/**
	 * Add monetization block
	 *
	 * @param string $type
	 * @param mixed $data
	 */
	public function add_monetization( string $type, $data ): void {
		$this->monetization[] = [
			'type' => $type,
			'data' => $data,
		];
	}
}
