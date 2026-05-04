<?php
/**
 * Live Pricing Data Layer - Dynamic pricing aggregation
 *
 * Aggregates real pricing data from:
 * - Calculator submissions
 * - Lead form submissions
 * - User-reported prices
 *
 * Displays live, updated pricing information on landing pages.
 *
 * @package PearBlogEngine\Content
 * @version 3.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Live Pricing Data Layer
 *
 * Collects and aggregates real pricing data to display on V3 landing pages.
 */
class LivePricingDataLayer {

	/**
	 * Cache duration for pricing queries (1 hour).
	 */
	private const CACHE_DURATION = 3600;

	/**
	 * Get live pricing data for a service.
	 *
	 * @param string $service Service slug.
	 * @param array  $filters Optional filters:
	 *                        - city: string
	 *                        - standard: string
	 *                        - lokalizacja: string
	 *                        - days: int (default: 90)
	 * @return array|null Pricing data or null if insufficient data:
	 *                    - avg_price_per_unit: float
	 *                    - min_price: float
	 *                    - max_price: float
	 *                    - sample_count: int
	 *                    - last_updated: string
	 *                    - confidence: string (low/medium/high)
	 */
	public static function get_live_pricing( string $service, array $filters = [] ): ?array {
		// Check cache first
		$cache_key = self::get_cache_key( $service, $filters );
		$cached = wp_cache_get( $cache_key, 'pearblog_pricing' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database
		$data = self::query_pricing_data( $service, $filters );

		if ( ! $data || $data['sample_count'] < 3 ) {
			// Insufficient data - return null or fallback
			$data = null;
		}

		// Cache result
		wp_cache_set( $cache_key, $data, 'pearblog_pricing', self::CACHE_DURATION );

		return $data;
	}

	/**
	 * Query pricing data from database.
	 *
	 * @param string $service Service slug.
	 * @param array  $filters Filters array.
	 * @return array|null Aggregated data.
	 */
	private static function query_pricing_data( string $service, array $filters ): ?array {
		global $wpdb;

		$calc_table = $wpdb->prefix . 'pearblog_calculator_submissions';
		$lead_table = $wpdb->prefix . 'poradnik_leads';

		// Check if tables exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$calc_table}'" ) !== $calc_table ) {
			return null;
		}

		$days = $filters['days'] ?? 90;
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Build WHERE clause
		$where = [ "service = %s", "submitted_at >= %s" ];
		$params = [ $service, $cutoff_date ];

		if ( ! empty( $filters['standard'] ) ) {
			$where[] = "standard = %s";
			$params[] = $filters['standard'];
		}

		if ( ! empty( $filters['lokalizacja'] ) ) {
			$where[] = "lokalizacja = %s";
			$params[] = $filters['lokalizacja'];
		}

		$where_clause = implode( ' AND ', $where );

		// Query calculator submissions
		$query = $wpdb->prepare(
			"SELECT
				AVG(cost_per_unit) as avg_price_per_unit,
				MIN(min_cost) as min_price,
				MAX(max_cost) as max_price,
				COUNT(*) as sample_count,
				MAX(submitted_at) as last_updated
			FROM {$calc_table}
			WHERE {$where_clause}",
			...$params
		);

		$result = $wpdb->get_row( $query, ARRAY_A );

		if ( ! $result || $result['sample_count'] == 0 ) {
			return null;
		}

		// Determine confidence level
		$confidence = self::calculate_confidence( (int) $result['sample_count'], $days );

		return [
			'avg_price_per_unit' => round( (float) $result['avg_price_per_unit'], 2 ),
			'min_price'          => round( (float) $result['min_price'], 2 ),
			'max_price'          => round( (float) $result['max_price'], 2 ),
			'sample_count'       => (int) $result['sample_count'],
			'last_updated'       => $result['last_updated'],
			'confidence'         => $confidence,
		];
	}

	/**
	 * Calculate confidence level based on sample size and recency.
	 *
	 * @param int $sample_count Number of samples.
	 * @param int $days         Days range.
	 * @return string Confidence level: low/medium/high.
	 */
	private static function calculate_confidence( int $sample_count, int $days ): string {
		// Adjust thresholds based on time range
		$threshold_multiplier = $days / 90;

		if ( $sample_count >= ( 50 * $threshold_multiplier ) ) {
			return 'high';
		} elseif ( $sample_count >= ( 15 * $threshold_multiplier ) ) {
			return 'medium';
		} else {
			return 'low';
		}
	}

	/**
	 * Render live pricing widget HTML.
	 *
	 * @param string $service Service slug.
	 * @param array  $filters Optional filters.
	 * @return string HTML markup.
	 */
	public static function render( string $service, array $filters = [] ): string {
		$data = self::get_live_pricing( $service, $filters );

		if ( ! $data ) {
			// No data available - return empty or fallback message
			return '<div class="live-pricing no-data"><p>Dane cenowe będą dostępne wkrótce.</p></div>';
		}

		ob_start();
		?>
		<div class="live-pricing-widget" data-service="<?php echo esc_attr( $service ); ?>">
			<div class="live-pricing-header">
				<h3>📊 Średnie ceny – aktualizacja live</h3>
				<span class="update-badge">
					Ostatnia aktualizacja: <?php echo esc_html( self::format_date( $data['last_updated'] ) ); ?>
				</span>
			</div>

			<div class="pricing-stats">
				<div class="stat-item stat-avg">
					<div class="stat-label">Średnia cena za m²</div>
					<div class="stat-value"><?php echo esc_html( number_format( $data['avg_price_per_unit'], 0, ',', ' ' ) ); ?> zł</div>
				</div>

				<div class="stat-item stat-range">
					<div class="stat-label">Zakres cen</div>
					<div class="stat-value">
						<?php echo esc_html( number_format( $data['min_price'], 0, ',', ' ' ) ); ?> -
						<?php echo esc_html( number_format( $data['max_price'], 0, ',', ' ' ) ); ?> zł
					</div>
				</div>

				<div class="stat-item stat-samples">
					<div class="stat-label">Analizowane wyceny</div>
					<div class="stat-value"><?php echo esc_html( $data['sample_count'] ); ?></div>
				</div>

				<div class="stat-item stat-confidence">
					<div class="stat-label">Wiarygodność danych</div>
					<div class="stat-value confidence-<?php echo esc_attr( $data['confidence'] ); ?>">
						<?php echo esc_html( self::get_confidence_label( $data['confidence'] ) ); ?>
					</div>
				</div>
			</div>

			<div class="pricing-source">
				<p>
					<small>
						📍 Dane aktualizowane na podstawie realnych wycen złożonych przez użytkowników platformy.
						<?php if ( 'high' === $data['confidence'] ) : ?>
						Wysoka jakość danych pozwala na precyzyjne szacowanie kosztów.
						<?php endif; ?>
					</small>
				</p>
			</div>

			<div class="pricing-cta">
				<button type="button" class="btn btn-outline open-lead-form">
					Porównaj z Twoją wycenę →
				</button>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get cache key for pricing data.
	 *
	 * @param string $service Service slug.
	 * @param array  $filters Filters array.
	 * @return string Cache key.
	 */
	private static function get_cache_key( string $service, array $filters ): string {
		$filter_string = serialize( $filters );
		return 'live_pricing_' . $service . '_' . md5( $filter_string );
	}

	/**
	 * Format date for display.
	 *
	 * @param string $date MySQL datetime.
	 * @return string Formatted date.
	 */
	private static function format_date( string $date ): string {
		$timestamp = strtotime( $date );
		$diff = time() - $timestamp;

		if ( $diff < 3600 ) {
			$minutes = floor( $diff / 60 );
			return $minutes . ' min temu';
		} elseif ( $diff < 86400 ) {
			$hours = floor( $diff / 3600 );
			return $hours . ' godz. temu';
		} elseif ( $diff < 604800 ) {
			$days = floor( $diff / 86400 );
			return $days . ' dni temu';
		} else {
			return gmdate( 'd.m.Y', $timestamp );
		}
	}

	/**
	 * Get confidence label in Polish.
	 *
	 * @param string $confidence Confidence level.
	 * @return string Translated label.
	 */
	private static function get_confidence_label( string $confidence ): string {
		$labels = [
			'high'   => 'Wysoka',
			'medium' => 'Średnia',
			'low'    => 'Niska',
		];

		return $labels[ $confidence ] ?? 'Nieznana';
	}

	/**
	 * Get pricing trend for service.
	 *
	 * Compares current period vs previous period.
	 *
	 * @param string $service Service slug.
	 * @param int    $days    Period in days (default: 30).
	 * @return array|null Trend data or null:
	 *                    - current_avg: float
	 *                    - previous_avg: float
	 *                    - change_percent: float
	 *                    - trend: string (up/down/stable)
	 */
	public static function get_pricing_trend( string $service, int $days = 30 ): ?array {
		global $wpdb;

		$calc_table = $wpdb->prefix . 'pearblog_calculator_submissions';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$calc_table}'" ) !== $calc_table ) {
			return null;
		}

		// Current period
		$current_start = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		$current_avg = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(cost_per_unit)
			FROM {$calc_table}
			WHERE service = %s AND submitted_at >= %s",
			$service,
			$current_start
		) );

		// Previous period
		$previous_start = gmdate( 'Y-m-d H:i:s', strtotime( "-" . ( $days * 2 ) . " days" ) );
		$previous_end = $current_start;
		$previous_avg = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(cost_per_unit)
			FROM {$calc_table}
			WHERE service = %s AND submitted_at >= %s AND submitted_at < %s",
			$service,
			$previous_start,
			$previous_end
		) );

		if ( ! $current_avg || ! $previous_avg ) {
			return null;
		}

		$change_percent = ( ( $current_avg - $previous_avg ) / $previous_avg ) * 100;

		$trend = 'stable';
		if ( abs( $change_percent ) > 5 ) {
			$trend = $change_percent > 0 ? 'up' : 'down';
		}

		return [
			'current_avg'    => round( (float) $current_avg, 2 ),
			'previous_avg'   => round( (float) $previous_avg, 2 ),
			'change_percent' => round( $change_percent, 1 ),
			'trend'          => $trend,
		];
	}

	/**
	 * Render pricing trend badge.
	 *
	 * @param string $service Service slug.
	 * @param int    $days    Period in days.
	 * @return string HTML markup.
	 */
	public static function render_trend( string $service, int $days = 30 ): string {
		$trend = self::get_pricing_trend( $service, $days );

		if ( ! $trend ) {
			return '';
		}

		$trend_icon = [
			'up'     => '📈',
			'down'   => '📉',
			'stable' => '➡️',
		];

		$trend_class = [
			'up'     => 'trend-up',
			'down'   => 'trend-down',
			'stable' => 'trend-stable',
		];

		$trend_label = [
			'up'     => 'wzrost',
			'down'   => 'spadek',
			'stable' => 'stabilne',
		];

		ob_start();
		?>
		<div class="pricing-trend <?php echo esc_attr( $trend_class[ $trend['trend'] ] ); ?>">
			<span class="trend-icon"><?php echo $trend_icon[ $trend['trend'] ]; ?></span>
			<span class="trend-text">
				<?php echo esc_html( $trend_label[ $trend['trend'] ] ); ?>
				<?php if ( 'stable' !== $trend['trend'] ) : ?>
					<?php echo esc_html( abs( $trend['change_percent'] ) ); ?>%
				<?php endif; ?>
				(ostatnie <?php echo esc_html( $days ); ?> dni)
			</span>
		</div>
		<?php

		return ob_get_clean();
	}
}
