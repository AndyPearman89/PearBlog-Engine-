<?php
/**
 * Smart Calculator Engine - Interactive cost calculator
 *
 * Multi-step calculator with:
 * - Dynamic input fields
 * - Real-time calculation
 * - Lead capture integration
 * - Data collection for pricing optimization
 *
 * @package PearBlogEngine\Content
 * @version 3.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Smart Calculator Engine
 *
 * Handles calculator configuration, rendering, and calculations
 * for V3 landing pages.
 */
class SmartCalculatorEngine {

	/**
	 * Calculate cost based on service and inputs.
	 *
	 * @param string $service Service slug (e.g., "budowa-domu").
	 * @param array  $inputs  User inputs:
	 *                        - metraz: int (m²)
	 *                        - standard: string (podstawowy/sredni/premium)
	 *                        - lokalizacja: string (miasto/przedmiescia/wies)
	 *                        - typ: string (service-specific type)
	 * @return array|null Calculation result or null on error:
	 *                    - min_cost: float
	 *                    - max_cost: float
	 *                    - avg_cost: float
	 *                    - cost_per_unit: float
	 *                    - breakdown: array
	 */
	public static function calculate( string $service, array $inputs ): ?array {
		// Validate inputs
		if ( ! self::validate_inputs( $inputs ) ) {
			return null;
		}

		// Get base prices for service
		$base_prices = self::get_base_prices( $service );
		if ( ! $base_prices ) {
			return null;
		}

		// Extract inputs
		$metraz      = (float) ( $inputs['metraz'] ?? 100 );
		$standard    = $inputs['standard'] ?? 'sredni';
		$lokalizacja = $inputs['lokalizacja'] ?? 'miasto';
		$typ         = $inputs['typ'] ?? 'standardowy';

		// Calculate multipliers
		$standard_multiplier    = self::get_standard_multiplier( $standard );
		$lokalizacja_multiplier = self::get_location_multiplier( $lokalizacja );
		$typ_multiplier         = self::get_type_multiplier( $service, $typ );

		// Calculate base costs
		$base_min = $base_prices['min'] * $metraz;
		$base_max = $base_prices['max'] * $metraz;

		// Apply multipliers
		$total_multiplier = $standard_multiplier * $lokalizacja_multiplier * $typ_multiplier;

		$min_cost = round( $base_min * $total_multiplier, 2 );
		$max_cost = round( $base_max * $total_multiplier, 2 );
		$avg_cost = round( ( $min_cost + $max_cost ) / 2, 2 );
		$cost_per_unit = round( $avg_cost / $metraz, 2 );

		// Build breakdown
		$breakdown = self::build_cost_breakdown( $service, $metraz, $standard, $lokalizacja );

		$result = [
			'min_cost'      => $min_cost,
			'max_cost'      => $max_cost,
			'avg_cost'      => $avg_cost,
			'cost_per_unit' => $cost_per_unit,
			'breakdown'     => $breakdown,
			'inputs'        => $inputs,
			'service'       => $service,
		];

		// Store calculation for data layer
		self::store_calculation( $service, $inputs, $result );

		return $result;
	}

	/**
	 * Validate calculator inputs.
	 *
	 * @param array $inputs Input array.
	 * @return bool True if valid.
	 */
	private static function validate_inputs( array $inputs ): bool {
		// Metraz validation
		if ( ! isset( $inputs['metraz'] ) ) {
			return false;
		}

		$metraz = (float) $inputs['metraz'];
		if ( $metraz < 10 || $metraz > 1000 ) {
			return false;
		}

		// Standard validation
		$valid_standards = [ 'podstawowy', 'sredni', 'premium' ];
		if ( isset( $inputs['standard'] ) && ! in_array( $inputs['standard'], $valid_standards, true ) ) {
			return false;
		}

		// Lokalizacja validation
		$valid_locations = [ 'miasto', 'przedmiescia', 'wies' ];
		if ( isset( $inputs['lokalizacja'] ) && ! in_array( $inputs['lokalizacja'], $valid_locations, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get base prices for service.
	 *
	 * @param string $service Service slug.
	 * @return array|null Array with 'min' and 'max' keys, or null.
	 */
	private static function get_base_prices( string $service ): ?array {
		// Default price database (per m²)
		$prices = [
			'budowa-domu'      => [ 'min' => 5000, 'max' => 7500 ],
			'remont-domu'      => [ 'min' => 1500, 'max' => 3500 ],
			'remont-mieszkania' => [ 'min' => 1200, 'max' => 2800 ],
			'dach'             => [ 'min' => 150, 'max' => 350 ],
			'okna'             => [ 'min' => 800, 'max' => 1500 ],
			'instalacja-elektryczna' => [ 'min' => 60, 'max' => 120 ],
			'instalacja-co'    => [ 'min' => 80, 'max' => 150 ],
			'pompa-ciepla'     => [ 'min' => 25000, 'max' => 45000 ], // fixed price, not per m²
		];

		/**
		 * Filter: pearblog_calculator_base_prices
		 *
		 * Allow customization of base prices.
		 *
		 * @param array $prices Service => [min, max] mapping.
		 */
		$prices = apply_filters( 'pearblog_calculator_base_prices', $prices );

		return $prices[ $service ] ?? [ 'min' => 1000, 'max' => 5000 ]; // fallback
	}

	/**
	 * Get standard multiplier.
	 *
	 * @param string $standard Standard level.
	 * @return float Multiplier.
	 */
	private static function get_standard_multiplier( string $standard ): float {
		$multipliers = [
			'podstawowy' => 0.85,
			'sredni'     => 1.0,
			'premium'    => 1.3,
		];

		return $multipliers[ $standard ] ?? 1.0;
	}

	/**
	 * Get location multiplier.
	 *
	 * @param string $lokalizacja Location type.
	 * @return float Multiplier.
	 */
	private static function get_location_multiplier( string $lokalizacja ): float {
		$multipliers = [
			'miasto'       => 1.15, // Urban areas more expensive
			'przedmiescia' => 1.0,  // Suburban baseline
			'wies'         => 0.85, // Rural areas cheaper
		];

		return $multipliers[ $lokalizacja ] ?? 1.0;
	}

	/**
	 * Get type-specific multiplier.
	 *
	 * @param string $service Service slug.
	 * @param string $typ     Type selection.
	 * @return float Multiplier.
	 */
	private static function get_type_multiplier( string $service, string $typ ): float {
		// Service-specific type multipliers
		$type_multipliers = [
			'budowa-domu' => [
				'parterowy'     => 0.9,
				'pietrowy'      => 1.0,
				'blizniak'      => 0.95,
				'szeregowy'     => 0.85,
			],
			'dach' => [
				'dwuspadowy'    => 0.9,
				'wielospadowy'  => 1.1,
				'plaski'        => 0.85,
			],
		];

		if ( ! isset( $type_multipliers[ $service ] ) ) {
			return 1.0;
		}

		return $type_multipliers[ $service ][ $typ ] ?? 1.0;
	}

	/**
	 * Build cost breakdown by category.
	 *
	 * @param string $service     Service slug.
	 * @param float  $metraz      Square meters.
	 * @param string $standard    Standard level.
	 * @param string $lokalizacja Location type.
	 * @return array Breakdown array.
	 */
	private static function build_cost_breakdown( string $service, float $metraz, string $standard, string $lokalizacja ): array {
		// Simplified breakdown percentages
		$breakdown_templates = [
			'budowa-domu' => [
				'Fundamenty'       => 0.15,
				'Ściany i strop'   => 0.25,
				'Dach'             => 0.15,
				'Instalacje'       => 0.20,
				'Wykończenie'      => 0.20,
				'Pozostałe'        => 0.05,
			],
			'remont-mieszkania' => [
				'Roboty rozbiórkowe' => 0.10,
				'Elektryka'        => 0.15,
				'Hydraulika'       => 0.15,
				'Posadzki'         => 0.20,
				'Ściany i sufit'   => 0.25,
				'Pozostałe'        => 0.15,
			],
		];

		$template = $breakdown_templates[ $service ] ?? [
			'Materiały'  => 0.50,
			'Robocizna'  => 0.40,
			'Pozostałe'  => 0.10,
		];

		// Get base prices
		$base_prices = self::get_base_prices( $service );
		$avg_base = ( $base_prices['min'] + $base_prices['max'] ) / 2;
		$total_base = $avg_base * $metraz;

		// Apply multipliers
		$standard_mult = self::get_standard_multiplier( $standard );
		$location_mult = self::get_location_multiplier( $lokalizacja );
		$total_cost = $total_base * $standard_mult * $location_mult;

		$breakdown = [];
		foreach ( $template as $category => $percentage ) {
			$breakdown[ $category ] = round( $total_cost * $percentage, 2 );
		}

		return $breakdown;
	}

	/**
	 * Store calculation for data layer and optimization.
	 *
	 * @param string $service Service slug.
	 * @param array  $inputs  Calculator inputs.
	 * @param array  $result  Calculation result.
	 * @return void
	 */
	private static function store_calculation( string $service, array $inputs, array $result ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_calculator_submissions';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return; // Table not created yet
		}

		$wpdb->insert(
			$table,
			[
				'service'         => $service,
				'metraz'          => $inputs['metraz'] ?? null,
				'standard'        => $inputs['standard'] ?? null,
				'lokalizacja'     => $inputs['lokalizacja'] ?? null,
				'typ'             => $inputs['typ'] ?? null,
				'min_cost'        => $result['min_cost'],
				'max_cost'        => $result['max_cost'],
				'avg_cost'        => $result['avg_cost'],
				'cost_per_unit'   => $result['cost_per_unit'],
				'ip_address'      => self::get_client_ip(),
				'user_agent'      => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
				'submitted_at'    => current_time( 'mysql' ),
			],
			[
				'%s', // service
				'%f', // metraz
				'%s', // standard
				'%s', // lokalizacja
				'%s', // typ
				'%f', // min_cost
				'%f', // max_cost
				'%f', // avg_cost
				'%f', // cost_per_unit
				'%s', // ip_address
				'%s', // user_agent
				'%s', // submitted_at
			]
		);
	}

	/**
	 * Render calculator HTML form.
	 *
	 * @param string $service Service slug.
	 * @param array  $config  Optional configuration.
	 * @return string HTML markup.
	 */
	public static function render( string $service, array $config = [] ): string {
		$calculator_id = 'calc-' . sanitize_title( $service );

		ob_start();
		?>
		<div class="smart-calculator" id="<?php echo esc_attr( $calculator_id ); ?>" data-service="<?php echo esc_attr( $service ); ?>">
			<form class="calculator-form">

				<div class="form-group">
					<label for="<?php echo esc_attr( $calculator_id ); ?>-metraz">
						Metraż (m²)
					</label>
					<input
						type="number"
						id="<?php echo esc_attr( $calculator_id ); ?>-metraz"
						name="metraz"
						min="10"
						max="1000"
						step="1"
						value="100"
						required
						class="form-control"
					/>
				</div>

				<div class="form-group">
					<label for="<?php echo esc_attr( $calculator_id ); ?>-standard">
						Standard wykończenia
					</label>
					<select
						id="<?php echo esc_attr( $calculator_id ); ?>-standard"
						name="standard"
						required
						class="form-control"
					>
						<option value="podstawowy">Podstawowy</option>
						<option value="sredni" selected>Średni</option>
						<option value="premium">Premium</option>
					</select>
				</div>

				<div class="form-group">
					<label for="<?php echo esc_attr( $calculator_id ); ?>-lokalizacja">
						Lokalizacja
					</label>
					<select
						id="<?php echo esc_attr( $calculator_id ); ?>-lokalizacja"
						name="lokalizacja"
						required
						class="form-control"
					>
						<option value="miasto">Miasto</option>
						<option value="przedmiescia" selected>Przedmieścia</option>
						<option value="wies">Wieś</option>
					</select>
				</div>

				<?php
				// Service-specific type field
				$type_options = self::get_type_options( $service );
				if ( ! empty( $type_options ) ) :
				?>
				<div class="form-group">
					<label for="<?php echo esc_attr( $calculator_id ); ?>-typ">
						Typ
					</label>
					<select
						id="<?php echo esc_attr( $calculator_id ); ?>-typ"
						name="typ"
						class="form-control"
					>
						<?php foreach ( $type_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<button type="submit" class="btn btn-primary btn-lg btn-block calculator-submit">
					🧮 Oblicz koszt
				</button>
			</form>

			<div class="calculator-result" style="display: none;">
				<h3>Szacunkowy koszt:</h3>

				<div class="result-summary">
					<div class="cost-range">
						<span class="cost-min"></span> - <span class="cost-max"></span> zł
					</div>
					<div class="cost-avg">
						Średnia: <strong><span class="cost-avg-value"></span> zł</strong>
					</div>
					<div class="cost-per-unit">
						Cena za m²: <span class="cost-per-unit-value"></span> zł/m²
					</div>
				</div>

				<div class="cost-breakdown">
					<h4>Rozbicie kosztów:</h4>
					<table class="breakdown-table">
						<tbody class="breakdown-items">
							<!-- Populated by JS -->
						</tbody>
					</table>
				</div>

				<div class="calculator-cta">
					<p>Chcesz otrzymać szczegółową wycenę od sprawdzonych firm?</p>
					<button type="button" class="btn btn-success btn-lg open-lead-form">
						📩 Wyślij zapytanie → otrzymaj oferty
					</button>
				</div>
			</div>

			<div class="calculator-error" style="display: none;">
				<p class="error-message"></p>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get type options for specific service.
	 *
	 * @param string $service Service slug.
	 * @return array Type options (value => label).
	 */
	private static function get_type_options( string $service ): array {
		$options = [
			'budowa-domu' => [
				'parterowy'  => 'Dom parterowy',
				'pietrowy'   => 'Dom piętrowy',
				'blizniak'   => 'Bliźniak',
				'szeregowy'  => 'Szeregowy',
			],
			'dach' => [
				'dwuspadowy'   => 'Dwuspadowy',
				'wielospadowy' => 'Wielospadowy',
				'plaski'       => 'Płaski',
			],
			'remont-mieszkania' => [
				'czysty'       => 'Remont od dewelopera',
				'czesciowy'    => 'Częściowy remont',
				'kapitalny'    => 'Remont kapitalny',
			],
		];

		return $options[ $service ] ?? [];
	}

	/**
	 * Get client IP address (safely).
	 *
	 * @return string IP address or 'unknown'.
	 */
	private static function get_client_ip(): string {
		$ip_keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Take first IP if comma-separated
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return 'unknown';
	}
}
