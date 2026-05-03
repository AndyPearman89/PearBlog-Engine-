<?php
/**
 * A/B Testing Framework
 *
 * Manages content experiments for optimization.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class ABTester
 *
 * A/B testing system for content variants.
 */
class ABTester {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Create A/B test.
	 *
	 * @param int    $article_id Article ID.
	 * @param string $test_name Test name (e.g., "cta_test", "title_test").
	 * @param string $variant_a Variant A content.
	 * @param string $variant_b Variant B content.
	 * @return int|false Test ID or false on failure.
	 */
	public function create_test( int $article_id, string $test_name, string $variant_a, string $variant_b ) {
		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';

		$result = $this->wpdb->insert(
			$table_name,
			array(
				'article_id'  => $article_id,
				'test_name'   => $test_name,
				'variant_a'   => $variant_a,
				'variant_b'   => $variant_b,
				'status'      => 'running',
				'started_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Get variant for a user (50/50 split).
	 *
	 * @param int    $test_id Test ID.
	 * @param string $session_id Session identifier.
	 * @return string Variant ('a' or 'b').
	 */
	public function get_variant( int $test_id, string $session_id ): string {
		// Consistent hashing for same session
		$hash = crc32( $session_id . $test_id );
		return ( $hash % 2 === 0 ) ? 'a' : 'b';
	}

	/**
	 * Record test view.
	 *
	 * @param int    $test_id Test ID.
	 * @param string $variant Variant shown ('a' or 'b').
	 * @return bool True on success.
	 */
	public function record_view( int $test_id, string $variant ): bool {
		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';
		$field      = $variant === 'a' ? 'variant_a_views' : 'variant_b_views';

		return (bool) $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$table_name} SET {$field} = {$field} + 1 WHERE id = %d",
				$test_id
			)
		);
	}

	/**
	 * Record test conversion.
	 *
	 * @param int    $test_id Test ID.
	 * @param string $variant Variant that converted ('a' or 'b').
	 * @return bool True on success.
	 */
	public function record_conversion( int $test_id, string $variant ): bool {
		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';
		$field      = $variant === 'a' ? 'variant_a_conversions' : 'variant_b_conversions';

		return (bool) $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$table_name} SET {$field} = {$field} + 1 WHERE id = %d",
				$test_id
			)
		);
	}

	/**
	 * Get test results.
	 *
	 * @param int $test_id Test ID.
	 * @return array|null Test results or null.
	 */
	public function get_results( int $test_id ): ?array {
		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';

		$test = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$test_id
			),
			ARRAY_A
		);

		if ( ! $test ) {
			return null;
		}

		$a_views       = (int) $test['variant_a_views'];
		$a_conversions = (int) $test['variant_a_conversions'];
		$b_views       = (int) $test['variant_b_views'];
		$b_conversions = (int) $test['variant_b_conversions'];

		$a_rate = $a_views > 0 ? ( $a_conversions / $a_views ) * 100 : 0;
		$b_rate = $b_views > 0 ? ( $b_conversions / $b_views ) * 100 : 0;

		return array(
			'test_id'       => $test_id,
			'test_name'     => $test['test_name'],
			'article_id'    => $test['article_id'],
			'status'        => $test['status'],
			'variant_a'     => array(
				'views'       => $a_views,
				'conversions' => $a_conversions,
				'rate'        => round( $a_rate, 2 ),
			),
			'variant_b'     => array(
				'views'       => $b_views,
				'conversions' => $b_conversions,
				'rate'        => round( $b_rate, 2 ),
			),
			'winner'        => $test['winner'],
			'started_at'    => $test['started_at'],
			'completed_at'  => $test['completed_at'],
		);
	}

	/**
	 * Determine test winner with statistical significance.
	 *
	 * @param int $test_id Test ID.
	 * @return string|null Winner ('a', 'b', 'inconclusive') or null.
	 */
	public function determine_winner( int $test_id ): ?string {
		$results = $this->get_results( $test_id );
		if ( ! $results ) {
			return null;
		}

		$a = $results['variant_a'];
		$b = $results['variant_b'];

		// Minimum sample size requirement
		if ( $a['views'] < 100 || $b['views'] < 100 ) {
			return 'inconclusive';
		}

		// Calculate z-score for proportion test
		$p1 = $a['conversions'] / $a['views'];
		$p2 = $b['conversions'] / $b['views'];
		$p  = ( $a['conversions'] + $b['conversions'] ) / ( $a['views'] + $b['views'] );

		$se = sqrt( $p * ( 1 - $p ) * ( ( 1 / $a['views'] ) + ( 1 / $b['views'] ) ) );

		if ( $se == 0 ) {
			return 'inconclusive';
		}

		$z_score = ( $p1 - $p2 ) / $se;

		// 95% confidence (z-score > 1.96)
		if ( abs( $z_score ) > 1.96 ) {
			return $z_score > 0 ? 'a' : 'b';
		}

		return 'inconclusive';
	}

	/**
	 * Complete test and mark winner.
	 *
	 * @param int $test_id Test ID.
	 * @return bool True on success.
	 */
	public function complete_test( int $test_id ): bool {
		$winner = $this->determine_winner( $test_id );

		if ( ! $winner ) {
			return false;
		}

		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';

		return (bool) $this->wpdb->update(
			$table_name,
			array(
				'status'       => 'completed',
				'winner'       => $winner,
				'completed_at' => current_time( 'mysql' ),
			),
			array( 'id' => $test_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Apply winning variant to article.
	 *
	 * @param int $test_id Test ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function apply_winner( int $test_id ) {
		$results = $this->get_results( $test_id );
		if ( ! $results || $results['status'] !== 'completed' ) {
			return new \WP_Error( 'test_not_completed', 'Test is not completed' );
		}

		if ( $results['winner'] === 'inconclusive' ) {
			return new \WP_Error( 'inconclusive', 'Test results inconclusive' );
		}

		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';

		$test = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$test_id
			),
			ARRAY_A
		);

		$winning_content = $results['winner'] === 'a' ? $test['variant_a'] : $test['variant_b'];

		// Get article
		$articles_table = $this->wpdb->prefix . 'pearblog_articles';
		$article        = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$articles_table} WHERE id = %d",
				$test['article_id']
			),
			ARRAY_A
		);

		if ( ! $article ) {
			return new \WP_Error( 'article_not_found', 'Article not found' );
		}

		// Update post with winning variant
		wp_update_post(
			array(
				'ID'           => $article['post_id'],
				'post_content' => $winning_content,
			)
		);

		return true;
	}

	/**
	 * Get active tests for an article.
	 *
	 * @param int $article_id Article ID.
	 * @return array Active tests.
	 */
	public function get_active_tests( int $article_id ): array {
		$table_name = $this->wpdb->prefix . 'pearblog_ab_tests';

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE article_id = %d AND status = 'running'",
				$article_id
			),
			ARRAY_A
		);
	}
}
