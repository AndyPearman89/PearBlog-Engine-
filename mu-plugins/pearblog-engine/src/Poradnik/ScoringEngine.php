<?php
/**
 * Scoring Engine V2
 *
 * Calculates article performance scores using weighted formula:
 * Score = (SEO × 0.2) + (ENG × 0.2) + (CTR × 0.2) + (REV × 0.4)
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class ScoringEngine
 *
 * Revenue-focused article scoring system.
 */
class ScoringEngine {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Event tracker instance.
	 *
	 * @var EventTracker
	 */
	private $event_tracker;

	/**
	 * Score weights.
	 *
	 * @var array
	 */
	private $weights = array(
		'seo'        => 0.2,
		'engagement' => 0.2,
		'ctr'        => 0.2,
		'revenue'    => 0.4,
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb          = $wpdb;
		$this->event_tracker = new EventTracker();
	}

	/**
	 * Calculate score for an article.
	 *
	 * @param int    $article_id Article ID.
	 * @param string $date Date to calculate for (Y-m-d). Default today.
	 * @return array Score data.
	 */
	public function calculate_score( int $article_id, string $date = '' ): array {
		if ( empty( $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		// Get statistics
		$stats = $this->event_tracker->get_article_stats( $article_id, $date, $date );

		// Get SEO data (would come from Search Console API in production)
		$seo_data = $this->get_seo_data( $article_id, $date );

		// Calculate individual scores
		$seo_score        = $this->calculate_seo_score( $seo_data );
		$engagement_score = $this->calculate_engagement_score( $stats );
		$ctr_score        = $this->calculate_ctr_score( $stats );
		$revenue_score    = $this->calculate_revenue_score( $stats );

		// Calculate total weighted score
		$total_score = (
			( $seo_score * $this->weights['seo'] ) +
			( $engagement_score * $this->weights['engagement'] ) +
			( $ctr_score * $this->weights['ctr'] ) +
			( $revenue_score * $this->weights['revenue'] )
		);

		// Determine category
		$category = $this->categorize_score( $total_score );

		return array(
			'article_id'       => $article_id,
			'date'             => $date,
			'total_score'      => round( $total_score, 2 ),
			'seo_score'        => round( $seo_score, 2 ),
			'engagement_score' => round( $engagement_score, 2 ),
			'ctr_score'        => round( $ctr_score, 2 ),
			'revenue_score'    => round( $revenue_score, 2 ),
			'category'         => $category,
			'stats'            => $stats,
			'seo_data'         => $seo_data,
		);
	}

	/**
	 * Calculate SEO score.
	 *
	 * SEO = (seo_clicks / seo_impressions) × 100
	 *
	 * @param array $seo_data SEO data.
	 * @return float Score (0-100).
	 */
	private function calculate_seo_score( array $seo_data ): float {
		if ( empty( $seo_data['impressions'] ) || $seo_data['impressions'] == 0 ) {
			return 0;
		}

		$ctr = ( $seo_data['clicks'] / $seo_data['impressions'] ) * 100;

		// Normalize to 0-100 scale (assuming 5% CTR = 100 points)
		return min( $ctr * 20, 100 );
	}

	/**
	 * Calculate engagement score.
	 *
	 * ENG = (avg_time_seconds / 60) + (scroll_depth_avg / 100)
	 *
	 * @param array $stats Article statistics.
	 * @return float Score (0-100).
	 */
	private function calculate_engagement_score( array $stats ): float {
		$time_score   = ( $stats['avg_time_seconds'] / 60 ) * 50; // 2 min = 100%
		$scroll_score = $stats['avg_scroll_depth'] * 0.5; // 100% scroll = 50 points

		$total = $time_score + $scroll_score;

		return min( $total, 100 );
	}

	/**
	 * Calculate CTR score.
	 *
	 * CTR = (cta_clicks / views) × 100
	 *
	 * @param array $stats Article statistics.
	 * @return float Score (0-100).
	 */
	private function calculate_ctr_score( array $stats ): float {
		if ( empty( $stats['views'] ) || $stats['views'] == 0 ) {
			return 0;
		}

		$ctr = ( $stats['cta_clicks'] / $stats['views'] ) * 100;

		// Normalize to 0-100 scale (assuming 10% CTR = 100 points)
		return min( $ctr * 10, 100 );
	}

	/**
	 * Calculate revenue score.
	 *
	 * REV = revenue (normalized 0-100)
	 *
	 * @param array $stats Article statistics.
	 * @return float Score (0-100).
	 */
	private function calculate_revenue_score( array $stats ): float {
		$revenue = $stats['revenue'];

		// Normalize revenue to 0-100 scale
		// Assuming 100 PLN per day = 100 points
		return min( $revenue, 100 );
	}

	/**
	 * Categorize score into action segments.
	 *
	 * @param float $score Total score.
	 * @return string Category (SCALE, BOOST, OPTIMIZE, DELETE).
	 */
	private function categorize_score( float $score ): string {
		if ( $score >= 90 ) {
			return 'SCALE';
		} elseif ( $score >= 70 ) {
			return 'BOOST';
		} elseif ( $score >= 50 ) {
			return 'OPTIMIZE';
		} else {
			return 'DELETE';
		}
	}

	/**
	 * Save score to database.
	 *
	 * @param array $score_data Score data from calculate_score().
	 * @return bool True on success.
	 */
	public function save_score( array $score_data ): bool {
		$table_name = $this->wpdb->prefix . 'pearblog_article_stats';

		$stats = $score_data['stats'];
		$seo   = $score_data['seo_data'];

		$insert_data = array(
			'article_id'             => $score_data['article_id'],
			'date'                   => $score_data['date'],
			'views'                  => $stats['views'],
			'unique_visitors'        => $stats['unique_visitors'],
			'avg_time_seconds'       => $stats['avg_time_seconds'],
			'scroll_depth_avg'       => $stats['avg_scroll_depth'],
			'bounce_rate'            => $stats['bounce_rate'],
			'cta_clicks'             => $stats['cta_clicks'],
			'cta_ctr'                => $stats['cta_ctr'],
			'leads'                  => $stats['leads'],
			'lead_conversion_rate'   => $stats['lead_conversion'],
			'revenue'                => $stats['revenue'],
			'seo_impressions'        => $seo['impressions'],
			'seo_clicks'             => $seo['clicks'],
			'seo_ctr'                => $seo['ctr'],
			'seo_position_avg'       => $seo['position'],
			'score'                  => $score_data['total_score'],
			'score_category'         => $score_data['category'],
			'updated_at'             => current_time( 'mysql' ),
		);

		// Check if record exists
		$existing = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE article_id = %d AND date = %s",
				$score_data['article_id'],
				$score_data['date']
			)
		);

		if ( $existing ) {
			// Update existing record
			return (bool) $this->wpdb->update(
				$table_name,
				$insert_data,
				array( 'id' => $existing->id ),
				array( '%d', '%s', '%d', '%d', '%d', '%f', '%f', '%d', '%f', '%d', '%f', '%f', '%d', '%d', '%f', '%f', '%f', '%s', '%s' ),
				array( '%d' )
			);
		}

		// Insert new record
		return (bool) $this->wpdb->insert(
			$table_name,
			$insert_data,
			array( '%d', '%s', '%d', '%d', '%d', '%f', '%f', '%d', '%f', '%d', '%f', '%f', '%d', '%d', '%f', '%f', '%f', '%s', '%s' )
		);
	}

	/**
	 * Get SEO data for an article.
	 *
	 * In production, this would fetch from Google Search Console API.
	 * For now, returns placeholder data.
	 *
	 * @param int    $article_id Article ID.
	 * @param string $date Date.
	 * @return array SEO data.
	 */
	private function get_seo_data( int $article_id, string $date ): array {
		// Placeholder: In production, integrate with Search Console API
		// For now, return sample data or fetch from a cache/custom table

		$table_name = $this->wpdb->prefix . 'pearblog_article_stats';

		$recent = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT seo_impressions, seo_clicks, seo_ctr, seo_position_avg
				FROM {$table_name}
				WHERE article_id = %d
				ORDER BY date DESC
				LIMIT 1",
				$article_id
			),
			ARRAY_A
		);

		if ( $recent ) {
			return array(
				'impressions' => (int) $recent['seo_impressions'],
				'clicks'      => (int) $recent['seo_clicks'],
				'ctr'         => (float) $recent['seo_ctr'],
				'position'    => (float) $recent['seo_position_avg'],
			);
		}

		return array(
			'impressions' => 0,
			'clicks'      => 0,
			'ctr'         => 0,
			'position'    => 0,
		);
	}

	/**
	 * Calculate scores for all articles.
	 *
	 * @param string $date Date to calculate for.
	 * @return array Results array with success/failure counts.
	 */
	public function calculate_all_scores( string $date = '' ): array {
		if ( empty( $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		$articles_table = $this->wpdb->prefix . 'pearblog_articles';

		// Get all published articles
		$articles = $this->wpdb->get_results(
			"SELECT id FROM {$articles_table} WHERE status = 'published'",
			ARRAY_A
		);

		$results = array(
			'total'     => count( $articles ),
			'success'   => 0,
			'failed'    => 0,
			'date'      => $date,
			'processed' => array(),
		);

		foreach ( $articles as $article ) {
			$score_data = $this->calculate_score( $article['id'], $date );
			$saved      = $this->save_score( $score_data );

			if ( $saved ) {
				$results['success']++;
				$results['processed'][] = array(
					'article_id' => $article['id'],
					'score'      => $score_data['total_score'],
					'category'   => $score_data['category'],
				);
			} else {
				$results['failed']++;
			}
		}

		return $results;
	}

	/**
	 * Get top performing articles by category.
	 *
	 * @param string $category Score category (SCALE, BOOST, OPTIMIZE, DELETE).
	 * @param int    $limit Number of results.
	 * @return array Articles.
	 */
	public function get_articles_by_category( string $category, int $limit = 10 ): array {
		$stats_table    = $this->wpdb->prefix . 'pearblog_article_stats';
		$articles_table = $this->wpdb->prefix . 'pearblog_articles';

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT a.id, a.topic, a.city, s.score, s.score_category, s.revenue, s.views
				FROM {$stats_table} s
				JOIN {$articles_table} a ON s.article_id = a.id
				WHERE s.score_category = %s
				ORDER BY s.score DESC
				LIMIT %d",
				$category,
				$limit
			),
			ARRAY_A
		);
	}
}
