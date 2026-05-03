<?php
/**
 * Price Comparison Engine – real-time price aggregation for the Decision Platform.
 *
 * Aggregates prices from multiple sources (partner APIs + structured-data
 * scraping) and surfaces them on decision-type articles as an interactive
 * comparison table.  Supports price-alert subscriptions so visitors are
 * notified by email when a price drops below their target.
 *
 * Storage:
 *   pearblog_prices_{product_id}     – price snapshot array (with TTL).
 *   pearblog_price_alerts            – serialised alert subscriptions array.
 *
 * REST endpoints:
 *   GET  /pearblog/v1/prices/{product_id}          – fetch cached prices.
 *   POST /pearblog/v1/prices/{product_id}/refresh  – force refresh from sources.
 *   POST /pearblog/v1/prices/alert                 – subscribe to price alert.
 *   GET  /pearblog/v1/prices/compare               – compare multiple products.
 *
 * Shortcode:
 *   [pearblog_price_comparison product="product-id" title="Compare prices"]
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Aggregates, caches, and displays price comparisons.
 */
class PriceComparison {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Cache TTL in seconds (30 minutes). */
	private const CACHE_TTL = 1800;

	/** Option key for alert subscriptions. */
	private const OPTION_ALERTS = 'pearblog_price_alerts';

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks, REST routes, shortcodes, and cron.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_shortcode( 'pearblog_price_comparison', [ $this, 'render_shortcode' ] );

		// Hourly cron to refresh cached prices and send price alerts.
		if ( ! wp_next_scheduled( 'pearblog_price_refresh' ) ) {
			wp_schedule_event( time(), 'hourly', 'pearblog_price_refresh' );
		}
		add_action( 'pearblog_price_refresh', [ $this, 'refresh_all_and_send_alerts' ] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/prices/(?P<product>[a-zA-Z0-9_-]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'product' => [ 'required' => true, 'type' => 'string' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/prices/(?P<product>[a-zA-Z0-9_-]+)/refresh', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_refresh' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/prices/alert', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_alert' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'product'     => [ 'required' => true, 'type' => 'string' ],
				'email'       => [ 'required' => true, 'type' => 'string', 'format' => 'email' ],
				'target_price'=> [ 'required' => true, 'type' => 'number' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/prices/compare', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_compare' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'products' => [ 'required' => true, 'type' => 'string' ], // comma-separated.
			],
		] );
	}

	/**
	 * Permission – manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /prices/{product} – return cached price data.
	 */
	public function rest_get( \WP_REST_Request $request ): \WP_REST_Response {
		$product = sanitize_key( $request->get_param( 'product' ) );
		$prices  = $this->get_prices( $product );

		if ( empty( $prices ) ) {
			$prices = $this->fetch_and_cache( $product );
		}

		if ( is_wp_error( $prices ) ) {
			return new \WP_REST_Response( [ 'error' => $prices->get_error_message() ], 422 );
		}

		return new \WP_REST_Response( $prices, 200 );
	}

	/**
	 * POST /prices/{product}/refresh – force refresh.
	 */
	public function rest_refresh( \WP_REST_Request $request ): \WP_REST_Response {
		$product = sanitize_key( $request->get_param( 'product' ) );
		delete_transient( "pearblog_prices_{$product}" );
		$prices = $this->fetch_and_cache( $product );

		if ( is_wp_error( $prices ) ) {
			return new \WP_REST_Response( [ 'error' => $prices->get_error_message() ], 422 );
		}

		return new \WP_REST_Response( $prices, 200 );
	}

	/**
	 * POST /prices/alert – subscribe to a price alert.
	 */
	public function rest_alert( \WP_REST_Request $request ): \WP_REST_Response {
		$product      = sanitize_key( $request->get_param( 'product' ) );
		$email        = sanitize_email( $request->get_param( 'email' ) );
		$target_price = (float) $request->get_param( 'target_price' );

		if ( ! is_email( $email ) ) {
			return new \WP_REST_Response( [ 'error' => 'Invalid email address.' ], 400 );
		}

		$alerts = $this->get_alerts();
		$alerts[] = [
			'product'      => $product,
			'email'        => $email,
			'target_price' => $target_price,
			'created_at'   => time(),
		];
		update_option( self::OPTION_ALERTS, $alerts );

		return new \WP_REST_Response( [ 'subscribed' => true, 'product' => $product, 'target_price' => $target_price ], 200 );
	}

	/**
	 * GET /prices/compare?products=a,b,c – compare multiple products.
	 */
	public function rest_compare( \WP_REST_Request $request ): \WP_REST_Response {
		$product_list = array_map( 'sanitize_key', explode( ',', (string) $request->get_param( 'products' ) ) );
		$result       = [];

		foreach ( array_slice( $product_list, 0, 10 ) as $product ) {
			$prices = $this->get_prices( $product );
			if ( empty( $prices ) ) {
				$prices = $this->fetch_and_cache( $product );
			}
			if ( ! is_wp_error( $prices ) ) {
				$result[ $product ] = $prices;
			}
		}

		return new \WP_REST_Response( $result, 200 );
	}

	// -----------------------------------------------------------------------
	// Shortcode
	// -----------------------------------------------------------------------

	/**
	 * Render a price comparison table.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Inner content (unused).
	 * @return string         HTML output.
	 */
	public function render_shortcode( array $atts, string $content = '' ): string {
		$atts = shortcode_atts(
			[
				'product' => '',
				'title'   => 'Compare prices',
				'show_alert' => 'true',
			],
			$atts,
			'pearblog_price_comparison'
		);

		if ( ! $atts['product'] ) {
			return '';
		}

		$product = sanitize_key( $atts['product'] );
		$prices  = $this->get_prices( $product );

		if ( empty( $prices ) ) {
			$prices = $this->fetch_and_cache( $product );
		}

		if ( is_wp_error( $prices ) || empty( $prices['offers'] ) ) {
			return '<p class="pearblog-price-error">' . esc_html__( 'Price data not available.', 'pearblog-engine' ) . '</p>';
		}

		$title       = esc_html( $atts['title'] );
		$show_alert  = filter_var( $atts['show_alert'], FILTER_VALIDATE_BOOLEAN );
		$product_esc = esc_attr( $product );
		$nonce       = wp_create_nonce( 'pearblog_price_alert' );
		$rest_url    = esc_url( rest_url( 'pearblog/v1/prices/alert' ) );

		ob_start();
		?>
		<div class="pearblog-price-comparison" data-product="<?php echo $product_esc; ?>">
			<h3 class="pearblog-price-title"><?php echo $title; ?></h3>
			<table class="pearblog-price-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Provider', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Price', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Updated', 'pearblog-engine' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $prices['offers'] as $offer ) : ?>
					<tr>
						<td><?php echo esc_html( $offer['provider'] ); ?></td>
						<td class="pearblog-price-amount"><?php echo esc_html( $offer['currency'] . ' ' . number_format( (float) $offer['price'], 2 ) ); ?></td>
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i', $offer['updated_at'] ?? 0 ) ); ?></td>
						<td>
							<?php if ( ! empty( $offer['url'] ) ) : ?>
							<a href="<?php echo esc_url( $offer['url'] ); ?>" target="_blank" rel="noopener sponsored" class="pearblog-btn-cta">
								<?php esc_html_e( 'Check price', 'pearblog-engine' ); ?>
							</a>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $show_alert ) : ?>
			<div class="pearblog-price-alert-form">
				<p><?php esc_html_e( 'Get notified when the price drops:', 'pearblog-engine' ); ?></p>
				<form class="pearblog-alert-form" data-product="<?php echo $product_esc; ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-endpoint="<?php echo $rest_url; ?>">
					<input type="email" name="alert_email" placeholder="<?php esc_attr_e( 'Your email', 'pearblog-engine' ); ?>" required>
					<input type="number" name="alert_price" placeholder="<?php esc_attr_e( 'Target price', 'pearblog-engine' ); ?>" step="0.01" min="0" required>
					<button type="submit"><?php esc_html_e( 'Notify me', 'pearblog-engine' ); ?></button>
				</form>
			</div>
			<script>
			(function(){
				document.querySelectorAll('.pearblog-alert-form').forEach(function(form){
					form.addEventListener('submit', function(e){
						e.preventDefault();
						var data = {
							product:      form.dataset.product,
							email:        form.querySelector('[name=alert_email]').value,
							target_price: parseFloat(form.querySelector('[name=alert_price]').value)
						};
						fetch(form.dataset.endpoint, {
							method: 'POST',
							headers: {'Content-Type':'application/json','X-WP-Nonce': form.dataset.nonce},
							body: JSON.stringify(data)
						}).then(function(r){ return r.json(); }).then(function(res){
							form.innerHTML = '<p style="color:green"><?php echo esc_js( __( 'Alert set! We will notify you.', 'pearblog-engine' ) ); ?></p>';
						}).catch(function(){
							alert('<?php echo esc_js( __( 'Error setting alert. Please try again.', 'pearblog-engine' ) ); ?>');
						});
					});
				});
			})();
			</script>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// -----------------------------------------------------------------------
	// Cron: refresh & send alerts
	// -----------------------------------------------------------------------

	/**
	 * Refresh all tracked products and send price-drop alerts.
	 */
	public function refresh_all_and_send_alerts(): void {
		$alerts   = $this->get_alerts();
		$products = array_unique( array_column( $alerts, 'product' ) );

		foreach ( $products as $product ) {
			delete_transient( "pearblog_prices_{$product}" );
			$prices = $this->fetch_and_cache( $product );
			if ( is_wp_error( $prices ) ) {
				continue;
			}

			$lowest = $this->lowest_price( $prices );
			if ( null === $lowest ) {
				continue;
			}

			foreach ( $alerts as $idx => $alert ) {
				if ( $alert['product'] !== $product ) {
					continue;
				}
				if ( $lowest <= $alert['target_price'] ) {
					$this->send_price_alert( $alert, $lowest, $prices );
					// Remove fired alert.
					unset( $alerts[ $idx ] );
				}
			}
		}

		update_option( self::OPTION_ALERTS, array_values( $alerts ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Get cached prices for a product.
	 *
	 * @param string $product Product ID.
	 * @return array  Price data array or empty array.
	 */
	private function get_prices( string $product ): array {
		return (array) get_transient( "pearblog_prices_{$product}" );
	}

	/**
	 * Fetch fresh prices and cache them.
	 *
	 * Fires the `pearblog_price_fetch_{product}` filter so external integrations
	 * can supply real prices.  Falls back to a stub response.
	 *
	 * @param string $product Product ID.
	 * @return array|\WP_Error  Price data or WP_Error.
	 */
	private function fetch_and_cache( string $product ): array|\WP_Error {
		// Allow integrations to supply price data via a filter.
		$data = apply_filters( "pearblog_price_fetch_{$product}", null, $product );

		if ( null === $data ) {
			// Generic filter for all products.
			$data = apply_filters( 'pearblog_price_fetch', null, $product );
		}

		if ( null === $data ) {
			// Return empty structure so callers know there is no data yet.
			$data = [
				'product'    => $product,
				'offers'     => [],
				'fetched_at' => time(),
			];
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		set_transient( "pearblog_prices_{$product}", $data, self::CACHE_TTL );

		return (array) $data;
	}

	/**
	 * Return the lowest price from a price data array.
	 *
	 * @param array $prices Price data.
	 * @return float|null  Lowest price or null if no offers.
	 */
	private function lowest_price( array $prices ): ?float {
		if ( empty( $prices['offers'] ) ) {
			return null;
		}

		$amounts = array_column( $prices['offers'], 'price' );
		return empty( $amounts ) ? null : (float) min( $amounts );
	}

	/**
	 * Return all price-alert subscriptions.
	 *
	 * @return array
	 */
	private function get_alerts(): array {
		return (array) get_option( self::OPTION_ALERTS, [] );
	}

	/**
	 * Send a price-drop notification email.
	 *
	 * @param array $alert   Alert subscription.
	 * @param float $price   Current lowest price.
	 * @param array $prices  Full price data.
	 */
	private function send_price_alert( array $alert, float $price, array $prices ): void {
		$subject = sprintf( __( 'Price alert: %s dropped to %.2f', 'pearblog-engine' ), $alert['product'], $price );
		$message = sprintf(
			/* translators: %1$s product, %2$.2f price */
			__(
				"Good news! The price of %1\$s has dropped to %2\$.2f, which is at or below your target of %3\$.2f.\n\nCheck current offers at %4\$s",
				'pearblog-engine'
			),
			$alert['product'],
			$price,
			$alert['target_price'],
			home_url()
		);

		wp_mail( $alert['email'], $subject, $message );
	}
}
