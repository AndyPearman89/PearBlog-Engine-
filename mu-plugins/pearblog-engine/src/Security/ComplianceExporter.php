<?php
/**
 * Compliance Exporter – exports audit logs in GDPR/SOC2 formats.
 *
 * Generates structured compliance reports from the PipelineAuditLog data,
 * including user-data processing events, data retention information,
 * and system access records.
 *
 * Supported export formats:
 *  - JSON (machine-readable, SOC2-ready)
 *  - CSV (human-readable, GDPR-ready)
 *  - PDF summary (text-based, for internal review)
 *
 * REST endpoint:
 *   GET /pearblog/v1/compliance/export?format=json|csv&days=30
 *
 * @package PearBlogEngine\Security
 */

declare(strict_types=1);

namespace PearBlogEngine\Security;

use PearBlogEngine\Pipeline\PipelineAuditLog;

/**
 * Exports compliance-ready audit reports.
 */
class ComplianceExporter {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Maximum days of history to export. */
	private const MAX_EXPORT_DAYS = 365;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/compliance/export', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_export' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	/**
	 * Add compliance page to admin.
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'pearblog-engine',
			__( 'Compliance Export', 'pearblog-engine' ),
			__( 'Compliance', 'pearblog-engine' ),
			'manage_options',
			'pearblog-compliance',
			[ $this, 'render_admin_page' ]
		);
	}

	// -----------------------------------------------------------------------
	// Export generation
	// -----------------------------------------------------------------------

	/**
	 * Build compliance report data.
	 *
	 * @param int    $days   Number of days to include.
	 * @param string $format Export format (json|csv).
	 * @return array<string,mixed>
	 */
	public function build_report( int $days = 30, string $format = 'json' ): array {
		$days    = min( $days, self::MAX_EXPORT_DAYS );
		$cutoff  = time() - ( $days * DAY_IN_SECONDS );
		$events  = ( new PipelineAuditLog() )->get_events( 500 );

		// Filter by date.
		$filtered = array_filter( $events, fn( $e ) => ( $e['timestamp'] ?? 0 ) >= $cutoff );

		// Build compliance metadata.
		$by_level = array_count_values( array_column( $filtered, 'level' ) );
		$by_event = array_count_values( array_column( $filtered, 'event' ) );

		arsort( $by_event );

		return [
			'report_id'         => 'pearblog-compliance-' . date( 'Y-m-d' ),
			'generated_at'      => gmdate( 'c' ),
			'generated_by'      => get_option( 'admin_email' ),
			'site_url'          => get_site_url(),
			'period_days'       => $days,
			'period_from'       => gmdate( 'c', $cutoff ),
			'period_to'         => gmdate( 'c' ),
			'total_events'      => count( $filtered ),
			'events_by_level'   => $by_level,
			'events_by_type'    => array_slice( $by_event, 0, 20, true ),
			'data_retention'    => [
				'audit_log_max_entries' => PipelineAuditLog::MAX_ENTRIES,
				'audit_log_current'     => count( $events ),
				'policy'                => 'Ring buffer – oldest entries auto-purged.',
			],
			'events'            => array_values( $filtered ),
		];
	}

	/**
	 * Render report as CSV.
	 *
	 * @param array<string,mixed> $report Report data.
	 * @return string CSV content.
	 */
	public function to_csv( array $report ): string {
		$output = "\xEF\xBB\xBF"; // UTF-8 BOM.
		$output .= "# PearBlog Engine Compliance Report\n";
		$output .= "# Report ID: {$report['report_id']}\n";
		$output .= "# Generated: {$report['generated_at']}\n";
		$output .= "# Period: {$report['period_from']} to {$report['period_to']}\n\n";

		$output .= "Event ID,Timestamp,Event Type,Level,Context\n";

		foreach ( $report['events'] as $event ) {
			$context = wp_json_encode( $event['context'] ?? [] );
			$ts      = gmdate( 'Y-m-d H:i:s', $event['timestamp'] ?? 0 );
			$output .= implode( ',', [
				'"' . ( $event['id'] ?? '' ) . '"',
				'"' . $ts . '"',
				'"' . ( $event['event'] ?? '' ) . '"',
				'"' . ( $event['level'] ?? '' ) . '"',
				'"' . str_replace( '"', '""', (string) $context ) . '"',
			] ) . "\n";
		}

		return $output;
	}

	// -----------------------------------------------------------------------
	// REST callback
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_export( \WP_REST_Request $request ): \WP_REST_Response {
		$format = (string) ( $request->get_param( 'format' ) ?? 'json' );
		$days   = (int) ( $request->get_param( 'days' ) ?? 30 );

		$report = $this->build_report( $days, $format );

		if ( 'csv' === $format ) {
			return new \WP_REST_Response( [
				'format'   => 'csv',
				'filename' => "pearblog-compliance-{$report['report_id']}.csv",
				'content'  => $this->to_csv( $report ),
			], 200 );
		}

		return new \WP_REST_Response( $report, 200 );
	}

	/**
	 * Render admin export page.
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '📋 Compliance Export', 'pearblog-engine' ); ?></h1>
			<p><?php esc_html_e( 'Export audit logs in GDPR/SOC2 compliant formats.', 'pearblog-engine' ); ?></p>

			<form method="get" action="<?php echo esc_url( rest_url( 'pearblog/v1/compliance/export' ) ); ?>">
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Format', 'pearblog-engine' ); ?></th>
						<td>
							<select name="format">
								<option value="json">JSON (SOC2)</option>
								<option value="csv">CSV (GDPR)</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Days', 'pearblog-engine' ); ?></th>
						<td>
							<input type="number" name="days" value="30" min="1" max="365">
							<p class="description"><?php esc_html_e( 'Number of days to include in the report.', 'pearblog-engine' ); ?></p>
						</td>
					</tr>
				</table>
				<?php wp_nonce_field( 'pearblog_compliance_export' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Download Report', 'pearblog-engine' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}
